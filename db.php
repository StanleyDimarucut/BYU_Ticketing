<?php
session_start();
// Basic DB connection
$host = 'localhost';
$user = 'root';
$pass = '';
$name = 'ticketing-system';

$conn = new mysqli($host, $user, $pass, $name);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Start Session globally
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/includes/functions.php';
?>