<?php
require_once 'db.php';
require_once 'includes/auth.php';
$user = require_login();

$ticket_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$single_ticket = null;

if ($ticket_id > 0) {
  if ($user['role'] === 'admin' || $user['role'] === 'technician') {
    $stmt = $conn->prepare('SELECT t.*, u.name AS owner_name FROM tickets t JOIN users u ON u.id = t.user_id WHERE t.id = ?');
    $stmt->bind_param('i', $ticket_id);
  } else {
    $stmt = $conn->prepare('SELECT * FROM tickets WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $ticket_id, $user['id']);
  }
  $stmt->execute();
  $res = $stmt->get_result();
  $single_ticket = $res->fetch_assoc();
  if (!$single_ticket) {
    $ticket_id = 0;
    $single_ticket = null;
  }
}

$attachments = [];
if ($single_ticket && $ticket_id > 0) {
  $stmt = $conn->prepare('SELECT id, filename, original_name FROM ticket_attachments WHERE ticket_id = ? ORDER BY id ASC');
  $stmt->bind_param('i', $ticket_id);
  $stmt->execute();
  $attachments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

  // Fetch Logs
  $logs = [];
  if ($user['role'] === 'admin' || $user['role'] === 'technician') {
    $log_stmt = $conn->prepare("SELECT l.*, u.name as actor_name, u.role as actor_role FROM ticket_logs l LEFT JOIN users u ON u.id = l.user_id WHERE l.ticket_id = ? ORDER BY l.created_at DESC");
    $log_stmt->bind_param('i', $ticket_id);
    $log_stmt->execute();
    $logs = $log_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  }

  // Fetch Responses
  $responses = [];
  if ($user['role'] === 'admin' || $user['role'] === 'technician') {
    $resp_stmt = $conn->prepare("SELECT r.*, u.name as user_name, u.role as user_role FROM ticket_responses r LEFT JOIN users u ON u.id = r.user_id WHERE r.ticket_id = ? ORDER BY r.created_at ASC");
    $resp_stmt->bind_param('i', $ticket_id);
    $resp_stmt->execute();
    $responses = $resp_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  }
}

if ($single_ticket) {
  $page_title = 'Ticket #' . $single_ticket['id'];
  $page_heading = 'Ticket #' . $single_ticket['id'];
  $page_subtitle = $single_ticket['subject'];
} else {
  $page_title = 'Tickets';
  $page_heading = 'Tickets';
  $page_subtitle = 'Listing all tickets';
}
require_once 'includes/header.php';

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<?php if ($flash_error): ?>
  <div class="bg-white border-2 border-[#262626] text-[#262626] rounded-xl p-4 my-4 text-sm" role="alert">
    <?= htmlspecialchars($flash_error) ?>
  </div>
<?php endif; ?>
<?php if ($flash_success): ?>
  <div class="bg-[#f5e6a3] border-2 border-[#262626] text-[#262626] rounded-xl p-4 my-4 text-sm" role="status">
    <?= htmlspecialchars($flash_success) ?>
  </div>
<?php endif; ?>

<?php if ($single_ticket): ?>
  <section class="bg-white border-2 border-[#262626] rounded-xl p-5 mb-4 shadow-sm"
    aria-labelledby="ticket-detail-heading">
    <h2 id="ticket-detail-heading" class="sr-only">Ticket details</h2>
    <div class="flex flex-wrap gap-4 mb-4 text-sm text-[#262626]">
      <span
        class="<?= status_badge_class($single_ticket['status']) ?>"><?= htmlspecialchars($single_ticket['status']) ?></span>
      <span class="text-[#525252]">Category: <span
          class="font-bold uppercase tracking-wide"><?= htmlspecialchars($single_ticket['category'] ?? 'Other') ?></span></span>
      <span class="text-[#525252]">Priority: <?= htmlspecialchars($single_ticket['priority'] ?? 'normal') ?></span>
      <?php if (!empty($single_ticket['importance'])): ?>
        <span class="text-[#525252] font-semibold">Importance: <span
            class="text-[#eab308]"><?= htmlspecialchars($single_ticket['importance']) ?></span></span>
      <?php endif; ?>
      <?php if (!empty($single_ticket['owner_name'])): ?>
        <span class="text-[#525252]">Owner: <?= htmlspecialchars($single_ticket['owner_name']) ?></span>
      <?php endif; ?>
      <span class="text-[#525252]">Created:
        <?= htmlspecialchars(date('M j, Y g:i A', strtotime($single_ticket['created_at'] ?? 'now'))) ?></span>
      <?php if ($user['role'] === 'admin' || $user['role'] === 'technician' || $user['id'] === $single_ticket['user_id']): ?>
        <a href="edit_ticket.php?id=<?= $single_ticket['id'] ?>"
          class="ml-auto text-sm font-bold text-[#eab308] hover:text-[#262626] underline">Edit Ticket</a>
      <?php endif; ?>
    </div>
    <h3 class="text-lg font-semibold text-[#262626] mb-2"><?= htmlspecialchars($single_ticket['subject']) ?></h3>
    <div class="pt-4 border-t border-[#262626] whitespace-pre-wrap text-[#262626] leading-relaxed">
      <?= nl2br(htmlspecialchars($single_ticket['description'] ?? '')) ?>
    </div>

    <?php if (!empty($attachments)): ?>
      <div class="mt-4 pt-4 border-t border-[#262626]">
        <h4 class="text-sm font-semibold text-[#262626] mb-2">Screenshots / images</h4>
        <div class="flex flex-wrap gap-4">
          <?php foreach ($attachments as $att): ?>
            <div class="flex flex-col items-start">
              <a href="view_attachment.php?id=<?= (int) $att['id'] ?>" target="_blank" rel="noopener"
                class="block border-2 border-[#262626] rounded-lg overflow-hidden hover:opacity-90 transition-opacity">
                <img src="view_attachment.php?id=<?= (int) $att['id'] ?>" alt="<?= htmlspecialchars($att['original_name']) ?>"
                  class="max-w-[200px] max-h-[150px] w-auto h-auto object-contain bg-[#f5e6a3]/30">
              </a>
              <span class="text-xs text-[#525252] mt-1 truncate max-w-[200px]"
                title="<?= htmlspecialchars($att['original_name']) ?>"><?= htmlspecialchars($att['original_name']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Grid Layout for Responses & Timeline -->
    <div class="mt-8 pt-6 border-t-2 border-[#262626] grid grid-cols-1 lg:grid-cols-3 gap-8">
      
      <!-- Left Column: Technician Responses (Wider) -->
      <div class="lg:col-span-2">
        <h3 class="text-xl font-bold text-[#262626] mb-4 uppercase tracking-wide">Technician Responses</h3>

        <?php if ($user['role'] === 'admin' || $user['role'] === 'technician'): ?>
          
          <!-- Display Existing Responses -->
          <?php if (!empty($responses)): ?>
              <div class="space-y-4 mb-6">
                  <?php foreach ($responses as $resp): ?>
                      <div class="bg-gray-50 border border-gray-200 p-4 rounded-xl">
                          <div class="flex justify-between items-center mb-2">
                              <span class="font-bold text-[#262626] text-sm">
                                  <?= htmlspecialchars($resp['user_name'] ?? 'Unknown') ?>
                                  <span class="text-xs font-normal text-[#525252] bg-gray-200 px-1.5 py-0.5 rounded ml-1 uppercase tracking-wider"><?= htmlspecialchars($resp['user_role'] ?? 'System') ?></span>
                              </span>
                              <span class="text-xs text-[#525252]"><?= date('M j, Y g:i A', strtotime($resp['created_at'])) ?></span>
                          </div>
                          <div class="text-sm text-[#262626] whitespace-pre-wrap leading-relaxed">
                              <?= nl2br(htmlspecialchars($resp['response'])) ?>
                          </div>
                      </div>
                  <?php endforeach; ?>
              </div>
          <?php endif; ?>

          <!-- Add Response Form -->
          <form action="update_response.php" method="POST"
            class="bg-[#262626] text-white p-6 rounded-xl shadow-lg border-2 border-[#eab308]">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="ticket_id" value="<?= (int) $single_ticket['id'] ?>">

            <div class="mb-4">
              <label for="technician_response" class="block text-sm font-bold text-[#f5e6a3] mb-1">Add Response</label>
              <textarea id="technician_response" name="technician_response" rows="3"
                class="w-full px-3 py-2 bg-white text-[#262626] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f5e6a3]"
                placeholder="Type your response here..."></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
               <div class="mb-4">
                  <label for="hours_worked" class="block text-sm font-bold text-[#f5e6a3] mb-1">Update Hours Worked (Total)</label>
                  <input type="number" step="0.01" id="hours_worked" name="hours_worked"
                    value="<?= htmlspecialchars($single_ticket['hours_worked'] ?? '') ?>"
                    class="w-full max-w-xs px-3 py-2 bg-white text-[#262626] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f5e6a3]"
                    placeholder="e.g. 2.5">
               </div>

              <!-- Resolution Status -->
              <div>
                <span class="block text-sm font-bold text-[#f5e6a3] mb-2">Update Resolution Status</span>
                <div class="space-y-2">
                  <?php
                  $res_stat = $single_ticket['resolution_status'] ?? '';
                  $ress = ['Resolved', 'Pending (Escalated)', 'Unresolved'];
                  ?>
                  <?php foreach ($ress as $opt): ?>
                    <label class="flex items-center gap-2 cursor-pointer group">
                      <input type="radio" name="resolution_status" value="<?= $opt ?>" <?= $res_stat === $opt ? 'checked' : '' ?>
                        class="w-4 h-4 text-[#eab308] focus:ring-[#f5e6a3] border-gray-500 bg-gray-700 checked:bg-[#eab308] checked:border-[#eab308]">
                      <span
                        class="text-sm font-medium text-white group-hover:text-[#f5e6a3] transition-colors"><?= $opt ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <div class="mb-6">
              <label for="additional_comments" class="block text-sm font-bold text-[#f5e6a3] mb-1">Update Additional Comments</label>
              <textarea id="additional_comments" name="additional_comments" rows="2"
                class="w-full px-3 py-2 bg-white text-[#262626] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f5e6a3]"
                placeholder="Any other notes..."><?= htmlspecialchars($single_ticket['additional_comments'] ?? '') ?></textarea>
            </div>

            <div class="flex justify-end">
              <button type="submit"
                class="px-6 py-2.5 bg-[#eab308] text-[#262626] font-bold uppercase tracking-wider rounded-lg shadow-md hover:bg-[#ca8a04] transition-colors">
                Submit Response & Update
              </button>
            </div>
          </form>

        <?php else: ?>
          <!-- User View (Read Only) - Show all responses -->
           <?php if (!empty($responses) || !empty($single_ticket['resolution_status'])): ?>
              
              <?php if (!empty($responses)): ?>
                  <div class="space-y-4 mb-6">
                      <?php foreach ($responses as $resp): ?>
                          <div class="bg-gray-50 border border-gray-200 p-4 rounded-xl">
                               <div class="flex justify-between items-center mb-2">
                                  <span class="font-bold text-[#262626] text-sm">
                                      <?= htmlspecialchars($resp['user_name'] ?? 'Unknown') ?>
                                      <span class="text-xs font-normal text-[#525252] bg-gray-200 px-1.5 py-0.5 rounded ml-1 uppercase tracking-wider"><?= htmlspecialchars($resp['user_role'] ?? 'System') ?></span>
                                  </span>
                                  <span class="text-xs text-[#525252]"><?= date('M j, Y g:i A', strtotime($resp['created_at'])) ?></span>
                              </div>
                              <div class="text-sm text-[#262626] whitespace-pre-wrap leading-relaxed">
                                  <?= nl2br(htmlspecialchars($resp['response'])) ?>
                              </div>
                          </div>
                      <?php endforeach; ?>
                  </div>
              <?php else: ?>
                   <div class="p-6 bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl text-center mb-6">
                      <p class="text-gray-500 italic">No technician response yet.</p>
                    </div>
              <?php endif; ?>

             <!-- Status/Hours Display for User -->
            <div class="bg-[#262626] text-white p-6 rounded-xl shadow-lg border-2 border-[#eab308]">
               <div class="flex flex-wrap gap-8 mb-4">
                <?php if (!empty($single_ticket['hours_worked']) && $single_ticket['hours_worked'] > 0): ?>
                  <div>
                    <span class="block text-xs font-bold text-[#f5e6a3] uppercase tracking-wider mb-1">Hours</span>
                    <span class="text-sm"><?= htmlspecialchars($single_ticket['hours_worked']) ?></span>
                  </div>
                <?php endif; ?>

                <?php if (!empty($single_ticket['importance'])): ?>
                  <div>
                    <span class="block text-xs font-bold text-[#f5e6a3] uppercase tracking-wider mb-1">Importance</span>
                    <span class="text-sm"><?= htmlspecialchars($single_ticket['importance']) ?></span>
                  </div>
                <?php endif; ?>

                <?php if (!empty($single_ticket['resolution_status'])): ?>
                  <div>
                    <span class="block text-xs font-bold text-[#f5e6a3] uppercase tracking-wider mb-1">Resolution</span>
                    <span class="text-sm"><?= htmlspecialchars($single_ticket['resolution_status']) ?></span>
                  </div>
                <?php endif; ?>
              </div>

              <?php if (!empty($single_ticket['additional_comments'])): ?>
                <div class="border-t border-[#525252] pt-4 mt-4">
                  <span class="block text-xs font-bold text-[#f5e6a3] uppercase tracking-wider mb-1">Additional Comments</span>
                  <div class="whitespace-pre-wrap text-sm text-gray-300">
                    <?= nl2br(htmlspecialchars($single_ticket['additional_comments'])) ?>
                  </div>
                </div>
              <?php endif; ?>

            </div>
          <?php else: ?>
            <div class="p-6 bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl text-center">
              <p class="text-gray-500 italic">No technician response yet.</p>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <!-- Right Column: Timeline & Quick Actions (Narrower) -->
      <div class="lg:col-span-1 space-y-8">
        
        <!-- Activity Timeline -->
        <?php if (!empty($logs)): ?>
          <div>
            <h3 class="text-xl font-bold text-[#262626] mb-4 uppercase tracking-wide">Activity Timeline</h3>
            <div class="relative border-l-2 border-[#eab308]/30 ml-3 space-y-6">
              <?php foreach ($logs as $log): ?>
                <div class="mb-4 ml-6 relative">
                  <span class="absolute -left-[31px] top-1.5 w-4 h-4 rounded-full bg-[#f5e6a3] border-2 border-[#eab308]"></span>
                  <div class="flex flex-col mb-1">
                    <h4 class="text-sm font-bold text-[#262626]">
                      <?= htmlspecialchars($log['actor_name'] ?? 'Unknown') ?>
                    </h4>
                    <span class="text-[10px] text-[#525252] uppercase tracking-wider"><?= htmlspecialchars($log['actor_role'] ?? 'System') ?></span>
                    <time class="text-xs text-[#525252] font-mono mt-0.5">
                      <?= date('M j, g:i A', strtotime($log['created_at'])) ?>
                    </time>
                  </div>
                  <p class="text-xs font-bold text-[#eab308] uppercase tracking-wide mb-1"><?= htmlspecialchars($log['action']) ?></p>
                  <?php if (!empty($log['details'])): ?>
                    <div class="text-xs text-[#525252] bg-gray-50 border border-gray-200 rounded p-2">
                      <?= nl2br(htmlspecialchars($log['details'])) ?>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Quick Status Update -->
        <?php if ($user['role'] === 'admin' || $user['role'] === 'technician'): ?>
          <div class="pt-6 border-t border-gray-200">
            <span class="block text-sm font-bold text-gray-500 mb-2">Quick Status Update</span>
            <form method="post" action="update_status.php" class="flex flex-col gap-2">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int) $single_ticket['id'] ?>">
              <label for="status-select" class="sr-only">Status</label>
              <select id="status-select" name="status"
                class="w-full px-3 py-2 border-2 border-[#262626] rounded-lg bg-white text-[#262626] font-medium focus:outline-none focus:ring-2 focus:ring-[#f5e6a3]">
                <?php foreach (['Open', 'In Progress', 'Closed'] as $opt): ?>
                  <option value="<?= htmlspecialchars($opt) ?>" <?= ($single_ticket['status'] ?? '') === $opt ? 'selected' : '' ?>>
                    <?= htmlspecialchars($opt) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <button type="submit"
                class="w-full px-4 py-2 rounded-lg border-2 border-[#262626] bg-[#f5e6a3] text-[#262626] font-semibold hover:bg-[#262626] hover:text-[#f5e6a3] transition-colors">Update Status</button>
            </form>
          </div>
        <?php endif; ?>

      </div>
    </div>

    <div class="mt-8 flex gap-3">
      <a href="tickets.php"
        class="inline-block px-4 py-2 rounded-lg border-2 border-[#262626] bg-white text-[#262626] font-medium hover:bg-[#262626] hover:text-[#f5e6a3] transition-colors">←
        Back to list</a>
      <?php if ($user['role'] === 'admin' || $user['role'] === 'technician' || $user['id'] === $single_ticket['user_id']): ?>
        <a href="edit_ticket.php?id=<?= $single_ticket['id'] ?>"
          class="inline-block px-4 py-2 rounded-lg border-2 border-[#262626] bg-[#f5e6a3] text-[#262626] font-semibold hover:bg-[#262626] hover:text-[#f5e6a3] transition-colors">Edit
          Ticket</a>

        <form action="delete_ticket.php" method="POST" class="inline-block"
          onsubmit="return confirm('Are you sure you want to delete this ticket? This action cannot be undone.');">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= $single_ticket['id'] ?>">
          <button type="submit"
            class="px-4 py-2 rounded-lg border-2 border-[#262626] bg-white text-red-600 font-semibold hover:bg-red-600 hover:text-white transition-colors">
            Delete
          </button>
        </form>
      <?php endif; ?>
    </div>
  </section>
<?php else: ?>
  <?php
  $sql = ($user['role'] === 'admin' || $user['role'] === 'technician')
    ? "SELECT tickets.*, users.name FROM tickets JOIN users ON users.id = tickets.user_id ORDER BY tickets.id DESC"
    : "SELECT * FROM tickets WHERE user_id = ? ORDER BY id DESC";
  if ($user['role'] === 'admin' || $user['role'] === 'technician') {
    $res = $conn->query($sql);
  } else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $res = $stmt->get_result();
  }
  $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  ?>
  <section aria-labelledby="tickets-list-heading">
    <h2 id="tickets-list-heading" class="text-lg font-semibold text-[#262626] mt-4 mb-4">All Tickets</h2>
    <?php if (empty($rows)): ?>
      <div class="bg-white border-2 border-[#262626] rounded-xl p-8 mb-4 shadow-sm text-center">
        <p class="text-[#525252] mb-4">No tickets yet.</p>
        <a href="create_ticket.php"
          class="inline-block px-4 py-2 rounded-lg border-2 border-[#262626] bg-[#f5e6a3] text-[#262626] font-semibold hover:bg-[#262626] hover:text-[#f5e6a3] transition-colors">Create
          your first ticket</a>
      </div>
    <?php else: ?>
      <div class="bg-white border-2 border-[#262626] rounded-xl overflow-hidden mb-8 shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead class="text-xs uppercase bg-[#f5e6a3] text-[#262626] font-bold border-b-2 border-[#262626]">
              <tr>
                <th class="px-6 py-4 tracking-wider">ID</th>
                <th class="px-6 py-4 tracking-wider">Subject</th>
                <th class="px-6 py-4 tracking-wider">Category</th>
                <th class="px-6 py-4 tracking-wider">Status</th>
                <th class="px-6 py-4 tracking-wider">Priority</th>
                <?php if ($user['role'] === 'admin' || $user['role'] === 'technician'): ?>
                  <th class="px-6 py-4 tracking-wider">Owner</th><?php endif; ?>
                <th class="px-6 py-4 tracking-wider text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#262626]/10">
              <?php foreach ($rows as $t): ?>
                <tr class="bg-white hover:bg-[#f5e6a3]/10 transition-colors">
                  <td class="px-6 py-4 font-mono text-[#525252]">#<?= (int) $t['id'] ?></td>
                  <td class="px-6 py-4 font-medium text-[#262626]"><?= htmlspecialchars($t['subject'] ?? '') ?></td>
                  <td class="px-6 py-4 text-[#525252] text-xs font-bold uppercase tracking-wide">
                    <?= htmlspecialchars($t['category'] ?? 'Other') ?>
                  </td>
                  <td class="px-6 py-4"><span
                      class="<?= status_badge_class($t['status'] ?? '') ?>"><?= htmlspecialchars($t['status'] ?? 'Unknown') ?></span>
                  </td>
                  <td class="px-6 py-4 text-[#525252] capitalize"><?= htmlspecialchars($t['priority'] ?? 'normal') ?></td>
                  <?php if ($user['role'] === 'admin' || $user['role'] === 'technician'): ?>
                    <td class="px-6 py-4 text-[#525252]"><?= htmlspecialchars($t['name'] ?? '') ?></td>
                  <?php endif; ?>
                  <td class="px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                      <a class="inline-flex items-center px-3 py-1.5 rounded-lg border border-[#262626] bg-white text-[#262626] text-xs font-bold uppercase tracking-wide hover:bg-[#262626] hover:text-[#f5e6a3] transition-colors"
                        href="tickets.php?id=<?= (int) $t['id'] ?>">View</a>
                      <?php if ($user['role'] === 'admin' || $user['role'] === 'technician'): ?>
                        <form class="inline-flex items-center gap-2" method="post" action="update_status.php">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                          <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                          <select name="status" aria-label="Status"
                            class="px-2 py-1.5 border border-[#262626] rounded-lg bg-white text-[#262626] text-xs font-medium focus:outline-none focus:ring-1 focus:ring-[#f5e6a3]">
                            <?php foreach (['Open', 'In Progress', 'Closed'] as $opt): ?>
                              <option value="<?= htmlspecialchars($opt) ?>" <?= ($t['status'] ?? '') === $opt ? 'selected' : '' ?>>
                                <?= htmlspecialchars($opt) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                          <button type="submit"
                            class="p-1.5 rounded-lg border border-[#262626] bg-[#f5e6a3] text-[#262626] hover:bg-[#262626] hover:text-[#f5e6a3] transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                              stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                          </button>
                        </form>
                      <?php endif; ?>

                      <?php if ($user['role'] === 'admin' || $user['id'] === $t['user_id']): ?>
                        <form action="delete_ticket.php" method="POST" class="inline-flex items-center"
                          onsubmit="return confirm('Delete ticket #<?= $t['id'] ?>?');">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                          <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                          <button type="submit"
                            class="p-1.5 rounded-lg border border-[#262626] bg-white text-red-600 hover:bg-red-600 hover:text-white transition-colors ml-2"
                            title="Delete">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                              stroke="currentColor">
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
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>