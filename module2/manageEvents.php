<?php
session_start();
include '../db_connect.php';


// Simple authentication check
if (!isset($_SESSION['logged_in'])) {
    $_SESSION['logged_in'] = true; // For demo purposes
}

// Sample event data (in real application, this would come from database)
$events = [
    [
        'id' => 1,
        'name' => 'kkkk',
        'location' => 'K',
        'date' => '2025-05-24',
        'status' => 'pending',
        'geo' => 'sami',
        'description' => '',
        'approval_letter' => 'View'
    ]
];

// Sample student data for committee assignment
$students = [
    ['id' => 1, 'name' => 'aziz'],
    ['id' => 2, 'name' => 'Ahmad Ali'],
    ['id' => 3, 'name' => 'Siti Fatimah'],
    ['id' => 4, 'name' => 'Muhammad Hassan']
];

// Committee roles
$committee_roles = [
    'Leader',
    'Logistics',
    'Publicity',
    'Registration',
    'Technical Support'
];

// Event levels for merit application
$event_levels = [
    'International',
    'National',
    'State',
    'District'
];

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_event':
                // Handle file upload
                $approval_letter_path = '';
                if (isset($_FILES['approval_letter']) && $_FILES['approval_letter']['error'] == 0) {
                    $upload_dir = 'uploads/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_name = time() . '_' . $_FILES['approval_letter']['name'];
                    $upload_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['approval_letter']['tmp_name'], $upload_path)) {
                        $approval_letter_path = $upload_path;
                    }
                }
                
                // Add new event to array (in real application, save to database)
                $new_event = [
                    'id' => count($events) + 1,
                    'name' => $_POST['event_title'],
                    'location' => $_POST['location'],
                    'date' => $_POST['start_date_time'],
                    'status' => $_POST['status'],
                    'geo' => $_POST['geolocation'],
                    'description' => $_POST['description'],
                    'approval_letter' => $approval_letter_path ? $file_name : 'Not uploaded'
                ];
                
                $events[] = $new_event;
                $success_message = "Event created successfully!";
                break;
            
            case 'apply_merit':
                $success_message = "Merit application submitted for Event: " . $_POST['event_name'] . " at " . $_POST['event_level'] . " level!";
                break;
            
            case 'assign_committee':
                $success_message = "Committee assigned: " . $_POST['student_name'] . " as " . $_POST['committee_role'] . " for Event ID: " . $_POST['event_id'];
                break;
            
            case 'delete_event':
                $event_id = $_POST['event_id'];
                // In real application, delete from database
                foreach ($events as $key => $event) {
                    if ($event['id'] == $event_id) {
                        unset($events[$key]);
                        break;
                    }
                }
                $success_message = "Event deleted successfully!";
                break;
                
            case 'update_event':
                // Update event logic here
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyPetakom - Event Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }

        /* Header Styles */
        .header {
            background-color: #2c5aa0;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
            font-weight: bold;
        }

        .logout-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .logout-btn:hover {
            background-color: #c82333;
        }

        /* Main Container */
        .container {
            min-height: calc(100vh - 70px);
        }

        /* Content Area */
        .content {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .content h2 {
            font-size: 28px;
            color: #333;
        }

        .add-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .add-btn:hover {
            background-color: #218838;
        }

        /* Event Card Styles */
        .event-card {
            background-color: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .event-field {
            margin-bottom: 10px;
            font-size: 14px;
        }

        .event-field strong {
            font-weight: bold;
            color: #333;
        }

        .approval-link {
            color: #007bff;
            text-decoration: underline;
            cursor: pointer;
        }

        .approval-link:hover {
            color: #0056b3;
        }

        /* Action Buttons */
        .action-buttons {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-info {
            background-color: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background-color: #138496;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        /* Modal Styles */
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
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
        }

        .form-group textarea {
            height: 120px;
            resize: vertical;
        }

        .form-group input[type="file"] {
            padding: 8px;
            background-color: #f8f9fa;
        }

        .form-group input[type="datetime-local"] {
            padding: 10px;
        }

        .btn-submit {
            background-color: #1e4a72;
            color: white;
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .btn-submit:hover {
            background-color: #163a5f;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .content {
                padding: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>MyPetakom</h1>
        <button class="logout-btn" onclick="logout()">Logout</button>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Content Area -->
        <div class="content">
            <?php if (isset($success_message)): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <div class="content-header">
                <h2>Manage Events</h2>
                <button class="add-btn" onclick="openAddEventModal()">+ Add Event</button>
            </div>

            <!-- Event Cards -->
            <?php foreach ($events as $event): ?>
            <div class="event-card">
                <div class="event-field">
                    <strong>Event Name:</strong> <?php echo htmlspecialchars($event['name']); ?>
                </div>
                <div class="event-field">
                    <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?>
                </div>
                <div class="event-field">
                    <strong>Date:</strong> <?php echo htmlspecialchars($event['date']); ?>
                </div>
                <div class="event-field">
                    <strong>Status:</strong> <?php echo htmlspecialchars($event['status']); ?>
                </div>
                <div class="event-field">
                    <strong>Geo:</strong> <?php echo htmlspecialchars($event['geo']); ?>
                </div>
                <div class="event-field">
                    <strong>Description:</strong><br>
                    <?php echo htmlspecialchars($event['description'] ?: 'No description provided'); ?>
                </div>
                <div class="event-field">
                    <strong>Approval Letter:</strong> 
                    <span class="approval-link" onclick="viewApprovalLetter(<?php echo $event['id']; ?>)">
                        <?php echo htmlspecialchars($event['approval_letter']); ?>
                    </span>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="updateEvent(<?php echo $event['id']; ?>)">Update Event</button>
                    <button class="btn btn-danger" onclick="deleteEvent(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['name']); ?>')">Delete Event</button>
                    <button class="btn btn-info" onclick="assignCommittees(<?php echo $event['id']; ?>)">Assign Committees</button>
                    <button class="btn btn-secondary" onclick="generateQRCode(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['name']); ?>')">Generate QR Code</button>
                    <button class="btn btn-success" onclick="applyMerit(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['name']); ?>')">Apply Merit</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add Event Modal -->
    <div id="addEventModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddEventModal()">&times;</span>
            <h3>Create Event</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_event">
                
                <div class="form-group">
                    <label for="event_title">Event Title</label>
                    <input type="text" id="event_title" name="event_title" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="6"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" required>
                </div>
                
                <div class="form-group">
                    <label for="start_date_time">Start Date & Time</label>
                    <input type="datetime-local" id="start_date_time" name="start_date_time" required>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="">Select status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="approval_letter">Upload Approval Letter</label>
                    <input type="file" id="approval_letter" name="approval_letter" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                </div>
                
                <div class="form-group">
                    <label for="geolocation">Geolocation</label>
                    <input type="text" id="geolocation" name="geolocation" placeholder="Enter coordinates or address">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-submit">Submit Event</button>
                    <button type="button" class="btn btn-secondary" onclick="closeAddEventModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Apply Merit Modal -->
    <div id="applyMeritModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeApplyMeritModal()">&times;</span>
            <h3>Apply Merit for Event</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="apply_merit">
                <input type="hidden" id="merit_event_id" name="event_id">
                
                <div class="form-group">
                    <label for="event_name_merit">Event Name:</label>
                    <input type="text" id="event_name_merit" name="event_name" readonly>
                </div>
                
                <div class="form-group">
                    <label for="event_level">Event Level:</label>
                    <select id="event_level" name="event_level" required>
                        <option value="">-- Select Level --</option>
                        <?php foreach ($event_levels as $level): ?>
                            <option value="<?php echo $level; ?>"><?php echo $level; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-submit">Apply Merit</button>
                    <button type="button" class="btn btn-secondary" onclick="closeApplyMeritModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assign Committee Modal -->
    <div id="assignCommitteeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAssignCommitteeModal()">&times;</span>
            <h3 id="assignCommitteeTitle">Assign Committee for Event ID: </h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="assign_committee">
                <input type="hidden" id="committee_event_id" name="event_id">
                
                <div class="form-group">
                    <label for="select_student">Select Student:</label>
                    <select id="select_student" name="student_name" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo htmlspecialchars($student['name']); ?>">
                                <?php echo htmlspecialchars($student['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="select_role">Select Role:</label>
                    <select id="select_role" name="committee_role" required>
                        <option value="">-- Select Role --</option>
                        <?php foreach ($committee_roles as $role): ?>
                            <option value="<?php echo $role; ?>"><?php echo $role; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-submit">Assign Committee</button>
                    <button type="button" class="btn btn-secondary" onclick="closeAssignCommitteeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Event Modal -->
    <div id="deleteEventModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteEventModal()">&times;</span>
            <h3>Delete Event</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete_event">
                <input type="hidden" id="delete_event_id" name="event_id">
                
                <div class="form-group">
                    <p>Are you sure you want to delete this event?</p>
                    <p><strong>Event Name:</strong> <span id="delete_event_name"></span></p>
                    <p style="color: #dc3545;"><strong>This action cannot be undone!</strong></p>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-danger">Yes, Delete Event</button>
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteEventModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div id="qrCodeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeQRCodeModal()">&times;</span>
            <h3>QR Code for Event</h3>
            <div id="qrCodeContent">
                <p><strong>Event:</strong> <span id="qr_event_name"></span></p>
                <div id="qrCodeDisplay" style="text-align: center; margin: 20px 0;">
                    <!-- QR Code will be generated here -->
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-primary" onclick="downloadQRCode()">Download QR Code</button>
                    <button type="button" class="btn btn-secondary" onclick="closeQRCodeModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modal Functions
        function openAddEventModal() {
            document.getElementById('addEventModal').style.display = 'block';
        }

        function closeAddEventModal() {
            document.getElementById('addEventModal').style.display = 'none';
        }

        // Event Action Functions
        function updateEvent(eventId) {
            alert('Update event functionality - Event ID: ' + eventId);
            // Implement update event logic
        }

        function deleteEvent(eventId, eventName) {
            document.getElementById('delete_event_id').value = eventId;
            document.getElementById('delete_event_name').textContent = eventName;
            document.getElementById('deleteEventModal').style.display = 'block';
        }

        function assignCommittees(eventId) {
            document.getElementById('committee_event_id').value = eventId;
            document.getElementById('assignCommitteeTitle').textContent = 'Assign Committee for Event ID: ' + eventId;
            document.getElementById('assignCommitteeModal').style.display = 'block';
        }

        function generateQRCode(eventId, eventName) {
            document.getElementById('qr_event_name').textContent = eventName;
            
            // Generate QR Code using a simple text-based representation
            // In a real application, you would use a QR code library like QRCode.js
            const qrContent = `Event: ${eventName}\nEvent ID: ${eventId}\nDate: ${new Date().toLocaleDateString()}`;
            document.getElementById('qrCodeDisplay').innerHTML = `
                <div style="border: 2px solid #000; padding: 20px; display: inline-block; font-family: monospace; background: #fff;">
                    <div style="font-size: 12px; text-align: left;">
                        ${qrContent.replace(/\n/g, '<br>')}
                    </div>
                    <div style="margin-top: 10px; font-size: 10px; text-align: center;">
                        QR Code Placeholder<br>
                        (Integrate QR library for actual QR code)
                    </div>
                </div>
            `;
            
            document.getElementById('qrCodeModal').style.display = 'block';
        }

        function applyMerit(eventId, eventName) {
            document.getElementById('merit_event_id').value = eventId;
            document.getElementById('event_name_merit').value = eventName;
            document.getElementById('applyMeritModal').style.display = 'block';
        }

        function viewApprovalLetter(eventId) {
            alert('View approval letter functionality - Event ID: ' + eventId);
            // Implement approval letter viewing logic
        }

        // Modal control functions
        function closeApplyMeritModal() {
            document.getElementById('applyMeritModal').style.display = 'none';
        }

        function closeAssignCommitteeModal() {
            document.getElementById('assignCommitteeModal').style.display = 'none';
        }

        function closeDeleteEventModal() {
            document.getElementById('deleteEventModal').style.display = 'none';
        }

        function closeQRCodeModal() {
            document.getElementById('qrCodeModal').style.display = 'none';
        }

        function downloadQRCode() {
            alert('QR Code download functionality - In real application, this would generate and download a QR code image');
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            var modals = ['addEventModal', 'applyMeritModal', 'assignCommitteeModal', 'deleteEventModal', 'qrCodeModal'];
            modals.forEach(function(modalId) {
                var modal = document.getElementById(modalId);
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>