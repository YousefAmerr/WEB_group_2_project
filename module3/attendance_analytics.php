<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'db_connect.php';

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || 
    ($_SESSION['role'] !== 'advisor' && $_SESSION['role'] !== 'event_advisor')) {
    header('Location: login.php');
    exit();
}

$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
if ($role === 'student') {
    include_once 'student_dashboard.php';
} elseif ($role === 'coordinator' || $role === 'petakom_coordinator') {
    include_once 'coordinator_dashboard.php';
} elseif ($role === 'advisor' || $role === 'event_advisor') {
    include_once 'advisor_dashboard.php';
}

// Use $conn from db_connect.php instead of Database class

// Get date range from filters
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-t');
$faculty_filter = $_GET['faculty'] ?? 'all';
$event_type = $_GET['event_type'] ?? 'all';

// Build WHERE clause
$where = "e.date_time BETWEEN ? AND ?";
$params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
$types = 'ss';

if ($faculty_filter !== 'all') {
    $where .= " AND s.faculty = ?";
    $params[] = $faculty_filter;
    $types .= 's';
}
if ($event_type !== 'all') {
    $where .= " AND e.event_type = ?";
    $params[] = $event_type;
    $types .= 's';
}

$stats_query = "SELECT 
    COUNT(DISTINCT e.id) as total_events,
    COUNT(DISTINCT ea.student_id) as unique_participants,
    COUNT(ea.id) as total_registrations,
    COUNT(CASE WHEN ea.check_in_time IS NOT NULL THEN 1 END) as total_checkins,
    COUNT(CASE WHEN ea.check_in_time IS NOT NULL AND ea.check_out_time IS NOT NULL THEN 1 END) as total_completions,
    ROUND(AVG(CASE WHEN ea.check_in_time IS NOT NULL THEN 1 ELSE 0 END) * 100, 2) as avg_attendance_rate,
    ROUND(AVG(CASE WHEN ea.check_in_time IS NOT NULL AND ea.check_out_time IS NOT NULL THEN 1 ELSE 0 END) * 100, 2) as avg_completion_rate
FROM events e 
LEFT JOIN event_attendances ea ON e.id = ea.event_id
LEFT JOIN students s ON ea.student_id = s.id
WHERE $where";

$stmt = $conn->prepare($stats_query);
if ($stmt && $types) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result ? $result->fetch_assoc() : [];
    $stmt->close();
} else {
    $stats = [];
}

// Get event-wise attendance data
$event_query = "SELECT 
    e.id,
    e.event_name,
    e.date_time,
    e.event_type,
    e.location,
    COUNT(ea.id) as total_registered,
    COUNT(CASE WHEN ea.check_in_time IS NOT NULL THEN 1 END) as checked_in,
    COUNT(CASE WHEN ea.check_in_time IS NOT NULL AND ea.check_out_time IS NOT NULL THEN 1 END) as completed,
    ROUND((COUNT(CASE WHEN ea.check_in_time IS NOT NULL THEN 1 END) / COUNT(ea.id)) * 100, 2) as attendance_rate,
    ROUND((COUNT(CASE WHEN ea.check_in_time IS NOT NULL AND ea.check_out_time IS NOT NULL THEN 1 END) / COUNT(ea.id)) * 100, 2) as completion_rate
FROM events e 
LEFT JOIN event_attendances ea ON e.id = ea.event_id
LEFT JOIN students s ON ea.student_id = s.id
WHERE $where
GROUP BY e.id ORDER BY e.date_time DESC";

$event_stmt = $conn->prepare($event_query);
if ($event_stmt && $types) {
    $event_stmt->bind_param($types, ...$params);
    $event_stmt->execute();
    $event_result = $event_stmt->get_result();
    $events = $event_result ? $event_result->fetch_all(MYSQLI_ASSOC) : [];
    $event_stmt->close();
} else {
    $events = [];
}

// Get faculty-wise statistics
$faculty_query = "SELECT 
    s.faculty,
    COUNT(DISTINCT ea.student_id) as unique_students,
    COUNT(ea.id) as total_registrations,
    COUNT(CASE WHEN ea.check_in_time IS NOT NULL THEN 1 END) as total_checkins,
    ROUND((COUNT(CASE WHEN ea.check_in_time IS NOT NULL THEN 1 END) / COUNT(ea.id)) * 100, 2) as attendance_rate
FROM event_attendances ea
JOIN students s ON ea.student_id = s.id
JOIN events e ON ea.event_id = e.id
WHERE $where
GROUP BY s.faculty ORDER BY attendance_rate DESC";

$faculty_stmt = $conn->prepare($faculty_query);
if ($faculty_stmt && $types) {
    $faculty_stmt->bind_param($types, ...$params);
    $faculty_stmt->execute();
    $faculty_result = $faculty_stmt->get_result();
    $faculty_stats = $faculty_result ? $faculty_result->fetch_all(MYSQLI_ASSOC) : [];
    $faculty_stmt->close();
} else {
    $faculty_stats = [];
}

// Get daily attendance trends
$daily_query = "SELECT 
    DATE(e.date_time) as event_date,
    COUNT(DISTINCT e.id) as events_count,
    COUNT(ea.id) as registrations,
    COUNT(CASE WHEN ea.check_in_time IS NOT NULL THEN 1 END) as checkins,
    ROUND((COUNT(CASE WHEN ea.check_in_time IS NOT NULL THEN 1 END) / COUNT(ea.id)) * 100, 2) as daily_attendance_rate
FROM events e
LEFT JOIN event_attendances ea ON e.id = ea.event_id
LEFT JOIN students s ON ea.student_id = s.id
WHERE $where
GROUP BY DATE(e.date_time) ORDER BY event_date";

$daily_stmt = $conn->prepare($daily_query);
if ($daily_stmt && $types) {
    $daily_stmt->bind_param($types, ...$params);
    $daily_stmt->execute();
    $daily_result = $daily_stmt->get_result();
    $daily_trends = $daily_result ? $daily_result->fetch_all(MYSQLI_ASSOC) : [];
    $daily_stmt->close();
} else {
    $daily_trends = [];
}

// Get list of faculties for filter
$faculty_list_query = "SELECT DISTINCT faculty FROM students ORDER BY faculty";
$faculty_list_stmt = $conn->prepare($faculty_list_query);
if (!$faculty_list_stmt) {
    die('Prepare failed (faculty_list): ' . $conn->error . '<br>SQL: ' . $faculty_list_query);
}
$faculty_list_stmt->execute();
$faculty_result = $faculty_list_stmt->get_result();
$faculties = $faculty_result ? $faculty_result->fetch_all(MYSQLI_ASSOC) : [];
$faculty_list_stmt->close();

// Get list of event types for filter
$event_type_query = "SELECT DISTINCT event_type FROM events ORDER BY event_type";
$event_type_stmt = $conn->prepare($event_type_query);
if (!$event_type_stmt) {
    die('Prepare failed (event_type): ' . $conn->error . '<br>SQL: ' . $event_type_query);
}
$event_type_stmt->execute();
$event_type_result = $event_type_stmt->get_result();
$event_types = $event_type_result ? $event_type_result->fetch_all(MYSQLI_ASSOC) : [];
$event_type_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Analytics - Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/utilities.css">
    <link rel="stylesheet" href="css/analytics.css">
    <link rel="stylesheet" href="css/attendance.css">
    <style>
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stats-card p {
            margin: 0;
            opacity: 0.9;
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .attendance-rate {
            font-weight: bold;
        }
        .rate-excellent { color: #28a745; }
        .rate-good { color: #ffc107; }
        .rate-poor { color: #dc3545; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-chart-line"></i> Attendance Analytics</h2>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="faculty" class="form-label">Faculty</label>
                            <select class="form-select" id="faculty" name="faculty">
                                <option value="all" <?php echo $faculty_filter === 'all' ? 'selected' : ''; ?>>All Faculties</option>
                                <?php foreach ($faculties as $faculty): ?>
                                    <option value="<?php echo $faculty['faculty']; ?>" <?php echo $faculty_filter === $faculty['faculty'] ? 'selected' : ''; ?>>
                                        <?php echo $faculty['faculty']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="event_type" class="form-label">Event Type</label>
                            <select class="form-select" id="event_type" name="event_type">
                                <option value="all" <?php echo $event_type === 'all' ? 'selected' : ''; ?>>All Types</option>
                                <?php foreach ($event_types as $type): ?>
                                    <option value="<?php echo $type['event_type']; ?>" <?php echo $event_type === $type['event_type'] ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($type['event_type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply Filters</button>
                            <a href="attendance_analytics.php" class="btn btn-secondary"><i class="fas fa-refresh"></i> Reset</a>
                        </div>
                    </form>
                </div>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card">
                            <h3><?php echo number_format($stats['total_events'] ?? 0); ?></h3>
                            <p><i class="fas fa-calendar"></i> Total Events</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card">
                            <h3><?php echo number_format($stats['unique_participants'] ?? 0); ?></h3>
                            <p><i class="fas fa-users"></i> Unique Participants</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card">
                            <h3><?php echo number_format($stats['total_registrations'] ?? 0); ?></h3>
                            <p><i class="fas fa-clipboard-list"></i> Total Registrations</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card">
                            <h3><?php echo $stats['avg_attendance_rate'] ?? 0; ?>%</h3>
                            <p><i class="fas fa-percentage"></i> Average Attendance Rate</p>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="chart-container">
                            <h5><i class="fas fa-line-chart"></i> Daily Attendance Trends</h5>
                            <canvas id="dailyTrendsChart" height="100"></canvas>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="chart-container">
                            <h5><i class="fas fa-pie-chart"></i> Faculty Participation</h5>
                            <canvas id="facultyChart" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Event-wise Attendance Table -->
                <div class="chart-container">
                    <h5><i class="fas fa-table"></i> Event-wise Attendance Details</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Event Name</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Location</th>
                                    <th>Registered</th>
                                    <th>Checked In</th>
                                    <th>Completed</th>
                                    <th>Attendance Rate</th>
                                    <th>Completion Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($event['date_time'])); ?></td>
                                        <td><?php echo ucfirst($event['event_type']); ?></td>
                                        <td><?php echo htmlspecialchars($event['location']); ?></td>
                                        <td><?php echo $event['total_registered']; ?></td>
                                        <td><?php echo $event['checked_in']; ?></td>
                                        <td><?php echo $event['completed']; ?></td>
                                        <td class="attendance-rate <?php 
                                            $rate = $event['attendance_rate'];
                                            if ($rate >= 80) echo 'rate-excellent';
                                            elseif ($rate >= 60) echo 'rate-good';
                                            else echo 'rate-poor';
                                        ?>"><?php echo $rate; ?>%</td>
                                        <td class="attendance-rate <?php 
                                            $comp_rate = $event['completion_rate'];
                                            if ($comp_rate >= 80) echo 'rate-excellent';
                                            elseif ($comp_rate >= 60) echo 'rate-good';
                                            else echo 'rate-poor';
                                        ?>"><?php echo $comp_rate; ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Faculty-wise Statistics Table -->
                <div class="chart-container">
                    <h5><i class="fas fa-university"></i> Faculty-wise Statistics</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Faculty</th>
                                    <th>Unique Students</th>
                                    <th>Total Registrations</th>
                                    <th>Total Check-ins</th>
                                    <th>Attendance Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($faculty_stats as $faculty): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($faculty['faculty']); ?></td>
                                        <td><?php echo $faculty['unique_students']; ?></td>
                                        <td><?php echo $faculty['total_registrations']; ?></td>
                                        <td><?php echo $faculty['total_checkins']; ?></td>
                                        <td class="attendance-rate <?php 
                                            $rate = $faculty['attendance_rate'];
                                            if ($rate >= 80) echo 'rate-excellent';
                                            elseif ($rate >= 60) echo 'rate-good';
                                            else echo 'rate-poor';
                                        ?>"><?php echo $rate; ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Daily Trends Chart
        const dailyTrendsCtx = document.getElementById('dailyTrendsChart').getContext('2d');
        const dailyTrendsChart = new Chart(dailyTrendsCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($d) { return '"' . date('M d', strtotime($d['event_date'])) . '"'; }, $daily_trends)); ?>],
                datasets: [{
                    label: 'Attendance Rate (%)',
                    data: [<?php echo implode(',', array_column($daily_trends, 'daily_attendance_rate')); ?>],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }, {
                    label: 'Number of Events',
                    data: [<?php echo implode(',', array_column($daily_trends, 'events_count')); ?>],
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        position: 'left'
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                }
            }
        });

        // Faculty Chart
        const facultyCtx = document.getElementById('facultyChart').getContext('2d');
        const facultyChart = new Chart(facultyCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', array_map(function($f) { return '"' . htmlspecialchars($f['faculty']) . '"'; }, $faculty_stats)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($faculty_stats, 'total_registrations')); ?>],
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40',
                        '#FF6384',
                        '#C9CBCF'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>