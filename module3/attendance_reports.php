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

// Get all events for filter dropdown
$stmt = $pdo->prepare("SELECT eventID, eventName FROM event WHERE advisorID = ? ORDER BY eventName");
$stmt->execute([$advisorID]);
$events = $stmt->fetchAll();

// Build query for attendance report
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

// Detailed attendance report
$stmt = $pdo->prepare("SELECT 
    e.eventName,
    e.eventLocation,
    e.eventLevel,
    s.studentName,
    s.studentCard,
    s.studentEmail,
    ac.attendance_date,
    ac.status,
    a.attendanceDate as session_date
FROM attendancecslot ac
JOIN attendance a ON ac.attendanceID = a.attendanceID
JOIN event e ON a.eventID = e.eventID
JOIN student s ON ac.studentID = s.studentID
WHERE $where_clause
ORDER BY ac.attendance_date DESC, e.eventName, s.studentName");

$stmt->execute($params);
$attendance_records = $stmt->fetchAll();

// Summary statistics
$stmt = $pdo->prepare("SELECT 
    COUNT(DISTINCT ac.studentID) as unique_students,
    COUNT(ac.checkInID) as total_checkins,
    COUNT(DISTINCT a.attendanceID) as total_sessions,
    COUNT(DISTINCT e.eventID) as total_events
FROM attendancecslot ac
JOIN attendance a ON ac.attendanceID = a.attendanceID
JOIN event e ON a.eventID = e.eventID
WHERE $where_clause");

$stmt->execute($params);
$summary = $stmt->fetch();

// Attendance by event summary
$stmt = $pdo->prepare("SELECT 
    e.eventName,
    e.eventLevel,
    COUNT(ac.checkInID) as attendance_count,
    COUNT(DISTINCT ac.studentID) as unique_attendees
FROM event e
LEFT JOIN attendance a ON e.eventID = a.eventID
LEFT JOIN attendancecslot ac ON a.attendanceID = ac.attendanceID
WHERE $where_clause
GROUP BY e.eventID, e.eventName, e.eventLevel
ORDER BY attendance_count DESC");

$stmt->execute($params);
$event_summary = $stmt->fetchAll();

// Location-based attendance statistics
$stmt = $pdo->prepare("SELECT 
    e.eventLocation,
    COUNT(ac.checkInID) as total_attendance,
    COUNT(DISTINCT e.eventID) as events_count
FROM event e
LEFT JOIN attendance a ON e.eventID = a.eventID
LEFT JOIN attendancecslot ac ON a.attendanceID = ac.attendanceID
WHERE $where_clause
GROUP BY e.eventLocation
ORDER BY total_attendance DESC");

$stmt->execute($params);
$location_stats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports - MyPetakom</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
        }
        
        .summary-card h3 {
            font-size: 2em;
            margin: 0;
            color: #007bff;
        }
        
        .summary-card p {
            margin: 5px 0 0 0;
            color: #666;
            font-weight: bold;
        }
        
        .export-section {
            text-align: right;
            margin-bottom: 20px;
        }
        
        .export-btn {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-left: 10px;
        }
        
        .export-btn:hover {
            background: #218838;
        }
        
        .report-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        
        .tab-btn {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            font-size: 16px;
        }
        
        .tab-btn.active {
            border-bottom-color: #007bff;
            color: #007bff;
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-present {
            background: #d4edda;
            color: #155724;
        }
        
        .status-absent {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <h1>📊 Attendance Reports</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['adUsername']); ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </header>

        <nav class="dashboard-nav">
            <a href="attendance_dashboard.php" class="nav-link">Dashboard</a>
            <a href="event_management.php" class="nav-link">Events</a>
            <a href="attendance_management.php" class="nav-link">Attendance</a>
            <a href="attendance_reports.php" class="nav-link active">Reports</a>
        </nav>

        <main class="dashboard-main">
            <!-- Filters Section -->
            <div class="filters-section">
                <h3>📋 Filter Reports</h3>
                <form method="GET" action="">
                    <div class="filter-row">
                        <div class="form-group">
                            <label>Event:</label>
                            <select name="event_id" class="form-control">
                                <option value="">All Events</option>
                                <?php foreach ($events as $event): ?>
                                    <option value="<?php echo $event['eventID']; ?>" 
                                            <?php echo ($selected_event == $event['eventID']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($event['eventName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Date From:</label>
                            <input type="date" name="date_from" value="<?php echo $date_from; ?>" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Date To:</label>
                            <input type="date" name="date_to" value="<?php echo $date_to; ?>" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <h3><?php echo $summary['total_events'] ?? 0; ?></h3>
                    <p>Events</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo $summary['total_sessions'] ?? 0; ?></h3>
                    <p>Attendance Sessions</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo $summary['unique_students'] ?? 0; ?></h3>
                    <p>Unique Students</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo $summary['total_checkins'] ?? 0; ?></h3>
                    <p>Total Check-ins</p>
                </div>
            </div>

            <!-- Export Section -->
            <div class="export-section">
                <a href="export_attendance.php?<?php echo http_build_query($_GET); ?>" class="export-btn">
                    📊 Export to Excel
                </a>
                <a href="print_report.php?<?php echo http_build_query($_GET); ?>" class="export-btn" target="_blank">
                    🖨️ Print Report
                </a>
            </div>

            <!-- Report Tabs -->
            <div class="report-tabs">
                <button class="tab-btn active" onclick="showTab('detailed')">Detailed Report</button>
                <button class="tab-btn" onclick="showTab('summary')">Event Summary</button>
                <button class="tab-btn" onclick="showTab('location')">Location Analysis</button>
            </div>

            <!-- Detailed Report Tab -->
            <div id="detailed" class="tab-content active">
                <div class="chart-container">
                    <h3>📝 Detailed Attendance Report</h3>
                    <?php if (empty($attendance_records)): ?>
                        <div class="no-data">
                            <p>No attendance records found for the selected criteria.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Event Name</th>
                                        <th>Location</th>
                                        <th>Level</th>
                                        <th>Student Name</th>
                                        <th>Student Card</th>
                                        <th>Email</th>
                                        <th>Check-in Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance_records as $record): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['eventName']); ?></td>
                                        <td><?php echo htmlspecialchars($record['eventLocation']); ?></td>
                                        <td><span class="badge badge-info"><?php echo htmlspecialchars($record['eventLevel']); ?></span></td>
                                        <td><?php echo htmlspecialchars($record['studentName']); ?></td>
                                        <td><?php echo htmlspecialchars($record['studentCard']); ?></td>
                                        <td><?php echo htmlspecialchars($record['studentEmail']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($record['attendance_date'])); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $record['status'] == 'Present' ? 'status-present' : 'status-absent'; ?>">
                                                <?php echo htmlspecialchars($record['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Event Summary Tab -->
            <div id="summary" class="tab-content">
                <div class="chart-container">
                    <h3>📈 Event Summary Report</h3>
                    <?php if (empty($event_summary)): ?>
                        <div class="no-data">
                            <p>No event data found for the selected criteria.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Event Name</th>
                                        <th>Event Level</th>
                                        <th>Total Attendance</th>
                                        <th>Unique Attendees</th>
                                        <th>Attendance Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($event_summary as $event): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event['eventName']); ?></td>
                                        <td><span class="badge badge-secondary"><?php echo htmlspecialchars($event['eventLevel']); ?></span></td>
                                        <td><?php echo $event['attendance_count'] ?? 0; ?></td>
                                        <td><?php echo $event['unique_attendees'] ?? 0; ?></td>
                                        <td>
                                            <?php 
                                            $rate = $event['unique_attendees'] > 0 ? 
                                                   round(($event['attendance_count'] / $event['unique_attendees']) * 100, 1) : 0;
                                            echo $rate . '%';
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Location Analysis Tab -->
            <div id="location" class="tab-content">
                <div class="chart-container">
                    <h3>📍 Location-based Analysis</h3>
                    <?php if (empty($location_stats)): ?>
                        <div class="no-data">
                            <p>No location data found for the selected criteria.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Location</th>
                                        <th>Total Attendance</th>
                                        <th>Events Held</th>
                                        <th>Average Attendance per Event</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($location_stats as $location): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($location['eventLocation']); ?></td>
                                        <td><?php echo $location['total_attendance'] ?? 0; ?></td>
                                        <td><?php echo $location['events_count'] ?? 0; ?></td>
                                        <td>
                                            <?php 
                                            $avg = $location['events_count'] > 0 ? 
                                                   round($location['total_attendance'] / $location['events_count'], 1) : 0;
                                            echo $avg;
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>