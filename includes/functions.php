<?php
// includes/functions.php

/**
 * Log a ticket activity.
 * 
 * @param mysqli $conn Database connection
 * @param int $ticket_id The ID of the ticket
 * @param int $user_id The ID of the user performing the action
 * @param string $action Short description of the action (e.g., 'Created', 'Status Update')
 * @param string|null $details Detailed description
 * @return void
 */
function log_ticket_activity($conn, $ticket_id, $user_id, $action, $details = null)
{
    $stmt = $conn->prepare("INSERT INTO ticket_logs (ticket_id, user_id, action, details) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iiss", $ticket_id, $user_id, $action, $details);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Send a notification to a specific user.
 */
function send_notification($conn, $user_id, $message, $link = '#')
{
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iss", $user_id, $message, $link);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Broadcast a notification to all users with a specific role.
 * Role can be a string 'admin' or array ['admin', 'technician']
 */
function broadcast_notification($conn, $roles, $message, $link = '#')
{
    $roles = (array) $roles;
    // Prepare string for IN clause
    $in = str_repeat('?,', count($roles) - 1) . '?';
    $types = str_repeat('s', count($roles));

    // Select users with these roles
    $sql = "SELECT id FROM users WHERE role IN ($in)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$roles);
    $stmt->execute();
    $res = $stmt->get_result();

    $users = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Insert notifications
    if (!empty($users)) {
        $insert_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        foreach ($users as $u) {
            $insert_stmt->bind_param("iss", $u['id'], $message, $link);
            $insert_stmt->execute();
        }
        $insert_stmt->close();
    }
}

/**
 * Get unread notifications count for a user.
 */
function get_unread_count($conn, $user_id)
{
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res['c'] ?? 0;
}
