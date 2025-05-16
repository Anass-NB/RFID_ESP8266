<?php
/**
 * API Endpoints for Employee Time Tracking System
 * 
 * This file provides JSON API endpoints for the time tracking system,
 * allowing access to employee data, time logs, and attendance information.
 */

require_once 'time_tracking.php';

// Set response headers
header('Content-Type: application/json');

// Get the request endpoint
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

// Process the API request
$response = [];

switch ($endpoint) {
    case 'scan':
        // Process an RFID scan
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['rfid_uid'])) {
            $response = TimeTracking::recordScan($_POST['rfid_uid'], isset($_POST['notes']) ? $_POST['notes'] : '');
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Invalid request. POST method with rfid_uid parameter required.',
                'data' => null
            ];
        }
        break;
        
    case 'employee_status':
        // Get an employee's current status
        if (!empty($_GET['employee_id'])) {
            $response = TimeTracking::getEmployeeStatus($_GET['employee_id']);
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Missing employee_id parameter',
                'data' => null
            ];
        }
        break;
        
    case 'employee_summary':
        // Get work summary for an employee for a date range
        if (!empty($_GET['employee_id']) && !empty($_GET['start_date']) && !empty($_GET['end_date'])) {
            $response = TimeTracking::getWorkSummary(
                $_GET['employee_id'],
                $_GET['start_date'],
                $_GET['end_date']
            );
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Missing required parameters: employee_id, start_date, end_date',
                'data' => null
            ];
        }
        break;
        
    case 'present_employees':
        // Get all employees currently present
        $response = TimeTracking::getPresentEmployees();
        break;
        
    case 'all_employees':
        // Get all active employees
        $response = TimeTracking::getAllEmployees();
        break;
        
    case 'daily_logs':
        // Get time logs for an employee for a specific date
        if (!empty($_GET['employee_id']) && !empty($_GET['date'])) {
            $response = TimeTracking::getDailyTimeLogs(
                $_GET['employee_id'],
                $_GET['date']
            );
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Missing required parameters: employee_id, date',
                'data' => null
            ];
        }
        break;
        
    case 'add_manual_log':
        // Manually add a time log (admin function)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
            !empty($_POST['employee_id']) && 
            !empty($_POST['log_type']) && 
            !empty($_POST['timestamp'])) {
            
            $response = TimeTracking::addManualTimeLog(
                $_POST['employee_id'],
                $_POST['log_type'],
                $_POST['timestamp'],
                isset($_POST['notes']) ? $_POST['notes'] : 'Manual entry'
            );
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Invalid request. POST method with employee_id, log_type, and timestamp required.',
                'data' => null
            ];
        }
        break;
        
    case 'daily_attendance':
        // Get company-wide attendance summary for a date
        if (!empty($_GET['date'])) {
            $response = TimeTracking::getDailyAttendanceSummary($_GET['date']);
        } else {
            // Default to today
            $response = TimeTracking::getDailyAttendanceSummary(date('Y-m-d'));
        }
        break;
        
    default:
        // Unknown endpoint
        $response = [
            'status' => 'error',
            'message' => 'Unknown API endpoint',
            'data' => null,
            'available_endpoints' => [
                'scan (POST)', 
                'employee_status', 
                'employee_summary', 
                'present_employees', 
                'all_employees', 
                'daily_logs', 
                'add_manual_log (POST)', 
                'daily_attendance'
            ]
        ];
        break;
}

// Return the JSON response
echo json_encode($response);
?>