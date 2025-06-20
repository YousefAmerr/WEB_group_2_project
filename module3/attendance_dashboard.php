<?php
// attendance_dashboard.php
include('includes/session.php');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Attendance Dashboard</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .main-content {
            padding: 40px;
            background-color: #f5f6fa;
            flex: 1;
        }
        .header {
            font-size: 28px;
            color: #1c2b5a;
            margin-bottom: 30px;
            font-weight: bold;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .card h3 {
            margin: 0;
            font-size: 22px;
            color: #1c2b5a;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="header">Welcome to Attendance Dashboard</div>

        <?php if ($role === 'advisor') : ?>
            <div class="card">
                <h3>Advisor Dashboard</h3>
                <p>You can manage attendance slots, view reports, and monitor student participation.</p>
            </div>
        <?php elseif ($role === 'student') : ?>
            <div class="card">
                <h3>Student Dashboard</h3>
                <p>You can register your attendance, view attended events and check merit progress.</p>
            </div>
        <?php elseif ($role === 'coordinator') : ?>
            <div class="card">
                <h3>Coordinator Dashboard</h3>
                <p>You can monitor all events, validate attendances, and export reports.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
