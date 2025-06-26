<?php
include '../db_connect.php';

if (!isset($_GET['eventID'])) {
    die('Error: Missing eventID parameter in URL.');
}

$eventID = $_GET['eventID'];

// Get event details
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
            max-width: 700px;
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

        /* QR Code Section Styles */
        .qr-section {
            margin-top: 30px;
            padding: 25px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 10px;
            text-align: center;
            border: 2px solid #e0e6ed;
        }

        .qr-section h3 {
            color: #005baa;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .qr-code-container {
            display: inline-block;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin: 15px 0;
        }

        .qr-code-image {
            display: block;
            max-width: 200px;
            height: auto;
            border-radius: 5px;
        }

        .qr-actions {
            margin-top: 15px;
        }

        .qr-btn {
            display: inline-block;
            margin: 5px;
            padding: 8px 16px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .qr-btn:hover {
            background-color: #218838;
        }

        .qr-btn.secondary {
            background-color: #6c757d;
        }

        .qr-btn.secondary:hover {
            background-color: #545b62;
        }

        .share-section {
            margin-top: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #005baa;
        }

        .share-section h4 {
            color: #005baa;
            margin-bottom: 10px;
        }

        .share-url {
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            word-break: break-all;
            margin: 10px 0;
            border: 1px solid #ced4da;
        }

        .copy-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }

        .copy-btn:hover {
            background-color: #0056b3;
        }

        @media (max-width: 600px) {
            .event-detail-container {
                margin: 20px;
                padding: 20px;
            }
            
            .qr-code-image {
                max-width: 150px;
            }
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
        
        <!-- QR Code Section -->
        <div class="qr-section">
            <h3>📱 Share This Event</h3>
            <p>Scan the QR code below to quickly access this event's details:</p>
            
            <div class="qr-code-container">
                <img src="generate_qr.php?eventID=<?php echo urlencode($eventID); ?>" 
                     alt="QR Code for Event <?php echo htmlspecialchars($eventID); ?>" 
                     class="qr-code-image"
                     onerror="this.style.display='none'; document.getElementById('qr-error').style.display='block';">
                
                <div id="qr-error" style="display:none; color: #d32f2f; padding: 20px;">
                    ❌ Failed to generate QR code<br>
                    <small>Please check if the QR library is properly installed</small>
                </div>
            </div>
            
            <div class="qr-actions">
                <a href="generate_qr.php?eventID=<?php echo urlencode($eventID); ?>" 
                   download="event-<?php echo htmlspecialchars($eventID); ?>-qr.png" 
                   class="qr-btn">
                    💾 Download QR Code
                </a>
                <a href="generate_qr.php?eventID=<?php echo urlencode($eventID); ?>&debug=1" 
                   target="_blank" 
                   class="qr-btn secondary">
                    🔍 Debug Info
                </a>
            </div>
        </div>
        
        <!-- Share Section -->
        <div class="share-section">
            <h4>🔗 Direct Link</h4>
            <p>Share this link to let others view this event:</p>
            <div class="share-url" id="shareUrl">
                <?php 
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $currentUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                echo htmlspecialchars($currentUrl);
                ?>
            </div>
            <button class="copy-btn" onclick="copyToClipboard()">📋 Copy Link</button>
        </div>
        
    <?php else: ?>
        <h1>❌ Event Not Found</h1>
        <p>Sorry, we couldn't find an event with ID: <strong><?php echo htmlspecialchars($eventID); ?></strong></p>
        <p>Please check the event ID and try again.</p>
    <?php endif; ?>

    <a href="event.php" class="back-btn">← Back to Events</a>
</div>

<script>
function copyToClipboard() {
    const shareUrl = document.getElementById('shareUrl').textContent;
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(shareUrl).then(function() {
            showCopySuccess();
        }).catch(function(err) {
            fallbackCopyTextToClipboard(shareUrl);
        });
    } else {
        fallbackCopyTextToClipboard(shareUrl);
    }
}

function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "absolute";
    textArea.style.left = "-999999px";
    
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showCopySuccess();
        } else {
            showCopyError();
        }
    } catch (err) {
        showCopyError();
    }
    
    document.body.removeChild(textArea);
}

function showCopySuccess() {
    const btn = document.querySelector('.copy-btn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '✅ Copied!';
    btn.style.backgroundColor = '#28a745';
    
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.style.backgroundColor = '#007bff';
    }, 2000);
}

function showCopyError() {
    const btn = document.querySelector('.copy-btn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '❌ Failed';
    btn.style.backgroundColor = '#dc3545';
    
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.style.backgroundColor = '#007bff';
    }, 2000);
}

// Check QR code loading
document.addEventListener('DOMContentLoaded', function() {
    const qrImage = document.querySelector('.qr-code-image');
    if (qrImage) {
        qrImage.addEventListener('load', function() {
            console.log('✅ QR code loaded successfully');
        });
        
        qrImage.addEventListener('error', function() {
            console.error('❌ Failed to load QR code');
        });
    }
});
</script>

</body>
</html>