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

if ($id > 0) {
    $stmt = $conn->prepare("SELECT k.*, u.name as author_name FROM kb_articles k LEFT JOIN users u ON u.id = k.author_id WHERE k.id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $article = $stmt->get_result()->fetch_assoc();
}

if (!$article) {
    $_SESSION['flash_error'] = 'Article not found.';
    header("Location: kb.php");
    exit;
}

$page_title = $article['title'];
$page_heading = 'Knowledge Base';
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

<article class="bg-white border-2 border-[#262626] rounded-xl p-8 shadow-sm">
    <header class="mb-8 pb-6 border-b border-[#262626]/10">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-[#262626] mb-2">
                    <?= htmlspecialchars($article['title']) ?>
                </h1>
                <div class="flex items-center text-sm text-[#525252] gap-4">
                    <span class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <?= htmlspecialchars($article['author_name'] ?? 'Unknown') ?>
                    </span>
                    <span class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <?= date('F j, Y', strtotime($article['created_at'])) ?>
                    </span>
                    <?php if ($article['updated_at'] && $article['updated_at'] != $article['created_at']): ?>
                        <span class="italic text-xs">(Updated:
                            <?= date('M j, Y', strtotime($article['updated_at'])) ?>)
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="kb_manage.php?id=<?= $article['id'] ?>"
                    class="inline-flex items-center px-3 py-1.5 rounded-lg border border-[#262626] bg-white text-[#262626] text-sm font-bold hover:bg-[#262626] hover:text-[#f5e6a3] transition-colors">
                    Edit
                </a>
                <form action="kb_delete.php" method="POST"
                    onsubmit="return confirm('Are you sure you want to delete this article?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= $article['id'] ?>">
                    <button type="submit"
                        class="inline-flex items-center px-3 py-1.5 rounded-lg border border-[#262626] bg-white text-red-600 text-sm font-bold hover:bg-red-600 hover:text-white transition-colors">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </header>

    <div class="prose max-w-none text-[#262626] leading-relaxed whitespace-pre-wrap">
        <?= nl2br(htmlspecialchars($article['content'])) ?>
    </div>
</article>

<?php require_once 'includes/footer.php'; ?>