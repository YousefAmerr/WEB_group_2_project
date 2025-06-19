<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['advisor', 'coordinator'])) {
    header("Location: ../module1/login.php");
    exit();
}

// Include appropriate sidebar based on user type
if ($_SESSION['user_type'] === 'advisor') {
    include '../sideBar/Advisor_SideBar.php';
} else {
    include '../sideBar/Coordinator_SideBar.php';
}

$user_type = $_SESSION['user_type'];
$username = $_SESSION['username'] ?? '';

// Get user ID and info based on username (with null coalescing to prevent undefined key warnings)
$user_id = '';
$user_info = [];

if ($user_type === 'advisor') {
    $stmt = $conn->prepare("SELECT advisorID, advisorName FROM advisor WHERE adUsername = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id = $row['advisorID'];
        $user_info['advisorName'] = $row['advisorName'];
    }
    $stmt->close();
} else {
    $stmt = $conn->prepare("SELECT coordinatorID, coordinatorName FROM petakomcoordinator WHERE CoUsername = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id = $row['coordinatorID'];
        $user_info['coordinatorName'] = $row['coordinatorName'];
    }
    $stmt->close();
}

// Redirect if user not found
if (empty($user_id)) {
    header("Location: ../module1/login.php");
    exit();
}

// Chart 1: Attendance per event (JOIN table)
$query1 = "
    SELECT e.eventName, e.eventLevel, COUNT(ac.checkInID) AS total_attended
    FROM attendancecslot ac
    JOIN attendance a ON ac.attendanceID = a.attendanceID
    JOIN event e ON a.eventID = e.eventID
";
if ($user_type === 'advisor') {
    $query1 .= " WHERE e.advisorID = ?";
}
$query1 .= " GROUP BY e.eventID, e.eventName, e.eventLevel ORDER BY total_attended DESC";

if ($user_type === 'advisor') {
    $stmt1 = $conn->prepare($query1);
    $stmt1->bind_param("i", $user_id);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    $results1 = $result1->fetch_all(MYSQLI_ASSOC);
    $stmt1->close();
} else {
    $result1 = $conn->query($query1);
    $results1 = $result1->fetch_all(MYSQLI_ASSOC);
}
$labels1 = [];
$data1 = [];
$colors1 = [];
$levelColors = [
    'INTERNATIONAL' => '#e74c3c',
    'NATIONAL' => '#f39c12',
    'STATE' => '#2ecc71',
    'DISTRICT' => '#3498db',
    'UMPSA' => '#9b59b6'
];

foreach ($results1 as $row) {
    $labels1[] = $row['eventName'];
    $data1[] = $row['total_attended'];
    $colors1[] = $levelColors[$row['eventLevel']] ?? '#95a5a6';
}

// Chart 2: Daily check-ins (Single table)
$query2 = "
    SELECT attendance_date, COUNT(checkInID) AS total_checkins
    FROM attendancecslot
    WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY attendance_date
    ORDER BY attendance_date ASC
";
$result2 = $conn->query($query2);
$results2 = $result2->fetch_all(MYSQLI_ASSOC);
$labels2 = [];
$data2 = [];
foreach ($results2 as $row) {
    $labels2[] = date('M d', strtotime($row['attendance_date']));
    $data2[] = $row['total_checkins'];
}

// Additional Statistics
// Total Statistics
$totalStats = [];

// Total Events
$query = "SELECT COUNT(*) as total FROM event";
if ($user_type === 'advisor') {
    $query .= " WHERE advisorID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $totalStats['events'] = $result->fetch_assoc()['total'];
    $stmt->close();
} else {
    $result = $conn->query($query);
    $totalStats['events'] = $result->fetch_assoc()['total'];
}

// Total Attendance Slots
$query = "SELECT COUNT(*) as total FROM attendance a";
if ($user_type === 'advisor') {
    $query .= " JOIN event e ON a.eventID = e.eventID WHERE e.advisorID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $totalStats['attendance_slots'] = $result->fetch_assoc()['total'];
    $stmt->close();
} else {
    $result = $conn->query($query);
    $totalStats['attendance_slots'] = $result->fetch_assoc()['total'];
}

// Total Check-ins
$query = "SELECT COUNT(*) as total FROM attendancecslot ac";
if ($user_type === 'advisor') {
    $query .= " JOIN attendance a ON ac.attendanceID = a.attendanceID 
                JOIN event e ON a.eventID = e.eventID WHERE e.advisorID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $totalStats['checkins'] = $result->fetch_assoc()['total'];
    $stmt->close();
} else {
    $result = $conn->query($query);
    $totalStats['checkins'] = $result->fetch_assoc()['total'];
}

// Unique Students
$query = "SELECT COUNT(DISTINCT ac.studentID) as total FROM attendancecslot ac";
if ($user_type === 'advisor') {
    $query .= " JOIN attendance a ON ac.attendanceID = a.attendanceID 
                JOIN event e ON a.eventID = e.eventID WHERE e.advisorID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $totalStats['unique_students'] = $result->fetch_assoc()['total'];
    $stmt->close();
} else {
    $result = $conn->query($query);
    $totalStats['unique_students'] = $result->fetch_assoc()['total'];
}

// Event Level Distribution (for coordinator)
$eventLevels = [];
if ($user_type === 'coordinator') {
    $query = "SELECT eventLevel, COUNT(*) as count FROM event GROUP BY eventLevel";
    $result = $conn->query($query);
    $eventLevels = $result->fetch_all(MYSQLI_ASSOC);
}

// Recent Activity
$recentQuery = "
    SELECT s.studentName, e.eventName, ac.attendance_date, ac.status
    FROM attendancecslot ac
    JOIN student s ON ac.studentID = s.studentID
    JOIN attendance a ON ac.attendanceID = a.attendanceID
    JOIN event e ON a.eventID = e.eventID
";
if ($user_type === 'advisor') {
    $recentQuery .= " WHERE e.advisorID = ?";
}
$recentQuery .= " ORDER BY ac.attendance_date DESC LIMIT 10";

if ($user_type === 'advisor') {
    $stmt = $conn->prepare($recentQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $recentActivity = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $conn->query($recentQuery);
    $recentActivity = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Dashboard - MyPetakom</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Main content styles to work with sidebar */


        .containerr {
            margin-top: 40px;
        }

        .stats-card {
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .chart-container {
            position: relative;
            height: 400px;
        }

        .activity-item {
            border-left: 3px solid #007bff;
            padding-left: 15px;
            margin-bottom: 15px;
        }

        .navbar-brand {
            font-weight: bold;
        }

     
    </style>
</head>

<body>

    <div class="main-content">
        <div class="container-fluid p-4">
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="mb-4">
                        <i class="fas fa-chart-bar"></i> Attendance Analytics
                        <small class="text-muted">(<?= ucfirst($user_type) ?> View)</small>
                    </h2>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card stats-card h-100 border-primary">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-alt fa-2x text-primary mb-2"></i>
                            <h4 class="text-primary"><?= $totalStats['events'] ?></h4>
                            <p class="card-text">Total Events</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card h-100 border-info">
                        <div class="card-body text-center">
                            <i class="fas fa-clock fa-2x text-info mb-2"></i>
                            <h4 class="text-info"><?= $totalStats['attendance_slots'] ?></h4>
                            <p class="card-text">Attendance Slots</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card h-100 border-success">
                        <div class="card-body text-center">
                            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                            <h4 class="text-success"><?= $totalStats['checkins'] ?></h4>
                            <p class="card-text">Total Check-ins</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card h-100 border-warning">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-2x text-warning mb-2"></i>
                            <h4 class="text-warning"><?= $totalStats['unique_students'] ?></h4>
                            <p class="card-text">Unique Students</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4">
                <div class="col-lg-8 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-chart-bar"></i> Attendance Per Event (JOIN Query)
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartEvent"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($user_type === 'coordinator' && !empty($eventLevels)): ?>
                    <div class="col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-chart-pie"></i> Event Levels
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="chartLevels"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="row mb-4">
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-chart-line"></i> Daily Check-ins (Last 30 Days) - Single Table Query
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartDate"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <i class="fas fa-history"></i> Recent Activity
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            <?php if (!empty($recentActivity)): ?>
                                <?php foreach ($recentActivity as $activity): ?>
                                    <div class="activity-item">
                                        <strong><?= htmlspecialchars($activity['studentName']) ?></strong>
                                        <small class="text-muted">checked in to</small>
                                        <br>
                                        <small class="text-primary"><?= htmlspecialchars($activity['eventName']) ?></small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($activity['attendance_date'])) ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No recent activity</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div> <!-- Close main-content -->

    <script>
        // Chart 1: Attendance per Event
        const ctx1 = document.getElementById('chartEvent').getContext('2d');
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels1) ?>,
                datasets: [{
                    label: 'Total Attended',
                    data: <?= json_encode($data1) ?>,
                    backgroundColor: <?= json_encode($colors1) ?>,
                    borderColor: <?= json_encode($colors1) ?>,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Event Attendance by Level (Color Coded)'
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Chart 2: Daily Check-ins
        const ctx2 = document.getElementById('chartDate').getContext('2d');
        new Chart(ctx2, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels2) ?>,
                datasets: [{
                    label: 'Check-ins',
                    data: <?= json_encode($data2) ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderColor: '#28a745',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#28a745',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Daily Check-in Trends'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        <?php if ($user_type === 'coordinator' && !empty($eventLevels)): ?>
            // Chart 3: Event Levels (Coordinator only)
            const ctx3 = document.getElementById('chartLevels').getContext('2d');
            new Chart(ctx3, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_column($eventLevels, 'eventLevel')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($eventLevels, 'count')) ?>,
                        backgroundColor: [
                            '#e74c3c', '#f39c12', '#2ecc71', '#3498db', '#9b59b6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Event Distribution by Level'
                        }
                    }
                }
            });
        <?php endif; ?>
    </script>
</body>

</html>