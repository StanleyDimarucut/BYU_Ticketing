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

    $search = $_GET['search'] ?? '';

    // Base query
    $sql = "SELECT id, title FROM kb_articles";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $sql .= " WHERE title LIKE ? OR content LIKE ?";
        $searchTerm = "%" . $search . "%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }

    // Always sort by newest and limit
    $sql .= " ORDER BY created_at DESC LIMIT 10";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $articles = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

    echo json_encode([
        'success' => true,
        'articles' => $articles
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
