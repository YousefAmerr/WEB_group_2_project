<?php
session_start();

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'mypetakom';
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle AJAX Approve/Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['action'])) {
    $id = intval($_POST['id']);
    $action = $_POST['action'];

    if ($action === 'approve') {
        $new_status = 'Approved';
    } elseif ($action === 'reject') {
        $new_status = 'Rejected';
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE meritapplication SET status = ? WHERE meritApplicationID = ?");
    $stmt->bind_param("si", $new_status, $id);

    if ($stmt->execute()) {
        // If approved, also add to meritaward table
        if ($new_status === 'Approved') {
            // Get event details to calculate merit points
            $merit_stmt = $conn->prepare("SELECT studentID, eventID, role_type FROM meritapplication WHERE meritApplicationID = ?");
            $merit_stmt->bind_param("i", $id);
            $merit_stmt->execute();
            $merit_result = $merit_stmt->get_result();
            
            if ($merit_row = $merit_result->fetch_assoc()) {
                // Calculate merit points based on role type
                $merit_points = ($merit_row['role_type'] === 'main-committee') ? 80 : 40;
                
                // Insert into meritaward table
                $award_stmt = $conn->prepare("INSERT INTO meritaward (studentID, eventID, meritPoints) VALUES (?, ?, ?)");
                $award_stmt->bind_param("ssi", $merit_row['studentID'], $merit_row['eventID'], $merit_points);
                $award_stmt->execute();
                $award_stmt->close();
            }
            $merit_stmt->close();
        }
        
        echo json_encode(['success' => true, 'new_status' => $new_status]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating application.']);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// Fetch only PENDING merit applications
$sql = "
    SELECT 
        m.meritApplicationID,
        s.studentID,
        s.studentName,
        e.eventName,
        m.status,
        m.role_type
    FROM meritapplication m
    JOIN student s ON m.studentID = s.studentID
    JOIN event e ON m.eventID = e.eventID
    WHERE m.status = 'Pending'
    ORDER BY m.meritApplicationID ASC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="Basyirah" content="Web Engineering Project - Admin Merit Approval">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/admin.css">
    <title>MyPetakom - Approve Merit</title>
   
    <script>
    function updateMeritStatus(meritApplicationID, action, rowElement) {
        if (confirm("Are you sure you want to " + action + " this application?")) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        alert("Application " + response.new_status.toLowerCase() + " successfully.");
                        rowElement.remove(); // remove row if approved/rejected
                    } else {
                        alert(response.message);
                    }
                }
            };

            xhr.send("id=" + encodeURIComponent(meritApplicationID) + "&action=" + encodeURIComponent(action));
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        const buttons = document.querySelectorAll(".approve-button");
        buttons.forEach(button => {
            button.addEventListener("click", function () {
                const id = this.getAttribute("data-id");
                const action = this.getAttribute("data-action");
                const row = this.closest("tr");
                updateMeritStatus(id, action, row);
            });
        });
    });
    </script>
</head>
<body>
    <div class="top-heading-container">MyPetakom - Admin</div>

    <div class="container">
        <div class="sidebar">
            <div class="logo">
                <img src="TestImages/UMP-Logo.jpg" alt="UMP Logo">
            </div>
            <img src="TestImages/user.png" alt="Profile Picture">
            <h2>Admin</h2>
            <a href="dashboardadmin.php">Dashboard</a>
            <a href="userprofile.php">User Profile</a>
            <a href="meritadmin.php">Merit</a>
        </div>

        <main class="main-content">
            <div class="header">
                <div class="header-left">
                    <h1>Pending Merit Applications</h1>
                </div>
                <a href="signout.php" class="signout-btn">SIGN OUT</a>
            </div>

            <section class="merit">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Student Name</th>
                            <th>Student ID</th>
                            <th>Event</th>
                            <th>Role Type</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result && $result->num_rows > 0) {
                            $count = 1;
                            while ($row = $result->fetch_assoc()) {
                                $status = htmlspecialchars($row['status']);
                                echo "<tr>";
                                echo "<td>" . $count++ . "</td>";
                                echo "<td>" . htmlspecialchars($row['studentName']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['studentID']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['eventName']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['role_type']) . "</td>";
                                echo "<td class='status-cell status-$status'>" . $status . "</td>";
                                echo "<td>";
                                echo "<button class='approve-button' data-id='" . $row['meritApplicationID'] . "' data-action='approve'>Approve</button>";
                                echo "<button class='approve-button' data-id='" . $row['meritApplicationID'] . "' data-action='reject'>Reject</button>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7'>No pending merit applications found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>

<?php $conn->close(); ?>