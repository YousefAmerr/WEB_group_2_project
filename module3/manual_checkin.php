<?php
session_start();
include 'db_connect.php';

$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
if ($role === 'student') {
    include_once 'student_dashboard.php';
} elseif ($role === 'coordinator' || $role === 'petakom_coordinator') {
    include_once 'coordinator_dashboard.php';
} elseif ($role === 'advisor' || $role === 'event_advisor') {
    include_once 'advisor_dashboard_SideBar.php';
}

if (!isset($_SESSION['advisorID'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: create_attendance_slot.php");
    exit();
}

$attendanceID = $_GET['id'];
$advisorID = $_SESSION['advisorID'];

// Get attendance details
$attendance_query = "SELECT a.*, e.eventName, e.eventLocation 
                     FROM attendance a 
                     JOIN event e ON a.eventID = e.eventID 
                     WHERE a.attendanceID = '$attendanceID' AND e.advisorID = '$advisorID'";
$attendance_result = $conn->query($attendance_query);

if ($attendance_result->num_rows == 0) {
    $_SESSION['error_message'] = "Attendance slot not found or access denied.";
    header("Location: create_attendance_slot.php");
    exit();
}

$attendance = $attendance_result->fetch_assoc();

// Get all students
$students_query = "SELECT studentID, studentName, studentCard, studentEmail FROM student ORDER BY studentName";
$students_result = $conn->query($students_query);

// Get already checked-in students
$checkedin_query = "SELECT studentID FROM attendancecslot WHERE attendanceID = '$attendanceID'";
$checkedin_result = $conn->query($checkedin_query);
$checkedin_students = [];
while ($row = $checkedin_result->fetch_assoc()) {
    $checkedin_students[] = $row['studentID'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $selected_students = isset($_POST['students']) ? $_POST['students'] : [];
    $checkin_date = $_POST['checkin_date'];
    
    if (empty($selected_students)) {
        $_SESSION['error_message'] = "Please select at least one student.";
    } else {
        $success_count = 0;
        $error_count = 0;
        
        foreach ($selected_students as $studentID) {
            // Check if student is already checked in
            if (in_array($studentID, $checkedin_students)) {
                $error_count++;
                continue;
            }
            
            $insert_query = "INSERT INTO attendancecslot (attendanceID, studentID, attendance_date, status) 
                           VALUES ('$attendanceID', '$studentID', '$checkin_date', 'Present')";
            
            if ($conn->query($insert_query) === TRUE) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        if ($success_count > 0) {
            $_SESSION['success_message'] = "Successfully checked in $success_count student(s).";
        }
        if ($error_count > 0) {
            $_SESSION['error_message'] = "Failed to check in $error_count student(s) (may already be checked in).";
        }
        
        header("Location: view_attendees.php?id=" . $attendanceID);
        exit();
    }
}
?>

<div class="main-content">
    <div class="content">
        <div class="card">
            <h2>Manual Check-in</h2>
            <p style="color: #666; margin-bottom: 20px;">
                Event: <strong><?php echo htmlspecialchars($attendance['eventName']); ?></strong><br>
                Location: <strong><?php echo htmlspecialchars($attendance['eventLocation']); ?></strong><br>
                Date: <strong><?php echo $attendance['attendanceDate']; ?></strong>
            </p>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="checkin_date">Check-in Date:</label>
                    <input type="datetime-local" name="checkin_date" id="checkin_date" 
                           value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Select Students to Check In:</label>
                    <div style="margin-bottom: 10px;">
                        <input type="text" id="studentSearch" placeholder="Search students..." 
                               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <div style="margin-top: 10px;">
                            <button type="button" onclick="selectAll()" style="background: #28a745; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; margin-right: 10px;">
                                Select All
                            </button>
                            <button type="button" onclick="deselectAll()" style="background: #dc3545; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer;">
                                Deselect All
                            </button>
                        </div>
                    </div>
                    
                    <div id="studentsList" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                        <?php while ($student = $students_result->fetch_assoc()): ?>
                            <?php $isCheckedIn = in_array($student['studentID'], $checkedin_students); ?>
                            <div class="student-item" style="display: flex; align-items: center; padding: 8px; margin-bottom: 5px; background: <?php echo $isCheckedIn ? '#f8f9fa' : 'white'; ?>; border-radius: 4px;">
                                <input type="checkbox" name="students[]" value="<?php echo $student['studentID']; ?>" 
                                       id="student_<?php echo $student['studentID']; ?>" 
                                       <?php echo $isCheckedIn ? 'disabled' : ''; ?>
                                       style="margin-right: 10px;">
                                <label for="student_<?php echo $student['studentID']; ?>" 
                                       style="flex: 1; cursor: <?php echo $isCheckedIn ? 'default' : 'pointer'; ?>; color: <?php echo $isCheckedIn ? '#6c757d' : 'inherit'; ?>;">
                                    <strong><?php echo htmlspecialchars($student['studentName']); ?></strong>
                                    <span style="color: #666; font-size: 12px;">
                                        (<?php echo htmlspecialchars($student['studentCard']); ?>) - <?php echo htmlspecialchars($student['studentEmail']); ?>
                                    </span>
                                    <?php if ($isCheckedIn): ?>
                                        <span style="color: #28a745; font-size: 12px; font-weight: bold;"> - Already Checked In</span>
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Check In Selected Students</button>
                    <a href="view_attendees.php?id=<?php echo $attendanceID; ?>" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function selectAll() {
    const checkboxes = document.querySelectorAll('input[name="students[]"]:not(:disabled)');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
}

function deselectAll() {
    const checkboxes = document.querySelectorAll('input[name="students[]"]:not(:disabled)');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
}

// Search functionality
document.getElementById('studentSearch').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const studentItems = document.querySelectorAll('.student-item');
    
    studentItems.forEach(item => {
        const text = item.textContent.toLowerCase();
        if (text.indexOf(filter) > -1) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
});
</script>

<style>
.card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.main-content {
    margin-left: 250px;
    padding: 20px;
}

.content {
    max-width: 1200px;
    margin: 0 auto;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}

.form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-actions {
    margin-top: 30px;
    text-align: center;
}

.btn-primary {
    background: #007bff;
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    margin-right: 10px;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    padding: 12px 30px;
    text-decoration: none;
    border-radius: 5px;
    font-size: 16px;
}

.btn-secondary:hover {
    background: #545b62;
}

.student-item:hover {
    background: #f8f9fa !important;
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 10px;
    }
    
    .card {
        padding: 15px;
    }
}
</style>