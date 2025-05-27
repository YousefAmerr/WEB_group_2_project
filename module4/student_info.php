<?php
include '../db_connect.php';

// Get student ID from URL parameter
$studentID = $_GET['student_id'] ?? '';

if (empty($studentID)) {
    die("Student ID is required");
}

// Get student information
$student_query = "SELECT studentID, studentName, studentEmail, studentCard FROM student WHERE studentID = ?";
$stmt = $conn->prepare($student_query);

if ($stmt === false) {
    die("Error preparing student query: " . $conn->error);
}

$stmt->bind_param("s", $studentID);
$stmt->execute();
$student_result = $stmt->get_result();

if ($student_result->num_rows === 0) {
    die("Student not found");
}

$student = $student_result->fetch_assoc();
$stmt->close();

// Get total merit points for all semesters - FIXED: Join with event table to get semester
$merit_query = "SELECT 
                    SUM(ma.meritPoints) as total_points,
                    COUNT(ma.ma_ID) as total_events,
                    SUM(CASE WHEN e.semester = '1' THEN ma.meritPoints ELSE 0 END) as semester1_points,
                    SUM(CASE WHEN e.semester = '2' THEN ma.meritPoints ELSE 0 END) as semester2_points,
                    COUNT(CASE WHEN e.semester = '1' THEN 1 END) as semester1_events,
                    COUNT(CASE WHEN e.semester = '2' THEN 1 END) as semester2_events
                FROM meritaward ma 
                JOIN event e ON ma.eventID = e.eventID
                WHERE ma.studentID = ?";

$stmt = $conn->prepare($merit_query);

if ($stmt === false) {
    die("Error preparing merit query: " . $conn->error);
}

$stmt->bind_param("s", $studentID);
$stmt->execute();
$merit_result = $stmt->get_result();
$merit_data = $merit_result->fetch_assoc();
$stmt->close();

// Get detailed merit awards by event
$details_query = "SELECT 
                    e.eventName,
                    ma.role_type,
                    ma.meritPoints,
                    e.semester,
                    ma.award_date
                FROM meritaward ma
                JOIN event e ON ma.eventID = e.eventID
                WHERE ma.studentID = ?
                ORDER BY ma.award_date DESC";

$stmt = $conn->prepare($details_query);

if ($stmt === false) {
    die("Error preparing details query: " . $conn->error);
}

$stmt->bind_param("s", $studentID);
$stmt->execute();
$details_result = $stmt->get_result();

?>

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Merit Information</title>
    <link rel="stylesheet" href="css/student_info.css"/>
    <style>
        /* Fallback CSS in case external CSS file is not available */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 900px;
            margin: 20px auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .student-info {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .student-info h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-label {
            font-weight: bold;
            color: #495057;
        }
        .info-value {
            color: #333;
        }
        .merit-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .merit-card {
            background: linear-gradient(135deg, #ff7a7a 0%, #ff6b6b 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .merit-card:nth-child(2) {
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
        }
        .merit-card:nth-child(3) {
            background: linear-gradient(135deg, #55a3ff 0%, #003d82 100%);
        }
        .merit-card:nth-child(4) {
            background: linear-gradient(135deg, #fd79a8 0%, #e84393 100%);
        }
        .merit-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .merit-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .details-section {
            margin-top: 30px;
        }
        .details-section h3 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .event-item {
            background-color: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .event-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .event-name {
            font-weight: bold;
            color: #333;
            font-size: 18px;
        }
        .event-points {
            background-color: #007bff;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        .event-details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            color: #666;
            font-size: 14px;
        }
        .event-details span {
            display: flex;
            align-items: center;
        }
        .role-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 5px;
        }
        .print-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            margin: 30px auto;
            display: block;
            transition: transform 0.2s;
        }
        .print-btn:hover {
            transform: translateY(-2px);
        }
        @media print {
            body { margin: 0; background: white; }
            .container { box-shadow: none; margin: 0; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Student Merit Report</h1>
            <p>Academic Achievement Summary</p>
        </div>
        
        <div class="content">
            <div class="student-info">
                <h2>Student Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Student ID</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['studentID'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Student Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['studentName'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['studentEmail'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Student Card</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['studentCard'] ?? 'N/A'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="merit-summary">
                <div class="merit-card">
                    <div class="merit-number"><?php echo $merit_data['total_points'] ?? 0; ?></div>
                    <div class="merit-label">Total Merit Points</div>
                </div>
                <div class="merit-card">
                    <div class="merit-number"><?php echo $merit_data['total_events'] ?? 0; ?></div>
                    <div class="merit-label">Total Events</div>
                </div>
                <div class="merit-card">
                    <div class="merit-number"><?php echo $merit_data['semester1_points'] ?? 0; ?></div>
                    <div class="merit-label">Semester 1 Points</div>
                </div>
                <div class="merit-card">
                    <div class="merit-number"><?php echo $merit_data['semester2_points'] ?? 0; ?></div>
                    <div class="merit-label">Semester 2 Points</div>
                </div>
            </div>
            
            <div class="details-section">
                <h3>Event Participation Details</h3>
                <?php if ($details_result->num_rows > 0): ?>
                    <?php while ($detail = $details_result->fetch_assoc()): ?>
                        <div class="event-item">
                            <div class="event-header">
                                <div class="event-name"><?php echo htmlspecialchars($detail['eventName']); ?></div>
                                <div class="event-points"><?php echo $detail['meritPoints']; ?> pts</div>
                            </div>
                            <div class="event-details">
                                <span>Role: <span class="role-badge"><?php echo htmlspecialchars($detail['role_type']); ?></span></span>
                                <span>Semester: <?php echo htmlspecialchars($detail['semester']); ?></span>
                                <span>Date: <?php echo date('M d, Y', strtotime($detail['award_date'])); ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No event participation records found.</p>
                <?php endif; ?>
            </div>
            
            <button class="print-btn" onclick="window.print()">Print Report</button>
        </div>
    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>