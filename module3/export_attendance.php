<?php
include('includes/session.php');
include('includes/db_connection.php');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=attendance_export.csv');

$output = fopen("php://output", "w");

// Column headers
fputcsv($output, ['Student Name', 'Event Name', 'Date', 'Status']);

if ($role === 'advisor') {
    $stmt = $conn->prepare("
        SELECT st.studentName, e.eventName, ac.attendance_date, ac.status
        FROM attendancecslot ac
        JOIN student st ON ac.studentID = st.studentID
        JOIN attendance s ON ac.attendanceID = s.attendanceID
        JOIN event e ON s.eventID = e.eventID
        WHERE s.advisorID = ?
    ");
    $stmt->bind_param("s", $_SESSION['advisorID']);
} elseif ($role === 'coordinator') {
    $stmt = $conn->prepare("
        SELECT st.studentName, e.eventName, ac.attendance_date, ac.status
        FROM attendancecslot ac
        JOIN student st ON ac.studentID = st.studentID
        JOIN attendance s ON ac.attendanceID = s.attendanceID
        JOIN event e ON s.eventID = e.eventID
    ");
} else {
    echo "You are not authorized to export attendance.";
    exit;
}

if ($stmt && $stmt->execute()) {
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['studentName'],
            $row['eventName'],
            $row['attendance_date'],
            $row['status']
        ]);
    }
} else {
    fputcsv($output, ["Error retrieving data."]);
}

fclose($output);
exit;
