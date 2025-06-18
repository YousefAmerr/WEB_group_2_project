<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an advisor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'advisor') {
    header("Location: index.php");
    exit();
}

// Fetch events for this advisor
$stmt = $pdo->prepare("SELECT * FROM event WHERE advisorID = ? ORDER BY eventID DESC");
$stmt->execute([$_SESSION['user_id']]);
$events = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management - MyPetakom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">MyPetakom - Advisor</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="advisor_dashboard.php">Dashboard</a>
                <a class="nav-link" href="event_management.php">Events</a>
                <a class="nav-link" href="attendance_management.php">Attendance</a>
                <a class="nav-link" href="merit_management.php">Merit</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Event Management</h2>
            <a href="event_crud.php?action=create" class="btn btn-primary">Add New Event</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Event ID</th>
                                <th>Event Name</th>
                                <th>Location</th>
                                <th>Level</th>
                                <th>Semester</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($events)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No events found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($events as $event): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event['eventID']); ?></td>
                                        <td><?php echo htmlspecialchars($event['eventName']); ?></td>
                                        <td><?php echo htmlspecialchars($event['eventLocation']); ?></td>
                                        <td><?php echo htmlspecialchars($event['eventLevel']); ?></td>
                                        <td><?php echo htmlspecialchars($event['semester']); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="event_crud.php?action=view&id=<?php echo $event['eventID']; ?>" 
                                                   class="btn btn-sm btn-info">View</a>
                                                <a href="event_crud.php?action=edit&id=<?php echo $event['eventID']; ?>" 
                                                   class="btn btn-sm btn-warning">Edit</a>
                                                <a href="event_crud.php?action=delete&id=<?php echo $event['eventID']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this event?')">Delete</a>
                                                <a href="generate_event_qr.php?id=<?php echo $event['eventID']; ?>" 
                                                   class="btn btn-sm btn-success">QR Code</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>