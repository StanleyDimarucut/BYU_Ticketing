<?php
require_once 'db.php';
require_once 'includes/auth.php';

$user = require_login();

$page_title = 'Notifications';
$page_heading = 'Notifications';
require_once 'includes/header.php';

// Fetch Notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$notifs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-[#262626] uppercase tracking-tight">Your Notifications</h2>
        <?php if (!empty($notifs)): ?>
            <form action="read_notification.php" method="POST">
                <input type="hidden" name="mark_all" value="1">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <button type="submit"
                    class="text-sm font-bold text-[#eab308] hover:text-[#262626] transition-colors underline">
                    Mark all as read
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($notifs)): ?>
        <div class="bg-white border-2 border-[#262626] rounded-xl p-12 shadow-sm text-center">
            <p class="text-[#525252] italic">You have no notifications.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($notifs as $n): ?>
                <div
                    class="relative bg-white border-l-4 <?= $n['is_read'] ? 'border-gray-300 opacity-75' : 'border-[#eab308] shadow-md' ?> rounded-r-xl p-5 transition-all hover:bg-gray-50">
                    <a href="read_notification.php?id=<?= $n['id'] ?>" class="block">
                        <div class="flex justify-between items-start">
                            <p class="text-[#262626] <?= $n['is_read'] ? '' : 'font-bold' ?> pr-8">
                                <?= htmlspecialchars($n['message']) ?>
                            </p>
                            <span class="text-xs text-[#525252] whitespace-nowrap ml-4">
                                <?= date('M j, g:i A', strtotime($n['created_at'])) ?>
                            </span>
                        </div>
                    </a>
                    <?php if (!$n['is_read']): ?>
                        <span class="absolute top-5 right-5 w-2 h-2 rounded-full bg-[#eab308]"></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>