<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an advisor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'advisor') {
    header("Location: index.php");
    exit();
}

$action = $_GET['action'] ?? 'create';
$eventID = $_GET['id'] ?? '';
$event = null;

// Fetch event data for edit/view
if (($action === 'edit' || $action === 'view') && $eventID) {
    $stmt = $pdo->prepare("SELECT * FROM event WHERE eventID = ? AND advisorID = ?");
    $stmt->execute([$eventID, $_SESSION['user_id']]);
    $event = $stmt->fetch();
    
    if (!$event) {
        header("Location: event_management.php?error=Event not found");
        exit();
    }
}

// Handle form submission
if ($_POST) {
    $eventName = trim($_POST['eventName'] ?? '');
    $eventLocation = trim($_POST['eventLocation'] ?? '');
    $eventLevel = $_POST['eventLevel'] ?? '';
    $semester = trim($_POST['semester'] ?? '');
    
    $errors = [];
    
    if (empty($eventName)) $errors[] = "Event name is required";
    if (empty($eventLocation)) $errors[] = "Event location is required";
    if (empty($eventLevel)) $errors[] = "Event level is required";
    if (empty($semester)) $errors[] = "Semester is required";
    
    if (empty($errors)) {
        try {
            if ($action === 'create') {
                // Generate new eventID
                $stmt = $pdo->prepare("SELECT MAX(CAST(eventID AS UNSIGNED)) as maxID FROM event");
                $stmt->execute();
                $result = $stmt->fetch();
                $newEventID = ($result['maxID'] ?? 0) + 1;
                
                $stmt = $pdo->prepare("
                    INSERT INTO event (eventID, eventName, eventLocation, eventLevel, advisorID, semester) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$newEventID, $eventName, $eventLocation, $eventLevel, $_SESSION['user_id'], $semester]);
                
                header("Location: event_management.php?success=Event created successfully");
                exit();
            } elseif ($action === 'edit') {
                $stmt = $pdo->prepare("
                    UPDATE event 
                    SET eventName = ?, eventLocation = ?, eventLevel = ?, semester = ? 
                    WHERE eventID = ? AND advisorID = ?
                ");
                $stmt->execute([$eventName, $eventLocation, $eventLevel, $semester, $eventID, $_SESSION['user_id']]);
                
                header("Location: event_management.php?success=Event updated successfully");
                exit();
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Handle delete action
if ($action === 'delete' && $eventID) {
    try {
        $stmt = $pdo->prepare("DELETE FROM event WHERE eventID = ? AND advisorID = ?");
        $stmt->execute([$eventID, $_SESSION['user_id']]);
        
        header("Location: event_management.php?success=Event deleted successfully");
        exit();
    } catch (PDOException $e) {
        header("Location: event_management.php?error=Cannot delete event: " . $e->getMessage());
        exit();
    }
}

$pageTitle = ucfirst($action) . ' Event';
$isReadonly = ($action === 'view');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - MyPetakom</title>
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><?php echo $pageTitle; ?></h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="eventName" class="form-label">Event Name *</label>
                                <input type="text" class="form-control" id="eventName" name="eventName" 
                                       value="<?php echo htmlspecialchars($event['eventName'] ?? ''); ?>"
                                       <?php echo $isReadonly ? 'readonly' : 'required'; ?>>
                            </div>

                            <div class="mb-3">
                                <label for="eventLocation" class="form-label">Event Location *</label>
                                <input type="text" class="form-control" id="eventLocation" name="eventLocation" 
                                       value="<?php echo htmlspecialchars($event['eventLocation'] ?? ''); ?>"
                                       <?php echo $isReadonly ? 'readonly' : 'required'; ?>>
                            </div>

                            <div class="mb-3">
                                <label for="eventLevel" class="form-label">Event Level *</label>
                                <select class="form-select" id="eventLevel" name="eventLevel" 
                                        <?php echo $isReadonly ? 'disabled' : 'required'; ?>>
                                    <option value="">Select Level</option>
                                    <?php 
                                    $levels = ['INTERNATIONAL', 'NATIONAL', 'STATE', 'DISTRICT', 'UMPSA'];
                                    foreach ($levels as $level): 
                                    ?>
                                        <option value="<?php echo $level; ?>" 
                                                <?php echo ($event['eventLevel'] ?? '') === $level ? 'selected' : ''; ?>>
                                            <?php echo $level; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="semester" class="form-label">Semester *</label>
                                <input type="text" class="form-control" id="semester" name="semester" 
                                       value="<?php echo htmlspecialchars($event['semester'] ?? ''); ?>"
                                       placeholder="e.g., 2024/2025-1"
                                       <?php echo $isReadonly ? 'readonly' : 'required'; ?>>
                            </div>

                            <div class="d-flex gap-2">
                                <?php if (!$isReadonly): ?>
                                    <button type="submit" class="btn btn-primary">
                                        <?php echo $action === 'create' ? 'Create Event' : 'Update Event'; ?>
                                    </button>
                                <?php endif; ?>
                                <a href="event_management.php" class="btn btn-secondary">Back to Events</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>