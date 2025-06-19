<?php
// DB connection
include '../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $eventName = $_POST['eventName'];
    $eventLocation = $_POST['eventLocation'];
    $eventLevel = $_POST['eventLevel']; // Changed from eventStatus
    $advisorID = $_POST['advisorID']; // Changed from staffID
    $semester = $_POST['semester']; // Added semester field

    // Insert into event table with only existing columns
    $stmt = $conn->prepare("INSERT INTO event (eventName, eventLocation, eventLevel, advisorID, semester) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $eventName, $eventLocation, $eventLevel, $advisorID, $semester);

    if ($stmt->execute()) {
        echo "<script>alert('Event added successfully!'); window.location.href='event.php';</script>";
    } else {
        echo "<script>alert('Error adding event.');</script>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="Basyirah" content="Web Engineering Project - Add Event">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event - MyPetakom</title>
    <link rel="stylesheet" href="../module2/addEvent.css">
</head>
<body>

<?php include "../sideBar/Advisor_SideBar.php";?>

   
        <main class="main-content">
            <div class="header">
                <h1>Add Event</h1>
                <a href="event.php" class="backbutton">Back</a>
            </div>

            <form method="POST" class="form">
                <label for="eventName">Event Name:</label>
                <input type="text" name="eventName" id="eventName" required><br>

                <label for="eventLocation">Event Location:</label>
                <input type="text" name="eventLocation" id="eventLocation" required><br>

                <label for="eventLevel">Event Level:</label>
                <select name="eventLevel" id="eventLevel" required>
                    <option value="">-- Select Level --</option>
                    <option value="UMPSA">UMPSA</option>
                    <option value="STATE">STATE</option>
                    <option value="NATIONAL">NATIONAL</option>
                </select><br>

                <label for="advisorID">Advisor ID:</label>
                <input type="text" name="advisorID" id="advisorID" required><br>

                <label for="semester">Semester:</label>
                <input type="text" name="semester" id="semester" placeholder="e.g. 1, 2" required><br>

                <!-- Correct submit button -->
                <button type="submit">Add</button>

            </form>
        </main>
    </div>
</body>
</html>