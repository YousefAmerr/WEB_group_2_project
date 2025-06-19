<?php

include '../db_connect.php';

$studentID = $_GET['student_id'] ?? '';

if (empty($studentID)) {
    http_response_code(400);
    die("Student ID is required");
}

// Fetch student information and total merit points
$query = "
    SELECT 
        s.studentID,
        s.studentName,
        s.studentCard,
        s.studentEmail,
        COALESCE(SUM(ma.meritPoints), 0) as totalMerit
    FROM student s
    LEFT JOIN meritaward ma ON s.studentID = ma.studentID
    WHERE s.studentID = ?
    GROUP BY s.studentID, s.studentName, s.studentCard, s.studentEmail
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    http_response_code(500);
    die("Database error: " . $conn->error);
}

$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    die("Student not found");
}

$student = $result->fetch_assoc();
$stmt->close();

// Get detailed merit breakdown
$merit_query = "
    SELECT 
        e.eventName,
        e.eventLevel,
        ma.meritPoints,
        'Event Participation' as source
    FROM meritaward ma
    JOIN event e ON ma.eventID = e.eventID
    WHERE ma.studentID = ?
    ORDER BY ma.meritPoints DESC
";

$merit_stmt = $conn->prepare($merit_query);
$merit_stmt->bind_param("s", $studentID);
$merit_stmt->execute();
$merit_result = $merit_stmt->get_result();

$merit_breakdown = [];
while ($row = $merit_result->fetch_assoc()) {
    $merit_breakdown[] = $row;
}
$merit_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Information - <?php echo ($student['studentName']); ?></title>
    <link rel="stylesheet" href="css/student_info.css" />

</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Student Information</h1>
        </div>

        <div class="student-info">
            <div class="info-card">
                <div class="info-row">
                    <span class="info-label">Student ID:</span>
                    <span class="info-value"><?php echo ($student['studentID']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Student Name:</span>
                    <span class="info-value"><?php echo ($student['studentName'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Student Card:</span>
                    <span class="info-value"><?php echo ($student['studentCard'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo ($student['studentEmail'] ?? 'N/A'); ?></span>
                </div>
            </div>

            <div class="merit-total">
                <h2><?php echo $student['totalMerit']; ?></h2>
                <p>Total Merit Points</p>
            </div>

            <?php if (!empty($merit_breakdown)): ?>
                <div class="merit-breakdown">
                    <h3>Merit Points Breakdown</h3>
                    <?php foreach ($merit_breakdown as $merit): ?>
                        <div class="merit-item">
                            <div class="event-info">
                                <div class="event-name"><?php echo ($merit['eventName']); ?></div>
                                <div class="event-level"><?php echo ($merit['eventLevel']); ?> Level</div>
                            </div>
                            <div class="merit-points"><?php echo $merit['meritPoints']; ?> pts</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-merits">
                    <p>No merit points recorded for this academic year.</p>
                </div> <?php endif; ?>
        </div>
    </div>
</body>

</html>