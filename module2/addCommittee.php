<?php
// DB connection
include '../db_connect.php';

// Fetch data for dropdowns
$events = $conn->query("SELECT eventID, eventName FROM event");
$students = $conn->query("SELECT studentID, studentName FROM student"); // Changed studID to studentID, studName to studentName
$coordinators = $conn->query("SELECT coordinatorID, coordinatorName FROM petakomcoordinator"); // Changed from staff to petakomcoordinator

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $studentID = $_POST['studentID'] ?? ''; // Changed studID to studentID
    $coordinatorID = $_POST['coordinatorID'] ?? ''; // Changed staffID to coordinatorID
    $eventID = $_POST['eventID'] ?? '';
        $role_type = $_POST['role_type'] ?? ''; // Add this line


    if ($studentID && $coordinatorID && $eventID) {
        // Insert into meritapplication table instead of commitee
        $stmt = $conn->prepare("INSERT INTO meritapplication (studentID, coordinatorID, eventID, role_type, status, submissionDate) VALUES (?, ?, ?, ?, 'Pending', CURDATE())");
$stmt->bind_param("ssss", $studentID, $coordinatorID, $eventID, $role_type);

        if ($stmt->execute()) {
            $message = "Committee member added successfully.";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "Please select all fields.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="Basyirah" content="Web Engineering Project - Event Advisor Dashboard">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Add Committee Member - MyPetakom</title>
    <link rel="stylesheet" href="../module2/addCommittee2.css">
</head>
<body>

<?php include "../sideBar/Advisor_SideBar.php";?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <div class="header-left">
                    <h1>Add Committee Member</h1>
                </div>
            </div>

            <section class="committee">
                <?php if ($message): ?>
                    <p class="message"><?php echo htmlspecialchars($message); ?></p>
                <?php endif; ?>

                <form method="post" action="addcommittee.php">
                    <label for="studentID">Student Name:</label><br />
                    <select name="studentID" id="studentID" required>
                        <option value="">-- Select Student --</option>
                        <?php while ($row = $students->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['studentID']); ?>">
                                <?php echo htmlspecialchars($row['studentName']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select><br /><br />

                    <label for="coordinatorID">Coordinator Name:</label><br />
                    <select name="coordinatorID" id="coordinatorID" required>
                        <option value="">-- Select Coordinator --</option>
                        <?php while ($row = $coordinators->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['coordinatorID']); ?>">
                                <?php echo htmlspecialchars($row['coordinatorName']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select><br /><br />

                    <label for="eventID">Event Name:</label><br />
                    <select name="eventID" id="eventID" required>
                        <option value="">-- Select Event --</option>
                        <?php while ($row = $events->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['eventID']); ?>">
                                <?php echo htmlspecialchars($row['eventName']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select><br /><br />

                <label for="role_type">Select Role Type:</label>
<select name="role_type" id="role_type" required>
    <option value="">-- Select Role --</option>
    <option value="committee">Committee</option>
    <option value="main-committee">Main Committee</option>
</select><br><br>

                    <a href="committee.php">
                    <button type="submit">Add</button>
					<button type="button">Cancel</button>
                    
                </form>
            </section>
        </main>
    </div>
</body>
</html>