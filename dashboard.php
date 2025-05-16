<?php
/**
 * Main Dashboard Page
 * Shows attendance overview and key metrics
 */

// Include header with proper page title
$pageTitle = "Dashboard";
require_once 'includes/header.php';
require_once 'time_tracking.php';

// Get today's date
$today = date('Y-m-d');

// Get attendance summary for today
$attendanceSummary = TimeTracking::getDailyAttendanceSummary($today);

// Get all employees and their status
$employeesResult = TimeTracking::getAllEmployees();
$employees = $employeesResult['status'] === 'success' ? $employeesResult['data'] : [];

// Get present employees
$presentResult = TimeTracking::getPresentEmployees();
$presentEmployees = $presentResult['status'] === 'success' ? $presentResult['data'] : [];
?>

<!-- Page Header with Date and Time -->
<div class="row mb-4">
    <div class="col-md-6">
        <h3><i class="fas fa-tachometer-alt me-2"></i> Dashboard</h3>
    </div>
    <div class="col-md-6 text-end">
        <h5 id="current-date" class="text-muted mb-1"></h5>
        <h3 id="current-time" class="mb-0"></h3>
    </div>
</div>

<!-- Today's Summary Stats -->
<?php if ($attendanceSummary['status'] === 'success'): ?>
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card present-card">
            <div class="stat-icon"><i class="fas fa-user-check"></i></div>
            <div class="stat-number"><?php echo $attendanceSummary['data']['present']; ?></div>
            <div class="stat-label">Present Today</div>
            <div class="stat-label">of <?php echo $attendanceSummary['data']['total_employees']; ?></div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card late-card">
            <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
            <div class="stat-number"><?php echo $attendanceSummary['data']['late']; ?></div>
            <div class="stat-label">Late Arrivals</div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card absent-card">
            <div class="stat-icon"><i class="fas fa-user-slash"></i></div>
            <div class="stat-number"><?php echo $attendanceSummary['data']['absent']; ?></div>
            <div class="stat-label">Absent Today</div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card hours-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-number"><?php echo $attendanceSummary['data']['total_hours']; ?></div>
            <div class="stat-label">Hours Worked</div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Department Summary and Chart -->
<div class="row mb-4">
    <div class="col-lg-8">
        <!-- Attendance Visualization -->
        <div class="app-card mb-4">
            <div class="card-header bg-primary">
                <h5 class="mb-0 text-white">Today's Attendance</h5>
            </div>
            <div class="card-body">
                <canvas id="attendanceChart" height="250"></canvas>
            </div>
        </div>
        
        <?php if ($attendanceSummary['status'] === 'success' && isset($attendanceSummary['data']['department_breakdown'])): ?>
        <!-- Department Breakdown -->
        <div class="app-card">
            <div class="card-header bg-primary">
                <h5 class="mb-0 text-white">Department Attendance</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th class="text-center">Present</th>
                                <th class="text-center">Late</th>
                                <th class="text-center">Absent</th>
                                <th>Attendance %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceSummary['data']['department_breakdown'] as $dept): 
                                $present = $dept['present_in_dept'] ?? 0;
                                $total = $dept['total_in_dept'] ?? 0;
                                $late = $dept['late_in_dept'] ?? 0;
                                $absent = $total - $present;
                                $percentage = $total > 0 ? round(($present / $total) * 100) : 0;
                            ?>
                            <tr>
                                <td>
                                    <a href="admin-dashboard.php?department=<?php echo urlencode($dept['department'] ?? 'Unassigned'); ?>">
                                        <?php echo $dept['department'] ?? 'Unassigned'; ?>
                                    </a>
                                </td>
                                <td class="text-center"><?php echo $present; ?></td>
                                <td class="text-center"><?php echo $late; ?></td>
                                <td class="text-center"><?php echo $absent; ?></td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $percentage; ?>%;" 
                                             aria-valuenow="<?php echo $percentage; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                             <?php echo $percentage; ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="reports.php" class="btn btn-primary">
                    <i class="fas fa-chart-bar me-2"></i> View Detailed Reports
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <!-- Currently Present Employees -->
        <div class="app-card mb-4">
            <div class="card-header bg-success">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white">
                        <i class="fas fa-users me-2"></i> Currently Present
                    </h5>
                    <span class="badge bg-light text-dark">
                        <?php echo count($presentEmployees); ?>
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($presentEmployees)): ?>
                    <div class="present-employees" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach($presentEmployees as $employee): ?>
                            <div class="employee-row d-flex align-items-center">
                                <?php if (!empty($employee['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($employee['profile_image']); ?>" alt="<?php echo htmlspecialchars($employee['name']); ?>" class="employee-avatar">
                                <?php else: ?>
                                    <div class="employee-initial-small"><?php echo substr($employee['name'], 0, 1); ?></div>
                                <?php endif; ?>
                                
                                <div class="flex-grow-1">
                                    <div class="name"><?php echo htmlspecialchars($employee['name']); ?></div>
                                    <div class="time">
                                        <i class="far fa-clock me-1"></i> 
                                        <?php echo date('h:i A', strtotime($employee['entry_time'])); ?>
                                    </div>
                                </div>
                                
                                <a href="employee-details.php?id=<?php echo $employee['employee_id']; ?>" class="btn btn-sm btn-outline-primary ms-2">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center">
                        <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                        <p>No employees are currently present</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="app-card">
            <div class="card-header bg-primary">
                <h5 class="mb-0 text-white"><i class="fas fa-bolt me-2"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="rfid-scan.php" class="btn btn-lg btn-outline-primary">
                        <i class="fas fa-id-card me-2"></i> Scan RFID Card
                    </a>
                    <a href="registration.php" class="btn btn-lg btn-outline-success">
                        <i class="fas fa-user-plus me-2"></i> Add New Employee
                    </a>
                    <a href="reports.php" class="btn btn-lg btn-outline-info">
                        <i class="fas fa-file-export me-2"></i> Generate Reports
                    </a>
                    <a href="admin-dashboard.php" class="btn btn-lg btn-outline-secondary">
                        <i class="fas fa-cogs me-2"></i> Admin Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="app-card">
    <div class="card-header bg-primary">
        <h5 class="mb-0 text-white"><i class="fas fa-history me-2"></i> Recent Activity</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Time</th>
                        <th>Employee</th>
                        <th>Action</th>
                        <th>Department</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get recent time logs
                    $pdo = Database::connect();
                    $sql = "SELECT tl.*, e.name, e.department, e.profile_image 
                           FROM time_logs tl
                           JOIN employees e ON tl.employee_id = e.employee_id
                           WHERE DATE(tl.timestamp) = CURRENT_DATE
                           ORDER BY tl.timestamp DESC
                           LIMIT 10";
                    $logs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                    Database::disconnect();
                    
                    if (!empty($logs)):
                        foreach ($logs as $log):
                    ?>
                    <tr>
                        <td class="align-middle"><?php echo date('h:i:s A', strtotime($log['timestamp'])); ?></td>
                        <td class="align-middle">
                            <?php if (!empty($log['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($log['profile_image']); ?>" alt="<?php echo htmlspecialchars($log['name']); ?>" 
                                     class="employee-avatar" style="width: 30px; height: 30px;">
                            <?php else: ?>
                                <div class="employee-initial-small" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                    <?php echo substr($log['name'], 0, 1); ?>
                                </div>
                            <?php endif; ?>
                            <span class="ms-2"><?php echo htmlspecialchars($log['name']); ?></span>
                        </td>
                        <td class="align-middle">
                            <span class="badge <?php echo ($log['log_type'] == 'entry') ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo ($log['log_type'] == 'entry') ? 'Clock In' : 'Clock Out'; ?>
                            </span>
                        </td>
                        <td class="align-middle"><?php echo htmlspecialchars($log['department'] ?? 'Not Assigned'); ?></td>
                    </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                    <tr>
                        <td colspan="4" class="text-center py-3">No activity recorded today</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (!empty($logs)): ?>
    <div class="card-footer text-center">
        <a href="reports.php" class="btn btn-primary">
            <i class="fas fa-search me-2"></i> View All Activity
        </a>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Update clock
    function updateClock() {
        const now = new Date();
        document.getElementById('current-time').textContent = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'});
        document.getElementById('current-date').textContent = now.toLocaleDateString([], {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'});
        setTimeout(updateClock, 1000);
    }
    updateClock();
    
    <?php if ($attendanceSummary['status'] === 'success'): ?>
    // Attendance pie chart
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const attendanceChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Present', 'Late', 'Absent'],
            datasets: [{
                data: [
                    <?php echo $attendanceSummary['data']['present'] - $attendanceSummary['data']['late']; ?>,
                    <?php echo $attendanceSummary['data']['late']; ?>,
                    <?php echo $attendanceSummary['data']['absent']; ?>
                ],
                backgroundColor: [
                    '#28a745', // Present
                    '#ffc107', // Late
                    '#dc3545'  // Absent
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: 'Today\'s Attendance'
                }
            }
        }
    });
    <?php endif; ?>
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>