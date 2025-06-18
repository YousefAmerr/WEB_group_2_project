<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'mypetakom';
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['eventID'])) {
    die('Error: Missing eventID parameter in URL.');
}

$eventID = $_GET['eventID'];

$sql = "SELECT eventName, eventLocation, eventLevel, semester FROM event WHERE eventID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $eventID);
$stmt->execute();
$result = $stmt->get_result();

$event = null;
if ($result->num_rows > 0) {
    $event = $result->fetch_assoc();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .top-heading-container {
            background-color: #005baa;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
        }

        .event-detail-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 30px;
        }

        .event-detail-container h1 {
            font-size: 28px;
            color: #005baa;
            text-align: center;
            margin-bottom: 20px;
        }

        .event-detail-container p {
            font-size: 18px;
            margin-bottom: 15px;
        }

        .event-detail-container p strong {
            color: #555;
        }

        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #005baa;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            text-align: center;
        }

        .back-btn:hover {
            background-color: #004080;
        }
    </style>
</head>
<body>

<div class="top-heading-container">
    MyPetakom - Event Details
</div>

<div class="event-detail-container">
    <?php if ($event): ?>
        <h1><?php echo htmlspecialchars($event['eventName']); ?></h1>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($event['eventLocation']); ?></p>
        <p><strong>Level:</strong> <?php echo htmlspecialchars($event['eventLevel']); ?></p>
        <p><strong>Semester:</strong> <?php echo htmlspecialchars($event['semester']); ?></p>
    <?php else: ?>
        <p>Event not found for eventID: <?php echo htmlspecialchars($eventID); ?></p>
    <?php endif; ?>

    <a href="event.php" class="back-btn">Back to Events</a>
</div>

</body>
</html>