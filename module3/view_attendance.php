<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an advisor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'advisor') {
    header("Location: index.php");
    exit();
}

$advisorID = $_SESSION['user_id'];
$attendanceID = $_GET['id'] ?? '';

if (empty($attendanceID)) {
    header("Location: attendance_management.php");
    exit();
}

// Fetch attendance details
$stmt = $pdo->prepare("
    SELECT a.*, e.eventName, e.eventLocation, e.eventLevel 
    FROM attendance a 
    JOIN event e ON a.eventID = e.eventID 
    WHERE a.attendanceID = ? AND a.advisorID = ?
");
$stmt->execute([$attendanceID, $advisorID]);
$attendance = $stmt->fetch();

if (!$attendance) {
    header("Location: attendance_management.php");
    exit();
}

// Fetch participants
$stmt = $pdo->prepare("
    SELECT ac.*, s.studentName, s.studentCard, s.studentEmail 
    FROM attendancecslot ac 
    JOIN student s ON ac.studentID = s.studentID 
    WHERE ac.attendanceID = ? 
    ORDER BY ac.attendance_date DESC
");
$stmt->execute([$attendanceID]);
$participants = $stmt->fetchAll();

// Handle participant status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_participant_status'])) {
    $checkInID = $_POST['checkInID'];
    $newStatus = $_POST['new_status'];
    
    $stmt = $pdo->prepare("UPDATE attendancecslot SET status = ? WHERE checkInID = ?");
    if ($stmt->execute([$newStatus, $checkInID])) {
        $message = "Participant status updated successfully!";
        // Refresh the page to show updated data
        header("Location: view_attendance.php?id=" . $attendanceID);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Attendance - MyPetakom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: bold;
            color: #007bff !important;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .table th {
            background-color: #f8f9fa;
        }
        .status-present {
            background-color: #d4edda;
            color: #155724;
        }
        .status-absent {
            background-color: #f8d7da;
            color: #721c24;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            color: #495057;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap"></i> MyPetakom
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
                </span>
                <a class="nav-link" href="advisor_dashboard.php">Dashboard</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-calendar-check"></i> Attendance Details</h2>
                    <a href="attendance_management.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Management
                    </a>
                </div>
            </div>
        </div>

        <!-- Attendance Information -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> Event Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <span class="info-label">Event Name:</span><br>
                                    <?php echo htmlspecialchars($attendance['eventName']); ?>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Location:</span><br>
                                    <?php echo htmlspecialchars($attendance['eventLocation']); ?>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Event Level:</span><br>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($attendance['eventLevel']); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <span class="info-label">Attendance Date:</span><br>
                                    <?php echo date('l, F d, Y', strtotime($attendance['attendanceDate'])); ?>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Status:</span><br>
                                    <span class="badge <?php echo $attendance['attendance_status'] == 'open' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo ucfirst($attendance['attendance_status']); ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Total Participants:</span><br>
                                    <span class="badge bg-primary"><?php echo count($participants); ?> students</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-qrcode"></i> QR Code Actions</h5>
                    </div>
                    <div class="card-body text-center">
                        <p class="text-muted">Generate or view QR code for this attendance session</p>
                        <a href="generate_qr.php?attendance_id=<?php echo $attendanceID; ?>" 
                           class="btn btn-success">
                            <i class="fas fa-qrcode"></i> Generate QR Code
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Participants List -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-users"></i> Participants</h5>
                        <div>
                            <button class="btn btn-sm btn-outline-primary" onclick="exportToCSV()">
                                <i class="fas fa-download"></i> Export CSV
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($participants)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No participants have checked in yet.</p>
                                <p class="text-muted">Share the QR code with students to allow them to check in.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped" id="participantsTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Student Card</th>
                                            <th>Student Name</th>
                                            <th>Email</th>
                                            <th>Check-in Time</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($participants as $index => $participant): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($participant['studentCard']); ?></td>
                                                <td><?php echo htmlspecialchars($participant['studentName']); ?></td>
                                                <td><?php echo htmlspecialchars($participant['studentEmail']); ?></td>
                                                <td><?php echo date('M d, Y g:i A', strtotime($participant['attendance_date'])); ?></td>
                                                <td>
                                                    <span class="badge <?php echo strtolower($participant['status']) == 'present' ? 'status-present' : 'status-absent'; ?>">
                                                        <?php echo htmlspecialchars($participant['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-warning" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#statusModal<?php echo $participant['checkInID']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>

                                                    <!-- Status Update Modal -->
                                                    <div class="modal fade" id="statusModal<?php echo $participant['checkInID']; ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Update Participant Status</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form method="POST">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="checkInID" value="<?php echo $participant['checkInID']; ?>">
                                                                        <p><strong>Student:</strong> <?php echo htmlspecialchars($participant['studentName']); ?></p>
                                                                        <p><strong>Current Status:</strong> <?php echo htmlspecialchars($participant['status']); ?></p>
                                                                        <div class="mb-3">
                                                                            <label class="form-label">New Status</label>
                                                                            <select class="form-select" name="new_status" required>
                                                                                <option value="Present" <?php echo $participant['status'] == 'Present' ? 'selected' : ''; ?>>Present</option>
                                                                                <option value="Absent" <?php echo $participant['status'] == 'Absent' ? 'selected' : ''; ?>>Absent</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="update_participant_status" class="btn btn-warning">Update Status</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportToCSV() {
            const table = document.getElementById('participantsTable');
            if (!table) return;
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [];
                const cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length - 1; j++) { // Exclude last column (Actions)
                    let data = cols[j].innerText.replace(/"/g, '""');
                    row.push('"' + data + '"');
                }
                csv.push(row.join(','));
            }
            
            const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
            const downloadLink = document.createElement('a');
            downloadLink.download = 'attendance_<?php echo $attendanceID; ?>.csv';
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    </script>
</body>
</html>