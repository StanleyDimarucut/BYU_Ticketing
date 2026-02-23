<?php
require_once 'db.php';
require_once 'includes/auth.php';

$user = require_login();

// Restrict to Admin/Technician
if ($user['role'] !== 'admin' && $user['role'] !== 'technician') {
    die("Access denied");
}

// Build Query (Same logic as reports.php)
$params = [];
$types = "";
$where = ["1=1"];

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$type_filter = $_GET['ticket_type'] ?? '';

if ($start_date) {
    $where[] = "created_at >= ?";
    $params[] = $start_date . " 00:00:00";
    $types .= "s";
}
if ($end_date) {
    $where[] = "created_at <= ?";
    $params[] = $end_date . " 23:59:59";
    $types .= "s";
}
if ($status_filter) {
    $where[] = "status = ?";
    $params[] = $status_filter;
    $types .= "s";
}
if ($priority_filter) {
    $where[] = "priority = ?";
    $params[] = $priority_filter;
    $types .= "s";
}
if ($type_filter) {
    $where[] = "ticket_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

// Select fields for CSV
$sql = "SELECT id, subject, status, priority, category, ticket_type, created_at, resolution_status, hours_worked FROM tickets WHERE " . implode(" AND ", $where) . " ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Set Headers for Download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="tickets_report_' . date('Y-m-d') . '.csv"');

// Open Output Stream
$fp = fopen('php://output', 'w');

// CSV Header Row
fputcsv($fp, ['Ticket ID', 'Subject', 'Status', 'Priority', 'Category', 'Type', 'Created Date', 'Resolution', 'Hours Worked']);

// CSV Data Rows
while ($row = $result->fetch_assoc()) {
    fputcsv($fp, [
        $row['id'],
        $row['subject'],
        $row['status'],
        $row['priority'],
        $row['category'],
        $row['ticket_type'],
        $row['created_at'],
        $row['resolution_status'],
        $row['hours_worked']
    ]);
}

fclose($fp);
exit;
