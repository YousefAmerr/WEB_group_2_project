<?php
//Database connection settings
include '../db_connect.php';

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
    <link rel="stylesheet" href="../module2/dashboardeventadvisor.css">
    <title>MyPetakom - Event Advisor Dashboard</title>
    <!-- Add Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include "../sideBar/Advisor_SideBar.php";?>

    <div class="container">
        
        <main class="main-content">
            <div class="header">
                <div class="header-left">
                    <h1>Event Reports</h1>
                </div>
              
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
                    <h2 class="h2">All Event</h2>
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