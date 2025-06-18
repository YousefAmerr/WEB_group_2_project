<?php
session_start();
include "../db_connect.php";

// Check if attendance ID is provided
if (!isset($_GET['att_id'])) {
    die("Attendance ID is required. Please scan the QR code again.");
}

$attendanceID = $_GET['att_id'];

// Get event details for display
try {
    $query = "SELECT a.*, e.eventName, e.eventLocation, e.eventLevel, e.semester 
              FROM attendance a 
              JOIN event e ON a.eventID = e.eventID 
              WHERE a.attendanceID = ?";
    
    if (!($stmt = $conn->prepare($query))) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $attendanceID);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $eventDetails = $result->fetch_assoc();
    
    if (!$eventDetails) {
        die("Invalid attendance ID or event not found.");
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $studentID = $_POST['studentID'];
    $password = $_POST['password'];
    $latitude = floatval($_POST['latitude']);
    $longitude = floatval($_POST['longitude']);

    try {
        // 1. Validate student credentials
        $query = "SELECT * FROM student WHERE studentID = ?";
        if (!($stmt = $conn->prepare($query))) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("s", $studentID);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();

        if (!$student || $student['StuPassword'] !== $password) {
            $error = "❌ Invalid Student ID or Password.";
        } else {
            // 2. Check if student already checked in
            $query = "SELECT * FROM attendancecslot WHERE attendanceID = ? AND studentID = ?";
            if (!($stmt = $conn->prepare($query))) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("is", $attendanceID, $studentID);
            $stmt->execute();
            $result = $stmt->get_result();
            $existingCheckin = $result->fetch_assoc();
            $stmt->close();
            
            if ($existingCheckin) {
                $error = "❌ You have already checked in for this event.";
            } else {
                // 3. Validate location
                $location_map = [
                    "Gambang" => ["lat" => 3.728, "lng" => 103.126],
                    "Machanical Faculty" => ["lat" => 3.715, "lng" => 103.120],
                    "UMPSA FKOM" => ["lat" => 3.726, "lng" => 103.124],
                    "UTM" => ["lat" => 1.559, "lng" => 103.638],
                    "Astaka" => ["lat" => 3.724, "lng" => 103.123]
                ];

                $eventLoc = $eventDetails['eventLocation'];
                $expected = $location_map[$eventLoc] ?? null;

                if ($expected) {
                    // Calculate distance using Haversine formula
                    $earthRadius = 6371; // km
                    $latDiff = deg2rad($expected["lat"] - $latitude);
                    $lngDiff = deg2rad($expected["lng"] - $longitude);
                    $a = sin($latDiff/2) * sin($latDiff/2) + cos(deg2rad($latitude)) * cos(deg2rad($expected["lat"])) * sin($lngDiff/2) * sin($lngDiff/2);
                    $distance = $earthRadius * 2 * atan2(sqrt($a), sqrt(1-$a)) * 1000; // in meters
                    
                    if ($distance > 100) { // 100 meters tolerance
                        $error = "❌ You are not at the correct event location. Distance: " . round($distance) . "m";
                    } else {
                        // 4. Generate unique check-in ID
                        $query = "SELECT MAX(CAST(checkInID AS UNSIGNED)) as maxID FROM attendancecslot";
                        $result = $conn->query($query);
                        $maxID = $result->fetch_assoc();
                        $newCheckInID = ($maxID['maxID'] ?? 0) + 1;
                        
                        // 5. Insert check-in record
                        $query = "INSERT INTO attendancecslot (checkInID, attendanceID, studentID, status, attendance_date) VALUES (?, ?, ?, 'Present', CURDATE())";
                        if (!($stmt = $conn->prepare($query))) {
                            throw new Exception("Prepare failed: " . $conn->error);
                        }
                        
                        $stmt->bind_param("iis", $newCheckInID, $attendanceID, $studentID);
                        $stmt->execute();
                        $stmt->close();
                        
                        $success = "✅ Check-in successful! Welcome to " . $eventDetails['eventName'];
                    }
                } else {
                    $error = "❌ No GPS coordinates configured for this event location.";
                }
            }
        }
    } catch (Exception $e) {
        $error = "❌ System error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Attendance Check-In - MyPetakom</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .event-info {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2196f3;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .btn {
            background: #4CAF50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .error {
            color: #d32f2f;
            background: #ffebee;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .success {
            color: #388e3c;
            background: #e8f5e8;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .loading {
            text-align: center;
            padding: 20px;
            display: none;
        }
        .event-detail {
            margin: 5px 0;
        }
        .event-detail strong {
            color: #1976d2;
        }
    </style>
    <script>
    let locationObtained = false;
    
    function getLocationAndSubmit() {
        const button = document.getElementById('checkinBtn');
        const loading = document.getElementById('loading');
        
        if (!navigator.geolocation) {
            alert("❌ Geolocation is not supported by this browser.");
            return;
        }
        
        button.disabled = true;
        button.innerHTML = "Getting Location...";
        loading.style.display = 'block';
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                document.getElementById("latitude").value = position.coords.latitude;
                document.getElementById("longitude").value = position.coords.longitude;
                locationObtained = true;
                
                button.innerHTML = "Submitting...";
                document.getElementById("checkinForm").submit();
            },
            function(error) {
                let errorMsg = "Geolocation failed: ";
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMsg += "Permission denied. Please allow location access.";
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMsg += "Location information unavailable.";
                        break;
                    case error.TIMEOUT:
                        errorMsg += "Location request timed out.";
                        break;
                    default:
                        errorMsg += "Unknown error occurred.";
                        break;
                }
                alert("❌ " + errorMsg);
                button.disabled = false;
                button.innerHTML = "Check In with Location";
                loading.style.display = 'none';
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000
            }
        );
    }
    
    function validateForm() {
        const studentID = document.getElementById('studentID').value.trim();
        const password = document.getElementById('password').value.trim();
        const button = document.getElementById('checkinBtn');
        
        if (studentID && password) {
            button.disabled = false;
        } else {
            button.disabled = true;
        }
    }
    
    window.onload = function() {
        document.getElementById('studentID').addEventListener('input', validateForm);
        document.getElementById('password').addEventListener('input', validateForm);
        validateForm();
    }
    </script>
</head>
<body>
    <div class="container">
        <h2>🎯 Event Attendance Check-In</h2>
        
        <?php if ($eventDetails): ?>
        <div class="event-info">
            <h3>📋 Event Information</h3>
            <div class="event-detail"><strong>Event:</strong> <?php echo htmlspecialchars($eventDetails['eventName']); ?></div>
            <div class="event-detail"><strong>Location:</strong> <?php echo htmlspecialchars($eventDetails['eventLocation']); ?></div>
            <div class="event-detail"><strong>Level:</strong> <?php echo htmlspecialchars($eventDetails['eventLevel']); ?></div>
            <div class="event-detail"><strong>Semester:</strong> <?php echo htmlspecialchars($eventDetails['semester']); ?></div>
            <div class="event-detail"><strong>Date:</strong> <?php echo htmlspecialchars($eventDetails['attendanceDate']); ?></div>
            <div class="event-detail"><strong>Status:</strong> 
                <span style="color: <?php echo $eventDetails['attendance_status'] === 'open' ? 'green' : 'red'; ?>">
                    <?php echo ucfirst($eventDetails['attendance_status']); ?>
                </span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
            <p><a href="student_dashboard.php">← Return to Dashboard</a></p>
        <?php else: ?>
            <?php if ($eventDetails['attendance_status'] !== 'open'): ?>
                <div class="error">❌ This attendance slot is currently closed.</div>
            <?php else: ?>
                <form method="POST" id="checkinForm">
                    <div class="form-group">
                        <label for="studentID">Student ID:</label>
                        <input type="text" id="studentID" name="studentID" required placeholder="Enter your Student ID">
                    </div>

                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required placeholder="Enter your password">
                    </div>

                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">

                    <div class="loading" id="loading">
                        <p>📍 Getting your location...</p>
                    </div>

                    <button type="button" id="checkinBtn" class="btn" onclick="getLocationAndSubmit()" disabled>
                        Check In with Location
                    </button>
                </form>
                
                <p style="margin-top: 15px; font-size: 14px; color: #666;">
                    ℹ️ <strong>Note:</strong> You must be at the event location to check in successfully.
                    Your location will be verified automatically.
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>