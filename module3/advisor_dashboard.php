<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'db_connect.php';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$advisor_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
if ($role !== 'advisor') {
    header('Location: login.php');
    exit();
}
// Fetch attendance records for students advised by this advisor
$attendance_records = [];
if ($advisor_id) {
    $sql = "SELECT a.*, s.studentName, s.studentID, e.eventName, e.eventLevel, a.attendanceDate, a.attendance_status FROM attendance a JOIN student s ON a.studentID = s.studentID JOIN event e ON a.eventID = e.eventID WHERE a.advisorID = ? ORDER BY a.attendanceDate DESC LIMIT 20";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('s', $advisor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $attendance_records = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
    }
}
include 'sidebar/Advisor_SideBar.php';
?>
<div class="main-content">
  <div class="content">
    <div class="card">
      <h2>Welcome, Advisor!</h2>
      <h4 class="mb-3"><i class="material-icons" style="color:#7367f0;vertical-align:middle;">event</i> Students' Attendance</h4>
      <?php if ($attendance_records): ?>
        <div class="table-responsive mb-4">
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>Date</th>
                <th>Student</th>
                <th>Event</th>
                <th>Level</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($attendance_records as $record): ?>
                <tr>
                  <td><?php echo htmlspecialchars($record['attendanceDate']); ?></td>
                  <td><?php echo htmlspecialchars($record['studentName']); ?></td>
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
        <div class="alert alert-info">No attendance records found for your students.</div>
      <?php endif; ?>
    </div>
  </div>
</div>
