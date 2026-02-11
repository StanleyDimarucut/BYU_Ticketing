<?php
require_once 'db.php';
require_once 'includes/auth.php';

$user = require_admin();

$edit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$edit_user = null;

if ($edit_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $edit_user = $stmt->get_result()->fetch_assoc();
}

if (!$edit_user) {
    $_SESSION['flash_error'] = "User not found.";
    header("Location: users.php");
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $password = $_POST['password'] ?? '';

    $errors = [];
    if (empty($name))
        $errors[] = "Name is required.";
    if (empty($username))
        $errors[] = "Username is required.";

    // Check if username is taken by another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->bind_param('si', $username, $edit_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Username is already taken.";
    }

    if (empty($errors)) {
        if (!empty($password)) {
            // Update with password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, role = ?, password = ? WHERE id = ?");
            $stmt->bind_param('ssssi', $name, $username, $role, $hashed_password, $edit_id);
        } else {
            // Update without password
            $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, role = ? WHERE id = ?");
            $stmt->bind_param('sssi', $name, $username, $role, $edit_id);
        }

        if ($stmt->execute()) {
            $_SESSION['flash_success'] = "User updated successfully.";
            header("Location: users.php");
            exit;
        } else {
            $errors[] = "Database update failed: " . $conn->error;
        }
    }
}

$page_title = 'Edit User';
$page_heading = 'Edit User';
$page_subtitle = 'Editing user #' . $edit_user['id'];
require_once 'includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <a href="users.php"
        class="inline-block mb-6 text-sm font-bold text-[#525252] hover:text-[#eab308] transition-colors">
        ← Back to Users
    </a>

    <?php if (!empty($errors)): ?>
        <div class="bg-white border-2 border-red-500 text-red-600 rounded-xl p-4 mb-6 text-sm" role="alert">
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $e): ?>
                    <li>
                        <?= htmlspecialchars($e) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <section class="bg-white border-2 border-[#262626] rounded-xl p-8 shadow-sm">
        <form action="edit_user.php?id=<?= $edit_user['id'] ?>" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

            <div>
                <label for="name" class="block text-sm font-bold text-[#262626] mb-1">Full Name</label>
                <input type="text" id="name" name="name"
                    value="<?= htmlspecialchars($_POST['name'] ?? $edit_user['name']) ?>"
                    class="w-full px-4 py-2 border-2 border-[#262626] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f5e6a3] transition-shadow"
                    required>
            </div>

            <div>
                <label for="username" class="block text-sm font-bold text-[#262626] mb-1">Username</label>
                <input type="text" id="username" name="username"
                    value="<?= htmlspecialchars($_POST['username'] ?? $edit_user['username']) ?>"
                    class="w-full px-4 py-2 border-2 border-[#262626] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f5e6a3] transition-shadow"
                    required>
            </div>

            <div>
                <label for="role" class="block text-sm font-bold text-[#262626] mb-1">Role</label>
                <select id="role" name="role"
                    class="w-full px-4 py-2 border-2 border-[#262626] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f5e6a3] transition-shadow bg-white">
                    <option value="user" <?= ($edit_user['role'] === 'user') ? 'selected' : '' ?>>User</option>
                    <option value="technician" <?= ($edit_user['role'] === 'technician') ? 'selected' : '' ?>>Technician
                    </option>
                    <option value="admin" <?= ($edit_user['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                </select>
                <p class="text-xs text-[#525252] mt-1">
                    <strong>User:</strong> Can create tickets.
                    <strong>Technician:</strong> Can view/reply to assigned tickets.
                    <strong>Admin:</strong> Full access.
                </p>
            </div>

            <div class="pt-4 border-t border-gray-200">
                <label for="password" class="block text-sm font-bold text-[#262626] mb-1">New Password
                    (Optional)</label>
                <input type="password" id="password" name="password"
                    class="w-full px-4 py-2 border-2 border-[#262626] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f5e6a3] transition-shadow"
                    placeholder="Leave blank to keep current password">
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit"
                    class="px-6 py-2.5 bg-[#eab308] text-[#262626] font-bold uppercase tracking-wider rounded-lg shadow-md hover:bg-[#ca8a04] transition-colors">
                    Update User
                </button>
            </div>
        </form>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>