<?php
require_once 'db.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: tickets.php');
  exit;
}

$user = require_staff();
if (!csrf_verify()) {
  $_SESSION['flash_error'] = 'Invalid request. Please try again.';
  header('Location: tickets.php');
  exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$status = $_POST['status'] ?? '';
$allowed = ['Open', 'In Progress', 'Closed'];
if (!in_array($status, $allowed)) {
  $status = 'Open';
}

$ok = false;
if ($id > 0) {
  $stmt = $conn->prepare('UPDATE tickets SET status = ? WHERE id = ?');
  $stmt->bind_param('si', $status, $id);
  $ok = $stmt->execute();
}

if ($ok) {
  log_ticket_activity($conn, $id, $user['id'], 'Status Update', "Status changed to \"{$status}\"");

  // Notify Ticket Owner
  // Need to fetch owner_id first.
  $owner_q = $conn->query("SELECT user_id, subject FROM tickets WHERE id = $id");
  if ($owner_q && $row = $owner_q->fetch_assoc()) {
    send_notification($conn, $row['user_id'], "Ticket #{$id} ({$row['subject']}) status updated to \"{$status}\"", "tickets.php?id=$id");
  }

  $_SESSION['flash_success'] = "Ticket #{$id} updated to \"{$status}\".";
} else {
  $_SESSION['flash_error'] = "Failed to update ticket #{$id}.";
}
header('Location: tickets.php' . ($id > 0 ? '?id=' . $id : ''));
exit;
