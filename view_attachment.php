<?php
require_once 'db.php';
require_once 'includes/auth.php';
$user = require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
  header('HTTP/1.1 404 Not Found');
  exit;
}

$stmt = $conn->prepare('SELECT a.*, t.user_id FROM ticket_attachments a JOIN tickets t ON t.id = a.ticket_id WHERE a.id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) {
  header('HTTP/1.1 404 Not Found');
  exit;
}

$can_access = ($user['role'] === 'admin' || $user['role'] === 'technician') || ((int) $row['user_id'] === (int) $user['id']);
if (!$can_access) {
  header('HTTP/1.1 403 Forbidden');
  exit;
}

$path = __DIR__ . '/uploads/tickets/' . (int) $row['ticket_id'] . '/' . $row['filename'];
if (!is_file($path)) {
  header('HTTP/1.1 404 Not Found');
  exit;
}

$mimes = [
  'jpg' => 'image/jpeg',
  'jpeg' => 'image/jpeg',
  'png' => 'image/png',
  'gif' => 'image/gif',
  'webp' => 'image/webp'
];
$ext = strtolower(pathinfo($row['filename'], PATHINFO_EXTENSION));
$mime = $mimes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . basename($row['original_name']) . '"');
readfile($path);
exit;
