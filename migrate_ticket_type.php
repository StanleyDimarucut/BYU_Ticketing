<?php
require_once 'db.php';

// Add ticket_type column to tickets table
$sql = "ALTER TABLE tickets ADD COLUMN ticket_type ENUM('Incident','Request') NOT NULL DEFAULT 'Incident' AFTER category";

if ($conn->query($sql) === TRUE) {
    echo "Migration successful: 'ticket_type' column added to tickets table.<br>";
    echo "<a href='dashboard.php'>Go to Dashboard</a>";
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        echo "Column 'ticket_type' already exists. No changes needed.<br>";
        echo "<a href='dashboard.php'>Go to Dashboard</a>";
    } else {
        echo "Migration failed: " . $conn->error;
    }
}

$conn->close();
?>