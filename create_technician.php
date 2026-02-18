<?php
require_once 'db.php';

// Create a technician user
$username = 'tech_user';
$password = 'password123';
$name = 'Technician One';
$role = 'technician';

// Check if user exists
$check = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check->bind_param("s", $username);
$check->execute();
$res = $check->get_result();

if ($row = $res->fetch_assoc()) {
    // Update role if exists
    $stmt = $conn->prepare("UPDATE users SET role = ?, password = ? WHERE id = ?");
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt->bind_param("ssi", $role, $hash, $row['id']);
    if ($stmt->execute()) {
        echo "User '{$username}' updated to role '{$role}'. Password reset to '{$password}'.\n";
    } else {
        echo "Error updating user: " . $conn->error . "\n";
    }
} else {
    // Create new user
    $stmt = $conn->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt->bind_param("ssss", $name, $username, $hash, $role);
    if ($stmt->execute()) {
        echo "User '{$username}' created with role '{$role}'. Password: '{$password}'.\n";
    } else {
        echo "Error creating user: " . $conn->error . "\n";
    }
}
