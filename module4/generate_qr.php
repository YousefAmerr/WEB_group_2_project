<?php
session_start();

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if GD extension is loaded
if (!extension_loaded('gd')) {
    http_response_code(500);
    die("GD extension is not installed. Please install php-gd extension.");
}

// Check if required GD functions exist
$required_functions = ['imagecreate', 'imagecolorallocate', 'imagesetpixel', 'imagepng'];
foreach ($required_functions as $func) {
    if (!function_exists($func)) {
        http_response_code(500);
        die("Required GD function '$func' is not available.");
    }
}

require_once 'phpqrcode/qrlib.php';
include '../db_connect.php';

// Get student ID from URL parameter
$studentID = $_GET['student_id'] ?? '';

if (empty($studentID)) {
    http_response_code(400);
    die("Student ID is required");
}

// Verify student exists
$student_check = "SELECT studentID FROM student WHERE studentID = ?";
$stmt = $conn->prepare($student_check);
$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    die("Student not found");
}
$stmt->close();

// Create QR codes directory
$qr_dir = 'qr_codes/';
if (!is_dir($qr_dir)) {
    if (!mkdir($qr_dir, 0755, true)) {
        http_response_code(500);
        die("Failed to create QR codes directory");
    }
}

// Check if directory is writable
if (!is_writable($qr_dir)) {
    http_response_code(500);
    die("QR codes directory is not writable");
}

// Generate the URL for the QR code
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$current_dir = rtrim(dirname($_SERVER['PHP_SELF']), '/');
$qr_url = $protocol . '://' . $host . $current_dir . '/student_info.php?student_id=' . urlencode($studentID);

// Generate QR code file
$qr_filename = 'student_' . $studentID . '_qr.png';
$qr_filepath = $qr_dir . $qr_filename;

// Only generate if file doesn't exist or is older than 1 hour
if (!file_exists($qr_filepath) || (time() - filemtime($qr_filepath)) > 3600) {
    try {
        // Set error correction level and size
        $errorCorrectionLevel = QR_ECLEVEL_L;
        $pixelSize = 8;
        $frameSize = 2;
        
        // Generate QR code with proper error handling
        QRcode::png($qr_url, $qr_filepath, $errorCorrectionLevel, $pixelSize, $frameSize);
        
        // Verify the file was created successfully
        if (!file_exists($qr_filepath) || filesize($qr_filepath) == 0) {
            throw new Exception("QR code file was not created or is empty");
        }
        
    } catch (Exception $e) {
        // Log the error and provide fallback
        error_log("QR Code generation failed: " . $e->getMessage());
        
        // Create a simple fallback QR code using alternative method
        if (createSimpleQR($qr_url, $qr_filepath)) {
            // Fallback succeeded
        } else {
            http_response_code(500);
            die("Error generating QR code: " . $e->getMessage());
        }
    }
}

// Check if we should display the image
if (isset($_GET['display']) && $_GET['display'] == 'true') {
    if (file_exists($qr_filepath) && filesize($qr_filepath) > 0) {
        header('Content-Type: image/png');
        header('Content-Length: ' . filesize($qr_filepath));
        header('Cache-Control: max-age=3600'); // Cache for 1 hour
        readfile($qr_filepath);
    } else {
        // Return a simple "QR Not Available" image
        header('Content-Type: image/png');
        createNotAvailableImage();
    }
} else {
    // Return JSON response with file info
    header('Content-Type: application/json');
    if (file_exists($qr_filepath) && filesize($qr_filepath) > 0) {
        echo json_encode([
            'success' => true,
            'file_path' => $qr_filepath,
            'url' => $qr_url,
            'qr_image_url' => $current_dir . '/generate_qr.php?student_id=' . urlencode($studentID) . '&display=true'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'QR code file not found',
            'direct_url' => $qr_url
        ]);
    }
}

$conn->close();

/**
 * Fallback function to create a simple QR code using Google Charts API
 */
function createSimpleQR($data, $filepath) {
    try {
        $size = '200x200';
        $url = 'https://chart.googleapis.com/chart?chs=' . $size . '&cht=qr&chl=' . urlencode($data);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (compatible; QR Generator)'
            ]
        ]);
        
        $qr_data = file_get_contents($url, false, $context);
        
        if ($qr_data !== false) {
            return file_put_contents($filepath, $qr_data) !== false;
        }
    } catch (Exception $e) {
        error_log("Fallback QR generation failed: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Create a "Not Available" placeholder image
 */
function createNotAvailableImage() {
    $width = 200;
    $height = 200;
    
    $image = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    $gray = imagecolorallocate($image, 128, 128, 128);
    
    imagefill($image, 0, 0, $white);
    imagerectangle($image, 0, 0, $width-1, $height-1, $gray);
    
    $text = "QR Code\nNot Available";
    $font_size = 3;
    
    // Calculate text position to center it
    $text_width = imagefontwidth($font_size) * 9; // approximate
    $text_height = imagefontheight($font_size) * 2; // 2 lines
    $x = ($width - $text_width) / 2;
    $y = ($height - $text_height) / 2;
    
    imagestring($image, $font_size, $x, $y-10, "QR Code", $black);
    imagestring($image, $font_size, $x-15, $y+10, "Not Available", $black);
    
    imagepng($image);
    imagedestroy($image);
}
?>