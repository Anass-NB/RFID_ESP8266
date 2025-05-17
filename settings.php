<?php
/**
 * System Settings Page
 * Manage global application settings.php
 */

// Include header with proper page title
$pageTitle = "System Settings";
require_once 'includes/header.php';

// Initialize error/success messages
$successMessage = null;
$errorMessage = null;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Connect to database
        $pdo = Database::connect();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Update settings
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $settingKey = substr($key, 8); // Remove 'setting_' prefix
                
                // Check if setting exists
                $sql = "SELECT COUNT(*) FROM settings WHERE setting_key = ?";
                $q = $pdo->prepare($sql);
                $q->execute(array($settingKey));
                $exists = $q->fetchColumn() > 0;
                
                if ($exists) {
                    // Update existing setting
                    $sql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
                    $q = $pdo->prepare($sql);
                    $q->execute(array($value, $settingKey));
                } else {
                    // Insert new setting
                    $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)";
                    $q = $pdo->prepare($sql);
                    $q->execute(array($settingKey, $value));
                }
            }
        }
        
        Database::disconnect();
        $successMessage = "Settings updated successfully.";
    } catch (PDOException $e) {
        $errorMessage = "Error updating settings: " . $e->getMessage();
    }
}

// Get current settings
$settings = array();
try {
    $pdo = Database::connect();
    $sql = "SELECT setting_key, setting_value FROM settings";
    foreach ($pdo->query($sql) as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    Database::disconnect();
} catch (PDOException $e) {
    $errorMessage = "Error loading settings: " . $e->getMessage();
}

// Set defaults for required settings
$defaults = array(
    'company_name' => 'Employee Time Tracking System',
    'workday_start' => '09:00:00',
    'workday_end' => '17:00:00',
    'grace_period' => '15',
    'weekend_days' => '0,6',  // Sunday (0) and Saturday (6)
    'timezone' => 'UTC',
    'date_format' => 'Y-m-d',
    'time_format' => 'H:i:s'
);

// Merge defaults with actual settings
foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-0"><i class="fas fa-cogs me-2"></i> System Settings</h3>
        <p class="text-muted mb-0">Configure application-wide settings and preferences</p>
    </div>
    <a href="dashboard.php" class="btn btn-outline-primary">
        <i class="fas fa-tachometer-alt me-2"></i> Back to Dashboard
    </a>
</div>

<!-- Notifications -->
<?php if ($successMessage): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> <?php echo $successMessage; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if ($errorMessage): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $errorMessage; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<form method="post" action="settings.php" class="app-form">
    <!-- Company Settings -->
    <div class="app-card mb-4">
        <div class="card-header bg-primary">
            <h5 class="mb-0 text-white"><i class="fas fa-building me-2"></i>Company Settings</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="company_name" class="form-label">Company Name</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-building"></i></span>
                        <input type="text" class="form-control" id="company_name" name="setting_company_name"
                            value="<?php echo htmlspecialchars($settings['company_name']); ?>" required>
                    </div>
                    <div class="form-text">This name will appear in the header and footer of the application.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="timezone" class="form-label">Timezone</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-globe"></i></span>
                        <select class="form-select" id="timezone" name="setting_timezone">
                            <?php
                            $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
                            foreach ($timezones as $tz) {
                                $selected = ($tz === $settings['timezone']) ? 'selected' : '';
                                echo "<option value=\"$tz\" $selected>$tz</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-text">Timezone used for date and time calculations.</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="date_format" class="form-label">Date Format</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                        <select class="form-select" id="date_format" name="setting_date_format">
                            <option value="Y-m-d" <?php if ($settings['date_format'] === 'Y-m-d') echo 'selected'; ?>>
                                YYYY-MM-DD</option>
                            <option value="m/d/Y" <?php if ($settings['date_format'] === 'm/d/Y') echo 'selected'; ?>>
                                MM/DD/YYYY</option>
                            <option value="d/m/Y" <?php if ($settings['date_format'] === 'd/m/Y') echo 'selected'; ?>>
                                DD/MM/YYYY</option>
                            <option value="d.m.Y" <?php if ($settings['date_format'] === 'd.m.Y') echo 'selected'; ?>>
                                DD.MM.YYYY</option>
                        </select>
                    </div>
                    <div class="form-text">Display format for dates throughout the application.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="time_format" class="form-label">Time Format</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-clock"></i></span>
                        <select class="form-select" id="time_format" name="setting_time_format">
                            <option value="H:i:s" <?php if ($settings['time_format'] === 'H:i:s') echo 'selected'; ?>>24
                                Hour (HH:MM:SS)</option>
                            <option value="H:i" <?php if ($settings['time_format'] === 'H:i') echo 'selected'; ?>>24
                                Hour (HH:MM)</option>
                            <option value="h:i:s A"
                                <?php if ($settings['time_format'] === 'h:i:s A') echo 'selected'; ?>>12 Hour (HH:MM:SS
                                AM/PM)</option>
                            <option value="h:i A" <?php if ($settings['time_format'] === 'h:i A') echo 'selected'; ?>>12
                                Hour (HH:MM AM/PM)</option>
                        </select>
                    </div>
                    <div class="form-text">Display format for times throughout the application.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Settings -->
    <div class="app-card mb-4">
        <div class="card-header bg-primary">
            <h5 class="mb-0 text-white"><i class="fas fa-calendar-check me-2"></i>Attendance Settings</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="workday_start" class="form-label">Workday Start Time</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-hourglass-start"></i></span>
                        <input type="time" class="form-control" id="workday_start" name="setting_workday_start"
                            value="<?php echo date('H:i', strtotime($settings['workday_start'])); ?>" required>
                    </div>
                    <div class="form-text">The official start time of the work day.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="workday_end" class="form-label">Workday End Time</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-hourglass-end"></i></span>
                        <input type="time" class="form-control" id="workday_end" name="setting_workday_end"
                            value="<?php echo date('H:i', strtotime($settings['workday_end'])); ?>" required>
                    </div>
                    <div class="form-text">The official end time of the work day.</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="grace_period" class="form-label">Late Arrival Grace Period (minutes)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-stopwatch"></i></span>
                        <input type="number" class="form-control" id="grace_period" name="setting_grace_period"
                            value="<?php echo $settings['grace_period']; ?>" min="0" max="60" required>
                    </div>
                    <div class="form-text">Minutes after workday start when an employee is considered late.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Weekend Days</label>
                    <div>
                        <?php
                        $weekendDays = explode(',', $settings['weekend_days']);
                        $days = array(
                            0 => 'Sunday',
                            1 => 'Monday',
                            2 => 'Tuesday',
                            3 => 'Wednesday',
                            4 => 'Thursday',
                            5 => 'Friday',
                            6 => 'Saturday'
                        );
                        
                        foreach ($days as $key => $day) {
                            $checked = in_array($key, $weekendDays) ? 'checked' : '';
                            echo "<div class=\"form-check form-check-inline\">
                                    <input class=\"form-check-input\" type=\"checkbox\" id=\"weekend_$key\" name=\"weekend_day[]\" value=\"$key\" $checked>
                                    <label class=\"form-check-label\" for=\"weekend_$key\">$day</label>
                                  </div>";
                        }
                        ?>
                    </div>
                    <div class="form-text">Days considered as weekends (no attendance required).</div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Appearance -->
    <div class="app-card mb-4">
        <div class="card-header bg-primary">
            <h5 class="mb-0 text-white"><i class="fas fa-palette me-2"></i>System Appearance</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="primary_color" class="form-label">Primary Color</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-paint-brush"></i></span>
                        <input type="color" class="form-control form-control-color" id="primary_color"
                            name="setting_primary_color"
                            value="<?php echo isset($settings['primary_color']) ? $settings['primary_color'] : '#4361ee'; ?>">
                        <input type="text" class="form-control"
                            value="<?php echo isset($settings['primary_color']) ? $settings['primary_color'] : '#4361ee'; ?>"
                            id="primary_color_text" readonly>
                    </div>
                    <div class="form-text">Primary branding color for the application.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="logo_path" class="form-label">Company Logo</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-image"></i></span>
                        <input type="file" class="form-control" id="logo_path" name="logo_file" accept="image/*"
                            disabled>
                    </div>
                    <div class="form-text">Company logo to display in the header. (Not yet implemented)</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="kiosk_message" class="form-label">Kiosk Welcome Message</label>
                    <textarea class="form-control" id="kiosk_message" name="setting_kiosk_message"
                        rows="3"><?php echo isset($settings['kiosk_message']) ? $settings['kiosk_message'] : 'Please scan your RFID card to clock in or out.'; ?></textarea>
                    <div class="form-text">Message displayed on the kiosk mode screen.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Kiosk Mode Options</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="kiosk_clock" name="setting_kiosk_clock"
                            value="1"
                            <?php echo (isset($settings['kiosk_clock']) && $settings['kiosk_clock'] == '1') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="kiosk_clock">
                            Show digital clock
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="kiosk_employee_photo"
                            name="setting_kiosk_employee_photo" value="1"
                            <?php echo (isset($settings['kiosk_employee_photo']) && $settings['kiosk_employee_photo'] == '1') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="kiosk_employee_photo">
                            Show employee photo when scanned
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="kiosk_auto_fullscreen"
                            name="setting_kiosk_auto_fullscreen" value="1"
                            <?php echo (isset($settings['kiosk_auto_fullscreen']) && $settings['kiosk_auto_fullscreen'] == '1') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="kiosk_auto_fullscreen">
                            Auto-enter fullscreen mode
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Notifications (Future Feature) -->
    <div class="app-card mb-4">
        <div class="card-header bg-secondary">
            <h5 class="mb-0 text-white"><i class="fas fa-envelope me-2"></i>Email Notifications (Coming Soon)</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Email notification settings will be available in a future
                update.
            </div>

            <div class="row opacity-50">
                <div class="col-md-6 mb-3">
                    <label for="smtp_host" class="form-label">SMTP Server</label>
                    <input type="text" class="form-control" id="smtp_host" name="setting_smtp_host" disabled
                        value="<?php echo isset($settings['smtp_host']) ? $settings['smtp_host'] : ''; ?>">
                </div>

                <div class="col-md-6 mb-3">
                    <label for="smtp_port" class="form-label">SMTP Port</label>
                    <input type="number" class="form-control" id="smtp_port" name="setting_smtp_port" disabled
                        value="<?php echo isset($settings['smtp_port']) ? $settings['smtp_port'] : '587'; ?>">
                </div>
            </div>

            <div class="row opacity-50">
                <div class="col-md-6 mb-3">
                    <label for="smtp_user" class="form-label">SMTP Username</label>
                    <input type="text" class="form-control" id="smtp_user" name="setting_smtp_user" disabled
                        value="<?php echo isset($settings['smtp_user']) ? $settings['smtp_user'] : ''; ?>">
                </div>

                <div class="col-md-6 mb-3">
                    <label for="smtp_pass" class="form-label">SMTP Password</label>
                    <input type="password" class="form-control" id="smtp_pass" name="setting_smtp_pass" disabled
                        value="<?php echo isset($settings['smtp_pass']) ? '********' : ''; ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <button type="button" class="btn btn-outline-secondary me-md-2" onclick="location.reload();">
            <i class="fas fa-undo me-2"></i> Reset Changes
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-2"></i> Save Settings
        </button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    // Handle weekend days checkboxes
    const weekendCheckboxes = document.querySelectorAll('input[name="weekend_day[]"]');

    // Update the hidden input field when checkboxes change
    function updateWeekendDays() {
        const selectedDays = [];
        weekendCheckboxes.forEach(function(checkbox) {
            if (checkbox.checked) {
                selectedDays.push(checkbox.value);
            }
        });

        // Create or update hidden field for weekend days
        let hiddenField = document.getElementById('setting_weekend_days');
        if (!hiddenField) {
            hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.id = 'setting_weekend_days';
            hiddenField.name = 'setting_weekend_days';
            document.querySelector('form').appendChild(hiddenField);
        }
        hiddenField.value = selectedDays.join(',');
    }

    weekendCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', updateWeekendDays);
    });

    // Initialize weekend days hidden field
    updateWeekendDays();

    // Sync color picker with text input
    const primaryColor = document.getElementById('primary_color');
    const primaryColorText = document.getElementById('primary_color_text');

    primaryColor.addEventListener('input', function() {
        primaryColorText.value = this.value;
    });
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>