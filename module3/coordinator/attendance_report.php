<?php
session_start();
include '../includes/db_connect.php';
include '../sideBar/Coordinator_SideBar.php';

if (!isset($_SESSION['username'])) {
    header("Location: ../module1/login.php");
    exit();
}

$username = $_SESSION['username'];

// Fetch attendance records
$attendances = [];
$sql = "SELECT ac.checkInID, s.studentName, s.studentEmail, e.eventName, a.slotDate, a.slotTime, ac.checkInTime, ac.location
        FROM AttendanceCheckIn ac
        JOIN student s ON ac.studentID = s.studentID
        JOIN AttendanceSlot a ON ac.attendanceSlotID = a.attendanceSlotID
        JOIN Event e ON a.eventID = e.eventID
        ORDER BY ac.checkInTime DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $attendances[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendance Report</title>
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f5f5f5; }
    </style>
</head>
<body>
<div class="main-content">
    <h2>Event Attendance Report</h2>
    <p>Total Attendance Records: <?php echo count($attendances); ?></p>
    <table>
        <thead>
            <tr>
                <th>Check-In ID</th>
                <th>Student</th>
                <th>Email</th>
                <th>Event</th>
                <th>Slot Date</th>
                <th>Slot Time</th>
                <th>Check-In Time</th>
                <th>Location (Lat, Long)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($attendances as $row): ?>
            <tr>
                <td><?php echo $row['checkInID']; ?></td>
                <td><?php echo htmlspecialchars($row['studentName']); ?></td>
                <td><?php echo htmlspecialchars($row['studentEmail']); ?></td>
                <td><?php echo htmlspecialchars($row['eventName']); ?></td>
                <td><?php echo $row['slotDate']; ?></td>
                <td><?php echo $row['slotTime']; ?></td>
                <td><?php echo $row['checkInTime']; ?></td>
                <td><?php echo htmlspecialchars($row['location']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>