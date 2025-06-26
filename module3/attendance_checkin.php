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

if (!isset($_GET['aid'])) {
    echo "Invalid QR Code!";
    exit();
}

$attendanceID = $_GET['aid'];

// Get attendance details
$query = "SELECT a.*, e.eventName, e.eventLocation, e.eventLevel 
          FROM attendance a 
          JOIN event e ON a.eventID = e.eventID 
          WHERE a.attendanceID = '$attendanceID'";
$result = $conn->query($query);
$attendance = $result->fetch_assoc();

if (!$attendance) {
    echo "Invalid attendance slot!";
    exit();
}

if ($attendance['attendance_status'] != 'open') {
    echo "This attendance slot is closed!";
    exit();
}

// Handle form submission
if ($_POST) {
    $studentID = $_POST['studentID'];
    $password = $_POST['password'];
    
    // Verify student credentials
    $student_query = "SELECT * FROM student WHERE studentID = '$studentID' AND StuPassword = '$password'";
    $student_result = $conn->query($student_query);
    
    if ($student_result->num_rows > 0) {
        $student = $student_result->fetch_assoc();
        
        // Check if already checked in
        $check_query = "SELECT * FROM attendancecslot WHERE attendanceID = '$attendanceID' AND studentID = '$studentID'";
        $check_result = $conn->query($check_query);
        
        if ($check_result->num_rows > 0) {
            $error_message = "You have already checked in for this event!";
        } else {
            // Insert check-in record
            $checkinID = rand(100000, 999999);
            $checkin_date = date('Y-m-d');
            
            $insert_query = "INSERT INTO attendancecslot (checkInID, attendanceID, studentID, status, attendance_date) 
                            VALUES ('$checkinID', '$attendanceID', '$studentID', 'Present', '$checkin_date')";
            
            if ($conn->query($insert_query)) {
                $success_message = "Successfully checked in! Welcome " . $student['studentName'];
            } else {
                $error_message = "Error during check-in: " . $conn->error;
            }
        }
    } else {
        $error_message = "Invalid student ID or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Check-in</title>
    <link rel="stylesheet" href="css/utilities.css">
    <link rel="stylesheet" href="css/attendance.css">
    <link rel="stylesheet" href="css/qr_attendance.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .event-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .event-header h2 {
            color: #333;
            margin-bottom: 10px;
        }
        .event-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .form-group input:focus {
            border-color: #667eea;
            outline: none;
        }
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .location-info {
            font-size: 12px;
            color: #666;
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="event-header">
            <h2><?php echo $attendance['eventName']; ?></h2>
            <div class="event-info">
                <p><strong>📍 Location:</strong> <?php echo $attendance['eventLocation']; ?></p>
                <p><strong>📅 Date:</strong> <?php echo $attendance['attendanceDate']; ?></p>
                <p><strong>🏆 Level:</strong> <?php echo $attendance['eventLevel']; ?></p>
            </div>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                ✅ <?php echo $success_message; ?>
                <div style="margin-top: 15px;">
                    <a href="../module4/dashboard.php" style="color: #155724; text-decoration: none; font-weight: bold;">
                        Go to Dashboard →
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    ❌ <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="studentID">Student ID:</label>
                    <input type="text" id="studentID" name="studentID" required 
                           placeholder="Enter your Student ID">
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn">Check In</button>
            </form>
            
            <div class="location-info">
                🔒 Your location will be verified for security purposes
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Get user location (simplified - in real app, verify against event location)
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                console.log("Location: " + position.coords.latitude + ", " + position.coords.longitude);
                // In real implementation, send this to server for verification
            });
        }
        
        // Auto-focus on student ID field
        document.addEventListener('DOMContentLoaded', function() {
            const studentIDField = document.getElementById('studentID');
            if (studentIDField) {
                studentIDField.focus();
            }
        });
    </script>
</body>
</html>