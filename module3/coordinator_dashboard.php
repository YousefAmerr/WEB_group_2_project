<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'db_connect.php';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
if ($role !== 'coordinator') {
    header('Location: login.php');
    exit();
}
$attendance_records = [];
$sql = "SELECT a.*, s.studentName, s.studentID, e.eventName, e.eventLevel, a.attendanceDate, a.attendance_status FROM attendance a JOIN student s ON a.studentID = s.studentID JOIN event e ON a.eventID = e.eventID ORDER BY a.attendanceDate DESC LIMIT 20";
$result = $conn->query($sql);
if ($result) {
    $attendance_records = $result->fetch_all(MYSQLI_ASSOC);
}
include 'sidebar/Coordinator_SideBar.php';
?>
<div class="main-content">
  <div class="content">
    <div class="card">
      <h2>Welcome, Coordinator!</h2>
      <h4 class="mb-3"><i class="material-icons" style="color:#7367f0;vertical-align:middle;">event</i> All Attendance Records</h4>
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
                  <td><?php echo htmlspecialchars($record['eventLevel']); ?></td>
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
