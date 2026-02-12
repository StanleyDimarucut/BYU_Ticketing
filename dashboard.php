<?php
require_once 'db.php';
require_once 'includes/auth.php';
$user = require_login();

$page_title = 'Dashboard';
$page_heading = 'Dashboard';
$page_subtitle = 'Welcome back, ' . ($user['name'] ?? 'User');
require_once 'includes/header.php';

$sql = ($user['role'] === 'admin' || $user['role'] === 'technician')
  ? "SELECT tickets.*, users.name FROM tickets JOIN users ON users.id = tickets.user_id ORDER BY tickets.id DESC LIMIT 20"
  : "SELECT * FROM tickets WHERE user_id = ? ORDER BY id DESC LIMIT 20";
if ($user['role'] === 'admin' || $user['role'] === 'technician') {
  $res = $conn->query($sql);
} else {
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i', $user['id']);
  $stmt->execute();
  $res = $stmt->get_result();
}
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// Calculate stats
$stats = [];
if ($user['role'] === 'admin' || $user['role'] === 'technician') {
  $total_res = $conn->query("SELECT COUNT(*) as c FROM tickets");
  $stats['total'] = $total_res->fetch_assoc()['c'];

  $open_res = $conn->query("SELECT COUNT(*) as c FROM tickets WHERE status = 'Open'");
  $stats['open'] = $open_res->fetch_assoc()['c'];

  $ip_res = $conn->query("SELECT COUNT(*) as c FROM tickets WHERE status = 'In Progress'");
  $stats['in_progress'] = $ip_res->fetch_assoc()['c'];

  $closed_res = $conn->query("SELECT COUNT(*) as c FROM tickets WHERE status = 'Closed'");
  $stats['closed'] = $closed_res->fetch_assoc()['c'];
} else {
  // User Stats
  $stmt = $conn->prepare("SELECT COUNT(*) as c FROM tickets WHERE user_id = ?");
  $stmt->bind_param('i', $user['id']);
  $stmt->execute();
  $stats['total'] = $stmt->get_result()->fetch_assoc()['c'];

  $stmt = $conn->prepare("SELECT COUNT(*) as c FROM tickets WHERE user_id = ? AND status = 'Open'");
  $stmt->bind_param('i', $user['id']);
  $stmt->execute();
  $stats['open'] = $stmt->get_result()->fetch_assoc()['c'];

  $stmt = $conn->prepare("SELECT COUNT(*) as c FROM tickets WHERE user_id = ? AND status = 'In Progress'");
  $stmt->bind_param('i', $user['id']);
  $stmt->execute();
  $stats['in_progress'] = $stmt->get_result()->fetch_assoc()['c'];

  $stmt = $conn->prepare("SELECT COUNT(*) as c FROM tickets WHERE user_id = ? AND status = 'Closed'");
  $stmt->bind_param('i', $user['id']);
  $stmt->execute();
  $stats['closed'] = $stmt->get_result()->fetch_assoc()['c'];
}
?>

<section aria-labelledby="stats-heading" class="mb-8">
  <h2 id="stats-heading" class="sr-only">Ticket Statistics</h2>
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
    <!-- Total -->
    <div
      class="bg-white border text-[#262626] rounded-xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-200 border-[#262626]">
      <div class="flex items-center justify-between mb-4">
        <span class="text-xs font-bold text-[#525252] uppercase tracking-wider">Total Tickets</span>
        <div class="p-2 bg-[#f5e6a3]/20 rounded-lg text-[#eab308]">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
        </div>
      </div>
      <div class="text-4xl font-extrabold text-[#262626]"><?= $stats['total'] ?></div>
    </div>
    <!-- Open -->
    <div
      class="bg-white border text-[#262626] rounded-xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-200 border-[#262626]">
      <div class="flex items-center justify-between mb-4">
        <span class="text-xs font-bold text-[#525252] uppercase tracking-wider">Open</span>
        <div class="p-2 bg-red-50 rounded-lg text-red-600">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
      </div>
      <div class="text-4xl font-extrabold text-[#262626]"><?= $stats['open'] ?></div>
    </div>
    <!-- In Progress -->
    <div
      class="bg-white border text-[#262626] rounded-xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-200 border-[#262626]">
      <div class="flex items-center justify-between mb-4">
        <span class="text-xs font-bold text-[#525252] uppercase tracking-wider">In Progress</span>
        <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>
      <div class="text-4xl font-extrabold text-[#262626]"><?= $stats['in_progress'] ?></div>
    </div>
    <!-- Resolved -->
    <div
      class="bg-white border text-[#262626] rounded-xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-200 border-[#262626]">
      <div class="flex items-center justify-between mb-4">
        <span class="text-xs font-bold text-[#525252] uppercase tracking-wider">Resolved</span>
        <div class="p-2 bg-green-50 rounded-lg text-green-600">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>
      <div class="text-4xl font-extrabold text-[#262626]"><?= $stats['closed'] ?></div>
    </div>
  </div>
</section>

<section aria-labelledby="recent-tickets">
  <div class="flex items-center justify-between mt-8 mb-6">
    <h2 id="recent-tickets" class="text-xl font-bold text-[#262626] uppercase tracking-tight">Recent Tickets</h2>
    <a href="tickets.php"
      class="text-sm font-bold text-[#eab308] hover:text-[#262626] transition-colors flex items-center gap-1 group">
      View All
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transform group-hover:translate-x-1 transition-transform"
        fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
      </svg>
    </a>
  </div>

  <?php if (empty($rows)): ?>
    <div class="bg-white border-2 border-[#262626] rounded-xl p-12 mb-8 shadow-sm text-center">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[#f5e6a3]/30 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-[#eab308]" fill="none" viewBox="0 0 24 24"
          stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
        </svg>
      </div>
      <h3 class="text-lg font-bold text-[#262626] mb-2">No tickets yet</h3>
      <p class="text-[#525252] mb-6 max-w-sm mx-auto">It looks like there are no tickets to display. Get started by
        creating your first ticket.</p>
      <a href="create_ticket.php"
        class="inline-flex items-center gap-2 px-6 py-3 rounded-lg border-2 border-[#262626] bg-[#f5e6a3] text-[#262626] font-bold uppercase tracking-wide hover:bg-[#262626] hover:text-[#f5e6a3] hover:-translate-y-0.5 shadow-sm transition-all">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
          stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        Create Ticket
      </a>
    </div>
  <?php else: ?>
    <div class="bg-white border-2 border-[#262626] rounded-xl overflow-hidden mb-8 shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="text-xs uppercase bg-[#f5e6a3] text-[#262626] font-bold border-b-2 border-[#262626]">
            <tr>
              <th scope="col" class="px-6 py-4 tracking-wider">Subject</th>
              <th scope="col" class="px-6 py-4 tracking-wider">Category</th>
              <th scope="col" class="px-6 py-4 tracking-wider">Status</th>
              <?php if ($user['role'] === 'admin' || $user['role'] === 'technician'): ?>
                <th scope="col" class="px-6 py-4 tracking-wider">Owner</th><?php endif; ?>
              <th scope="col" class="px-6 py-4 tracking-wider text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#262626]/10">
            <?php foreach ($rows as $t): ?>
              <tr class="bg-white hover:bg-[#f5e6a3]/10 transition-colors">
                <td class="px-6 py-4 font-medium text-[#262626]">
                  <div class="truncate max-w-[200px] sm:max-w-xs"><?= htmlspecialchars($t['subject'] ?? '') ?></div>
                </td>
                <td class="px-6 py-4 text-[#525252] text-xs font-bold uppercase tracking-wide">
                  <?= htmlspecialchars($t['category'] ?? 'Other') ?>
                </td>
                <td class="px-6 py-4"><span
                    class="<?= status_badge_class($t['status'] ?? '') ?>"><?= htmlspecialchars($t['status'] ?? 'Unknown') ?></span>
                </td>
                <?php if ($user['role'] === 'admin' || $user['role'] === 'technician'): ?>
                  <td class="px-6 py-4 text-[#525252]"><?= htmlspecialchars($t['name'] ?? '') ?></td>
                <?php endif; ?>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <a class="inline-flex items-center px-3 py-1.5 rounded-lg border border-[#262626] bg-white text-[#262626] text-xs font-bold uppercase tracking-wide hover:bg-[#262626] hover:text-[#f5e6a3] transition-colors"
                      href="tickets.php?id=<?= (int) $t['id'] ?>">View</a>

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