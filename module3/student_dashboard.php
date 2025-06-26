<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'db_connect.php';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$student_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
if ($role !== 'student') {
    header('Location: login.php');
    exit();
}
// Fetch attendance records for the logged-in student
$attendance_records = [];
if ($student_id) {
    $sql = "SELECT a.*, e.eventName, e.eventLevel, a.attendanceDate, a.attendance_status FROM attendance a JOIN event e ON a.eventID = e.eventID WHERE a.studentID = ? ORDER BY a.attendanceDate DESC LIMIT 20";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('s', $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $attendance_records = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
    }
}
$studentName = '';
if ($student_id) {
    $stmt = $conn->prepare("SELECT studentName FROM student WHERE studentID = ?");
    $stmt->bind_param('s', $student_id);
    $stmt->execute();
    $stmt->bind_result($studentName);
    $stmt->fetch();
    $stmt->close();
}
include 'sidebar/Student_SideBar.php';
?>
<div class="main-content">
  <div class="content">
    <div class="card">
      <h2>Welcome, <?php echo htmlspecialchars($studentName); ?>!</h2>
      <h4 class="mb-3"><i class="material-icons" style="color:#7367f0;vertical-align:middle;">event</i> My Attendance</h4>
      <?php if ($attendance_records): ?>
        <div class="table-responsive mb-4">
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>Date</th>
                <th>Event</th>
                <th>Level</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($attendance_records as $record): ?>
                <tr>
                  <td><?php echo htmlspecialchars($record['attendanceDate']); ?></td>
                  <td><?php echo htmlspecialchars($record['eventName']); ?></td>
                  <td><?php echo htmlspecialchars($record['eventLevel']); ?>
                    <?php if (strtolower($record['eventLevel']) === 'joint'): ?>
                      <span style="background:#ff9800;color:#fff;padding:2px 8px;border-radius:4px;font-size:12px;margin-left:6px;">Joint Event</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($record['attendance_status']); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="alert alert-info">No attendance records found.</div>
      <?php endif; ?>
    </div>
  </div>
</div>
