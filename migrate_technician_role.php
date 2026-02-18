<?php
require_once 'db.php';

// Add 'technician' to the ENUM list for the 'role' column in 'users' table
$sql = "ALTER TABLE users MODIFY COLUMN role ENUM('user', 'admin', 'technician') NOT NULL DEFAULT 'user'";

if ($conn->query($sql) === TRUE) {
    echo "Database updated successfully: Added 'technician' role.\n";
} else {
    echo "Error updating database: " . $conn->error . "\n";
}
