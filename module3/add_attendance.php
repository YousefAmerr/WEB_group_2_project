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

// Handle form submission
if ($_POST) {
    $student_id = intval($_POST['student_id']);
    $subject_id = intval($_POST['subject_id']);
    $attendance_date = $_POST['attendance_date'];
    $status = $_POST['status'];
    $check_in_time = $_POST['check_in_time'];
    $remarks = trim($_POST['remarks']);
    
    // Validate input
    if (empty($student_id) || empty($subject_id) || empty($attendance_date) || empty($status)) {
        $error = "All required fields must be filled.";
    } else {
        try {
            // Check if attendance already exists for this student, subject, and date
            $check_stmt = $pdo->prepare("
                SELECT id FROM attendance 
                WHERE student_id = ? AND subject_id = ? AND attendance_date = ?
            ");
            $check_stmt->execute([$student_id, $subject_id, $attendance_date]);
            
            if ($check_stmt->fetch()) {
                $error = "Attendance record already exists for this student, subject, and date.";
            } else {
                // Insert new attendance record
                $stmt = $pdo->prepare("
                    INSERT INTO attendance (student_id, subject_id, attendance_date, status, check_in_time, remarks, created_by, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $check_in_time = !empty($check_in_time) ? $check_in_time : null;
                
                if ($stmt->execute([$student_id, $subject_id, $attendance_date, $status, $check_in_time, $remarks, $_SESSION['user_id']])) {
                    $message = "Attendance record added successfully!";
                    
                    // Log the action
                    $log_stmt = $pdo->prepare("
                        INSERT INTO activity_logs (user_id, action, details, created_at) 
                        VALUES (?, 'ADD_ATTENDANCE', ?, NOW())
                    ");
                    $log_details = "Added attendance for student ID: $student_id, Subject ID: $subject_id, Date: $attendance_date";
                    $log_stmt->execute([$_SESSION['user_id'], $log_details]);
                    
                    // Clear form data after successful submission
                    $_POST = array();
                } else {
                    $error = "Failed to add attendance record.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
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

// Get current date for default value
$current_date = date('Y-m-d');
$current_time = date('H:i');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Attendance - Student Management System</title>
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
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .required {
            color: #dc3545;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .quick-select-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .quick-select-buttons .btn {
            padding: 5px 15px;
            font-size: 0.9em;
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
                        <h2><i class="fas fa-plus text-primary me-2"></i>Add Attendance Record</h2>
                        <p class="text-muted mb-0">Create a new attendance entry for a student</p>
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

                <!-- Add Attendance Form -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-form me-2"></i>Attendance Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="attendanceForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="student_id" class="form-label">
                                        <i class="fas fa-user me-1"></i>Student <span class="required">*</span>
                                    </label>
                                    <select class="form-select select2" id="student_id" name="student_id" required>
                                        <option value="">Select Student</option>
                                        <?php foreach ($students as $student): ?>
                                            <option value="<?php echo $student['id']; ?>" 
                                                <?php echo (isset($_POST['student_id']) && $_POST['student_id'] == $student['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="subject_id" class="form-label">
                                        <i class="fas fa-book me-1"></i>Subject <span class="required">*</span>
                                    </label>
                                    <select class="form-select select2" id="subject_id" name="subject_id" required>
                                        <option value="">Select Subject</option>
                                        <?php foreach ($subjects as $subject): ?>
                                            <option value="<?php echo $subject['id']; ?>"
                                                <?php echo (isset($_POST['subject_id']) && $_POST['subject_id'] == $subject['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="attendance_date" class="form-label">
                                        <i class="fas fa-calendar me-1"></i>Date <span class="required">*</span>
                                    </label>
                                    <div class="quick-select-buttons">
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="setToday()">Today</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setYesterday()">Yesterday</button>
                                    </div>
                                    <input type="date" class="form-control" id="attendance_date" name="attendance_date" 
                                           value="<?php echo isset($_POST['attendance_date']) ? $_POST['attendance_date'] : $current_date; ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">
                                        <i class="fas fa-check-circle me-1"></i>Attendance Status <span class="required">*</span>
                                    </label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="">Select Status</option>
                                        <option value="present" <?php echo (isset($_POST['status']) && $_POST['status'] == 'present') ? 'selected' : 'selected'; ?>>Present</option>
                                        <option value="absent" <?php echo (isset($_POST['status']) && $_POST['status'] == 'absent') ? 'selected' : ''; ?>>Absent</option>
                                        <option value="late" <?php echo (isset($_POST['status']) && $_POST['status'] == 'late') ? 'selected' : ''; ?>>Late</option>
                                        <option value="excused" <?php echo (isset($_POST['status']) && $_POST['status'] == 'excused') ? 'selected' : ''; ?>>Excused</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="check_in_time" class="form-label">
                                        <i class="fas fa-clock me-1"></i>Check-in Time (Optional)
                                    </label>
                                    <div class="quick-select-buttons">
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="setCurrentTime()">Now</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearTime()">Clear</button>
                                    </div>
                                    <input type="time" class="form-control" id="check_in_time" name="check_in_time" 
                                           value="<?php echo isset($_POST['check_in_time']) ? $_POST['check_in_time'] : ''; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="remarks" name="remarks" 
                                                  style="height: 100px" placeholder="Add any additional notes..."><?php echo isset($_POST['remarks']) ? htmlspecialchars($_POST['remarks']) : ''; ?></textarea>
                                        <label for="remarks">
                                            <i class="fas fa-comment me-1"></i>Remarks (Optional)
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="attendance.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <div>
                                    <button type="button" class="btn btn-outline-primary me-2" onclick="resetForm()">
                                        <i class="fas fa-refresh me-2"></i>Reset Form
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Add Attendance
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Quick Tips -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i>Quick Tips</h6>
                        <ul class="mb-0">
                            <li>Use the search feature in dropdowns to quickly find students and subjects</li>
                            <li>Check-in time is automatically recorded when using QR code attendance</li>
                            <li>You can bulk mark attendance for multiple students using the Bulk Attendance feature</li>
                            <li>Duplicate records for the same student, subject, and date are not allowed</li>
                        </ul>
                    </div>
                </div>
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
                allowClear: true,
                width: '100%'
            });
            
            // Form validation
            $('#attendanceForm').on('submit', function(e) {
                let isValid = true;
                
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
        
        // Quick date functions
        function setToday() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('attendance_date').value = today;
        }
        
        function setYesterday() {
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            document.getElementById('attendance_date').value = yesterday.toISOString().split('T')[0];
        }
        
        // Quick time functions
        function setCurrentTime() {
            const now = new Date();
            const time = now.toTimeString().slice(0, 5);
            document.getElementById('check_in_time').value = time;
        }
        
        function clearTime() {
            document.getElementById('check_in_time').value = '';
        }
        
        // Reset form function
        function resetForm() {
            if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
                document.getElementById('attendanceForm').reset();
                $('.select2').val('').trigger('change');
                setToday(); // Set today's date as default
            }
        }
    </script>
</body>
</html>