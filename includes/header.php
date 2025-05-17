<?php
/**
 * Header Template for Employee Time Tracking System
 * Includes site header, navigation, and common elements
 */

// Reset UID container – only needed for pages that use RFID scanning
if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
    $Write = "<?php $" . "UIDresult=''; " . "echo $" . "UIDresult;" . " ?>";
file_put_contents('UIDContainer.php', $Write);
}

// Include database connection
require_once 'database.php';

// Get company name from settings
$companyName = "Employee Time Tracking System";
$pdo = Database::connect();
$sql = "SELECT setting_value FROM settings WHERE setting_key = 'company_name'";
try {
$stmt = $pdo->prepare($sql);
$stmt->execute();
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
$companyName = $row['setting_value'];
}
} catch (PDOException $e) {
// fallback to default
}
Database::disconnect();

// Determine current page for active nav
$currentPage = basename($_SERVER['PHP_SELF']);
function isActive($page) {
global $currentPage;
return is_array($page)
? in_array($currentPage, $page) ? 'active' : ''
: ($currentPage === $page ? 'active' : '');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($companyName) ?> &mdash; Time Tracker</title>

    <link rel="icon" href="assets/favicon.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/enhanced-styles.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <script src="jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous">
    </script>
    <script src="js/rfid-enhanced.js"></script>
</head>

<body class="bg-light font-poppins">
    <div class="container-fluid">
        <!-- Top Bar -->
        <div class="d-flex align-items-center justify-content-between py-3">
            <div class="d-flex align-items-center">
                <img src="assets/company-logo.png" alt="<?= htmlspecialchars($companyName) ?>" height="40" class="me-2">
                <h1 class="h5 mb-0 text-primary fw-bold"><?= htmlspecialchars($companyName) ?></h1>
            </div>
            <span class="badge bg-primary d-none d-lg-inline-block">RFID Enabled</span>
        </div>

        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm rounded mb-4">
            <div class="container-fluid px-0">
                <a class="navbar-brand d-lg-none" href="home.php">
                    <i class="fas fa-clock me-1"></i> Time Tracker
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?= isActive('home.php') ?>" href="home.php"><i
                                    class="fas fa-home me-1"></i>Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= isActive('user-data.php') ?>" href="user-data.php"><i
                                    class="fas fa-users me-1"></i>Employees</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= isActive('registration.php') ?>" href="registration.php"><i
                                    class="fas fa-user-plus me-1"></i>Registration</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= isActive(['rfid-scan.php','read-tag.php']) ?>"
                                href="rfid-scan.php"><i class="fas fa-id-card me-1"></i>Scan Card</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= isActive(['dashboard.php','admin-dashboard.php','employee-dashboard.php']) ?>"
                                href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= isActive('reports.php') ?>" href="reports.php"><i
                                    class="fas fa-chart-bar me-1"></i>Reports</a>
                        </li>
                    </ul>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?= isActive(['settings.php','admin-users.php','admin-departments.php']) ?>"
                                href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-cog me-1"></i>Admin
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="settings.php"><i
                                            class="fas fa-sliders-h me-2"></i>Settings</a></li>
                                <li><a class="dropdown-item" href="admin-users.php"><i
                                            class="fas fa-user-shield me-2"></i>Manage Users</a></li>
                                <li><a class="dropdown-item" href="admin-departments.php"><i
                                            class="fas fa-building me-2"></i>Departments</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i
                                            class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main>
            <!-- (Dashboard, Forms, etc. will be injected here) -->