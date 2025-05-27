<?php
session_start();

// Include database connection
include '../db_connect.php';


// Get the student ID from session
$username = $_SESSION['username'] ?? '';

// Then get the studentID from the database using the username
$studentID = '';
if (!empty($username)) {
    $student_query = "SELECT studentID FROM student WHERE username = ?";
    $stmt = $conn->prepare($student_query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $studentID = $row['studentID'];
    }
    $stmt->close();
}

// If no studentID found, redirect to login
if (empty($studentID)) {
    header("Location: ../module1/login.php");
    exit();
}

// Function to check if user can claim for an event
function canClaimEvent($conn, $studentID, $eventID) {
    // Check if user already has a merit award for this event
    $award_check = "SELECT ma_ID FROM meritaward WHERE studentID = ? AND eventID = ?";
    $award_stmt = $conn->prepare($award_check);
    $award_stmt->bind_param("ss", $studentID, $eventID);
    $award_stmt->execute();
    $award_result = $award_stmt->get_result();
    
    if ($award_result && $award_result->num_rows > 0) {
        $award_stmt->close();
        return array('can_claim' => false, 'reason' => 'You already have a merit award for this event.');
    }
    $award_stmt->close();
    
    // Check if user already has a pending or approved claim for this event
    $claim_check = "SELECT claim_id, status FROM merit_claims WHERE studentID = ? AND eventID = ? AND status IN ('Pending', 'Approved')";
    $claim_stmt = $conn->prepare($claim_check);
    $claim_stmt->bind_param("ss", $studentID, $eventID);
    $claim_stmt->execute();
    $claim_result = $claim_stmt->get_result();
    
    if ($claim_result && $claim_result->num_rows > 0) {
        $existing_claim = $claim_result->fetch_assoc();
        $claim_stmt->close();
        return array('can_claim' => false, 'reason' => 'You already have a ' . strtolower($existing_claim['status']) . ' claim for this event.');
    }
    $claim_stmt->close();
    
    return array('can_claim' => true, 'reason' => '');
}

// Function to get available events for claiming (now includes semester from event table)
function getAvailableEvents($conn, $studentID) {
    $available_events = [];
    
    // Get all events with their semesters
    $events_sql = "SELECT eventID, eventName, semester FROM event ORDER BY eventName";
    $events_result = $conn->query($events_sql);
    
    if ($events_result && $events_result->num_rows > 0) {
        while ($event = $events_result->fetch_assoc()) {
            $can_claim = canClaimEvent($conn, $studentID, $event['eventID']);
            if ($can_claim['can_claim']) {
                $available_events[] = $event;
            }
        }
    }
    
    return $available_events;
}

// Handle file upload
function uploadFile($file) {
    $target_dir = "uploads/merit_claims/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowed_extensions = array("jpg", "jpeg", "png", "gif", "pdf", "doc", "docx");
    
    // Check if file extension is allowed
    if (!in_array($file_extension, $allowed_extensions)) {
        return array('success' => false, 'message' => 'Only JPG, JPEG, PNG, GIF, PDF, DOC, DOCX files are allowed.');
    }
    
    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        return array('success' => false, 'message' => 'File is too large. Maximum size is 5MB.');
    }
    
    // Generate unique filename
    $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $unique_filename;
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return array('success' => true, 'filename' => $unique_filename, 'path' => $target_file);
    } else {
        return array('success' => false, 'message' => 'Error uploading file.');
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_claim':
                $eventID = $_POST['event_id'] ?? '';
                $supportDoc = '';
                
                // Check if user can claim for this event
                if (!empty($eventID)) {
                    $can_claim = canClaimEvent($conn, $studentID, $eventID);
                    if (!$can_claim['can_claim']) {
                        echo "<script>alert('Cannot submit claim: " . addslashes($can_claim['reason']) . "');</script>";
                        break;
                    }
                }
                
                // Handle file upload
                if (isset($_FILES['support_doc']) && $_FILES['support_doc']['error'] == 0) {
                    $upload_result = uploadFile($_FILES['support_doc']);
                    if ($upload_result['success']) {
                        $supportDoc = $upload_result['filename'];
                    } else {
                        echo "<script>alert('" . $upload_result['message'] . "');</script>";
                        break;
                    }
                }
                
                if (!empty($eventID)) {
                    // Removed semester from INSERT since it's now in event table
                    $insert_sql = "INSERT INTO merit_claims (studentID, eventID, support_doc, status, claim_date) 
                                   VALUES (?, ?, ?, 'Pending', NOW())";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param("sss", $studentID, $eventID, $supportDoc);
                    $insert_stmt->execute();
                    $insert_stmt->close();
                    echo "<script>alert('Merit claim submitted successfully!');</script>";
                }
                break;
                
            case 'update_claim':
                $claimID = $_POST['claim_id'] ?? '';
                $eventID = $_POST['event_id'] ?? '';
                $supportDoc = $_POST['current_support_doc'] ?? '';
                
                // Check if claim is approved
                $check_sql = "SELECT status, eventID FROM merit_claims WHERE claim_id = ? AND studentID = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("ss", $claimID, $studentID);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $claim_data = $check_result->fetch_assoc();
                $check_stmt->close();
                
                if ($claim_data['status'] == 'Approved') {
                    echo "<script>alert('Cannot update approved claims!');</script>";
                    break;
                }
                
                // If event is being changed, check if the new event can be claimed
                if (!empty($eventID) && $eventID != $claim_data['eventID']) {
                    $can_claim = canClaimEvent($conn, $studentID, $eventID);
                    if (!$can_claim['can_claim']) {
                        echo "<script>alert('Cannot update claim: " . addslashes($can_claim['reason']) . "');</script>";
                        break;
                    }
                }
                
                // Handle file upload
                if (isset($_FILES['support_doc']) && $_FILES['support_doc']['error'] == 0) {
                    $upload_result = uploadFile($_FILES['support_doc']);
                    if ($upload_result['success']) {
                        // Delete old file if exists
                        if (!empty($supportDoc) && file_exists("uploads/merit_claims/" . $supportDoc)) {
                            unlink("uploads/merit_claims/" . $supportDoc);
                        }
                        $supportDoc = $upload_result['filename'];
                    } else {
                        echo "<script>alert('" . $upload_result['message'] . "');</script>";
                        break;
                    }
                }
                
                if (!empty($claimID)) {
                    // Removed semester from UPDATE since it's now in event table
                    $update_sql = "UPDATE merit_claims SET eventID = ?, support_doc = ? WHERE claim_id = ? AND studentID = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("ssss", $eventID, $supportDoc, $claimID, $studentID);
                    $update_stmt->execute();
                    $update_stmt->close();
                    echo "<script>alert('Merit claim updated successfully!');</script>";
                }
                break;
                
            case 'delete_claim':
                $claimID = $_POST['claim_id'] ?? '';
                
                if (!empty($claimID)) {
                    // Check if claim is approved
                    $check_sql = "SELECT status, support_doc FROM merit_claims WHERE claim_id = ? AND studentID = ?";
                    $check_stmt = $conn->prepare($check_sql);
                    $check_stmt->bind_param("ss", $claimID, $studentID);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    $claim_data = $check_result->fetch_assoc();
                    $check_stmt->close();
                    
                    if ($claim_data['status'] == 'Approved') {
                        echo "<script>alert('Cannot delete approved claims!');</script>";
                        break;
                    }
                    
                    // Delete the file if exists
                    if (!empty($claim_data['support_doc']) && file_exists("uploads/merit_claims/" . $claim_data['support_doc'])) {
                        unlink("uploads/merit_claims/" . $claim_data['support_doc']);
                    }
                    
                    $delete_sql = "DELETE FROM merit_claims WHERE claim_id = ? AND studentID = ?";
                    $delete_stmt = $conn->prepare($delete_sql);
                    $delete_stmt->bind_param("ss", $claimID, $studentID);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                    echo "<script>alert('Merit claim deleted successfully!');</script>";
                }
                break;
        }
    }
}

// Get available events for the dropdown (only events that can be claimed)
$available_events = getAvailableEvents($conn, $studentID);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Missing Merit </title>
    <link rel="stylesheet" href="css/claimAward.css">
    <link rel="stylesheet" href="../sideBar/side.css">
</head>
<body>
    <?php include '../sideBar/Student_SideBar.php'; ?>
    <div class="main-content">
        <div class="claim-container">
            <div class="claim-header">
                Claim Missing Merit 
            </div>
            <div class="claim-content">
                <div class="add-claim-section">
                    <?php if (count($available_events) > 0): ?>
                        <button class="add-claim-btn" onclick="openAddModal()">
                             Add new merit claim
                        </button>
                    <?php else: ?>
                        <div class="no-available-events">
                            <span style="color: #666; font-style: italic;">No events available for claiming. You have either already claimed or received awards for all events.</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="merit-claim-list">
                    <div class="merit-section-title">Merit Claim List:</div>
                    
                    <?php
                    // Fetch merit claims for the student with event details (now including semester from event table)
                    try {
                        $claims_sql = "SELECT mc.*, e.eventName, e.semester 
                                      FROM merit_claims mc 
                                      LEFT JOIN event e ON mc.eventID = e.eventID 
                                      WHERE mc.studentID = ? 
                                      ORDER BY mc.claim_date DESC";
                        $claims_stmt = $conn->prepare($claims_sql);
                        $claims_stmt->bind_param("s", $studentID);
                        $claims_stmt->execute();
                        $claims_result = $claims_stmt->get_result();
                        
                        if ($claims_result && $claims_result->num_rows > 0) {
                            while ($claim = $claims_result->fetch_assoc()) {
                                $statusClass = 'status-' . strtolower($claim['status']);
                                $isApproved = $claim['status'] == 'Approved';
                                $disabledClass = $isApproved ? 'disabled' : '';
                                
                                echo "
                                <div class='claim-item'>
                                    <div class='claim-item-content'>
                                        <div class='claim-details'>
                                            <div class='details-label'>< Details ></div>
                                            <div class='claim-info'>
                                                <strong>Event:</strong> " . htmlspecialchars($claim['eventName'] ?? 'Event not found') . "<br>
                                                <strong>Semester:</strong> " . htmlspecialchars($claim['semester'] ?? 'Not specified') . "<br>
                                                <strong>Support Document:</strong> ";
                                                
                                if (!empty($claim['support_doc'])) {
                                    echo "<a href='uploads/merit_claims/" . htmlspecialchars($claim['support_doc']) . "' target='_blank' class='doc-link'>" . htmlspecialchars($claim['support_doc']) . "</a>";
                                } else {
                                    echo "None";
                                }
                                
                                echo "<br><strong>Claim Date:</strong> " . date('Y-m-d H:i', strtotime($claim['claim_date'])) . "
                                            </div>
                                        </div>
                                        <div class='claim-actions'>
                                            <div class='claim-buttons'>";
                                
                                if (!$isApproved) {
                                    echo "<button class='btn-update' onclick='openUpdateModal(" . json_encode($claim) . ")'>Update</button>
                                          <button class='btn-delete' onclick='deleteClaim(" . $claim['claim_id'] . ")'>Delete</button>";
                                } else {
                                    echo "<button class='btn-update disabled' disabled>Update</button>
                                          <button class='btn-delete disabled' disabled>Delete</button>";
                                }
                                
                                echo "</div>
                                            <div class='status-section'>
                                                <span class='status-label'>Claim Status:</span>
                                                <span class='claim-status $statusClass'>" . htmlspecialchars($claim['status']) . "</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>";
                            }
                        } else {
                            echo "
                            <div class='claim-item'>
                                <div class='no-claims'>
                                    No merit claims found. Click 'Add new merit claim' to create your first claim.
                                </div>
                            </div>";
                        }
                        
                        $claims_stmt->close();
                    } catch (Exception $e) {
                        echo "<div class='claim-item'><div class='no-claims'>Error loading claims: " . $e->getMessage() . "</div></div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Update Modal -->
    <div id="claimModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Merit Claim</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="claimForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add_claim">
                <input type="hidden" name="claim_id" id="claimId" value="">
                <input type="hidden" name="current_support_doc" id="currentSupportDoc" value="">
                
                <div class="form-group">
                    <label for="event_id">Event (Semester will be set automatically):</label>
                    <select name="event_id" id="event_id" required>
                        <option value="">Select Event</option>
                        <?php foreach ($available_events as $event): ?>
                            <option value="<?php echo htmlspecialchars($event['eventID']); ?>" 
                                    data-semester="<?php echo htmlspecialchars($event['semester'] ?? 'Not specified'); ?>">
                                <?php echo htmlspecialchars($event['eventName']); ?> 
                                (Semester <?php echo htmlspecialchars($event['semester'] ?? 'Not specified'); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (count($available_events) == 0): ?>
                        <small class="file-info" style="color: #dc3545;">No events available for claiming.</small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="support_doc">Support Document:</label>
                    <input type="file" name="support_doc" id="support_doc" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">
                    <small class="file-info">Allowed formats: JPG, JPEG, PNG, GIF, PDF, DOC, DOCX (Max: 5MB)</small>
                    <div id="currentFileInfo" style="display: none; margin-top: 10px;">
                        <strong>Current file:</strong> <span id="currentFileName"></span>
                        <br><small>Leave empty to keep current file, or select new file to replace.</small>
                    </div>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Submit Claim</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Merit Claim';
            document.getElementById('formAction').value = 'add_claim';
            document.getElementById('claimId').value = '';
            document.getElementById('currentSupportDoc').value = '';
            document.getElementById('claimForm').reset();
            document.getElementById('currentFileInfo').style.display = 'none';
            
            // Reset event dropdown to show available events
            populateEventDropdown('add');
            
            document.getElementById('claimModal').style.display = 'block';
        }
        
        function openUpdateModal(claim) {
            document.getElementById('modalTitle').textContent = 'Update Merit Claim';
            document.getElementById('formAction').value = 'update_claim';
            document.getElementById('claimId').value = claim.claim_id;
            document.getElementById('currentSupportDoc').value = claim.support_doc || '';
            
            // Populate event dropdown for update (include current event + available events)
            populateEventDropdown('update', claim.eventID, claim.eventName, claim.semester);
            
            document.getElementById('event_id').value = claim.eventID;
            
            // Show current file info if exists
            if (claim.support_doc) {
                document.getElementById('currentFileName').textContent = claim.support_doc;
                document.getElementById('currentFileInfo').style.display = 'block';
            } else {
                document.getElementById('currentFileInfo').style.display = 'none';
            }
            
            document.getElementById('claimModal').style.display = 'block';
        }
        
        function populateEventDropdown(mode, currentEventID = null, currentEventName = null, currentSemester = null) {
            const eventSelect = document.getElementById('event_id');
            eventSelect.innerHTML = '<option value="">Select Event</option>';
            
            // Available events from PHP
            const availableEvents = <?php echo json_encode($available_events); ?>;
            
            // If updating and there's a current event, add it first
            if (mode === 'update' && currentEventID && currentEventName) {
                const currentOption = document.createElement('option');
                currentOption.value = currentEventID;
                currentOption.textContent = currentEventName + ' (Semester ' + (currentSemester || 'Not specified') + ')';
                currentOption.setAttribute('data-semester', currentSemester || 'Not specified');
                eventSelect.appendChild(currentOption);
            }
            
            // Add available events
            availableEvents.forEach(event => {
                // Skip if it's the current event (already added above)
                if (mode === 'update' && event.eventID === currentEventID) {
                    return;
                }
                
                const option = document.createElement('option');
                option.value = event.eventID;
                option.textContent = event.eventName + ' (Semester ' + (event.semester || 'Not specified') + ')';
                option.setAttribute('data-semester', event.semester || 'Not specified');
                eventSelect.appendChild(option);
            });
        }
        
        function closeModal() {
            document.getElementById('claimModal').style.display = 'none';
        }
        
        function deleteClaim(claimId) {
            if (confirm('Are you sure you want to delete this merit claim? This will also delete any uploaded files.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_claim">
                    <input type="hidden" name="claim_id" value="${claimId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('claimModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>