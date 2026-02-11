<?php
require_once 'db.php';
if (!empty($_SESSION['user'])) {
  header('Location: dashboard.php');
  exit;
}

$error = '';
$success = $_GET['registered'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($name === '' || $username === '' || $password === '') {
    $error = 'All fields are required.';
  } elseif (strlen($password) < 8) {
    $error = 'Password must be at least 8 characters.';
  } else {
    $check = $conn->prepare('SELECT id FROM users WHERE username = ?');
    $check->bind_param('s', $username);
    $check->execute();
    if ($check->get_result()->fetch_assoc()) {
      $error = 'An account with that username already exists.';
    } else {
      $role = $_POST['role'] ?? 'help_desk';
      if (!in_array($role, ['help_desk', 'technician', 'admin'])) {
        $role = 'help_desk';
      }
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare('INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)');
      $stmt->bind_param('ssss', $name, $username, $hash, $role);
      if ($stmt->execute()) {
        // Notify Admins
        broadcast_notification($conn, 'admin', "New User Registered: $name ($username)", "users.php");

        header('Location: index.php?registered=' . urlencode('Account created. Please sign in.'));
        exit;
      }
      $error = 'Registration failed. Please try again.';
    }
  }
}

$page_title = 'Register';
$page_heading = 'Create Account';
$page_subtitle = 'Sign up to submit tickets';
require_once 'includes/header.php';
?>

<?php if ($error): ?>
  <div class="bg-white border border-[#262626] text-[#262626] rounded-lg p-4 my-4 text-sm max-w-xl mx-auto" role="alert">
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>
<?php if ($success): ?>
  <div class="bg-[#f5e6a3] border border-[#262626] text-[#262626] rounded-lg p-4 my-4 text-sm max-w-xl mx-auto"
    role="status"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="max-w-xl mx-auto">
  <div class="bg-white border border-[#262626] rounded-lg p-6 md:p-8 shadow-sm">
    <form method="POST" class="space-y-5" novalidate>
      <div>
        <label for="name" class="block text-sm font-medium text-[#262626] mb-1">Full name</label>
        <input id="name" name="name" placeholder="Name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
          autocomplete="name"
          class="w-full px-3 py-2 border border-[#262626] rounded-lg bg-white text-[#262626] focus:outline-none focus:ring-2 focus:ring-[#eab308]/50 focus:border-[#262626]">
      </div>
      <div>
        <label for="username" class="block text-sm font-medium text-[#262626] mb-1">Username</label>
        <input id="username" name="username" type="text" placeholder="username" required
          value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autocomplete="username"
          class="w-full px-3 py-2 border border-[#262626] rounded-lg bg-white text-[#262626] focus:outline-none focus:ring-2 focus:ring-[#eab308]/50 focus:border-[#262626]">
      </div>
      <div>
        <label for="password" class="block text-sm font-medium text-[#262626] mb-1">Password</label>
        <input id="password" name="password" type="password" placeholder="At least 8 characters" required minlength="8"
          autocomplete="new-password"
          class="w-full px-3 py-2 border border-[#262626] rounded-lg bg-white text-[#262626] focus:outline-none focus:ring-2 focus:ring-[#eab308]/50 focus:border-[#262626]">
      </div>
      <div>
        <label for="role" class="block text-sm font-medium text-[#262626] mb-1">Role</label>
        <div class="relative">
          <select id="role" name="role" required
            class="w-full px-3 py-2 border border-[#262626] rounded-lg bg-white text-[#262626] focus:outline-none focus:ring-2 focus:ring-[#eab308]/50 focus:border-[#262626] appearance-none">
            <option value="help_desk">Help Desk (Standard User)</option>
            <option value="technician">Technician</option>
            <option value="admin">Admin</option>
          </select>
          <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-[#525252]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
          </div>
        </div>
      </div>
      <div class="pt-2">
        <button type="submit"
          class="inline-block px-6 py-2.5 rounded-lg bg-[#eab308] text-[#262626] font-semibold shadow-md hover:bg-[#ca8a04] hover:shadow-lg transition-all">Register</button>
      </div>
    </form>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>