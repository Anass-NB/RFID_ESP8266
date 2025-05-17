<?php
/**
 * Reports Page
 * Generate and view detailed attendance reports
 */

// Include header
$pageTitle = "Attendance Reports";
require_once 'includes/header.php';

// Reset UID container
$Write="<?php $" . "UIDresult=''; " . "echo $" . "UIDresult;" . " ?>";
file_put_contents('UIDContainer.php',$Write);

// Include time tracking functions
require_once 'time_tracking.php';

// Get date parameters or use defaults
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$employeeId = isset($_GET['employee_id']) ? $_GET['employee_id'] : null;

// Get employee list for dropdown
$employeesResult = TimeTracking::getAllEmployees();
$employees = $employeesResult['status'] === 'success' ? $employeesResult['data'] : [];

// Get report data if employee is selected
$reportData = null;
if ($employeeId) {
    $reportData = TimeTracking::getWorkSummary($employeeId, $startDate, $endDate);
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-0"><i class="fas fa-chart-bar me-2"></i> Attendance Reports</h3>
        <p class="text-muted mb-0">Generate detailed attendance and work hour reports</p>
    </div>
    <div>
        <a href="dashboard.php" class="btn btn-outline-primary">
            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>
    </div>
</div>

<!-- Report Filters -->
<div class="app-card mb-4">
    <div class="card-header bg-primary">
        <h5 class="mb-0 text-white"><i class="fas fa-filter me-2"></i> Report Filters</h5>
    </div>
    <div class="card-body">
        <form id="report-form" method="GET" action="reports.php" class="app-form">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="employee_id" class="form-label">Employee</label>
                    <select class="form-select" id="employee_id" name="employee_id">
                        <option value="">All Employees</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['employee_id']; ?>" <?php if ($employeeId == $emp['employee_id']) echo 'selected'; ?>>
                                <?php echo $emp['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                </div>
                
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i> Generate
                    </button>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary quick-date" data-days="7">
                            <i class="fas fa-calendar-week me-1"></i> Last Week
                        </button>
                        <button type="button" class="btn btn-outline-secondary quick-date" data-days="30">
                            <i class="fas fa-calendar-alt me-1"></i> Last Month
                        </button>
                        <button type="button" class="btn btn-outline-secondary quick-date" data-days="90">
                            <i class="fas fa-calendar me-1"></i> Last 3 Months
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="current-month-btn">
                            <i class="fas fa-calendar-day me-1"></i> Current Month
                        </button>
                    </div>
                    
                    <?php if ($reportData && $reportData['status'] === 'success'): ?>
                        <button type="button" class="btn btn-success float-end" id="export-report">
                            <i class="fas fa-download me-2"></i> Export to CSV
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Report Results -->
<?php if ($reportData && $reportData['status'] === 'success'): ?>
    <div class="app-card mb-4">
        <div class="card-header bg-primary d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white">
                <?php if ($employeeId): ?>
                    <i class="fas fa-user me-2"></i> Report for <?php echo $reportData['data']['employee']['name']; ?>
                <?php else: ?>
                    <i class="fas fa-users me-2"></i> All Employees Report
                <?php endif; ?>
            </h5>
            <span class="badge bg-light text-dark px-3 py-2">
                <i class="fas fa-calendar me-1"></i>
                <?php echo date('M d, Y', strtotime($startDate)); ?> - 
                <?php echo date('M d, Y', strtotime($endDate)); ?>
            </span>
        </div>
        <div class="card-body">
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                    <div class="stat-card present-card">
                        <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                        <div class="stat-number"><?php echo $reportData['data']['summary']['present_days']; ?></div>
                        <div class="stat-label">Present Days</div>
                        <div class="stat-label">of <?php echo $reportData['data']['summary']['total_days']; ?> working days</div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                    <div class="stat-card late-card">
                        <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
                        <div class="stat-number"><?php echo $reportData['data']['summary']['late_days']; ?></div>
                        <div class="stat-label">Late Days</div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                    <div class="stat-card absent-card">
                        <div class="stat-icon"><i class="fas fa-user-slash"></i></div>
                        <div class="stat-number"><?php echo $reportData['data']['summary']['absent_days']; ?></div>
                        <div class="stat-label">Absent Days</div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card hours-card">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-number"><?php echo $reportData['data']['summary']['total_hours']; ?></div>
                        <div class="stat-label">Total Hours</div>
                        <div class="stat-label">
                            (Avg: <?php echo $reportData['data']['summary']['average_hours']; ?> hrs/day)
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="row mb-4">
                <div class="col-md-6 mb-4 mb-md-0">
                    <div class="app-card h-100">
                        <div class="card-header bg-secondary">
                            <h5 class="mb-0 text-white"><i class="fas fa-chart-pie me-2"></i>Attendance Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="attendancePieChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="app-card h-100">
                        <div class="card-header bg-secondary">
                            <h5 class="mb-0 text-white"><i class="fas fa-chart-bar me-2"></i>Daily Work Hours</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="workHoursChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detailed Records -->
            <div class="app-card">
                <div class="card-header bg-secondary">
                    <h5 class="mb-0 text-white"><i class="fas fa-list-alt me-2"></i>Daily Attendance Records</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped app-table mb-0" id="detailedReport">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Status</th>
                                    <th>First In</th>
                                    <th>Last Out</th>
                                    <th>Work Hours</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php 
                                // Create array of all dates in range
                                $period = new DatePeriod(
                                    new DateTime($startDate),
                                    new DateInterval('P1D'),
                                    new DateTime($endDate . ' +1 day')
                                );
                                
                                // Create associative array of records by date
                                $recordsByDate = [];
                                foreach ($reportData['data']['records'] as $record) {
                                    $recordsByDate[$record['work_date']] = $record;
                                }
                                
                                // Helper function to format time
                                function formatTime($datetime) {
                                    if (!$datetime) return '---';
                                    return date('h:i A', strtotime($datetime));
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
                                        <span class="fw-medium"><?php echo $date->format('Y-m-d'); ?></span>
                                    </td>
                                    <td>
                                        <?php echo $dayName; ?>
                                        <?php if ($isWeekend): ?>
                                            <span class="badge bg-secondary">Weekend</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($record): ?>
                                            <span class="badge 
                                                <?php if ($record['status'] == 'present') echo 'bg-success';
                                                      else if ($record['status'] == 'late') echo 'bg-warning';
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
                                            <span class="fw-bold">
                                                <?php echo number_format($record['work_hours'], 2); ?>
                                            </span>
                                            <span class="text-muted">hrs</span>
                                        <?php else: ?>
                                            <span class="text-muted">0.00 hrs</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($record): ?>
                                            <a href="daily-log.php?employee_id=<?php echo $employeeId; ?>&date=<?php echo $dateStr; ?>" 
                                               class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="View Details">
                                                <i class="fas fa-list-ul"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">---</span>
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
<?php elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['employee_id'])): ?>
    <div class="alert alert-warning" role="alert">
        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>No Data Available</h4>
        <p>No attendance records found for the selected criteria. Please try a different employee or date range.</p>
    </div>
<?php else: ?>
    <div class="app-card">
        <div class="card-body text-center py-5">
            <i class="fas fa-chart-line fa-4x text-muted mb-3"></i>
            <h4 class="text-muted">Select an employee and date range to generate a report</h4>
            <p>The report will show attendance records, work hours, and other statistics for the selected period.</p>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        
        // Quick date selection buttons
        document.querySelectorAll('.quick-date').forEach(function(button) {
            button.addEventListener('click', function() {
                const days = parseInt(this.getAttribute('data-days'));
                const endDate = new Date();
                const startDate = new Date();
                startDate.setDate(startDate.getDate() - days);
                
                document.getElementById('start_date').value = formatDateForInput(startDate);
                document.getElementById('end_date').value = formatDateForInput(endDate);
            });
        });
        
        // Current month button
        document.getElementById('current-month-btn').addEventListener('click', function() {
            const now = new Date();
            const startDate = new Date(now.getFullYear(), now.getMonth(), 1);
            const endDate = new Date();
            
            document.getElementById('start_date').value = formatDateForInput(startDate);
            document.getElementById('end_date').value = formatDateForInput(endDate);
        });
        
        // Format date for input fields (YYYY-MM-DD)
        function formatDateForInput(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        <?php if ($reportData && $reportData['status'] === 'success'): ?>
            // Attendance Pie Chart
            const pieCtx = document.getElementById('attendancePieChart').getContext('2d');
            const attendancePieChart = new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Present On-Time', 'Late', 'Absent'],
                    datasets: [{
                        data: [
                            <?php echo $reportData['data']['summary']['present_days'] - $reportData['data']['summary']['late_days']; ?>,
                            <?php echo $reportData['data']['summary']['late_days']; ?>,
                            <?php echo $reportData['data']['summary']['absent_days']; ?>
                        ],
                        backgroundColor: [
                            '#4caf50', // Present
                            '#ffc107', // Late
                            '#f44336'  // Absent
                        ],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            
            // Work Hours Chart
            const hoursCtx = document.getElementById('workHoursChart').getContext('2d');
            
            // Prepare data for chart
            const dates = [];
            const hours = [];
            const colors = [];
            
            <?php
                // Sort records by date
                $sortedRecords = $reportData['data']['records'];
                usort($sortedRecords, function($a, $b) {
                    return strtotime($a['work_date']) - strtotime($b['work_date']);
                });
                
                // Get the most recent records (up to 10)
                $recentRecords = array_slice($sortedRecords, -10);
            ?>
            
            <?php foreach ($recentRecords as $record): ?>
                dates.push('<?php echo date('M d', strtotime($record['work_date'])); ?>');
                hours.push(<?php echo $record['work_hours']; ?>);
                
                // Color based on status
                <?php if ($record['status'] === 'late'): ?>
                    colors.push('#ffc107');
                <?php elseif ($record['status'] === 'absent'): ?>
                    colors.push('#f44336');
                <?php else: ?>
                    colors.push('#4caf50');
                <?php endif; ?>
            <?php endforeach; ?>
            
            const workHoursChart = new Chart(hoursCtx, {
                type: 'bar',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Work Hours',
                        data: hours,
                        backgroundColor: colors,
                        borderWidth: 0,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Hours',
                                font: {
                                    weight: 'bold'
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
            
            // Export to CSV functionality
            document.getElementById('export-report').addEventListener('click', function() {
                // Get the table data
                const table = document.getElementById('detailedReport');
                let csv = [];
                
                // Add header row
                const headers = [];
                const headerCells = table.querySelectorAll('thead th');
                headerCells.forEach(cell => {
                    if (cell.textContent !== 'Actions') { // Skip the actions column
                        headers.push(cell.textContent.trim());
                    }
                });
                csv.push(headers.join(','));
                
                // Add data rows
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const rowData = [];
                    const cells = row.querySelectorAll('td');
                    cells.forEach((cell, index) => {
                        // Skip the actions column
                        if (index !== 6) { 
                            let text = cell.textContent.trim();
                            // Clean up badge text
                            if (index === 2) {
                                // Status column
                                const badge = cell.querySelector('.badge');
                                text = badge ? badge.textContent.trim() : 'N/A';
                            }
                            // Wrap with quotes to handle commas
                            rowData.push(`"${text}"`);
                        }
                    });
                    csv.push(rowData.join(','));
                });
                
                // Generate file name
                const fileName = 'attendance_report_<?php echo $employeeId ? $reportData['data']['employee']['name'] : 'all'; ?>_' + 
                                 '<?php echo $startDate; ?>_to_<?php echo $endDate; ?>.csv';
                
                // Create and download the file
                const csvContent = csv.join('\n');
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                
                link.setAttribute('href', url);
                link.setAttribute('download', fileName);
                link.style.visibility = 'hidden';
                
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        <?php endif; ?>
    });
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>