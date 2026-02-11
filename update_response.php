<?php
require_once 'db.php';
require_once 'includes/auth.php';

// Ensure staff can update response
$user = require_staff();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: tickets.php');
    exit;
}

if (!csrf_verify()) {
    $_SESSION['flash_error'] = 'Invalid request (CSRF). Please try again.';
    header('Location: tickets.php');
    exit;
}

$ticket_id = isset($_POST['ticket_id']) ? (int) $_POST['ticket_id'] : 0;
if ($ticket_id <= 0) {
    header('Location: tickets.php');
    exit;
}

// Gather fields
$response = trim($_POST['technician_response'] ?? '');
$hours = trim($_POST['hours_worked'] ?? '');
$importance = $_POST['importance'] ?? null; // array or string depending on form
$resolution = $_POST['resolution_status'] ?? null; // array or string
$comments = trim($_POST['additional_comments'] ?? '');

// Process checkboxes (if multiple selected, join them, though UI might force single)
if (is_array($importance)) {
    $importance = implode(', ', $importance);
}
if (is_array($resolution)) {
    $resolution = implode(', ', $resolution);
}

// Validate Hours
$hours_val = null;
if ($hours !== '') {
    if (is_numeric($hours)) {
        $hours_val = (float) $hours;
    } else {
        // invalid hours, maybe ignore or error? keeping null
    }
}

// Logic: Update Ticket
// We also might want to update the main 'status' based on 'resolution_status'
// Resolved -> Closed
// Pending (Escalated) -> In Progress
// Unresolved -> Open (or keep current)

$main_status = null;
if ($resolution) {
    if (stripos($resolution, 'Resolved') !== false) {
        $main_status = 'Closed';
    } elseif (stripos($resolution, 'Pending') !== false) {
        $main_status = 'In Progress';
    } elseif (stripos($resolution, 'Unresolved') !== false) {
        // Maybe open?
        // $main_status = 'Open';
    }
}

// Prepare Update
// Prepare Update for Ticket Fields (Hours, Status, etc)
$sql = "UPDATE tickets SET 
        hours_worked = ?, 
        resolution_status = ?, 
        additional_comments = ?";

$params = [$hours_val, $resolution, $comments];
$types = "dss";

if ($main_status) {
    $sql .= ", status = ?";
    $params[] = $main_status;
    $types .= "s";
}

$sql .= " WHERE id = ?";
$params[] = $ticket_id;
$types .= "i";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    // If there is a new response, insert it
    if (!empty($response)) {
        $resp_stmt = $conn->prepare("INSERT INTO ticket_responses (ticket_id, user_id, response) VALUES (?, ?, ?)");
        $resp_stmt->bind_param('iis', $ticket_id, $user['id'], $response);
        $resp_stmt->execute();

        log_ticket_activity($conn, $ticket_id, $user['id'], 'Response Added', 'Technician added a new response');
    } else {
        log_ticket_activity($conn, $ticket_id, $user['id'], 'Ticket Updated', 'Technician updated ticket details');
    }

    // Notifications
    $ticket_q = $conn->query("SELECT user_id, subject FROM tickets WHERE id = $ticket_id");
    if ($ticket_q && $t_row = $ticket_q->fetch_assoc()) {
        $owner_id = $t_row['user_id'];

        // Notify if response added
        if (!empty($response)) {
            send_notification($conn, $owner_id, "New response on Ticket #$ticket_id: " . substr($response, 0, 50) . "...", "tickets.php?id=$ticket_id");
        }

        // Notify if status changed
        if ($main_status) {
            send_notification($conn, $owner_id, "Ticket #$ticket_id status updated to $main_status", "tickets.php?id=$ticket_id");
        }
    }

    $_SESSION['flash_success'] = "Ticket updated successfully.";
} else {
    $_SESSION['flash_error'] = "Failed to update ticket.";
}

header('Location: tickets.php?id=' . $ticket_id);
exit;
