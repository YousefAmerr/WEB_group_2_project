<?php
require_once 'config.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=attendance_report.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Student Name', 'Email', 'Event Name', 'Date', 'Status']);

$query = "
SELECT s.studentName, s.studentEmail, e.eventName, a.attendanceDate, ac.status
FROM attendancecslot ac
JOIN student s ON ac.studentID = s.studentID
JOIN attendance a ON ac.attendanceID = a.attendanceID
JOIN event e ON a.eventID = e.eventID
ORDER BY a.attendanceDate DESC
";

$stmt = $pdo->query($query);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}
fclose($output);
exit;
