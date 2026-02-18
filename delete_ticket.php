<?php
require_once 'db.php';
require_once 'includes/auth.php';

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: tickets.php');
    exit;
}

if (!csrf_verify()) {
    $_SESSION['flash_error'] = 'Invalid request (CSRF). Please try again.';
    header('Location: tickets.php');
    exit;
}

$ticket_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($ticket_id <= 0) {
    $_SESSION['flash_error'] = 'Invalid ticket ID.';
    header('Location: tickets.php');
    exit;
}

// Check permissions: Admin or Owner
if ($user['role'] === 'admin') {
    $stmt = $conn->prepare('SELECT id FROM tickets WHERE id = ?');
    $stmt->bind_param('i', $ticket_id);
} else {
    $stmt = $conn->prepare('SELECT id FROM tickets WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $ticket_id, $user['id']);
}

$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    $_SESSION['flash_error'] = 'Ticket not found or permission denied.';
    header('Location: tickets.php');
    exit;
}

// Delete ticket (CASCADE will handle attachments usually, but let's rely on DB constraints)
$del = $conn->prepare('DELETE FROM tickets WHERE id = ?');
$del->bind_param('i', $ticket_id);

if ($del->execute()) {
    $_SESSION['flash_success'] = 'Ticket deleted successfully.';
} else {
    $_SESSION['flash_error'] = 'Failed to delete ticket.';
}

header('Location: tickets.php');
exit;
