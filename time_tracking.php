<?php
/**
 * Time Tracking Core Functions
 * 
 * This file contains the core functionality for the employee time tracking system
 * including functions to record entry/exit times, calculate work hours, and manage
 * employee status changes when RFID cards are scanned.
 */

require_once 'database.php';

class TimeTracking {
    
    /**
     * Records a new time log entry when an employee scans their RFID card
     * 
     * @param string $rfidUid The RFID card UID
     * @param string $notes Optional notes for this scan
     * @return array Result with status and message
     */
    public static function recordScan($rfidUid, $notes = '') {
        try {
            $pdo = Database::connect();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // First check if this RFID is registered to an employee
            $sql = "SELECT employee_id, name, current_status, employment_status FROM employees WHERE rfid_uid = ?";
            $q = $pdo->prepare($sql);
            $q->execute(array($rfidUid));
            $employee = $q->fetch(PDO::FETCH_ASSOC);
            
            if (!$employee) {
                Database::disconnect();
                return [
                    'status' => 'error',
                    'message' => 'Unregistered RFID card. Please register this card first.',
                    'data' => ['rfid_uid' => $rfidUid]
                ];
            }
            
            // Check if employee is active
            if ($employee['employment_status'] !== 'active') {
                Database::disconnect();
                return [
                    'status' => 'error',
                    'message' => 'Employee account is inactive. Please contact administrator.',
                    'data' => ['employee' => $employee]
                ];
            }
            
            // Determine if this is an entry or exit scan based on current status
            $newStatus = ($employee['current_status'] === 'in') ? 'out' : 'in';
            $logType = ($newStatus === 'in') ? 'entry' : 'exit';
            
            // Begin transaction
            $pdo->beginTransaction();
            
            // Record the time log
            $sql = "INSERT INTO time_logs (employee_id, rfid_uid, log_type, notes) 
                   VALUES (?, ?, ?, ?)";
            $q = $pdo->prepare($sql);
            $q->execute(array(
                $employee['employee_id'],
                $rfidUid,
                $logType,
                $notes
            ));
            
            // Update the employee's current status
            $sql = "UPDATE employees SET current_status = ? WHERE employee_id = ?";
            $q = $pdo->prepare($sql);
            $q->execute(array($newStatus, $employee['employee_id']));
            
            // If this is an exit scan, update the daily record
            if ($logType === 'exit') {
                self::updateDailyRecord($pdo, $employee['employee_id']);
            } 
            // If this is an entry scan and no daily record for today, create one
            else if ($logType === 'entry') {
                $today = date('Y-m-d');
                $sql = "SELECT count(*) as count FROM daily_records 
                       WHERE employee_id = ? AND work_date = ?";
                $q = $pdo->prepare($sql);
                $q->execute(array($employee['employee_id'], $today));
                $result = $q->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] == 0) {
                    // Get current time
                    $now = date('Y-m-d H:i:s');
                    
                    // Create a new daily record
                    $sql = "INSERT INTO daily_records 
                           (employee_id, work_date, first_entry, status) 
                           VALUES (?, ?, ?, ?)";
                    $q = $pdo->prepare($sql);
                    $q->execute(array(
                        $employee['employee_id'],
                        $today,
                        $now,
                        'present'
                    ));
                    
                    // Check if employee is late
                    self::checkLateArrival($pdo, $employee['employee_id'], $now);
                }
            }
            
            // Commit transaction
            $pdo->commit();
            
            Database::disconnect();
            
            return [
                'status' => 'success',
                'message' => ($logType === 'entry') 
                    ? "Welcome, {$employee['name']}! Entry recorded at " . date('h:i A') 
                    : "Goodbye, {$employee['name']}! Exit recorded at " . date('h:i A'),
                'data' => [
                    'employee' => $employee,
                    'log_type' => $logType,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ];
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            Database::disconnect();
            
            return [
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage(),
                'data' => null
            ];
        } catch (Exception $e) {
            Database::disconnect();
            
            return [
                'status' => 'error',
                'message' => 'System error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Updates the daily record for an employee after an exit scan
     * 
     * @param PDO $pdo Database connection
     * @param int $employeeId The employee ID
     * @return bool Success or failure
     */
    private static function updateDailyRecord($pdo, $employeeId) {
        try {
            $today = date('Y-m-d');
            $now = date('Y-m-d H:i:s');
            
            // Get the daily record for today if it exists
            $sql = "SELECT record_id, first_entry FROM daily_records 
                   WHERE employee_id = ? AND work_date = ?";
            $q = $pdo->prepare($sql);
            $q->execute(array($employeeId, $today));
            $record = $q->fetch(PDO::FETCH_ASSOC);
            
            if ($record) {
                // Update existing record with exit time and calculate work hours
                $sql = "UPDATE daily_records SET last_exit = ? WHERE record_id = ?";
                $q = $pdo->prepare($sql);
                $q->execute(array($now, $record['record_id']));
                
                // Calculate and update work hours
                self::calculateWorkHours($pdo, $record['record_id']);
            } else {
                // Create a new record if none exists (unusual case - exit without entry)
                $sql = "INSERT INTO daily_records 
                       (employee_id, work_date, last_exit, status) 
                       VALUES (?, ?, ?, ?)";
                $q = $pdo->prepare($sql);
                $q->execute(array(
                    $employeeId,
                    $today,
                    $now,
                    'present'
                ));
            }
            
            return true;
        } catch (Exception $e) {
            // Log error
            error_log('Error updating daily record: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calculates work hours for a daily record
     * 
     * @param PDO $pdo Database connection
     * @param int $recordId The daily record ID
     * @return bool Success or failure
     */
    private static function calculateWorkHours($pdo, $recordId) {
        try {
            // Get the record with entry and exit times
            $sql = "SELECT first_entry, last_exit FROM daily_records WHERE record_id = ?";
            $q = $pdo->prepare($sql);
            $q->execute(array($recordId));
            $record = $q->fetch(PDO::FETCH_ASSOC);
            
            if (!$record || !$record['first_entry'] || !$record['last_exit']) {
                return false;
            }
            
            // Calculate time difference in hours
            $entry = new DateTime($record['first_entry']);
            $exit = new DateTime($record['last_exit']);
            $interval = $entry->diff($exit);
            
            // Convert to decimal hours (hours + minutes/60)
            $hours = $interval->h + ($interval->i / 60);
            
            // Get all entry/exit pairs for this employee on this day
            $sql = "SELECT dr.employee_id, dr.work_date FROM daily_records dr 
                   WHERE dr.record_id = ?";
            $q = $pdo->prepare($sql);
            $q->execute(array($recordId));
            $dailyRecord = $q->fetch(PDO::FETCH_ASSOC);
            
            if ($dailyRecord) {
                // Get all time logs for this employee on this day
                $sql = "SELECT log_type, timestamp 
                       FROM time_logs 
                       WHERE employee_id = ? 
                       AND DATE(timestamp) = ? 
                       ORDER BY timestamp ASC";
                $q = $pdo->prepare($sql);
                $q->execute(array($dailyRecord['employee_id'], $dailyRecord['work_date']));
                $logs = $q->fetchAll(PDO::FETCH_ASSOC);
                
                // Calculate total work hours considering multiple entry/exit pairs
                $totalHours = 0;
                $entryTime = null;
                
                foreach ($logs as $log) {
                    if ($log['log_type'] === 'entry') {
                        $entryTime = new DateTime($log['timestamp']);
                    } else if ($log['log_type'] === 'exit' && $entryTime) {
                        $exitTime = new DateTime($log['timestamp']);
                        $interval = $entryTime->diff($exitTime);
                        $totalHours += $interval->h + ($interval->i / 60);
                        $entryTime = null;
                    }
                }
                
                // If there's an unclosed entry (no exit scan), don't count it
                
                // Update the record with calculated hours
                $sql = "UPDATE daily_records SET work_hours = ? WHERE record_id = ?";
                $q = $pdo->prepare($sql);
                $q->execute(array(round($totalHours, 2), $recordId));
            }
            
            return true;
        } catch (Exception $e) {
            // Log error
            error_log('Error calculating work hours: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Checks if an employee arrived late and updates their status
     * 
     * @param PDO $pdo Database connection
     * @param int $employeeId The employee ID
     * @param string $entryTime The entry timestamp
     * @return bool Success or failure
     */
    private static function checkLateArrival($pdo, $employeeId, $entryTime) {
        try {
            // Get the configured workday start time from settings
            $sql = "SELECT setting_value FROM settings WHERE setting_key = 'workday_start'";
            $q = $pdo->prepare($sql);
            $q->execute();
            $result = $q->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return false; // No configured start time
            }
            
            $workdayStart = $result['setting_value'];
            
            // Get late threshold from settings
            $sql = "SELECT setting_value FROM settings WHERE setting_key = 'late_threshold'";
            $q = $pdo->prepare($sql);
            $q->execute();
            $result = $q->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return false; // No configured late threshold
            }
            
            $lateThreshold = $result['setting_value'];
            
            // Parse the entry time
            $entryDateTime = new DateTime($entryTime);
            $entryDate = $entryDateTime->format('Y-m-d');
            
            // Create DateTime for start time today
            $startDateTime = new DateTime($entryDate . ' ' . $workdayStart);
            
            // Add late threshold
            $lateTime = clone $startDateTime;
            list($hours, $minutes, $seconds) = explode(':', $lateThreshold);
            $lateTime->modify("+{$hours} hours +{$minutes} minutes +{$seconds} seconds");
            
            // Check if entry time is after late time
            if ($entryDateTime > $lateTime) {
                // Update the daily record to mark as late
                $sql = "UPDATE daily_records 
                       SET status = 'late'
                       WHERE employee_id = ? AND work_date = ?";
                $q = $pdo->prepare($sql);
                $q->execute(array($employeeId, $entryDate));
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            // Log error
            error_log('Error checking late arrival: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gets the current status of an employee
     * 
     * @param int $employeeId The employee ID
     * @return array Result with status and data
     */
    public static function getEmployeeStatus($employeeId) {
        try {
            $pdo = Database::connect();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $sql = "SELECT e.*, 
                   (SELECT log_type FROM time_logs WHERE employee_id = e.employee_id ORDER BY timestamp DESC LIMIT 1) as last_action,
                   (SELECT timestamp FROM time_logs WHERE employee_id = e.employee_id ORDER BY timestamp DESC LIMIT 1) as last_scan_time
                   FROM employees e WHERE e.employee_id = ?";
            $q = $pdo->prepare($sql);
            $q->execute(array($employeeId));
            $employee = $q->fetch(PDO::FETCH_ASSOC);
            
            if (!$employee) {
                Database::disconnect();
                return [
                    'status' => 'error',
                    'message' => 'Employee not found',
                    'data' => null
                ];
            }
            
            // Get today's work record
            $today = date('Y-m-d');
            $sql = "SELECT * FROM daily_records WHERE employee_id = ? AND work_date = ?";
            $q = $pdo->prepare($sql);
            $q->execute(array($employeeId, $today));
            $todayRecord = $q->fetch(PDO::FETCH_ASSOC);
            
            Database::disconnect();
            
            return [
                'status' => 'success',
                'message' => 'Employee status retrieved',
                'data' => [
                    'employee' => $employee,
                    'today_record' => $todayRecord
                ]
            ];
            
        } catch (Exception $e) {
            Database::disconnect();
            
            return [
                'status' => 'error',
                'message' => 'Error retrieving employee status: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Gets work summary for an employee for a specified date range
     * 
     * @param int $employeeId The employee ID
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array Result with status and data
     */
    public static function getWorkSummary($employeeId, $startDate, $endDate) {
        try {
            $pdo = Database::connect();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Get employee details
            $sql = "SELECT * FROM employees WHERE employee_id = ?";
            $q = $pdo->prepare($sql);
            $q->execute(array($employeeId));
            $employee = $q->fetch(PDO::FETCH_ASSOC);
            
            if (!$employee) {
                Database::disconnect();
                return [
                    'status' => 'error',
                    'message' => 'Employee not found',
                    'data' => null
                ];
            }
            
            // Get daily records for the date range
            $sql = "SELECT * FROM daily_records 
                   WHERE employee_id = ? AND work_date BETWEEN ? AND ?
                   ORDER BY work_date ASC";
            $q = $pdo->prepare($sql);
            $q->execute(array($employeeId, $startDate, $endDate));
            $records = $q->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate summary statistics
            $totalDays = 0;
            $presentDays = 0;
            $lateDays = 0;
            $absentDays = 0;
            $totalHours = 0;
            
            // Calculate the number of working days in the date range
            $weekendDays = self::getWeekendDays($pdo);
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
            $end->modify('+1 day'); // Include the end date
            
            $interval = new DateInterval('P1D');
            $dateRange = new DatePeriod($start, $interval, $end);
            
            foreach ($dateRange as $date) {
                // Skip weekend days
                if (in_array($date->format('w'), $weekendDays)) {
                    continue;
                }
                $totalDays++;
            }
            
            // Process the records
            foreach ($records as $record) {
                if ($record['status'] === 'present') {
                    $presentDays++;
                } else if ($record['status'] === 'late') {
                    $lateDays++;
                    $presentDays++; // Late is still present
                } else if ($record['status'] === 'absent') {
                    $absentDays++;
                }
                
                $totalHours += $record['work_hours'];
            }
            
            // Calculate absent days (days with no record)
            $absentDays = $totalDays - $presentDays;
            
            Database::disconnect();
            
            return [
                'status' => 'success',
                'message' => 'Work summary retrieved',
                'data' => [
                    'employee' => $employee,
                    'date_range' => [
                        'start' => $startDate,
                        'end' => $endDate
                    ],
                    'summary' => [
                        'total_days' => $totalDays,
                        'present_days' => $presentDays,
                        'late_days' => $lateDays,
                        'absent_days' => $absentDays,
                        'total_hours' => round($totalHours, 2),
                        'average_hours' => $presentDays > 0 ? round($totalHours / $presentDays, 2) : 0
                    ],
                    'records' => $records
                ]
            ];
            
        } catch (Exception $e) {
            Database::disconnect();
            
            return [
                'status' => 'error',
                'message' => 'Error retrieving work summary: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Gets the configured weekend days from settings
     * 
     * @param PDO $pdo Database connection
     * @return array Array of weekend day numbers (0=Sunday, 6=Saturday)
     */
    private static function getWeekendDays($pdo) {
        try {
            $sql = "SELECT setting_value FROM settings WHERE setting_key = 'weekend_days'";
            $q = $pdo->prepare($sql);
            $q->execute();
            $result = $q->fetch(PDO::FETCH_ASSOC);
            
            if ($result && !empty($result['setting_value'])) {
                return explode(',', $result['setting_value']);
            }
            
            // Default: Saturday and Sunday
            return ['0', '6'];
        } catch (Exception $e) {
            // Log error
            error_log('Error getting weekend days: ' . $e->getMessage());
            return ['0', '6']; // Default
        }
    }
    
    /**
     * Gets employees currently present in the workplace
     * 
     * @return array Result with status and data
     */
    public static function getPresentEmployees() {
        try {
            $pdo = Database::connect();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $sql = "SELECT e.*, 
                   (SELECT timestamp FROM time_logs 
                    WHERE employee_id = e.employee_id AND log_type = 'entry' 
                    ORDER BY timestamp DESC LIMIT 1) as entry_time
                   FROM employees e 
                   WHERE e.current_status = 'in' AND e.employment_status = 'active'
                   ORDER BY name ASC";
            $q = $pdo->prepare($sql);
            $q->execute();
            $employees = $q->fetchAll(PDO::FETCH_ASSOC);
            
            Database::disconnect();
            
            return [
                'status' => 'success',
                'message' => count($employees) . ' employees currently present',
                'data' => $employees
            ];
            
        } catch (Exception $e) {
            Database::disconnect();
            
            return [
                'status' => 'error',
                'message' => 'Error retrieving present employees: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Gets all active employees with their current status
     * 
     * @return array Result with status and data
     */
    public static function getAllEmployees() {
        try {
            $pdo = Database::connect();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $sql = "SELECT e.*, 
                   (SELECT timestamp FROM time_logs 
                    WHERE employee_id = e.employee_id 
                    ORDER BY timestamp DESC LIMIT 1) as last_scan
                   FROM employees e 
                   WHERE e.employment_status = 'active'
                   ORDER BY name ASC";
            $q = $pdo->prepare($sql);
            $q->execute();
            $employees = $q->fetchAll(PDO::FETCH_ASSOC);
            
            Database::disconnect();
            
            return [
                'status' => 'success',
                'message' => count($employees) . ' active employees',
                'data' => $employees
            ];
            
        } catch (Exception $e) {
            Database::disconnect();
            
            return [
                'status' => 'error',
                'message' => 'Error retrieving employees: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Gets the detailed time logs for an employee for a specific date
     * 
     * @param int $employeeId The employee ID
     * @param string $date The date to get logs for (YYYY-MM-DD)
     * @return array Result with status and data
     */
    public static function getDailyTimeLogs($employeeId, $date) {
        try {
            $pdo = Database::connect();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Get employee details
            $sql = "SELECT * FROM employees WHERE employee_id = ?";
            $q = $pdo->prepare($sql);
            $q->execute(array($employeeId));
            $employee = $q->fetch(PDO::FETCH_ASSOC);
            
            if (!$employee) {
                Database::disconnect();
                return [
                    'status' => 'error',
                    'message' => 'Employee not found',
                    'data' => null
                ];
            }
            
            // Get time logs for the specified date
            $sql = "SELECT * FROM time_logs 
                   WHERE employee_id = ? AND DATE(timestamp) = ?
                   ORDER BY timestamp ASC";
            $q = $pdo->prepare($sql);
            $q->execute(array($employeeId, $date));
            $logs = $q->fetchAll(PDO::FETCH_ASSOC);
            
            // Get daily record summary
            $sql = "SELECT * FROM daily_records 
                   WHERE employee_id = ? AND work_date = ?";
            $q = $pdo->prepare($sql);
            $q->execute(array($employeeId, $date));
            $dailyRecord = $q->fetch(PDO::FETCH_ASSOC);
            
            Database::disconnect();
            
            return [
                'status' => 'success',
                'message' => 'Time logs retrieved',
                'data' => [
                    'employee' => $employee,
                    'date' => $date,
                    'logs' => $logs,
                    'daily_record' => $dailyRecord
                ]
            ];
            
        } catch (Exception $e) {
            Database::disconnect();
            
            return [
                'status' => 'error',
                'message' => 'Error retrieving time logs: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Manually adds a time log entry (for admin use)
     * 
     * @param int $employeeId The employee ID
     * @param string $logType The log type (entry/exit)
     * @param string $timestamp The timestamp (YYYY-MM-DD HH:MM:SS)
     * @param string $notes Optional notes
     * @return array Result with status and message
     */
    public static function addManualTimeLog($employeeId, $logType, $timestamp, $notes = 'Manual entry') {
        try {
            $pdo = Database::connect();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if employee exists
            $sql = "SELECT * FROM employees WHERE employee_id = ?";
            $q = $pdo->prepare($sql);
            $q->execute(array($employeeId));
            $employee = $q->fetch(PDO::FETCH_ASSOC);
            
            if (!$employee) {
                Database::disconnect();
                return [
                    'status' => 'error',
                    'message' => 'Employee not found',
                    'data' => null
                ];
            }
            
            // Begin transaction
            $pdo->beginTransaction();
            
            // Add the time log
            $sql = "INSERT INTO time_logs (employee_id, rfid_uid, log_type, timestamp, notes) 
                   VALUES (?, ?, ?, ?, ?)";
            $q = $pdo->prepare($sql);
            $q->execute(array(
                $employeeId,
                $employee['rfid_uid'],
                $logType,
                $timestamp,
                $notes
            ));
            
            // Update employee status if this is the latest log
            $currentTimestamp = date('Y-m-d H:i:s');
            $logTimestamp = new DateTime($timestamp);
            $now = new DateTime();
            
            // Only update current status if the log is for today
            if ($logTimestamp->format('Y-m-d') === $now->format('Y-m-d')) {
                // Get the latest log after this addition
                $sql = "SELECT log_type FROM time_logs 
                       WHERE employee_id = ? 
                       ORDER BY timestamp DESC LIMIT 1";
                $q = $pdo->prepare($sql);
                $q->execute(array($employeeId));
                $latestLog = $q->fetch(PDO::FETCH_ASSOC);
                
                // Update status based on latest log
                if ($latestLog) {
                    $newStatus = ($latestLog['log_type'] === 'entry') ? 'in' : 'out';
                    $sql = "UPDATE employees SET current_status = ? WHERE employee_id = ?";
                    $q = $pdo->prepare($sql);
                    $q->execute(array($newStatus, $employeeId));
                }
            }
            
            // Get date from timestamp for daily record
            $logDate = date('Y-m-d', strtotime($timestamp));
            
            // Update or create daily record
            if ($logType === 'entry') {
                // Check if daily record exists for this date
                $sql = "SELECT count(*) as count FROM daily_records 
                       WHERE employee_id = ? AND work_date = ?";
                $q = $pdo->prepare($sql);
                $q->execute(array($employeeId, $logDate));
                $result = $q->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] == 0) {
                    // Create a new daily record
                    $sql = "INSERT INTO daily_records 
                           (employee_id, work_date, first_entry, status) 
                           VALUES (?, ?, ?, ?)";
                    $q = $pdo->prepare($sql);
                    $q->execute(array(
                        $employeeId,
                        $logDate,
                        $timestamp,
                        'present'
                    ));
                    
                    // Check if this was a late entry
                    self::checkLateArrival($pdo, $employeeId, $timestamp);
                } else {
                    // Update existing record if first_entry is null or later than this entry
                    $sql = "UPDATE daily_records 
                           SET first_entry = CASE 
                               WHEN first_entry IS NULL THEN ? 
                               WHEN first_entry > ? THEN ? 
                               ELSE first_entry 
                           END 
                           WHERE employee_id = ? AND work_date = ?";
                    $q = $pdo->prepare($sql);
                    $q->execute(array(
                        $timestamp,
                        $timestamp,
                        $timestamp,
                        $employeeId,
                        $logDate
                    ));
                }
            } else if ($logType === 'exit') {
                // Check if daily record exists for this date
                $sql = "SELECT record_id FROM daily_records 
                       WHERE employee_id = ? AND work_date = ?";
                $q = $pdo->prepare($sql);
                $q->execute(array($employeeId, $logDate));
                $record = $q->fetch(PDO::FETCH_ASSOC);
                
                if ($record) {
                    // Update existing record with exit time
                    $sql = "UPDATE daily_records 
                           SET last_exit = ? 
                           WHERE record_id = ?";
                    $q = $pdo->prepare($sql);
                    $q->execute(array($timestamp, $record['record_id']));
                    
                    // Recalculate work hours
                    self::calculateWorkHours($pdo, $record['record_id']);
                } else {
                    // Create a new record if none exists
                    $sql = "INSERT INTO daily_records 
                           (employee_id, work_date, last_exit, status) 
                           VALUES (?, ?, ?, ?)";
                    $q = $pdo->prepare($sql);
                    $q->execute(array(
                        $employeeId,
                        $logDate,
                        $timestamp,
                        'present'
                    ));
                }
            }
            
            // Commit transaction
            $pdo->commit();
            
            Database::disconnect();
            
            return [
                'status' => 'success',
                'message' => 'Time log added successfully',
                'data' => [
                    'employee_id' => $employeeId,
                    'log_type' => $logType,
                    'timestamp' => $timestamp
                ]
            ];
            
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            Database::disconnect();
            
            return [
                'status' => 'error',
                'message' => 'Error adding time log: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Gets company-wide attendance summary for a specified date
     * 
     * @param string $date The date to get summary for (YYYY-MM-DD)
     * @return array Result with status and data
     */
    public static function getDailyAttendanceSummary($date) {
        try {
            $pdo = Database::connect();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Get total active employees
            $sql = "SELECT COUNT(*) as total FROM employees WHERE employment_status = 'active'";
            $q = $pdo->prepare($sql);
            $q->execute();
            $totalResult = $q->fetch(PDO::FETCH_ASSOC);
            $totalEmployees = $totalResult['total'];
            
            // Get attendance counts for the day
            $sql = "SELECT 
                   COUNT(*) as total_records,
                   SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                   SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                   SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                   SUM(work_hours) as total_hours
                   FROM daily_records
                   WHERE work_date = ?";
            $q = $pdo->prepare($sql);
            $q->execute(array($date));
            $summary = $q->fetch(PDO::FETCH_ASSOC);
            
            // Calculate absent employees (no record for the day)
            $absentEmployees = $totalEmployees - $summary['total_records'];
            if ($absentEmployees < 0) $absentEmployees = 0;
            
            // Get employees currently present (status = 'in')
            $sql = "SELECT COUNT(*) as current_present FROM employees 
                   WHERE current_status = 'in' AND employment_status = 'active'";
            $q = $pdo->prepare($sql);
            $q->execute();
            $presentResult = $q->fetch(PDO::FETCH_ASSOC);
            $currentlyPresent = $presentResult['current_present'];
            
            // Get department attendance breakdown
            $sql = "SELECT 
                   e.department,
                   COUNT(DISTINCT e.employee_id) as total_in_dept,
                   COUNT(DISTINCT dr.employee_id) as present_in_dept,
                   SUM(CASE WHEN dr.status = 'late' THEN 1 ELSE 0 END) as late_in_dept
                   FROM employees e
                   LEFT JOIN daily_records dr ON e.employee_id = dr.employee_id AND dr.work_date = ?
                   WHERE e.employment_status = 'active'
                   GROUP BY e.department";
            $q = $pdo->prepare($sql);
            $q->execute(array($date));
            $departmentBreakdown = $q->fetchAll(PDO::FETCH_ASSOC);
            
            Database::disconnect();
            
            return [
                'status' => 'success',
                'message' => 'Attendance summary for ' . $date,
                'data' => [
                    'date' => $date,
                    'total_employees' => $totalEmployees,
                    'present' => (int)$summary['present'] + (int)$summary['late'],
                    'late' => (int)$summary['late'],
                    'absent' => $absentEmployees,
                    'currently_present' => $currentlyPresent,
                    'total_hours' => round($summary['total_hours'], 2),
                    'department_breakdown' => $departmentBreakdown
                ]
            ];
            
        } catch (Exception $e) {
            Database::disconnect();
            
            return [
                'status' => 'error',
                'message' => 'Error retrieving attendance summary: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
?>