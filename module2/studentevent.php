<?php

// DB connection
include '../db_connect.php';

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
    <link rel="stylesheet" href="../module2/studentevent.css">
</head>
<body>

<?php include "../sideBar/Student_SideBar.php";?>


        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Event Info</h1>
            </div>

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