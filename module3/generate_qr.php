<?php
require_once '../db_connect.php';

if (!isset($_GET['att_id'])) {
    die("No attendance slot selected.");
}

$attendanceID = $_GET['att_id'];

// Fetch event info
$query = "SELECT e.eventName, e.eventLocation, e.eventLevel, a.attendanceDate 
          FROM attendance a 
          JOIN event e ON a.eventID = e.eventID 
          WHERE a.attendanceID = ?";

if (!($stmt = $conn->prepare($query))) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $attendanceID);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    die("Invalid attendance ID.");
}

$eventName = htmlspecialchars($event['eventName']);
$eventLocation = htmlspecialchars($event['eventLocation']);
$eventLevel = htmlspecialchars($event['eventLevel']);
$attendanceDate = htmlspecialchars($event['attendanceDate']);

// Generate QR code URL
$baseURL = "/projectweb/WEB_group_2_project/module3/attendance_checkin.php";
$checkinURL = $baseURL . "?att_id=" . urlencode($attendanceID);

// Use Google Chart API for QR code
$qrCodeURL = "https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=" . urlencode($checkinURL);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Generated QR Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 30px;
        }
        .qr-box {
            text-align: center;
            padding: 20px;
            border: 2px solid #007bff;
            width: fit-content;
            margin: auto;
            border-radius: 10px;
        }
        .qr-box h2 {
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="qr-box">
        <h2>QR Code for Event Check-In</h2>
        <p><strong>Event:</strong> <?= $eventName ?></p>
        <p><strong>Location:</strong> <?= $eventLocation ?></p>
        <p><strong>Level:</strong> <?= $eventLevel ?></p>
        <p><strong>Date:</strong> <?= $attendanceDate ?></p>
        <img src="<?= $qrCodeURL ?>" alt="QR Code">
        <p style="margin-top: 10px;">Scan this QR code to check in.</p>
        <a href="attendance_management.php">⬅ Back to Attendance Management</a>
    </div>
</body>
</html>
