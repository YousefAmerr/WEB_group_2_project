<?php

require_once '../db_connect.php';

// Define merit points by event level and role type
function getMeritPoints($eventLevel, $role_type)
{
    $level = strtoupper(trim($eventLevel));
    $role = strtolower(trim($role_type)); // Normalize role to lowercase

    // Define points table with lowercase role keys for consistency
    $pointsTable = [
        'INTERNATIONAL' => ['main-committee' => 100, 'committee' => 70, 'participant' => 50],
        'NATIONAL'      => ['main-committee' => 80,  'committee' => 50, 'participant' => 40],
        'STATE'         => ['main-committee' => 60,  'committee' => 40, 'participant' => 30],
        'DISTRICT'      => ['main-committee' => 40,  'committee' => 30, 'participant' => 15],
        'UMPSA'         => ['main-committee' => 30,  'committee' => 20, 'participant' => 5]
    ];

    if (isset($pointsTable[$level][$role])) {
        return $pointsTable[$level][$role];
    }

    return 0;
}

function calculate_Committee_Main_Committee_Merits()
{
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
function storeOrUpdateMeritAwardMySQLi($conn, $studentID, $eventID, $points)
{
    // First check if record already exists
    $checkSql = "SELECT ma_ID FROM meritaward WHERE studentID = ? AND eventID = ?";
    $checkStmt = $conn->prepare($checkSql);

    if (!$checkStmt) {
        die("Database error (check): " . $conn->error);
    }

    $checkStmt->bind_param("ii", $studentID, $eventID);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        // Record exists, update it
        $checkStmt->close();
        $updateSql = "UPDATE meritaward SET meritPoints = ? WHERE studentID = ? AND eventID = ?";
        $updateStmt = $conn->prepare($updateSql);

        if (!$updateStmt) {
            die("Database error (update): " . $conn->error);
        }

        $updateStmt->bind_param("iii", $points, $studentID, $eventID);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        // Record doesn't exist, insert new one
        $checkStmt->close();
        $insertSql = "INSERT INTO meritaward (studentID, eventID, meritPoints) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);

        if (!$insertStmt) {
            die("Database error (insert): " . $conn->error);
        }

        $insertStmt->bind_param("iii", $studentID, $eventID, $points);
        $insertStmt->execute();
        $insertStmt->close();
    }
}


function calculateParticipantMerits()
{
    global $conn;

    if (!$conn || $conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    // Get students who attended events but don't have merit awards yet
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

        if ($points > 0) {
            // Store in meritaward table
            storeOrUpdateMeritAwardMySQLi($conn, $studentID, $eventID, $points);
            $processed++;
        }
    }

    $stmt->close();
    return $processed;
}



function getSemester2EventCount($studentID)
{
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
function getSemester2MeritTotal($studentID)
{
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

/**
 * Process approved merit claims and add them to meritaward table
 * This function checks for approved claims that haven't been processed yet
 * and adds them to the meritaward table with appropriate merit points
 */
function processApprovedMeritClaims()
{
    global $conn;

    if (!$conn || $conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    // Get all approved claims that don't have merit awards yet
    $sql = "
        SELECT mc.studentID, mc.eventID, mc.claim_id, mc.roleType, e.eventLevel
        FROM meritclaim mc
        JOIN event e ON mc.eventID = e.eventID
        LEFT JOIN meritaward mw ON mc.studentID = mw.studentID AND mc.eventID = mw.eventID
        WHERE mc.status = 'Approved' 
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
        $studentID = $row['studentID'];
        $eventID = $row['eventID'];
        $eventLevel = $row['eventLevel'];
        $roleType = strtolower($row['roleType'] ?: 'participant'); // Default to participant if null and normalize case

        // Calculate points based on the claimed role type
        $points = getMeritPoints($eventLevel, $roleType);

        // Store in meritaward table
        $insertSql = "INSERT INTO meritaward (studentID, eventID, meritPoints) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);

        if (!$insertStmt) {
            die("Database error (insert): " . $conn->error);
        }

        $insertStmt->bind_param("ssi", $studentID, $eventID, $points);

        if ($insertStmt->execute()) {
            $processed++;
        }

        $insertStmt->close();
    }

    $stmt->close();
    return $processed;
}

/**
 * Fix existing participant records with 0 merit points
 * This function will recalculate and update merit points for participants
 */
function fixParticipantMeritPoints()
{
    global $conn;

    if (!$conn || $conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    // Find merit awards with 0 points that should be participants
    $sql = "
        SELECT mw.ma_ID, mw.studentID, mw.eventID, e.eventLevel
        FROM meritaward mw
        JOIN event e ON mw.eventID = e.eventID
        LEFT JOIN meritapplication ma ON mw.studentID = ma.studentID 
            AND mw.eventID = ma.eventID 
            AND ma.status = 'Approved'
            AND ma.role_type IN ('committee', 'main-committee')
        WHERE mw.meritPoints = 0
        AND ma.studentID IS NULL
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Database error: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $fixed = 0;
    while ($row = $result->fetch_assoc()) {
        $ma_ID = $row['ma_ID'];
        $eventLevel = $row['eventLevel'];

        // Calculate correct participant points
        $points = getMeritPoints($eventLevel, 'participant');

        if ($points > 0) {
            // Update the merit award with correct points
            $updateSql = "UPDATE meritaward SET meritPoints = ? WHERE ma_ID = ?";
            $updateStmt = $conn->prepare($updateSql);

            if (!$updateStmt) {
                die("Database error (update): " . $conn->error);
            }

            $updateStmt->bind_param("ii", $points, $ma_ID);
            if ($updateStmt->execute()) {
                $fixed++;
            }
            $updateStmt->close();
        }
    }

    $stmt->close();
    return $fixed;
}

/**
 * Automatically run the process approved merit claims function
 * This can be called periodically or after claim approval
 */
function autoProcessMeritClaims()
{
    $processedClaims = processApprovedMeritClaims();
    $processedCommittee = calculate_Committee_Main_Committee_Merits();
    $processedParticipants = calculateParticipantMerits();
    $fixedParticipants = fixParticipantMeritPoints();

    return [
        'claims' => $processedClaims,
        'committee' => $processedCommittee,
        'participants' => $processedParticipants,
        'fixed' => $fixedParticipants,
        'total' => $processedClaims + $processedCommittee + $processedParticipants + $fixedParticipants
    ];
}
