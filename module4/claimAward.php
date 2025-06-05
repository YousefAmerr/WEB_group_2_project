<?php
session_start();
include '../db_connect.php';

// Get student ID from session
$username = $_SESSION['username'] ?? '';
$studentID = '';
if ($username) {
    $stmt = $conn->prepare("SELECT studentID FROM student WHERE StuUsername = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $studentID = $result->fetch_assoc()['studentID'];
    }
    $stmt->close();
}

if (!$studentID) {
    header("Location: ../module1/login.php");
    exit();
}

// Check if user can claim event
function canClaimEvent($conn, $studentID, $eventID) {
    $check_sql = "SELECT 1 FROM meritaward WHERE studentID = ? AND eventID = ? 
                  UNION 
                  SELECT 1 FROM meritclaim WHERE studentID = ? AND eventID = ? AND status IN ('Pending', 'Approved')";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ssss", $studentID, $eventID, $studentID, $eventID);
    $stmt->execute();
    return $stmt->get_result()->num_rows == 0;
}

// Get events for dropdown
function getEvents($conn, $studentID, $available_only = true) {
    $events = [];
    $result = $conn->query("SELECT eventID, eventName, eventLocation, eventLevel, semester FROM event ORDER BY eventName");
    
    while ($event = $result->fetch_assoc()) {
        if (!$available_only || canClaimEvent($conn, $studentID, $event['eventID'])) {
            $events[] = $event;
        }
    }
    return $events;
}

// File upload handler
function uploadFile($file) {
    $target_dir = "uploads/meritclaim/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    
    $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    if (!in_array($ext, ["jpg", "jpeg", "png", "gif", "pdf", "doc", "docx"])) {
        return ['success' => false, 'message' => 'Invalid file type.'];
    }
    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'File too large (max 5MB).'];
    }
    
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $target_file = $target_dir . $filename;
    
    return move_uploaded_file($file["tmp_name"], $target_file) 
        ? ['success' => true, 'filename' => $filename] 
        : ['success' => false, 'message' => 'Upload failed.'];
}

// Handle form submissions
if ($_POST['action'] ?? false) {
    $action = $_POST['action'];
    $eventID = $_POST['event_id'] ?? '';
    $claimID = $_POST['claim_id'] ?? '';
    
    if ($action == 'add_claim') {
        if (!$eventID || !canClaimEvent($conn, $studentID, $eventID)) {
            echo "<script>alert('Cannot submit claim.');</script>";
        } else {
            $supportDoc = '';
            if ($_FILES['supporingDoc']['error'] == 0) {
                $upload = uploadFile($_FILES['supporingDoc']);
                if (!$upload['success']) {
                    echo "<script>alert('" . addslashes($upload['message']) . "');</script>";
                    goto end_processing;
                }
                $supportDoc = $upload['filename'];
            }
            
            $stmt = $conn->prepare("INSERT INTO meritclaim (studentID, eventID, supporingDoc, status, claim_date) VALUES (?, ?, ?, 'Pending', NOW())");
            $stmt->bind_param("sss", $studentID, $eventID, $supportDoc);
            echo $stmt->execute() ? "<script>alert('Claim submitted successfully!');</script>" : "<script>alert('Error submitting claim.');</script>";
            $stmt->close();
        }
    }
    
    if ($action == 'update_claim') {
        $stmt = $conn->prepare("SELECT status, supporingDoc FROM meritclaim WHERE claim_id = ? AND studentID = ?");
        $stmt->bind_param("is", $claimID, $studentID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            echo "<script>alert('Claim not found.');</script>";
        } else {
            $claim = $result->fetch_assoc();
            if ($claim['status'] == 'Approved') {
                echo "<script>alert('Cannot update approved claims!');</script>";
            } else {
                $supportDoc = $_POST['current_support_doc'] ?? '';
                
                if ($_FILES['supporingDoc']['error'] == 0) {
                    $upload = uploadFile($_FILES['supporingDoc']);
                    if ($upload['success']) {
                        if ($supportDoc && file_exists("uploads/meritclaim/" . $supportDoc)) {
                            unlink("uploads/meritclaim/" . $supportDoc);
                        }
                        $supportDoc = $upload['filename'];
                    }
                }

                $update_stmt = $conn->prepare("UPDATE meritclaim SET eventID = ?, supporingDoc = ? WHERE claim_id = ? AND studentID = ?");
                $update_stmt->bind_param("ssis", $eventID, $supportDoc, $claimID, $studentID);
                echo $update_stmt->execute() ? "<script>alert('Claim updated successfully!');</script>" : "<script>alert('Error updating claim.');</script>";
                $update_stmt->close();
            }
        }
        $stmt->close();
    }
    
    if ($action == 'delete_claim') {
        $stmt = $conn->prepare("SELECT status, supporingDoc FROM meritclaim WHERE claim_id = ? AND studentID = ?");
        $stmt->bind_param("is", $claimID, $studentID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $claim = $result->fetch_assoc();
            if ($claim['status'] != 'Approved') {
                if ($claim['supporingDoc'] && file_exists("uploads/meritclaim/" . $claim['supporingDoc'])) {
                    unlink("uploads/meritclaim/" . $claim['supporingDoc']);
                }

                $delete_stmt = $conn->prepare("DELETE FROM meritclaim WHERE claim_id = ? AND studentID = ?");
                $delete_stmt->bind_param("is", $claimID, $studentID);
                echo $delete_stmt->execute() ? "<script>alert('Claim deleted successfully!');</script>" : "<script>alert('Error deleting claim.');</script>";
                $delete_stmt->close();
            } else {
                echo "<script>alert('Cannot delete approved claims!');</script>";
            }
        }
        $stmt->close();
    }
}

end_processing:
$available_events = getEvents($conn, $studentID, true);
$all_events = getEvents($conn, $studentID, false);



?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Missing Merit</title>
    <link rel="stylesheet" href="css/claimAward.css">
    <link rel="stylesheet" href="../sideBar/side.css">
</head>
<body>
    <?php include '../sideBar/Student_SideBar.php'; ?>
    <div class="main-content">
        <div class="claim-container">
            <div class="claim-header">Claim Missing Merit</div>
            <div class="claim-content">
                <div class="add-claim-section">
                    <?php if (count($available_events) > 0): ?>
                        <button class="add-claim-btn" onclick="openAddModal()">Add new merit claim</button>
                    <?php else: ?>
                        <div class="no-available-events">
                            <span style="color: #666; font-style: italic;">No events available for claiming. You have either already claimed or received awards for all events.</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="merit-claim-list">
                    <div class="merit-section-title">Merit Claim List:</div>
                    
                    <?php
                    $claims_stmt = $conn->prepare("SELECT mc.*, e.eventName, e.eventLocation, e.eventLevel, e.semester FROM meritclaim mc LEFT JOIN event e ON mc.eventID = e.eventID WHERE mc.studentID = ? ORDER BY mc.claim_date DESC");
                    $claims_stmt->bind_param("s", $studentID);
                    $claims_stmt->execute();
                    $claims_result = $claims_stmt->get_result();
                    
                    if ($claims_result->num_rows > 0) {
                        while ($claim = $claims_result->fetch_assoc()) {
                            $statusClass = 'status-' . strtolower($claim['status']);
                            $isApproved = $claim['status'] == 'Approved';
                            $updateBtn = $isApproved ? "disabled" : "onclick='openUpdateModal(" . htmlspecialchars(json_encode($claim)) . ")'";
                            $deleteBtn = $isApproved ? "disabled" : "onclick='deleteClaim(" . $claim['claim_id'] . ")'";
                            $docLink = $claim['supporingDoc'] ? "<a href='uploads/meritclaim/" . htmlspecialchars($claim['supporingDoc']) . "' target='_blank' class='doc-link'>" . htmlspecialchars($claim['supporingDoc']) . "</a>" : "None";
                            
                            echo "
                            <div class='claim-item'>
                                <div class='claim-item-content'>
                                    <div class='claim-details'>
                                        <div class='details-label'>< Details ></div>
                                        <div class='claim-info'>
                                            <strong>Event:</strong> " . htmlspecialchars($claim['eventName'] ?? 'Event not found') . "<br>
                                            <strong>Location:</strong> " . htmlspecialchars($claim['eventLocation'] ?? 'Not specified') . "<br>
                                            <strong>Level:</strong> " . htmlspecialchars($claim['eventLevel'] ?? 'Not specified') . "<br>
                                            <strong>Semester:</strong> " . htmlspecialchars($claim['semester'] ?? 'Not specified') . "<br>
                                            <strong>Support Document:</strong> $docLink<br>
                                            <strong>Claim Date:</strong> " . date('Y-m-d H:i', strtotime($claim['claim_date'])) . "
                                        </div>
                                    </div>
                                    <div class='claim-actions'>
                                        <div class='claim-buttons'>
                                            <button class='btn-update" . ($isApproved ? ' disabled' : '') . "' $updateBtn " . ($isApproved ? 'disabled' : '') . ">Update</button>
                                            <button class='btn-delete" . ($isApproved ? ' disabled' : '') . "' $deleteBtn " . ($isApproved ? 'disabled' : '') . ">Delete</button>
                                        </div>
                                        <div class='status-section'>
                                            <span class='status-label'>Claim Status:</span>
                                            <span class='claim-status $statusClass'>" . htmlspecialchars($claim['status']) . "</span>
                                        </div>
                                    </div>
                                </div>
                            </div>";
                        }
                    } else {
                        echo "<div class='claim-item'><div class='no-claims'>No merit claims found. Click 'Add new merit claim' to create your first claim.</div></div>";
                    }
                    $claims_stmt->close();
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="claimModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Merit Claim</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="claimForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add_claim">
                <input type="hidden" name="claim_id" id="claimId">
                <input type="hidden" name="current_support_doc" id="currentSupportDoc">
                
                <div class="form-group">
                    <label for="event_id">Event:</label>
                    <select name="event_id" id="event_id" required>
                        <option value="">Select Event</option>
                    </select>
                    <div id="eventDetails" style="margin-top: 10px; padding: 10px; background-color: #f5f5f5; border-radius: 4px; display: none;">
                        <strong>Event Details:</strong><br>
                        <span id="eventLocation"></span><br>
                        <span id="eventLevel"></span><br>
                        <span id="eventSemester"></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="supporingDoc">Support Document:</label>
                    <input type="file" name="supporingDoc" id="supporingDoc" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">
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
        const availableEvents = <?php echo json_encode($available_events); ?>;
        const allEvents = <?php echo json_encode($all_events); ?>;
        
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Merit Claim';
            document.getElementById('formAction').value = 'add_claim';
            document.getElementById('claimForm').reset();
            document.getElementById('currentFileInfo').style.display = 'none';
            document.getElementById('eventDetails').style.display = 'none';
            populateEvents(availableEvents);
            document.getElementById('claimModal').style.display = 'block';
        }
        
        function openUpdateModal(claim) {
            document.getElementById('modalTitle').textContent = 'Update Merit Claim';
            document.getElementById('formAction').value = 'update_claim';
            document.getElementById('claimId').value = claim.claim_id;
            document.getElementById('currentSupportDoc').value = claim.support_doc || '';
            populateEvents(allEvents, claim.eventID);
            
            if (claim.support_doc) {
                document.getElementById('currentFileName').textContent = claim.support_doc;
                document.getElementById('currentFileInfo').style.display = 'block';
            }
            
            document.getElementById('claimModal').style.display = 'block';
        }
        
        function populateEvents(events, selectedID = null) {
            const select = document.getElementById('event_id');
            select.innerHTML = '<option value="">Select Event</option>';
            
            events.forEach(event => {
                const option = new Option(
                    `${event.eventName} (Semester ${event.semester || 'Not specified'})`,
                    event.eventID
                );
                option.dataset.location = event.eventLocation || 'Not specified';
                option.dataset.level = event.eventLevel || 'Not specified';
                option.dataset.semester = event.semester || 'Not specified';
                if (event.eventID == selectedID) option.selected = true;
                select.add(option);
            });
            
            if (selectedID) showEventDetails(selectedID);
        }
        
        function showEventDetails(eventID) {
            const option = document.querySelector(`option[value="${eventID}"]`);
            if (option) {
                document.getElementById('eventLocation').textContent = 'Location: ' + option.dataset.location;
                document.getElementById('eventLevel').textContent = 'Level: ' + option.dataset.level;
                document.getElementById('eventSemester').textContent = 'Semester: ' + option.dataset.semester;
                document.getElementById('eventDetails').style.display = 'block';
            } else {
                document.getElementById('eventDetails').style.display = 'none';
            }
        }
        
        document.getElementById('event_id').addEventListener('change', function() {
            this.value ? showEventDetails(this.value) : document.getElementById('eventDetails').style.display = 'none';
        });
        
        function closeModal() {
            document.getElementById('claimModal').style.display = 'none';
        }
        
        function deleteClaim(claimId) {
            if (confirm('Are you sure you want to delete this merit claim? This will also delete any uploaded files.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="delete_claim"><input type="hidden" name="claim_id" value="${claimId}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        window.onclick = function(event) {
            if (event.target == document.getElementById('claimModal')) closeModal();
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>