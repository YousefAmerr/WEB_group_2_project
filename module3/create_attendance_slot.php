<?php
session_start();
require_once 'db_connect.php';

// Robust session/role check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || 
    ($_SESSION['role'] !== 'advisor' && $_SESSION['role'] !== 'event_advisor')) {
    header('Location: login.php');
    exit();
}

$role = $_SESSION['role'];
$advisorID = $_SESSION['user_id']; // Use the correct session variable

// Get events for this advisor
$events_query = "SELECT * FROM event WHERE advisorID = '$advisorID'";
$events_result = $conn->query($events_query);

// Handle form submission
if ($_POST) {
    $eventID = $_POST['eventID'];
    $attendanceDate = $_POST['attendanceDate'];
    
    // Generate unique attendance ID
    $attendanceID = 'att_' . rand(100000, 999999);
    
    // Insert attendance record
    $insert_query = "INSERT INTO attendance (attendanceID, advisorID, eventID, attendanceDate, attendance_status) 
                     VALUES ('$attendanceID', '$advisorID', '$eventID', '$attendanceDate', 'open')";
    
    if ($conn->query($insert_query)) {
        // Generate QR code data (simplified - in real implementation, use QR library)
        $qr_data = "attendance_checkin.php?aid=" . $attendanceID;
        
        $qr_insert = "INSERT INTO qrcode (qrID, attendanceID, qrLink) 
                      VALUES ('qr_$attendanceID', '$attendanceID', '$qr_data')";
        $conn->query($qr_insert);
        
        $success_message = "Attendance slot created successfully! QR Code generated.";
    } else {
        $error_message = "Error creating attendance slot: " . $conn->error;
    }
}

// Get existing attendance slots
$attendance_query = "SELECT a.*, e.eventName, e.eventLocation 
                     FROM attendance a 
                     JOIN event e ON a.eventID = e.eventID 
                     WHERE a.advisorID = '$advisorID' 
                     ORDER BY a.attendanceDate DESC";
$attendance_result = $conn->query($attendance_query);
?>

<div class="main-content">
    <div class="content">
        <div class="card">
            <h2>Create Event Attendance Slot</h2>
            
            <?php if (isset($success_message)): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div style="margin-bottom: 15px;">
                    <label>Select Event:</label>
                    <select name="eventID" required style="width: 100%; padding: 8px; margin-top: 5px;">
                        <option value="">Choose an event</option>
                        <?php while ($event = $events_result->fetch_assoc()): ?>
                            <option value="<?php echo $event['eventID']; ?>">
                                <?php echo $event['eventName'] . ' - ' . $event['eventLocation']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label>Attendance Date:</label>
                    <input type="date" name="attendanceDate" required 
                           style="width: 100%; padding: 8px; margin-top: 5px;">
                </div>
                
                <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
                    Create Attendance Slot
                </button>
            </form>
        </div>
        
        <div class="card">
            <h2>Existing Attendance Slots</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 10px; border: 1px solid #ddd;">Event Name</th>
                        <th style="padding: 10px; border: 1px solid #ddd;">Location</th>
                        <th style="padding: 10px; border: 1px solid #ddd;">Date</th>
                        <th style="padding: 10px; border: 1px solid #ddd;">Status</th>
                        <th style="padding: 10px; border: 1px solid #ddd;">QR Code</th>
                        <th style="padding: 10px; border: 1px solid #ddd;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($attendance = $attendance_result->fetch_assoc()): ?>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $attendance['eventName']; ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $attendance['eventLocation']; ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $attendance['attendanceDate']; ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <span style="background: <?php echo $attendance['attendance_status'] == 'open' ? '#28a745' : '#6c757d'; ?>; 
                                           color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">
                                    <?php echo strtoupper($attendance['attendance_status']); ?>
                                </span>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <a href="view_qr.php?id=<?php echo $attendance['attendanceID']; ?>" 
                                   style="color: #007bff; text-decoration: none;">View QR</a>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <a href="view_attendees.php?id=<?php echo $attendance['attendanceID']; ?>" 
                                   style="color: #28a745; text-decoration: none; margin-right: 10px;">View Attendees</a>
                                <?php if ($attendance['attendance_status'] == 'open'): ?>
                                    <a href="close_attendance.php?id=<?php echo $attendance['attendanceID']; ?>" 
                                       style="color: #dc3545; text-decoration: none;">Close</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
table { font-size: 14px; }
th { font-weight: 600; }
</style>