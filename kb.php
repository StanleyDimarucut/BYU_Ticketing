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

$search = $_GET['search'] ?? '';

$sql = "SELECT k.*, u.name as author_name FROM kb_articles k LEFT JOIN users u ON u.id = k.author_id";
$params = [];
$types = "";

if ($search) {
    $sql .= " WHERE k.title LIKE ? OR k.content LIKE ?";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

$sql .= " ORDER BY k.created_at DESC";

if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($sql);
}

$articles = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

$page_title = 'Knowledge Base';
$page_heading = 'Knowledge Base';
$page_subtitle = 'Technician Resources & Documentation';
require_once 'includes/header.php';
?>

<section aria-labelledby="kb-heading">
    <div class="flex flex-col sm:flex-row items-center justify-between mb-6 gap-4">
        <h2 id="kb-heading" class="sr-only">Knowledge Base Articles</h2>

        <!-- Search -->
        <form action="kb.php" method="GET" class="w-full sm:w-auto flex-1 max-w-md">
            <div class="relative">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search articles..."
                    class="w-full pl-10 pr-4 py-2 border-2 border-[#262626] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f5e6a3] text-[#262626]">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </form>

        <a href="kb_manage.php"
            class="inline-flex items-center gap-2 px-4 py-2 bg-[#eab308] text-[#262626] font-bold rounded-lg border-2 border-[#262626] hover:bg-[#ca8a04] hover:text-white transition-colors shadow-sm whitespace-nowrap">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            New Article
        </a>
    </div>

    <?php if (empty($articles)): ?>
        <div class="bg-white border-2 border-[#262626] rounded-xl p-12 text-center shadow-sm">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[#f5e6a3]/30 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-[#eab308]" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-[#262626] mb-2">No articles found</h3>
            <p class="text-[#525252] mb-6">Start building your knowledge base by creating the first article.</p>
            <a href="kb_manage.php" class="text-[#eab308] font-bold hover:underline">Create Article</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($articles as $article): ?>
                <a href="kb_article.php?id=<?= $article['id'] ?>"
                    class="group block bg-white border-2 border-[#262626] rounded-xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all h-full flex flex-col">
                    <h3 class="text-lg font-bold text-[#262626] mb-2 group-hover:text-[#eab308] transition-colors line-clamp-2">
                        <?= htmlspecialchars($article['title']) ?>
                    </h3>
                    <p class="text-sm text-[#525252] mb-4 line-clamp-3 flex-1 break-words">
                        <?= htmlspecialchars(mb_strimwidth(strip_tags($article['content']), 0, 150, "...")) ?>
                    </p>
                    <div
                        class="flex items-center justify-between mt-auto pt-4 border-t border-[#262626]/10 text-xs text-[#525252]">
                        <span class="font-semibold">
                            <?= htmlspecialchars($article['author_name'] ?? 'Unknown') ?>
                        </span>
                        <span>
                            <?= date('M j, Y', strtotime($article['created_at'])) ?>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>