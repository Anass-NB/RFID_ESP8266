<?php
/**
 * RFID Scanner Kiosk Mode
 * 
 * This is a simplified interface for RFID scanning designed for kiosk touchscreens.
 * It shows a fullscreen interface with minimal distractions, perfect for mounting
 * at entry/exit points.
 */

// Reset UID container
$Write="<?php $" . "UIDresult=''; " . "echo $" . "UIDresult;" . " ?>";
file_put_contents('UIDContainer.php',$Write);

// Include required files
require_once 'time_tracking.php';
require_once 'database.php';

// Get company settings
$companyName = "Employee Time Tracking System";
$logoUrl = ""; // Default: no logo

// Get company name from settings if available
$pdo = Database::connect();
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('company_name', 'company_logo')";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
Database::disconnect();

foreach ($settings as $setting) {
    if ($setting['setting_key'] === 'company_name') {
        $companyName = $setting['setting_value'];
    } elseif ($setting['setting_key'] === 'company_logo') {
        $logoUrl = $setting['setting_value'];
    }
}

// Get current date and time
$currentTime = date('h:i:s A');
$currentDate = date('l, F j, Y');

// Customize welcome message based on time of day
$hour = (int)date('H');
if ($hour >= 5 && $hour < 12) {
    $welcomeMessage = "Good Morning!";
} elseif ($hour >= 12 && $hour < 17) {
    $welcomeMessage = "Good Afternoon!";
} else {
    $welcomeMessage = "Good Evening!";
}

// Get present employee count
$presentCount = 0;
$presentResult = TimeTracking::getPresentEmployees();
if ($presentResult['status'] === 'success') {
    $presentCount = count($presentResult['data']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="utf-8">
    <title>RFID Kiosk - <?php echo htmlspecialchars($companyName); ?></title>
    
    <!-- Stylesheets -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <!-- Scripts -->
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="jquery.min.js"></script>
    <script src="js/rfid-scanner.js"></script>
    
    <style>
        /* Kiosk-specific styles */
        body, html {
            height: 100%;
            margin: 0;
            overflow: hidden;
            background-color: #f8f9fa;
        }
        
        .kiosk-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .kiosk-header {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .company-logo {
            height: 50px;
            margin-right: 15px;
        }
        
        .kiosk-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .kiosk-footer {
            background-color: #343a40;
            color: white;
            padding: 10px 20px;
            text-align: center;
        }
        
        .clock-display {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .current-time {
            font-size: 5rem;
            font-weight: 700;
            margin: 0;
            color: #333;
        }
        
        .current-date {
            font-size: 1.5rem;
            color: #666;
            margin: 0;
        }
        
        .scan-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        .welcome-message {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 20px;
        }
        
        .scan-animation {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: #f1f1f1;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .scan-animation::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 4px solid #007bff;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(0.95);
                opacity: 1;
            }
            70% {
                transform: scale(1.1);
                opacity: 0.3;
            }
            100% {
                transform: scale(0.95);
                opacity: 1;
            }
        }
        
        .scan-icon {
            color: #007bff;
            font-size: 4rem;
        }
        
        .scan-instructions {
            font-size: 1.25rem;
            margin: 20px 0;
            color: #666;
        }
        
        .scan-result {
            min-height: 200px;
            margin-top: 30px;
        }
        
        .status-info {
            margin-top: 20px;
            font-size: 1.25rem;
            color: #666;
        }
        
        .status-count {
            font-weight: bold;
            color: #28a745;
        }
        
        /* Responsive adjustments */
        @media (max-height: 700px) {
            .current-time {
                font-size: 3rem;
            }
            .current-date {
                font-size: 1.2rem;
            }
            .welcome-message {
                font-size: 1.8rem;
            }
            .scan-animation {
                width: 100px;
                height: 100px;
                margin-bottom: 20px;
            }
            .scan-icon {
                font-size: 2.5rem;
            }
            .scan-instructions {
                font-size: 1rem;
                margin: 10px 0;
            }
        }
        
        /* Fullscreen button */
        .fullscreen-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            color: white;
            background-color: rgba(0,0,0,0.5);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .fullscreen-btn:hover {
            background-color: rgba(0,0,0,0.7);
        }
        
        /* Sound elements (hidden) */
        audio {
            display: none;
        }
    </style>
</head>

<body>
    <!-- Audio elements for scan sounds -->
    <audio id="scan-sound" src="sounds/scan.mp3"></audio>
    <audio id="success-sound" src="sounds/success.mp3"></audio>
    <audio id="error-sound" src="sounds/error.mp3"></audio>
    
    <!-- Fullscreen button -->
    <button class="fullscreen-btn" id="fullscreen-toggle">
        <i class="fas fa-expand"></i>
    </button>
    
    <div class="kiosk-container">
        <!-- Header -->
        <div class="kiosk-header">
            <div class="d-flex align-items-center">
                <?php if (!empty($logoUrl)): ?>
                    <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Company Logo" class="company-logo">
                <?php endif; ?>
                <h2 class="mb-0"><?php echo htmlspecialchars($companyName); ?></h2>
            </div>
            <div class="date-display">
                <h5 id="current-date-small"><?php echo $currentDate; ?></h5>
            </div>
        </div>
        
        <!-- Main content -->
        <div class="kiosk-body">
            <!-- Clock Display -->
            <div class="clock-display">
                <p class="current-time" id="current-time"><?php echo $currentTime; ?></p>
                <p class="current-date" id="current-date"><?php echo $currentDate; ?></p>
            </div>
            
            <!-- Scan Card -->
            <div class="scan-card">
                <h1 class="welcome-message" id="welcome-message"><?php echo $welcomeMessage; ?></h1>
                <p class="scan-instructions">Please scan your RFID card to clock in or out</p>
                
                <div class="scan-animation">
                    <i class="fas fa-id-card scan-icon"></i>
                </div>
                
                <!-- Hidden UID container -->
                <p id="getUID" hidden></p>
                
                <!-- Scan result will be shown here -->
                <div id="scan-result" class="scan-result">
                    <!-- Content will be populated by AJAX -->
                </div>
                
                <div class="status-info">
                    Currently <span class="status-count" id="present-count"><?php echo $presentCount; ?></span> employees are present
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="kiosk-footer">
            <p class="mb-0">
                <i class="far fa-clock"></i> Scan your card when entering or leaving the workplace
                <span class="float-end">Time Tracking System</span>
            </p>
        </div>
    </div>
    
    <script>
        // Fullscreen toggle functionality
        document.getElementById('fullscreen-toggle').addEventListener('click', function() {
            if (!document.fullscreenElement) {
                // Enter fullscreen
                document.documentElement.requestFullscreen().catch(err => {
                    console.log(`Error attempting to enable fullscreen: ${err.message}`);
                });
                this.innerHTML = '<i class="fas fa-compress"></i>';
            } else {
                // Exit fullscreen
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                    this.innerHTML = '<i class="fas fa-expand"></i>';
                }
            }
        });
        
        // Hide fullscreen button after 5 seconds
        setTimeout(function() {
            document.getElementById('fullscreen-toggle').style.opacity = '0.3';
        }, 5000);
        
        // Show button again on mouse movement
        document.addEventListener('mousemove', function() {
            document.getElementById('fullscreen-toggle').style.opacity = '1';
            
            // Hide again after 3 seconds
            setTimeout(function() {
                document.getElementById('fullscreen-toggle').style.opacity = '0.3';
            }, 3000);
        });
    </script>
</body>
</html>