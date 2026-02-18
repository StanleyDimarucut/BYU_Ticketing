<?php
require_once 'db.php';
require_once 'includes/auth.php';

$user = require_login();

// Handle "Mark All Read"
if (isset($_POST['mark_all'])) {
    if (!csrf_verify()) {
        $_SESSION['flash_error'] = "Invalid CSRF token.";
        header("Location: notifications.php");
        exit;
    }

    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    header("Location: notifications.php");
    exit;
}

// Handle Single Notification Link Click
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id > 0) {
    // Check ownership and fetch link
    $stmt = $conn->prepare("SELECT link FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user['id']);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        // Mark as read
        $upd = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $upd->bind_param("i", $id);
        $upd->execute();

        $link = $row['link'] ? $row['link'] : 'notifications.php';
        header("Location: " . $link);
        exit;
    }
}

header("Location: notifications.php");
exit;
