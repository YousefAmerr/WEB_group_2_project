<?php
session_start();

// Include database connection
include '../db_connect.php';

// Get the student ID from session
// First, get the username from session
$username = $_SESSION['username'] ?? '';

// Then get the studentID from the database using the username
$studentID = '';
if (!empty($username)) {
    $student_query = "SELECT studentID FROM student WHERE username = ?";
    $stmt = $conn->prepare($student_query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $studentID = $row['studentID'];
    }
    $stmt->close();
}

// If no studentID found, redirect to login
if (empty($studentID)) {
    header("Location: ../module1/login.php");
    exit();
}

// Get selected role filter
$roleFilter = isset($_GET['category']) ? $_GET['category'] : '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Merit Awarded List</title>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <link rel="stylesheet" href="css/meritAwardedList.css" />
  <link rel="stylesheet" href="../sideBar/side.css" />
</head>
<body>
  <?php include '../sideBar/Student_SideBar.php'; ?>
  <div class="main-content">
    <h1 class="page_title">Merit Awarded List</h1>

    <div class="border">
      <form class="border_filter" method="GET" action="">
        <select id="category" name="category" onchange="this.form.submit()">
          <option value="">-- Select Role --</option>
          <option value="Participant" <?php echo ($roleFilter == 'Participant') ? 'selected' : ''; ?>>Participant</option>
          <option value="Committee" <?php echo ($roleFilter == 'Committee') ? 'selected' : ''; ?>>Committee</option>
          <option value="Main-Committee" <?php echo ($roleFilter == 'Main-Committee') ? 'selected' : ''; ?>>Main Committee</option>
        </select>
      </form>

      <div class="event-list">
        <?php
        // Initialize total merit score and event count
        $totalMeritScore = 0;
        $eventCount = 0;

        try {
            // Build the SQL query based on filter - FIXED: Get semester from event table
            $sql = "SELECT e.eventName as event_name, 
                           ma.role_type AS role, 
                           ma.meritPoints AS merit_score,
                           e.semester,
                           ma.award_date
                    FROM event e
                    JOIN meritaward ma ON e.eventID = ma.eventID
                    WHERE ma.studentID = ?";
            
            // Add role filter if selected
            if (!empty($roleFilter)) {
                $sql .= " AND ma.role_type = ?";
            }
            
            $sql .= " ORDER BY ma.award_date DESC";

            $stmt = $conn->prepare($sql);
            
            if ($stmt === false) {
                throw new Exception("Error preparing query: " . $conn->error);
            }
            
            // Bind parameters based on whether filter is applied
            if (!empty($roleFilter)) {
                $stmt->bind_param("ss", $studentID, $roleFilter);
            } else {
                $stmt->bind_param("s", $studentID);
            }

            if (!$stmt->execute()) {
                throw new Exception("Error executing query: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $eventCount = $result->num_rows;

            if ($eventCount === 0) {
                echo "<div class='no-events'>";
                echo "<p>No events found for the selected criteria.</p>";
                echo "</div>";
            } else {
                while ($row = $result->fetch_assoc()) {
                    $eventName = htmlspecialchars($row['event_name']);
                    $role = htmlspecialchars($row['role'] ?? 'Not Specified');
                    $meritScore = intval($row['merit_score'] ?? 0);
                    $semester = htmlspecialchars($row['semester'] ?? 'Not Specified');
                    
                    $totalMeritScore += $meritScore;

                    echo "
                        <div class='sections_border'>
                            <h4>Event Name:  $eventName</h4>
                            <div class='event-details'>
                                <p><strong>Semester:</strong> $semester</p>
                            </div>
                            <div class='sections_border_space'>
                                <h5>Role: $role</h5>
                                <h5 style='float:right;'>Score: $meritScore</h5>
                            </div>
                        </div>";
                }
            }

            $stmt->close();

            // FIXED: Get total merit score ONLY for semester 2 - Join with event table to get semester
            $totalSQL = "SELECT 
                            SUM(ma.meritPoints) AS total_merit,
                            COUNT(ma.ma_ID) AS event_count
                        FROM meritaward ma
                        JOIN event e ON ma.eventID = e.eventID
                        WHERE ma.studentID = ? AND e.semester = '2'";

            $totalStmt = $conn->prepare($totalSQL);
            
            if ($totalStmt === false) {
                throw new Exception("Error preparing total summary query: " . $conn->error);
            }
            
            $totalStmt->bind_param("s", $studentID);
            
            if (!$totalStmt->execute()) {
                throw new Exception("Error executing total summary query: " . $totalStmt->error);
            }
            
            $totalResult = $totalStmt->get_result();
            $totalData = $totalResult->fetch_assoc();
            $grandTotal = $totalData['total_merit'] ?? 0;
            $totalEventCount = $totalData['event_count'] ?? 0;

            $totalStmt->close();

        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
            $grandTotal = 0;
            $totalEventCount = 0;
        }
        ?> 
      </div>
    </div>

    <div class="summary-container">
      <div class="summary-left">
        <h3>Semester 2 Merits Summary</h3>
        <div class="merit_summary_box">
          <p><strong>Total Events Participated (Semester 2):</strong> <span style="padding-left: 30px;"><?php echo $totalEventCount; ?></span></p>
          <p><strong>Total Semester 2 Merits Points:</strong> <span style="padding-left: 30px;"><?php echo $grandTotal; ?> </span></p>
          <?php if (!empty($roleFilter)): ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Replace the QR section in your meritAwardedList.php with this improved version -->
<div class="summary-right">
    <h3>Scan for Complete Merit Report</h3>
    <div class="qr_box">
        <?php
        // Generate QR code for the current student
        $qr_image_path = "generate_qr.php?student_id=" . urlencode($studentID) . "&display=true";
        
        // Also create a direct link to the student info page
        $direct_link = "student_info.php?student_id=" . urlencode($studentID);
        ?>
        <div style="text-align: center; padding: 20px;">
            <!-- QR Code Image with improved error handling -->
            <div id="qr-container-<?php echo $studentID; ?>">
                <img id="qr-image-<?php echo $studentID; ?>" 
                     src="<?php echo $qr_image_path; ?>" 
                     alt="QR Code for Student Merit Report" 
                     style="max-width: 200px; max-height: 200px; border: 2px solid #ddd; border-radius: 8px;"
                     onload="handleQRLoad('<?php echo $studentID; ?>')"
                     onerror="handleQRError('<?php echo $studentID; ?>', '<?php echo $direct_link; ?>')">
            </div>
            
            <!-- Loading message -->
            <div id="qr-loading-<?php echo $studentID; ?>" style="display: none; padding: 20px; border: 2px solid #ddd; border-radius: 8px; background-color: #f0f8ff;">
                <p style="color: #666; margin: 0;">Generating QR Code...</p>
            </div>
            
            <!-- Fallback error message -->
            <div id="qr-error-<?php echo $studentID; ?>" style="display: none; padding: 20px; border: 2px solid #ddd; border-radius: 8px; background-color: #fff3cd;">
                <p style="color: #856404; margin: 0; margin-bottom: 10px;">QR Code temporarily unavailable</p>
                <a href="<?php echo $direct_link; ?>" target="_blank" 
                   style="background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; display: inline-block;">
                    View Merit Report
                </a>
            </div>
            
            <p style="margin-top: 10px; font-size: 14px; color: #666;">
                Scan this QR code to view your complete merit report
            </p>
            
            <!-- Alternative access methods -->
            <div style="margin-top: 2px; padding-top: 5px; border-top: 1px solid #eee;">
                <p style="margin-bottom: 3px; font-size: 12px; color: #888;">Alternative Access:</p>
                <a href="<?php echo $direct_link; ?>" target="_blank" 
                   style="font-size: 12px; color: #007bff; text-decoration: none; margin-right: 5px;">
                    📱 Direct Link
                </a>
                
            </div>
        </div>
    </div>
</div>

<script>
function handleQRLoad(studentId) {
    // QR code loaded successfully
    document.getElementById('qr-loading-' + studentId).style.display = 'none';
}

function handleQRError(studentId, directLink) {
    // Hide the failed image and show error message
    document.getElementById('qr-image-' + studentId).style.display = 'none';
    document.getElementById('qr-loading-' + studentId).style.display = 'none';
    document.getElementById('qr-error-' + studentId).style.display = 'block';
    
    // Try to reload the QR code after a delay
    setTimeout(function() {
        retryQRGeneration(studentId, directLink);
    }, 3000);
}

function retryQRGeneration(studentId, directLink) {
    // Show loading message
    document.getElementById('qr-error-' + studentId).style.display = 'none';
    document.getElementById('qr-loading-' + studentId).style.display = 'block';
    
    // Try to reload the image
    var img = document.getElementById('qr-image-' + studentId);
    var originalSrc = img.src;
    img.src = originalSrc + '&retry=' + Date.now();
    
    // If it fails again after 5 seconds, show error
    setTimeout(function() {
        if (img.style.display === 'none') {
            document.getElementById('qr-loading-' + studentId).style.display = 'none';
            document.getElementById('qr-error-' + studentId).style.display = 'block';
        }
    }, 5000);
}

function shareReport(url) {
    if (navigator.share) {
        navigator.share({
            title: 'Student Merit Report',
            text: 'View my academic merit report',
            url: url
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(url).then(function() {
            alert('Report link copied to clipboard!');
        }).catch(function() {
            // Final fallback: show the URL
            prompt('Copy this link to share your report:', url);
        });
    }
}
</script>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>