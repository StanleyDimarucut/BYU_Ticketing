<?php
session_start(); // Must be at the top
require_once __DIR__ . '/includes/functions.php';
date_default_timezone_set("Asia/Manila");


// Show errors temporarily (for debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$host = 'localhost';
$user = 'user';          // <-- our MySQL user
$pass = 'mypassword';    // <-- password for 'user'
$name = 'byu_ticketing';

$conn = new mysqli($host, $user, $pass, $name);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

