<?php
include('includes/session.php');

// Only advisors can edit
if ($role !== 'advisor') {
    header("Location: attendance_dashboard.php");
    exit;
}

// Ensure slot ID is provided
if (!isset($_GET['id'])) {
    header("Location: attendance_management.php");
    exit;
}

$attendanceID = $_GET['id'];
$message = "";

// Fetch slot details
$stmt = $conn->prepare("SELECT * FROM attendance WHERE attendanceID = ? AND advisorID = ?");
$stmt->bind_param("ss", $attendanceID, $_SESSION['advisorID']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: attendance_management.php");
    exit;
}

$slot = $result->fetch_assoc();

// Handle update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $eventID = $_POST['eventID'];
    $attendanceDate = $_POST['attendanceDate'];
    $status = $_POST['attendance_status'];

    $stmt = $conn->prepare("UPDATE attendance SET eventID = ?, attendanceDate = ?, attendance_status = ? WHERE attendanceID = ? AND advisorID = ?");
    $stmt->bind_param("sssss", $eventID, $attendanceDate, $status, $attendanceID, $_SESSION['advisorID']);

    if ($stmt->execute()) {
        $message = "Attendance slot updated successfully!";
    } else {
        $message = "Update failed.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Attendance Slot</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .main-content {
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
        <div class="header">Edit Attendance Slot</div>

        <form method="POST" action="">
            <label for="eventID">Select Event:</label>
            <select name="eventID" required>
                <?php
                $stmt = $conn->prepare("SELECT eventID, eventName FROM event WHERE advisorID = ?");
                $stmt->bind_param("s", $_SESSION['advisorID']);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $selected = ($row['eventID'] === $slot['eventID']) ? "selected" : "";
                    echo "<option value='{$row['eventID']}' $selected>{$row['eventName']}</option>";
                }
                ?>
            </select>

            <label for="attendanceDate">Date:</label>
            <input type="date" name="attendanceDate" value="<?= $slot['attendanceDate'] ?>" required>

            <label for="attendance_status">Status:</label>
            <select name="attendance_status" required>
                <option value="open" <?= ($slot['attendance_status'] === 'open') ? 'selected' : '' ?>>Open</option>
                <option value="closed" <?= ($slot['attendance_status'] === 'closed') ? 'selected' : '' ?>>Closed</option>
            </select>

            <input type="submit" class="submit-btn" value="Update Slot">

            <?php if ($message): ?>
                <div class="message"><?= $message ?></div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
