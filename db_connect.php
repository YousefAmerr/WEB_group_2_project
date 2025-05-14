<?php
// Database connection settings
$host = 'localhost';      // Your database host (localhost in most cases)
$username = 'root';       // Your MySQL username (default is usually 'root')
$password = '';           // Your MySQL password (leave empty if no password)
$dbname = 'mypetakom';    // The name of your database

// Create a connection to the database
$conn = mysqli_connect($host, $username, $password, $dbname);

// Check the connection
if (!$conn) {
    // If the connection fails, display an error message and stop execution
    die("Connection failed: " . mysqli_connect_error());
}

// Optional: You can set the character encoding for the connection (useful for special characters)
mysqli_set_charset($conn, "utf8");

?>
