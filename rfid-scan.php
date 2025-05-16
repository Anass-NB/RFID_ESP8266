<?php
/**
 * RFID Scan Processing Interface
 * 
 * This is the main interface for RFID scanning. It handles the scan workflow
 * and automatically detects whether an employee is entering or exiting based
 * on their current status.
 */

// Include header with proper page title
$pageTitle = "RFID Scanner";
require_once 'includes/header.php';

// Get current time for display
$currentTime = date('h:i:s A');
$currentDate = date('l, F j, Y');

// Get all present employees for the status display
$presentEmployees = [];
$presentResult = TimeTracking::getPresentEmployees();
if ($presentResult['status'] === 'success') {
    $presentEmployees = $presentResult['data'];
}

// Format time for display
function formatTime($timestamp) {
    return date('h:i A', strtotime($timestamp));
}

// Get company settings
$companyName = "Employee Time Tracking System";
$welcomeMessage = "Please Scan Your RFID Card";
$workdayStart = "09:00 AM";
$workdayEnd = "05:00 PM";

// Get company name from settings if available
$pdo = Database::connect();
$sql = "SELECT setting_value FROM settings WHERE setting_key = 'company_name'";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
    $companyName = $result['setting_value'];
}

// Get workday start/end times from settings if available
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('workday_start', 'workday_end')";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($settings as $setting) {
    if ($setting['setting_key'] === 'workday_start') {
        $workdayStart = date('h:i A', strtotime($setting['setting_value']));
    } elseif ($setting['setting_key'] === 'workday_end') {
        $workdayEnd = date('h:i A', strtotime($setting['setting_value']));
    }
}
Database::disconnect();

// Customize welcome message based on time of day
$hour = (int)date('H');
if ($hour >= 5 && $hour < 12) {
    $welcomeMessage = "Good Morning! Please Scan Your RFID Card";
} elseif ($hour >= 12 && $hour < 17) {
    $welcomeMessage = "Good Afternoon! Please Scan Your RFID Card";
} else {
    $welcomeMessage = "Good Evening! Please Scan Your RFID Card";
}
?>

<!-- Clock Display -->
<div class="text-center mb-4">
    <h3 id="current-time"><?php echo $currentTime; ?></h3>
    <p class="text-muted" id="current-date"><?php echo $currentDate; ?></p>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Scan Card -->
        <div class="app-card scan-card mb-4">
            <div class="card-body text-center">
                <h3 class="welcome-message" id="welcome-message"><?php echo $welcomeMessage; ?></h3>
                
                <div class="scan-animation">
                    <i class="fas fa-id-card scan-icon"></i>
                </div>
                
                <p class="scan-instructions">
                    Place your RFID card or key fob near the reader to clock in or out
                </p>
                
                <p class="text-muted">
                    <i class="far fa-clock"></i> Work Hours: <?php echo $workdayStart; ?> - <?php echo $workdayEnd; ?>
                </p>
                
                <!-- Hidden UID container -->
                <p id="getUID" hidden></p>
                
                <!-- Scan result will be shown here -->
                <div id="scan-result" class="scan-result">
                    <!-- Content will be populated by AJAX -->
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Present Employees List -->
        <div class="app-card mb-4">
            <div class="card-header bg-success">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-users"></i> Currently Present
                    </h5>
                    <span class="badge bg-light text-dark"><?php echo count($presentEmployees); ?></span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="present-employees" id="present-employees" style="max-height: 400px; overflow-y: auto;">
                    <?php if (!empty($presentEmployees)): ?>
                        <?php foreach($presentEmployees as $employee): ?>
                            <div class="employee-row d-flex align-items-center">
                                <?php if (!empty($employee['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($employee['profile_image']); ?>" alt="<?php echo htmlspecialchars($employee['name']); ?>" class="employee-avatar">
                                <?php else: ?>
                                    <div class="employee-initial-small"><?php echo substr($employee['name'], 0, 1); ?></div>
                                <?php endif; ?>
                                
                                <div>
                                    <div class="name"><?php echo htmlspecialchars($employee['name']); ?></div>
                                    <div class="time">
                                        <i class="far fa-clock"></i> 
                                        In since <?php echo formatTime($employee['entry_time']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-user-slash fa-2x mb-3"></i>
                            <p>No employees are currently present</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-tachometer-alt"></i> View Dashboard
                </a>
            </div>
        </div>
        
        <!-- Quick Links -->
        <div class="app-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-link"></i> Quick Links</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="kiosk-mode.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tablet-alt me-2"></i> Kiosk Mode
                    </a>
                    <a href="registration.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-plus me-2"></i> Register New Employee
                    </a>
                    <a href="user-data.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> View All Employees
                    </a>
                    <a href="reports.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar me-2"></i> Generate Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page-specific scripts -->
<script src="js/rfid-scanner.js"></script>

<?php
// Include footer
require_once 'includes/footer.php';
?>