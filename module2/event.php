<?php
include '../db_connect.php';

$result = $conn->query("SELECT * FROM event");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="Basyirah" content="Web Engineering Project - Student Dashboard">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyPetakom - Event</title>
    <link rel="stylesheet" href="../module2/event.css">
</head>
<body>

<?php include "../sideBar/Advisor_SideBar.php";?>



        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <div class="header-left">
                    <h1>Events</h1>
                </div>
            </div>

            <section class="upcoming-events">
                <div class="events-header">
                    <h2 class="h">Upcoming Events</h2>
                    <a href="addevent.php" class="addbutton">Add</a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Title</th>
                            <th>Location</th>
                            <th>Level</th>
                            <th>Semester</th>
                            <th>Actions</th>
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
                                echo "<td>
                                    <a href='updateevent.php?eventID=" . $row['eventID'] . "' class='button'>Update</a>
                                    <a href='deleteevent.php?eventID=" . $row['eventID'] . "' class='button' onclick=\"return confirm('Delete this event?');\">Delete</a>
                                    <a href='generate_qr.php?eventID=" . $row['eventID'] . "' class='button'>QR</a>
                                </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No events found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>