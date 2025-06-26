<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is event advisor or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'event_advisor' && $_SESSION['role'] !== 'admin')) {
    header('Location: /web/WEB_group_2_project/module1/login.php');
    exit();
}

$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
if ($role === 'student') {
    include_once 'student_dashboard.php';
} elseif ($role === 'coordinator' || $role === 'petakom_coordinator') {
    include_once 'coordinator_dashboard.php';
} elseif ($role === 'advisor' || $role === 'event_advisor') {
    include_once 'advisor_dashboard.php';
}

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_settings'])) {
            // Update attendance settings
            $qr_expiry_hours = (int)$_POST['qr_expiry_hours'];
            $max_check_distance = (int)$_POST['max_check_distance'];
            $allow_early_checkin = isset($_POST['allow_early_checkin']) ? 1 : 0;
            $early_checkin_minutes = (int)$_POST['early_checkin_minutes'];
            $late_checkin_minutes = (int)$_POST['late_checkin_minutes'];
            $require_checkout = isset($_POST['require_checkout']) ? 1 : 0;
            $auto_checkout_hours = (int)$_POST['auto_checkout_hours'];
            
            // Check if settings exist
            $check_query = "SELECT id FROM attendance_settings WHERE id = 1";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                // Update existing settings
                $update_query = "UPDATE attendance_settings SET 
                    qr_expiry_hours = ?, 
                    max_check_distance = ?, 
                    allow_early_checkin = ?, 
                    early_checkin_minutes = ?, 
                    late_checkin_minutes = ?, 
                    require_checkout = ?, 
                    auto_checkout_hours = ?,
                    updated_at = NOW()
                    WHERE id = 1";
                
                $update_stmt = $db->prepare($update_query);
                $update_stmt->execute([
                    $qr_expiry_hours, 
                    $max_check_distance, 
                    $allow_early_checkin, 
                    $early_checkin_minutes, 
                    $late_checkin_minutes, 
                    $require_checkout, 
                    $auto_checkout_hours
                ]);
            } else {
                // Insert new settings
                $insert_query = "INSERT INTO attendance_settings 
                    (qr_expiry_hours, max_check_distance, allow_early_checkin, early_checkin_minutes, 
                     late_checkin_minutes, require_checkout, auto_checkout_hours, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->execute([
                    $qr_expiry_hours, 
                    $max_check_distance, 
                    $allow_early_checkin, 
                    $early_checkin_minutes, 
                    $late_checkin_minutes, 
                    $require_checkout, 
                    $auto_checkout_hours
                ]);
            }
            
            $message = 'Attendance settings updated successfully!';
        }
        
        if (isset($_POST['reset_settings'])) {
            // Reset to default settings
            $reset_query = "UPDATE attendance_settings SET 
                qr_expiry_hours = 24, 
                max_check_distance = 100, 
                allow_early_checkin = 1, 
                early_checkin_minutes = 30, 
                late_checkin_minutes = 15, 
                require_checkout = 0, 
                auto_checkout_hours = 12,
                updated_at = NOW()
                WHERE id = 1";
            
            $reset_stmt = $db->prepare($reset_query);
            $reset_stmt->execute();
            
            $message = 'Settings reset to default values!';
        }
        
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Fetch current settings
$settings_query = "SELECT * FROM attendance_settings WHERE id = 1";
$settings_stmt = $db->prepare($settings_query);
$settings_stmt->execute();
$settings = $settings_stmt->fetch(PDO::FETCH_ASSOC);

// Default settings if none exist
if (!$settings) {
    $settings = [
        'qr_expiry_hours' => 24,
        'max_check_distance' => 100,
        'allow_early_checkin' => 1,
        'early_checkin_minutes' => 30,
        'late_checkin_minutes' => 15,
        'require_checkout' => 0,
        'auto_checkout_hours' => 12
    ];
}

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_events,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_events,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_events
    FROM events WHERE deleted_at IS NULL";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$attendance_stats_query = "SELECT 
    COUNT(*) as total_attendances,
    COUNT(CASE WHEN check_in_time IS NOT NULL AND check_out_time IS NOT NULL THEN 1 END) as completed_attendances,
    COUNT(CASE WHEN check_in_time IS NOT NULL AND check_out_time IS NULL THEN 1 END) as pending_checkouts
    FROM event_attendance";
$attendance_stats_stmt = $db->prepare($attendance_stats_query);
$attendance_stats_stmt->execute();
$attendance_stats = $attendance_stats_stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-cogs"></i> Attendance Settings</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="attendance_dashboard.php">Attendance</a></li>
                        <li class="breadcrumb-item active">Settings</li>
                    </ol>
                </nav>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Events</h6>
                                    <h3><?php echo number_format($stats['total_events']); ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Active Events</h6>
                                    <h3><?php echo number_format($stats['active_events']); ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-play-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Attendances</h6>
                                    <h3><?php echo number_format($attendance_stats['total_attendances']); ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Pending Checkouts</h6>
                                    <h3><?php echo number_format($attendance_stats['pending_checkouts']); ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-sliders-h"></i> Attendance Configuration
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <!-- QR Code Settings -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-qrcode"></i> QR Code Settings</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="qr_expiry_hours" class="form-label">QR Code Expiry (Hours)</label>
                                            <input type="number" class="form-control" id="qr_expiry_hours" 
                                                   name="qr_expiry_hours" value="<?php echo $settings['qr_expiry_hours']; ?>" 
                                                   min="1" max="168" required>
                                            <div class="form-text">How long the QR code remains valid (1-168 hours)</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Location Settings -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-map-marker-alt"></i> Location Settings</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="max_check_distance" class="form-label">Maximum Check-in Distance (meters)</label>
                                            <input type="number" class="form-control" id="max_check_distance" 
                                                   name="max_check_distance" value="<?php echo $settings['max_check_distance']; ?>" 
                                                   min="10" max="1000" required>
                                            <div class="form-text">Maximum distance allowed from event location (10-1000 meters)</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Check-in Settings -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-sign-in-alt"></i> Check-in Settings</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="allow_early_checkin" 
                                                       name="allow_early_checkin" <?php echo $settings['allow_early_checkin'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="allow_early_checkin">
                                                    Allow Early Check-in
                                                </label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="early_checkin_minutes" class="form-label">Early Check-in Window (Minutes)</label>
                                            <input type="number" class="form-control" id="early_checkin_minutes" 
                                                   name="early_checkin_minutes" value="<?php echo $settings['early_checkin_minutes']; ?>" 
                                                   min="0" max="120">
                                            <div class="form-text">How early participants can check-in before event start</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="late_checkin_minutes" class="form-label">Late Check-in Window (Minutes)</label>
                                            <input type="number" class="form-control" id="late_checkin_minutes" 
                                                   name="late_checkin_minutes" value="<?php echo $settings['late_checkin_minutes']; ?>" 
                                                   min="0" max="120">
                                            <div class="form-text">How late participants can still check-in after event start</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Check-out Settings -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-sign-out-alt"></i> Check-out Settings</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="require_checkout" 
                                                       name="require_checkout" <?php echo $settings['require_checkout'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="require_checkout">
                                                    Require Check-out
                                                </label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="auto_checkout_hours" class="form-label">Auto Check-out After (Hours)</label>
                                            <input type="number" class="form-control" id="auto_checkout_hours" 
                                                   name="auto_checkout_hours" value="<?php echo $settings['auto_checkout_hours']; ?>" 
                                                   min="1" max="24">
                                            <div class="form-text">Automatically check-out participants after this duration</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" name="reset_settings" class="btn btn-outline-secondary" 
                                    onclick="return confirm('Are you sure you want to reset all settings to default values?')">
                                <i class="fas fa-undo"></i> Reset to Default
                            </button>
                            <button type="submit" name="update_settings" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Additional Settings -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tools"></i> System Maintenance
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <i class="fas fa-broom fa-2x text-info mb-2"></i>
                                    <h6>Cleanup Old QR Codes</h6>
                                    <p class="text-muted small">Remove expired QR codes from the system</p>
                                    <button class="btn btn-outline-info btn-sm" onclick="cleanupQRCodes()">
                                        <i class="fas fa-trash"></i> Cleanup
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                    <h6>Auto Check-out</h6>
                                    <p class="text-muted small">Process pending auto check-outs</p>
                                    <button class="btn btn-outline-warning btn-sm" onclick="processAutoCheckouts()">
                                        <i class="fas fa-play"></i> Process
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <i class="fas fa-download fa-2x text-success mb-2"></i>
                                    <h6>Export Settings</h6>
                                    <p class="text-muted small">Download current configuration</p>
                                    <button class="btn btn-outline-success btn-sm" onclick="exportSettings()">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cleanupQRCodes() {
    if (confirm('This will remove all expired QR codes. Continue?')) {
        fetch('ajax/cleanup_qr_codes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('QR codes cleaned up successfully!');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('An error occurred: ' + error);
        });
    }
}

function processAutoCheckouts() {
    if (confirm('This will process all pending auto check-outs. Continue?')) {
        fetch('ajax/process_auto_checkouts.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Processed ${data.count} auto check-outs successfully!`);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('An error occurred: ' + error);
        });
    }
}

function exportSettings() {
    window.location.href = 'ajax/export_settings.php';
}

// Toggle early check-in minutes based on checkbox
document.getElementById('allow_early_checkin').addEventListener('change', function() {
    const earlyCheckinMinutes = document.getElementById('early_checkin_minutes');
    earlyCheckinMinutes.disabled = !this.checked;
    if (!this.checked) {
        earlyCheckinMinutes.value = 0;
    }
});

// Toggle auto checkout hours based on require checkout
document.getElementById('require_checkout').addEventListener('change', function() {
    const autoCheckoutHours = document.getElementById('auto_checkout_hours');
    if (!this.checked) {
        autoCheckoutHours.disabled = true;
        autoCheckoutHours.value = 0;
    } else {
        autoCheckoutHours.disabled = false;
        if (autoCheckoutHours.value == 0) {
            autoCheckoutHours.value = 12;
        }
    }
});

// Initialize form state
document.addEventListener('DOMContentLoaded', function() {
    const allowEarlyCheckin = document.getElementById('allow_early_checkin');
    const requireCheckout = document.getElementById('require_checkout');
    
    // Trigger change events to set initial state
    allowEarlyCheckin.dispatchEvent(new Event('change'));
    requireCheckout.dispatchEvent(new Event('change'));
});
</script>

<?php include '../includes/footer.php'; ?>