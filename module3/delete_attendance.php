<?php
session_start();
include 'db_connect.php';

$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
if ($role === 'student') {
    include_once 'student_dashboard.php';
} elseif ($role === 'coordinator' || $role === 'petakom_coordinator') {
    include_once 'coordinator_dashboard.php';
} elseif ($role === 'advisor' || $role === 'event_advisor') {
    include_once 'advisor.php';
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

// Check if there are any check-ins for this attendance
$checkin_query = "SELECT COUNT(*) as count FROM attendancecslot WHERE attendanceID = '$attendanceID'";
$checkin_result = $conn->query($checkin_query);
$checkin_count = $checkin_result->fetch_assoc()['count'];

if ($checkin_count > 0) {
    $_SESSION['error_message'] = "Cannot delete attendance slot. There are " . $checkin_count . " check-in records associated with it.";
    header("Location: view_attendees.php?id=" . $attendanceID);
    exit();
}

// Delete the attendance slot
$delete_query = "DELETE FROM attendance WHERE attendanceID = '$attendanceID'";

if ($conn->query($delete_query) === TRUE) {
    $_SESSION['success_message'] = "Attendance slot for '" . $attendance['eventName'] . "' has been deleted successfully.";
} else {
    $_SESSION['error_message'] = "Error deleting attendance slot: " . $conn->error;
}

header("Location: create_attendance_slot.php");
exit();
?>