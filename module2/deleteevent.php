<?php
include '../db_connect.php';

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