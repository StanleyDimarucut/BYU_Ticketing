<?php
require_once 'db.php';

// 1. Create table
$sql = "CREATE TABLE IF NOT EXISTS `ticket_responses` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `response` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `responses_ticket_fk` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `responses_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Table 'ticket_responses' created successfully.\n";
} else {
    die("Error creating table: " . $conn->error . "\n");
}

// 2. Migrate existing responses
// We need to fetch tickets with non-empty technician_response
$res = $conn->query("SELECT id, technician_response, created_at FROM tickets WHERE technician_response IS NOT NULL AND technician_response != ''");

if ($res) {
    $count = 0;
    $stmt = $conn->prepare("INSERT INTO ticket_responses (ticket_id, user_id, response, created_at) VALUES (?, ?, ?, ?)");

    // We don't know *who* wrote the original response easily if we didn't track it.
    // We'll assign it to the first admin we find, or a fallback system user, or just leave user_id as a valid admin ID.
    // Let's find an admin ID to use as the default 'migrated' author.
    $admin_res = $conn->query("SELECT id FROM users WHERE role IN ('admin', 'technician') LIMIT 1");
    $admin_row = $admin_res->fetch_assoc();
    $default_user_id = $admin_row['id'] ?? 1; // Fallback to ID 1

    while ($row = $res->fetch_assoc()) {
        $ticket_id = $row['id'];
        $response = $row['technician_response'];
        // Use ticket creation time or current time? Maybe current time is safer to avoid confusion, 
        // OR simply user current time for the migration. Let's use NOW for simplicity unless we have a 'updated_at' on tickets (we don't).
        $created_at = date('Y-m-d H:i:s');

        $stmt->bind_param('iiss', $ticket_id, $default_user_id, $response, $created_at);
        if ($stmt->execute()) {
            $count++;
        }
    }
    echo "Migrated $count existing responses.\n";
} else {
    echo "Error fetching existing responses: " . $conn->error . "\n";
}

// 3. Drop the old column? 
// Ideally yes, but let's keep it for safety for now, or just ignore it.
// $conn->query("ALTER TABLE tickets DROP COLUMN technician_response");

echo "Migration complete.\n";
