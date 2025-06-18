<?php
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

if (!isset($_GET['eventID'])) {
    die("No event ID specified.");
}

$eventID = $_GET['eventID'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventName = $_POST['eventName'];
    $eventLocation = $_POST['eventLocation'];
    $eventLevel = $_POST['eventLevel'];
    $advisorID = $_POST['advisorID'];
    $semester = $_POST['semester'];

    $stmt = $conn->prepare("UPDATE event SET eventName=?, eventLocation=?, eventLevel=?, advisorID=?, semester=? WHERE eventID=?");
    $stmt->bind_param("ssssss", $eventName, $eventLocation, $eventLevel, $advisorID, $semester, $eventID);

    if ($stmt->execute()) {
        echo "<script>alert('Event updated successfully.'); window.location.href='event.php';</script>";
    } else {
        echo "Error updating event: " . $conn->error;
    }
}

// Get current event details
$stmt = $conn->prepare("SELECT * FROM event WHERE eventID = ?");
$stmt->bind_param("s", $eventID);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

// Get advisors for dropdown
$advisors = $conn->query("SELECT advisorID, advisorName FROM advisor");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="Basyirah" content="Web Engineering Project- Student Dashboard">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Event - MyPetakom</title>
    <link rel="stylesheet" href="style/eventadvisor.css">
</head>
<body>
    <!-- Top Header -->
    <div class="top-heading-container">
        MyPetakom - Event Advisor 
    </div>

    <div class="container">
        <div class="sidebar">
            <div class="logo">
                <img src="TestImages/UMP-Logo.jpg" alt="UMP Logo">
            </div>
            <img src="TestImages/user.png" alt="Profile Picture">
            <h2>Event Advisor</h2>
            <a href="eventadvisorprofile.php">Profile</a>
            <a href="dashboardeventadvisor.php">Dashboard</a>
            <a href="event.php">Events</a>
            <a href="meriteventadvisor.php">Merit</a>
            <a href="committee.php">Committee</a>
            <a href="attendanceeventadvisor.php">Attendance</a>
        </div>

        <main class="main-content">
            <div class="header">
                <div class="header-left">
                    <h1>Update Event</h1>
                </div>
                <a href="signouteventadvisor.php" class="signout-btn">SIGN OUT</a>
            </div>

            <section class="upcoming-events">
                <form method="post" class="form">
                    <label>Event Name:</label>
                    <input type="text" name="eventName" value="<?= htmlspecialchars($event['eventName']) ?>" required><br>

                    <label>Event Location:</label>
                    <input type="text" name="eventLocation" value="<?= htmlspecialchars($event['eventLocation']) ?>" required><br>

                    <label>Event Level:</label>
                    <select name="eventLevel" required>
                        <option value="">-- Select Level --</option>
                        <option value="UMPSA" <?= $event['eventLevel'] == 'UMPSA' ? 'selected' : '' ?>>UMPSA</option>
                        <option value="STATE" <?= $event['eventLevel'] == 'STATE' ? 'selected' : '' ?>>STATE</option>
                        <option value="NATIONAL" <?= $event['eventLevel'] == 'NATIONAL' ? 'selected' : '' ?>>NATIONAL</option>
                    </select><br>

                    <label>Advisor:</label>
                    <select name="advisorID" required>
                        <option value="">-- Select Advisor --</option>
                        <?php while ($advisor = $advisors->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($advisor['advisorID']) ?>" 
                                <?= $event['advisorID'] == $advisor['advisorID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($advisor['advisorName'] ?: 'Advisor ' . $advisor['advisorID']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select><br>

                    <label>Semester:</label>
                    <input type="text" name="semester" value="<?= htmlspecialchars($event['semester']) ?>" required><br><br>

                    <button type="submit" class="button">Update</button>
                    <a href="event.php" class="button">Back</a>
                </form>
            </section>
        </main>
    </div>
</body>
</html>