<?php
session_start();
include 'db_connect.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];

// Get student ID
$student_query = "SELECT studentID, studentName FROM student WHERE StuUsername = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param('s', $username);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();
$studentID = $student['studentID'];
$studentName = $student['studentName'];
$stmt->close();

// Get attendance records for this student
$attendance_query = "SELECT ac.*, e.eventName, e.eventLocation, a.attendanceDate
    FROM attendancecslot ac
    JOIN attendance a ON ac.attendanceID = a.attendanceID
    JOIN event e ON a.eventID = e.eventID
    WHERE ac.studentID = ?
    ORDER BY a.attendanceDate DESC, ac.attendance_date DESC";
$stmt = $conn->prepare($attendance_query);
$stmt->bind_param('s', $studentID);
$stmt->execute();
$attendance_result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Attendance Records</title>
    <link rel="stylesheet" href="css/attendance.css">
    <link rel="stylesheet" href="css/utilities.css">
    <style>
        .container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        h2 { color: #4a90e2; text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
        tr:nth-child(even) { background: #f9f9f9; }
        .status-present { background: #28a745; color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 13px; }
        .status-absent { background: #dc3545; color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 13px; }
        .back-btn { display: inline-block; margin-top: 25px; background: #6c757d; color: #fff; padding: 10px 20px; border-radius: 5px; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">
    <h2>My Attendance Records</h2>
    <p><strong>Name:</strong> <?php echo htmlspecialchars($studentName); ?></p>
    <?php if ($attendance_result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Event Name</th>
                    <th>Location</th>
                    <th>Attendance Date</th>
                    <th>Check-in Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($row = $attendance_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($row['eventName']); ?></td>
                    <td><?php echo htmlspecialchars($row['eventLocation']); ?></td>
                    <td><?php echo htmlspecialchars($row['attendanceDate']); ?></td>
                    <td><?php echo htmlspecialchars($row['attendance_date']); ?></td>
                    <td>
                        <span class="<?php echo $row['status'] == 'Present' ? 'status-present' : 'status-absent'; ?>">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </span>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align:center; color:#888; margin:40px 0;">No attendance records found.</p>
    <?php endif; ?>
    <a href="student_dashboard.php" class="back-btn">&larr; Back to Dashboard</a>
</div>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>
