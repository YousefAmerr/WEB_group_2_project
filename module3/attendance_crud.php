<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

switch ($action) {
    case 'create_attendance_slot':
        createAttendanceSlot();
        break;
    case 'get_attendance_slots':
        getAttendanceSlots();
        break;
    case 'update_attendance_slot':
        updateAttendanceSlot();
        break;
    case 'delete_attendance_slot':
        deleteAttendanceSlot();
        break;
    case 'get_attendance_records':
        getAttendanceRecords();
        break;
    case 'register_attendance':
        registerAttendance();
        break;
    case 'get_qr_code':
        getQRCode();
        break;
    case 'check_in_attendance':
        checkInAttendance();
        break;
    case 'get_events':
        getEvents();
        break;
    case 'close_attendance':
        closeAttendance();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// Create Attendance Slot (Advisor only)
function createAttendanceSlot() {
    global $conn, $user_type, $user_id;
    
    if ($user_type !== 'advisor') {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    $eventID = sanitize_input($_POST['eventID']);
    $attendanceDate = sanitize_input($_POST['attendanceDate']);
    $attendance_status = sanitize_input($_POST['attendance_status']);
    
    // Validate inputs
    if (empty($eventID) || empty($attendanceDate) || empty($attendance_status)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    // Check if event belongs to the advisor
    $checkQuery = "SELECT eventID FROM event WHERE eventID = ? AND advisorID = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ss", $eventID, $user_id);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Event not found or access denied']);
        return;
    }
    
    // Generate unique attendance ID using the function from config.php
    $attendanceID = generateID('A', $conn, 'attendance', 'attendanceID');
    
    // Insert attendance slot
    $query = "INSERT INTO attendance (attendanceID, advisorID, eventID, attendanceDate, attendance_status) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssss", $attendanceID, $user_id, $eventID, $attendanceDate, $attendance_status);
    
    if ($stmt->execute()) {
        // Generate QR code link
        $qrLink = "attendance_check.php?id=" . $attendanceID;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Attendance slot created successfully',
            'attendanceID' => $attendanceID,
            'qrLink' => $qrLink
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create attendance slot']);
    }
}

// Get Attendance Slots
function getAttendanceSlots() {
    global $conn, $user_type, $user_id;
    
    if ($user_type === 'advisor') {
        $query = "SELECT a.attendanceID, a.attendanceDate, a.attendance_status, 
                         e.eventName, e.eventLocation, e.eventLevel,
                         COUNT(ac.checkInID) as total_checkins
                  FROM attendance a
                  JOIN event e ON a.eventID = e.eventID
                  LEFT JOIN attendancecslot ac ON a.attendanceID = ac.attendanceID
                  WHERE a.advisorID = ?
                  GROUP BY a.attendanceID
                  ORDER BY a.attendanceDate DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $user_id);
    } else {
        $query = "SELECT a.attendanceID, a.attendanceDate, a.attendance_status, 
                         e.eventName, e.eventLocation, e.eventLevel,
                         COUNT(ac.checkInID) as total_checkins
                  FROM attendance a
                  JOIN event e ON a.eventID = e.eventID
                  LEFT JOIN attendancecslot ac ON a.attendanceID = ac.attendanceID
                  GROUP BY a.attendanceID
                  ORDER BY a.attendanceDate DESC";
        $stmt = $conn->prepare($query);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $slots = [];
    while ($row = $result->fetch_assoc()) {
        $slots[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $slots]);
}

// Update Attendance Slot
function updateAttendanceSlot() {
    global $conn, $user_type, $user_id;
    
    if ($user_type !== 'advisor') {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    $attendanceID = sanitize_input($_POST['attendanceID']);
    $attendanceDate = sanitize_input($_POST['attendanceDate']);
    $attendance_status = sanitize_input($_POST['attendance_status']);
    
    // Check if attendance slot belongs to the advisor
    $checkQuery = "SELECT a.attendanceID FROM attendance a 
                   JOIN event e ON a.eventID = e.eventID 
                   WHERE a.attendanceID = ? AND e.advisorID = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ss", $attendanceID, $user_id);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Attendance slot not found or access denied']);
        return;
    }
    
    $query = "UPDATE attendance SET attendanceDate = ?, attendance_status = ? WHERE attendanceID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $attendanceDate, $attendance_status, $attendanceID);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Attendance slot updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update attendance slot']);
    }
}

// Delete Attendance Slot
function deleteAttendanceSlot() {
    global $conn, $user_type, $user_id;
    
    if ($user_type !== 'advisor') {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    $attendanceID = sanitize_input($_POST['attendanceID']);
    
    // Check if attendance slot belongs to the advisor
    $checkQuery = "SELECT a.attendanceID FROM attendance a 
                   JOIN event e ON a.eventID = e.eventID 
                   WHERE a.attendanceID = ? AND e.advisorID = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ss", $attendanceID, $user_id);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Attendance slot not found or access denied']);
        return;
    }
    
    // Delete related check-ins first
    $deleteCheckInsQuery = "DELETE FROM attendancecslot WHERE attendanceID = ?";
    $deleteCheckInsStmt = $conn->prepare($deleteCheckInsQuery);
    $deleteCheckInsStmt->bind_param("s", $attendanceID);
    $deleteCheckInsStmt->execute();
    
    // Delete attendance slot
    $query = "DELETE FROM attendance WHERE attendanceID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $attendanceID);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Attendance slot deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete attendance slot']);
    }
}

// Get Attendance Records
function getAttendanceRecords() {
    global $conn, $user_type, $user_id;
    
    $dateFilter = $_GET['date'] ?? '';
    $eventFilter = $_GET['event'] ?? '';
    
    $query = "SELECT ac.checkInID, ac.attendance_date, ac.status,
                     s.studentName, s.studentID, s.studentEmail,
                     e.eventName, e.eventLocation, e.eventLevel,
                     a.attendanceDate, a.attendance_status
              FROM attendancecslot ac
              JOIN student s ON ac.studentID = s.studentID
              JOIN attendance a ON ac.attendanceID = a.attendanceID
              JOIN event e ON a.eventID = e.eventID";
    
    $conditions = [];
    $params = [];
    $types = "";
    
    if ($user_type === 'advisor') {
        $conditions[] = "a.advisorID = ?";
        $params[] = $user_id;
        $types .= "s";
    }
    
    if (!empty($dateFilter)) {
        $conditions[] = "ac.attendance_date = ?";
        $params[] = $dateFilter;
        $types .= "s";
    }
    
    if (!empty($eventFilter)) {
        $conditions[] = "e.eventID = ?";
        $params[] = $eventFilter;
        $types .= "s";
    }
    
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $query .= " ORDER BY ac.attendance_date DESC, ac.checkInID DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $records]);
}

// Register Attendance (Student)
function registerAttendance() {
    global $conn, $user_type, $user_id;

    if ($user_type !== 'student') {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }

    $attendanceID = sanitize_input($_POST['attendanceID']);
    $studentPassword = sanitize_input($_POST['password']);
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);

    // Step 1: Verify student password
    $studentQuery = "SELECT studentID, StuPassword FROM student WHERE studentID = ?";
    $studentStmt = $conn->prepare($studentQuery);
    $studentStmt->bind_param("s", $user_id);
    $studentStmt->execute();
    $studentResult = $studentStmt->get_result();

    if ($studentResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        return;
    }

    $student = $studentResult->fetch_assoc();
    if ($student['StuPassword'] !== $studentPassword) {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
        return;
    }

    // Step 2: Check if attendance slot is open
    $attendanceQuery = "SELECT attendanceID FROM attendance WHERE attendanceID = ? AND attendance_status = 'open'";
    $attendanceStmt = $conn->prepare($attendanceQuery);
    $attendanceStmt->bind_param("s", $attendanceID);
    $attendanceStmt->execute();

    if ($attendanceStmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Attendance slot not found or closed']);
        return;
    }

    // Step 3: Prevent duplicate check-in
    $existingQuery = "SELECT checkInID FROM attendancecslot WHERE attendanceID = ? AND studentID = ?";
    $existingStmt = $conn->prepare($existingQuery);
    $existingStmt->bind_param("ss", $attendanceID, $user_id);
    $existingStmt->execute();

    if ($existingStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already checked in for this event']);
        return;
    }

    // Step 4: Geolocation check (mock: UMPSA coords)
    $expectedLat = 3.727;
    $expectedLng = 103.123;

    function getDistance($lat1, $lon1, $lat2, $lon2) {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        return $dist * 60 * 1.1515 * 1.609344; // km
    }

    $distance = getDistance($latitude, $longitude, $expectedLat, $expectedLng);
    if ($distance > 1.0) {
        echo json_encode(['success' => false, 'message' => 'You are not at the event location']);
        return;
    }

    // Step 5: Register attendance
    $checkInID = generateID('', $conn, 'attendancecslot', 'checkInID');
    $insertQuery = "INSERT INTO attendancecslot (checkInID, attendanceID, studentID, status, attendance_date)
                    VALUES (?, ?, ?, 'Present', CURDATE())";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("sss", $checkInID, $attendanceID, $user_id);

    if ($insertStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Attendance registered successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to register attendance']);
    }
}

// Get QR Code for attendance
function getQRCode() {
    global $conn, $user_type, $user_id;
    
    $attendanceID = $_GET['attendanceID'] ?? '';
    
    if (empty($attendanceID)) {
        echo json_encode(['success' => false, 'message' => 'Attendance ID required']);
        return;
    }
    
    // Check if attendance slot exists
    $query = "SELECT a.attendanceID, a.attendance_status, e.eventName, e.eventLocation 
              FROM attendance a 
              JOIN event e ON a.eventID = e.eventID 
              WHERE a.attendanceID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $attendanceID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Attendance slot not found']);
        return;
    }
    
    $attendance = $result->fetch_assoc();
    $qrLink = "attendance_check.php?id=" . $attendanceID;
    
    echo json_encode([
        'success' => true, 
        'qrLink' => $qrLink,
        'eventName' => $attendance['eventName'],
        'eventLocation' => $attendance['eventLocation'],
        'status' => $attendance['attendance_status']
    ]);
}

// Check-in Attendance
function checkInAttendance() {
    global $conn, $user_type, $user_id;
    
    if ($user_type !== 'student') {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    $attendanceCode = sanitize_input($_POST['attendanceCode']);
    $password = sanitize_input($_POST['password']);
    
    // Extract attendance ID from code
    if (strpos($attendanceCode, 'id=') !== false) {
        parse_str(parse_url($attendanceCode, PHP_URL_QUERY), $params);
        $attendanceID = $params['id'] ?? '';
    } else {
        $attendanceID = $attendanceCode; // Direct ID
    }
    
    if (empty($attendanceID)) {
        echo json_encode(['success' => false, 'message' => 'Invalid attendance code']);
        return;
    }
    
    // Verify student password
    $studentQuery = "SELECT studentID, StuPassword FROM student WHERE studentID = ?";
    $studentStmt = $conn->prepare($studentQuery);
    $studentStmt->bind_param("s", $user_id);
    $studentStmt->execute();
    $studentResult = $studentStmt->get_result();
    
    if ($studentResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        return;
    }
    
    $student = $studentResult->fetch_assoc();
    if ($student['StuPassword'] !== $password) {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
        return;
    }
    
    // Check if attendance slot exists and is open
    $attendanceQuery = "SELECT a.attendanceID, a.attendance_status, e.eventName, e.eventLocation 
                       FROM attendance a 
                       JOIN event e ON a.eventID = e.eventID 
                       WHERE a.attendanceID = ? AND a.attendance_status = 'open'";
    $attendanceStmt = $conn->prepare($attendanceQuery);
    $attendanceStmt->bind_param("s", $attendanceID);
    $attendanceStmt->execute();
    $attendanceResult = $attendanceStmt->get_result();
    
    if ($attendanceResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Attendance slot not found or closed']);
        return;
    }
    
    $attendanceData = $attendanceResult->fetch_assoc();
    
    // Check if student already checked in
    $existingQuery = "SELECT checkInID FROM attendancecslot WHERE attendanceID = ? AND studentID = ?";
    $existingStmt = $conn->prepare($existingQuery);
    $existingStmt->bind_param("ss", $attendanceID, $user_id);
    $existingStmt->execute();
    
    if ($existingStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already checked in for this event']);
        return;
    }
    
    // Generate check-in ID
    $checkInID = generateID('', $conn, 'attendancecslot', 'checkInID');
    
    // Register attendance
    $insertQuery = "INSERT INTO attendancecslot (checkInID, attendanceID, studentID, status, attendance_date) 
                    VALUES (?, ?, ?, 'Present', CURDATE())";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("sss", $checkInID, $attendanceID, $user_id);
    
    if ($insertStmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Attendance registered successfully',
            'eventName' => $attendanceData['eventName'],
            'eventLocation' => $attendanceData['eventLocation']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to register attendance']);
    }
}

// Get Events for dropdown
function getEvents() {
    global $conn, $user_type, $user_id;
    
    if ($user_type === 'advisor') {
        $query = "SELECT eventID, eventName, eventLocation, eventLevel FROM event WHERE advisorID = ? ORDER BY eventName";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $user_id);
    } else {
        $query = "SELECT eventID, eventName, eventLocation, eventLevel FROM event ORDER BY eventName";
        $stmt = $conn->prepare($query);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $events]);
}

// Close Attendance
function closeAttendance() {
    global $conn, $user_type, $user_id;
    
    if ($user_type !== 'advisor') {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    $attendanceID = sanitize_input($_POST['attendanceID']);
    
    // Check if attendance slot belongs to the advisor
    $checkQuery = "SELECT a.attendanceID FROM attendance a 
                   JOIN event e ON a.eventID = e.eventID 
                   WHERE a.attendanceID = ? AND e.advisorID = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ss", $attendanceID, $user_id);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Attendance slot not found or access denied']);
        return;
    }
    
    $query = "UPDATE attendance SET attendance_status = 'closed' WHERE attendanceID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $attendanceID);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Attendance slot closed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to close attendance slot']);
    }
}
?>