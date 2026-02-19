<?php
require_once 'db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php'; // Ensure functions are loaded for format_hours_worked
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
      <?php if (($user['role'] === 'admin' || $user['role'] === 'technician') && !empty($single_ticket['hours_worked'])): ?>
        <span class="text-[#525252] font-semibold ml-2">Total Hours: <span
            class="text-[#eab308]"><?= format_hours_worked($single_ticket['hours_worked']) ?></span></span>
      <?php endif; ?>
      <?php if ($user['role'] === 'technician' || $user['id'] === $single_ticket['user_id']): ?>
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

    <!-- Grid Layout for Responses & Timeline & KB -->
    <div class="mt-8 pt-6 border-t-2 border-[#262626] grid grid-cols-1 lg:grid-cols-12 gap-6">
      
      <?php
      // Dynamic Column Sizing
      // Default (Technician/Admin default): Responses 6 (50%), Timeline 3 (25%), KB 3 (25%) -> Adjusted for wider timeline
      // Technician: Resp 5 (41%), Timeline 4 (33%), KB 3 (25%)
      // Admin (No KB): Resp 6 (50%), Timeline 6 (50%)

      $col_resp = 'lg:col-span-5';
      $col_timeline = 'lg:col-span-4';
      $col_kb = 'lg:col-span-3';

      if ($user['role'] === 'admin') {
          $col_resp = 'lg:col-span-6';
          $col_timeline = 'lg:col-span-6';
          $col_kb = 'hidden'; // Hide KB column completely
      }
      ?>

      <!-- Left Column: Technician Responses -->
      <div class="<?= $col_resp ?>">
        <h3 class="text-xl font-bold text-[#262626] mb-4 uppercase tracking-wide">Technician Responses</h3>

        <?php if ($user['role'] === 'technician'): ?>

          <!-- Display Existing Responses -->
          <?php if (!empty($responses)): ?>
            <div class="space-y-4 mb-6">
              <?php foreach ($responses as $resp): ?>
                <div class="bg-gray-50 border border-gray-200 p-4 rounded-xl">
                  <div class="flex justify-between items-center mb-2">
                    <span class="font-bold text-[#262626] text-sm">
                      <?= htmlspecialchars($resp['user_name'] ?? 'Unknown') ?>
                      <span
                        class="text-xs font-normal text-[#525252] bg-gray-200 px-1.5 py-0.5 rounded ml-1 uppercase tracking-wider"><?= htmlspecialchars($resp['user_role'] ?? 'System') ?></span>
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
                <label class="block text-sm font-bold text-[#f5e6a3] mb-1">Add Hours Worked</label>
                       <div class="flex gap-4">
                           <div class="w-1/2">
                                <label for="hours_input" class="block text-xs text-gray-400 mb-1">Hours</label>
                                <input type="number" min="0" id="hours_input" name="hours_input"
                                   value=""
                                   class="w-full px-3 py-2 bg-white text-[#262626] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f5e6a3]"
                                   placeholder="0">
                           </div>
                           <div class="w-1/2">
                                <label for="minutes_input" class="block text-xs text-gray-400 mb-1">Minutes</label>
                                <select id="minutes_input" name="minutes_input"
                                   class="w-full px-3 py-2 bg-white text-[#262626] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f5e6a3]">
                                   <?php foreach ([0, 15, 30, 45] as $m): ?>
                                         <option value="<?= $m ?>"><?= $m ?> mins</option>
                                   <?php endforeach; ?>
                                </select>
                           </div>
                       </div>
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

        <!-- Center Column: Activity & Quick Update -->
        <div class="<?= $col_timeline ?> space-y-8">
        
          <!-- Activity Timeline -->
          <?php if (!empty($logs)): ?>
              <div>
                <h3 class="text-xl font-bold text-[#262626] mb-4 uppercase tracking-wide">Activity Timeline</h3>
                <div class="scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent max-h-[600px] overflow-y-auto pr-2">
                <div class="relative border-l-2 border-gray-200 ml-3 space-y-8">
                  <?php foreach ($logs as $log): 
                      $action = strtolower($log['action']);
                      $icon_bg = 'bg-gray-100';
                      $icon_text = 'text-gray-500';
                      $icon_border = 'border-gray-200';
                      $icon_svg = ''; // Default

                      if (strpos($action, 'created') !== false) {
                          $icon_bg = 'bg-emerald-100'; $icon_text = 'text-emerald-600'; $icon_border = 'border-emerald-200';
                          $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>';
                      } elseif (strpos($action, 'response') !== false) {
                          $icon_bg = 'bg-blue-100'; $icon_text = 'text-blue-600'; $icon_border = 'border-blue-200';
                          $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>';
                      } elseif (strpos($action, 'status') !== false) {
                          $icon_bg = 'bg-amber-100'; $icon_text = 'text-amber-600'; $icon_border = 'border-amber-200';
                          $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>';
                      } elseif (strpos($action, 'updated') !== false || strpos($action, 'edit') !== false) {
                          $icon_bg = 'bg-violet-100'; $icon_text = 'text-violet-600'; $icon_border = 'border-violet-200';
                          $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>';
                      } else {
                          // Default Info
                          $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
                      }
                  ?>
                      <div class="mb-4 ml-8 relative group">
                        <!-- Icon -->
                        <span class="absolute -left-[45px] top-0 flex items-center justify-center w-8 h-8 rounded-full <?= $icon_bg ?> <?= $icon_text ?> border <?= $icon_border ?> shadow-sm z-10">
                            <?= $icon_svg ?>
                        </span>
                        
                        <div class="flex flex-col bg-white border border-gray-100 rounded-lg p-3 shadow-sm hover:shadow-md transition-shadow">
                          <div class="flex justify-between items-start mb-1">
                              <div>
                                  <h4 class="text-xs font-bold text-[#262626] uppercase tracking-wide">
                                    <?= htmlspecialchars($log['action']) ?>
                                  </h4>
                                  <div class="flex items-center gap-1.5 mt-0.5">
                                      <span class="text-xs font-semibold text-gray-700"><?= htmlspecialchars($log['actor_name'] ?? 'Unknown') ?></span>
                                      <span class="text-[10px] text-gray-400">&bull;</span>
                                      <span class="text-[10px] text-gray-500 uppercase tracking-wider"><?= htmlspecialchars($log['actor_role'] ?? 'System') ?></span>
                                  </div>
                              </div>
                              <time class="text-[10px] text-gray-400 font-medium whitespace-nowrap">
                                <?= date('M j, g:i A', strtotime($log['created_at'])) ?>
                              </time>
                          </div>
                          
                          <?php if (!empty($log['details'])): ?>
                            <div class="text-xs text-gray-600 bg-gray-50 border border-gray-100 rounded p-2 mt-1 leading-relaxed">
                              <?= nl2br(htmlspecialchars($log['details'])) ?>
                            </div>
                          <?php endif; ?>
                        </div>
                      </div>
                  <?php endforeach; ?>
                </div>
                </div>
              </div>
          <?php endif; ?>

          <!-- Quick Status Update -->
          <?php if ($user['role'] === 'technician'): ?>
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

        <!-- Right Column: Knowledge Base (25%) -->
      <div class="<?= $col_kb ?>">
           <!-- Knowledge Base Card -->
          <?php if ($user['role'] === 'technician'): ?>
                <div class="bg-white border-2 border-[#262626] rounded-xl p-5 shadow-sm h-full max-h-[calc(100vh-100px)] overflow-y-auto">
                
                    <!-- List View -->
                    <div id="kb-list-view">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-[#262626] uppercase tracking-wide">Knowledge Base</h3>
                            <a href="kb.php" class="text-xs text-[#eab308] font-bold hover:underline">View All</a>
                        </div>
                    
                        <!-- Search Form (Mini) -->
                    <form id="kb-search-form" action="kb.php" method="GET" class="mb-4">
                        <div class="relative">
                            <input type="text" name="search" placeholder="Search..."
                                class="w-full px-3 py-1.5 text-sm border-2 border-[#262626] rounded-lg focus:outline-none focus:ring-1 focus:ring-[#f5e6a3]">
                            <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-[#525252] hover:text-[#262626]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                        </div>
                    </form>

                    <!-- Recent Articles -->
                    <?php
                    // Fetch recent 5 articles (Initial Load)
                    $kb_stmt = $conn->prepare("SELECT id, title FROM kb_articles ORDER BY created_at DESC LIMIT 5");
                    $kb_stmt->execute();
                    $kb_res = $kb_stmt->get_result();
                    $kb_articles = $kb_res ? $kb_res->fetch_all(MYSQLI_ASSOC) : [];
                    ?>
                
                    <ul id="kb-articles-list" class="space-y-2">
                        <?php if (!empty($kb_articles)): ?>
                              <?php foreach ($kb_articles as $kb): ?>
                                    <li>
                                        <a href="kb_article.php?id=<?= $kb['id'] ?>" target="_blank"
                                           class="kb-link block text-sm text-[#525252] hover:text-[#eab308] hover:underline truncate transition-colors">
                                            <?= htmlspecialchars($kb['title']) ?>
                                        </a>
                                    </li>
                              <?php endforeach; ?>
                        <?php else: ?>
                              <li class="text-xs text-[#525252] italic">No articles yet.</li>
                        <?php endif; ?>
                    </ul>
                
                    <div class="mt-4 pt-3 border-t border-[#262626]/10 text-center">
                        <a href="kb_manage.php" class="inline-flex items-center text-xs font-bold text-[#262626] hover:text-[#eab308] uppercase tracking-wide">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            New Article
                        </a>
                    </div>
                </div>

                <!-- Article Detail View (Hidden by default) -->
                <div id="kb-article-view" class="hidden">
                    <button id="kb-back-btn" class="mb-3 inline-flex items-center text-xs font-bold text-[#525252] hover:text-[#262626]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back
                    </button>

                    <div id="kb-loading" class="hidden flex flex-col items-center justify-center py-8">
                        <svg class="animate-spin h-6 w-6 text-[#eab308]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <div id="kb-error" class="hidden p-3 bg-red-50 border border-red-200 rounded text-red-600 text-xs mb-4"></div>

                    <div id="kb-content" class="hidden">
                        <h3 id="kb-title" class="text-lg font-bold text-[#262626] mb-2 leading-tight"></h3>
                        <div class="flex items-center text-[10px] text-[#525252] mb-4 pb-2 border-b border-[#262626]/10 gap-2">
                             <span id="kb-author" class="font-semibold"></span>
                             <span>&bull;</span>
                             <span id="kb-date"></span>
                        </div>
                        <div id="kb-body" class="prose prose-sm prose-p:text-xs prose-headings:text-sm text-[#262626] leading-relaxed whitespace-pre-wrap"></div>
                    </div>
                </div>

                </div>
          <?php endif; ?>
        </div>
    
      </div>

      <div class="mt-8 flex gap-3">
        <a href="tickets.php"
          class="inline-block px-4 py-2 rounded-lg border-2 border-[#262626] bg-white text-[#262626] font-medium hover:bg-[#262626] hover:text-[#f5e6a3] transition-colors">←
          Back to list</a>
        <?php if ($user['role'] === 'technician' || $user['id'] === $single_ticket['user_id']): ?>
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
    // Get filter parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
    $priority_filter = isset($_GET['priority']) ? trim($_GET['priority']) : '';

    // Build Query
    $where_clauses = [];
    $params = [];
    $types = '';

    if ($user['role'] === 'admin' || $user['role'] === 'technician') {
      $base_sql = "SELECT tickets.*, users.name FROM tickets JOIN users ON users.id = tickets.user_id";
    } else {
      $base_sql = "SELECT * FROM tickets";
      $where_clauses[] = "user_id = ?";
      $params[] = $user['id'];
      $types .= 'i';
    }

    // Search Logic
    if ($search) {
      if (is_numeric($search)) {
        // If numeric, search ID or Subject
        $where_clauses[] = "(tickets.id = ? OR tickets.subject LIKE ?)";
        $params[] = $search;
        $params[] = "%$search%";
        $types .= 'is';
      } else {
        $where_clauses[] = "tickets.subject LIKE ?";
        $params[] = "%$search%";
        $types .= 's';
      }
    }

    // Filter Logic
    if ($status_filter) {
      $where_clauses[] = "tickets.status = ?";
      $params[] = $status_filter;
      $types .= 's';
    }

    if ($priority_filter) {
      $where_clauses[] = "tickets.priority = ?";
      $params[] = $priority_filter;
      $types .= 's';
    }

    // Combine
    if (!empty($where_clauses)) {
      $base_sql .= " WHERE " . implode(' AND ', $where_clauses);
    }

    $base_sql .= " ORDER BY tickets.id DESC";

    // Execute
    $stmt = $conn->prepare($base_sql);
    if (!empty($params)) {
      // Create references for bind_param
      $types_and_params = [];
      $types_and_params[] = &$types; // First element is types string
      foreach ($params as $k => $v) {
        $params[$k] = $v; // Ensure value is set
        $types_and_params[] = &$params[$k]; // Add reference
      }
      call_user_func_array([$stmt, 'bind_param'], $types_and_params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    ?>
    <section aria-labelledby="tickets-list-heading">
      <div class="bg-white border-2 border-[#262626] rounded-xl p-4 mb-6 shadow-sm">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
              <h2 id="tickets-list-heading" class="text-xl font-bold text-[#262626] uppercase tracking-wide whitespace-nowrap">All Tickets</h2>
            
              <form method="GET" action="tickets.php" class="flex flex-wrap items-center gap-3 w-full md:w-auto md:justify-end">
                  <!-- Search -->
                  <div class="relative w-full md:w-64">
                      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search subject or ID..." 
                          class="w-full pl-9 pr-4 py-2 border-2 border-[#262626] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f5e6a3] text-sm font-medium">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-[#525252]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                      </svg>
                  </div>
                
                  <!-- Status Filter -->
                  <select name="status" class="flex-grow md:flex-none w-full md:w-40 px-3 py-2 border-2 border-[#262626] rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[#f5e6a3] text-sm font-medium cursor-pointer">
                      <option value="">All Statuses</option>
                      <?php foreach (['Open', 'In Progress', 'Closed'] as $s): ?>
                            <option value="<?= $s ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= $s ?></option>
                      <?php endforeach; ?>
                  </select>

                  <!-- Priority Filter -->
                  <select name="priority" class="flex-grow md:flex-none w-full md:w-40 px-3 py-2 border-2 border-[#262626] rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[#f5e6a3] text-sm font-medium cursor-pointer">
                      <option value="">All Priorities</option>
                      <?php foreach (['low', 'normal', 'high'] as $p): ?>
                            <option value="<?= $p ?>" <?= $priority_filter === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
                      <?php endforeach; ?>
                  </select>

                  <div class="flex gap-2 w-full md:w-auto">
                      <button type="submit" class="flex-grow md:flex-none px-3 py-2 bg-[#262626] text-[#f5e6a3] rounded-lg hover:bg-black transition-colors shadow-sm flex items-center justify-center" title="Filter">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                          </svg>
                      </button>
                    
                      <?php if ($search || $status_filter || $priority_filter): ?>
                            <a href="tickets.php" class="flex-grow md:flex-none px-3 py-2 border-2 border-[#262626] text-[#262626] rounded-lg hover:bg-gray-100 transition-colors shadow-sm flex items-center justify-center" title="Clear Filters">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                      <?php endif; ?>
                  </div>
              </form>
          </div>
      </div>
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
                        <th class="px-6 py-4 tracking-wider">Owner</th>
                        <th class="px-6 py-4 tracking-wider">Hours</th>
                    <?php endif; ?>
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
                            <td class="px-6 py-4 text-[#525252] font-mono"><?= format_hours_worked($t['hours_worked'] ?? 0) ?></td>
                        <?php endif; ?>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                          <div class="flex items-center justify-end gap-1.5 flex-nowrap">
                            <a class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-[#262626] bg-white text-[#262626] text-xs font-bold uppercase tracking-wide hover:bg-[#262626] hover:text-[#f5e6a3] transition-colors"
                              href="tickets.php?id=<?= (int) $t['id'] ?>">View</a>
                            <?php if ($user['role'] === 'technician'): ?>
                                <form class="inline-flex items-center gap-1.5" method="post" action="update_status.php">
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
                                    class="px-2.5 py-1.5 rounded-lg border border-[#262626] bg-[#f5e6a3] text-[#262626] text-xs font-bold uppercase tracking-wide hover:bg-[#262626] hover:text-[#f5e6a3] transition-colors">
                                    Update
                                  </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($user['id'] === $t['user_id']): ?>
                                <form action="delete_ticket.php" method="POST" class="inline-flex items-center"
                                  onsubmit="return confirm('Delete ticket #<?= $t['id'] ?>?');">
                                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                  <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                                  <button type="submit"
                                    class="p-1.5 rounded-lg border border-[#262626] bg-white text-red-600 hover:bg-red-600 hover:text-white transition-colors ml-1"
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const listView = document.getElementById('kb-list-view');
    const articleView = document.getElementById('kb-article-view');
    const backBtn = document.getElementById('kb-back-btn');
    const searchForm = document.getElementById('kb-search-form');
    const articlesList = document.getElementById('kb-articles-list');
    
    // Elements to populate
    const loading = document.getElementById('kb-loading');
    const errorMsg = document.getElementById('kb-error');
    const content = document.getElementById('kb-content');
    const titleEl = document.getElementById('kb-title');
    const authorEl = document.getElementById('kb-author');
    const dateEl = document.getElementById('kb-date');
    const bodyEl = document.getElementById('kb-body');

    // Back button
    backBtn.addEventListener('click', function() {
        articleView.classList.add('hidden');
        listView.classList.remove('hidden');
    });

    // Handle Search
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const query = this.querySelector('input[name="search"]').value;
            
            // Show opacity/loading state on list
            articlesList.style.opacity = '0.5';
            
            fetch(`ajax_kb_search.php?search=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    articlesList.style.opacity = '1';
                    if (data.success) {
                        renderArticles(data.articles);
                    } else {
                        console.error('Search failed:', data.error);
                        articlesList.innerHTML = '<li class="text-xs text-red-500 italic">Error searching articles.</li>';
                    }
                })
                .catch(err => {
                    articlesList.style.opacity = '1';
                    console.error('Search error:', err);
                    articlesList.innerHTML = '<li class="text-xs text-red-500 italic">Network error during search.</li>';
                });
        });
    }

    function renderArticles(articles) {
        articlesList.innerHTML = '';
        if (articles.length === 0) {
            articlesList.innerHTML = '<li class="text-xs text-[#525252] italic">No articles found.</li>';
            return;
        }

        articles.forEach(article => {
            const li = document.createElement('li');
            li.innerHTML = `
                <a href="kb_article.php?id=${article.id}" target="_blank"
                   class="kb-link block text-sm text-[#525252] hover:text-[#eab308] hover:underline truncate transition-colors">
                    ${escapeHtml(article.title)}
                </a>
            `;
            articlesList.appendChild(li);
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Event Delegation for KB Links (handles initial and dynamic links)
    if (listView) {
        listView.addEventListener('click', function(e) {
            const link = e.target.closest('a.kb-link');
            if (link && link.getAttribute('target') === '_blank') {
                e.preventDefault(); 
                const url = new URL(link.href);
                const id = url.searchParams.get('id');
                if (id) {
                    showArticle(id);
                }
            }
        });
    }

    function showArticle(id) {
        // Toggle views
        listView.classList.add('hidden');
        articleView.classList.remove('hidden');
        
        // Reset state
        loading.classList.remove('hidden');
        errorMsg.classList.add('hidden');
        content.classList.add('hidden');
        
        // Fetch
        fetch(`ajax_kb_article.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                loading.classList.add('hidden');
                
                if (data.success && data.article) {
                    titleEl.textContent = data.article.title;
                    authorEl.textContent = data.article.author_name;
                    dateEl.textContent = data.article.created_at;
                    bodyEl.innerHTML = data.article.content_html;
                    
                    content.classList.remove('hidden');
                } else {
                    throw new Error(data.error || 'Unknown error');
                }
            })
            .catch(err => {
                loading.classList.add('hidden');
                errorMsg.textContent = 'Failed: ' + err.message;
                errorMsg.classList.remove('hidden');
            });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>