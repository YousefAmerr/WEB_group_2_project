<?php
session_start();

// Include database connection
include '../db_connect.php';

// Check if advisor is logged in
$username = $_SESSION['username'] ?? '';
if (empty($username)) {
    echo '<p>Access denied. Please log in.</p>';
    exit();
}

// Check if claim_id is provided
if (!isset($_GET['claim_id']) || empty($_GET['claim_id'])) {
    echo '<p>Invalid claim ID.</p>';
    exit();
}

$claim_id = $_GET['claim_id'];

try {
    // Fetch detailed claim information
    $sql = "SELECT mc.*, s.studentName, s.studentEmail, s.studentCard, s.studentID,
                   e.eventName, e.eventLevel, e.eventLocation, e.semester
            FROM merit_claims mc
            JOIN student s ON mc.studentID = s.studentID
            LEFT JOIN event e ON mc.eventID = e.eventID
            WHERE mc.claim_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $claim_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo '<p>Claim not found.</p>';
        exit();
    }
    
    $claim = $result->fetch_assoc();
    $stmt->close();
    
    // Format the claim date
    $claimDate = date('F j, Y \a\t g:i A', strtotime($claim['claim_date']));
    
    ?>
    
    <div class="student-info">
        <h3 style="margin-top: 0; color: #2c3e50; margin-bottom: 16px;">
            <i class="material-icons" style="vertical-align: middle; margin-right: 8px;">person</i>
            Student Information
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
            <div>
                <strong>Name:</strong><br>
                <?php echo htmlspecialchars($claim['studentName']); ?>
            </div>
            <div>
                <strong>Student ID:</strong><br>
                <?php echo htmlspecialchars($claim['studentCard']); ?>
            </div>
            <div>
                <strong>Email:</strong><br>
                <?php echo htmlspecialchars($claim['studentEmail']); ?>
            </div>
        </div>
    </div>
    
    <div style="background-color: #f8f9fa; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
        <h3 style="margin-top: 0; color: #2c3e50; margin-bottom: 16px;">
            <i class="material-icons" style="vertical-align: middle; margin-right: 8px;">event</i>
            Event Information
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
            <div>
                <strong>Event Name:</strong><br>
                <?php echo htmlspecialchars($claim['eventName'] ?? 'Event Not Found'); ?>
            </div>
            <div>
                <strong>Event Level:</strong><br>
                <?php echo htmlspecialchars($claim['eventLevel'] ?? 'N/A'); ?>
            </div>
            <div>
                <strong>Location:</strong><br>
                <?php echo htmlspecialchars($claim['eventLocation'] ?? 'N/A'); ?>
            </div>
            <div>
                <strong>Semester:</strong><br>
                <?php echo htmlspecialchars($claim['semester'] ?? 'N/A'); ?>
            </div>
        </div>
    </div>
    
    <div style="background-color: #f8f9fa; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
        <h3 style="margin-top: 0; color: #2c3e50; margin-bottom: 16px;">
            <i class="material-icons" style="vertical-align: middle; margin-right: 8px;">assignment</i>
            Claim Information
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
            <div>
                <strong>Claim ID:</strong><br>
                #<?php echo htmlspecialchars($claim['claim_id']); ?>
            </div>
            <div>
                <strong>Status:</strong><br>
                <span class="status-badge status-<?php echo strtolower($claim['status']); ?>" style="display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                    <?php echo htmlspecialchars($claim['status']); ?>
                </span>
            </div>
            <div>
                <strong>Submission Date:</strong><br>
                <?php echo $claimDate; ?>
            </div>
        </div>
    </div>
    
    <?php if (!empty($claim['support_doc'])): ?>
    <div class="support-doc">
        <h3 style="color: #2c3e50; margin-bottom: 16px;">
            <i class="material-icons" style="vertical-align: middle; margin-right: 8px;">attachment</i>
            Supporting Document
        </h3>
        <div style="text-align: center; padding: 20px; background-color: #f8f9fa; border-radius: 8px;">
            <?php
            $supportDocPath = '../uploads/merit_claims/' . $claim['support_doc'];
            $fileExtension = strtolower(pathinfo($claim['support_doc'], PATHINFO_EXTENSION));
            
            // Check if it's an image file
            if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                if (file_exists($supportDocPath)) {
                    echo '<img src="' . htmlspecialchars($supportDocPath) . '" alt="Supporting Document" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
                } else {
                    echo '<p style="color: #dc3545; margin: 0;"><i class="material-icons" style="vertical-align: middle;">error</i> Image file not found</p>';
                }
            } else {
                // For non-image files, show download link
                echo '<div style="padding: 20px; border: 2px dashed #dee2e6; border-radius: 8px;">';
                echo '<i class="material-icons" style="font-size: 48px; color: #6c757d; margin-bottom: 12px;">insert_drive_file</i><br>';
                echo '<strong>Document:</strong> ' . htmlspecialchars($claim['support_doc']) . '<br>';
                if (file_exists($supportDocPath)) {
                    echo '<a href="' . htmlspecialchars($supportDocPath) . '" target="_blank" style="color: #007bff; text-decoration: none; font-weight: 500;">
                            <i class="material-icons" style="vertical-align: middle; font-size: 16px;">download</i> 
                            View/Download Document
                          </a>';
                } else {
                    echo '<p style="color: #dc3545; margin: 8px 0 0 0;">File not found</p>';
                }
                echo '</div>';
            }
            ?>
        </div>
    </div>
    <?php else: ?>
    <div class="support-doc">
        <h3 style="color: #2c3e50; margin-bottom: 16px;">
            <i class="material-icons" style="vertical-align: middle; margin-right: 8px;">attachment</i>
            Supporting Document
        </h3>
        <div style="text-align: center; padding: 30px; background-color: #f8f9fa; border-radius: 8px; color: #6c757d;">
            <i class="material-icons" style="font-size: 48px; margin-bottom: 12px;">description</i><br>
            No supporting document uploaded
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($claim['status'] === 'Pending'): ?>
    <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e9ecef;">
        <h3 style="color: #2c3e50; margin-bottom: 16px;">Actions</h3>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <form method="POST" action="advisor_student_claim_merit.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to approve this claim?')">
                <input type="hidden" name="claim_id" value="<?php echo $claim['claim_id']; ?>">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-approve" style="padding: 10px 20px; background-color: #28a745; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="material-icons" style="font-size: 16px;">check</i>
                    Approve Claim
                </button>
            </form>
            <form method="POST" action="advisor_student_claim_merit.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to reject this claim?')">
                <input type="hidden" name="claim_id" value="<?php echo $claim['claim_id']; ?>">
                <input type="hidden" name="action" value="reject">
                <button type="submit" class="btn btn-reject" style="padding: 10px 20px; background-color: #dc3545; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="material-icons" style="font-size: 16px;">close</i>
                    Reject Claim
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <style>
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
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
    </style>
    
    <?php
    
} catch (Exception $e) {
    echo '<p style="color: #dc3545;">Error loading claim details: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

$conn->close();
?>