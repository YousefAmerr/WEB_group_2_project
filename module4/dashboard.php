<?php
session_start();
include '../db_connect.php';
include 'merit_functions.php';

$username = $_SESSION['username'] ?? '';

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

if (empty($studentID)) {
    header("Location: ../module1/login.php");
    exit();
}

// Fetch dashboard data
$result = $conn->query("SELECT COALESCE(SUM(meritPoints), 0) as total FROM meritaward WHERE studentID = '$studentID'");
$totalPoints = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(DISTINCT eventID) as count FROM meritaward WHERE studentID = '$studentID'");
$eventsCount = $result->fetch_assoc()['count'];

$avgPoints = $eventsCount > 0 ? round($totalPoints / $eventsCount, 1) : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM merit_claims WHERE studentID = '$studentID' AND status = 'Pending'");
$pendingClaims = $result->fetch_assoc()['count'];

// Chart data - Event Level
$result = $conn->query("
    SELECT e.eventLevel, COALESCE(SUM(ma.meritPoints), 0) as points 
    FROM meritaward ma 
    JOIN event e ON ma.eventID = e.eventID 
    WHERE ma.studentID = '$studentID' 
    GROUP BY e.eventLevel
");
$eventLevelData = [];
while ($row = $result->fetch_assoc()) {
    $eventLevelData[] = $row;
}
$levelLabels = array_column($eventLevelData, 'eventLevel');
$levelPoints = array_column($eventLevelData, 'points');

// Chart data - Role Type (FIXED VERSION)
$roleData = [];

// Get committee points - merit awards where student has approved committee role for that specific event
$result = $conn->query("
    SELECT COALESCE(SUM(mw.meritPoints), 0) as points
    FROM meritaward mw
    WHERE mw.studentID = '$studentID'
    AND EXISTS (
        SELECT 1 FROM meritapplication ma 
        WHERE ma.studentID = mw.studentID 
        AND ma.eventID = mw.eventID 
        AND ma.status = 'Approved'
        AND ma.role_type = 'committee'
    )
");
$committeePoints = $result->fetch_assoc()['points'];

// Get main-committee points - merit awards where student has approved main-committee role for that specific event
$result = $conn->query("
    SELECT COALESCE(SUM(mw.meritPoints), 0) as points
    FROM meritaward mw
    WHERE mw.studentID = '$studentID'
    AND EXISTS (
        SELECT 1 FROM meritapplication ma 
        WHERE ma.studentID = mw.studentID 
        AND ma.eventID = mw.eventID 
        AND ma.status = 'Approved'
        AND ma.role_type = 'main-committee'
    )
");
$mainCommitteePoints = $result->fetch_assoc()['points'];

// Get participant points - merit awards where student has NO approved committee/main-committee role for that specific event
$result = $conn->query("
    SELECT COALESCE(SUM(mw.meritPoints), 0) as points
    FROM meritaward mw
    WHERE mw.studentID = '$studentID'
    AND NOT EXISTS (
        SELECT 1 FROM meritapplication ma 
        WHERE ma.studentID = mw.studentID 
        AND ma.eventID = mw.eventID 
        AND ma.status = 'Approved'
        AND ma.role_type IN ('committee', 'main-committee')
    )
");
$participantPoints = $result->fetch_assoc()['points'];

// Build role data array (only include roles with points > 0)
if ($committeePoints > 0) {
    $roleData[] = ['role_type' => 'committee', 'points' => $committeePoints];
}
if ($mainCommitteePoints > 0) {
    $roleData[] = ['role_type' => 'main-committee', 'points' => $mainCommitteePoints];
}
if ($participantPoints > 0) {
    $roleData[] = ['role_type' => 'participant', 'points' => $participantPoints];
}

$roleLabels = array_column($roleData, 'role_type');
$rolePoints = array_column($roleData, 'points');

// Recent activities
$result = $conn->query("
    SELECT ma.meritPoints, e.eventName, e.eventLevel, ma.ma_ID as id
    FROM meritaward ma 
    JOIN event e ON ma.eventID = e.eventID 
    WHERE ma.studentID = '$studentID' 
    ORDER BY ma.ma_ID DESC 
    LIMIT 4
");

$activities = [];
while ($row = $result->fetch_assoc()) {
    $activities[] = $row;
}

$result = $conn->query("
    SELECT e.eventName, mc.claim_date, mc.status 
    FROM merit_claims mc 
    JOIN event e ON mc.eventID = e.eventID 
    WHERE mc.studentID = '$studentID' AND mc.status = 'Pending' 
    ORDER BY mc.claim_date DESC 
    LIMIT 1
");
$claims = [];
while ($row = $result->fetch_assoc()) {
    $claims[] = $row;
}

// Get student name for display (optional)
$result = $conn->query("SELECT studentName FROM student WHERE studentID = '$studentID'");
$studentName = $result->fetch_assoc()['studentName'] ?? 'Student';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyPetakom Student Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="../sideBar/side.css" />
    <link rel="stylesheet" href="css/dashboard.css" />
</head>
<body>
    <?php include '../sideBar/Student_SideBar.php'; ?>

    <div class="main-content">
        <div class="dashboard-header">
            <h1>MyPetakom Dashboard</h1>
            <p>Student Merit Point Tracking System - Welcome, <?= htmlspecialchars($studentName) ?>!</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $totalPoints ?></div>
                <div class="stat-label">Total Merit Points</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $eventsCount ?></div>
                <div class="stat-label">Events Participated</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $avgPoints ?></div>
                <div class="stat-label">Avg Points/Event</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $pendingClaims ?></div>
                <div class="stat-label">Pending Claims</div>
            </div>
        </div>

        <div class="charts-section">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Merit Points by Event Level</h3>
                </div>
                <div class="chart-container">
                    <canvas id="eventLevelChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Merit Points by Role Type</h3>
                </div>
                <div class="chart-container">
                    <canvas id="roleTypeChart"></canvas>
                </div>
            </div>
        </div>

        <div class="recent-activities">
            <h3>Recent Activities</h3>
            
            <?php foreach($claims as $claim): ?>
            <div class="activity-item">
                <div class="activity-icon claim">C</div>
                <div class="activity-content">
                    <div class="activity-title">Merit Claim Pending</div>
                    <div class="activity-desc">Application for <?= htmlspecialchars($claim['eventName']) ?> under review</div>
                    <div class="activity-date"><?= date('M j, Y', strtotime($claim['claim_date'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php foreach($activities as $activity): ?>
            <div class="activity-item">
                <div class="activity-icon merit">M</div>
                <div class="activity-content">
                    <div class="activity-title">Merit Points Awarded - <?= htmlspecialchars($activity['eventLevel']) ?> Event</div>
                    <div class="activity-desc"><?= $activity['meritPoints'] ?> points for <?= htmlspecialchars($activity['eventName']) ?></div>
                    <div class="activity-date"><?= date('M j, Y') ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Chart data
var levelLabels = [<?php foreach($levelLabels as $label) echo "'".$label."'".($label === end($levelLabels) ? '' : ','); ?>];
var levelPoints = [<?php foreach($levelPoints as $point) echo $point.($point === end($levelPoints) ? '' : ','); ?>];
var roleLabels = [<?php foreach($roleLabels as $label) echo "'".$label."'".($label === end($roleLabels) ? '' : ','); ?>];
var rolePoints = [<?php foreach($rolePoints as $point) echo $point.($point === end($rolePoints) ? '' : ','); ?>];

// Event Level Chart
new Chart(document.getElementById('eventLevelChart'), {
    type: 'doughnut',
    data: {
        labels: levelLabels,
        datasets: [{
            data: levelPoints,
            backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#00f2fe']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        aspectRatio: 2.0,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Role Type Chart
new Chart(document.getElementById('roleTypeChart'), {
    type: 'bar',
    data: {
        labels: roleLabels,
        datasets: [{
            data: rolePoints,
            backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#4facfe']
        }]
    }
});

// Simple animations
window.onload = function() {
    var cards = document.querySelectorAll('.stat-card');
    for(var i = 0; i < cards.length; i++) {
        cards[i].style.opacity = '1';
    }
};
    </script>
</body>
</html>