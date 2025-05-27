<?php
session_start();

// Include database connection
include '../db_connect.php';
include '../sideBar/Advisor_SideBar.php';

// Check if advisor is logged in
$username = $_SESSION['username'] ?? '';
if (empty($username)) {
    header("Location: ../module1/login.php");
    exit();
}

// Get advisor ID from session
$advisorID = '';
$advisor_query = "SELECT advisorID FROM advisor WHERE username = ?";
$stmt = $conn->prepare($advisor_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $advisorID = $row['advisorID'];
}
$stmt->close();

// Handle approve/reject actions
if ($_POST && isset($_POST['action']) && isset($_POST['claim_id'])) {
    $claim_id = $_POST['claim_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve' || $action === 'reject') {
        $new_status = ($action === 'approve') ? 'Approved' : 'Rejected';
        
        $update_sql = "UPDATE merit_claims SET status = ? WHERE claim_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $new_status, $claim_id);
        
        if ($update_stmt->execute()) {
            $message = "Claim has been " . strtolower($new_status) . " successfully.";
            $message_type = "success";
        } else {
            $message = "Error updating claim status.";
            $message_type = "error";
        }
        $update_stmt->close();
    }
}

// Get filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$studentFilter = isset($_GET['student']) ? $_GET['student'] : '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Merit Claims Review</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <style>
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            padding-top: 70px;
            padding-left: 20px;
            padding-right: 20px;
            background-color: #f8f9fa;
        }

        .page_title {
            font-size: 28px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }

        .filters-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filter-row {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }

        .filter-group select {
            padding: 8px 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            min-width: 150px;
        }

        .filter-group select:focus {
            outline: none;
            border-color: #7367f0;
        }

        .claims-container {
            display: grid;
            gap: 20px;
        }

        .claim-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-left: 4px solid #17a2b8;
        }

        .claim-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .claim-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .claim-info {
            flex: 1;
        }

        .claim-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .claim-subtitle {
            color: #6c757d;
            font-size: 14px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-approved {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .claim-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .detail-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: #6c757d;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 14px;
            color: #2c3e50;
            font-weight: 500;
        }

        .claim-actions {
            display: flex;
            gap: 10px;
            padding-top: 16px;
            border-top: 1px solid #e9ecef;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-view {
            background-color: #007bff;
            color: white;
        }

        .btn-view:hover {
            background-color: #0056b3;
        }

        .btn-approve {
            background-color: #28a745;
            color: white;
        }

        .btn-approve:hover {
            background-color: #218838;
        }

        .btn-reject {
            background-color: #dc3545;
            color: white;
        }

        .btn-reject:hover {
            background-color: #c82333;
        }

        .no-claims {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .no-claims i {
            font-size: 64px;
            color: #dee2e6;
            margin-bottom: 16px;
        }

        .no-claims h3 {
            color: #6c757d;
            margin-bottom: 8px;
        }

        .no-claims p {
            color: #adb5bd;
        }

        .alert {
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            color: #2c3e50;
        }

        .close {
            color: #adb5bd;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }

        .close:hover {
            color: #6c757d;
        }

        .modal-body {
            padding: 20px;
        }

        .student-info {
            background-color: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .support-doc {
            margin-top: 16px;
        }

        .support-doc img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 14px;
            font-weight: 500;
        }

        .stat-pending .stat-number { color: #f39c12; }
        .stat-approved .stat-number { color: #27ae60; }
        .stat-rejected .stat-number { color: #e74c3c; }
        .stat-total .stat-number { color: #3498db; }
    </style>
</head>
<body>
    <div class="main-content">
        <h1 class="page_title">Student Merit Claims Review</h1>

        <?php if (isset($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php
        // Get statistics
        $stats_sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
                      FROM merit_claims";
        $stats_result = $conn->query($stats_sql);
        $stats = $stats_result->fetch_assoc();
        ?>

        <div class="stats-container">
            <div class="stat-card stat-total">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Claims</div>
            </div>
            <div class="stat-card stat-pending">
                <div class="stat-number"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            <div class="stat-card stat-approved">
                <div class="stat-number"><?php echo $stats['approved']; ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card stat-rejected">
                <div class="stat-number"><?php echo $stats['rejected']; ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>

        <div class="filters-container">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="status">Filter by Status:</label>
                        <select id="status" name="status" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="Pending" <?php echo ($statusFilter == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Approved" <?php echo ($statusFilter == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                            <option value="Rejected" <?php echo ($statusFilter == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="student">Filter by Student:</label>
                        <select id="student" name="student" onchange="this.form.submit()">
                            <option value="">All Students</option>
                            <?php
                            $students_sql = "SELECT DISTINCT s.studentID, s.studentName FROM student s 
                                           INNER JOIN merit_claims mc ON s.studentID = mc.studentID 
                                           ORDER BY s.studentName";
                            $students_result = $conn->query($students_sql);
                            while ($student = $students_result->fetch_assoc()) {
                                $selected = ($studentFilter == $student['studentID']) ? 'selected' : '';
                                echo "<option value='{$student['studentID']}' $selected>{$student['studentName']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <div class="claims-container">
            <?php
            try {
                // Build query with filters
                $sql = "SELECT mc.*, s.studentName, s.studentEmail, s.studentCard, e.eventName, e.eventLevel 
                        FROM merit_claims mc
                        JOIN student s ON mc.studentID = s.studentID
                        LEFT JOIN event e ON mc.eventID = e.eventID
                        WHERE 1=1";
                
                $params = [];
                $types = "";
                
                if (!empty($statusFilter)) {
                    $sql .= " AND mc.status = ?";
                    $params[] = $statusFilter;
                    $types .= "s";
                }
                
                if (!empty($studentFilter)) {
                    $sql .= " AND mc.studentID = ?";
                    $params[] = $studentFilter;
                    $types .= "s";
                }
                
                $sql .= " ORDER BY mc.claim_date DESC";
                
                $stmt = $conn->prepare($sql);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    echo '<div class="no-claims">
                            <i class="material-icons">assignment</i>
                            <h3>No Claims Found</h3>
                            <p>There are no merit claims matching your current filters.</p>
                          </div>';
                } else {
                    while ($row = $result->fetch_assoc()) {
                        $claimId = $row['claim_id'];
                        $studentName = htmlspecialchars($row['studentName']);
                        $studentCard = htmlspecialchars($row['studentCard']);
                        $studentEmail = htmlspecialchars($row['studentEmail']);
                        $eventName = htmlspecialchars($row['eventName'] ?? 'Event Not Found');
                        $eventLevel = htmlspecialchars($row['eventLevel'] ?? 'N/A');
                        $status = $row['status'];
                        $claimDate = date('M d, Y H:i', strtotime($row['claim_date']));
                        $supportDoc = $row['support_doc'];
                        
                        $statusClass = 'status-' . strtolower($status);
                        
                        echo "
                        <div class='claim-card'>
                            <div class='claim-header'>
                                <div class='claim-info'>
                                    <div class='claim-title'>Merit Claim #{$claimId}</div>
                                    <div class='claim-subtitle'>Submitted by {$studentName}</div>
                                </div>
                                <div class='status-badge {$statusClass}'>{$status}</div>
                            </div>
                            
                            <div class='claim-details'>
                                <div class='detail-item'>
                                    <div class='detail-label'>Student</div>
                                    <div class='detail-value'>{$studentName} ({$studentCard})</div>
                                </div>
                                <div class='detail-item'>
                                    <div class='detail-label'>Event</div>
                                    <div class='detail-value'>{$eventName}</div>
                                </div>
                                <div class='detail-item'>
                                    <div class='detail-label'>Event Level</div>
                                    <div class='detail-value'>{$eventLevel}</div>
                                </div>
                                <div class='detail-item'>
                                    <div class='detail-label'>Claim Date</div>
                                    <div class='detail-value'>{$claimDate}</div>
                                </div>
                            </div>
                            
                            <div class='claim-actions'>
                                <button class='btn btn-view' onclick='viewClaimDetails({$claimId})'>
                                    <i class='material-icons' style='font-size: 16px;'>visibility</i>
                                    View Details
                                </button>";
                        
                        if ($status === 'Pending') {
                            echo "
                                <form method='POST' style='display: inline;' onsubmit='return confirm(\"Are you sure you want to approve this claim?\")'>
                                    <input type='hidden' name='claim_id' value='{$claimId}'>
                                    <input type='hidden' name='action' value='approve'>
                                    <button type='submit' class='btn btn-approve'>
                                        <i class='material-icons' style='font-size: 16px;'>check</i>
                                        Approve
                                    </button>
                                </form>
                                <form method='POST' style='display: inline;' onsubmit='return confirm(\"Are you sure you want to reject this claim?\")'>
                                    <input type='hidden' name='claim_id' value='{$claimId}'>
                                    <input type='hidden' name='action' value='reject'>
                                    <button type='submit' class='btn btn-reject'>
                                        <i class='material-icons' style='font-size: 16px;'>close</i>
                                        Reject
                                    </button>
                                </form>";
                        }
                        
                        echo "
                            </div>
                        </div>";
                    }
                }
                
                $stmt->close();
            } catch (Exception $e) {
                echo "<div class='alert alert-error'>Error loading claims: " . $e->getMessage() . "</div>";
            }
            ?>
        </div>
    </div>

    <!-- Modal for viewing claim details -->
    <div id="claimModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Claim Details</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        function viewClaimDetails(claimId) {
            // Show loading
            document.getElementById('modalBody').innerHTML = '<p>Loading claim details...</p>';
            document.getElementById('claimModal').style.display = 'block';
            
            // Fetch claim details via AJAX
            fetch('get_claim_details.php?claim_id=' + claimId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modalBody').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('modalBody').innerHTML = '<p>Error loading claim details.</p>';
                });
        }

        function closeModal() {
            document.getElementById('claimModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('claimModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>