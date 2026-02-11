<?php
require_once 'db.php';

// 1. Alter Users Table
try {
    echo "Migrating Users table...\n";
    // Check if email column exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'email'");
    if ($result && $result->num_rows > 0) {
        // Add username column
        $conn->query("ALTER TABLE users ADD COLUMN username VARCHAR(50) NOT NULL AFTER name");
        // For existing users, temporarily fill username with email prefix or random string to avoid duplicate errors
        // Note: This is destructive to 'email' data if we drop it, assuming dev env.

        $users = $conn->query("SELECT id, email FROM users");
        while ($u = $users->fetch_assoc()) {
            $parts = explode('@', $u['email']);
            $username = $parts[0] . '_' . $u['id']; // ensure unique
            $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->bind_param('si', $username, $u['id']);
            $stmt->execute();
        }

        // Drop email column
        $conn->query("ALTER TABLE users DROP COLUMN email");
        // Add unique index to username
        $conn->query("ALTER TABLE users ADD UNIQUE KEY username (username)");
        echo "Users table migrated. Email replaced with Username.\n";
    } else {
        echo "Users table already migrated or email column missing.\n";
    }
} catch (Exception $e) {
    echo "Error updating users: " . $e->getMessage() . "\n";
}

// 2. Alter Tickets Table
try {
    echo "Migrating Tickets table...\n";
    $cols_to_add = [
        'technician_response' => "TEXT DEFAULT NULL",
        'hours_worked' => "DECIMAL(10,2) DEFAULT NULL",
        'importance' => "VARCHAR(50) DEFAULT NULL",
        'resolution_status' => "VARCHAR(50) DEFAULT NULL",
        'additional_comments' => "TEXT DEFAULT NULL"
    ];

    foreach ($cols_to_add as $col => $def) {
        $result = $conn->query("SHOW COLUMNS FROM tickets LIKE '$col'");
        if ($result && $result->num_rows == 0) {
            $conn->query("ALTER TABLE tickets ADD COLUMN $col $def");
            echo "Added column $col.\n";
        } else {
            echo "Column $col already exists.\n";
        }
    }
} catch (Exception $e) {
    echo "Error updating tickets: " . $e->getMessage() . "\n";
}

echo "Migration complete.\n";
