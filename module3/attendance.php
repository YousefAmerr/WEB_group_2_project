<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /web/WEB_group_2_project/module1/login.php");
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

$message = '';
$error = '';

// Handle quick actions
if ($_POST) {
    if (isset($_POST['action']) && $_POST['action'] == 'quick_delete') {
        $attendance_id = intval($_POST['attendance_id']);
        $stmt = $conn->prepare("DELETE FROM attendance WHERE id = ?");
        if ($stmt && $stmt->bind_param("i", $attendance_id) && $stmt->execute()) {
            $message = "Attendance record deleted successfully!";
        } else {
            $error = "Failed to delete attendance record.";
        }
        if ($stmt) $stmt->close();
    }
}

// Pagination and filtering
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 15;
$offset = ($page - 1) * $records_per_page;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_subject = isset($_GET['subject']) ? intval($_GET['subject']) : 0;
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';

// Build query conditions
$conditions = [];
$params = [];
$types = '';

if ($search) {
    $conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}
if ($filter_subject) {
    $conditions[] = "a.subject_id = ?";
    $params[] = $filter_subject;
    $types .= 'i';
}
if ($filter_status) {
    $conditions[] = "a.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}
if ($filter_date) {
    $conditions[] = "DATE(a.attendance_date) = ?";
    $params[] = $filter_date;
    $types .= 's';
}
$where_clause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get total records for pagination
$count_sql = "SELECT COUNT(*) FROM attendance a 
              JOIN student s ON a.student_id = s.studentID 
              JOIN subjects sub ON a.subject_id = sub.id $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!$count_stmt) {
    die('Prepare failed (count): ' . $conn->error . '<br>SQL: ' . $count_sql);
}
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_stmt->bind_result($total_records);
$count_stmt->fetch();
$count_stmt->close();
$total_pages = ceil($total_records / $records_per_page);

// Fetch attendance records
$sql = "SELECT a.*, s.studentName, s.studentID as student_number,
               sub.subject_name, sub.subject_code
        FROM attendance a
        JOIN student s ON a.student_id = s.studentID
        JOIN subjects sub ON a.subject_id = sub.id
        $where_clause
        ORDER BY a.attendance_date DESC, a.created_at DESC
        LIMIT $records_per_page OFFSET $offset";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Prepare failed (fetch): ' . $conn->error . '<br>SQL: ' . $sql);
}
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$attendance_records = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// Get subjects for filter dropdown
$subjects = [];
$subjects_result = $conn->query("SELECT id, subject_code, subject_name FROM subjects ORDER BY subject_name");
if ($subjects_result) {
    while ($row = $subjects_result->fetch_assoc()) {
        $subjects[] = $row;
    }
}

// Get attendance statistics
$stats_sql = "SELECT 
    COUNT(*) as total_records,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
    SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_count
FROM attendance a
JOIN student s ON a.student_id = s.studentID
JOIN subjects sub ON a.subject_id = sub.id
$where_clause";
$stats_stmt = $conn->prepare($stats_sql);
if ($types && $stats_stmt) $stats_stmt->bind_param($types, ...$params);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result ? $stats_result->fetch_assoc() : [
    'total_records' => 0,
    'present_count' => 0,
    'absent_count' => 0,
    'late_count' => 0,
    'excused_count' => 0
];
$stats_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management - Student Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/utilities.css">
    <link rel="stylesheet" href="css/attendance.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        
        .main-content {
            margin-top: 20px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 10px;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .btn {
            border-radius: 10px;
            padding: 8px 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 500;
        }
        
        .status-present { background-color: #d4edda; color: #155724; }
        .status-absent { background-color: #f8d7da; color: #721c24; }
        .status-late { background-color: #fff3cd; color: #856404; }
        .status-excused { background-color: #d1ecf1; color: #0c5460; }
        
        .table th {
            background-color: #f8f9fa;
            border-top: none;
            font-weight: 600;
            color: #495057;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .action-buttons .btn {
            padding: 5px 10px;
            font-size: 0.8em;
        }
        
        .filter-section {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .quick-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .quick-actions {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>
                Student Management System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="attendance.php">
                            <i class="fas fa-calendar-check me-1"></i>Attendance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="students.php">
                            <i class="fas fa-users me-1"></i>Students
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid main-content">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="fas fa-calendar-check text-primary me-2"></i>Attendance Management</h2>
                <p class="text-muted mb-0">Monitor and manage student attendance records</p>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="stats-card">
                    <div class="row">
                        <div class="col-md-3 col-6">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $stats['total_records']; ?></span>
                                <span class="stat-label">Total Records</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $stats['present_count']; ?></span>
                                <span class="stat-label">Present</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $stats['absent_count']; ?></span>
                                <span class="stat-label">Absent</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $stats['late_count']; ?></span>
                                <span class="stat-label">Late</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="add_attendance.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add Attendance
            </a>
            <a href="bulk_attendance.php" class="btn btn-success">
                <i class="fas fa-users me-2"></i>Bulk Attendance
            </a>
            <a href="create_attendance_slot.php" class="btn btn-info">
                <i class="fas fa-clock me-2"></i>Create Time Slot
            </a>
            <a href="attendance_report.php" class="btn btn-warning">
                <i class="fas fa-chart-bar me-2"></i>Reports
            </a>
            <a href="export_attendance.php" class="btn btn-secondary">
                <i class="fas fa-download me-2"></i>Export Data
            </a>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search Student</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Name or Student ID">
                </div>
                <div class="col-md-2">
                    <label for="subject" class="form-label">Subject</label>
                    <select class="form-select" id="subject" name="subject">
                        <option value="">All Subjects</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['id']; ?>" 
                                <?php echo ($filter_subject == $subject['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['subject_code']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="present" <?php echo ($filter_status == 'present') ? 'selected' : ''; ?>>Present</option>
                        <option value="absent" <?php echo ($filter_status == 'absent') ? 'selected' : ''; ?>>Absent</option>
                        <option value="late" <?php echo ($filter_status == 'late') ? 'selected' : ''; ?>>Late</option>
                        <option value="excused" <?php echo ($filter_status == 'excused') ? 'selected' : ''; ?>>Excused</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" 
                           value="<?php echo htmlspecialchars($filter_date); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                        <a href="attendance.php" class="btn btn-outline-secondary">
                            <i class="fas fa-refresh me-1"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Attendance Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Attendance Records
                    <span class="badge bg-light text-dark ms-2"><?php echo $total_records; ?> records</span>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($attendance_records): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Check-in Time</th>
                                    <th>Remarks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendance_records as $record): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($record['attendance_date'])); ?></td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($record['student_number']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($record['subject_code']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($record['subject_name']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($record['status']); ?>">
                                                <?php echo ucfirst($record['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $record['check_in_time'] ? date('g:i A', strtotime($record['check_in_time'])) : '-'; ?></td>
                                        <td><?php echo htmlspecialchars($record['remarks']) ?: '-'; ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit_attendance.php?id=<?php echo $record['id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                        onclick="deleteRecord(<?php echo $record['id']; ?>)" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Attendance pagination">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page-1; ?>&<?php echo http_build_query($_GET); ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query($_GET); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page+1; ?>&<?php echo http_build_query($_GET); ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h5>No Attendance Records Found</h5>
                        <p class="text-muted">Start by adding attendance records for your students.</p>
                        <a href="add_attendance.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add First Record
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this attendance record? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="quick_delete">
                        <input type="hidden" name="attendance_id" id="deleteId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <script>
        function deleteRecord(id) {
            document.getElementById('deleteId').value = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // Auto-dismiss alerts
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    </script>
</body>
</html>