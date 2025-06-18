<?php
// DB connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'mypetakom';
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['eventID'])) {
    die("No event ID specified.");
}

$eventID = $_GET['eventID'];

// Delete the event
$stmt = $conn->prepare("DELETE FROM event WHERE eventID = ?");
$stmt->bind_param("s", $eventID);
if ($stmt->execute()) {
    echo "<script>alert('Event deleted successfully.'); window.location.href='event.php';</script>";
} else {
    echo "Error deleting event: " . $conn->error;
}
?>