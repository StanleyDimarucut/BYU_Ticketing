<?php
session_start(); // Must be at the top
require_once __DIR__ . '/includes/functions.php';

// Show errors temporarily (for debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$host = 'localhost';
$user = 'root';          // <-- our MySQL user
$pass = '';    // <-- password for 'user'
$name = 'byu_ticketing';

$conn = new mysqli($host, $user, $pass, $name);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

