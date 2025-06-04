<?php
 
 require_once '../db_connect.php'; 

// Define merit points by event level and role type
function getMeritPoints($eventLevel, $role_type) {

    $level = strtoupper(trim($eventLevel));

    // Define points from taable
    $pointsTable = [
        'INTERNATIONAL' => ['main-committee' => 100, 'committee' => 70, 'participant' => 50],
        'NATIONAL'      => ['main-committee' => 80,  'committee' => 50, 'participant' => 40],
        'STATE'         => ['main-committee' => 60,  'committee' => 40, 'participant' => 30],
        'DISTRICT'      => ['main-committee' => 40,  'committee' => 30, 'participant' => 15],
        'UMPSA'         => ['main-committee' => 30,  'committee' => 20, 'participant' => 5]
    ];

    if (isset($pointsTable[$level][$role_type])) {
        return $pointsTable[$level][$role_type];
    }

    return 0;
}

function calculate_Committee_Main_Committee_Merits() {
    global $conn;
    
    if (!$conn || $conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    // Get all approved applications that don't have merit awards yet
    $sql = "
        SELECT ma.studentID, ma.eventID, ma.role_type, e.eventLevel
        FROM meritapplication ma
        JOIN event e ON ma.eventID = e.eventID
        LEFT JOIN meritaward mw ON ma.studentID = mw.studentID AND ma.eventID = mw.eventID
        WHERE ma.status = 'Approved' 
        AND ma.role_type IN ('committee', 'main-committee')
        AND mw.ma_ID IS NULL
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Database error: " . $conn->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $processed = 0;
    while ($row = $result->fetch_assoc()) {
        $studentID = (int)$row['studentID'];
        $eventID = (int)$row['eventID'];
        $roleType = $row['role_type'];
        $eventLevel = $row['eventLevel'];
        
        // Calculate points
        $points = getMeritPoints($eventLevel, $roleType);
        
        // Store in meritaward table
        storeOrUpdateMeritAwardMySQLi($conn, $studentID, $eventID, $points);
        $processed++;
    }

    $stmt->close();
    return $processed;
}


// Helper function to store or update merit award using MySQLi (fixed version)
function storeOrUpdateMeritAwardMySQLi($conn, $studentID, $eventID, $points) {
   
    $insertSql = "INSERT INTO meritaward (studentID, eventID, meritPoints) VALUES (?, ?, ?)";
    $insertStmt = $conn->prepare($insertSql);
    
    if (!$insertStmt) {
        die("Database error (insert): " . $conn->error);
    }
    
    $insertStmt->bind_param("iii", $studentID, $eventID, $points);
    $insertStmt->execute();
    $insertStmt->close();
}


function calculateParticipantMerits() {
    global $conn;
    
    if (!$conn || $conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    // and don't have approved committee or main-committee roles
    $sql = "
        SELECT DISTINCT 
            acs.studentID, 
            a.eventID, 
            e.eventLevel
        FROM attendancecslot AS acs
        JOIN attendance AS a ON acs.attendanceID = a.attendanceID
        JOIN event AS e ON a.eventID = e.eventID
        LEFT JOIN meritaward mw ON acs.studentID = mw.studentID AND a.eventID = mw.eventID
        LEFT JOIN meritapplication ma ON acs.studentID = ma.studentID 
            AND a.eventID = ma.eventID 
            AND ma.status = 'Approved'
            AND ma.role_type IN ('committee', 'main-committee')
        WHERE 
            acs.status = 'Present'
            AND mw.ma_ID IS NULL
            AND ma.studentID IS NULL
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Database error: " . $conn->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $processed = 0;
    while ($row = $result->fetch_assoc()) {
        $studentID = (int)$row['studentID'];
        $eventID = (int)$row['eventID'];
        $eventLevel = $row['eventLevel'];
        
        // Calculate points for participant
        $points = getMeritPoints($eventLevel, 'participant');
        
        // Store in meritaward table
        storeOrUpdateMeritAwardMySQLi($conn, $studentID, $eventID, $points);
        $processed++;
    }

    $stmt->close();
    return $processed;
}



function getSemester2EventCount($studentID) {
    global $conn;
    
    $sql = "SELECT COUNT(DISTINCT ma.eventID) as total_events 
            FROM meritaward ma 
            JOIN event e ON ma.eventID = e.eventID 
            WHERE e.semester = '2' AND ma.studentID = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $studentID);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result['total_events'] ?? 0;
}

// Get semester 2 merit total
function getSemester2MeritTotal($studentID) {
    global $conn;
    
    $sql = "SELECT SUM(ma.meritPoints) as total_merits 
            FROM meritaward ma 
            JOIN event e ON ma.eventID = e.eventID 
            WHERE e.semester = '2' AND ma.studentID = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $studentID);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result['total_merits'] ?? 0;
}



?>