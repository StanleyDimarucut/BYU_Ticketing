<?php
require_once 'db.php';
require_once 'includes/auth.php';

$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $_SESSION['flash_error'] = "Invalid CSRF token.";
        header("Location: users.php");
        exit;
    }

    $delete_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    if ($delete_id <= 0) {
        $_SESSION['flash_error'] = "Invalid user ID.";
        header("Location: users.php");
        exit;
    }

    if ($delete_id === $user['id']) {
        $_SESSION['flash_error'] = "You cannot delete your own account.";
        header("Location: users.php");
        exit;
    }

    // Prepare delete statement
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('i', $delete_id);

    if ($stmt->execute()) {
        $_SESSION['flash_success'] = "User deleted successfully.";
    } else {
        $_SESSION['flash_error'] = "Failed to delete user: " . $conn->error;
    }
}

header("Location: users.php");
exit;
