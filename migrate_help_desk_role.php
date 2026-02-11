<?php
require_once 'db.php';

// 1. Modify the ENUM to include 'help_desk' (retaining 'user' temporarily to avoid data loss during transition if strict mode is on)
// Actually, we can just alter it to the new set if we are careful, but usually it's safer to add, update data, then drop.
// However, for this simple setup, we can try to do it in one go or multiple steps. 
// A safer approach for MySQL/MariaDB ENUMs involves changing the column definition.

echo "Starting migration: 'user' -> 'help_desk'...\n";

// Step 1: Allow both old and new values
$sql1 = "ALTER TABLE users MODIFY COLUMN role ENUM('user', 'admin', 'technician', 'help_desk') NOT NULL DEFAULT 'help_desk'";
if ($conn->query($sql1)) {
    echo "Step 1: ENUM updated to include 'help_desk'.\n";
} else {
    die("Error Step 1: " . $conn->error . "\n");
}

// Step 2: Update existing 'user' records to 'help_desk'
$sql2 = "UPDATE users SET role = 'help_desk' WHERE role = 'user'";
if ($conn->query($sql2)) {
    echo "Step 2: Updated existing 'user' records to 'help_desk'. Rows matched: " . $conn->affected_rows . "\n";
} else {
    die("Error Step 2: " . $conn->error . "\n");
}

// Step 3: Remove 'user' from ENUM
$sql3 = "ALTER TABLE users MODIFY COLUMN role ENUM('help_desk', 'admin', 'technician') NOT NULL DEFAULT 'help_desk'";
if ($conn->query($sql3)) {
    echo "Step 3: ENUM updated to remove 'user'. Migration complete.\n";
} else {
    die("Error Step 3: " . $conn->error . "\n");
}
