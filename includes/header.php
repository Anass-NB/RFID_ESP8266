<?php
/**
 * Header Template for Employee Time Tracking System
 * Includes site header, navigation, and common elements
 */

// Reset UID container - only needed for pages that use RFID scanning
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
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $companyName = $result['setting_value'];
    }
} catch (PDOException $e) {
    // Just use the default company name if there's an error
}
Database::disconnect();

// Define the current page for navigation highlighting
$currentPage = basename($_SERVER['PHP_SELF']);

// Function to check if a navigation item is active
function isActive($page) {
    global $currentPage;
    if (is_array($page)) {
        return in_array($currentPage, $page) ? 'active' : '';
    }
    return $currentPage === $page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($companyName); ?> - Employee Time Tracking</title>
    
    <!-- Favicon -->
    <link rel="icon" href="assets/favicon.ico" type="image/x-icon">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <link href="css/modern-styles.css" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="jquery.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="js/bootstrap.bundle.min.js"></script>
    
    <!-- Other page-specific meta tags and styles can be added here -->
</head>
<body>
    <div class="app-container py-4">
        <!-- Header -->
        <header class="app-header text-center">
            <h1 class="app-title"><?php echo htmlspecialchars($companyName); ?></h1>
        </header>
        
        <!-- Main Navigation -->
        <nav class="navbar navbar-expand-lg app-navbar mb-4">
            <div class="container-fluid">
                <a class="navbar-brand d-lg-none" href="home.php">
                    <i class="fas fa-clock"></i> Time Tracker
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('home.php'); ?>" href="home.php">
                                <i class="fas fa-home"></i> Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('user-data.php'); ?>" href="user-data.php">
                                <i class="fas fa-users"></i> Employees
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('registration.php'); ?>" href="registration.php">
                                <i class="fas fa-user-plus"></i> Registration
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive(['rfid-scan.php', 'read-tag.php']); ?>" href="rfid-scan.php">
                                <i class="fas fa-id-card"></i> Scan Card
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive(['dashboard.php', 'admin-dashboard.php', 'employee-dashboard.php']); ?>" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('reports.php'); ?>" href="reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                    </ul>
                    
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-cog"></i> Admin
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                                <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                                <li><a class="dropdown-item" href="admin-users.php">Manage Users</a></li>
                                <li><a class="dropdown-item" href="admin-departments.php">Departments</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Page Content Container -->
        <main class="app-content">
            <!-- Page content will be inserted here -->