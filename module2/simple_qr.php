<?php
// Alternative QR code generator using Google Charts API (deprecated but still works)
// Or we can generate a simple HTML version for testing

$eventID = $_GET['eventID'] ?? 'TEST';
$url = "http://localhost:8080/workproject/WEB_group_2_project/event_detail.php?eventID=" . urlencode($eventID);

// Method 1: Redirect to Google QR API (simple test)
if (isset($_GET['method']) && $_GET['method'] == 'google') {
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($url);
    header("Location: " . $qr_url);
    exit;
}

// Method 2: Generate HTML page with QR code from external service
?>
<!DOCTYPE html>
<html>
<head>
    <title>QR Code for Event <?= htmlspecialchars($eventID) ?></title>
</head>
<body>
    <h2>QR Code for Event: <?= htmlspecialchars($eventID) ?></h2>
    <p>URL: <?= htmlspecialchars($url) ?></p>
    
    <h3>QR Code:</h3>
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?= urlencode($url) ?>" 
         alt="QR Code" style="border: 1px solid #ccc;">
    
    <br><br>
    <a href="<?= htmlspecialchars($url) ?>">Test the link directly</a>
    
    <br><br>
    <p><strong>Testing methods:</strong></p>
    <ul>
        <li><a href="?eventID=<?= htmlspecialchars($eventID) ?>&method=google">Direct QR image (Google API)</a></li>
        <li><a href="?eventID=2">Test with Event ID 2</a></li>
    </ul>
</body>
</html>