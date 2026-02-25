<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP password is blank

// 1. Connect to MySQL server (no database selected yet)
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Create Database
$dbname = 'byu_ticketing';
$sql = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "Database '$dbname' created successfully.\n";
} else {
    die("Error creating database: " . $conn->error);
}

// 3. Select Database
$conn->select_db($dbname);

// 4. Read SQL file
$sqlFile = 'schema.sql';
if (!file_exists($sqlFile)) {
    die("SQL file not found: $sqlFile");
}
$sql = file_get_contents($sqlFile);

// 5. Execute Multi Query
if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
        // Prepare next result set
    } while ($conn->next_result());

    if ($conn->errno) {
        echo "Error importing schema: " . $conn->error . "\n";
    } else {
        echo "Schema imported successfully.\n";
    }
} else {
    echo "Error executing multi_query: " . $conn->error . "\n";
}

$conn->close();
?>