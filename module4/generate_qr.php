<?php
// Include the phpqrcode library
require_once 'phpqrcode/qrlib.php';

// Get student ID from URL parameter
$studentID = $_GET['student_id'] ?? '';
$display = $_GET['display'] ?? false;

if (empty($studentID)) {
    if ($display) {
        header('Content-Type: text/html');
        echo '<div style="padding: 20px; text-align: center; color: #dc3545;">Error: Student ID is required</div>';
        exit();
    } else {
        http_response_code(400);
        die("Student ID is required");
    }
}

try {
    // Check if phpqrcode library exists
    if (!class_exists('QRcode')) {
        throw new Exception('PHPQRCode library not found. Please check the library path.');
    }

    // Create the URL that the QR code will point to
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];

    // Get the current directory path
    $current_dir = dirname($_SERVER['PHP_SELF']);
    $base_url = $protocol . '://' . $host . $current_dir;
    $target_url = $base_url . '/student_info.php?student_id=' . urlencode($studentID);

    // Create directory for QR codes if it doesn't exist
    $qr_dir = __DIR__ . '/qr_codes/';
    if (!file_exists($qr_dir)) {
        if (!mkdir($qr_dir, 0755, true)) {
            throw new Exception('Failed to create QR codes directory: ' . $qr_dir);
        }
    }

    // Check if directory is writable
    if (!is_writable($qr_dir)) {
        // Try to change permissions
        chmod($qr_dir, 0755);
        if (!is_writable($qr_dir)) {
            throw new Exception('QR codes directory is not writable: ' . $qr_dir);
        }
    }

    // Generate unique filename for the QR code using student ID
    $safe_student_id = preg_replace('/[^a-zA-Z0-9]/', '_', $studentID);
    $filename = $qr_dir . 'student_' . $safe_student_id . '_qr.png';
    $relative_filename = 'qr_codes/student_' . $safe_student_id . '_qr.png';

    // Check if QR code already exists and is recent (less than 24 hours old)
    $regenerate = true;
    if (file_exists($filename)) {
        $file_age = time() - filemtime($filename);
        // If file is less than 24 hours old, don't regenerate
        if ($file_age < 86400) {
            $regenerate = false;
        }
    }

    if ($regenerate) {
        // QR code parameters
        $errorCorrectionLevel = 'L'; // Low error correction level
        $matrixPointSize = 6; // Increased size for better readability
        $margin = 2; // Margin around QR code

        // Generate QR code
        QRcode::png($target_url, $filename, $errorCorrectionLevel, $matrixPointSize, $margin);

        // Add a small delay to ensure file is written
        usleep(100000); // 100ms delay
    }
    // Verify file exists
    if (!file_exists($filename)) {
        throw new Exception('QR code file was not created successfully: ' . $filename);
    }

    if ($display) {
        // Display the QR code image directly
        if (file_exists($filename)) {
            header('Content-Type: image/png');
            header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
            header('Content-Length: ' . filesize($filename));
            readfile($filename);
        } else {
            // Fallback error image
            header('Content-Type: text/html');
            echo '<div style="padding: 20px; text-align: center; color: #dc3545;">Error: QR code file not found</div>';
        }
    } else {
        // Return JSON response with file path
        header('Content-Type: application/json');
        if (file_exists($filename)) {
            echo json_encode([
                'success' => true,
                'qr_path' => $relative_filename,
                'full_path' => $filename,
                'target_url' => $target_url,
                'student_id' => $studentID,
                'file_size' => filesize($filename),
                'created_time' => date('Y-m-d H:i:s', filemtime($filename))
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to generate QR code',
                'attempted_path' => $filename
            ]);
        }
    }
} catch (Exception $e) {
    if ($display) {
        header('Content-Type: text/html');
        echo '<div style="padding: 20px; text-align: center; color: #dc3545;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'student_id' => $studentID
        ]);
    }
}
