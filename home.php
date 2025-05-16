<?php
// Include header
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
<div class="row align-items-center mb-5">
    <div class="col-lg-6">
        <div class="app-card">
            <div class="card-body">
                <h2 class="mb-4">Welcome to the Employee Time Tracking System</h2>
                <p class="lead">An efficient way to manage employee attendance and monitor work hours using RFID technology.</p>
                <p>This system helps you track employee entry and exit times, calculate work hours, generate reports, and manage employee information - all in one place.</p>
                <div class="d-flex flex-wrap mt-4">
                    <a href="rfid-scan.php" class="btn btn-primary me-2 mb-2">
                        <i class="fas fa-id-card"></i> Scan RFID Card
                    </a>
                    <a href="dashboard.php" class="btn btn-outline-primary me-2 mb-2">
                        <i class="fas fa-tachometer-alt"></i> View Dashboard
                    </a>
                    <a href="reports.php" class="btn btn-outline-primary mb-2">
                        <i class="fas fa-chart-bar"></i> Generate Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="app-card text-center overflow-hidden position-relative">
            <div class="card-body">
                <img src="assets/time-tracking-illustration.svg" alt="Time Tracking Illustration" class="img-fluid" style="max-height: 300px;">
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card present-card">
            <div class="stat-icon"><i class="fas fa-user-check"></i></div>
            <div class="stat-number"><?php echo $presentEmployeesCount; ?></div>
            <div class="stat-label">Employees Present</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card late-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-number"><?php echo $totalEmployeesCount; ?></div>
            <div class="stat-label">Total Employees</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
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
<div class="row mb-4">
    <div class="col-12">
        <div class="app-card">
            <div class="card-header">
                <h3 class="mb-0">System Features</h3>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="d-flex align-items-start">
                            <div class="feature-icon me-3">
                                <i class="fas fa-id-card fs-1 text-primary"></i>
                            </div>
                            <div>
                                <h4>RFID Card Scanning</h4>
                                <p>Quickly clock in and out using RFID cards or key fobs. The system automatically detects entry and exit.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-start">
                            <div class="feature-icon me-3">
                                <i class="fas fa-chart-line fs-1 text-primary"></i>
                            </div>
                            <div>
                                <h4>Attendance Tracking</h4>
                                <p>Track employee attendance, late arrivals, work hours, and generate comprehensive reports.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-start">
                            <div class="feature-icon me-3">
                                <i class="fas fa-tachometer-alt fs-1 text-primary"></i>
                            </div>
                            <div>
                                <h4>Real-time Dashboard</h4>
                                <p>View current employee status, attendance statistics, and department performance in real-time.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-start">
                            <div class="feature-icon me-3">
                                <i class="fas fa-file-alt fs-1 text-primary"></i>
                            </div>
                            <div>
                                <h4>Detailed Reports</h4>
                                <p>Generate and export detailed reports on attendance, work hours, late arrivals, and more.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-start">
                            <div class="feature-icon me-3">
                                <i class="fas fa-users-cog fs-1 text-primary"></i>
                            </div>
                            <div>
                                <h4>Employee Management</h4>
                                <p>Manage employee information, departments, positions, and RFID card assignments.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-start">
                            <div class="feature-icon me-3">
                                <i class="fas fa-mobile-alt fs-1 text-primary"></i>
                            </div>
                            <div>
                                <h4>Responsive Design</h4>
                                <p>Access the system from any device - desktop, tablet, or mobile phone with a fully responsive interface.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Access Buttons -->
<div class="row">
    <div class="col-12">
        <div class="app-card">
            <div class="card-header">
                <h3 class="mb-0">Quick Access</h3>
            </div>
            <div class="card-body">
                <div class="row text-center g-3">
                    <div class="col-md-3 col-sm-6">
                        <a href="rfid-scan.php" class="text-decoration-none">
                            <div class="p-4 rounded bg-light">
                                <i class="fas fa-id-card fs-1 text-primary mb-3"></i>
                                <h5>Scan RFID</h5>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="user-data.php" class="text-decoration-none">
                            <div class="p-4 rounded bg-light">
                                <i class="fas fa-users fs-1 text-primary mb-3"></i>
                                <h5>Employees</h5>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="registration.php" class="text-decoration-none">
                            <div class="p-4 rounded bg-light">
                                <i class="fas fa-user-plus fs-1 text-primary mb-3"></i>
                                <h5>Registration</h5>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="kiosk-mode.php" class="text-decoration-none">
                            <div class="p-4 rounded bg-light">
                                <i class="fas fa-tablet-alt fs-1 text-primary mb-3"></i>
                                <h5>Kiosk Mode</h5>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>