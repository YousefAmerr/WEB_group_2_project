<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'teacher')) {
    header("Location: login.php");
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
$attendance_record = null;

// Get attendance record ID from URL
$attendance_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission
if ($_POST) {
    $attendance_id = intval($_POST['attendance_id']);
    $student_id = intval($_POST['student_id']);
    $subject_id = intval($_POST['subject_id']);
    $attendance_date = $_POST['attendance_date'];
    $status = $_POST['status'];
    $remarks = trim($_POST['remarks']);
    
    // Validate input
    if (empty($student_id) || empty($subject_id) || empty($attendance_date) || empty($status)) {
        $error = "All required fields must be filled.";
    } else {
        try {
            // Update attendance record
            $stmt = $pdo->prepare("
                UPDATE attendance 
                SET student_id = ?, subject_id = ?, attendance_date = ?, status = ?, remarks = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            if ($stmt->execute([$student_id, $subject_id, $attendance_date, $status, $remarks, $attendance_id])) {
                $message = "Attendance record updated successfully!";
                
                // Log the action
                $log_stmt = $pdo->prepare("
                    INSERT INTO activity_logs (user_id, action, details, created_at) 
                    VALUES (?, 'UPDATE_ATTENDANCE', ?, NOW())
                ");
                $log_details = "Updated attendance for student ID: $student_id, Subject ID: $subject_id, Date: $attendance_date";
                $log_stmt->execute([$_SESSION['user_id'], $log_details]);
            } else {
                $error = "Failed to update attendance record.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch attendance record for editing
if ($attendance_id > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT a.*, s.first_name, s.last_name, s.student_id as student_number,
                   sub.subject_name, sub.subject_code
            FROM attendance a
            JOIN students s ON a.student_id = s.id
            JOIN subjects sub ON a.subject_id = sub.id
            WHERE a.id = ?
        ");
        $stmt->execute([$attendance_id]);
        $attendance_record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$attendance_record) {
            $error = "Attendance record not found.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Fetch all students for dropdown
try {
    $students_stmt = $pdo->query("SELECT id, student_id, first_name, last_name FROM students ORDER BY first_name, last_name");
    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $students = [];
}

// Fetch all subjects for dropdown
try {
    $subjects_stmt = $pdo->query("SELECT id, subject_code, subject_name FROM subjects ORDER BY subject_name");
    $subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $subjects = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Attendance - Student Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet">
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
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #ddd;
            padding: 12px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }
        
        .btn {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background-color: #6c757d;
            border: none;
        }
        
        .btn-secondary:hover {
            background-color: #545b62;
            transform: translateY(-2px);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .select2-container .select2-selection--single {
            height: 48px;
            border-radius: 10px;
            border: 1px solid #ddd;
        }
        
        .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: 46px;
            padding-left: 12px;
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
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .current-info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
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
                        <a class="nav-link" href="attendance.php">
                            <i class="fas fa-calendar-check me-1"></i>Attendance
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

    <div class="container main-content">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-edit text-primary me-2"></i>Edit Attendance Record</h2>
                        <p class="text-muted mb-0">Modify attendance information for students</p>
                    </div>
                    <a href="attendance.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Attendance
                    </a>
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

                <?php if ($attendance_record): ?>
                    <!-- Current Record Info -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Current Record Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="current-info">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Student:</strong> <?php echo htmlspecialchars($attendance_record['first_name'] . ' ' . $attendance_record['last_name']); ?><br>
                                        <strong>Student ID:</strong> <?php echo htmlspecialchars($attendance_record['student_number']); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Subject:</strong> <?php echo htmlspecialchars($attendance_record['subject_name']); ?><br>
                                        <strong>Subject Code:</strong> <?php echo htmlspecialchars($attendance_record['subject_code']); ?>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <strong>Date:</strong> <?php echo date('F j, Y', strtotime($attendance_record['attendance_date'])); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Current Status:</strong> 
                                        <span class="status-badge status-<?php echo strtolower($attendance_record['status']); ?>">
                                            <?php echo ucfirst($attendance_record['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Form -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Attendance Details</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="attendance_id" value="<?php echo $attendance_record['id']; ?>">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="student_id" class="form-label">
                                            <i class="fas fa-user me-1"></i>Student
                                        </label>
                                        <select class="form-select select2" id="student_id" name="student_id" required>
                                            <option value="">Select Student</option>
                                            <?php foreach ($students as $student): ?>
                                                <option value="<?php echo $student['id']; ?>" 
                                                    <?php echo ($student['id'] == $attendance_record['student_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="subject_id" class="form-label">
                                            <i class="fas fa-book me-1"></i>Subject
                                        </label>
                                        <select class="form-select select2" id="subject_id" name="subject_id" required>
                                            <option value="">Select Subject</option>
                                            <?php foreach ($subjects as $subject): ?>
                                                <option value="<?php echo $subject['id']; ?>"
                                                    <?php echo ($subject['id'] == $attendance_record['subject_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="attendance_date" class="form-label">
                                            <i class="fas fa-calendar me-1"></i>Date
                                        </label>
                                        <input type="date" class="form-control" id="attendance_date" name="attendance_date" 
                                               value="<?php echo $attendance_record['attendance_date']; ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="status" class="form-label">
                                            <i class="fas fa-check-circle me-1"></i>Attendance Status
                                        </label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="">Select Status</option>
                                            <option value="present" <?php echo ($attendance_record['status'] == 'present') ? 'selected' : ''; ?>>Present</option>
                                            <option value="absent" <?php echo ($attendance_record['status'] == 'absent') ? 'selected' : ''; ?>>Absent</option>
                                            <option value="late" <?php echo ($attendance_record['status'] == 'late') ? 'selected' : ''; ?>>Late</option>
                                            <option value="excused" <?php echo ($attendance_record['status'] == 'excused') ? 'selected' : ''; ?>>Excused</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="remarks" class="form-label">
                                        <i class="fas fa-comment me-1"></i>Remarks (Optional)
                                    </label>
                                    <textarea class="form-control" id="remarks" name="remarks" rows="3" 
                                              placeholder="Add any additional notes or comments..."><?php echo htmlspecialchars($attendance_record['remarks']); ?></textarea>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="attendance.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Attendance
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                            <h4>Record Not Found</h4>
                            <p class="text-muted">The attendance record you're trying to edit could not be found.</p>
                            <a href="attendance.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Attendance
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Select2 for better dropdowns
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select an option...',
                allowClear: true
            });
            
            // Form validation
            $('form').on('submit', function(e) {
                let isValid = true;
                let errorMessage = '';
                
                // Check required fields
                const requiredFields = ['student_id', 'subject_id', 'attendance_date', 'status'];
                requiredFields.forEach(function(field) {
                    if (!$('#' + field).val()) {
                        isValid = false;
                        $('#' + field).addClass('is-invalid');
                    } else {
                        $('#' + field).removeClass('is-invalid');
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                    return false;
                }
                
                // Confirm update
                if (!confirm('Are you sure you want to update this attendance record?')) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Remove validation classes on input
            $('.form-control, .form-select').on('change input', function() {
                $(this).removeClass('is-invalid');
            });
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        });
    </script>
</body>
</html>