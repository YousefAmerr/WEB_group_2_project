<?php
session_start();
require_once 'config.php';

// Check if user is logged in as advisor
if (!isset($_SESSION['adUsername']) || $_SESSION['user_type'] !== 'advisor') {
    header("Location: index.php");
    exit();
}

$advisorID = $_SESSION['advisorID'];

// Get filter parameters
$selected_event = isset($_GET['event_id']) ? $_GET['event_id'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Get event name for title
$event_name = "All Events";
if ($selected_event) {
    $stmt = $pdo->prepare("SELECT eventName FROM event WHERE eventID = ?");
    $stmt->execute([$selected_event]);
    $event = $stmt->fetch();
    if ($event) {
        $event_name = $event['eventName'];
    }
}

// Build dynamic WHERE clause
$where_conditions = ["e.advisorID = ?"];
$params = [$advisorID];

if ($selected_event) {
    $where_conditions[] = "e.eventID = ?";
    $params[] = $selected_event;
}
if ($date_from) {
    $where_conditions[] = "ac.attendance_date >= ?";
    $params[] = $date_from;
}
if ($date_to) {
    $where_conditions[] = "ac.attendance_date <= ?";
    $params[] = $date_to;
}

$where_clause = implode(" AND ", $where_conditions);

// Complete SQL query
$sql = "SELECT 
    e.eventName,
    e.eventLocation,
    e.eventLevel,
    s.studentName,
    s.studentCard,
    s.studentEmail,
    ac.attendance_date,
    ac.status
FROM attendancecslot ac
JOIN attendance a ON ac.attendanceID = a.attendanceID
JOIN student s ON ac.studentID = s.studentID
JOIN event e ON a.eventID = e.eventID
WHERE $where_clause
ORDER BY ac.attendance_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendance Report - <?php echo htmlspecialchars($event_name); ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body onload="window.print()">

<div class="header">
    <h2>Attendance Report</h2>
    <p><strong>Event:</strong> <?php echo htmlspecialchars($event_name); ?></p>
    <p><strong>Advisor ID:</strong> <?php echo htmlspecialchars($advisorID); ?></p>
    <p><strong>Date:</strong> <?php echo date('Y-m-d'); ?></p>
</div>

<table>
    <tr>
        <th>No</th>
        <th>Student Name</th>
        <th>Student Card</th>
        <th>Email</th>
        <th>Event</th>
        <th>Location</th>
        <th>Level</th>
        <th>Date</th>
        <th>Status</th>
    </tr>
    <?php if (count($records) > 0): ?>
        <?php $no = 1; foreach ($records as $row): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($row['studentName']) ?></td>
            <td><?= htmlspecialchars($row['studentCard']) ?></td>
            <td><?= htmlspecialchars($row['studentEmail']) ?></td>
            <td><?= htmlspecialchars($row['eventName']) ?></td>
            <td><?= htmlspecialchars($row['eventLocation']) ?></td>
            <td><?= htmlspecialchars($row['eventLevel']) ?></td>
            <td><?= htmlspecialchars($row['attendance_date']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="9">No records found for the selected filters.</td></tr>
    <?php endif; ?>
</table>

</body>
</html>
