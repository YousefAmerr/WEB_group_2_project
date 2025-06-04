<?php
session_start();
include '../includes/db_connect.php';
include '../sideBar/Student_SideBar.php';

if (!isset($_SESSION['username'])) {
    header("Location: ../module1/login.php");
    exit();
}

$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT studentID FROM student WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$studentID = $row['studentID'] ?? '';
$stmt->close();

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $slotID = $_POST['slotID'];
    $location = $_POST['location'] ?? 'Unknown';
    $checkInID = uniqid("CI");
    $checkInTime = date("Y-m-d H:i:s");

    // Optional: validate slotID exists
    $checkSlot = $conn->prepare("SELECT * FROM AttendanceSlot WHERE attendanceSlotID = ?");
    $checkSlot->bind_param("s", $slotID);
    $checkSlot->execute();
    $slotExists = $checkSlot->get_result()->num_rows > 0;
    $checkSlot->close();

    if ($slotExists) {
        $stmt = $conn->prepare("INSERT INTO AttendanceCheckIn (checkInID, attendanceSlotID, studentID, checkInTime, location) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $checkInID, $slotID, $studentID, $checkInTime, $location);
        if ($stmt->execute()) {
            $message = "✅ Successfully checked in!";
        } else {
            $message = "❌ Error: Could not check in.";
        }
        $stmt->close();
    } else {
        $message = "❌ Invalid attendance slot ID.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendance Check-In</title>
    <script>
        function submitCheckIn() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    document.getElementById('location').value =
                        position.coords.latitude + ',' + position.coords.longitude;
                    document.getElementById('checkinForm').submit();
                }, function() {
                    alert('Geolocation failed. Allow location access.');
                });
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }
    </script>
</head>
<body>
<div class="main-content">
    <h2>Event Attendance Check-In</h2>
    <?php if ($message): ?>
        <p style="color:green;"><?php echo $message; ?></p>
    <?php endif; ?>
    <form id="checkinForm" method="POST">
        <label>Slot ID (from QR code):</label><br>
        <input type="text" name="slotID" required><br><br>
        <input type="hidden" name="location" id="location">
        <button type="button" onclick="submitCheckIn()">Check In with Location</button>
    </form>
</div>
</body>
</html>