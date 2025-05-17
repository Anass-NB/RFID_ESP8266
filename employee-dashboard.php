<?php
	// Reset UID container
	$Write="<?php $" . "UIDresult=''; " . "echo $" . "UIDresult;" . " ?>";
	file_put_contents('UIDContainer.php',$Write);
	
	// Include time tracking functions
	require_once 'time_tracking.php';
	require_once 'database.php';
	
	// Check if user is logged in (implement proper authentication later)
	session_start();
	$isAdmin = isset($_SESSION['admin']) && $_SESSION['admin'] === true;
	
	// Get employee ID from session or URL
	$employeeId = isset($_GET['id']) ? $_GET['id'] : (isset($_SESSION['employee_id']) ? $_SESSION['employee_id'] : null);
	
	// If no employee ID, show an error or redirect
	if (!$employeeId) {
		$error = "No employee selected. Please select an employee or login to your account.";
	}
	
	// Get date parameters for filtering
	$today = date('Y-m-d');
	$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
	$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : $today;
	
	// Get employee details
	$employee = null;
	$employeeStatus = null;
	$workSummary = null;
	$todayRecord = null;
	$timeLogs = [];
	
	if ($employeeId) {
		// Get employee status
		$statusResult = TimeTracking::getEmployeeStatus($employeeId);
		if ($statusResult['status'] === 'success') {
			$employee = $statusResult['data']['employee'];
			$employeeStatus = $statusResult['data'];
			$todayRecord = $statusResult['data']['today_record'];
		}
		
		// Get work summary for the selected date range
		$workSummary = TimeTracking::getWorkSummary($employeeId, $startDate, $endDate);
		
		// Get today's time logs
		$logsResult = TimeTracking::getDailyTimeLogs($employeeId, $today);
		if ($logsResult['status'] === 'success') {
			$timeLogs = $logsResult['data']['logs'];
		}
	}
	
	// Format time from datetime
	function formatTime($datetime) {
		if (!$datetime) return '--:--';
		return date('h:i A', strtotime($datetime));
	}
	
	// Get list of all employees for admin dropdown
	$allEmployees = [];
	if ($isAdmin) {
		$employeesResult = TimeTracking::getAllEmployees();
		if ($employeesResult['status'] === 'success') {
			$allEmployees = $employeesResult['data'];
		}
	}
	
	// Get all departments for filters
	$departments = [];
	$pdo = Database::connect();
	$sql = "SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department";
	foreach ($pdo->query($sql) as $row) {
		$departments[] = $row['department'];
	}
	Database::disconnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta charset="utf-8">
	<title>Employee Dashboard - Time Tracking System</title>
	
	<!-- Stylesheets and Libraries -->
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="css/styles.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
	<!-- Scripts -->
	<script src="js/bootstrap.bundle.min.js"></script>
	<script src="jquery.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
	<script src="js/time-tracking.js"></script>
</head>

<body>
	<div class="container">
		<h2 class="text-center mt-4 mb-4">Employee Time Tracking System</h2>
		
		<!-- Navigation Bar -->
		<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
			<div class="container-fluid">
				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse" id="navbarNav">
					<ul class="navbar-nav">
						<li class="nav-item">
							<a class="nav-link" href="home.php">Home</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="user-data.php">Employees</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="registration.php">Registration</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="read-tag.php">Scan Card</a>
						</li>
						<li class="nav-item">
							<a class="nav-link active" href="employee-dashboard.php">Dashboard</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="reports.php">Reports</a>
						</li>
					</ul>
					
					<?php if ($isAdmin): ?>
					<ul class="navbar-nav ms-auto">
						<li class="nav-item dropdown">
							<a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
								<i class="fas fa-cog"></i> Admin
							</a>
							<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
								<li><a class="dropdown-item" href="admin-settings.php">Settings</a></li>
								<li><a class="dropdown-item" href="admin-users.php">Manage Users</a></li>
								<li><a class="dropdown-item" href="admin-departments.php">Departments</a></li>
								<li><hr class="dropdown-divider"></li>
								<li><a class="dropdown-item" href="logout.php">Logout</a></li>
							</ul>
						</li>
					</ul>
					<?php endif; ?>
				</div>
			</div>
		</nav>
		
		<?php if (isset($error)): ?>
		<div class="alert alert-danger">
			<?php echo $error; ?>
		</div>
		<?php endif; ?>
		
		<?php if ($employee): ?>
		<!-- Employee Selection (Admin only) -->
		<?php if ($isAdmin): ?>
		<div class="card mb-4">
			<div class="card-body">
				<form id="employee-select-form" class="row g-3 align-items-center">
					<div class="col-md-5">
						<label for="employee-select" class="form-label">Select Employee</label>
						<select class="form-select" id="employee-select" name="id" onchange="this.form.submit()">
							<option value="">-- Select Employee --</option>
							<?php foreach ($allEmployees as $emp): ?>
							<option value="<?php echo $emp['employee_id']; ?>" <?php if ($emp['employee_id'] == $employeeId) echo 'selected'; ?>>
								<?php echo $emp['name']; ?> (<?php echo $emp['department'] ?: 'No Department'; ?>)
							</option>
							<?php endforeach; ?>
						</select>
					</div>
					
					<div class="col-md-5">
						<label for="department-filter" class="form-label">Filter by Department</label>
						<select class="form-select" id="department-filter">
							<option value="">All Departments</option>
							<?php foreach ($departments as $dept): ?>
							<option value="<?php echo $dept; ?>"><?php echo $dept; ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					
					<div class="col-md-2">
						<label class="form-label d-block">&nbsp;</label>
						<button type="button" class="btn btn-primary w-100" id="refresh-dashboard">
							<i class="fas fa-sync-alt"></i> Refresh
						</button>
					</div>
				</form>
			</div>
		</div>
		<?php endif; ?>
		
		<!-- Today's Status Card -->
		<div class="row">
			<div class="col-lg-4 col-md-5">
				<!-- Employee Profile Card -->
				<div class="card mb-4">
					<div class="card-header bg-primary text-white">
						<h5 class="mb-0">Employee Profile</h5>
					</div>
					<div class="card-body text-center">
						<!-- Profile image placeholder -->
						<div class="profile-image mx-auto mb-3">
							<?php if (!empty($employee['profile_image'])): ?>
								<img src="<?php echo $employee['profile_image']; ?>" alt="Profile Image" class="img-fluid rounded-circle">
							<?php else: ?>
								<div class="profile-placeholder">
									<?php echo substr($employee['name'], 0, 1); ?>
								</div>
							<?php endif; ?>
						</div>
						
						<h4><?php echo $employee['name']; ?></h4>
						
						<!-- Current status badge -->
						<div class="badge <?php echo ($employee['current_status'] == 'in') ? 'bg-success' : 'bg-danger'; ?> status-badge mb-3">
							<?php echo ($employee['current_status'] == 'in') ? 'CLOCKED IN' : 'CLOCKED OUT'; ?>
						</div>
						
						<div class="employee-details">
							<p><i class="fas fa-building"></i> <?php echo $employee['department'] ?: 'Not assigned'; ?></p>
							<p><i class="fas fa-briefcase"></i> <?php echo $employee['position'] ?: 'Not assigned'; ?></p>
							<p><i class="fas fa-envelope"></i> <?php echo $employee['email']; ?></p>
							<p><i class="fas fa-phone"></i> <?php echo $employee['mobile']; ?></p>
						</div>
						
						<a href="employee-details.php?id=<?php echo $employeeId; ?>" class="btn btn-primary mt-2">
							<i class="fas fa-user"></i> View Full Profile
						</a>
					</div>
				</div>
			</div>
			
			<div class="col-lg-8 col-md-7">
				<!-- Today's Activity -->
				<div class="card mb-4">
					<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
						<h5 class="mb-0">Today's Activity</h5>
						<span class="badge bg-light text-dark" id="current-date-display"></span>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-md-6 text-center mb-3">
								<h5>First Clock In</h5>
								<div class="time-display first-clock">
									<?php echo $todayRecord && $todayRecord['first_entry'] ? formatTime($todayRecord['first_entry']) : '--:--'; ?>
								</div>
							</div>
							<div class="col-md-6 text-center mb-3">
								<h5>Last Clock Out</h5>
								<div class="time-display last-clock">
									<?php echo $todayRecord && $todayRecord['last_exit'] ? formatTime($todayRecord['last_exit']) : '--:--'; ?>
								</div>
							</div>
							<div class="col-md-6 text-center">
								<h5>Status</h5>
								<?php if ($todayRecord): ?>
									<span class="badge px-4 py-2 fs-6
										<?php if ($todayRecord['status'] == 'present') echo 'bg-success';
											  else if ($todayRecord['status'] == 'late') echo 'bg-warning text-dark';
											  else echo 'bg-danger'; ?>">
										<?php echo strtoupper($todayRecord['status']); ?>
									</span>
								<?php else: ?>
									<span class="badge bg-secondary px-4 py-2 fs-6">NOT RECORDED</span>
								<?php endif; ?>
							</div>
							<div class="col-md-6 text-center">
								<h5>Work Hours</h5>
								<div class="work-hours-display">
									<?php 
										if ($todayRecord && isset($todayRecord['work_hours'])) {
											echo number_format($todayRecord['work_hours'], 2);
										} else {
											echo '0.00';
										}
									?> hrs
								</div>
							</div>
						</div>
						
						<!-- Time log entries -->
						<?php if (!empty($timeLogs)): ?>
							<div class="mt-4">
								<h5>Time Log Entries</h5>
								<div class="table-responsive">
									<table class="table table-sm table-striped">
										<thead>
											<tr>
												<th>Time</th>
												<th>Action</th>
												<th>Notes</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach($timeLogs as $log): ?>
												<tr>
													<td><?php echo formatTime($log['timestamp']); ?></td>
													<td>
														<span class="badge <?php echo ($log['log_type'] == 'entry') ? 'bg-success' : 'bg-danger'; ?>">
															<?php echo ($log['log_type'] == 'entry') ? 'CLOCK IN' : 'CLOCK OUT'; ?>
														</span>
													</td>
													<td><?php echo $log['notes'] ?: ''; ?></td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>
							</div>
						<?php else: ?>
							<div class="alert alert-info mt-4">
								<i class="fas fa-info-circle"></i> No time log entries recorded for today.
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		
		<!-- Attendance Summary -->
		<div class="card mb-4">
			<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
				<h5 class="mb-0">Attendance Summary</h5>
				
				<!-- Date range selector -->
				<div class="date-range-picker">
					<form id="date-range-form" class="row g-2">
						<input type="hidden" name="id" value="<?php echo $employeeId; ?>">
						<div class="col-auto">
							<input type="text" class="form-control form-control-sm date-picker" id="start-date" name="start_date" value="<?php echo $startDate; ?>" placeholder="Start Date">
						</div>
						<div class="col-auto">
							<input type="text" class="form-control form-control-sm date-picker" id="end-date" name="end_date" value="<?php echo $endDate; ?>" placeholder="End Date">
						</div>
						<div class="col-auto">
							<button type="submit" class="btn btn-sm btn-light">Apply</button>
						</div>
					</form>
				</div>
			</div>
			<div class="card-body">
				<?php if ($workSummary && $workSummary['status'] === 'success'): ?>
					<div class="row">
						<!-- Summary Cards -->
						<div class="col-md-4">
							<div class="card">
								<div class="card-body">
									<div class="row">
										<div class="col-md-6 col-6 text-center mb-3">
											<h6>Present Days</h6>
											<h3 class="text-success">
												<?php echo $workSummary['data']['summary']['present_days']; ?>
												<small class="text-muted">/ <?php echo $workSummary['data']['summary']['total_days']; ?></small>
											</h3>
										</div>
										<div class="col-md-6 col-6 text-center mb-3">
											<h6>Late Days</h6>
											<h3 class="text-warning">
												<?php echo $workSummary['data']['summary']['late_days']; ?>
											</h3>
										</div>
										<div class="col-md-6 col-6 text-center">
											<h6>Absent Days</h6>
											<h3 class="text-danger">
												<?php echo $workSummary['data']['summary']['absent_days']; ?>
											</h3>
										</div>
										<div class="col-md-6 col-6 text-center">
											<h6>Total Hours</h6>
											<h3 class="text-primary">
												<?php echo $workSummary['data']['summary']['total_hours']; ?>
											</h3>
										</div>
									</div>
									
									<div class="mt-3">
										<h6>Attendance Rate</h6>
										<?php 
											$attendanceRate = 0;
											if ($workSummary['data']['summary']['total_days'] > 0) {
												$attendanceRate = ($workSummary['data']['summary']['present_days'] / $workSummary['data']['summary']['total_days']) * 100;
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
						</div>
						
						<!-- Attendance Chart -->
						<div class="col-md-8">
							<canvas id="attendanceChart" height="250"></canvas>
						</div>
					</div>
				<?php else: ?>
					<div class="alert alert-info">
						<i class="fas fa-info-circle"></i> No attendance data available for the selected date range.
					</div>
				<?php endif; ?>
			</div>
		</div>
		
		<!-- Attendance Records Table -->
		<div class="card mb-4">
			<div class="card-header bg-primary text-white">
				<h5 class="mb-0">Attendance Records</h5>
			</div>
			<div class="card-body p-0">
				<?php if ($workSummary && $workSummary['status'] === 'success' && isset($workSummary['data']['records'])): ?>
					<div class="table-responsive">
						<table class="table table-striped table-hover mb-0">
							<thead class="table-light">
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
									foreach ($workSummary['data']['records'] as $record) {
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
										<td><?php echo $date->format('M d, Y'); ?></td>
										<td><?php echo $dayName; ?></td>
										<td>
											<?php if ($record): ?>
												<span class="badge 
													<?php if ($record['status'] == 'present') echo 'bg-success';
														  else if ($record['status'] == 'late') echo 'bg-warning text-dark';
														  else echo 'bg-danger'; ?>">
													<?php echo strtoupper($record['status']); ?>
												</span>
											<?php elseif (!$isWeekend): ?>
												<span class="badge bg-danger">ABSENT</span>
											<?php else: ?>
												<span class="badge bg-secondary">WEEKEND</span>
											<?php endif; ?>
										</td>
										<td><?php echo $record && $record['first_entry'] ? formatTime($record['first_entry']) : '--:--'; ?></td>
										<td><?php echo $record && $record['last_exit'] ? formatTime($record['last_exit']) : '--:--'; ?></td>
										<td><?php echo $record ? number_format($record['work_hours'], 2) . ' hrs' : '--'; ?></td>
										<td>
											<?php if ($record): ?>
												<a href="daily-log.php?employee_id=<?php echo $employeeId; ?>&date=<?php echo $dateStr; ?>" class="btn btn-sm btn-info">
													<i class="fas fa-list"></i> Details
												</a>
											<?php else: ?>
												<button class="btn btn-sm btn-secondary" disabled>No Data</button>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else: ?>
					<div class="alert alert-info m-3">
						<i class="fas fa-info-circle"></i> No attendance records available for the selected date range.
					</div>
				<?php endif; ?>
			</div>
			<div class="card-footer">
				<?php if ($workSummary && $workSummary['status'] === 'success'): ?>
					<a href="reports.php?employee_id=<?php echo $employeeId; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-primary">
						<i class="fas fa-file-export"></i> Generate Report
					</a>
					<button type="button" class="btn btn-success" id="export-csv">
						<i class="fas fa-download"></i> Export to CSV
					</button>
				<?php endif; ?>
			</div>
		</div>
		<?php endif; ?>
	</div>
	
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			// Show current date and time
			const currentDateElement = document.getElementById('current-date-display');
			if (currentDateElement) {
				const now = new Date();
				currentDateElement.textContent = now.toLocaleDateString([], {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'});
			}
			
			// Initialize date pickers
			if (typeof flatpickr === 'function') {
				flatpickr('.date-picker', {
					dateFormat: 'Y-m-d',
					maxDate: 'today'
				});
			}
			
			// Department filter functionality for admin
			const departmentFilter = document.getElementById('department-filter');
			const employeeSelect = document.getElementById('employee-select');
			
			if (departmentFilter && employeeSelect) {
				departmentFilter.addEventListener('change', function() {
					const selectedDepartment = this.value;
					const employeeOptions = employeeSelect.options;
					
					for (let i = 1; i < employeeOptions.length; i++) {
						const option = employeeOptions[i];
						const optionText = option.text;
						
						if (selectedDepartment === '' || optionText.includes(`(${selectedDepartment})`)) {
							option.style.display = '';
						} else {
							option.style.display = 'none';
						}
					}
				});
			}
			
			// Refresh dashboard button
			const refreshButton = document.getElementById('refresh-dashboard');
			if (refreshButton) {
				refreshButton.addEventListener('click', function() {
					window.location.reload();
				});
			}
			
			// Initialize attendance chart if data is available
			<?php if ($workSummary && $workSummary['status'] === 'success' && isset($workSummary['data']['records'])): ?>
				const ctx = document.getElementById('attendanceChart').getContext('2d');
				
				// Prepare chart data
				const dates = [];
				const workHours = [];
				const barColors = [];
				
				<?php
					// Sort records by date
					$sortedRecords = $workSummary['data']['records'];
					usort($sortedRecords, function($a, $b) {
						return strtotime($a['work_date']) - strtotime($b['work_date']);
					});
					
					// Get up to 14 most recent records
					$recentRecords = array_slice($sortedRecords, -14);
				?>
				
				<?php foreach ($recentRecords as $record): ?>
					dates.push('<?php echo date("M d", strtotime($record["work_date"])); ?>');
					workHours.push(<?php echo $record["work_hours"]; ?>);
					
					// Set color based on status
					<?php if ($record['status'] === 'late'): ?>
						barColors.push('#ffc107'); // Warning/yellow for late
					<?php elseif ($record['status'] === 'absent'): ?>
						barColors.push('#dc3545'); // Danger/red for absent
					<?php else: ?>
						barColors.push('#28a745'); // Success/green for present
					<?php endif; ?>
				<?php endforeach; ?>
				
				// Create the chart
				const attendanceChart = new Chart(ctx, {
					type: 'bar',
					data: {
						labels: dates,
						datasets: [{
							label: 'Work Hours',
							data: workHours,
							backgroundColor: barColors,
							borderWidth: 1
						}]
					},
					options: {
						responsive: true,
						plugins: {
							title: {
								display: true,
								text: 'Daily Work Hours'
							},
							legend: {
								display: false
							}
						},
						scales: {
							y: {
								beginAtZero: true,
								title: {
									display: true,
									text: 'Hours'
								}
							}
						}
					}
				});
				
				// Export to CSV functionality
				document.getElementById('export-csv').addEventListener('click', function() {
					// Define CSV headers
					let csvContent = "Date,Day,Status,First In,Last Out,Work Hours\n";
					
					// Get data from all dates in range
					<?php
						$period = new DatePeriod(
							new DateTime($startDate),
							new DateInterval('P1D'),
							new DateTime($endDate . ' +1 day')
						);
						
						foreach ($period as $date): 
							$dateStr = $date->format('Y-m-d');
							$dayName = $date->format('D');
							$record = isset($recordsByDate[$dateStr]) ? $recordsByDate[$dateStr] : null;
							$isWeekend = in_array($date->format('w'), [0, 6]);
							
							// Determine status
							if ($record) {
								$status = strtoupper($record['status']);
							} elseif ($isWeekend) {
								$status = "WEEKEND";
							} else {
								$status = "ABSENT";
							}
							
							// Format first in
							$firstIn = $record && $record['first_entry'] ? date('h:i A', strtotime($record['first_entry'])) : '--:--';
							
							// Format last out
							$lastOut = $record && $record['last_exit'] ? date('h:i A', strtotime($record['last_exit'])) : '--:--';
							
							// Format work hours
							$workHours = $record ? number_format($record['work_hours'], 2) : '0';
					?>
						csvContent += "<?php echo $date->format('Y-m-d'); ?>,";
						csvContent += "<?php echo $dayName; ?>,";
						csvContent += "<?php echo $status; ?>,";
						csvContent += "<?php echo $firstIn; ?>,";
						csvContent += "<?php echo $lastOut; ?>,";
						csvContent += "<?php echo $workHours; ?>\n";
					<?php endforeach; ?>
					
					// Create and download the CSV file
					const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
					const url = URL.createObjectURL(blob);
					const link = document.createElement('a');
					link.setAttribute('href', url);
					link.setAttribute('download', 'attendance_<?php echo $employee['name']; ?>_<?php echo $startDate; ?>_to_<?php echo $endDate; ?>.csv');
					link.style.visibility = 'hidden';
					document.body.appendChild(link);
					link.click();
					document.body.removeChild(link);
				});
			<?php endif; ?>
		});
	</script>
</body>
</html>