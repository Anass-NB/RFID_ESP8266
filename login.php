<?php
/**
 * Login Page
 * 
 * This file provides the login interface for the Employee Time Tracking System.
 */

// Start session
session_start();

// Check if already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Process login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get username and password from form
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        require_once 'database.php';
        
        try {
            $pdo = Database::connect();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if users table exists
            $userTableExists = false;
            try {
                $result = $pdo->query("SELECT 1 FROM users LIMIT 1");
                $userTableExists = true;
            } catch (Exception $e) {
                // Users table doesn't exist
                $userTableExists = false;
            }
            
            if ($userTableExists) {
                // Check user credentials
                $sql = "SELECT * FROM users WHERE username = ?";
                $q = $pdo->prepare($sql);
                $q->execute(array($username));
                $user = $q->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, $user['password'])) {
                    // Successful login
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    if ($user['employee_id']) {
                        $_SESSION['employee_id'] = $user['employee_id'];
                    }
                    
                    // Update last login time
                    $sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
                    $q = $pdo->prepare($sql);
                    $q->execute(array($user['user_id']));
                    
                    // Redirect to dashboard
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $error = 'Invalid username or password';
                }
            } else {
                // For testing/demo: allow login with admin/admin if no users table exists
                if ($username === 'admin' && $password === 'admin') {
                    $_SESSION['user_id'] = 1;
                    $_SESSION['username'] = 'admin';
                    $_SESSION['role'] = 'admin';
                    
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $error = 'Invalid username or password';
                }
            }
            
            Database::disconnect();
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Employee Time Tracking System</title>
    
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
    
    <style>
        body {
            background-color: #f5f7fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 20px;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo img {
            max-width: 200px;
            height: auto;
        }
        
        .app-card {
            border-radius: 10px;
        }
        
        .app-card .card-body {
            padding: 2rem;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <img src="assets/modern-logo.png" alt="Time Tracking System Logo">
        </div>
        
        <div class="app-card">
            <div class="card-body">
                <h4 class="text-center mb-4">Login to Your Account</h4>
                
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <form method="post" action="login.php" class="app-form">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required autofocus>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="#" class="text-primary">Forgot Password?</a>
                </div>
            </div>
        </div>
        
        <div class="login-footer">
            <p>For demo purposes, use: <strong>admin / admin</strong></p>
            <p>&copy; <?php echo date('Y'); ?> Employee Time Tracking System</p>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>