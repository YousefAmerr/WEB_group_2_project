<?php 
include 'phpqrcode/qrlib.php';  

if (isset($_GET['eventID']) && !empty($_GET['eventID'])) {     
    $eventID = $_GET['eventID']; // eventID is varchar in your schema
    $eventDetailsUrl = "http://localhost/WEB%20ENGINEERING/event_detail.php?eventID=" . $eventID;     
    header('Content-Type: image/png');     
    QRcode::png($eventDetailsUrl); 
} else {     
    // Show a basic image that says "Invalid QR"     
    header('Content-Type: image/png');     
    QRcode::png('Invalid QR Code - Missing Event ID'); 
} 
?>