<?php
// DB connection
include '../db_connect.php';

$message = "";

// Check meritApplicationID is passed
if (!isset($_GET['meritApplicationID'])) {
    die("No merit application ID specified.");
}

$meritApplicationID = $_GET['meritApplicationID'];

// Fetch data for dropdowns
$events = $conn->query("SELECT eventID, eventName FROM event");
$students = $conn->query("SELECT studentID, studentName FROM student");
$coordinators = $conn->query("SELECT coordinatorID, coordinatorName FROM petakomcoordinator");

// Fetch existing merit application data to pre-fill the form
$stmt = $conn->prepare("SELECT * FROM meritapplication WHERE meritApplicationID = ?");
$stmt->bind_param("i", $meritApplicationID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Merit application not found.");
}

$application = $result->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $studentID = $_POST['studentID'] ?? '';
    $coordinatorID = $_POST['coordinatorID'] ?? '';
    $eventID = $_POST['eventID'] ?? '';
    $role_type = $_POST['role_type'] ?? '';

    if ($studentID && $coordinatorID && $eventID && $role_type) {
        $updateStmt = $conn->prepare("UPDATE meritapplication SET studentID = ?, coordinatorID = ?, eventID = ?, role_type = ? WHERE meritApplicationID = ?");
        $updateStmt->bind_param("ssssi", $studentID, $coordinatorID, $eventID, $role_type, $meritApplicationID);

        if ($updateStmt->execute()) {
            $message = "Merit application updated successfully.";
            
            // Refresh application data after update
            $stmt->execute();
            $result = $stmt->get_result();
            $application = $result->fetch_assoc();
        } else {
            $message = "Error updating merit application: " . $conn->error;
        }
        $updateStmt->close();
    } else {
        $message = "Please fill in all required fields.";
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="Basyirah" content="Web Engineering Project - Event Advisor Dashboard">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Update Merit Application - MyPetakom</title>
    <link rel="stylesheet" href="../module2/updatecommitee2.css">
</head>
<body>

<?php include "../sideBar/Advisor_SideBar.php";?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <div class="header-left">
                    <h1>Update Committee</h1>
                </div>
            </div>

            <section class="committee">
                <?php if (!empty($message)): ?>
                    <script>
                        alert("<?php echo addslashes($message); ?>");
                    </script>
                <?php endif; ?>

                <form method="post" action="">
                    <label for="studentID">Student Name:</label><br />
                    <select name="studentID" id="studentID" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $row): ?>
                            <option value="<?php echo htmlspecialchars($row['studentID']); ?>" <?php if ($application['studentID'] == $row['studentID']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($row['studentName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br /><br />
                    
                    <label for="coordinatorID">Coordinator Name:</label><br />
                    <select name="coordinatorID" id="coordinatorID" required>
                        <option value="">-- Select Coordinator --</option>
                        <?php foreach ($coordinators as $row): ?>
                            <option value="<?php echo htmlspecialchars($row['coordinatorID']); ?>" <?php if ($application['coordinatorID'] == $row['coordinatorID']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($row['coordinatorName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br /><br />
                    
                    <label for="eventID">Event Name:</label><br />
                    <select name="eventID" id="eventID" required>
                        <option value="">-- Select Event --</option>
                        <?php foreach ($events as $row): ?>
                            <option value="<?php echo htmlspecialchars($row['eventID']); ?>" <?php if ($application['eventID'] == $row['eventID']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($row['eventName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br /><br />
                    
                    <label for="role_type">Role Type:</label><br />
                    <select name="role_type" id="role_type" required>
                        <option value="">-- Select Role Type --</option>
                        <option value="committee" <?php if ($application['role_type'] == 'committee') echo 'selected'; ?>>Committee</option>
                        <option value="main-committee" <?php if ($application['role_type'] == 'main-committee') echo 'selected'; ?>>Main Committee</option>
                    </select><br /><br />

                    <button type="submit" class="button">Update</button>
                    <a href="committee.php" class="button">Back</a>
                </form>
            </section>
        </main>
    </div>
</body>
</html>