<?php
require_once 'db.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

try {
    $user = require_login();

    // Only Admins and Technicians can access KB
    if ($user['role'] !== 'admin' && $user['role'] !== 'technician') {
        throw new Exception('Access denied');
    }

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id <= 0) {
        throw new Exception('Invalid article ID');
    }

    $stmt = $conn->prepare("SELECT k.*, u.name as author_name FROM kb_articles k LEFT JOIN users u ON u.id = k.author_id WHERE k.id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $article = $stmt->get_result()->fetch_assoc();

    if (!$article) {
        throw new Exception('Article not found');
    }

    echo json_encode([
        'success' => true,
        'article' => [
            'id' => $article['id'],
            'title' => $article['title'],
            'content_html' => nl2br(htmlspecialchars($article['content'])), // Pre-process for display
            'author_name' => $article['author_name'] ?? 'Unknown',
            'created_at' => date('F j, Y', strtotime($article['created_at'])),
            'updated_at' => ($article['updated_at'] && $article['updated_at'] != $article['created_at'])
                ? date('M j, Y', strtotime($article['updated_at']))
                : null
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
