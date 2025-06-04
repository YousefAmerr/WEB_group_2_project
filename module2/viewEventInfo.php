<?php
session_start();

include '../db_connect.php';

// Simple authentication check for students
if (!isset($_SESSION['student_logged_in'])) {
    $_SESSION['student_logged_in'] = true; // For demo purposes
    $_SESSION['student_id'] = 22007; // Sample student ID
    $_SESSION['student_name'] = 'Ali bin Abu'; // Sample student name
}

// Sample event data (in real application, this would come from database)
$all_events = [
    [
        'id' => 25_001,
        'name' => 'The COMBAT 2025',
        'location' => 'UMPSA Pekan',
        'date' => '2025-05-09',
        'time' => '09:00',
        'status' => 'Completed',
        'geo' => '26600 Pekan Pahang, Malaysia',
        'description' => 'Annual programming competition for computer science students. This event brings together the best minds in programming to compete in various challenges.',
        'approval_letter' => 'combat_2025_approval.pdf',
        'organizer' => 'Computer Science Department',
        'level' => 'National',
        'participants_limit' => 100,
        'registration_deadline' => '2025-04-30'
    ],
    [
        'id' => 25_002,
        'name' => 'Engineering Expo 2025',
        'location' => 'UMPSA Gambang',
        'date' => '2025-06-15',
        'time' => '10:00',
        'status' => 'Approved',
        'geo' => '26300 Gambang, Pahang, Malaysia',
        'description' => 'Annual engineering exhibition showcasing innovative projects and research from engineering students and faculty.',
        'approval_letter' => 'eng_expo_2025_approval.pdf',
        'organizer' => 'Engineering Faculty',
        'level' => 'UMPSA',
        'participants_limit' => 200,
        'registration_deadline' => '2025-06-01'
    ],
    [
        'id' => 25_003,
        'name' => 'Tech Talk Series',
        'location' => 'Online Platform',
        'date' => '2025-07-20',
        'time' => '14:00',
        'status' => 'Pending',
        'geo' => 'Virtual Event',
        'description' => 'Monthly tech talk series featuring industry experts sharing insights on latest technology trends.',
        'approval_letter' => 'tech_talk_approval.pdf',
        'organizer' => 'IT Club',
        'level' => 'International',
        'participants_limit' => 500,
        'registration_deadline' => '2025-07-15'
    ],
    [
        'id' => 25_004,
        'name' => 'Sports Day 2025',
        'location' => 'UMPSA Sports Complex',
        'date' => '2025-08-10',
        'time' => '08:00',
        'status' => 'Approved',
        'geo' => '26600 Pekan Pahang, Malaysia',
        'description' => 'Annual sports competition featuring various athletic events for all students.',
        'approval_letter' => 'sports_day_approval.pdf',
        'organizer' => 'Sports Department',
        'level' => 'UMPSA',
        'participants_limit' => 300,
        'registration_deadline' => '2025-08-01'
    ]
];

// Filter events based on search criteria
$events = $all_events;
$search_performed = false;

if ($_GET) {
    $search_performed = true;
    $filtered_events = [];
    
    foreach ($all_events as $event) {
        $match = true;
        
        // Filter by event name
        if (!empty($_GET['search_name'])) {
            if (stripos($event['name'], $_GET['search_name']) === false) {
                $match = false;
            }
        }
        
        // Filter by status
        if (!empty($_GET['status']) && $_GET['status'] !== 'all') {
            if (strtolower($event['status']) !== strtolower($_GET['status'])) {
                $match = false;
            }
        }
        
        // Filter by location
        if (!empty($_GET['location'])) {
            if (stripos($event['location'], $_GET['location']) === false) {
                $match = false;
            }
        }
        
        // Filter by date range
        if (!empty($_GET['date_from'])) {
            if ($event['date'] < $_GET['date_from']) {
                $match = false;
            }
        }
        
        if (!empty($_GET['date_to'])) {
            if ($event['date'] > $_GET['date_to']) {
                $match = false;
            }
        }
        
        if ($match) {
            $filtered_events[] = $event;
        }
    }
    
    $events = $filtered_events;
}

// Function to get status class
function getStatusClass($status) {
    switch (strtolower($status)) {
        case 'completed':
            return 'status-completed';
        case 'pending':
            return 'status-pending';
        case 'approved':
            return 'status-approved';
        case 'rejected':
            return 'status-rejected';
        default:
            return 'status-pending';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyPetakom - View Events</title>
    <link rel="stylesheet" href="viewEventInfo.css">
</head>
<body>
    <?php include '../sideBar/Student_SideBar.php'; ?>

    <!-- Main Container -->
    <div class="container">
        <div class="content">
            <div class="content-header">
                <h2>Available Events</h2>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search_name">Event Name:</label>
                            <input type="text" id="search_name" name="search_name" 
                                   value="<?php echo htmlspecialchars($_GET['search_name'] ?? ''); ?>" 
                                   placeholder="Search by event name">
                        </div>
                        
                        <div class="filter-group">
                            <label for="status">Status:</label>
                            <select id="status" name="status">
                                <option value="all" <?php echo ($_GET['status'] ?? '') === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo ($_GET['status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="completed" <?php echo ($_GET['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="rejected" <?php echo ($_GET['status'] ?? '') === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="location">Location:</label>
                            <input type="text" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($_GET['location'] ?? ''); ?>" 
                                   placeholder="Search by location">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_from">From Date:</label>
                            <input type="date" id="date_from" name="date_from" 
                                   value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_to">To Date:</label>
                            <input type="date" id="date_to" name="date_to" 
                                   value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="filter-btn">Search Events</button>
                            <button type="button" class="reset-btn" onclick="resetFilters()">Reset</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Event Count -->
            <div class="event-count">
                <?php if ($search_performed): ?>
                    Found <?php echo count($events); ?> event(s) matching your criteria
                <?php else: ?>
                    Showing all <?php echo count($events); ?> available event(s)
                <?php endif; ?>
            </div>

            <!-- Events Display -->
            <?php if (empty($events)): ?>
                <div class="no-events">
                    <h3>No Events Found</h3>
                    <p>No events match your search criteria. Please try adjusting your filters.</p>
                </div>
            <?php else: ?>
                <div class="events-grid">
                    <?php foreach ($events as $event): ?>
                    <div class="event-card">
                        <div class="event-header">
                            <div class="event-title"><?php echo htmlspecialchars($event['name']); ?></div>
                            <span class="event-status <?php echo getStatusClass($event['status']); ?>">
                                <?php echo htmlspecialchars($event['status']); ?>
                            </span>
                        </div>
                        
                        <div class="event-field">
                            <strong>📍 Location:</strong>
                            <span class="value"><?php echo htmlspecialchars($event['location']); ?></span>
                        </div>
                        
                        <div class="event-field">
                            <strong>📅 Date:</strong>
                            <span class="value"><?php echo date('d M Y', strtotime($event['date'])); ?></span>
                        </div>
                        
                        <div class="event-field">
                            <strong>🕒 Time:</strong>
                            <span class="value"><?php echo htmlspecialchars($event['time']); ?></span>
                        </div>
                        
                        <div class="event-field">
                            <strong>👥 Organizer:</strong>
                            <span class="value"><?php echo htmlspecialchars($event['organizer']); ?></span>
                        </div>
                        
                        <div class="event-field">
                            <strong>🏆 Level:</strong>
                            <span class="value"><?php echo htmlspecialchars($event['level']); ?></span>
                        </div>
                        
                        <div class="event-field">
                            <strong>📋 Limit:</strong>
                            <span class="value"><?php echo htmlspecialchars($event['participants_limit']); ?> participants</span>
                        </div>
                        
                        <div class="event-field">
                            <strong>⏰ Deadline:</strong>
                            <span class="value"><?php echo date('d M Y', strtotime($event['registration_deadline'])); ?></span>
                        </div>
                        
                        <?php if (!empty($event['description'])): ?>
                        <div class="event-description">
                            <?php echo htmlspecialchars($event['description']); ?>
                        </div>
                        <?php endif; ?>

                        <div class="student-actions">
                            <button class="btn btn-info" onclick="viewEventDetails(<?php echo $event['id']; ?>)">
                                View Details
                            </button>
                            <?php if ($event['status'] === 'Approved' && strtotime($event['registration_deadline']) > time()): ?>
                            <button class="btn btn-success" onclick="registerForEvent(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['name']); ?>')">
                                Register
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-primary" onclick="viewLocation('<?php echo htmlspecialchars($event['geo']); ?>')">
                                View Location
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div id="eventDetailsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEventDetailsModal()">&times;</span>
            <div id="eventDetailsContent" class="event-details">
                <!-- Event details will be populated here -->
            </div>
        </div>
    </div>

    <!-- Registration Confirmation Modal -->
    <div id="registrationModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeRegistrationModal()">&times;</span>
            <h3>Event Registration</h3>
            <div id="registrationContent">
                <p>Are you sure you want to register for this event?</p>
                <p><strong>Event:</strong> <span id="reg_event_name"></span></p>
                <p><strong>Student:</strong> <?php echo htmlspecialchars($_SESSION['student_name']); ?></p>
                <p><strong>Student ID:</strong> <?php echo htmlspecialchars($_SESSION['student_id']); ?></p>
                <br>
                <button class="btn btn-success" onclick="confirmRegistration()">Confirm Registration</button>
                <button class="btn btn-secondary" onclick="closeRegistrationModal()" style="background-color: #6c757d;">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        let currentEventId = null;
        
        // Event data for JavaScript access
        const eventsData = <?php echo json_encode($events); ?>;
        
        function viewEventDetails(eventId) {
            const event = eventsData.find(e => e.id == eventId);
            if (!event) return;
            
            const detailsHtml = `
                <h3>${event.name}</h3>
                <div class="detail-row">
                    <div class="detail-label">Event ID:</div>
                    <div class="detail-value">${event.id}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value">
                        <span class="event-status ${getStatusClass(event.status)}">${event.status}</span>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Location:</div>
                    <div class="detail-value">${event.location}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Date & Time:</div>
                    <div class="detail-value">${formatDate(event.date)} at ${event.time}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Organizer:</div>
                    <div class="detail-value">${event.organizer}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Event Level:</div>
                    <div class="detail-value">${event.level}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Participants Limit:</div>
                    <div class="detail-value">${event.participants_limit} participants</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Registration Deadline:</div>
                    <div class="detail-value">${formatDate(event.registration_deadline)}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Geolocation:</div>
                    <div class="detail-value">${event.geo}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Description:</div>
                    <div class="detail-value">${event.description}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Approval Letter:</div>
                    <div class="detail-value">
                        <span class="approval-link" onclick="viewApprovalLetter('${event.approval_letter}')">${event.approval_letter}</span>
                    </div>
                </div>
            `;
            
            document.getElementById('eventDetailsContent').innerHTML = detailsHtml;
            document.getElementById('eventDetailsModal').style.display = 'block';
        }
        
        function registerForEvent(eventId, eventName) {
            currentEventId = eventId;
            document.getElementById('reg_event_name').textContent = eventName;
            document.getElementById('registrationModal').style.display = 'block';
        }
        
        function confirmRegistration() {
            // In a real application, this would send data to the server
            alert(`Registration successful for Event ID: ${currentEventId}!\n\nYou will receive a confirmation email shortly.`);
            closeRegistrationModal();
        }
        
        function viewLocation(geo) {
            if (geo.toLowerCase().includes('virtual')) {
                alert('This is a virtual event. Connection details will be provided upon registration.');
            } else {
                // In a real application, this could open a map or provide directions
                alert(`Event Location: ${geo}\n\nThis would typically open in your preferred maps application.`);
            }
        }
        
        function viewApprovalLetter(filename) {
            // In a real application, this would open or download the file
            alert(`Opening approval letter: ${filename}\n\nThis would typically open the PDF document.`);
        }
        
        function resetFilters() {
            window.location.href = window.location.pathname;
        }
        
        // Modal control functions
        function closeEventDetailsModal() {
            document.getElementById('eventDetailsModal').style.display = 'none';
        }
        
        function closeRegistrationModal() {
            document.getElementById('registrationModal').style.display = 'none';
            currentEventId = null;
        }
        
        // Utility functions
        function getStatusClass(status) {
            switch (status.toLowerCase()) {
                case 'completed': return 'status-completed';
                case 'pending': return 'status-pending';
                case 'approved': return 'status-approved';
                case 'rejected': return 'status-rejected';
                default: return 'status-pending';
            }
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = ['eventDetailsModal', 'registrationModal'];
            modals.forEach(function(modalId) {
                const modal = document.getElementById(modalId);
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>