<?php
include('includes/session.php');

// Only advisors can delete their own slots
if ($role !== 'advisor') {
    header("Location: attendance_management.php");
    exit;
}

// Ensure slot ID is provided
if (!isset($_GET['id'])) {
    header("Location: attendance_management.php");
    exit;
}

$attendanceID = $_GET['id'];

// Verify that the slot belongs to the logged-in advisor
$stmt = $conn->prepare("SELECT * FROM attendance WHERE attendanceID = ? AND advisorID = ?");
$stmt->bind_param("ss", $attendanceID, $_SESSION['advisorID']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: attendance_management.php");
    exit;
}

// Delete attendance record
$stmt = $conn->prepare("DELETE FROM attendance WHERE attendanceID = ? AND advisorID = ?");
$stmt->bind_param("ss", $attendanceID, $_SESSION['advisorID']);
$stmt->execute();

header("Location: attendance_management.php");
exit;
?>
