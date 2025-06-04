<?php
session_start();

include '../db_connect.php';
include '../sideBar/Advisor_SideBar.php';

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

// approve/reject actions
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

// filter parameters
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
    <link rel="stylesheet" href="css/advisor.css" />
</head>
<body>
    <div class="main-content">
        <h1 class="page_title">Student Merit Claims Review</h1>



        <?php
        $stats_sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
                      FROM merit_claims";
        $stats_result = $conn->query($stats_sql);
        $stats = $stats_result ? $stats_result->fetch_assoc() : ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
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
                            if ($students_result) {
                                while ($student = $students_result->fetch_assoc()) {
                                    $selected = ($studentFilter == $student['studentID']) ? 'selected' : '';
                                    echo "<option value='{$student['studentID']}' $selected>{$student['studentName']}</option>";
                                }
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
                $sql = "SELECT mc.*, s.studentName, s.studentEmail, e.eventName, e.eventLevel 
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
                        $studentName = htmlspecialchars($row['studentName'] ?? '');
                        $studentEmail = htmlspecialchars($row['studentEmail'] ?? '');
                        $eventName = htmlspecialchars($row['eventName'] ?? 'General Merit Claim');
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
                                    <div class='detail-value'>{$studentName} </div>
                                </div>
                                <div class='detail-item'>
                                    <div class='detail-label'>Email</div>
                                    <div class='detail-value'>{$studentEmail}</div>
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
                            
                            <div class='claim-actions'>";
                        
                        if (!empty($supportDoc)) {
                            echo "<button class='btn btn-view' onclick='viewDocument(\"{$supportDoc}\", {$claimId})'>
                                    <i class='material-icons'>visibility</i>
                                    View Document
                                  </button>";
                        }
                        
                        // Action buttons only for pending claims
                        if ($status === 'Pending') {
                            echo "<form method='POST' style='display: inline;' onsubmit='return confirmAction(\"approve\")'>
                                    <input type='hidden' name='claim_id' value='{$claimId}'>
                                    <input type='hidden' name='action' value='approve'>
                                    <button type='submit' class='btn btn-approve'>
                                        <i class='material-icons'>check</i>
                                        Approve
                                    </button>
                                  </form>
                                  
                                  <form method='POST' style='display: inline;' onsubmit='return confirmAction(\"reject\")'>
                                    <input type='hidden' name='claim_id' value='{$claimId}'>
                                    <input type='hidden' name='action' value='reject'>
                                    <button type='submit' class='btn btn-reject'>
                                        <i class='material-icons'>close</i>
                                        Reject
                                    </button>
                                  </form>";
                        }
                        
                        echo "    </div>
                        </div>";
                    }
                }
                $stmt->close();
            } catch (Exception $e) {
                echo '<div class="alert alert-error">Error loading claims: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>
        </div>
    </div>

    <!-- Document View Modal -->
    <div id="documentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Supporting Document</h2>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="document-info">
                    <h4>Claim #<span id="modalClaimId"></span></h4>
                    <p>Review the supporting document for this merit claim.</p>
                </div>
                <div id="documentContainer">
                    <img id="documentImage" class="document-preview" style="display: none;" />
                    <div id="documentError" style="display: none; text-align: center; color: #dc3545;">
                        <i class="material-icons" style="font-size: 48px;">error</i>
                        <p>Unable to load document</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmAction(action) {
            const actionText = action === 'approve' ? 'approve' : 'reject';
            return confirm(`Are you sure you want to ${actionText} this merit claim?`);
        }

        function viewDocument(filename, claimId) {
            const modal = document.getElementById('documentModal');
            const modalClaimId = document.getElementById('modalClaimId');
            const documentImage = document.getElementById('documentImage');
            const documentError = document.getElementById('documentError');
            
            modalClaimId.textContent = claimId;
            
            documentImage.style.display = 'none';
            documentError.style.display = 'none';
            
            documentImage.onload = function() {
                documentImage.style.display = 'block';
            };
            
            documentImage.onerror = function() {
                documentError.style.display = 'block';
            };
            
            // Fix the path - use forward slash and correct relative path
            documentImage.src = 'uploads/merit_claims/' + filename;
            
            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('documentModal').style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('documentModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        
    </script>
</body>
</html>

<?php
$conn->close();
?>