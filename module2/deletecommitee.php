<?php
// DB connection
include '../db_connect.php';

// Check meritApplicationID is passed
if (!isset($_GET['meritApplicationID'])) {
    die("No merit application ID specified.");
}

$meritApplicationID = $_GET['meritApplicationID'];

// Delete the committee member
$stmt = $conn->prepare("DELETE FROM meritapplication WHERE meritApplicationID = ?");
$stmt->bind_param("s", $meritApplicationID);

if ($stmt->execute()) {
    echo "<script>alert('Committee member deleted successfully.'); window.location.href='committee.php';</script>";
} else {
    echo "Error deleting committee member: " . $conn->error;
}
?>