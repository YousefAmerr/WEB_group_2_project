<?php
session_start();

// Simulate logged-in staff (event advisor)
if (!isset($_SESSION['advisorID'])) {
    $_SESSION['advisorID'] = 1; // Replace with real login logic
}
$advisorID = $_SESSION['advisorID'];

// DB connection
include '../db_connect.php';


$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $studentID = $_POST['studentID'];
    $eventID = $_POST['eventID'];
    $roleType = $_POST['role_type'];

    // Check if this student already applied for this event
    $checkSql = "SELECT * FROM meritapplication WHERE studentID = ? AND eventID = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ss", $studentID, $eventID);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $message = "This student has already applied for merit for this event.";
    } else {
        $sql = "INSERT INTO meritapplication (studentID, coordinatorID, eventID, status, submissionDate, role_type) 
                VALUES (?, ?, ?, 'Pending', CURDATE(), ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $studentID, $advisorID, $eventID, $roleType);

        if ($stmt->execute()) {
            $message = "Merit successfully applied and pending admin approval.";
        } else {
            $message = "Error: " . $conn->error;
        }
        $stmt->close();
    }
    $checkStmt->close();
}

// Fetch events for dropdown
$eventDropdownSql = "SELECT eventID, eventName FROM event ORDER BY eventName ASC";
$eventDropdownResult = $conn->query($eventDropdownSql);

// Fetch students for dropdown
$studentDropdownSql = "SELECT studentID, studentName FROM student ORDER BY studentName ASC";
$studentDropdownResult = $conn->query($studentDropdownSql);

// Fetch all merit applications
$pendingSql = "
    SELECT m.meritApplicationID, s.studentID, s.studentName, e.eventName, m.status, m.role_type
    FROM meritapplication m
    JOIN student s ON m.studentID = s.studentID
    JOIN event e ON m.eventID = e.eventID
    ORDER BY m.meritApplicationID DESC
";
$pendingResult = $conn->query($pendingSql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="Basyirah" content="Web Engineering Project - Student Dashboard">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../module2/meriteventadvisor.css">
    <title>MyPetakom - Event Advisor</title>
    <style>
        /* Optional: Add colors to status */
        .status-pending { color: orange; font-weight: bold; }
        .status-approved { color: green; font-weight: bold; }
        .status-rejected { color: red; font-weight: bold; }
    </style>
</head>
<body>

<?php include "../sideBar/Advisor_SideBar.php";?>


        <main class="main-content">
            <div class="header">
                <div class="header-left">
                    <h1>Merit</h1>
                </div>
            </div>

            <?php if ($message): ?>
                <p style="color: <?= strpos($message, 'successfully') !== false ? 'green' : 'red'; ?>;">
                    <?= htmlspecialchars($message) ?>
                </p>
                <?php if (strpos($message, 'successfully') !== false): ?>
                    <script>alert("<?= addslashes($message) ?>");</script>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Form to Apply for Merit -->
            <form method="POST" action="">
                <h2>Apply Merit</h2>
                <label for="studentID">Select Student:</label>
                <select name="studentID" id="studentID" required>
                    <option value="">-- Select Student --</option>
                    <?php
                    if ($studentDropdownResult->num_rows > 0) {
                        while ($student = $studentDropdownResult->fetch_assoc()) {
                            echo "<option value='" . $student['studentID'] . "'>" . htmlspecialchars($student['studentName']) . "</option>";
                        }
                    }
                    ?>
                </select><br><br>

                <label for="eventID">Select Event:</label>
                <select name="eventID" id="eventID" required>
                    <option value="">-- Select Event --</option>
                    <?php
                    if ($eventDropdownResult->num_rows > 0) {
                        while ($event = $eventDropdownResult->fetch_assoc()) {
                            echo "<option value='" . $event['eventID'] . "'>" . htmlspecialchars($event['eventName']) . "</option>";
                        }
                    }
                    ?>
                </select><br><br>

                <label for="role_type">Select Role Type:</label>
                <select name="role_type" id="role_type" required>
                    <option value="">-- Select Role --</option>
                    <option value="committee">Committee</option>
                    <option value="main-committee">Main Committee</option>
                </select><br><br>

                <button type="submit">Apply Merit</button>
            </form>

            <!-- All Merit Applications Table -->
            <h2>All Merit Applications</h2>
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Event Name</th>
                        <th>Role Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($pendingResult && $pendingResult->num_rows > 0) {
                        $count = 1;
                        while ($row = $pendingResult->fetch_assoc()) {
                            // Determine status class for color
                            $statusClass = '';
                            if ($row['status'] == 'Pending') {
                                $statusClass = 'status-pending';
                            } elseif ($row['status'] == 'Approved') {
                                $statusClass = 'status-approved';
                            } elseif ($row['status'] == 'Rejected') {
                                $statusClass = 'status-rejected';
                            }

                            echo "<tr>";
                            echo "<td>" . $count++ . "</td>";
                            echo "<td>" . htmlspecialchars($row['studentID']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['studentName']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['eventName']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['role_type']) . "</td>";
                            echo "<td class='$statusClass'>" . htmlspecialchars($row['status']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No merit applications found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

        </main>
    </div>
</body>
</html>

<?php
$conn->close();
?>