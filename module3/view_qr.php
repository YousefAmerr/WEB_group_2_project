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

if (!isset($_GET['id'])) {
    header("Location: create_attendance_slot.php");
    exit();
}

$attendanceID = $_GET['id'];

// Get attendance and QR details
$query = "SELECT a.*, e.eventName, e.eventLocation, q.qrLink 
          FROM attendance a 
          JOIN event e ON a.eventID = e.eventID 
          LEFT JOIN qrcode q ON a.attendanceID = q.attendanceID 
          WHERE a.attendanceID = '$attendanceID'";
$result = $conn->query($query);
$attendance = $result->fetch_assoc();

if (!$attendance) {
    echo "Attendance slot not found!";
    exit();
}

// Generate QR code URL for display (using online QR generator for simplicity)
$qr_content = "http://localhost/mypetakom/module3/attendance_checkin.php?aid=" . $attendanceID;
$qr_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qr_content);
?>

<!DOCTYPE html>
<html>
<head>
    <title>QR Code - <?php echo $attendance['eventName']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 20px;
            background: #f5f5f5;
        }
        .qr-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            display: inline-block;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .event-info {
            margin-bottom: 20px;
            color: #333;
        }
        .qr-code {
            margin: 20px 0;
        }
        .instructions {
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
        .back-btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="qr-container">
        <div class="event-info">
            <h2><?php echo $attendance['eventName']; ?></h2>
            <p><strong>Location:</strong> <?php echo $attendance['eventLocation']; ?></p>
            <p><strong>Date:</strong> <?php echo $attendance['attendanceDate']; ?></p>
        </div>
        
        <div class="qr-code">
            <img src="<?php echo $qr_image_url; ?>" alt="QR Code" />
        </div>
        
        <div class="instructions">
            <p><strong>Instructions:</strong></p>
            <p>1. Students should scan this QR code to register attendance</p>
            <p>2. They will need to enter their student ID and password</p>
            <p>3. Location verification will be performed automatically</p>
        </div>
        
        <a href="create_attendance_slot.php" class="back-btn">Back to Attendance Management</a>
    </div>
    
    <script>
        // Auto print function for printing QR code
        function printQR() {
            window.print();
        }
        
        // Add print button
        document.addEventListener('DOMContentLoaded', function() {
            const printBtn = document.createElement('button');
            printBtn.innerHTML = 'Print QR Code';
            printBtn.style.cssText = 'background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;';
            printBtn.onclick = printQR;
            document.querySelector('.qr-container').appendChild(printBtn);
        });
    </script>
</body>
</html>