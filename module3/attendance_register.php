<?php
include('includes/db_connection.php');
session_start();

$message = "";
$slotValid = false;
$slotDetails = [];

if (isset($_GET['attendanceID'])) {
    $attendanceID = $_GET['attendanceID'];

    // Fetch slot info
    $stmt = $conn->prepare("SELECT A.attendanceID, A.attendanceDate, A.attendance_status, E.eventName, E.eventLocation
                            FROM attendance A
                            JOIN event E ON A.eventID = E.eventID
                            WHERE A.attendanceID = ?");
    $stmt->bind_param("s", $attendanceID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $slotValid = true;
        $slotDetails = $result->fetch_assoc();
    } else {
        $message = "Invalid or expired attendance slot.";
    }
} else {
    $message = "No attendance slot specified.";
}

// Handle student submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['studentID'], $_POST['password'], $_POST['lat'], $_POST['lon'], $_POST['attendanceID'])) {
    $studentID = $_POST['studentID'];
    $password = $_POST['password'];
    $lat = $_POST['lat'];
    $lon = $_POST['lon'];
    $attendanceID = $_POST['attendanceID'];

    // Check student credentials
    $stmt = $conn->prepare("SELECT * FROM student WHERE studentID = ? AND StuPassword = ?");
    $stmt->bind_param("ss", $studentID, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Optional: Verify geolocation vs event location (simplified)

        // Prevent duplicate attendance
        $check = $conn->prepare("SELECT * FROM attendancecslot WHERE studentID = ? AND attendanceID = ?");
        $check->bind_param("ss", $studentID, $attendanceID);
        $check->execute();
        $already = $check->get_result();
        if ($already->num_rows > 0) {
            $message = "You have already registered attendance for this slot.";
        } else {
            // Register attendance
            $checkInID = uniqid("chk_");
            $today = date("Y-m-d");

            $stmt = $conn->prepare("INSERT INTO attendancecslot (checkInID, attendanceID, studentID, status, attendance_date)
                                    VALUES (?, ?, ?, 'Present', ?)");
            $stmt->bind_param("ssss", $checkInID, $attendanceID, $studentID, $today);
            if ($stmt->execute()) {
                $message = "Attendance successfully recorded!";
            } else {
                $message = "Error recording attendance.";
            }
        }
    } else {
        $message = "Invalid student ID or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Event Attendance Registration</title>
    <style>
        body {
            font-family: Arial;
            background: #f0f2f5;
            padding: 40px;
        }
        .container {
            background: #fff;
            padding: 30px;
            max-width: 600px;
            margin: auto;
            border-radius: 10px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }
        h2 {
            color: #1c2b5a;
        }
        .info {
            margin-bottom: 20px;
        }
        label {
            font-weight: bold;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .submit-btn {
            background: #1c2b5a;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .message {
            margin-top: 20px;
            color: green;
            font-weight: bold;
        }
    </style>
    <script>
        function setLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    document.getElementById("lat").value = position.coords.latitude;
                    document.getElementById("lon").value = position.coords.longitude;
                }, function() {
                    alert("Geolocation is required to register attendance.");
                });
            } else {
                alert("Geolocation is not supported by your browser.");
            }
        }
        window.onload = setLocation;
    </script>
</head>
<body>
    <div class="container">
        <h2>Attendance Registration</h2>

        <?php if ($slotValid): ?>
            <div class="info">
                <p><strong>Event:</strong> <?= $slotDetails['eventName'] ?></p>
                <p><strong>Location:</strong> <?= $slotDetails['eventLocation'] ?></p>
                <p><strong>Date:</strong> <?= $slotDetails['attendanceDate'] ?></p>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="attendanceID" value="<?= htmlspecialchars($attendanceID) ?>">
                <input type="hidden" name="lat" id="lat" value="">
                <input type="hidden" name="lon" id="lon" value="">

                <label for="studentID">Student ID:</label>
                <input type="text" name="studentID" required>

                <label for="password">Password:</label>
                <input type="password" name="password" required>

                <input type="submit" class="submit-btn" value="Register Attendance">
            </form>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
