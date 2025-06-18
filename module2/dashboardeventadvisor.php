<?php
//Database connection settings
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'mypetakom';
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error){
    die("Connection failed: " .$conn->connect_error);
}

// Upcoming Events
$eventSql = "SELECT eventName, eventLocation, eventLevel FROM event ORDER BY eventName ASC";
$eventResult = $conn->query($eventSql);

// Stats
$totalEvents = $conn->query("SELECT COUNT(*) as total FROM event")->fetch_assoc()['total'];
$totalStudents = $conn->query("SELECT COUNT(*) as total FROM student")->fetch_assoc()['total'];
$pendingApplications = $conn->query("SELECT COUNT(*) as total FROM meritapplication WHERE status = 'Pending'")->fetch_assoc()['total'];

// Fetch event level counts for chart
$levelSql = "SELECT eventLevel, COUNT(*) as count FROM event GROUP BY eventLevel";
$levelResult = $conn->query($levelSql);

$eventLevelLabels = [];
$eventLevelCounts = [];

while ($row = $levelResult->fetch_assoc()) {
    $eventLevelLabels[] = $row['eventLevel'];
    $eventLevelCounts[] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="Jaclina" content="Web Engineering Project- Student Dashboard">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/eventadvisor.css">
    <title>MyPetakom - Event Advisor Dashboard</title>
    <!-- Add Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Top Header -->
    <div class="top-heading-container">
        MyPetakom - Event Advisor
    </div>

    <div class="container">
        <div class="sidebar">
            <!-- PETAKOM Logo -->
            <div class="logo">
                <img src="TestImages/petakom.png" alt="PETAKOM Logo">
            </div>
            <!-- Profile Picture -->
            <img src="TestImages/user.png" alt="Profile Picture">
            <h2>Event Advisor</h2>
            <a href="eventadvisorprofile.php">Profile</a>
            <a href="dashboardeventadvisor.php">Dashboard</a>
            <a href="event.php">Events</a>
            <a href="meriteventadvisor.php">Merit</a>
            <a href="committee.php">Committee</a>
            <a href="graph.php">Attendance</a>
        </div>

        <main class="main-content">
            <div class="header">
                <div class="header-left">
                    <h1>Welcome</h1>
                </div>
                <a href="signout.php" class="signout-btn">SIGN OUT</a>
            </div>

            <!-- Stats Section -->
            <section class="stats">
                <div class="stat-box">Total Events<br><strong><?php echo $totalEvents; ?></strong></div>
                <div class="stat-box">Total Students<br><strong><?php echo $totalStudents; ?></strong></div>
                <div class="stat-box">Pending Applications<br><strong><?php echo $pendingApplications; ?></strong></div>
            </section>

            <!-- Event Level Chart -->
            <section class="event-status-chart">
                <h2 class="h2">Event Level Overview</h2>
                <canvas id="eventStatusChart" width="200" height="200"></canvas>
            </section>

            <!-- Events Section -->
            <section class="upcoming-events">
                <div class="events-header">
                    <h2 class="h2">Events</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Title</th>
                            <th>Location</th>
                            <th>Level</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($eventResult->num_rows > 0){
                            $i = 1;
                            while ($row = $eventResult->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $i++ . "</td>";
                                echo "<td>" . htmlspecialchars($row['eventName']). "</td>";
                                echo "<td>" . htmlspecialchars($row['eventLocation']). "</td>";
                                echo "<td>" . htmlspecialchars($row['eventLevel']). "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4'>No events found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <!-- Chart.js script -->
    <script>
        // Chart.js script
const ctx = document.getElementById('eventStatusChart').getContext('2d');
const eventStatusChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($eventLevelLabels); ?>,
        datasets: [{
            label: 'Event Level',
            data: <?php echo json_encode($eventLevelCounts); ?>,
            backgroundColor: [
                'rgba(75, 192, 192, 0.6)',
                'rgba(255, 206, 86, 0.6)',
                'rgba(255, 99, 132, 0.6)',
                'rgba(153, 102, 255, 0.6)',
                'rgba(54, 162, 235, 0.6)' 
            ],
            borderColor: [
                'rgba(75, 192, 192, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(255, 99, 132, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(54, 162, 235, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: false, // Prevent resizing
        maintainAspectRatio: false, // Allow fixed width/height
        plugins: {
            legend: {
                position: 'bottom',
            },
            title: {
                display: true,
                text: 'Event Level Distribution'
            }
        }
    }
});

    </script>
</body>
</html>
<?php
$conn->close();
?>