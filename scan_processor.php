<?php
/**
 * Enhanced RFID Scan Processor
 * 
 * This file processes RFID card scans for the employee time tracking system.
 * It automatically detects whether an employee is entering or exiting based on their current status
 * and records the appropriate timestamp.
 */

require_once 'time_tracking.php';
require_once 'database.php';

// Set content type to JSON if this is an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
}

// Get the RFID UID
$UIDresult = "";
if (!empty($_POST["UIDresult"])) {
    $UIDresult = $_POST["UIDresult"];
} else if (isset($UIDresult)) {
    // Value might be set directly in UIDContainer.php
}

// Default response
$response = [
    'status' => 'error',
    'message' => 'No RFID card detected. Please try again.',
    'data' => null
];

// Process the scan if we have a UID
if (!empty($UIDresult)) {
    // Store the UID in UIDContainer.php as the original application does
    $Write = "<?php $" . "UIDresult='" . $UIDresult . "'; " . "echo $" . "UIDresult;" . " ?>";
    file_put_contents('UIDContainer.php', $Write);
    
    // Check if this card is registered to an employee
    $pdo = Database::connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = "SELECT * FROM employees WHERE rfid_uid = ?";
    $q = $pdo->prepare($sql);
    $q->execute(array($UIDresult));
    $employee = $q->fetch(PDO::FETCH_ASSOC);
    Database::disconnect();
    
    if (!$employee) {
        // Card not registered
        $response = [
            'status' => 'error',
            'message' => 'Unregistered RFID card. Please register this card first.',
            'data' => ['rfid_uid' => $UIDresult]
        ];
    } else if ($employee['employment_status'] !== 'active') {
        // Employee account is inactive
        $response = [
            'status' => 'error',
            'message' => 'Employee account is inactive. Please contact administrator.',
            'data' => ['employee' => $employee]
        ];
    } else {
        // Card is registered, process the entry/exit
        $response = TimeTracking::recordScan($UIDresult);
        
        // Generate custom messages based on employee status and time of day
        if ($response['status'] === 'success') {
            $name = $employee['name'];
            $firstName = explode(' ', $name)[0]; // Get first name for friendlier messages
            $hour = (int)date('H');
            $logType = $response['data']['log_type'];
            
            if ($logType === 'entry') {
                // Custom entry messages based on time of day
                if ($hour < 12) {
                    $response['message'] = "Good morning, $firstName! Welcome to work.";
                } else if ($hour < 17) {
                    $response['message'] = "Good afternoon, $firstName! You're now clocked in.";
                } else {
                    $response['message'] = "Good evening, $firstName! You're working late today.";
                }
                
                // Check if employee is late based on workday start time
                $pdo = Database::connect();
                $sql = "SELECT setting_value FROM settings WHERE setting_key = 'workday_start'";
                $q = $pdo->prepare($sql);
                $q->execute();
                $setting = $q->fetch(PDO::FETCH_ASSOC);
                Database::disconnect();
                
                if ($setting) {
                    $workdayStart = $setting['setting_value'];
                    $currentTime = date('H:i:s');
                    
                    if ($currentTime > $workdayStart) {
                        // Get the late threshold
                        $pdo = Database::connect();
                        $sql = "SELECT setting_value FROM settings WHERE setting_key = 'late_threshold'";
                        $q = $pdo->prepare($sql);
                        $q->execute();
                        $thresholdSetting = $q->fetch(PDO::FETCH_ASSOC);
                        Database::disconnect();
                        
                        if ($thresholdSetting) {
                            $lateThreshold = $thresholdSetting['setting_value'];
                            $lateTime = date('H:i:s', strtotime($workdayStart . ' + ' . $lateThreshold));
                            
                            if ($currentTime > $lateTime) {
                                $response['message'] .= " Note: You're late today.";
                            }
                        }
                    }
                }
            } else {
                // Custom exit messages
                if ($hour < 17) {
                    $response['message'] = "Goodbye, $firstName! See you tomorrow.";
                } else {
                    $response['message'] = "Good night, $firstName! Have a pleasant evening.";
                }
                
                // Add work hours summary if available
                $today = date('Y-m-d');
                $pdo = Database::connect();
                $sql = "SELECT work_hours FROM daily_records WHERE employee_id = ? AND work_date = ?";
                $q = $pdo->prepare($sql);
                $q->execute(array($employee['employee_id'], $today));
                $dailyRecord = $q->fetch(PDO::FETCH_ASSOC);
                Database::disconnect();
                
                if ($dailyRecord && isset($dailyRecord['work_hours'])) {
                    $hours = number_format($dailyRecord['work_hours'], 2);
                    $response['message'] .= " You worked $hours hours today.";
                }
            }
        }
    }
}

// Return JSON response if this is an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    echo json_encode($response);
    exit;
}

// Otherwise, include the response data for use in the page
$scanResult = $response;
?>