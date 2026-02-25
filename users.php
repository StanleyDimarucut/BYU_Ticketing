<?php
require_once 'db.php';
require_once 'includes/auth.php';

$user = require_admin();

$page_title = 'User Management';
$page_heading = 'Users';
$page_subtitle = 'Manage system users';
require_once 'includes/header.php';

$sql = "SELECT * FROM users ORDER BY created_at DESC";
$res = $conn->query($sql);
$users = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
?>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="bg-[#f5e6a3] border-2 border-[#262626] text-[#262626] rounded-xl p-4 my-4 text-sm" role="status">
        <?= htmlspecialchars($_SESSION['flash_success']) ?>
        <?php unset($_SESSION['flash_success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="bg-white border-2 border-[#262626] text-[#262626] rounded-xl p-4 my-4 text-sm" role="alert">
        <?= htmlspecialchars($_SESSION['flash_error']) ?>
        <?php unset($_SESSION['flash_error']); ?>
    </div>
<?php endif; ?>

<section aria-labelledby="users-list-heading">
    <div class="flex items-center justify-between mt-4 mb-6">
        <h2 id="users-list-heading" class="text-xl font-bold text-[#262626] uppercase tracking-tight">All Users</h2>
    </div>

    <?php if (empty($users)): ?>
        <div class="bg-white border-2 border-[#262626] rounded-xl p-8 mb-4 shadow-sm text-center">
            <p class="text-[#525252] mb-4">No users found.</p>
        </div>
    <?php else: ?>
        <div class="bg-white border-2 border-[#262626] rounded-xl overflow-hidden mb-8 shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-[#f5e6a3] text-[#262626] font-bold border-b-2 border-[#262626]">
                        <tr>
                            <th scope="col" class="px-6 py-4 tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-4 tracking-wider">Name</th>
                            <th scope="col" class="px-6 py-4 tracking-wider">Username</th>
                            <th scope="col" class="px-6 py-4 tracking-wider">Role</th>
                            <th scope="col" class="px-6 py-4 tracking-wider">Created At</th>
                            <th scope="col" class="px-6 py-4 tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#262626]/10">
                        <?php foreach ($users as $u): ?>
                            <tr class="bg-white hover:bg-[#f5e6a3]/10 transition-colors">
                                <td class="px-6 py-4 font-mono text-[#525252]">#
                                    <?= (int) $u['id'] ?>
                                </td>
                                <td class="px-6 py-4 font-medium text-[#262626]">
                                    <?= htmlspecialchars($u['name']) ?>
                                </td>
                                <td class="px-6 py-4 text-[#525252]">
                                    <?= htmlspecialchars($u['username']) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-block px-2 py-1 text-xs font-bold uppercase tracking-wider rounded border border-[#262626] 
                                        <?= $u['role'] === 'admin' ? 'bg-[#262626] text-[#f5e6a3]' : ($u['role'] === 'technician' ? 'bg-blue-100 text-blue-800 border-blue-200' : 'bg-gray-100 text-gray-800 border-gray-200') ?>">
                                        <?= htmlspecialchars($u['role']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-[#525252] font-mono text-xs">
                                    <?= date('M j, Y g:i A', strtotime($u['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="edit_user.php?id=<?= $u['id'] ?>"
                                            class="inline-flex items-center px-3 py-1.5 rounded-lg border border-[#262626] bg-white text-[#262626] text-xs font-bold uppercase tracking-wide hover:bg-[#262626] hover:text-[#f5e6a3] transition-colors">
                                            Edit
                                        </a>
                                        <?php if ($u['id'] !== $user['id']): // Prevent self-deletion ?>
                                            <form action="delete_user.php" method="POST" class="inline-block"
                                                onsubmit="return confirm('Are you sure you want to delete this user? All their tickets and data will be removed.');">
                                                <input type="hidden" name="csrf_token"
                                                    value="<?= htmlspecialchars(csrf_token()) ?>">
                                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                <button type="submit"
                                                    class="p-1.5 rounded-lg border border-[#262626] bg-white text-red-600 hover:bg-red-600 hover:text-white transition-colors"
                                                    title="Delete User">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>