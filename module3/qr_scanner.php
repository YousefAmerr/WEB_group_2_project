<?php
session_start();
require_once 'config.php';

// Get attendance ID from QR code scan
$attendanceID = isset($_GET['attendance_id']) ? $_GET['attendance_id'] : '';

if (empty($attendanceID)) {
    die("Invalid QR code. Please scan a valid attendance QR code.");
}

// Get attendance session details
$stmt = $pdo->prepare("SELECT a.*, e.eventName, e.eventLocation, e.eventLevel 
                      FROM attendance a 
                      JOIN event e ON a.eventID = e.eventID 
                      WHERE a.attendanceID = ? AND a.attendance_status = 'open'");
$stmt->execute([$attendanceID]);
$attendance_session = $stmt->fetch();

if (!$attendance_session) {
    die("Attendance session not found or has been closed.");
}

$message = '';
$error = '';

// Handle form submission for check-in
if ($_POST && isset($_POST['checkin'])) {
    $studentID = trim($_POST['studentID']);
    $password = trim($_POST['password']);
    
    if (empty($studentID) || empty($password)) {
        $error = "Please enter both Student ID and Password.";
    } else {
        // Verify student credentials
        $stmt = $pdo->prepare("SELECT * FROM student WHERE studentID = ? AND StuPassword = ?");
        $stmt->execute([$studentID, $password]);
        $student = $stmt->fetch();
        
        if (!$student) {
            $error = "Invalid Student ID or Password.";
        } else {
            // Check if student already checked in for this session
            $stmt = $pdo->prepare("SELECT * FROM attendancecslot WHERE attendanceID = ? AND studentID = ?");
            $stmt->execute([$attendanceID, $studentID]);
            $existing_checkin = $stmt->fetch();
            
            if ($existing_checkin) {
                $error = "You have already checked in for this session.";
            } else {
                // Insert check-in record
                $checkInID = uniqid();
                $stmt = $pdo->prepare("INSERT INTO attendancecslot (checkInID, attendanceID, studentID, status, attendance_date) 
                                     VALUES (?, ?, ?, 'Present', CURDATE())");
                
                if ($stmt->execute([$checkInID, $attendanceID, $studentID])) {
                    $message = "Successfully checked in! Welcome to " . htmlspecialchars($attendance_session['eventName']);
                } else {
                    $error = "Failed to check in. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Check-in - MyPetakom</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .checkin-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .event-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #007bff;
        }
        
        .event-info h2 {
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .event-details {
            display: grid;
            gap: 10px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-label {
            font-weight: bold;
            color: #666;
        }
        
        .checkin-form {
            background: #fff;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: #007bff;
            outline: none;
        }
        
        .checkin-btn {
            width: 100%;
            padding: 15px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .checkin-btn:hover {
            background: #218838;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
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
        
        .security-note {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
        }
        
        .security-note strong {
            display: block;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="checkin-container">
        <!-- Event Information -->
        <div class="event-info">
            <h2><?php echo htmlspecialchars($attendance_session['eventName']); ?></h2>
            <div class="event-details">
                <div class="detail-row">
                    <span class="detail-label">Location:</span>
                    <span><?php echo htmlspecialchars($attendance_session['eventLocation']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Level:</span>
                    <span><?php echo htmlspecialchars($attendance_session['eventLevel']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span><?php echo date('F d, Y', strtotime($attendance_session['attendanceDate'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="status-badge open"><?php echo ucfirst($attendance_session['attendance_status']); ?></span>
                </div>
            </div>
        </div>

        <!-- Security Note -->
        <div class="security-note">
            <strong>Security Verification Required</strong>
            Please enter your Student ID and password to verify your attendance. This ensures only registered students can check in.
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success">
                <strong>Success!</strong> <?php echo $message; ?>
                <br><br>
                <a href="index.php" class="btn btn-primary">Return to Home</a>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <strong>Error!</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Check-in Form -->
        <?php if (!$message): ?>
        <form method="POST" class="checkin-form">
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
            
            <button type="submit" name="checkin" class="checkin-btn">
                ✓ Check In to Event
            </button>
        </form>
        <?php endif; ?>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 30px; color: #666;">
            <small>MyPetakom Attendance System</small>
        </div>
    </div>

    <script>
        // Auto-focus on student ID field
        document.getElementById('studentID').focus();
        
        // Form validation
        document.querySelector('.checkin-form').addEventListener('submit', function(e) {
            const studentID = document.getElementById('studentID').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!studentID || !password) {
                e.preventDefault();
                alert('Please fill in both Student ID and Password.');
                return false;
            }
        });
    </script>
</body>
</html>