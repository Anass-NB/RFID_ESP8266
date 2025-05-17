<?php
/**
 * Employee Details Page
 * Shows detailed information about a specific employee
 */

// Include header
$pageTitle = "Employee Details";
require_once 'includes/header.php';

// Reset UID container
$Write="<?php $" . "UIDresult=''; " . "echo $" . "UIDresult;" . " ?>";
file_put_contents('UIDContainer.php',$Write);

// Include time tracking functions
require_once 'time_tracking.php';

// Get employee ID from URL
$employeeId = isset($_GET['id']) ? $_GET['id'] : null;

// If no employee ID provided, redirect to employees list
if (!$employeeId) {
    header("Location: user-data.php");
    exit;
}

// Get employee details
$employee = null;
$todayLogs = null;
$weeklySummary = null;

try {
    // Get employee status
    $statusResult = TimeTracking::getEmployeeStatus($employeeId);
    if ($statusResult['status'] === 'success') {
        $employee = $statusResult['data']['employee'];
    }
    
    // Get today's logs
    $today = date('Y-m-d');
    $logsResult = TimeTracking::getDailyTimeLogs($employeeId, $today);
    if ($logsResult['status'] === 'success') {
        $todayLogs = $logsResult['data'];
    }
    
    // Get weekly summary
    $startDate = date('Y-m-d', strtotime('-6 days'));
    $endDate = date('Y-m-d');
    $weeklySummary = TimeTracking::getWorkSummary($employeeId, $startDate, $endDate);
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Format time from datetime
function formatTime($datetime) {
    return date('h:i A', strtotime($datetime));
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-0"><i class="fas fa-user-circle me-2"></i> Employee Details</h3>
        <?php if ($employee): ?>
        <p class="text-muted mb-0">Complete profile and attendance history for <?php echo htmlspecialchars($employee['name']); ?></p>
        <?php endif; ?>
    </div>
    <a href="user-data.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left me-2"></i> Back to Employee List
    </a>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger" role="alert">
        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Error</h4>
        <p class="mb-0"><?php echo $error; ?></p>
    </div>
<?php elseif (!$employee): ?>
    <div class="alert alert-danger" role="alert">
        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Employee Not Found</h4>
        <p class="mb-0">The requested employee information could not be found.</p>
    </div>
<?php else: ?>
    
    <!-- Employee Profile -->
    <div class="app-card mb-4">
        <div class="card-header bg-primary">
            <h4 class="mb-0 text-white"><i class="fas fa-id-card me-2"></i>Employee Profile</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 text-center mb-4 mb-md-0">
                    <!-- Profile image placeholder -->
                    <div class="profile-image mb-3">
                        <?php if (!empty($employee['profile_image'])): ?>
                            <img src="<?php echo $employee['profile_image']; ?>" alt="Profile Image">
                        <?php else: ?>
                            <div class="profile-placeholder">
                                <?php echo substr($employee['name'], 0, 1); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Current status badge -->
                    <div class="status-badge <?php echo ($employee['current_status'] == 'in') ? 'bg-success' : 'bg-danger'; ?> mb-3">
                        <i class="fas <?php echo ($employee['current_status'] == 'in') ? 'fa-check-circle' : 'fa-times-circle'; ?> me-1"></i>
                        <?php echo ($employee['current_status'] == 'in') ? 'CLOCKED IN' : 'CLOCKED OUT'; ?>
                    </div>
                    
                    <!-- Actions -->
                    <div class="d-grid gap-2">
                        <a href="user-data-edit-page.php?id=<?php echo $employee['rfid_uid']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i> Edit Profile
                        </a>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addTimeLogModal">
                            <i class="fas fa-clock me-2"></i> Add Time Log
                        </button>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-6 mb-4 mb-lg-0">
                            <h5 class="border-bottom pb-2 mb-3">Personal Information</h5>
                            <table class="table table-hover">
                                <tr>
                                    <th><i class="fas fa-user me-2 text-primary"></i>Name:</th>
                                    <td class="fw-medium"><?php echo htmlspecialchars($employee['name']); ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-venus-mars me-2 text-primary"></i>Gender:</th>
                                    <td><?php echo htmlspecialchars($employee['gender']); ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-envelope me-2 text-primary"></i>Email:</th>
                                    <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-phone me-2 text-primary"></i>Mobile:</th>
                                    <td><?php echo htmlspecialchars($employee['mobile']); ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-id-card me-2 text-primary"></i>RFID Card:</th>
                                    <td><code><?php echo htmlspecialchars($employee['rfid_uid']); ?></code></td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2 mb-3">Employment Information</h5>
                            <table class="table table-hover">
                                <tr>
                                    <th><i class="fas fa-building me-2 text-primary"></i>Department:</th>
                                    <td><?php echo htmlspecialchars($employee['department'] ?? 'Not assigned'); ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-briefcase me-2 text-primary"></i>Position:</th>
                                    <td><?php echo htmlspecialchars($employee['position'] ?? 'Not assigned'); ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-calendar-alt me-2 text-primary"></i>Hire Date:</th>
                                    <td><?php echo $employee['hire_date'] ? date('M d, Y', strtotime($employee['hire_date'])) : 'Not set'; ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-user-tag me-2 text-primary"></i>Status:</th>
                                    <td>
                                        <span class="badge <?php echo ($employee['employment_status'] == 'active') ? 'bg-success' : 'bg-danger'; ?> p-2">
                                            <i class="fas <?php echo ($employee['employment_status'] == 'active') ? 'fa-check-circle' : 'fa-times-circle'; ?> me-1"></i>
                                            <?php echo strtoupper($employee['employment_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-fingerprint me-2 text-primary"></i>Employee ID:</th>
                                    <td>#<?php echo htmlspecialchars($employee['employee_id']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Today's Activity -->
    <div class="app-card mb-4">
        <div class="card-header bg-primary d-flex justify-content-between align-items-center">
            <h4 class="mb-0 text-white"><i class="fas fa-calendar-day me-2"></i>Today's Activity</h4>
            <span class="badge bg-light text-dark px-3 py-2">
                <i class="fas fa-calendar me-1"></i> <?php echo date('l, F j, Y'); ?>
            </span>
        </div>
        <div class="card-body">
            <?php if ($todayLogs && isset($todayLogs['logs']) && count($todayLogs['logs']) > 0): ?>
                <div class="row">
                    <div class="col-md-6 mb-4 mb-md-0">
                        <div class="app-card">
                            <div class="card-header bg-secondary">
                                <h5 class="mb-0 text-white"><i class="fas fa-list-ul me-2"></i>Time Logs</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Time</th>
                                                <th>Action</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($todayLogs['logs'] as $log): ?>
                                                <tr>
                                                    <td>
                                                        <i class="fas fa-clock text-primary me-1"></i>
                                                        <?php echo formatTime($log['timestamp']); ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo ($log['log_type'] == 'entry') ? 'bg-success' : 'bg-danger'; ?> p-2">
                                                            <i class="fas <?php echo ($log['log_type'] == 'entry') ? 'fa-sign-in-alt' : 'fa-sign-out-alt'; ?> me-1"></i>
                                                            <?php echo ($log['log_type'] == 'entry') ? 'CLOCK IN' : 'CLOCK OUT'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($log['notes'])): ?>
                                                            <i class="fas fa-comment-alt text-muted me-1"></i>
                                                            <?php echo htmlspecialchars($log['notes']); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">No notes</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="app-card h-100">
                            <div class="card-header bg-secondary">
                                <h5 class="mb-0 text-white"><i class="fas fa-chart-pie me-2"></i>Summary</h5>
                            </div>
                            <div class="card-body">
                                <?php if (isset($todayLogs['daily_record'])): ?>
                                    <?php $record = $todayLogs['daily_record']; ?>
                                    <div class="row text-center">
                                        <div class="col-md-6 col-6 mb-4">
                                            <h6 class="text-muted mb-1">First Clock In</h6>
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="fas fa-sign-in-alt text-success me-2 fa-lg"></i>
                                                <h4 class="text-primary mb-0">
                                                    <?php echo $record['first_entry'] ? formatTime($record['first_entry']) : 'N/A'; ?>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-6 mb-4">
                                            <h6 class="text-muted mb-1">Last Clock Out</h6>
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="fas fa-sign-out-alt text-danger me-2 fa-lg"></i>
                                                <h4 class="text-primary mb-0">
                                                    <?php echo $record['last_exit'] ? formatTime($record['last_exit']) : 'N/A'; ?>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-6">
                                            <h6 class="text-muted mb-1">Status</h6>
                                            <span class="badge 
                                                <?php if ($record['status'] == 'present') echo 'bg-success';
                                                      else if ($record['status'] == 'late') echo 'bg-warning text-dark';
                                                      else echo 'bg-danger'; ?>
                                                px-3 py-2 fs-6">
                                                <?php if ($record['status'] == 'present'): ?>
                                                    <i class="fas fa-check-circle me-1"></i>
                                                <?php elseif ($record['status'] == 'late'): ?>
                                                    <i class="fas fa-clock me-1"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-times-circle me-1"></i>
                                                <?php endif; ?>
                                                <?php echo strtoupper($record['status']); ?>
                                            </span>
                                        </div>
                                        <div class="col-md-6 col-6">
                                            <h6 class="text-muted mb-1">Work Hours</h6>
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="fas fa-clock text-primary me-2 fa-lg"></i>
                                                <h4 class="text-primary mb-0">
                                                    <?php echo number_format($record['work_hours'], 2); ?>
                                                    <small class="text-muted">hrs</small>
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <p class="mb-0">No activity summary available for today.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-day fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No activity recorded for today</h5>
                    <p class="mb-4">This employee has not clocked in or out today.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTimeLogModal">
                        <i class="fas fa-plus-circle me-2"></i> Add Manual Time Log
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Weekly Summary -->
    <div class="app-card mb-4">
        <div class="card-header bg-primary">
            <h4 class="mb-0 text-white"><i class="fas fa-calendar-week me-2"></i>Weekly Summary</h4>
        </div>
        <div class="card-body">
            <?php if ($weeklySummary && $weeklySummary['status'] === 'success'): ?>
                <div class="row">
                    <div class="col-md-5 mb-4 mb-md-0">
                        <div class="app-card h-100">
                            <div class="card-header bg-secondary">
                                <h5 class="mb-0 text-white"><i class="fas fa-chart-bar me-2"></i>Weekly Stats</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6 mb-4">
                                        <h6 class="text-muted mb-2">Present Days</h6>
                                        <div class="d-flex align-items-center justify-content-center">
                                            <i class="fas fa-user-check text-success me-2 fa-lg"></i>
                                            <h3 class="text-success mb-0">
                                                <?php echo $weeklySummary['data']['summary']['present_days']; ?>
                                                <small class="text-muted">/ <?php echo $weeklySummary['data']['summary']['total_days']; ?></small>
                                            </h3>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-4">
                                        <h6 class="text-muted mb-2">Late Days</h6>
                                        <div class="d-flex align-items-center justify-content-center">
                                            <i class="fas fa-user-clock text-warning me-2 fa-lg"></i>
                                            <h3 class="text-warning mb-0">
                                                <?php echo $weeklySummary['data']['summary']['late_days']; ?>
                                            </h3>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-4">
                                        <h6 class="text-muted mb-2">Absences</h6>
                                        <div class="d-flex align-items-center justify-content-center">
                                            <i class="fas fa-user-slash text-danger me-2 fa-lg"></i>
                                            <h3 class="text-danger mb-0">
                                                <?php echo $weeklySummary['data']['summary']['absent_days']; ?>
                                            </h3>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-4">
                                        <h6 class="text-muted mb-2">Total Hours</h6>
                                        <div class="d-flex align-items-center justify-content-center">
                                            <i class="fas fa-clock text-primary me-2 fa-lg"></i>
                                            <h3 class="text-primary mb-0">
                                                <?php echo $weeklySummary['data']['summary']['total_hours']; ?>
                                                <small class="text-muted">hrs</small>
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                                
                                <h6 class="text-muted mb-2 text-center">Attendance Rate</h6>
                                <?php 
                                    $attendanceRate = 0;
                                    if ($weeklySummary['data']['summary']['total_days'] > 0) {
                                        $attendanceRate = ($weeklySummary['data']['summary']['present_days'] / $weeklySummary['data']['summary']['total_days']) * 100;
                                    }
                                ?>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo round($attendanceRate); ?>%;" 
                                         aria-valuenow="<?php echo round($attendanceRate); ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                         <?php echo round($attendanceRate); ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-7">
                        <div class="app-card h-100">
                            <div class="card-header bg-secondary">
                                <h5 class="mb-0 text-white"><i class="fas fa-list-alt me-2"></i>Daily Records</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>First In</th>
                                                <th>Last Out</th>
                                                <th>Hours</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                                // Create array of all dates in range
                                                $period = new DatePeriod(
                                                    new DateTime($weeklySummary['data']['date_range']['start']),
                                                    new DateInterval('P1D'),
                                                    new DateTime($weeklySummary['data']['date_range']['end'] . ' +1 day')
                                                );
                                                
                                                // Create associative array of records by date
                                                $recordsByDate = [];
                                                foreach ($weeklySummary['data']['records'] as $record) {
                                                    $recordsByDate[$record['work_date']] = $record;
                                                }
                                            ?>
                                            
                                            <?php foreach ($period as $date): 
                                                $dateStr = $date->format('Y-m-d');
                                                $record = isset($recordsByDate[$dateStr]) ? $recordsByDate[$dateStr] : null;
                                                $dayName = $date->format('D');
                                                $isWeekend = in_array($date->format('w'), [0, 6]); // 0 = Sunday, 6 = Saturday
                                            ?>
                                                <tr <?php if ($isWeekend) echo 'class="table-secondary"'; ?>>
                                                    <td>
                                                        <i class="fas fa-calendar-day text-primary me-1"></i>
                                                        <?php echo $dayName . ', ' . $date->format('M j'); ?>
                                                        <?php if ($isWeekend): ?>
                                                            <span class="badge bg-secondary">Weekend</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($record): ?>
                                                            <span class="badge 
                                                                <?php if ($record['status'] == 'present') echo 'bg-success';
                                                                      else if ($record['status'] == 'late') echo 'bg-warning text-dark';
                                                                      else echo 'bg-danger'; ?> p-2">
                                                                <?php if ($record['status'] == 'present'): ?>
                                                                    <i class="fas fa-check-circle me-1"></i>
                                                                <?php elseif ($record['status'] == 'late'): ?>
                                                                    <i class="fas fa-clock me-1"></i>
                                                                <?php else: ?>
                                                                    <i class="fas fa-times-circle me-1"></i>
                                                                <?php endif; ?>
                                                                <?php echo strtoupper($record['status']); ?>
                                                            </span>
                                                        <?php elseif (!$isWeekend): ?>
                                                            <span class="badge bg-danger p-2">
                                                                <i class="fas fa-times-circle me-1"></i>ABSENT
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary p-2">
                                                                <i class="fas fa-minus-circle me-1"></i>N/A
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($record && $record['first_entry']): ?>
                                                            <i class="fas fa-sign-in-alt text-success me-1"></i>
                                                            <?php echo formatTime($record['first_entry']); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">---</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($record && $record['last_exit']): ?>
                                                            <i class="fas fa-sign-out-alt text-danger me-1"></i>
                                                            <?php echo formatTime($record['last_exit']); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">---</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($record && $record['work_hours'] > 0): ?>
                                                            <span class="fw-medium"><?php echo number_format($record['work_hours'], 2); ?></span>
                                                            <span class="text-muted">hrs</span>
                                                        <?php else: ?>
                                                            <span class="text-muted">0.00 hrs</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-week fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Weekly summary data not available</h5>
                    <p>There are no attendance records for this employee in the past week.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add Time Log Modal -->
    <div class="modal fade" id="addTimeLogModal" tabindex="-1" aria-labelledby="addTimeLogModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addTimeLogModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>Add Manual Time Log
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addTimeLogForm" class="app-form needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="logType" class="form-label">Log Type</label>
                            <select class="form-select" id="logType" name="log_type" required>
                                <option value="entry">Clock In</option>
                                <option value="exit">Clock Out</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="logDate" class="form-label">Date</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                <input type="date" class="form-control" id="logDate" name="log_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="invalid-feedback">Please select a date.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="logTime" class="form-label">Time</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                <input type="time" class="form-control" id="logTime" name="log_time" value="<?php echo date('H:i'); ?>" required>
                            </div>
                            <div class="invalid-feedback">Please specify a time.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="logNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="logNotes" name="notes" rows="3" 
                                placeholder="Reason for manual entry">Manual entry by administrator</textarea>
                        </div>
                        
                        <input type="hidden" name="employee_id" value="<?php echo $employee['employee_id']; ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Log
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
            
            // Form validation
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
            
            // Handle manual time log form submission
            document.getElementById('addTimeLogForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!this.checkValidity()) {
                    return;
                }
                
                const formData = new FormData(this);
                const employeeId = formData.get('employee_id');
                const logType = formData.get('log_type');
                const logDate = formData.get('log_date');
                const logTime = formData.get('log_time');
                const notes = formData.get('notes');
                
                // Create timestamp in format YYYY-MM-DD HH:MM:SS
                const timestamp = `${logDate} ${logTime}:00`;
                
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalBtnHtml = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                
                // Send data to API
                fetch('api.php?endpoint=add_manual_log', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `employee_id=${employeeId}&log_type=${logType}&timestamp=${timestamp}&notes=${encodeURIComponent(notes)}`
                })
                .then(response => response.json())
                .then(data => {
                    // Reset button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                    
                    if (data.status === 'success') {
                        // Show success message
                        alert('Time log added successfully');
                        
                        // Reset form and close modal
                        this.reset();
                        this.classList.remove('was-validated');
                        
                        // Close modal
                        bootstrap.Modal.getInstance(document.getElementById('addTimeLogModal')).hide();
                        
                        // Reload the page to show the new log
                        window.location.reload();
                    } else {
                        // Show error message
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    // Reset button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                    
                    console.error('Error:', error);
                    alert('An error occurred while adding the time log');
                });
            });
        });
    </script>
<?php endif; ?>

<?php
// Include footer
require_once 'includes/footer.php';
?>