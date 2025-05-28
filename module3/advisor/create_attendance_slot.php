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

$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $eventID = $_POST['eventID'];
    $slotDate = $_POST['slotDate'];
    $slotTime = $_POST['slotTime'];
    $slotID = uniqid("AS");

    $stmt = $conn->prepare("INSERT INTO AttendanceSlot (attendanceSlotID, advisorID, eventID, slotDate, slotTime) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $slotID, $advisorID, $eventID, $slotDate, $slotTime);
    if ($stmt->execute()) {
        $message = "Slot created.";
    } else {
        $message = "Error.";
    }
    $stmt->close();
}

$events = [];
$eventStmt = $conn->prepare("SELECT eventID, eventName FROM Event WHERE advisorID = ?");
$eventStmt->bind_param("s", $advisorID);
$eventStmt->execute();
$eventResult = $eventStmt->get_result();
while ($row = $eventResult->fetch_assoc()) {
    $events[] = $row;
}
$eventStmt->close();
?>
<!DOCTYPE html>
<html>
<head><title>Create Slot</title></head>
<body>
<div class="main-content">
    <h2>Create Attendance Slot</h2>
    <p style="color:green;"><?php echo $message; ?></p>
    <form method="post">
        <label>Event:</label>
        <select name="eventID" required>
            <?php foreach ($events as $event): ?>
                <option value="<?php echo $event['eventID']; ?>"><?php echo $event['eventName']; ?></option>
            <?php endforeach; ?>
        </select><br><br>
        <label>Date:</label><input type="date" name="slotDate" required><br><br>
        <label>Time:</label><input type="time" name="slotTime" required><br><br>
        <button type="submit">Create</button>
    </form>
</div>
</body>
</html>