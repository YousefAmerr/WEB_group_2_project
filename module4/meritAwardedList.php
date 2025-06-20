<?php
session_start();

include '../db_connect.php';


$username = $_SESSION['username'] ?? '';

$studentID = '';
if (!empty($username)) {
    $student_query = "SELECT studentID FROM student WHERE StuUsername = ?";
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

if (empty($studentID)) {
    header("Location: ../module1/login.php");
    exit();
}

// Get selected role filter and search query
$roleFilter = isset($_GET['category']) ? $_GET['category'] : '';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

$meritQuery = "SELECT DISTINCT ma.*, e.eventName, e.eventLocation, e.eventLevel, e.semester, 
               COALESCE(app.role_type, mc.roleType, 'participant') as role_type,
               COALESCE(app.submissionDate, mc.claim_date, (
                   SELECT MIN(att_inner.attendanceDate) 
                   FROM attendancecslot acs_inner 
                   JOIN attendance att_inner ON acs_inner.attendanceID = att_inner.attendanceID 
                   WHERE acs_inner.studentID = ma.studentID 
                   AND att_inner.eventID = ma.eventID 
                   AND acs_inner.status = 'Present'
               )) as submissionDate
               FROM meritaward ma
               JOIN event e ON ma.eventID = e.eventID
               LEFT JOIN meritapplication app ON ma.studentID = app.studentID AND ma.eventID = app.eventID
               LEFT JOIN meritclaim mc ON ma.studentID = mc.studentID AND ma.eventID = mc.eventID AND mc.status = 'Approved'
               WHERE ma.studentID = ?";

// Add role filter if selected
$params = [$studentID];
$paramTypes = "s";

if (!empty($roleFilter)) {
    $meritQuery .= " AND COALESCE(app.role_type, mc.roleType, 'participant') = ?";
    $params[] = $roleFilter;
    $paramTypes .= "s";
}

// Add search filter if provided
if (!empty($searchQuery)) {
    $meritQuery .= " AND (e.eventName LIKE ? OR e.eventLocation LIKE ? OR e.eventLevel LIKE ?)";
    $searchParam = "%{$searchQuery}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $paramTypes .= "sss";
}

$meritQuery .= " ORDER BY e.semester DESC, ma.meritPoints DESC";

$stmt = $conn->prepare($meritQuery);
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$meritResult = $stmt->get_result();

// Calculate totals
$totalEventCount = 0;
$grandTotal = 0;
$semester2Count = 0;
$semester2Total = 0;

$meritData = [];
if ($meritResult) {
    while ($row = $meritResult->fetch_assoc()) {
        $meritData[] = $row;
        $totalEventCount++;
        $grandTotal += $row['meritPoints'];

        if ($row['semester'] == '2') {
            $semester2Count++;
            $semester2Total += $row['meritPoints'];
        }
    }
}


include 'merit_functions.php';

calculate_Committee_Main_Committee_Merits();
calculateParticipantMerits();

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
                <div class="filter-container">
                    <div class="search-group">
                        <label for="search">Search Events:</label>
                        <input type="text"
                            id="search"
                            name="search"
                            placeholder="Search by event name, location, or level..."
                            value="<?php echo htmlspecialchars($searchQuery); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="category">Filter by Role:</label>
                        <select id="category" name="category">
                            <option value="">-- Select Role --</option>
                            <option value="committee" <?php echo ($roleFilter == 'committee') ? 'selected' : ''; ?>>Committee</option>
                            <option value="main-committee" <?php echo ($roleFilter == 'main-committee') ? 'selected' : ''; ?>>Main Committee</option>
                            <option value="participant" <?php echo ($roleFilter == 'participant') ? 'selected' : ''; ?>>Participant</option>
                        </select>
                    </div>
                    <div class="button-group">
                        <button type="submit" class="search-btn">
                            <i class="material-icons">search</i>
                            Search
                        </button>
                        <a href="?" class="clear-btn">
                            <i class="material-icons">clear</i>
                            Clear
                        </a>
                    </div>
                </div>
            </form>

            <div class="event-list">
                <?php if (!empty($meritData)) {
                    foreach ($meritData as $merit) {
                        // Normalize role display - handle both 'Participant' and 'participant'
                        $role = $merit['role_type'];
                        if (strtolower($role) === 'participant') {
                            $roleDisplay = 'Participant';
                        } else {
                            $roleDisplay = !empty($role) ? ucwords(str_replace('-', ' ', $role)) : 'N/A';
                        }
                        $eventDate = !empty($merit['submissionDate']) ? date('d/m/Y H:i', strtotime($merit['submissionDate'])) : 'N/A';
                ?>
                        <div class="event-card">
                            <div class="sections_border">
                                <h3>Event Name: <?php echo ($merit['eventName']); ?></h3>



                                <p><strong>Event Level:</strong> <span class="event-level <?php echo ($merit['eventLevel']); ?>"><?php echo $merit['eventLevel']; ?></span></p>
                                <p><strong>Event Location:</strong> <?php echo ($merit['eventLocation']); ?></p>
                                <p><strong>Semester:</strong> <?php echo $merit['semester']; ?></p>
                                <p><strong>Date:</strong> <?php echo $eventDate; ?></p>
                                <p><strong>Role:</strong> <?php echo $roleDisplay; ?></p>
                                <div class="sections_border_space">
                                    <span class="merit-score"><strong>Score:</strong> <?php echo $merit['meritPoints']; ?></span>
                                </div>
                            </div>
                        </div> <?php
                            }
                        } else {
                            if (!empty($searchQuery) || !empty($roleFilter)) {
                                echo '<div class="no-events">';
                                echo '<p><i class="material-icons" style="font-size: 48px; color: #ccc;">search_off</i></p>';
                                echo '<p>No merit awards found matching your search criteria.</p>';
                                if (!empty($searchQuery)) {
                                    echo '<p>Search term: "<strong>' . htmlspecialchars($searchQuery) . '</strong>"</p>';
                                }
                                if (!empty($roleFilter)) {
                                    echo '<p>Role filter: <strong>' . ucwords(str_replace('-', ' ', $roleFilter)) . '</strong></p>';
                                }
                                echo '<p><a href="?" style="color: #007bff; text-decoration: none;">Clear filters to see all results</a></p>';
                                echo '</div>';
                            } else {
                                echo '<div class="no-events"><p>No merit awards found.</p></div>';
                            }
                        }
                                ?>
            </div>
        </div>

        <div class="summary-container">
            <div class="summary-left">
                <h3>Semester 2 Merits Summary</h3>
                <div class="merit_summary_box">
                    <p><strong>Total Events Participated (Semester 2):</strong> <span style="padding-left: 30px;"><?php echo getSemester2EventCount($studentID); ?></span></p>
                    <p><strong>Total Semester 2 Merits Points:</strong> <span style="padding-left: 30px;"><?php echo getSemester2MeritTotal($studentID); ?></span></p>
                    <?php if (!empty($roleFilter)): ?>
                        <p><strong>Filtered by Role:</strong> <span style="padding-left: 30px;"><?php echo ucwords(str_replace('-', ' ', $roleFilter)); ?></span></p>
                    <?php endif; ?>
                    <?php if (!empty($searchQuery)): ?>
                        <p><strong>Search Query:</strong> <span style="padding-left: 30px;">"<?php echo htmlspecialchars($searchQuery); ?>"</span></p>
                        <p><strong>Search Results:</strong> <span style="padding-left: 30px;"><?php echo count($meritData); ?> events found</span></p>
                    <?php endif; ?>
                </div>
            </div>


            <!-- QR Code Section -->
            <div class="summary-right">
                <h3>Scan for Complete Merit Report</h3>
                <div class="qr_box">
                    <?php
                    // Ensure $studentID is defined and set to a valid value
                    $studentID = isset($studentID) ? $studentID : null;
                    if ($studentID === null) {
                        echo "Error: Student ID is not set.";
                        return;
                    }

                    $qr_image_path = "generate_qr.php?student_id=" . urlencode($studentID) . "&display=true";
                    $direct_link = "student_info.php?student_id=" . urlencode($studentID);
                    ?>
                    <div style="text-align: center; padding: 20px;">
                        <div id="qr-container-<?php echo $studentID; ?>">
                            <img id="qr-image-<?php echo $studentID; ?>"
                                src="<?php echo $qr_image_path; ?>"
                                alt="QR Code for Student Merit Report"
                                style="max-width: 200px; max-height: 200px; border: 2px solid #ddd; border-radius: 8px;"
                                onload="handleQRLoad('<?php echo $studentID; ?>')"
                                onerror="handleQRError('<?php echo $studentID; ?>', '<?php echo $direct_link; ?>')">
                        </div>

                        <div id="qr-loading-<?php echo $studentID; ?>" style="display: none; padding: 20px; border: 2px solid #ddd; border-radius: 8px; background-color: #f0f8ff;">
                            <p style="color: #666; margin: 0;">Generating QR Code...</p>
                        </div>

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
        </div>
    </div>
    <script>
        function handleQRLoad(studentId) {
            // QR code loaded successfully
            console.log('QR code loaded for student:', studentId);
        }

        function handleQRError(studentId, directLink) {
            console.log('QR code failed to load for student:', studentId);
            document.getElementById('qr-container-' + studentId).style.display = 'none';
            document.getElementById('qr-error-' + studentId).style.display = 'block';
        }

        // Auto-refresh QR code if it fails to load initially
        document.addEventListener('DOMContentLoaded', function() {
            const qrImages = document.querySelectorAll('img[id^="qr-image-"]');
            qrImages.forEach(function(img) {
                img.addEventListener('error', function() {
                    // Retry loading QR code after 2 seconds
                    setTimeout(function() {
                        const currentSrc = img.src;
                        img.src = currentSrc + (currentSrc.includes('?') ? '&' : '?') + 'retry=' + Date.now();
                    }, 2000);
                });
            });

            // Search functionality enhancements
            const searchInput = document.getElementById('search');
            const categorySelect = document.getElementById('category');

            // Real-time search with debounce
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    if (searchInput.value.length >= 3 || searchInput.value.length === 0) {
                        // Auto-submit form when user types 3+ characters or clears the search
                        document.querySelector('.border_filter').submit();
                    }
                }, 500); // 500ms delay
            });

            // Auto-submit when category changes
            categorySelect.addEventListener('change', function() {
                document.querySelector('.border_filter').submit();
            });

            // Handle Enter key in search input
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.querySelector('.border_filter').submit();
                }
            });

            // Highlight search results
            const searchQuery = '<?php echo addslashes($searchQuery); ?>';
            if (searchQuery) {
                highlightSearchTerms(searchQuery);
            }
        });

        function highlightSearchTerms(query) {
            if (!query) return;

            const eventCards = document.querySelectorAll('.event-card');
            eventCards.forEach(function(card) {
                const textElements = card.querySelectorAll('h3, p');
                textElements.forEach(function(element) {
                    const regex = new RegExp(`(${query})`, 'gi');
                    element.innerHTML = element.innerHTML.replace(regex, '<mark style="background-color: #fff59d; padding: 1px 2px; border-radius: 2px;">$1</mark>');
                });
            });
        }
    </script>
</body>

</html>
<?php
$conn->close();
?>