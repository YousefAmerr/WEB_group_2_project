<?php
session_start();
include '../includes/db_connect.php';
include '../sideBar/Advisor_SideBar.php';

if (!isset($_SESSION['username'])) {
    header("Location: ../module1/login.php");
    exit();
}

$slotID = $_GET['slotID'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>QR Display</title>
</head>
<body>
<div class="main-content">
    <h2>QR Code for Attendance Slot</h2>
    <?php if ($slotID): ?>
        <img src="../assets/qr/<?php echo $slotID; ?>.png" alt="QR Code" width="300">
        <p>Slot ID: <?php echo $slotID; ?></p>
    <?php else: ?>
        <p>No slot ID provided.</p>
    <?php endif; ?>
</div>
</body>
</html>