<?php
include('includes/session.php');

// Only advisors can access this page
if ($role !== 'advisor') {
    header("Location: attendance_dashboard.php");
    exit;
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Attendance Management</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .main-content {
            margin-left: 270px;
            padding: 40px;
            background-color: #f5f6fa;
            min-height: 100vh;
        }
        .header {
            font-size: 28px;
            font-weight: bold;
            color: #1c2b5a;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }
        th {
            background-color: #1c2b5a;
            color: white;
        }
        .qr-img {
            width: 80px;
        }
        .action-links a {
            margin: 0 5px;
            text-decoration: none;
            color: #1c2b5a;
            font-weight: bold;
        }
        .add-btn {
            margin-bottom: 20px;
            display: inline-block;
            padding: 10px 20px;
            background-color: #1c2b5a;
            color: white;
            border-radius: 6px;
            text-decoration: none;
        }
    </style>
</head>
<body>
<?php include('includes/advisor_sidebar.php'); ?>
<div class="main-content">
    <div class="header">Manage Attendance Slots</div>
    <a href="add_attendance_slot.php" class="add-btn">+ Add New Slot</a>
    <table>
        <thead>
            <tr>
                <th>Attendance ID</th>
                <th>Event Name</th>
                <th>Date</th>
                <th>Status</th>
                <th>QR Code</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $stmt = $conn->prepare("SELECT A.attendanceID, A.attendanceDate, A.attendance_status, E.eventName
                                FROM attendance A
                                JOIN event E ON A.eventID = E.eventID
                                WHERE A.advisorID = ?");
        $stmt->bind_param("s", $_SESSION['advisorID']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $qrPath = "qr/slot_" . $row['attendanceID'] . ".png";
            echo "<tr>
                    <td>{$row['attendanceID']}</td>
                    <td>{$row['eventName']}</td>
                    <td>{$row['attendanceDate']}</td>
                    <td>{$row['attendance_status']}</td>
                    <td><img src='" . $qrPath . "' class='qr-img' alt='QR'></td>
                    <td class='action-links'>
                        <a href='edit_attendance_slot.php?id={$row['attendanceID']}'>Edit</a>
                        <a href='delete_attendance_slot.php?id={$row['attendanceID']}'>Delete</a>
                    </td>
                </tr>";
        }
        ?>
        </tbody>
    </table>
</div>
</body>
</html>
