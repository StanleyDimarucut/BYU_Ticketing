<?php
require_once 'db.php';
if (!empty($_SESSION['user'])) {
  header('Location: dashboard.php');
  exit;
}


$error = '';
$success = $_GET['registered'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $pass = $_POST['password'] ?? '';

  $stmt = $conn->prepare('SELECT * FROM users WHERE username = ?');
  $stmt->bind_param('s', $username);
  $stmt->execute();
  $res = $stmt->get_result();
  $user = $res->fetch_assoc();

  if ($user && password_verify($pass, $user['password'])) {
    unset($user['password']);
    $_SESSION['user'] = $user;
    header('Location: dashboard.php');
    exit;
  }
  $error = 'Invalid username or password';
}

$page_title = 'Login';
$page_heading = 'Sign In';
$page_subtitle = 'Enter your credentials to continue';
require_once 'includes/header.php';
?>

<?php if ($error): ?>
  <div class="max-w-md mx-auto mb-6">
    <div class="bg-red-50 border-l-4 border-red-500 p-4 shadow-sm">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
              clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm text-red-700 font-medium"><?= htmlspecialchars($error) ?></p>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ($success): ?>
  <div class="max-w-md mx-auto mb-6">
    <div class="bg-green-50 border-l-4 border-green-500 p-4 shadow-sm">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
              clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm text-green-700 font-medium"><?= htmlspecialchars($success) ?></p>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="max-w-md mx-auto mt-20">
  <div
    class="bg-white border-2 border-[#262626] rounded-xl overflow-hidden shadow-[8px_8px_0_0_rgba(38,38,38,1)] transform transition-transform hover:-translate-y-1">
    <div class="p-8">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-black text-[#262626] mb-2 uppercase tracking-tight">Welcome Back</h1>
        <p class="text-[#525252]">Sign in to access your dashboard</p>
      </div>

      <form method="POST" class="space-y-6" novalidate>
        <div>
          <label for="username"
            class="block text-xs font-bold text-[#525252] uppercase tracking-wider mb-2">Username</label>
          <input id="username" name="username" type="text" placeholder="Enter your username" required
            autocomplete="username"
            class="w-full px-4 py-3 border border-[#262626]/20 rounded-lg bg-gray-50 text-[#262626] focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#f5e6a3] focus:border-transparent transition-all duration-200">
        </div>
        <div>
          <div class="flex items-center justify-between mb-2">
            <label for="password"
              class="block text-xs font-bold text-[#525252] uppercase tracking-wider">Password</label>
          </div>
          <input id="password" name="password" type="password" placeholder="••••••••" required
            autocomplete="current-password"
            class="w-full px-4 py-3 border border-[#262626]/20 rounded-lg bg-gray-50 text-[#262626] focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#f5e6a3] focus:border-transparent transition-all duration-200">
        </div>
        <div class="pt-2">
          <button type="submit"
            class="w-full py-3.5 bg-[#f5e6a3] text-[#262626] font-bold uppercase tracking-wider rounded-lg border-2 border-[#262626] hover:bg-[#262626] hover:text-[#f5e6a3] shadow-md hover:-translate-y-0.5 transition-all duration-200">
            Sign In
          </button>
        </div>
      </form>

      <div class="mt-8 pt-6 border-t border-[#262626]/10 text-center">
        <p class="text-sm text-[#525252]">
          Don't have an account?
          <a href="register.php"
            class="font-bold text-[#262626] hover:text-[#eab308] underline decoration-2 decoration-[#f5e6a3] underline-offset-2 transition-colors">Create
            one now</a>
        </p>
      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
