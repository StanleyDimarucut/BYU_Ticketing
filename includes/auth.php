<?php
// Callers must include db.php first (session + $conn).

function require_login()
{
  if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
  }
  return $_SESSION['user'];
}

function require_admin()
{
  $user = require_login();
  if (($user['role'] ?? '') !== 'admin') {
    header('Location: dashboard.php');
    exit;
  }
  return $user;
}

function require_staff()
{
  $user = require_login();
  if (!in_array(($user['role'] ?? ''), ['admin', 'technician'])) {
    header('Location: dashboard.php');
    exit;
  }
  return $user;
}

function csrf_token()
{
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function csrf_verify()
{
  $token = $_POST['csrf_token'] ?? '';
  return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function status_badge_class($status)
{
  $st = strtolower((string) $status);
  $base = 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border border-[#262626]';
  if (strpos($st, 'open') !== false)
    return $base . ' bg-[#f5e6a3] text-[#262626]';
  if (strpos($st, 'progress') !== false)
    return $base . ' bg-white text-[#262626]';
  if (strpos($st, 'close') !== false)
    return $base . ' bg-[#262626] text-[#f5e6a3]';
  return $base . ' bg-white text-[#262626]';
}
