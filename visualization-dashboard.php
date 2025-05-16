<?php
/**
 * Visualization Dashboard
 * 
 * This page displays various charts and graphs to visualize attendance patterns,
 * work hours, and other employee metrics.
 */

// Include header with proper page title
$pageTitle = "Data Visualization";
require_once 'includes/header.php';
require_once 'time_tracking.php';

// Get date parameters for filtering
$today = date('Y-m-d');
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : $today;

// Get department filter if set
$departmentFilter = isset($_GET['department']) ? $_GET['department'] : '';

// Get all departments for filter dropdown
$departments = [];
$pdo = Database::connect();
$sql = "SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department";
foreach ($pdo->query($sql) as $row) {
    $departments[] = $row['department'];
}
Database::disconnect();

// Function to generate date array between two dates
function generateDateRange($start, $end) {
    $dates = [];
    $current = strtotime($start);
    $endTime = strtotime($end);
    
    while ($current <= $endTime) {
        $dates[] = date('Y-m-d', $current);
        $current = strtotime('+1 day', $current);
    }
    
    return $dates;
}

// Get attendance data for visualization
$attendanceData = [];
try {
    // Get daily attendance summaries for the date range
    $pdo = Database::connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Total employees count
    $sql = "SELECT COUNT(*) AS total FROM employees WHERE employment_status = 'active'";
    if (!empty($departmentFilter)) {
        $sql .= " AND department = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$departmentFilter]);
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    $totalEmployees = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get date range
    $dateRange = generateDateRange($startDate, $endDate);
    
    // For each date, get attendance summary
    foreach ($dateRange as $date) {
        // Skip future dates
        if (strtotime($date) > strtotime($today)) {
            continue;
        }
        
        // Get attendance for this date
        $sql = "SELECT 
               COUNT(DISTINCT dr.employee_id) as present,
               SUM(CASE WHEN dr.status = 'late' THEN 1 ELSE 0 END) as late
               FROM daily_records dr
               JOIN employees e ON dr.employee_id = e.employee_id
               WHERE dr.work_date = ?";
        
        $params = [$date];
        
        if (!empty($departmentFilter)) {
            $sql .= " AND e.department = ?";
            $params[] = $departmentFilter;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $present = $result['present'] ?? 0;
        $late = $result['late'] ?? 0;
        $absent = $totalEmployees - $present;
        
        // Calculate attendance rate
        $attendanceRate = ($totalEmployees > 0) ? round(($present / $totalEmployees) * 100, 1) : 0;
        
        // Add to attendance data array
        $attendanceData[$date] = [
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'attendance_rate' => $attendanceRate
        ];
    }
    
    // Get department attendance comparison
    $departmentStats = [];
    
    if (count($departments) > 0) {
        $sql = "SELECT 
                e.department,
                COUNT(DISTINCT e.employee_id) as total_in_dept,
                COUNT(DISTINCT CASE WHEN dr.employee_id IS NOT NULL THEN dr.employee_id END) as present_in_dept,
                SUM(CASE WHEN dr.status = 'late' THEN 1 ELSE 0 END) as late_in_dept
                FROM employees e
                LEFT JOIN daily_records dr ON e.employee_id = dr.employee_id AND dr.work_date = ?
                WHERE e.department IS NOT NULL AND e.department != ''
                GROUP BY e.department";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$today]);
        $departmentStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get hourly activity data (entries and exits by hour)
    $hourlyActivity = [
        'entries' => array_fill(0, 12, 0), // 8AM to 7PM
        'exits' => array_fill(0, 12, 0)
    ];
    
    $sql = "SELECT 
           HOUR(timestamp) as hour,
           log_type,
           COUNT(*) as count
           FROM time_logs
           WHERE DATE(timestamp) = ?
           GROUP BY HOUR(timestamp), log_type
           ORDER BY HOUR(timestamp)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$today]);
    $hourResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($hourResults as $hourData) {
        $hour = $hourData['hour'];
        $logType = $hourData['log_type'];
        $count = $hourData['count'];
        
        // Map 24-hour time to our 8AM-7PM index (0-11)
        $index = $hour - 8;
        if ($index >= 0 && $index < 12) {
            if ($logType === 'entry') {
                $hourlyActivity['entries'][$index] = $count;
            } else {
                $hourlyActivity['exits'][$index] = $count;
            }
        }
    }
    
    // Get punctuality data for the last 5 workdays
    $punctualityData = [];
    $workdays = [];
    $currentDay = strtotime($today);
    $count = 0;
    
    while (count($workdays) < 5 && $count < 10) {
        $dayOfWeek = date('w', $currentDay);
        
        // Skip weekends (0 = Sunday, 6 = Saturday)
        if ($dayOfWeek != 0 && $dayOfWeek != 6) {
            $dateStr = date('Y-m-d', $currentDay);
            $workdays[] = date('D', $currentDay); // Day abbreviation (Mon, Tue, etc.)
            
            // Get on-time vs late counts
            $sql = "SELECT 
                   COUNT(CASE WHEN dr.status = 'present' THEN 1 END) as on_time,
                   COUNT(CASE WHEN dr.status = 'late' THEN 1 END) as late
                   FROM daily_records dr
                   JOIN employees e ON dr.employee_id = e.employee_id
                   WHERE dr.work_date = ?";
            
            $params = [$dateStr];
            
            if (!empty($departmentFilter)) {
                $sql .= " AND e.department = ?";
                $params[] = $departmentFilter;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $punctualityData[] = [
                'date' => $dateStr,
                'day' => date('D', $currentDay),
                'on_time' => $result['on_time'] ?? 0,
                'late' => $result['late'] ?? 0
            ];
        }
        
        $currentDay = strtotime('-1 day', $currentDay);
        $count++;
    }
    
    // Reverse to get chronological order
    $punctualityData = array_reverse($punctualityData);
    
    Database::disconnect();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Get attendance for today to show in summary
$todaySummary = $attendanceData[$today] ?? [
    'present' => 0,
    'late' => 0,
    'absent' => $totalEmployees
];
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3><i class="fas fa-chart-bar me-2"></i> Attendance Visualization</h3>
    <a href="reports.php" class="btn btn-primary">
        <i class="fas fa-file-export me-2"></i> Generate Reports
    </a>
</div>

<!-- Filters and Date Range -->
<div class="app-card mb-4">
    <div class="card-body">
        <form method="GET" action="visualization-dashboard.php" class="row g-3">
            <div class="col-md-4">
                <label for="department" class="form-label">Department</label>
                <select class="form-select" id="department" name="department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>" 
                                <?php if ($departmentFilter === $dept) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" 
                       value="<?php echo $startDate; ?>" max="<?php echo $today; ?>">
            </div>
            
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" 
                       value="<?php echo $endDate; ?>" max="<?php echo $today; ?>">
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i> Apply
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle me-2"></i> Error: <?php echo htmlspecialchars($error); ?>
    </div>
<?php else: ?>

<!-- Today's Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stat-card present-card">
            <div class="stat-icon"><i class="fas fa-user-check"></i></div>
            <div class="stat-number"><?php echo $todaySummary['present']; ?></div>
            <div class="stat-label">Present Today</div>
            <div class="stat-label">of <?php echo $totalEmployees; ?> employees</div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stat-card late-card">
            <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
            <div class="stat-number"><?php echo $todaySummary['late']; ?></div>
            <div class="stat-label">Late Arrivals</div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stat-card absent-card">
            <div class="stat-icon"><i class="fas fa-user-slash"></i></div>
            <div class="stat-number"><?php echo $todaySummary['absent']; ?></div>
            <div class="stat-label">Absent Today</div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stat-card hours-card">
            <div class="stat-icon"><i class="fas fa-chart-pie"></i></div>
            <div class="stat-number"><?php echo $todaySummary['attendance_rate']; ?>%</div>
            <div class="stat-label">Attendance Rate</div>
        </div>
    </div>
</div>

<!-- Main Charts Row -->
<div class="row mb-4">
    <!-- Attendance Summary Pie Chart -->
    <div class="col-lg-4 mb-4">
        <div class="app-card h-100">
            <div class="card-header bg-primary">
                <h5 class="mb-0 text-white">Today's Attendance</h5>
            </div>
            <div class="card-body">
                <canvas id="attendance-summary-chart" height="250"
                        data-present="<?php echo $todaySummary['present']; ?>"
                        data-late="<?php echo $todaySummary['late']; ?>"
                        data-absent="<?php echo $todaySummary['absent']; ?>">
                </canvas>
            </div>
        </div>
    </div>
    
    <!-- Department Comparison Chart -->
    <div class="col-lg-8 mb-4">
        <div class="app-card h-100">
            <div class="card-header bg-primary">
                <h5 class="mb-0 text-white">Department Comparison</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($departmentStats)): ?>
                    <canvas id="department-comparison-chart" height="250"
                            data-departments="<?php echo htmlspecialchars(json_encode(array_column($departmentStats, 'department'))); ?>"
                            data-present-counts="<?php echo htmlspecialchars(json_encode(array_column($departmentStats, 'present_in_dept'))); ?>"
                            data-late-counts="<?php echo htmlspecialchars(json_encode(array_column($departmentStats, 'late_in_dept'))); ?>"
                            data-absent-counts="<?php echo htmlspecialchars(json_encode(
                                array_map(function($dept) {
                                    return $dept['total_in_dept'] - $dept['present_in_dept'];
                                }, $departmentStats)
                            )); ?>">
                    </canvas>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No department data available.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Second Row Charts -->
<div class="row mb-4">
    <!-- Attendance Trend Chart -->
    <div class="col-lg-8 mb-4">
        <div class="app-card h-100">
            <div class="card-header bg-primary">
                <h5 class="mb-0 text-white">Attendance Trend</h5>
            </div>
            <div class="card-body">
                <?php
                // Prepare attendance rate trend data
                $trendDates = [];
                $attendanceRates = [];
                
                // Filter to only include data points at regular intervals to avoid overcrowding
                $totalDays = count($attendanceData);
                $interval = $totalDays > 30 ? 5 : ($totalDays > 14 ? 2 : 1);
                
                $i = 0;
                foreach ($attendanceData as $date => $data) {
                    if ($i % $interval === 0) {
                        $trendDates[] = date('M d', strtotime($date));
                        $attendanceRates[] = $data['attendance_rate'];
                    }
                    $i++;
                }
                ?>
                
                <canvas id="attendance-trend-chart" height="250"
                        data-dates="<?php echo htmlspecialchars(json_encode($trendDates)); ?>"
                        data-rates="<?php echo htmlspecialchars(json_encode($attendanceRates)); ?>">
                </canvas>
            </div>
        </div>
    </div>
    
    <!-- Punctuality Chart -->
    <div class="col-lg-4 mb-4">
        <div class="app-card h-100">
            <div class="card-header bg-primary">
                <h5 class="mb-0 text-white">Punctuality</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($punctualityData)): 
                    $days = array_column($punctualityData, 'day');
                    $onTime = array_column($punctualityData, 'on_time');
                    $late = array_column($punctualityData, 'late');
                ?>
                    <canvas id="punctuality-chart" height="250"
                            data-periods="<?php echo htmlspecialchars(json_encode($days)); ?>"
                            data-on-time="<?php echo htmlspecialchars(json_encode($onTime)); ?>"
                            data-late="<?php echo htmlspecialchars(json_encode($late)); ?>">
                    </canvas>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No punctuality data available.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Daily Activity Chart -->
<div class="app-card mb-4">
    <div class="card-header bg-primary">
        <h5 class="mb-0 text-white">Daily Activity Pattern</h5>
    </div>
    <div class="card-body">
        <?php
        // Format hour labels in 12-hour format
        $hourLabels = ['8AM', '9AM', '10AM', '11AM', '12PM', '1PM', '2PM', '3PM', '4PM', '5PM', '6PM', '7PM'];
        ?>
        <canvas id="daily-activity-chart" height="250"
                data-entries="<?php echo htmlspecialchars(json_encode($hourlyActivity['entries'])); ?>"
                data-exits="<?php echo htmlspecialchars(json_encode($hourlyActivity['exits'])); ?>">
        </canvas>
    </div>
</div>

<!-- Employee List with Attendance Metrics -->
<div class="app-card">
    <div class="card-header bg-primary">
        <h5 class="mb-0 text-white">Employee Attendance Metrics</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Present Days</th>
                        <th>Late Days</th>
                        <th>Absent Days</th>
                        <th>Attendance Rate</th>
                        <th>Avg. Work Hours</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get employee attendance metrics for the selected date range
                    $pdo = Database::connect();
                    $sql = "SELECT 
                           e.employee_id,
                           e.name,
                           e.department,
                           COUNT(DISTINCT dr.work_date) as present_days,
                           SUM(CASE WHEN dr.status = 'late' THEN 1 ELSE 0 END) as late_days,
                           AVG(dr.work_hours) as avg_hours
                           FROM employees e
                           LEFT JOIN daily_records dr ON e.employee_id = dr.employee_id 
                               AND dr.work_date BETWEEN ? AND ?
                           WHERE e.employment_status = 'active'";
                    
                    $params = [$startDate, $endDate];
                    
                    if (!empty($departmentFilter)) {
                        $sql .= " AND e.department = ?";
                        $params[] = $departmentFilter;
                    }
                    
                    $sql .= " GROUP BY e.employee_id, e.name, e.department ORDER BY present_days DESC, avg_hours DESC";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $employeeMetrics = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Get working days count (excluding weekends)
                    $workingDays = 0;
                    $start = new DateTime($startDate);
                    $end = new DateTime($endDate);
                    $interval = new DateInterval('P1D');
                    $period = new DatePeriod($start, $interval, $end->modify('+1 day'));
                    
                    foreach ($period as $date) {
                        $dayOfWeek = $date->format('w'); // 0 (Sun) to 6 (Sat)
                        if ($dayOfWeek != 0 && $dayOfWeek != 6) { // Skip weekends
                            $workingDays++;
                        }
                    }
                    
                    if (!empty($employeeMetrics)):
                        foreach ($employeeMetrics as $employee):
                            $presentDays = $employee['present_days'];
                            $lateDays = $employee['late_days'];
                            $absentDays = $workingDays - $presentDays;
                            $attendanceRate = ($workingDays > 0) ? round(($presentDays / $workingDays) * 100, 1) : 0;
                            $avgHours = $employee['avg_hours'] ? round($employee['avg_hours'], 2) : 0;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($employee['name']); ?></td>
                        <td><?php echo htmlspecialchars($employee['department'] ?? 'Not Assigned'); ?></td>
                        <td><?php echo $presentDays; ?></td>
                        <td><?php echo $lateDays; ?></td>
                        <td><?php echo $absentDays; ?></td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar <?php echo $attendanceRate < 70 ? 'bg-danger' : ($attendanceRate < 90 ? 'bg-warning' : 'bg-success'); ?>" 
                                     role="progressbar" style="width: <?php echo $attendanceRate; ?>%;" 
                                     aria-valuenow="<?php echo $attendanceRate; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo $attendanceRate; ?>%
                                </div>
                            </div>
                        </td>
                        <td><?php echo $avgHours; ?> hrs</td>
                    </tr>
                    <?php 
                        endforeach;
                    else:
                    ?>
                    <tr>
                        <td colspan="7" class="text-center py-3">No employee data available for the selected criteria.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <a href="reports.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&department=<?php echo $departmentFilter; ?>" class="btn btn-primary">
            <i class="fas fa-file-export me-2"></i> Generate Detailed Report
        </a>
    </div>
</div>

<!-- Include Chart.js and Visualization Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="js/visualization.js"></script>

<?php endif; ?>

<?php
// Include footer
require_once 'includes/footer.php';
?>