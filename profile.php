<?php
require_once 'db.php';
require_once 'includes/auth.php';

$user = require_login();
$user_id = $user['id'];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];
    if (empty($name))
        $errors[] = "Name is required.";
    if (empty($username))
        $errors[] = "Username is required.";

    // Check if username is taken by another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->bind_param('si', $username, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Username is already taken.";
    }

    if (empty($errors)) {
        // If password fields are filled, validating them
        if (!empty($new_password)) {
            if (empty($current_password)) {
                $errors[] = "Current password is required to set a new password.";
            } elseif (empty($confirm_password)) {
                $errors[] = "Please confirm your new password.";
            } elseif ($new_password !== $confirm_password) {
                $errors[] = "New password and confirmation do not match.";
            } else {
                // Verify current password
                $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    if (!password_verify($current_password, $row['password'])) {
                        $errors[] = "Current password is incorrect.";
                    }
                } else {
                    $errors[] = "User not found.";
                }
            }
        }
    }

    if (empty($errors)) {
        if (!empty($new_password)) {
            // Update with password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, password = ? WHERE id = ?");
            $stmt->bind_param('sssi', $name, $username, $hashed_password, $user_id);
        } else {
            // Update without password
            $stmt = $conn->prepare("UPDATE users SET name = ?, username = ? WHERE id = ?");
            $stmt->bind_param('ssi', $name, $username, $user_id);
        }

        if ($stmt->execute()) {
            // Update session data
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['username'] = $username;

            $_SESSION['flash_success'] = "Profile updated successfully.";
            header("Location: profile.php");
            exit;
        } else {
            $errors[] = "Database update failed: " . $conn->error;
        }
    }
}

$page_title = 'My Profile';
$page_heading = 'My Profile';
$page_subtitle = 'Manage your account details';
require_once 'includes/header.php';
?>

<div class="max-w-2xl mx-auto">

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-md shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700">
                        <?= htmlspecialchars($_SESSION['flash_success']) ?>
                    </p>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-md shadow-sm" role="alert">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc list-inside space-y-1">
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <section class="bg-white border-2 border-[#262626] rounded-xl p-8 shadow-sm">
        <form action="profile.php" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

            <div>
                <label for="name" class="block text-sm font-bold text-[#262626] mb-1">Full Name</label>
                <input type="text" id="name" name="name"
                    value="<?= htmlspecialchars($_POST['name'] ?? $user['name']) ?>"
                    class="w-full px-4 py-2 border-2 border-[#262626] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f5e6a3] transition-shadow"
                    required>
            </div>

            <div>
                <label for="username" class="block text-sm font-bold text-[#262626] mb-1">Username</label>
                <input type="text" id="username" name="username"
                    value="<?= htmlspecialchars($_POST['username'] ?? $user['username']) ?>"
                    class="w-full px-4 py-2 border-2 border-[#262626] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f5e6a3] transition-shadow"
                    required>
            </div>

            <div class="pt-6 border-t border-gray-200 mt-6">
                <h3 class="text-lg font-bold text-[#262626] mb-4">Change Password</h3>

                <div class="space-y-4">
                    <div>
                        <label for="current_password" class="block text-sm font-bold text-[#262626] mb-1">Current
                            Password</label>
                        <input type="password" id="current_password" name="current_password"
                            class="w-full px-4 py-2 border-2 border-[#262626] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f5e6a3] transition-shadow"
                            placeholder="Required to change password">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="new_password" class="block text-sm font-bold text-[#262626] mb-1">New
                                Password</label>
                            <input type="password" id="new_password" name="new_password"
                                class="w-full px-4 py-2 border-2 border-[#262626] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f5e6a3] transition-shadow"
                                placeholder="Min. 8 characters recommended">
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-bold text-[#262626] mb-1">Confirm
                                New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password"
                                class="w-full px-4 py-2 border-2 border-[#262626] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f5e6a3] transition-shadow"
                                placeholder="Re-enter new password">
                        </div>
                    </div>
                </div>
                <p class="text-xs text-[#525252] mt-2">
                    Leave these fields blank if you only want to update your profile details.
                </p>
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit"
                    class="px-6 py-2.5 bg-[#eab308] text-[#262626] font-bold uppercase tracking-wider rounded-lg shadow-md hover:bg-[#ca8a04] transition-colors"
                    name="update_profile">
                    Update Profile
                </button>
            </div>
        </form>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>