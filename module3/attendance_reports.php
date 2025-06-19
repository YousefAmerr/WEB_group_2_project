<?php
include('includes/session.php');
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Attendance Reports</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/attendance_style.css">
    <style>
        .main-content {
            padding: 40px;
            background-color: #f5f6fa;
        }
        .header {
            font-size: 28px;
            font-weight: bold;
            color: #1c2b5a;
            margin-bottom: 30px;
        }
        h3 {
            margin-top: 40px;
            color: #1c2b5a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        thead {
            background-color: #1c2b5a;
            color: white;
        }
        th, td {
            padding: 14px;
            text-align: left;
        }
        tbody tr:nth-child(even) {
            background-color: #f4f6fb;
        }
        td[colspan] {
            text-align: center;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="header">Attendance Reports</div>

        <h3>Attendance Slots Created</h3>
        <table>
            <thead>
                <tr>
                    <th>Slot ID</th>
                    <th>Event</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Venue</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($role === 'advisor') {
                    $stmt1 = $conn->prepare("SELECT a.attendanceID, e.eventName, a.attendanceDate, a.attendance_status, e.eventLocation
                                             FROM attendance a
                                             JOIN event e ON a.eventID = e.eventID
                                             WHERE a.advisorID = ?");
                    $stmt1->bind_param("s", $_SESSION['advisorID']);
                } else {
                    $stmt1 = $conn->prepare("SELECT a.attendanceID, e.eventName, a.attendanceDate, a.attendance_status, e.eventLocation
                                             FROM attendance a
                                             JOIN event e ON a.eventID = e.eventID");
                }

                if ($stmt1 && $stmt1->execute()) {
                    $result1 = $stmt1->get_result();
                    if ($result1->num_rows > 0) {
                        while ($row = $result1->fetch_assoc()) {
                            echo "<tr>
                                    <td>{$row['attendanceID']}</td>
                                    <td>{$row['eventName']}</td>
                                    <td>{$row['attendanceDate']}</td>
                                    <td>{$row['attendance_status']}</td>
                                    <td>{$row['eventLocation']}</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No slots found.</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>Query Error (Attendance Slots)</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <h3>Student Attendance Records</h3>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Event</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($role === 'advisor') {
                    $stmt2 = $conn->prepare("SELECT s.studentName, e.eventName, ac.attendance_date, ac.status
                                             FROM attendancecslot ac
                                             JOIN student s ON ac.studentID = s.studentID
                                             JOIN attendance a ON ac.attendanceID = a.attendanceID
                                             JOIN event e ON a.eventID = e.eventID
                                             WHERE a.advisorID = ?");
                    $stmt2->bind_param("s", $_SESSION['advisorID']);
                } else {
                    $stmt2 = $conn->prepare("SELECT s.studentName, e.eventName, ac.attendance_date, ac.status
                                             FROM attendancecslot ac
                                             JOIN student s ON ac.studentID = s.studentID
                                             JOIN attendance a ON ac.attendanceID = a.attendanceID
                                             JOIN event e ON a.eventID = e.eventID");
                }

                if ($stmt2 && $stmt2->execute()) {
                    $result2 = $stmt2->get_result();
                    if ($result2->num_rows > 0) {
                        while ($row = $result2->fetch_assoc()) {
                            echo "<tr>
                                    <td>{$row['studentName']}</td>
                                    <td>{$row['eventName']}</td>
                                    <td>{$row['attendance_date']}</td>
                                    <td>{$row['status']}</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>No records found.</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>Query Error (Student Attendance)</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
