<?php
session_start();
include 'db_connect.php';

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

if (!isset($_GET['id'])) {
    header("Location: create_attendance_slot.php");
    exit();
}

$attendanceID = $_GET['id'];

// Get attendance details
$attendance_query = "SELECT a.*, e.eventName, e.eventLocation 
                     FROM attendance a 
                     JOIN event e ON a.eventID = e.eventID 
                     WHERE a.attendanceID = '$attendanceID'";
$attendance_result = $conn->query($attendance_query);
$attendance = $attendance_result->fetch_assoc();

// Get attendees list
$attendees_query = "SELECT ac.*, s.studentName, s.studentCard, s.studentEmail 
                    FROM attendancecslot ac 
                    JOIN student s ON ac.studentID = s.studentID 
                    WHERE ac.attendanceID = '$attendanceID' 
                    ORDER BY ac.attendance_date DESC";
$attendees_result = $conn->query($attendees_query);

// Get attendance statistics
$total_attendees = $attendees_result->num_rows;
$present_count = 0;
$attendees_result->data_seek(0); // Reset pointer
while ($row = $attendees_result->fetch_assoc()) {
    if ($row['status'] == 'Present') $present_count++;
}
$attendees_result->data_seek(0); // Reset pointer again
?>

<div class="main-content">
    <div class="content">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h2>Attendees List</h2>
                    <p style="color: #666; margin: 5px 0;">
                        Event: <strong><?php echo htmlspecialchars($attendance['eventName']); ?></strong><br>
                        Location: <strong><?php echo htmlspecialchars($attendance['eventLocation']); ?></strong><br>
                        Date: <strong><?php echo $attendance['attendanceDate']; ?></strong>
                    </p>
                </div>
                <div style="text-align: right;">
                    <div style="background: #28a745; color: white; padding: 10px; border-radius: 8px; margin-bottom: 10px;">
                        <strong>Total Present: <?php echo $present_count; ?></strong>
                    </div>
                    <button onclick="exportToCSV()" style="background: #17a2b8; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer;">
                        Export CSV
                    </button>
                </div>
            </div>
            
            <?php if ($total_attendees > 0): ?>
                <div style="overflow-x: auto;">
                    <table id="attendeesTable" style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">#</th>
                                <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Student ID</th>
                                <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Student Name</th>
                                <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Student Card</th>
                                <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Email</th>
                                <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Check-in Date</th>
                                <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $counter = 1;
                            while ($attendee = $attendees_result->fetch_assoc()): 
                            ?>
                                <tr style="<?php echo $counter % 2 == 0 ? 'background: #f9f9f9;' : ''; ?>">
                                    <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $counter; ?></td>
                                    <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">
                                        <?php echo htmlspecialchars($attendee['studentID']); ?>
                                    </td>
                                    <td style="padding: 10px; border: 1px solid #ddd;">
                                        <?php echo htmlspecialchars($attendee['studentName']); ?>
                                    </td>
                                    <td style="padding: 10px; border: 1px solid #ddd;">
                                        <?php echo htmlspecialchars($attendee['studentCard']); ?>
                                    </td>
                                    <td style="padding: 10px; border: 1px solid #ddd;">
                                        <?php echo htmlspecialchars($attendee['studentEmail']); ?>
                                    </td>
                                    <td style="padding: 10px; border: 1px solid #ddd;">
                                        <?php echo date('M d, Y', strtotime($attendee['attendance_date'])); ?>
                                    </td>
                                    <td style="padding: 10px; border: 1px solid #ddd;">
                                        <span style="background: <?php echo $attendee['status'] == 'Present' ? '#28a745' : '#dc3545'; ?>; 
                                                   color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                            <?php echo htmlspecialchars($attendee['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php 
                            $counter++;
                            endwhile; 
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="material-icons" style="font-size: 48px; margin-bottom: 10px;">person_off</i>
                    <h3>No Attendees Yet</h3>
                    <p>Students haven't checked in for this event yet.</p>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 30px; text-align: center;">
                <a href="create_attendance_slot.php" 
                   style="background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;">
                    Back to Attendance Management
                </a>
                
                <?php if ($attendance['attendance_status'] == 'open'): ?>
                    <a href="close_attendance.php?id=<?php echo $attendanceID; ?>" 
                       style="background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;"
                       onclick="return confirm('Are you sure you want to close this attendance slot?')">
                        Close Attendance
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function exportToCSV() {
    const table = document.getElementById('attendeesTable');
    if (!table) {
        alert('No data to export');
        return;
    }
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            let cellText = cols[j].innerText.replace(/"/g, '""');
            row.push('"' + cellText + '"');
        }
        
        csv.push(row.join(','));
    }
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'attendees_<?php echo str_replace(' ', '_', $attendance['eventName']); ?>_<?php echo date('Y-m-d'); ?>.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// Add search functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add search box
    const searchBox = document.createElement('input');
    searchBox.type = 'text';
    searchBox.placeholder = 'Search attendees...';
    searchBox.style.cssText = 'padding: 8px; margin-bottom: 15px; width: 300px; border: 1px solid #ddd; border-radius: 4px;';
    
    const table = document.getElementById('attendeesTable');
    if (table) {
        table.parentNode.insertBefore(searchBox, table);
        
        searchBox.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const tbody = table.getElementsByTagName('tbody')[0];
            const rows = tbody.getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
        });
    }
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

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 10px;
    }
    
    .card {
        padding: 15px;
    }
    
    table {
        font-size: 14px;
    }
    
    th, td {
        padding: 8px !important;
    }
}
</style>