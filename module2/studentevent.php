<?php
// Start session to get logged-in student information
session_start();

// DB connection - MOVED TO TOP
include '../db_connect.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['username']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../module1/login.php"); // Redirect to login if not logged in or not a student
    exit();
}

// Get student ID and name from database using the username stored in session
$username = $_SESSION['username'];
$studentInfoSql = "SELECT studentID, studentName FROM student WHERE StuUsername = ?";
$studentStmt = $conn->prepare($studentInfoSql);
$studentStmt->bind_param("s", $username);
$studentStmt->execute();
$studentInfoResult = $studentStmt->get_result();

if ($studentInfoResult->num_rows === 0) {
    // Student not found, redirect to login
    header("Location: ../module1/login.php");
    exit();
}

$studentInfo = $studentInfoResult->fetch_assoc();
$studentID = $studentInfo['studentID'];
$studentName = $studentInfo['studentName'];

// Fetch approved merit applications for the student
$sql = "
    SELECT e.eventName, e.eventLocation, e.eventLevel, e.semester, ma.role_type, ma.status, ma.submissionDate
    FROM meritapplication ma
    JOIN event e ON ma.eventID = e.eventID
    WHERE ma.studentID = ? AND ma.status = 'Approved'
    ORDER BY ma.submissionDate DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();

// Also fetch all available events (optional - for students to see what events exist)
$allEventsSql = "
    SELECT eventID, eventName, eventLocation, eventLevel, semester
    FROM event
    ORDER BY eventID DESC
";
$allEventsResult = $conn->query($allEventsSql);

// Student info already retrieved above using username from session
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="Basyirah" content="Web Engineering Project - Student Dashboard">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyPetakom - My Events</title>
    <link rel="stylesheet" href="../module2/studentevent.css">
</head>
<body>

<?php include "../sideBar/Student_SideBar.php";?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Event Info</h1>
            </div>

            <!-- My Approved Events Section -->
            <section class="upcoming-events">
                <h2 class="h2">My Approved Events</h2>
                <table class="events-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Event Name</th>
                            <th>Location</th>
                            <th>Level</th>
                            <th>Semester</th>
                            <th>Role Type</th>
                            <th>Status</th>
                            <th>Application Date</th>
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
                                echo "<td>" . htmlspecialchars($row['semester']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['role_type']) . "</td>";
                                echo "<td><span class='status-approved'>" . htmlspecialchars($row['status']) . "</span></td>";
                                echo "<td>" . htmlspecialchars($row['submissionDate']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8' class='no-data'>You have no approved event applications yet.</td></tr>";
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
$studentStmt->close();
$conn->close();
?>