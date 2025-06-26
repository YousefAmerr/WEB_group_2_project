<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'db_connect.php';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
if ($role !== 'advisor') {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advisor Attendance Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/utilities.css">
    <link rel="stylesheet" href="css/attendance.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-user-tie me-2"></i>Advisor Dashboard
            </a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="nav-link"><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($username); ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
                </li>
            </ul>
        </div>
    </nav>
    <div class="container main-content mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0">Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
                    </div>
                    <div class="card-body">
                        <h4 class="mb-3"><i class="fas fa-calendar-check text-primary me-2"></i>Attendance Management</h4>
                        <div class="list-group mb-4">
                            <a href="attendance.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-list me-2"></i>View Attendance
                            </a>
                            <a href="attendance_analytics.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-chart-line me-2"></i>Analytics
                            </a>
                        </div>
                        <h4 class="mb-3"><i class="fas fa-link text-primary me-2"></i>Quick Links</h4>
                        <div class="list-group">
                            <a href="attendance_report.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-file-alt me-2"></i>Attendance Report
                            </a>
                            <a href="create_attendance_slot.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-clock me-2"></i>Create Attendance Slot
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
