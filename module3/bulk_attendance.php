<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'teacher'])) {
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

$message = '';
$error = '';

// Get classes for the logged-in user
if ($_SESSION['user_type'] == 'admin') {
    $classes_query = "SELECT * FROM classes ORDER BY class_name";
} else {
    $classes_query = "SELECT c.* FROM classes c 
                     JOIN teacher_class_assignments tca ON c.class_id = tca.class_id 
                     WHERE tca.teacher_id = ? ORDER BY c.class_name";
}

$classes_stmt = $conn->prepare($classes_query);
if ($_SESSION['user_type'] != 'admin') {
    $classes_stmt->bind_param("i", $_SESSION['user_id']);
}
$classes_stmt->execute();
$classes_result = $classes_stmt->get_result();

// Handle form submission
if ($_POST['action'] == 'bulk_mark' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $class_id = $_POST['class_id'];
    $date = $_POST['date'];
    $period = $_POST['period'];
    $attendance_data = $_POST['attendance'] ?? [];
    
    try {
        $conn->begin_transaction();
        
        // Delete existing attendance records for this class, date, and period
        $delete_stmt = $conn->prepare("DELETE FROM attendance WHERE class_id = ? AND date = ? AND period = ?");
        $delete_stmt->bind_param("isi", $class_id, $date, $period);
        $delete_stmt->execute();
        
        // Insert new attendance records
        $insert_stmt = $conn->prepare("INSERT INTO attendance (student_id, class_id, date, period, status, marked_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        
        foreach ($attendance_data as $student_id => $status) {
            $insert_stmt->bind_param("iisisi", $student_id, $class_id, $date, $period, $status, $_SESSION['user_id']);
            $insert_stmt->execute();
        }
        
        $conn->commit();
        $message = "Bulk attendance marked successfully!";
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error marking attendance: " . $e->getMessage();
    }
}

// Get students for selected class
$students = [];
if (isset($_GET['class_id']) || isset($_POST['class_id'])) {
    $selected_class_id = $_GET['class_id'] ?? $_POST['class_id'];
    
    $students_stmt = $conn->prepare("
        SELECT s.student_id, s.first_name, s.last_name, s.roll_number 
        FROM students s 
        JOIN student_class_assignments sca ON s.student_id = sca.student_id 
        WHERE sca.class_id = ? 
        ORDER BY s.roll_number, s.last_name, s.first_name
    ");
    $students_stmt->bind_param("i", $selected_class_id);
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();
    $students = $students_result->fetch_all(MYSQLI_ASSOC);
    
    // Get existing attendance for the selected date and period
    $existing_attendance = [];
    if (isset($_POST['date']) && isset($_POST['period'])) {
        $existing_stmt = $conn->prepare("
            SELECT student_id, status 
            FROM attendance 
            WHERE class_id = ? AND date = ? AND period = ?
        ");
        $existing_stmt->bind_param("isi", $selected_class_id, $_POST['date'], $_POST['period']);
        $existing_stmt->execute();
        $existing_result = $existing_stmt->get_result();
        while ($row = $existing_result->fetch_assoc()) {
            $existing_attendance[$row['student_id']] = $row['status'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Attendance - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .attendance-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        .attendance-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .status-buttons {
            display: flex;
            gap: 5px;
        }
        .status-btn {
            flex: 1;
            padding: 8px;
            border: 2px solid transparent;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .status-btn.present {
            background-color: #d4edda;
            color: #155724;
        }
        .status-btn.absent {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-btn.late {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-btn.selected {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        .quick-actions {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-users-cog"></i> Bulk Attendance</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="attendance.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Attendance
                            </a>
                        </div>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Class and Date Selection -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-alt"></i> Select Class and Date</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" id="classDateForm">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="class_id" class="form-label">Class</label>
                                    <select name="class_id" id="class_id" class="form-select" required>
                                        <option value="">Select Class</option>
                                        <?php while ($class = $classes_result->fetch_assoc()): ?>
                                            <option value="<?php echo $class['class_id']; ?>" 
                                                    <?php echo (isset($_GET['class_id']) && $_GET['class_id'] == $class['class_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($class['class_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="date" class="form-label">Date</label>
                                    <input type="date" name="date" id="date" class="form-control" 
                                           value="<?php echo $_GET['date'] ?? date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="period" class="form-label">Period</label>
                                    <select name="period" id="period" class="form-select" required>
                                        <option value="">Select Period</option>
                                        <?php for ($i = 1; $i <= 8; $i++): ?>
                                            <option value="<?php echo $i; ?>" 
                                                    <?php echo (isset($_GET['period']) && $_GET['period'] == $i) ? 'selected' : ''; ?>>
                                                Period <?php echo $i; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Load Students
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (!empty($students)): ?>
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h6><i class="fas fa-bolt"></i> Quick Actions</h6>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-success btn-sm" onclick="markAll('present')">
                            <i class="fas fa-check"></i> Mark All Present
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="markAll('absent')">
                            <i class="fas fa-times"></i> Mark All Absent
                        </button>
                        <button type="button" class="btn btn-warning btn-sm" onclick="markAll('late')">
                            <i class="fas fa-clock"></i> Mark All Late
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="clearAll()">
                            <i class="fas fa-eraser"></i> Clear All
                        </button>
                    </div>
                </div>

                <!-- Attendance Form -->
                <form method="POST" id="attendanceForm">
                    <input type="hidden" name="action" value="bulk_mark">
                    <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($_GET['class_id']); ?>">
                    <input type="hidden" name="date" value="<?php echo htmlspecialchars($_GET['date']); ?>">
                    <input type="hidden" name="period" value="<?php echo htmlspecialchars($_GET['period']); ?>">
                    
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-clipboard-list"></i> Mark Attendance</h5>
                            <span class="badge bg-primary"><?php echo count($students); ?> Students</span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($students as $student): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="attendance-card">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                                <small class="text-muted">Roll: <?php echo htmlspecialchars($student['roll_number']); ?></small>
                                            </div>
                                            <div class="status-buttons">
                                                <div class="status-btn present <?php echo (isset($existing_attendance[$student['student_id']]) && $existing_attendance[$student['student_id']] == 'present') ? 'selected' : ''; ?>" 
                                                     onclick="selectStatus(<?php echo $student['student_id']; ?>, 'present', this)">
                                                    <i class="fas fa-check"></i> Present
                                                </div>
                                                <div class="status-btn absent <?php echo (isset($existing_attendance[$student['student_id']]) && $existing_attendance[$student['student_id']] == 'absent') ? 'selected' : ''; ?>" 
                                                     onclick="selectStatus(<?php echo $student['student_id']; ?>, 'absent', this)">
                                                    <i class="fas fa-times"></i> Absent
                                                </div>
                                                <div class="status-btn late <?php echo (isset($existing_attendance[$student['student_id']]) && $existing_attendance[$student['student_id']] == 'late') ? 'selected' : ''; ?>" 
                                                     onclick="selectStatus(<?php echo $student['student_id']; ?>, 'late', this)">
                                                    <i class="fas fa-clock"></i> Late
                                                </div>
                                            </div>
                                            <input type="hidden" name="attendance[<?php echo $student['student_id']; ?>]" 
                                                   value="<?php echo $existing_attendance[$student['student_id']] ?? ''; ?>" 
                                                   id="attendance_<?php echo $student['student_id']; ?>">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mt-4 text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> Save Attendance
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectStatus(studentId, status, element) {
            // Remove selected class from all buttons for this student
            const card = element.closest('.attendance-card');
            const buttons = card.querySelectorAll('.status-btn');
            buttons.forEach(btn => btn.classList.remove('selected'));
            
            // Add selected class to clicked button
            element.classList.add('selected');
            
            // Update hidden input
            document.getElementById('attendance_' + studentId).value = status;
        }

        function markAll(status) {
            const cards = document.querySelectorAll('.attendance-card');
            cards.forEach(card => {
                const buttons = card.querySelectorAll('.status-btn');
                const targetButton = card.querySelector('.status-btn.' + status);
                const studentId = card.querySelector('input[type="hidden"]').name.match(/\[(\d+)\]/)[1];
                
                // Remove selected from all buttons
                buttons.forEach(btn => btn.classList.remove('selected'));
                
                // Select target button
                if (targetButton) {
                    targetButton.classList.add('selected');
                    document.getElementById('attendance_' + studentId).value = status;
                }
            });
        }

        function clearAll() {
            const cards = document.querySelectorAll('.attendance-card');
            cards.forEach(card => {
                const buttons = card.querySelectorAll('.status-btn');
                buttons.forEach(btn => btn.classList.remove('selected'));
                
                const studentId = card.querySelector('input[type="hidden"]').name.match(/\[(\d+)\]/)[1];
                document.getElementById('attendance_' + studentId).value = '';
            });
        }

        // Auto-submit form when class, date, or period changes
        document.getElementById('class_id').addEventListener('change', function() {
            if (this.value) {
                document.getElementById('classDateForm').submit();
            }
        });
    </script>
</body>
</html>