<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || 
    ($_SESSION['role'] !== 'advisor' && $_SESSION['role'] !== 'event_advisor')) {
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

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$advisorID = $_SESSION['user_id'];

// Get filter parameters
$filter_event = isset($_GET['event']) ? $_GET['event'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build the query with filters
$where_conditions = ["e.advisorID = '$advisorID'"];

if (!empty($filter_event)) {
    $where_conditions[] = "e.eventID = '$filter_event'";
}

if (!empty($filter_status)) {
    $where_conditions[] = "a.attendance_status = '$filter_status'";
}

if (!empty($filter_date_from)) {
    $where_conditions[] = "a.attendanceDate >= '$filter_date_from'";
}

if (!empty($filter_date_to)) {
    $where_conditions[] = "a.attendanceDate <= '$filter_date_to'";
}

$where_clause = implode(' AND ', $where_conditions);

// Get attendance records
$attendance_query = "SELECT a.*, e.eventName, e.eventLocation,
                     (SELECT COUNT(*) FROM attendancecslot ac WHERE ac.attendanceID = a.attendanceID AND ac.status = 'Present') as present_count,
                     (SELECT COUNT(*) FROM attendancecslot ac WHERE ac.attendanceID = a.attendanceID) as total_attendees
                     FROM attendance a 
                     JOIN event e ON a.eventID = e.eventID 
                     WHERE $where_clause
                     ORDER BY a.attendanceDate DESC";
$attendance_result = $conn->query($attendance_query);

// Get events for filter dropdown
$events_query = "SELECT eventID, eventName FROM event WHERE advisorID = '$advisorID' ORDER BY eventName";
$events_result = $conn->query($events_query);
?>

<div class="main-content" style="display: flex; gap: 32px; align-items: flex-start;">
    <div class="content" style="flex: 2; min-width: 0;">
        <div class="card">
            <h2>Attendance Reports</h2>
            <!-- Filter Form and Summary will be moved to the right panel -->
            <!-- Attendance Table -->
            <?php if ($attendance_result->num_rows > 0): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Event Name</th>
                                <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Location</th>
                                <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Date</th>
                                <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Status</th>
                                <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Attendees</th>
                                <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Attendance Rate</th>
                                <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($attendance = $attendance_result->fetch_assoc()): ?>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">
                                        <?php echo htmlspecialchars($attendance['eventName']); ?>
                                    </td>
                                    <td style="padding: 10px; border: 1px solid #ddd;">
                                        <?php echo htmlspecialchars($attendance['eventLocation']); ?>
                                    </td>
                                    <td style="padding: 10px; border: 1px solid #ddd;">
                                        <?php echo date('M d, Y', strtotime($attendance['attendanceDate'])); ?>
                                    </td>
                                    <td style="padding: 10px; border: 1px solid #ddd;">
                                        <span style="background: <?php echo $attendance['attendance_status'] == 'open' ? '#28a745' : '#dc3545'; ?>; 
                                                   color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                            <?php echo ucfirst($attendance['attendance_status']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 10px; border: 1px solid #ddd;">
                                        <?php echo $attendance['present_count'] . '/' . $attendance['total_attendees']; ?>
                                    </td>
                                    <td style="padding: 10px; border: 1px solid #ddd;">
                                        <?php 
                                        $rate = $attendance['total_attendees'] > 0 ? 
                                               round(($attendance['present_count'] / $attendance['total_attendees']) * 100, 1) : 0;
                                        echo $rate . '%';
                                        ?>
                                    </td>
                                    <td style="padding: 10px; border: 1px solid #ddd;">
                                        <a href="view_attendees.php?id=<?php echo $attendance['attendanceID']; ?>" 
                                           style="background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 12px;">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="material-icons" style="font-size: 48px; margin-bottom: 10px;">assignment</i>
                    <h3>No Attendance Records Found</h3>
                    <p>No attendance records match your current filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="report-panel" style="flex: 1; min-width: 320px; max-width: 400px;">
        <div class="card" style="position:sticky;top:32px;">
            <h2 style="font-size:1.5rem; margin-bottom:1rem;">Attendance Reports</h2>
            <!-- Filter Form -->
            <form method="GET" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: grid; grid-template-columns: 1fr; gap: 15px; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Event:</label>
                        <select name="event" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Events</option>
                            <?php $events_result->data_seek(0); while ($event = $events_result->fetch_assoc()): ?>
                                <option value="<?php echo $event['eventID']; ?>" <?php echo $filter_event == $event['eventID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($event['eventName']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Status:</label>
                        <select name="status" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Status</option>
                            <option value="open" <?php echo $filter_status == 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="closed" <?php echo $filter_status == 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">From Date:</label>
                        <input type="date" name="date_from" value="<?php echo $filter_date_from; ?>" 
                               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">To Date:</label>
                        <input type="date" name="date_to" value="<?php echo $filter_date_to; ?>" 
                               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div>
                        <button type="submit" style="background: #007bff; color: white; padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer;">
                            Filter
                        </button>
                        <a href="attendance_report.php" style="background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin-left: 10px;">
                            Clear
                        </a>
                    </div>
                </div>
            </form>
            <!-- Summary Statistics -->
            <div style="display: grid; grid-template-columns: 1fr; gap: 15px; margin-bottom: 20px;">
                <div style="background: #28a745; color: white; padding: 15px; border-radius: 8px; text-align: center;">
                    <h3 style="margin: 0;"><?php echo $attendance_result->num_rows; ?></h3>
                    <p style="margin: 5px 0 0 0;">Total Sessions</p>
                </div>
                <div style="background: #17a2b8; color: white; padding: 15px; border-radius: 8px; text-align: center;">
                    <?php
                    $total_present = 0;
                    $total_attendees = 0;
                    $attendance_result->data_seek(0);
                    while ($row = $attendance_result->fetch_assoc()) {
                        $total_present += $row['present_count'];
                        $total_attendees += $row['total_attendees'];
                    }
                    $attendance_result->data_seek(0);
                    ?>
                    <h3 style="margin: 0;"><?php echo $total_present; ?></h3>
                    <p style="margin: 5px 0 0 0;">Total Present</p>
                </div>
                <div style="background: #ffc107; color: white; padding: 15px; border-radius: 8px; text-align: center;">
                    <h3 style="margin: 0;"><?php echo $total_attendees > 0 ? round(($total_present / $total_attendees) * 100, 1) : 0; ?>%</h3>
                    <p style="margin: 5px 0 0 0;">Attendance Rate</p>
                </div>
            </div>
        </div>
    </div>
</div>

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
    display: flex;
    gap: 32px;
    align-items: flex-start;
}

.content {
    max-width: 1200px;
    margin: 0 auto;
}

.report-panel {
    min-width: 320px;
    max-width: 400px;
}

@media (max-width: 1000px) {
    .main-content {
        flex-direction: column;
        gap: 0;
    }
    .report-panel {
        max-width: 100%;
        min-width: 0;
        margin-top: 24px;
    }
}
</style>