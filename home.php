
<?php
// Home.php

require_once 'includes/header.php';

// Get present employees count
$presentEmployeesCount = 0;
$pdo = Database::connect();
$sql = "SELECT COUNT(*) AS count FROM employees WHERE current_status = 'in' AND employment_status = 'active'";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
    $presentEmployeesCount = $result['count'];
}

// Get total employees count
$totalEmployeesCount = 0;
$sql = "SELECT COUNT(*) AS count FROM employees WHERE employment_status = 'active'";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
    $totalEmployeesCount = $result['count'];
}

// Get company information
$companyInfo = [];
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('company_name', 'workday_start', 'workday_end')";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($settings as $setting) {
    $companyInfo[$setting['setting_key']] = $setting['setting_value'];
}
Database::disconnect();

// Format work hours for display
$workdayStart = isset($companyInfo['workday_start']) ? date('h:i A', strtotime($companyInfo['workday_start'])) : '09:00 AM';
$workdayEnd = isset($companyInfo['workday_end']) ? date('h:i A', strtotime($companyInfo['workday_end'])) : '05:00 PM';
?>

<!-- Welcome Banner -->
<div class="row mb-4">
    <div class="col-lg-7">
        <div class="welcome-banner">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2>Welcome to the Employee Time Tracking System</h2>
                    <p class="lead">The smart way to manage attendance with RFID technology</p>
                    <p>Track employee attendance, monitor work hours, generate reports, and manage employee information all in one place.</p>
                    
                    <div class="d-flex flex-wrap mt-4">
                        <a href="rfid-scan.php" class="btn btn-lg me-2 mb-2">
                            <i class="fas fa-id-card me-2"></i> Scan RFID Card
                        </a>
                        <a href="dashboard.php" class="btn btn-lg me-2 mb-2">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                    </div>
                </div>
                <div class="col-md-4 d-none d-md-block">
                    <div class="welcome-banner-icon">
                        <i class="fas fa-clock fa-5x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="app-card overflow-hidden">
            <div class="card-body p-0">
                <img src="assets/time-tracking-illustration.png" alt="Time Tracking Illustration" class="img-fluid w-100">
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-5">
    <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
        <div class="stat-card present-card">
            <div class="stat-icon"><i class="fas fa-user-check"></i></div>
            <div class="stat-number"><?php echo $presentEmployeesCount; ?></div>
            <div class="stat-label">Employees Present</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
        <div class="stat-card late-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-number"><?php echo $totalEmployeesCount; ?></div>
            <div class="stat-label">Total Employees</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
        <div class="stat-card hours-card">
            <div class="stat-icon"><i class="far fa-clock"></i></div>
            <div class="stat-number"><?php echo $workdayStart; ?></div>
            <div class="stat-label">Workday Start</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card absent-card">
            <div class="stat-icon"><i class="far fa-clock"></i></div>
            <div class="stat-number"><?php echo $workdayEnd; ?></div>
            <div class="stat-label">Workday End</div>
        </div>
    </div>
</div>

<!-- System Features -->
<div class="app-card mb-5">
    <div class="card-header">
        <h4 class="mb-0"><i class="fas fa-star me-2"></i>System Features</h4>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-id-card text-primary"></i>
                    </div>
                    <h5>RFID Card Scanning</h5>
                    <p>Quickly clock in and out using RFID cards or key fobs. The system automatically detects entry and exit.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-chart-line text-primary"></i>
                    </div>
                    <h5>Attendance Tracking</h5>
                    <p>Track employee attendance, late arrivals, work hours, and generate comprehensive reports.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-tachometer-alt text-primary"></i>
                    </div>
                    <h5>Real-time Dashboard</h5>
                    <p>View current employee status, attendance statistics, and department performance in real-time.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-file-alt text-primary"></i>
                    </div>
                    <h5>Detailed Reports</h5>
                    <p>Generate and export detailed reports on attendance, work hours, late arrivals, and more.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-users-cog text-primary"></i>
                    </div>
                    <h5>Employee Management</h5>
                    <p>Manage employee information, departments, positions, and RFID card assignments.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-mobile-alt text-primary"></i>
                    </div>
                    <h5>Responsive Design</h5>
                    <p>Access the system from any device - desktop, tablet, or mobile phone with a fully responsive interface.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Access -->
<div class="app-card">
    <div class="card-header">
        <h4 class="mb-0"><i class="fas fa-th-large me-2"></i>Quick Access</h4>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3 col-sm-6">
                <a href="rfid-scan.php" class="text-decoration-none">
                    <div class="quick-access-btn">
                        <i class="fas fa-id-card"></i>
                        <h5>Scan RFID</h5>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="user-data.php" class="text-decoration-none">
                    <div class="quick-access-btn">
                        <i class="fas fa-users"></i>
                        <h5>Employees</h5>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="registration.php" class="text-decoration-none">
                    <div class="quick-access-btn">
                        <i class="fas fa-user-plus"></i>
                        <h5>Registration</h5>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="kiosk-mode.php" class="text-decoration-none">
                    <div class="quick-access-btn">
                        <i class="fas fa-tablet-alt"></i>
                        <h5>Kiosk Mode</h5>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>