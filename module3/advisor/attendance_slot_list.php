<?php
session_start();
include '../includes/db_connect.php';
include '../sideBar/Advisor_SideBar.php';

if (!isset($_SESSION['username'])) {
    header("Location: ../module1/login.php");
    exit();
}

$username = $_SESSION['username'];
$advisorID = '';
$stmt = $conn->prepare("SELECT advisorID FROM advisor WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $advisorID = $row['advisorID'];
}
$stmt->close();

if (isset($_GET['delete'])) {
    $deleteID = $_GET['delete'];
    $delStmt = $conn->prepare("DELETE FROM AttendanceSlot WHERE attendanceSlotID = ? AND advisorID = ?");
    $delStmt->bind_param("ss", $deleteID, $advisorID);
    $delStmt->execute();
    $delStmt->close();
    header("Location: attendance_slot_list.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['slotID'])) {
    $updateStmt = $conn->prepare("UPDATE AttendanceSlot SET slotDate = ?, slotTime = ? WHERE attendanceSlotID = ? AND advisorID = ?");
    $updateStmt->bind_param("ssss", $_POST['slotDate'], $_POST['slotTime'], $_POST['slotID'], $advisorID);
    $updateStmt->execute();
    $updateStmt->close();
}

$slots = [];
$query = "SELECT a.attendanceSlotID, e.eventName, a.slotDate, a.slotTime FROM AttendanceSlot a JOIN Event e ON a.eventID = e.eventID WHERE a.advisorID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $advisorID);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $slots[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attendance Slot List</title>
</head>
<body>
<div class="main-content">
    <h2>My Attendance Slots</h2>
    <table border="1">
        <tr>
            <th>Slot ID</th>
            <th>Event</th>
            <th>Date</th>
            <th>Time</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($slots as $slot): ?>
        <tr>
            <form method="post">
                <td><input type="hidden" name="slotID" value="<?php echo $slot['attendanceSlotID']; ?>"><?php echo $slot['attendanceSlotID']; ?></td>
                <td><?php echo $slot['eventName']; ?></td>
                <td><input type="date" name="slotDate" value="<?php echo $slot['slotDate']; ?>"></td>
                <td><input type="time" name="slotTime" value="<?php echo $slot['slotTime']; ?>"></td>
                <td>
                    <button type="submit">Update</button>
                    <a href="?delete=<?php echo $slot['attendanceSlotID']; ?>" onclick="return confirm('Delete this slot?')">Delete</a>
                    <a href="display_qr.php?slotID=<?php echo $slot['attendanceSlotID']; ?>" target="_blank">Show QR</a>
                </td>
            </form>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>