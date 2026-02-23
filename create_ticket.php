<?php
require_once 'db.php';
require_once 'includes/auth.php';
$user = require_login();

$error = '';
$success = $_GET['success'] ?? '';

define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_FILES', 5);
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $category = in_array($_POST['category'] ?? '', ['Hardware', 'Software', 'Network', 'Access/Login', 'Other']) ? $_POST['category'] : 'Other';
  $priority = in_array($_POST['priority'] ?? '', ['low', 'normal', 'high']) ? $_POST['priority'] : 'normal';
  $ticket_type = in_array($_POST['ticket_type'] ?? '', ['Incident', 'Request']) ? $_POST['ticket_type'] : 'Incident';

  $importance = $_POST['importance'] ?? '';

  if ($title === '' || $description === '' || $importance === '') {
    $error = 'Title, description, and importance level are required.';
  } else {
    $stmt = $conn->prepare('INSERT INTO tickets (user_id, subject, description, category, ticket_type, status, priority, importance) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $status = 'Open';
    $stmt->bind_param('isssssss', $user['id'], $title, $description, $category, $ticket_type, $status, $priority, $importance);
    if ($stmt->execute()) {
      $ticket_id = (int) $conn->insert_id;
      log_ticket_activity($conn, $ticket_id, $user['id'], 'Created', 'Ticket created');

      // Notify Admins and Technicians
      broadcast_notification($conn, ['admin', 'technician'], "New Ticket #$ticket_id: $title", "tickets.php?id=$ticket_id");

      $upload_dir = __DIR__ . '/uploads/tickets/' . $ticket_id;
      if (!empty($_FILES['attachments']['name'][0])) {
        $names = is_array($_FILES['attachments']['name']) ? $_FILES['attachments']['name'] : [$_FILES['attachments']['name']];
        $tmp = is_array($_FILES['attachments']['tmp_name']) ? $_FILES['attachments']['tmp_name'] : [$_FILES['attachments']['tmp_name']];
        $count = 0;
        foreach ($names as $i => $original_name) {
          if ($count >= MAX_FILES)
            break;
          if (trim($original_name) === '')
            continue;
          $tmp_name = $tmp[$i] ?? '';
          if (!is_uploaded_file($tmp_name))
            continue;
          $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
          if (!in_array($ext, ALLOWED_EXT))
            continue;
          $finfo = finfo_open(FILEINFO_MIME_TYPE);
          $mime = finfo_file($finfo, $tmp_name);
          finfo_close($finfo);
          if (!in_array($mime, ALLOWED_TYPES))
            continue;
          if (filesize($tmp_name) > MAX_FILE_SIZE)
            continue;
          if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
          }
          $filename = bin2hex(random_bytes(8)) . '.' . $ext;
          $path = $upload_dir . '/' . $filename;
          if (move_uploaded_file($tmp_name, $path)) {
            $ins = $conn->prepare('INSERT INTO ticket_attachments (ticket_id, filename, original_name) VALUES (?, ?, ?)');
            $ins->bind_param('iss', $ticket_id, $filename, $original_name);
            $ins->execute();
            $count++;
          }
        }
      }
      header('Location: create_ticket.php?success=' . urlencode('Ticket created successfully.'));
      exit;
    }
    $error = 'Could not create ticket. Please try again.';
  }
}

$page_title = 'Create Ticket';
$page_heading = 'Create Ticket';
$page_subtitle = 'Welcome, ' . ($user['name'] ?? 'User');
require_once 'includes/header.php';
?>

<?php if ($error): ?>
  <div class="bg-white border-2 border-[#262626] text-[#262626] rounded-xl p-4 my-4 text-sm" role="alert">
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>
<?php if ($success): ?>
  <div class="bg-[#f5e6a3] border-2 border-[#262626] text-[#262626] rounded-xl p-4 my-4 text-sm" role="status">
    <?= htmlspecialchars($success) ?>
  </div>
<?php endif; ?>

<div class="bg-white border-2 border-[#262626] rounded-xl p-8 mb-8 shadow-sm">
  <form method="post" action="create_ticket.php" enctype="multipart/form-data" novalidate class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label for="title" class="block text-xs font-bold text-[#525252] uppercase tracking-wider mb-2">Title</label>
        <input id="title" name="title" type="text" required placeholder="Brief summary of the issue"
          value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
          class="w-full px-4 py-3 border border-[#262626]/20 rounded-lg bg-gray-50 text-[#262626] focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#f5e6a3] focus:border-transparent transition-all duration-200">
      </div>
      <div>
        <label for="category"
          class="block text-xs font-bold text-[#525252] uppercase tracking-wider mb-2">Category</label>
        <div class="relative">
          <select id="category" name="category" required
            class="w-full px-4 py-3 border border-[#262626]/20 rounded-lg bg-gray-50 text-[#262626] focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#f5e6a3] focus:border-transparent transition-all duration-200 appearance-none">
            <option value="Hardware" <?= ($_POST['category'] ?? '') === 'Hardware' ? 'selected' : '' ?>>Hardware</option>
            <option value="Software" <?= ($_POST['category'] ?? '') === 'Software' ? 'selected' : '' ?>>Software</option>
            <option value="Network" <?= ($_POST['category'] ?? '') === 'Network' ? 'selected' : '' ?>>Network</option>
            <option value="Access/Login" <?= ($_POST['category'] ?? '') === 'Access/Login' ? 'selected' : '' ?>>
              Access/Login</option>
            <option value="Other" <?= ($_POST['category'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
          </select>
          <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-[#525252]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
          </div>
        </div>
      </div>
      <div>
        <label for="priority"
          class="block text-xs font-bold text-[#525252] uppercase tracking-wider mb-2">Priority</label>
        <div class="relative">
          <select id="priority" name="priority" required
            class="w-full px-4 py-3 border border-[#262626]/20 rounded-lg bg-gray-50 text-[#262626] focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#f5e6a3] focus:border-transparent transition-all duration-200 appearance-none">
            <option value="normal" <?= ($_POST['priority'] ?? '') === 'normal' ? 'selected' : '' ?>>Normal</option>
            <option value="high" <?= ($_POST['priority'] ?? '') === 'high' ? 'selected' : '' ?>>High</option>
            <option value="low" <?= ($_POST['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
          </select>
          <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-[#525252]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Ticket Type Section -->
    <div>
      <span class="block text-xs font-bold text-[#525252] uppercase tracking-wider mb-2">Ticket Type <span
          class="text-red-500">*</span></span>
      <div class="flex flex-wrap gap-4 p-4 border border-[#262626]/20 rounded-lg bg-gray-50">
        <?php $POST_TYPE = $_POST['ticket_type'] ?? 'Incident'; ?>
        <?php foreach (['Incident', 'Request'] as $type_opt): ?>
          <label class="flex items-center gap-3 cursor-pointer group">
            <div class="relative flex items-center">
              <input type="radio" name="ticket_type" value="<?= $type_opt ?>" <?= $type_opt === $POST_TYPE ? 'checked' : '' ?>
                class="peer w-5 h-5 text-[#eab308] focus:ring-[#f5e6a3] border-gray-300 checked:bg-[#eab308] checked:border-[#eab308] transition-all">
            </div>
            <div class="flex items-center gap-2">
              <?php if ($type_opt === 'Incident'): ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
              <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              <?php endif; ?>
              <span
                class="text-sm font-medium text-[#262626] group-hover:text-[#eab308] transition-colors"><?= $type_opt ?></span>
            </div>
          </label>
        <?php endforeach; ?>
      </div>
      <p class="text-xs text-[#525252] mt-1"><strong>Incident:</strong> Something is broken or not working.
        <strong>Request:</strong> A new service or change request.</p>
    </div>

    <!-- Importance Section -->
    <div>
      <span class="block text-xs font-bold text-[#525252] uppercase tracking-wider mb-2">Importance <span
          class="text-red-500">*</span></span>
      <div class="flex flex-wrap gap-4 p-4 border border-[#262626]/20 rounded-lg bg-gray-50">
        <?php
        $POST_IMP = $_POST['importance'] ?? '';
        ?>
        <?php foreach (['Mission Critical', 'Slowing User Down', 'Schedule When Able'] as $opt): ?>
          <label class="flex items-center gap-3 cursor-pointer group">
            <div class="relative flex items-center">
              <input type="radio" name="importance" value="<?= $opt ?>" <?= $opt === $POST_IMP ? 'checked' : '' ?>
                class="peer w-5 h-5 text-[#eab308] focus:ring-[#f5e6a3] border-gray-300 checked:bg-[#eab308] checked:border-[#eab308] transition-all">
            </div>
            <span
              class="text-sm font-medium text-[#262626] group-hover:text-[#eab308] transition-colors"><?= $opt ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div>
      <label for="description"
        class="block text-xs font-bold text-[#525252] uppercase tracking-wider mb-2">Description</label>
      <textarea id="description" name="description"
        placeholder="Describe the issue in detail. You can add screenshots below." required rows="6"
        class="w-full px-4 py-3 border border-[#262626]/20 rounded-lg bg-gray-50 text-[#262626] focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#f5e6a3] focus:border-transparent transition-all duration-200"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
    </div>

    <div class="pt-4 border-t border-[#262626]/10">
      <label for="attachments" class="block text-xs font-bold text-[#525252] uppercase tracking-wider mb-2">Screenshots
        / images (optional, max <?= MAX_FILES ?> files, 5MB each, JPG/PNG/GIF/WebP)</label>
      <input id="attachments" name="attachments[]" type="file" accept="image/jpeg,image/png,image/gif,image/webp"
        multiple
        class="block w-full text-sm text-[#525252] file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-[#f5e6a3] file:text-[#262626] hover:file:bg-[#eab308] transition-colors cursor-pointer">
    </div>

    <div class="flex gap-4 pt-4">
      <button type="submit"
        class="px-6 py-2.5 rounded-lg border-2 border-[#262626] bg-[#f5e6a3] text-[#262626] font-bold uppercase tracking-wide hover:bg-[#262626] hover:text-[#f5e6a3] shadow-md hover:-translate-y-0.5 transition-all duration-200">Submit
        Ticket</button>
      <a href="tickets.php"
        class="px-6 py-2.5 rounded-lg border-2 border-[#262626] bg-white text-[#262626] font-bold uppercase tracking-wide hover:bg-[#262626] hover:text-[#f5e6a3] shadow-sm hover:-translate-y-0.5 transition-all duration-200">Cancel</a>
    </div>
  </form>
</div>
</section>

<?php require_once 'includes/footer.php'; ?>