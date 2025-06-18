<?php
session_start();
require_once 'config.php';

// Check advisor login
if (!isset($_SESSION['adUsername']) || $_SESSION['user_type'] !== 'advisor') {
    header("Location: index.php");
    exit();
}

$advisorID = $_SESSION['advisorID'];
$success = "";
$error = "";

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // Create new attendance slot
    if (isset($_POST['create_slot'])) {
        $eventID = $_POST['event_id'];
        $attendanceDate = $_POST['attendance_date'];
        
        // Validate date is not in the past
        if (strtotime($attendanceDate) < strtotime(date('Y-m-d'))) {
            $error = "Attendance date cannot be in the past.";
        } else {
            try {
                // Check if slot already exists for this event and date
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE eventID = ? AND attendanceDate = ?");
                $checkStmt->execute([$eventID, $attendanceDate]);
                
                if ($checkStmt->fetchColumn() > 0) {
                    $error = "Attendance slot already exists for this event on this date.";
                } else {
                    // Use uniqid for better uniqueness
                    $newID = uniqid('att_', true);
                    $stmt = $pdo->prepare("INSERT INTO attendance (attendanceID, advisorID, eventID, attendanceDate, attendance_status) VALUES (?, ?, ?, ?, 'open')");
                    $stmt->execute([$newID, $advisorID, $eventID, $attendanceDate]);
                    
                    $success = "Attendance slot created successfully!";
                    // Redirect to QR generation
                    header("Location: generate_qr.php?att_id=" . $newID);
                    exit();
                }
            } catch (Exception $e) {
                $error = "Error creating attendance slot: " . $e->getMessage();
            }
        }
    }
    
    // Update attendance status
    if (isset($_POST['update_status'])) {
        $attendanceID = $_POST['attendance_id'];
        $newStatus = $_POST['new_status'];
        
        try {
            $stmt = $pdo->prepare("UPDATE attendance SET attendance_status = ? WHERE attendanceID = ? AND advisorID = ?");
            $stmt->execute([$newStatus, $attendanceID, $advisorID]);
            $success = "Attendance status updated successfully!";
        } catch (Exception $e) {
            $error = "Error updating status: " . $e->getMessage();
        }
    }
}

// Load advisor's events
$stmt = $pdo->prepare("SELECT eventID, eventName FROM event WHERE advisorID = ? ORDER BY eventName");
$stmt->execute([$advisorID]);
$events = $stmt->fetchAll();

// Load existing attendance slots
$stmt = $pdo->prepare("
    SELECT a.attendanceID, a.attendanceDate, a.attendance_status, e.eventName,
           COUNT(sr.studentID) as student_count
    FROM attendance a 
    JOIN event e ON a.eventID = e.eventID 
    LEFT JOIN student_record sr ON a.attendanceID = sr.attendanceID
    WHERE a.advisorID = ? 
    GROUP BY a.attendanceID
    ORDER BY a.attendanceDate DESC
");
$stmt->execute([$advisorID]);
$attendanceSlots = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; background: #d4edda; padding: 10px; border-radius: 3px; }
        .error { color: red; background: #f8d7da; padding: 10px; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, button { padding: 8px; font-size: 14px; }
        button { background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .status-open { color: green; font-weight: bold; }
        .status-closed { color: red; font-weight: bold; }
        .btn-small { padding: 5px 10px; font-size: 12px; margin: 2px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Attendance Management</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Create New Attendance Slot -->
        <div class="section">
            <h2>Create New Attendance Slot</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="event_id">Select Event:</label>
                    <select name="event_id" id="event_id" required>
                        <option value="">-- Select Event --</option>
                        <?php foreach ($events as $event): ?>
                            <option value="<?= $event['eventID'] ?>">
                                <?= htmlspecialchars($event['eventName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="attendance_date">Attendance Date:</label>
                    <input type="date" name="attendance_date" id="attendance_date" 
                           min="<?= date('Y-m-d') ?>" required>
                </div>

                <button type="submit" name="create_slot">Create Attendance Slot & Generate QR</button>
            </form>
        </div>

        <!-- Existing Attendance Slots -->
        <div class="section">
            <h2>Existing Attendance Slots</h2>
            <?php if (empty($attendanceSlots)): ?>
                <p>No attendance slots created yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Students Attended</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendanceSlots as $slot): ?>
                            <tr>
                                <td><?= htmlspecialchars($slot['eventName']) ?></td>
                                <td><?= date('M d, Y', strtotime($slot['attendanceDate'])) ?></td>
                                <td>
                                    <span class="status-<?= $slot['attendance_status'] ?>">
                                        <?= ucfirst($slot['attendance_status']) ?>
                                    </span>
                                </td>
                                <td><?= $slot['student_count'] ?></td>
                                <td>
                                    <a href="generate_qr.php?att_id=<?= $slot['attendanceID'] ?>" 
                                       class="btn-small" style="background: #28a745; color: white; text-decoration: none; padding: 5px 10px; border-radius: 3px;">
                                        View QR
                                    </a>
                                    
                                    <a href="view_attendance.php?att_id=<?= $slot['attendanceID'] ?>" 
                                       class="btn-small" style="background: #17a2b8; color: white; text-decoration: none; padding: 5px 10px; border-radius: 3px;">
                                        View Records
                                    </a>
                                    
                                    <!-- Status Toggle -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="attendance_id" value="<?= $slot['attendanceID'] ?>">
                                        <input type="hidden" name="new_status" 
                                               value="<?= $slot['attendance_status'] === 'open' ? 'closed' : 'open' ?>">
                                        <button type="submit" name="update_status" class="btn-small"
                                                style="background: <?= $slot['attendance_status'] === 'open' ? '#dc3545' : '#28a745' ?>;">
                                            <?= $slot['attendance_status'] === 'open' ? 'Close' : 'Open' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="advisor_dashboard.php" style="color: #007bff; text-decoration: none;">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>