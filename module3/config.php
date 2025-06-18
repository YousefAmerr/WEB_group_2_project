<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mypetakom";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8");

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to generate unique ID
function generateID($prefix, $conn, $table, $column) {
    $query = "SELECT MAX(CAST(SUBSTRING($column, 2) AS UNSIGNED)) as max_id FROM $table WHERE $column LIKE '$prefix%'";
    $result = $conn->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        $max_id = $row['max_id'] ? $row['max_id'] : 0;
        return $prefix . ($max_id + 1);
    }
    return $prefix . "1";
}
?>