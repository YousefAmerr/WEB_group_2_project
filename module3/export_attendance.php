<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['event_advisor', 'admin', 'petakom_coordinator', 'coordinator'])) {
    header('Location: login.php');
    exit();
}

$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Handle export request
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $export_type = $_GET['type'] ?? 'attendance';
    $format = $_GET['format'] ?? 'csv';
    $event_id = $_GET['event_id'] ?? 'all';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $status_filter = $_GET['status'] ?? 'all';
    
    try {
        switch ($export_type) {
            case 'attendance':
                exportAttendanceData($conn, $format, $event_id, $date_from, $date_to, $status_filter);
                break;
            case 'events':
                exportEventsData($conn, $format, $date_from, $date_to);
                break;
            case 'summary':
                exportSummaryData($conn, $format, $date_from, $date_to);
                break;
            case 'analytics':
                exportAnalyticsData($conn, $format, $date_from, $date_to);
                break;
            default:
                exportAttendanceData($conn, $format, $event_id, $date_from, $date_to, $status_filter);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Export failed: ' . $e->getMessage();
        header('Location: export_attendance.php');
        exit();
    }
}

// Get events for dropdown
$events = [];
$events_query = "SELECT id, title, event_date FROM events WHERE deleted_at IS NULL ORDER BY event_date DESC";
$events_result = $conn->query($events_query);
if ($events_result) {
    while ($row = $events_result->fetch_assoc()) {
        $events[] = $row;
    }
}

// Get export statistics
$stats_query = "SELECT 
    COUNT(DISTINCT ea.event_id) as total_events_with_attendance,
    COUNT(*) as total_attendance_records,
    COUNT(CASE WHEN ea.check_in_time IS NOT NULL AND ea.check_out_time IS NOT NULL THEN 1 END) as completed_attendances,
    COUNT(CASE WHEN ea.check_in_time IS NOT NULL AND ea.check_out_time IS NULL THEN 1 END) as pending_checkouts,
    MIN(ea.check_in_time) as earliest_attendance,
    MAX(ea.check_in_time) as latest_attendance
    FROM event_attendance ea 
    JOIN events e ON ea.event_id = e.id 
    WHERE e.deleted_at IS NULL";
$stats_stmt = $conn->prepare($stats_query);
if ($stats_stmt) {
    $stats_stmt->execute();
    $result = $stats_stmt->get_result();
    $stats = $result->fetch_assoc();
    $stats_stmt->close();
} else {
    $stats = [
        'total_events_with_attendance' => 0,
        'total_attendance_records' => 0,
        'completed_attendances' => 0,
        'pending_checkouts' => 0,
        'earliest_attendance' => null,
        'latest_attendance' => null
    ];
}

include '../includes/header.php';

function exportAttendanceData($conn, $format, $event_id, $date_from, $date_to, $status_filter) {
    $query = "SELECT 
        e.title as event_title,
        e.event_date,
        e.start_time,
        e.end_time,
        e.location,
        u.student_id,
        u.full_name,
        u.email,
        u.faculty,
        u.course,
        ea.check_in_time,
        ea.check_out_time,
        ea.check_in_latitude,
        ea.check_in_longitude,
        ea.check_out_latitude,
        ea.check_out_longitude,
        CASE 
            WHEN ea.check_in_time IS NOT NULL AND ea.check_out_time IS NOT NULL THEN 'Completed'
            WHEN ea.check_in_time IS NOT NULL AND ea.check_out_time IS NULL THEN 'Checked In'
            ELSE 'Registered'
        END as attendance_status,
        CASE 
            WHEN ea.check_in_time <= CONCAT(e.event_date, ' ', e.start_time) THEN 'On Time'
            ELSE 'Late'
        END as punctuality_status,
        TIMESTAMPDIFF(MINUTE, CONCAT(e.event_date, ' ', e.start_time), ea.check_in_time) as minutes_late,
        CASE 
            WHEN ea.check_in_time IS NOT NULL AND ea.check_out_time IS NOT NULL 
            THEN TIMESTAMPDIFF(MINUTE, ea.check_in_time, ea.check_out_time)
            ELSE NULL
        END as duration_minutes,
        ea.created_at as registration_time
        FROM event_attendance ea
        JOIN events e ON ea.event_id = e.id
        JOIN users u ON ea.student_id = u.student_id
        WHERE e.deleted_at IS NULL";
    
    $params = [];
    
    if ($event_id !== 'all') {
        $query .= " AND ea.event_id = ?";
        $params[] = $event_id;
    }
    
    if ($date_from) {
        $query .= " AND e.event_date >= ?";
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $query .= " AND e.event_date <= ?";
        $params[] = $date_to;
    }
    
    if ($status_filter !== 'all') {
        switch ($status_filter) {
            case 'completed':
                $query .= " AND ea.check_in_time IS NOT NULL AND ea.check_out_time IS NOT NULL";
                break;
            case 'checked_in':
                $query .= " AND ea.check_in_time IS NOT NULL AND ea.check_out_time IS NULL";
                break;
            case 'registered':
                $query .= " AND ea.check_in_time IS NULL";
                break;
        }
    }
    
    $query .= " ORDER BY e.event_date DESC, ea.check_in_time DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if ($format === 'excel') {
        exportToExcel($data, 'attendance_report');
    } else {
        exportToCSV($data, 'attendance_report');
    }
}

function exportEventsData($conn, $format, $date_from, $date_to) {
    $query = "SELECT 
        e.title,
        e.description,
        e.event_date,
        e.start_time,
        e.end_time,
        e.location,
        e.latitude,
        e.longitude,
        e.max_participants,
        e.status,
        e.created_by,
        advisor.full_name as advisor_name,
        COUNT(ea.id) as total_registrations,
        COUNT(CASE WHEN ea.check_in_time IS NOT NULL THEN 1 END) as total_checkins,
        COUNT(CASE WHEN ea.check_in_time IS NOT NULL AND ea.check_out_time IS NOT NULL THEN 1 END) as total_completed,
        ROUND(AVG(CASE WHEN ea.check_in_time IS NOT NULL AND ea.check_out_time IS NOT NULL 
            THEN TIMESTAMPDIFF(MINUTE, ea.check_in_time, ea.check_out_time) END), 2) as avg_duration_minutes,
        e.created_at,
        e.updated_at
        FROM events e
        LEFT JOIN users advisor ON e.created_by = advisor.id
        LEFT JOIN event_attendance ea ON e.id = ea.event_id
        WHERE e.deleted_at IS NULL";
    
    $params = [];
    
    if ($date_from) {
        $query .= " AND e.event_date >= ?";
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $query .= " AND e.event_date <= ?";
        $params[] = $date_to;
    }
    
    $query .= " GROUP BY e.id ORDER BY e.event_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if ($format === 'excel') {
        exportToExcel($data, 'events_report');
    } else {
        exportToCSV($data, 'events_report');
    }
}

function exportSummaryData($conn, $format, $date_from, $date_to) {
    $query = "SELECT 
        e.title as event_title,
        e.event_date,
        e.location,
        e.max_participants,
        COUNT(ea.id) as total_registered,
        COUNT(CASE WHEN ea.check_in_time IS NOT NULL THEN 1 END) as total_attended,
        COUNT(CASE WHEN ea.check_in_time IS NOT NULL AND ea.check_out_time IS NOT NULL THEN 1 END) as total_completed,
        ROUND((COUNT(CASE WHEN ea.check_in_time IS NOT NULL THEN 1 END) / COUNT(ea.id)) * 100, 2) as attendance_rate,
        ROUND((COUNT(CASE WHEN ea.check_in_time IS NOT NULL AND ea.check_out_time IS NOT NULL THEN 1 END) / COUNT(CASE WHEN ea.check_in_time IS NOT NULL THEN 1 END)) * 100, 2) as completion_rate,
        COUNT(CASE WHEN ea.check_in_time <= CONCAT(e.event_date, ' ', e.start_time) THEN 1 END) as on_time_count,
        COUNT(CASE WHEN ea.check_in_time > CONCAT(e.event_date, ' ', e.start_time) THEN 1 END) as late_count,
        ROUND(AVG(CASE WHEN ea.check_in_time IS NOT NULL AND ea.check_out_time IS NOT NULL 
            THEN TIMESTAMPDIFF(MINUTE, ea.check_in_time, ea.check_out_time) END), 2) as avg_duration_minutes
        FROM events e
        LEFT JOIN event_attendance ea ON e.id = ea.event_id
        WHERE e.deleted_at IS NULL";
    
    $params = [];
    
    if ($date_from) {
        $query .= " AND e.event_date >= ?";
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $query .= " AND e.event_date <= ?";
        $params[] = $date_to;
    }
    
    $query .= " GROUP BY e.id ORDER BY e.event_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if ($format === 'excel') {
        exportToExcel($data, 'summary_report');
    } else {
        exportToCSV($data, 'summary_report');
    }
}

function exportAnalyticsData($conn, $format, $date_from, $date_to) {
    // Get faculty-wise attendance data
    $query = "SELECT 
        u.faculty,
        COUNT(DISTINCT u.student_id) as total_students,
        COUNT(ea.id) as total_registrations,
        COUNT(CASE WHEN ea.check_in_time IS NOT NULL THEN 1 END) as total_attendance,
        ROUND(AVG(CASE WHEN ea.check_in_time IS NOT NULL THEN 1 ELSE 0 END) * 100, 2) as attendance_rate,
        COUNT(CASE WHEN ea.check_in_time <= CONCAT(e.event_date, ' ', e.start_time) THEN 1 END) as on_time_attendance,
        COUNT(CASE WHEN ea.check_in_time > CONCAT(e.event_date, ' ', e.start_time) THEN 1 END) as late_attendance
        FROM users u
        LEFT JOIN event_attendance ea ON u.student_id = ea.student_id
        LEFT JOIN events e ON ea.event_id = e.id
        WHERE u.role = 'student' AND e.deleted_at IS NULL";
    
    $params = [];
    
    if ($date_from) {
        $query .= " AND e.event_date >= ?";
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $query .= " AND e.event_date <= ?";
        $params[] = $date_to;
    }
    
    $query .= " GROUP BY u.faculty ORDER BY total_attendance DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if ($format === 'excel') {
        exportToExcel($data, 'analytics_report');
    } else {
        exportToCSV($data, 'analytics_report');
    }
}

function exportToCSV($data, $filename) {
    $filename = $filename . '_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($data)) {
        // Write headers
        fputcsv($output, array_keys($data[0]));
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit();
}

function exportToExcel($data, $filename) {
    $filename = $filename . '_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    
    // Simple Excel XML format
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
    echo '<Worksheet ss:Name="Sheet1">' . "\n";
    echo '<Table>' . "\n";
    
    if (!empty($data)) {
        // Write headers
        echo '<Row>' . "\n";
        foreach (array_keys($data[0]) as $header) {
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>' . "\n";
        }
        echo '</Row>' . "\n";
        
        // Write data
        foreach ($data as $row) {
            echo '<Row>' . "\n";
            foreach ($row as $value) {
                $type = is_numeric($value) ? 'Number' : 'String';
                echo '<Cell><Data ss:Type="' . $type . '">' . htmlspecialchars($value) . '</Data></Cell>' . "\n";
            }
            echo '</Row>' . "\n";
        }
    }
    
    echo '</Table>' . "\n";
    echo '</Worksheet>' . "\n";
    echo '</Workbook>' . "\n";
    
    exit();
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-download"></i> Export Attendance Data</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="attendance_dashboard.php">Attendance</a></li>
                        <li class="breadcrumb-item active">Export</li>
                    </ol>
                </nav>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Export Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Events with Attendance</h6>
                                    <h3><?php echo number_format($stats['total_events_with_attendance']); ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-check fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Records</h6>
                                    <h3><?php echo number_format($stats['total_attendance_records']); ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-database fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Completed</h6>
                                    <h3><?php echo number_format($stats['completed_attendances']); ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-double fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Pending</h6>
                                    <h3><?php echo number_format($stats['pending_checkouts']); ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Export Forms -->
            <div class="row">
                <!-- Attendance Data Export -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-users"></i> Attendance Records
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Export detailed attendance records with student information, check-in/out times, and location data.</p>
                            
                            <form method="GET" action="">
                                <input type="hidden" name="action" value="export">
                                <input type="hidden" name="type" value="attendance">
                                
                                <div class="mb-3">
                                    <label for="attendance_event" class="form-label">Event</label>
                                    <select class="form-select" id="attendance_event" name="event_id">
                                        <option value="all">All Events</option>
                                        <?php foreach ($events as $event): ?>
                                            <option value="<?php echo $event['id']; ?>">
                                                <?php echo htmlspecialchars($event['title'] . ' - ' . date('M d, Y', strtotime($event['event_date']))); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="attendance_date_from" class="form-label">Date From</label>
                                            <input type="date" class="form-control" id="attendance_date_from" name="date_from">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="attendance_date_to" class="form-label">Date To</label>
                                            <input type="date" class="form-control" id="attendance_date_to" name="date_to">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="attendance_status" class="form-label">Status Filter</label>
                                    <select class="form-select" id="attendance_status" name="status">
                                        <option value="all">All Statuses</option>
                                        <option value="completed">Completed (Checked In & Out)</option>
                                        <option value="checked_in">Checked In Only</option>
                                        <option value="registered">Registered Only</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="attendance_format" class="form-label">Format</label>
                                    <select class="form-select" id="attendance_format" name="format">
                                        <option value="csv">CSV</option>
                                        <option value="excel">Excel</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-download"></i> Export Attendance
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Events Summary Export -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-calendar-alt"></i> Events Summary
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Export events with attendance statistics including registration counts, attendance rates, and completion rates.</p>
                            
                            <form method="GET" action="">
                                <input type="hidden" name="action" value="export">
                                <input type="hidden" name="type" value="events">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="events_date_from" class="form-label">Date From</label>
                                            <input type="date" class="form-control" id="events_date_from" name="date_from">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="events_date_to" class="form-label">Date To</label>
                                            <input type="date" class="form-control" id="events_date_to" name="date_to">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="events_format" class="form-label">Format</label>
                                    <select class="form-select" id="events_format" name="format">
                                        <option value="csv">CSV</option>
                                        <option value="excel">Excel</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-download"></i> Export Events
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <!-- Summary Report Export -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-bar"></i> Summary Report
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Export condensed summary with key metrics like attendance rates, punctuality, and completion rates per event.</p>
                            
                            <form method="GET" action="">
                                <input type="hidden" name="action" value="export">
                                <input type="hidden" name="type" value="summary">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="summary_date_from" class="form-label">Date From</label>
                                            <input type="date" class="form-control" id="summary_date_from" name="date_from">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="summary_date_to" class="form-label">Date To</label>
                                            <input type="date" class="form-control" id="summary_date_to" name="date_to">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="summary_format" class="form-label">Format</label>
                                    <select class="form-select" id="summary_format" name="format">
                                        <option value="csv">CSV</option>
                                        <option value="excel">Excel</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-download"></i> Export Summary
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Analytics Export -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-analytics"></i> Analytics Data
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Export faculty-wise analytics including student participation rates, attendance patterns, and punctuality statistics.</p>
                            
                            <form method="GET" action="">
                                <input type="hidden" name="action" value="export">
                                <input type="hidden" name="type" value="analytics">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="analytics_date_from" class="form-label">Date From</label>
                                            <input type="date" class="form-control" id="analytics_date_from" name="date_from">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="analytics_date_to" class="form-label">Date To</label>
                                            <input type="date" class="form-control" id="analytics_date_to" name="date_to">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="analytics_format" class="form-label">Format</label>
                                    <select class="form-select" id="analytics_format" name="format">
                                        <option value="csv">CSV</option>
                                        <option value="excel">Excel</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-download"></i> Export Analytics
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Export Options -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt"></i> Quick Export Options
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="d-grid">
                                <a href="?action=export&type=attendance&format=csv&date_from=<?php echo date('Y-m-01'); ?>&date_to=<?php echo date('Y-m-t'); ?>" 
                                   class="btn btn-outline-primary">
                                    <i class="fas fa-calendar-month"></i><br>
                                    <small>This Month's Attendance</small>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-grid">
                                <a href="?action=export&type=events&format=csv&date_from=<?php echo date('Y-01-01'); ?>&date_to=<?php echo date('Y-12-31'); ?>" 
                                   class="btn btn-outline-success">
                                    <i class="fas fa-calendar-year"></i><br>
                                    <small>This Year's Events</small>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-grid">
                                <a href="?action=export&type=summary&format=excel&date_from=<?php echo date('Y-m-01', strtotime('-6 months')); ?>&date_to=<?php echo date('Y-m-t'); ?>" 
                                   class="btn btn-outline-info">
                                    <i class="fas fa-chart-line"></i><br>
                                    <small>6-Month Summary</small>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-grid">
                                <a href="?action=export&type=analytics&format=excel&date_from=<?php echo date('Y-01-01'); ?>&date_to=<?php echo date('Y-12-31'); ?>" 
                                   class="btn btn-outline-warning">
                                    <i class="fas fa-graduation-cap"></i><br>
                                    <small>Faculty Analytics</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Export Guidelines -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle"></i> Export Guidelines
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-file-csv text-success"></i> CSV Format</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Lightweight and fast</li>
                                <li><i class="fas fa-check text-success"></i> Compatible with all spreadsheet applications</li>
                                <li><i class="fas fa-check text-success"></i> Suitable for large datasets</li>
                                <li><i class="fas fa-check text-success"></i> Easy to import into databases</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-file-excel text-primary"></i> Excel Format</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Preserves data types</li>
                                <li><i class="fas fa-check text-success"></i> Better formatting options</li>
                                <li><i class="fas fa-check text-success"></i> Direct opening in Excel</li>
                                <li><i class="fas fa-exclamation-triangle text-warning"></i> Larger file size</li>
                            </ul>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-12">
                            <h6><i class="fas fa-lightbulb text-info"></i> Tips for Better Exports</h6>
                            <ul>
                                <li>Use date filters to limit data size for better performance</li>
                                <li>Choose specific events when exporting detailed attendance records</li>
                                <li>Use CSV format for large datasets (>1000 records)</li>
                                <li>Export during off-peak hours for better system performance</li>
                                <li>Verify data completeness before generating reports</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Set default date ranges
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    
    const formatDate = (date) => {
        return date.toISOString().split('T')[0];
    };
    
    // Set default date ranges for all forms
    const dateFromInputs = document.querySelectorAll('input[name="date_from"]');
    const dateToInputs = document.querySelectorAll('input[name="date_to"]');
    
    dateFromInputs.forEach(input => {
        if (!input.value) {
            input.value = formatDate(firstDay);
        }
    });
    
    dateToInputs.forEach(input => {
        if (!input.value) {
            input.value = formatDate(lastDay);
        }
    });
});

// Show loading state when exporting
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
        
        // Re-enable after 5 seconds (in case of error)
        setTimeout(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }, 5000);
    });
});

// Quick export confirmation
document.querySelectorAll('a[href*="action=export"]').forEach(link => {
    link.addEventListener('click', function(e) {
        if (!confirm('This will download the export file immediately. Continue?')) {
            e.preventDefault();
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>