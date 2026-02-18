<?php
require_once 'db.php';
require_once 'includes/auth.php';
$user = require_login();

// Only Admins and Technicians can access KB
if ($user['role'] !== 'admin' && $user['role'] !== 'technician') {
    $_SESSION['flash_error'] = 'Access denied.';
    header("Location: index.php");
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$article = null;
$page_title = 'Create Article';
$title_val = '';
$content_val = '';

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM kb_articles WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $article = $stmt->get_result()->fetch_assoc();

    if (!$article) {
        $_SESSION['flash_error'] = 'Article not found.';
        header("Location: kb.php");
        exit;
    }

    $page_title = 'Edit Article';
    $title_val = $article['title'];
    $content_val = $article['content'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $_SESSION['flash_error'] = 'Invalid CSRF token.';
        header("Location: kb.php");
        exit;
    }

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($title) || empty($content)) {
        $error = "Title and Content are required.";
        $title_val = $title;
        $content_val = $content;
    } else {
        if ($id > 0) {
            // Update
            $stmt = $conn->prepare("UPDATE kb_articles SET title = ?, content = ? WHERE id = ?");
            $stmt->bind_param('ssi', $title, $content, $id);
            if ($stmt->execute()) {
                $_SESSION['flash_success'] = 'Article updated successfully.';
                header("Location: kb_article.php?id=$id");
                exit;
            } else {
                $error = "db error: " . $conn->error;
            }
        } else {
            // Create
            $stmt = $conn->prepare("INSERT INTO kb_articles (author_id, title, content) VALUES (?, ?, ?)");
            $stmt->bind_param('iss', $user['id'], $title, $content);
            if ($stmt->execute()) {
                $new_id = $stmt->insert_id;
                $_SESSION['flash_success'] = 'Article created successfully.';
                header("Location: kb_article.php?id=$new_id");
                exit;
            } else {
                $error = "db error: " . $conn->error;
            }
        }
    }
}

$page_heading = $page_title;
$page_subtitle = '';
require_once 'includes/header.php';
?>

<div class="mb-6">
    <a href="kb.php" class="inline-flex items-center text-[#525252] hover:text-[#262626] font-medium transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Back to Knowledge Base
    </a>
</div>

<div class="max-w-3xl mx-auto">
    <div class="bg-white border-2 border-[#262626] rounded-xl p-8 shadow-sm">
        <?php if (!empty($error)): ?>
            <div class="bg-red-50 text-red-600 p-4 rounded-lg mb-6 text-sm border border-red-200">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

            <div class="mb-6">
                <label for="title" class="block text-sm font-bold text-[#262626] mb-2">Title</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($title_val) ?>" required
                    class="w-full px-4 py-3 border-2 border-[#262626] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f5e6a3] text-[#262626] text-lg font-medium placeholder-gray-400"
                    placeholder="Enter article title...">
            </div>

            <div class="mb-8">
                <label for="content" class="block text-sm font-bold text-[#262626] mb-2">Content</label>
                <textarea id="content" name="content" rows="15" required
                    class="w-full px-4 py-3 border-2 border-[#262626] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f5e6a3] text-[#262626] placeholder-gray-400 font-mono text-sm leading-relaxed"
                    placeholder="Write your article content here..."><?= htmlspecialchars($content_val) ?></textarea>
                <p class="mt-2 text-xs text-[#525252]">You can use plain text. HTML/Markdown is not currently parsed,
                    but line breaks are preserved.</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-6 border-t border-[#262626]/10">
                <a href="kb.php"
                    class="px-6 py-2.5 rounded-lg border-2 border-transparent text-[#525252] font-bold hover:text-[#262626] transition-colors">
                    Cancel
                </a>
                <button type="submit"
                    class="px-8 py-2.5 bg-[#eab308] text-[#262626] font-bold rounded-lg border-2 border-[#262626] hover:bg-[#ca8a04] hover:text-white transition-colors shadow-sm">
                    <?= $id > 0 ? 'Update Article' : 'Publish Article' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>