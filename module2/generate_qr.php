<?php 
// Enhanced QR Code Generator connected to event_detail.php
// Using external QR API service (more reliable than local phpqrcode)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database connection to validate eventID
include '../db_connect.php';

// Function to validate eventID exists in database
function validateEventID($eventID, $conn) {
    $sql = "SELECT eventID FROM event WHERE eventID = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("s", $eventID);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    
    $stmt->close();
    return $exists;
}

// Function to sanitize eventID
function sanitizeEventID($eventID) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '', trim($eventID));
}

// Function to generate QR code using external API
function generateQRCodeFromAPI($data, $size = 300) {
    $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($data);
    
    // Fetch the QR code image from the API
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'QR Generator'
        ]
    ]);
    
    $qr_image = file_get_contents($qr_api_url, false, $context);
    
    if ($qr_image === false) {
        return false;
    }
    
    // Output the image
    header('Content-Type: image/png');
    header('Content-Length: ' . strlen($qr_image));
    echo $qr_image;
    return true;
}

// Function to find event_detail.php in the project
function findEventDetailPath() {
    $possiblePaths = [
        // Same directory
        './event_detail.php',
        // Parent directory (most likely based on your file structure)
        '../event_detail.php',
        // Root of project
        '../../event_detail.php',
        // Specific module paths
        '../module1/event_detail.php',
        '../module2/event_detail.php',
        '../module3/event_detail.php',
        '../module4/event_detail.php',
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return null;
}

// Function to convert relative path to URL path
function getUrlPath($relativePath) {
    // Get the current script's directory in URL terms
    $currentScriptDir = dirname($_SERVER['SCRIPT_NAME']);
    
    // Handle relative path conversion
    if (strpos($relativePath, './') === 0) {
        // Same directory
        return $currentScriptDir . '/' . substr($relativePath, 2);
    } elseif (strpos($relativePath, '../') === 0) {
        // Parent directory - count how many levels up
        $levels = substr_count($relativePath, '../');
        $filename = basename($relativePath);
        
        // Go up the required number of levels
        $pathParts = explode('/', trim($currentScriptDir, '/'));
        for ($i = 0; $i < $levels && count($pathParts) > 0; $i++) {
            array_pop($pathParts);
        }
        
        return '/' . implode('/', $pathParts) . '/' . $filename;
    }
    
    return $relativePath;
}

// Main logic
if (isset($_GET['eventID']) && !empty(trim($_GET['eventID']))) {
    
    $eventID = sanitizeEventID($_GET['eventID']);
    
    if (empty($eventID)) {
        header('Content-Type: text/plain');
        die('Error: Invalid Event ID format');
    }
    
    // Validate eventID exists in database
    if (!validateEventID($eventID, $conn)) {
        header('Content-Type: text/plain');
        die('Error: Event ID not found in database: ' . $eventID);
    }
    
    // Find the actual location of event_detail.php
    $eventDetailRelativePath = findEventDetailPath();
    
    if (!$eventDetailRelativePath) {
        header('Content-Type: text/plain');
        die('Error: event_detail.php not found in project structure');
    }
    
    // Build the correct URL path
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $port = $_SERVER['SERVER_PORT'];
    
    // Add port if not standard
    $hostWithPort = $host;
    if (($protocol === 'http' && $port != 80) || ($protocol === 'https' && $port != 443)) {
        $hostWithPort .= ':' . $port;
    }
    
    // Convert relative path to URL path
    $eventDetailUrlPath = getUrlPath($eventDetailRelativePath);
    
    // Build the full URL
    $eventDetailsUrl = $protocol . "://" . $hostWithPort . $eventDetailUrlPath . "?eventID=" . urlencode($eventID);
    
    // Debug mode - show the URL that will be encoded
    if (isset($_GET['debug'])) {
        header('Content-Type: text/html');
        echo "<h3>QR Code Generator Debug Information</h3>";
        echo "<strong>Event ID:</strong> " . htmlspecialchars($eventID) . "<br>";
        echo "<strong>Event Detail Relative Path:</strong> " . htmlspecialchars($eventDetailRelativePath) . "<br>";
        echo "<strong>Event Detail URL Path:</strong> " . htmlspecialchars($eventDetailUrlPath) . "<br>";
        echo "<strong>Full URL to be encoded:</strong> <a href='" . htmlspecialchars($eventDetailsUrl) . "' target='_blank'>" . htmlspecialchars($eventDetailsUrl) . "</a><br>";
        echo "<strong>Event exists in database:</strong> " . (validateEventID($eventID, $conn) ? 'Yes' : 'No') . "<br>";
        echo "<strong>Protocol:</strong> " . $protocol . "<br>";
        echo "<strong>Host:</strong> " . $host . "<br>";
        echo "<strong>Port:</strong> " . $port . "<br>";
        echo "<strong>Current Script:</strong> " . $_SERVER['SCRIPT_NAME'] . "<br>";
        echo "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
        
        // Test if the target file actually exists and is accessible
        echo "<br><h4>File System Check:</h4>";
        $fullPath = realpath($eventDetailRelativePath);
        echo "<strong>Full System Path:</strong> " . ($fullPath ? htmlspecialchars($fullPath) : 'Not found') . "<br>";
        echo "<strong>File Exists:</strong> " . (file_exists($eventDetailRelativePath) ? 'Yes' : 'No') . "<br>";
        echo "<strong>File Readable:</strong> " . (is_readable($eventDetailRelativePath) ? 'Yes' : 'No') . "<br>";
        
        $conn->close();
        exit;
    }
    
    // Set cache headers
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Generate QR code using external API
    if (!generateQRCodeFromAPI($eventDetailsUrl)) {
        header('Content-Type: text/plain');
        die('Error: Failed to generate QR code');
    }
    
} else {
    // Missing eventID parameter - generate error QR code
    $errorMessage = "Error: Missing Event ID parameter";
    if (!generateQRCodeFromAPI($errorMessage)) {
        header('Content-Type: text/plain');
        die('Error: Failed to generate error QR code');
    }
}

$conn->close();
?>