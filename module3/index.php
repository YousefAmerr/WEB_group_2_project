<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../module1/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>MyPetakom Attendance Module</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="main-content">
    <h2>Welcome to MyPetakom - Manage Event Attendance</h2>
    <p>Please use the sidebar to access your module features.</p>
</div>
</body>
</html>


// db_connect.php
<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mypetakom";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
