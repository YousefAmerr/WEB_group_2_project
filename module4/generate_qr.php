<?php
// Include the phpqrcode library
require_once 'phpqrcode/qrlib.php';
include '../db_connect.php';

// Get parameters
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
$display = isset($_GET['display']) ? $_GET['display'] : false;

// Validate student_id
if (empty($student_id)) {
    if ($display) {
        // Return a placeholder image or error message
        header('Content-Type: text/html');
        echo '<div style="padding: 20px; border: 2px solid #ddd; border-radius: 8px; background-color: #fff3cd; text-align: center;">';
        echo '<p style="color: #856404; margin: 0;">Invalid Student ID</p>';
        echo '</div>';
        exit();
    } else {
        die('Invalid student ID');
    }
}

// Create qr_codes directory if it doesn't exist
$qr_dir = 'qr_codes/';
if (!file_exists($qr_dir)) {
    mkdir($qr_dir, 0755, true);
}

// Generate the URL that the QR code will point to
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$script_dir = dirname($_SERVER['PHP_SELF']);
$qr_url = $base_url . $script_dir . '/student_info.php?student_id=' . urlencode($student_id);

// Define the QR code filename
$qr_filename = $qr_dir . 'student_' . $student_id . '.png';

try {
    // Check if QR code already exists and is recent (less than 24 hours old)
    $regenerate = true;
    if (file_exists($qr_filename)) {
        $file_age = time() - filemtime($qr_filename);
        if ($file_age < 86400) { // 24 hours = 86400 seconds
            $regenerate = false;
        }
    }
    
    // Generate QR code if needed
    if ($regenerate) {
        // QR code parameters
        $errorCorrectionLevel = 'M'; // Error correction level (L, M, Q, H)
        $matrixPointSize = 6; // Size of each matrix point
        
        // Generate QR code and save to file
        QRcode::png($qr_url, $qr_filename, $errorCorrectionLevel, $matrixPointSize, 2);
    }
    
    // Check if file was created successfully
    if (!file_exists($qr_filename)) {
        throw new Exception('Failed to generate QR code file');
    }
    
    if ($display) {
        // Display the QR code image
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
        
        // Output the image
        readfile($qr_filename);
        exit();
    } else {
        // Return success message or redirect
        echo json_encode([
            'success' => true,
            'message' => 'QR code generated successfully',
            'filename' => $qr_filename,
            'url' => $qr_url
        ]);
    }
    
} catch (Exception $e) {
    // Handle errors
    error_log('QR Code Generation Error: ' . $e->getMessage());
    
    if ($display) {
        // Return error image or placeholder
        header('Content-Type: text/html');
        echo '<div style="padding: 20px; border: 2px solid #ddd; border-radius: 8px; background-color: #fff3cd; text-align: center;">';
        echo '<p style="color: #856404; margin: 0; margin-bottom: 10px;">QR Code Generation Failed</p>';
        echo '<a href="' . htmlspecialchars($qr_url) . '" target="_blank" ';
        echo 'style="background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; display: inline-block;">';
        echo 'View Merit Report</a>';
        echo '</div>';
        exit();
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to generate QR code: ' . $e->getMessage()
        ]);
    }
}

// Close database connection if it was opened
if (isset($conn)) {
    $conn->close();
}
?>