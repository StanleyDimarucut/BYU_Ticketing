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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $_SESSION['flash_error'] = 'Invalid CSRF token.';
        header("Location: kb.php");
        exit;
    }

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM kb_articles WHERE id = ?");
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Article deleted successfully.';
        } else {
            $_SESSION['flash_error'] = 'Error deleting article: ' . $conn->error;
        }
    } else {
        $_SESSION['flash_error'] = 'Invalid article ID.';
    }
}

header("Location: kb.php");
exit;
