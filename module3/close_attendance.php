<?php
session_start();
include 'db_connect.php';

$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
if ($role === 'student') {
    include_once 'student_dashboard.php';
} elseif ($role === 'coordinator' || $role === 'petakom_coordinator') {
    include_once 'coordinator_dashboard.php';
} elseif ($role === 'advisor' || $role === 'event_advisor') {
    include_once 'advisor_dashboard.php';
}

if (!isset($_SESSION['advisorID'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid attendance ID.";
    header("Location: create_attendance_slot.php");
    exit();
}

$attendanceID = $_GET['id'];
$advisorID = $_SESSION['advisorID'];

// Verify that this attendance belongs to the advisor
$verify_query = "SELECT a.*, e.eventName 
                 FROM attendance a 
                 JOIN event e ON a.eventID = e.eventID 
                 WHERE a.attendanceID = '$attendanceID' AND e.advisorID = '$advisorID'";
$verify_result = $conn->query($verify_query);

if ($verify_result->num_rows == 0) {
    $_SESSION['error_message'] = "Attendance slot not found or access denied.";
    header("Location: create_attendance_slot.php");
    exit();
}

$attendance = $verify_result->fetch_assoc();

// Check if already closed
if ($attendance['attendance_status'] == 'closed') {
    $_SESSION['error_message'] = "This attendance slot is already closed.";
    header("Location: view_attendees.php?id=" . $attendanceID);
    exit();
}

// Close the attendance
$close_query = "UPDATE attendance SET attendance_status = 'closed' WHERE attendanceID = '$attendanceID'";

if ($conn->query($close_query) === TRUE) {
    $_SESSION['success_message'] = "Attendance for '" . $attendance['eventName'] . "' has been closed successfully.";
} else {
    $_SESSION['error_message'] = "Error closing attendance: " . $conn->error;
}

header("Location: view_attendees.php?id=" . $attendanceID);
exit();
?>