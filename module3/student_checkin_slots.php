<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['user_id'];

// Get all open attendance slots for this student (not yet checked in)
$sql = "SELECT a.attendanceID, e.eventName, e.eventLocation, a.attendanceDate, a.attendance_status
        FROM attendance a
        JOIN event e ON a.eventID = e.eventID
        WHERE a.attendance_status = 'open' AND NOT EXISTS (
            SELECT 1 FROM attendancecslot ac WHERE ac.attendanceID = a.attendanceID AND ac.studentID = ?
        )
        ORDER BY a.attendanceDate DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $student_id);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Available Check-In Slots</title>
    <link rel="stylesheet" href="css/attendance.css">
    <link rel="stylesheet" href="css/utilities.css">
    <style>
        .container { max-width: 700px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        h2 { color: #4a90e2; text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
        tr:nth-child(even) { background: #f9f9f9; }
        .btn { background: #7367f0; color: #fff; padding: 6px 16px; border-radius: 4px; text-decoration: none; }
        .btn:hover { background: #4a90e2; }
    </style>
</head>
<body>
<div class="container">
    <h2>Available Check-In Slots</h2>
    <?php if ($result->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Event</th>
                <th>Location</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['eventName']); ?></td>
                <td><?php echo htmlspecialchars($row['eventLocation']); ?></td>
                <td><?php echo htmlspecialchars($row['attendanceDate']); ?></td>
                <td><a class="btn" href="view_qr.php?id=<?php echo $row['attendanceID']; ?>" target="_blank">Show QR</a></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="text-align:center; color:#888; margin:40px 0;">No open attendance slots available for check-in.</p>
    <?php endif; ?>
    <a href="student_dashboard.php" class="btn" style="background:#6c757d; margin-top:20px;">&larr; Back to Dashboard</a>
</div>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>
