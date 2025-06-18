<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'Student') {
    // If not logged in as student, redirect to login
    header("Location: login.php");
    exit();
}

// Get student ID from session
$studentID = $_SESSION['userID'];

// DB connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'mypetakom';
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch approved merit applications for the student
$sql = "
    SELECT e.eventName, e.eventLocation, e.eventLevel, ma.role_type, ma.status
    FROM meritapplication ma
    JOIN event e ON ma.eventID = e.eventID
    WHERE ma.studentID = ? AND ma.status = 'Approved'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="Basyirah" content="Web Engineering Project - Student Dashboard">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyPetakom - My Events</title>
    <link rel="stylesheet" href="style/StudentDash.css">
</head>
<body>
    <!-- Top Header -->
    <div class="top-heading-container">
        MyPetakom - Student 
    </div>

    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <!-- UMP Logo -->
            <div class="logo">
                <img src="TestImages/petakom.png" alt="PETAKOM Logo">
            </div>
            <!-- Profile Picture -->
            <img src="TestImages/user.png" alt="Profile Picture">
            <h2>Student</h2>
            <a href="studentprofile.php">Profile</a>
            <a href="dashboardstudent.php">Dashboard</a>
            <a href="studentevent.php">Events</a>
            <a href="studCheckIn.php">Attendance</a>
            <a href="Managemerit.php">Merit Management</a>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Events</h1>
            </div>
            <a href="signout.php" class="signout-btn">SIGN OUT</a>

            <section class="upcoming-events">
                <h2 class="h2">My Approved Events</h2>
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Event Name</th>
                            <th>Location</th>
                            <th>Level</th>
                            <th>Role Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            $count = 1;
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $count++ . "</td>";
                                echo "<td>" . htmlspecialchars($row['eventName']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['eventLocation']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['eventLevel']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['role_type']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>You have no approved event applications yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>