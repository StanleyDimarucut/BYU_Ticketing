<?php
require_once 'db.php';
require_once 'includes/auth.php';
$user = require_login();

define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_FILES', 5);
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Handle Attachment Deletion
if (isset($_GET['delete_attachment']) && is_numeric($_GET['delete_attachment'])) {
    $att_id = (int) $_GET['delete_attachment'];
    $ticket_id_chk = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    // Verify ownership/permission for this specific attachment
    // We need to check if the attachment belongs to a ticket that the user can edit
    $chk_sql = ($user['role'] === 'admin' || $user['role'] === 'technician')
        ? "SELECT a.id, a.filename FROM ticket_attachments a JOIN tickets t ON t.id = a.ticket_id WHERE a.id = ? AND t.id = ?"
        : "SELECT a.id, a.filename FROM ticket_attachments a JOIN tickets t ON t.id = a.ticket_id WHERE a.id = ? AND t.id = ? AND t.user_id = ?";

    $chk_stmt = $conn->prepare($chk_sql);
    if ($user['role'] === 'admin' || $user['role'] === 'technician') {
        $chk_stmt->bind_param('ii', $att_id, $ticket_id_chk);
    } else {
        $chk_stmt->bind_param('iii', $att_id, $ticket_id_chk, $user['id']);
    }
    $chk_stmt->execute();
    $att_res = $chk_stmt->get_result()->fetch_assoc();

    if ($att_res) {
        // Delete file
        $file_path = __DIR__ . '/uploads/tickets/' . $ticket_id_chk . '/' . $att_res['filename'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        // Delete DB record
        $del_stmt = $conn->prepare("DELETE FROM ticket_attachments WHERE id = ?");
        $del_stmt->bind_param('i', $att_id);
        $del_stmt->execute();

        $_SESSION['flash_success'] = "Attachment deleted.";
    } else {
        $_SESSION['flash_error'] = "Could not delete attachment.";
    }
    header('Location: edit_ticket.php?id=' . $ticket_id_chk);
    exit;
}

$ticket_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$ticket = null;

// Fetch ticket if user is owner or admin or technician
if ($ticket_id > 0) {
    if ($user['role'] === 'admin' || $user['role'] === 'technician') {
        $stmt = $conn->prepare('SELECT * FROM tickets WHERE id = ?');
        $stmt->bind_param('i', $ticket_id);
    } else {
        $stmt = $conn->prepare('SELECT * FROM tickets WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $ticket_id, $user['id']);
    }
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();

    // Fetch attachments
    $attachments = [];
    if ($ticket) {
        $stmt_att = $conn->prepare('SELECT id, filename, original_name FROM ticket_attachments WHERE ticket_id = ?');
        $stmt_att->bind_param('i', $ticket_id);
        $stmt_att->execute();
        $attachments = $stmt_att->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

if (!$ticket) {
    $_SESSION['flash_error'] = 'Ticket not found or access denied.';
    header('Location: tickets.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = in_array($_POST['category'] ?? '', ['Hardware', 'Software', 'Network', 'Access/Login', 'Other']) ? $_POST['category'] : 'Other';
    $priority = in_array($_POST['priority'] ?? '', ['low', 'normal', 'high']) ? $_POST['priority'] : 'normal';
    $ticket_type = in_array($_POST['ticket_type'] ?? '', ['Incident', 'Request']) ? $_POST['ticket_type'] : 'Incident';

    $importance = $_POST['importance'] ?? '';

    if ($title === '' || $description === '') {
        $error = 'Title and description are required.';
    } else {
        $stmt = $conn->prepare('UPDATE tickets SET subject = ?, description = ?, category = ?, priority = ?, importance = ?, ticket_type = ? WHERE id = ?');
        $stmt->bind_param('ssssssi', $title, $description, $category, $priority, $importance, $ticket_type, $ticket_id);

        if ($stmt->execute()) {
            log_ticket_activity($conn, $ticket_id, $user['id'], 'Updated', 'Ticket details updated');

            // Handle New Attachments
            $upload_dir = __DIR__ . '/uploads/tickets/' . $ticket_id;
            $upload_warnings = [];
            if (!empty($_FILES['attachments']['name'][0])) {
                $names = is_array($_FILES['attachments']['name']) ? $_FILES['attachments']['name'] : [$_FILES['attachments']['name']];
                $tmp = is_array($_FILES['attachments']['tmp_name']) ? $_FILES['attachments']['tmp_name'] : [$_FILES['attachments']['tmp_name']];
                $errors = is_array($_FILES['attachments']['error']) ? $_FILES['attachments']['error'] : [$_FILES['attachments']['error']];
                $count = 0;

                foreach ($names as $i => $original_name) {
                    if ($count >= MAX_FILES)
                        break;
                    if (trim($original_name) === '')
                        continue;

                    // Check PHP upload error
                    $upload_err = $errors[$i] ?? UPLOAD_ERR_OK;
                    if ($upload_err !== UPLOAD_ERR_OK) {
                        $err_msgs = [
                            UPLOAD_ERR_INI_SIZE => 'File exceeds server upload_max_filesize limit',
                            UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE',
                            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder on server',
                            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk on server',
                            UPLOAD_ERR_EXTENSION => 'Upload stopped by a PHP extension',
                        ];
                        $upload_warnings[] = htmlspecialchars($original_name) . ': ' . ($err_msgs[$upload_err] ?? 'Unknown upload error');
                        continue;
                    }

                    $tmp_name = $tmp[$i] ?? '';
                    if (!is_uploaded_file($tmp_name)) {
                        $upload_warnings[] = htmlspecialchars($original_name) . ': Not a valid uploaded file';
                        continue;
                    }

                    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                    if (!in_array($ext, ALLOWED_EXT)) {
                        $upload_warnings[] = htmlspecialchars($original_name) . ': File type not allowed (.' . $ext . ')';
                        continue;
                    }

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $tmp_name);
                    finfo_close($finfo);

                    if (!in_array($mime, ALLOWED_TYPES)) {
                        $upload_warnings[] = htmlspecialchars($original_name) . ': Invalid MIME type (' . $mime . ')';
                        continue;
                    }
                    if (filesize($tmp_name) > MAX_FILE_SIZE) {
                        $upload_warnings[] = htmlspecialchars($original_name) . ': File exceeds 5MB limit';
                        continue;
                    }

                    if (!is_dir($upload_dir)) {
                        if (!mkdir($upload_dir, 0777, true)) {
                            $upload_warnings[] = 'Could not create upload directory. Check server folder permissions for: uploads/tickets/';
                            break;
                        }
                    }

                    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
                    $path = $upload_dir . '/' . $filename;

                    if (move_uploaded_file($tmp_name, $path)) {
                        $ins = $conn->prepare('INSERT INTO ticket_attachments (ticket_id, filename, original_name) VALUES (?, ?, ?)');
                        $ins->bind_param('iss', $ticket_id, $filename, $original_name);
                        $ins->execute();
                        $count++;
                    } else {
                        $upload_warnings[] = htmlspecialchars($original_name) . ': Failed to move file. Check server write permissions on uploads/ folder.';
                    }
                }
            }

            $flash_msg = 'Ticket updated successfully.';
            if (!empty($upload_warnings)) {
                $flash_msg .= ' However, some attachments failed: ' . implode('; ', $upload_warnings);
            }
            $_SESSION['flash_success'] = $flash_msg;
            header('Location: tickets.php?id=' . $ticket_id);
            exit;
        } else {
            $error = 'Failed to update ticket.';
        }
    }
}

$page_title = 'Edit Ticket #' . $ticket['id'];
$page_heading = 'Edit Ticket';
$page_subtitle = $ticket['subject'];
require_once 'includes/header.php';
?>

<?php if ($error): ?>
    <div class="bg-white border-2 border-[#262626] text-[#262626] rounded-xl p-4 my-4 text-sm" role="alert">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<section aria-labelledby="edit-ticket-heading">
    <div class="bg-white border-2 border-[#262626] rounded-xl p-8 mb-8 shadow-sm">
        <form method="post" enctype="multipart/form-data" novalidate class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- ... (fields) ... -->

                <div>
                    <label for="title"
                        class="block text-xs font-bold text-[#525252] uppercase tracking-wider mb-2">Title</label>
                    <input id="title" name="title" type="text" required
                        value="<?= htmlspecialchars($_POST['title'] ?? $ticket['subject']) ?>"
                        class="w-full px-4 py-3 border border-[#262626]/20 rounded-lg bg-gray-50 text-[#262626] focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#f5e6a3] focus:border-transparent transition-all duration-200">
                </div>
                <div>
                    <label for="category"
                        class="block text-xs font-bold text-[#525252] uppercase tracking-wider mb-2">Category</label>
                    <div class="relative">
                        <select id="category" name="category" required
                            class="w-full px-4 py-3 border border-[#262626]/20 rounded-lg bg-gray-50 text-[#262626] focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#f5e6a3] focus:border-transparent transition-all duration-200 appearance-none">
                            <?php $c = $_POST['category'] ?? $ticket['category']; ?>
                            <option value="Hardware" <?= $c === 'Hardware' ? 'selected' : '' ?>>Hardware</option>
                            <option value="Software" <?= $c === 'Software' ? 'selected' : '' ?>>Software</option>
                            <option value="Network" <?= $c === 'Network' ? 'selected' : '' ?>>Network</option>
                            <option value="Access/Login" <?= $c === 'Access/Login' ? 'selected' : '' ?>>Access/Login
                            </option>
                            <option value="Other" <?= $c === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                        <div
                            class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-[#525252]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
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
                            <?php $p = $_POST['priority'] ?? $ticket['priority']; ?>
                            <option value="normal" <?= $p === 'normal' ? 'selected' : '' ?>>Normal</option>
                            <option value="high" <?= $p === 'high' ? 'selected' : '' ?>>High</option>
                            <option value="low" <?= $p === 'low' ? 'selected' : '' ?>>Low</option>
                        </select>
                        <div
                            class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-[#525252]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ticket Type Section -->
            <div>
                <span class="block text-xs font-bold text-[#525252] uppercase tracking-wider mb-2">Ticket Type</span>
                <div class="flex flex-wrap gap-4 p-4 border border-[#262626]/20 rounded-lg bg-gray-50">
                    <?php $curr_type = $_POST['ticket_type'] ?? $ticket['ticket_type'] ?? 'Incident'; ?>
                    <?php foreach (['Incident', 'Request'] as $type_opt): ?>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <div class="relative flex items-center">
                                <input type="radio" name="ticket_type" value="<?= $type_opt ?>" <?= $type_opt === $curr_type ? 'checked' : '' ?>
                                    class="peer w-5 h-5 text-[#eab308] focus:ring-[#f5e6a3] border-gray-300 checked:bg-[#eab308] checked:border-[#eab308] transition-all">
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if ($type_opt === 'Incident'): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-500" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                <?php else: ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-500" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
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
            </div>

            <!-- Importance Section -->
            <div>
                <span class="block text-xs font-bold text-[#525252] uppercase tracking-wider mb-2">Importance</span>
                <div class="flex flex-wrap gap-4 p-4 border border-[#262626]/20 rounded-lg bg-gray-50">
                    <?php
                    $curr_imp = $_POST['importance'] ?? $ticket['importance'] ?? '';
                    ?>
                    <?php foreach (['Mission Critical', 'Slowing User Down', 'Schedule When Able'] as $opt): ?>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <div class="relative flex items-center">
                                <input type="radio" name="importance" value="<?= $opt ?>" <?= $opt === $curr_imp ? 'checked' : '' ?>
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
                <textarea id="description" name="description" required rows="6"
                    class="w-full px-4 py-3 border border-[#262626]/20 rounded-lg bg-gray-50 text-[#262626] focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#f5e6a3] focus:border-transparent transition-all duration-200"><?= htmlspecialchars($_POST['description'] ?? $ticket['description']) ?></textarea>
            </div>

            <!-- Existing Attachments -->
            <?php if (!empty($attachments)): ?>
                <div>
                    <span class="block text-xs font-bold text-[#525252] uppercase tracking-wider mb-2">Current
                        Attachments</span>
                    <div class="flex flex-wrap gap-4">
                        <?php foreach ($attachments as $att): ?>
                            <div class="relative group border border-[#262626]/20 rounded-lg p-2 bg-gray-50">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-[#525252] truncate max-w-[150px]">
                                        <?= htmlspecialchars($att['original_name']) ?>
                                    </span>
                                    <a href="edit_ticket.php?id=<?= $ticket['id'] ?>&delete_attachment=<?= $att['id'] ?>"
                                        class="text-red-500 hover:text-red-700 font-bold px-2"
                                        onclick="return confirm('Delete this attachment?');" title="Delete Attachment">
                                        &times;
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="pt-4 border-t border-[#262626]/10">
                <label for="attachments"
                    class="block text-xs font-bold text-[#525252] uppercase tracking-wider mb-2">Add New
                    Images (optional, max 5MB each)</label>
                <input id="attachments" name="attachments[]" type="file"
                    accept="image/jpeg,image/png,image/gif,image/webp" multiple
                    class="block w-full text-sm text-[#525252] file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-[#f5e6a3] file:text-[#262626] hover:file:bg-[#eab308] transition-colors cursor-pointer">
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit"
                    class="px-6 py-2.5 rounded-lg border-2 border-[#262626] bg-[#f5e6a3] text-[#262626] font-bold uppercase tracking-wide hover:bg-[#262626] hover:text-[#f5e6a3] shadow-md hover:-translate-y-0.5 transition-all duration-200">Update
                    Ticket</button>
                <a href="tickets.php?id=<?= $ticket['id'] ?>"
                    class="px-6 py-2.5 rounded-lg border-2 border-[#262626] bg-white text-[#262626] font-bold uppercase tracking-wide hover:bg-[#262626] hover:text-[#f5e6a3] shadow-sm hover:-translate-y-0.5 transition-all duration-200">Cancel</a>
            </div>
        </form>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>