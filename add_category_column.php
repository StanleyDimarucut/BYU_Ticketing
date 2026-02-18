<?php
require_once 'db.php';

// Add category column
$sql = "ALTER TABLE tickets ADD COLUMN category VARCHAR(50) NOT NULL DEFAULT 'Other' AFTER description";

if ($conn->query($sql) === TRUE) {
    echo "Column 'category' added successfully.";
} else {
    echo "Error adding column: " . $conn->error;
}
?>