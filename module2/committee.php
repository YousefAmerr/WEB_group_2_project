<?php
include '../db_connect.php';

// Fetch list of events for dropdown
$eventListSql = "SELECT eventID, eventName FROM event ORDER BY eventName ASC";
$eventListResult = $conn->query($eventListSql);

// Get selected eventID from GET parameter
$selectedEventID = isset($_GET['eventID']) ? $_GET['eventID'] : '';

// Fetch committee members (filtered if event selected)
$sql = "
    SELECT ma.meritApplicationID, s.studentName, ma.role_type, ma.status, e.eventName, ma.submissionDate
    FROM meritapplication ma
    JOIN student s ON ma.studentID = s.studentID
    JOIN event e ON ma.eventID = e.eventID
    WHERE ma.status = 'Approved'
";

$params = [];
$types = "";

if ($selectedEventID != '') {
    $sql .= " AND ma.eventID = ?";
    $params[] = $selectedEventID;
    $types .= "s";
}

$sql .= " ORDER BY e.eventName, s.studentName";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MyPetakom - Committee</title>
    <link rel="stylesheet" href="../module2/committee.css">
</head>

<body>

    <?php include "../sideBar/Advisor_SideBar.php"; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="header">
                <div class="header-left">
                    <h1>Committee</h1>
                </div>
            </div>

            <section class="committee">
                <div class="events-header">
                    <h2 class="h">Committee Members</h2>
                    <a href="addcommittee.php" class="addbutton">Add</a>
                </div>

                <!-- Filter Dropdown Form -->
                <form method="GET" style="margin-bottom: 20px;">
                    <label for="eventID"><strong>Filter by Event:</strong></label>
                    <select name="eventID" id="eventID" onchange="this.form.submit()">
                        <option value="">-- All Events --</option>
                        <?php
                        if ($eventListResult->num_rows > 0) {
                            while ($eventRow = $eventListResult->fetch_assoc()) {
                                $selected = ($eventRow['eventID'] == $selectedEventID) ? 'selected' : '';
                                echo "<option value='" . $eventRow['eventID'] . "' $selected>" . htmlspecialchars($eventRow['eventName']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </form>

                <!-- Committee Table -->
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Student Name</th>
                            <th>Role Type</th>
                            <th>Status</th>
                            <th>Event Name</th>
                            <th>Submission Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            $no = 1;
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $no++ . "</td>";
                                echo "<td>" . htmlspecialchars($row['studentName']) . "</td>";
                                echo "<td>" . htmlspecialchars(ucfirst($row['role_type'])) . "</td>";
                                echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['eventName']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['submissionDate']) . "</td>";
                                echo "<td>
                                    <a href='updatecommitee.php?meritApplicationID=" . $row['meritApplicationID'] . "' class='button'>Update</a>
                                    <a href='deletecommitee.php?meritApplicationID=" . $row['meritApplicationID'] . "' class='button' onclick=\"return confirm('Delete this committee member?');\">Delete</a>
                                </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7'>No committee members found.</td></tr>";
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