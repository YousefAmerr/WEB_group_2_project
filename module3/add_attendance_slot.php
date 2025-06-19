<?php
include('includes/session.php');

// Only advisors can access this page
if ($role !== 'advisor') {
    header("Location: attendance_dashboard.php");
    exit;
}

include('includes/advisor_sidebar.php');

// Handle form submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $eventID = $_POST['eventID'];
    $attendanceDate = $_POST['attendanceDate'];
    $attendance_status = $_POST['attendance_status'];

    // Generate unique attendanceID (e.g., using uniqid)
    $attendanceID = uniqid('att_');

    $stmt = $conn->prepare("INSERT INTO attendance (attendanceID, advisorID, eventID, attendanceDate, attendance_status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $attendanceID, $_SESSION['advisorID'], $eventID, $attendanceDate, $attendance_status);

    if ($stmt->execute()) {
        $message = "Attendance slot added successfully!";
    } else {
        $message = "Failed to add attendance slot.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Add Attendance Slot</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .main-content {
            margin-left: 270px;
            padding: 40px;
            background-color: #f5f6fa;
        }
        .header {
            font-size: 26px;
            font-weight: bold;
            color: #1c2b5a;
            margin-bottom: 30px;
        }
        form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.05);
            max-width: 600px;
        }
        label {
            display: block;
            margin-top: 20px;
            margin-bottom: 6px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .submit-btn {
            margin-top: 25px;
            background-color: #1c2b5a;
            color: white;
            padding: 12px 18px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }
        .message {
            margin-top: 20px;
            font-weight: bold;
            color: green;
        }
    </style>
</head>
<body>
<div class="main-content">
    <div class="header">Add Attendance Slot</div>

    <form method="POST" action="">
        <label for="eventID">Select Event:</label>
        <select name="eventID" required>
            <option value="">-- Select Event --</option>
            <?php
            $stmt = $conn->prepare("SELECT eventID, eventName FROM event WHERE advisorID = ?");
            $stmt->bind_param("s", $_SESSION['advisorID']);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                echo "<option value='{$row['eventID']}'>{$row['eventName']}</option>";
            }
            ?>
        </select>

        <label for="attendanceDate">Date:</label>
        <input type="date" name="attendanceDate" required>

        <label for="attendance_status">Status:</label>
        <select name="attendance_status" required>
            <option value="open">Open</option>
            <option value="closed">Closed</option>
        </select>

        <input type="submit" class="submit-btn" value="Create Slot">

        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
    </form>
</div>
</body>
</html>