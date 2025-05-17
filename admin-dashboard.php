<?php
	// Reset UID container
	$Write="<?php $" . "UIDresult=''; " . "echo $" . "UIDresult;" . " ?>";
	file_put_contents('UIDContainer.php',$Write);
	
	// Include time tracking functions
	require_once 'time_tracking.php';
	require_once 'database.php';
	
	// Administrator dashboard for employee time tracking
	
	// Get filter parameters
	$today = date('Y-m-d');
	$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
	$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : $today;
	$department = isset($_GET['department']) ? $_GET['department'] : '';
	
	// Get the current day's attendance summary
	$dailyAttendance = TimeTracking::getDailyAttendanceSummary($today);
	
	// Get all active employees
	$employeesResult = TimeTracking::getAllEmployees();
	$employees = $employeesResult['status'] === 'success' ? $employeesResult['data'] : [];
	
	// Get present employees
	$presentResult = TimeTracking::getPresentEmployees();
	$presentEmployees = $presentResult['status'] === 'success' ? $presentResult['data'] : [];
	
	// Get all departments
	$departments = [];
	$pdo = Database::connect();
	$sql = "SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department";
	foreach ($pdo->query($sql) as $row) {
		$departments[] = $row['department'];
	}
	
	// Calculate department-wise stats if a department is selected
	$departmentStats = null;
	if (!empty($department)) {
		$sql = "SELECT 
				COUNT(DISTINCT e.employee_id) as total_employees,
				COUNT(DISTINCT CASE WHEN e.current_status = 'in' THEN e.employee_id END) as currently_present,
				AVG(dr.work_hours) as avg_hours
				FROM employees e
				LEFT JOIN daily_records dr ON e.employee_id = dr.employee_id AND dr.work_date BETWEEN ? AND ?
				WHERE e.department = ? AND e.employment_status = 'active'";
		$q = $pdo->prepare($sql);
		$q->execute(array($startDate, $endDate, $department));
		$departmentStats = $q->fetch(PDO::FETCH_ASSOC);
		
		// Get late employees in the department for selected date range
		$sql = "SELECT COUNT(DISTINCT dr.employee_id) as late_count
				FROM daily_records dr
				JOIN employees e ON dr.employee_id = e.employee_id
				WHERE e.department = ? AND dr.work_date BETWEEN ? AND ? AND dr.status = 'late'";
		$q = $pdo->prepare($sql);
		$q->execute(array($department, $startDate, $endDate));
		$lateResult = $q->fetch(PDO::FETCH_ASSOC);
		$departmentStats['late_count'] = $lateResult['late_count'];
	}
	
	Database::disconnect();
	
	// Format time from datetime
	function formatTime($datetime) {
		if (!$datetime) return '--:--';
		return date('h:i A', strtotime($datetime));
	}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta charset="utf-8">
	<title>Admin Dashboard - Time Tracking System</title>
	
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
							<a class="nav-link active" href="admin-dashboard.php">Admin Dashboard</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="reports.php">Reports</a>
						</li>
					</ul>
					
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
				</div>
			</div>
		</nav>
		
		<!-- Date, Filters and Refresh -->
		<div class="row mb-4">
			<div class="col-md-6">
				<h4><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h4>
			</div>
			<div class="col-md-6 text-end">
				<h5 id="current-date" class="text-muted"></h5>
				<h3 id="current-time"></h3>
			</div>
		</div>
		
		<!-- Filters -->
		<div class="card mb-4">
			<div class="card-body">
				<form id="filter-form" class="row g-3" method="GET">
					<div class="col-md-3">
						<label for="department-filter" class="form-label">Department</label>
						<select class="form-select" id="department-filter" name="department">
							<option value="">All Departments</option>
							<?php foreach ($departments as $dept): ?>
							<option value="<?php echo $dept; ?>" <?php if ($department === $dept) echo 'selected'; ?>>
								<?php echo $dept; ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>
					
					<div class="col-md-3">
						<label for="start-date" class="form-label">Start Date</label>
						<input type="text" class="form-control date-picker" id="start-date" name="start_date" value="<?php echo $startDate; ?>">
					</div>
					
					<div class="col-md-3">
						<label for="end-date" class="form-label">End Date</label>
						<input type="text" class="form-control date-picker" id="end-date" name="end_date" value="<?php echo $endDate; ?>">
					</div>
					
					<div class="col-md-3 d-flex align-items-end">
						<button type="submit" class="btn btn-primary">
							<i class="fas fa-filter"></i> Apply Filters
						</button>
						<button type="button" id="refresh-dashboard" class="btn btn-secondary ms-2">
							<i class="fas fa-sync-alt"></i>
						</button>
					</div>
				</form>
			</div>
		</div>
		
		<!-- Today's Summary Stats -->
		<?php if ($dailyAttendance['status'] === 'success'): ?>
		<div class="row">
			<div class="col-lg-3 col-md-6 mb-4">
				<div class="stat-card present-card">
					<span class="stat-label">Present</span>
					<div class="stat-number"><?php echo $dailyAttendance['data']['present']; ?></div>
					<span class="stat-label">of <?php echo $dailyAttendance['data']['total_employees']; ?> employees</span>
				</div>
			</div>
			
			<div class="col-lg-3 col-md-6 mb-4">
				<div class="stat-card late-card">
					<span class="stat-label">Late</span>
					<div class="stat-number"><?php echo $dailyAttendance['data']['late']; ?></div>
					<span class="stat-label">employees today</span>
				</div>
			</div>
			
			<div class="col-lg-3 col-md-6 mb-4">
				<div class="stat-card absent-card">
					<span class="stat-label">Absent</span>
					<div class="stat-number"><?php echo $dailyAttendance['data']['absent']; ?></div>
					<span class="stat-label">employees today</span>
				</div>
			</div>
			
			<div class="col-lg-3 col-md-6 mb-4">
				<div class="stat-card hours-card">
					<span class="stat-label">Total Hours</span>
					<div class="stat-number"><?php echo $dailyAttendance['data']['total_hours']; ?></div>
					<span class="stat-label">worked today</span>
				</div>
			</div>
		</div>
		<?php endif; ?>
		
		<!-- Department-specific stats if a department is selected -->
		<?php if ($departmentStats): ?>
		<div class="card mb-4">
			<div class="card-header bg-primary text-white">
				<h5 class="mb-0"><?php echo $department; ?> Department Statistics</h5>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-3 text-center">
						<h6>Total Employees</h6>
						<h3 class="text-primary"><?php echo $departmentStats['total_employees']; ?></h3>
					</div>
					<div class="col-md-3 text-center">
						<h6>Currently Present</h6>
						<h3 class="text-success"><?php echo $departmentStats['currently_present']; ?></h3>
					</div>
					<div class="col-md-3 text-center">
						<h6>Late Employees</h6>
						<h3 class="text-warning"><?php echo $departmentStats['late_count']; ?></h3>
					</div>
					<div class="col-md-3 text-center">
						<h6>Avg. Work Hours</h6>
						<h3 class="text-info"><?php echo number_format($departmentStats['avg_hours'], 2); ?></h3>
					</div>
				</div>
			</div>
		</div>
		<?php endif; ?>
		
		<!-- Two Column Layout -->
		<div class="row">
			<!-- Left Column - Charts -->
			<div class="col-lg-6">
				<!-- Attendance Distribution -->
				<div class="card mb-4">
					<div class="card-header bg-primary text-white">
						<h5 class="mb-0">Attendance Distribution</h5>
					</div>
					<div class="card-body">
						<canvas id="attendanceChart" height="250"></canvas>
					</div>
				</div>
				
				<!-- Department Summary -->
				<?php if ($dailyAttendance['status'] === 'success' && isset($dailyAttendance['data']['department_breakdown'])): ?>
				<div class="card mb-4">
					<div class="card-header bg-primary text-white">
						<h5 class="mb-0">Department Attendance</h5>
					</div>
					<div class="card-body">
						<div class="table-responsive">
							<table class="table table-sm table-striped">
								<thead>
									<tr>
										<th>Department</th>
										<th>Present</th>
										<th>Late</th>
										<th>Absent</th>
										<th>Attendance %</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($dailyAttendance['data']['department_breakdown'] as $dept): 
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
										<td><?php echo $present; ?></td>
										<td><?php echo $late; ?></td>
										<td><?php echo $absent; ?></td>
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
				</div>
				<?php endif; ?>
			</div>
			
			<!-- Right Column - Present Employees and Recent Activity -->
			<div class="col-lg-6">
				<!-- Currently Present Employees -->
				<div class="card mb-4">
					<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
						<h5 class="mb-0">Currently Present</h5>
						<span class="badge bg-light text-dark">
							<?php echo count($presentEmployees); ?> employees
						</span>
					</div>
					<div class="card-body p-0">
						<?php if (!empty($presentEmployees)): ?>
							<div class="table-responsive">
								<table class="table table-sm table-hover mb-0">
									<thead class="table-light">
										<tr>
											<th>Employee</th>
											<th>Department</th>
											<th>Clocked In At</th>
											<th>Actions</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($presentEmployees as $emp): 
											// Apply department filter if set
											if (!empty($department) && $emp['department'] !== $department) continue;
										?>
										<tr>
											<td>
												<?php if (!empty($emp['profile_image'])): ?>
													<img src="<?php echo $emp['profile_image']; ?>" alt="<?php echo $emp['name']; ?>" class="avatar-xs me-2">
												<?php else: ?>
													<div class="status-dot status-in"></div>
												<?php endif; ?>
												<?php echo $emp['name']; ?>
											</td>
											<td><?php echo $emp['department'] ?? 'N/A'; ?></td>
											<td><?php echo formatTime($emp['entry_time']); ?></td>
											<td>
												<a href="employee-details.php?id=<?php echo $emp['employee_id']; ?>" class="btn btn-sm btn-outline-primary">
													<i class="fas fa-eye"></i>
												</a>
											</td>
										</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php else: ?>
							<div class="alert alert-info m-3">
								<i class="fas fa-info-circle"></i> No employees are currently present.
							</div>
						<?php endif; ?>
					</div>
				</div>
				
				<!-- Late Arrival Alert -->
				<?php 
					// Count late employees today
					$lateEmployees = [];
					foreach ($employees as $emp) {
						if ($emp['current_status'] === 'in') {
							$pdo = Database::connect();
							$sql = "SELECT * FROM daily_records WHERE employee_id = ? AND work_date = ? AND status = 'late'";
							$q = $pdo->prepare($sql);
							$q->execute(array($emp['employee_id'], $today));
							$record = $q->fetch(PDO::FETCH_ASSOC);
							Database::disconnect();
							
							if ($record) {
								// Apply department filter if set
								if (empty($department) || $emp['department'] === $department) {
									$lateEmployees[] = [
										'employee' => $emp,
										'record' => $record
									];
								}
							}
						}
					}
				?>
				
				<?php if (!empty($lateEmployees)): ?>
				<div class="card mb-4 border-warning">
					<div class="card-header bg-warning text-dark">
						<h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Late Arrivals Today</h5>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table table-sm table-hover mb-0">
								<thead class="table-light">
									<tr>
										<th>Employee</th>
										<th>Department</th>
										<th>Arrived At</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($lateEmployees as $item): ?>
									<tr>
										<td>
											<?php echo $item['employee']['name']; ?>
										</td>
										<td><?php echo $item['employee']['department'] ?? 'N/A'; ?></td>
										<td><?php echo formatTime($item['record']['first_entry']); ?></td>
										<td>
											<a href="employee-details.php?id=<?php echo $item['employee']['employee_id']; ?>" class="btn btn-sm btn-outline-primary">
												<i class="fas fa-eye"></i>
											</a>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<?php endif; ?>
				
				<!-- Quick Actions -->
				<div class="card mb-4">
					<div class="card-header bg-primary text-white">
						<h5 class="mb-0">Quick Actions</h5>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-6 mb-3">
								<a href="registration.php" class="btn btn-outline-primary btn-lg w-100">
									<i class="fas fa-user-plus"></i><br>
									Register Employee
								</a>
							</div>
							<div class="col-6 mb-3">
								<a href="reports.php" class="btn btn-outline-success btn-lg w-100">
									<i class="fas fa-file-export"></i><br>
									Generate Reports
								</a>
							</div>
							<div class="col-6 mb-3">
								<a href="admin-settings.php" class="btn btn-outline-secondary btn-lg w-100">
									<i class="fas fa-cogs"></i><br>
									System Settings
								</a>
							</div>
							<div class="col-6 mb-3">
								<a href="admin-departments.php" class="btn btn-outline-info btn-lg w-100">
									<i class="fas fa-building"></i><br>
									Manage Departments
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		
		<!-- Employees Table with Status and Filters -->
		<div class="card mb-4">
			<div class="card-header bg-primary text-white">
				<h5 class="mb-0">All Employees</h5>
			</div>
			<div class="card-body p-0">
				<?php if (!empty($employees)): ?>
					<div class="table-responsive">
						<table class="table table-hover mb-0" id="employees-table">
							<thead class="table-light">
								<tr>
									<th>Employee</th>
									<th>Department</th>
									<th>Position</th>
									<th>Status</th>
									<th>Last Activity</th>
									<th>Contact</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($employees as $emp): 
									// Apply department filter if set
									if (!empty($department) && $emp['department'] !== $department) continue;
								?>
								<tr>
									<td>
										<?php if (!empty($emp['profile_image'])): ?>
											<img src="<?php echo $emp['profile_image']; ?>" alt="<?php echo $emp['name']; ?>" class="avatar-xs me-2">
										<?php else: ?>
											<div class="status-dot <?php echo $emp['current_status'] === 'in' ? 'status-in' : 'status-out'; ?>"></div>
										<?php endif; ?>
										<?php echo $emp['name']; ?>
									</td>
									<td><?php echo $emp['department'] ?? 'N/A'; ?></td>
									<td><?php echo $emp['position'] ?? 'N/A'; ?></td>
									<td>
										<span class="badge <?php echo $emp['current_status'] === 'in' ? 'bg-success' : 'bg-danger'; ?>">
											<?php echo $emp['current_status'] === 'in' ? 'PRESENT' : 'ABSENT'; ?>
										</span>
									</td>
									<td>
										<?php 
											echo isset($emp['last_scan']) ? date('M d, h:i A', strtotime($emp['last_scan'])) : 'Never';
										?>
									</td>
									<td>
										<a href="mailto:<?php echo $emp['email']; ?>" class="btn btn-sm btn-outline-secondary">
											<i class="fas fa-envelope"></i>
										</a>
										<a href="tel:<?php echo $emp['mobile']; ?>" class="btn btn-sm btn-outline-secondary">
											<i class="fas fa-phone"></i>
										</a>
									</td>
									<td>
										<div class="btn-group" role="group">
											<a href="employee-details.php?id=<?php echo $emp['employee_id']; ?>" class="btn btn-sm btn-info">
												<i class="fas fa-eye"></i>
											</a>
											<a href="employee-dashboard.php?id=<?php echo $emp['employee_id']; ?>" class="btn btn-sm btn-primary">
												<i class="fas fa-tachometer-alt"></i>
											</a>
											<a href="user-data-edit-page.php?id=<?php echo $emp['rfid_uid']; ?>" class="btn btn-sm btn-warning">
												<i class="fas fa-edit"></i>
											</a>
										</div>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else: ?>
					<div class="alert alert-info m-3">
						<i class="fas fa-info-circle"></i> No employees found.
					</div>
				<?php endif; ?>
			</div>
			
			<!-- Search, Filter and Export Controls -->
			<div class="card-footer">
				<div class="row">
					<div class="col-md-6">
						<input type="text" id="employee-search" class="form-control" placeholder="Search employees...">
					</div>
					<div class="col-md-6 text-end">
						<div class="btn-group" role="group">
							<button type="button" class="btn btn-outline-primary" id="filter-present">Present Only</button>
							<button type="button" class="btn btn-outline-danger" id="filter-absent">Absent Only</button>
							<button type="button" class="btn btn-outline-secondary" id="filter-all">Show All</button>
						</div>
						<button type="button" class="btn btn-success ms-2" id="export-employee-csv">
							<i class="fas fa-download"></i> Export
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			// Initialize clock
			updateClock();
			
			// Initialize date pickers
			if (typeof flatpickr === 'function') {
				flatpickr('.date-picker', {
					dateFormat: 'Y-m-d',
					maxDate: 'today'
				});
			}
			
			// Refresh dashboard button
			document.getElementById('refresh-dashboard').addEventListener('click', function() {
				window.location.reload();
			});
			
			// Initialize attendance chart
			<?php if ($dailyAttendance['status'] === 'success'): ?>
			const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
			const attendanceChart = new Chart(attendanceCtx, {
				type: 'pie',
				data: {
					labels: ['Present', 'Late', 'Absent'],
					datasets: [{
						data: [
							<?php echo $dailyAttendance['data']['present'] - $dailyAttendance['data']['late']; ?>,
							<?php echo $dailyAttendance['data']['late']; ?>, 
							<?php echo $dailyAttendance['data']['absent']; ?>
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
			
			// Employee table filtering
			const employeeTable = document.getElementById('employees-table');
			if (employeeTable) {
				const rows = employeeTable.querySelectorAll('tbody tr');
				
				// Search functionality
				document.getElementById('employee-search').addEventListener('input', function() {
					const searchTerm = this.value.toLowerCase();
					
					rows.forEach(row => {
						const text = row.textContent.toLowerCase();
						if (text.includes(searchTerm)) {
							row.style.display = '';
						} else {
							row.style.display = 'none';
						}
					});
				});
				
				// Filter buttons
				document.getElementById('filter-present').addEventListener('click', function() {
					rows.forEach(row => {
						const statusBadge = row.querySelector('.badge');
						if (statusBadge && statusBadge.textContent.trim() === 'PRESENT') {
							row.style.display = '';
						} else {
							row.style.display = 'none';
						}
					});
				});
				
				document.getElementById('filter-absent').addEventListener('click', function() {
					rows.forEach(row => {
						const statusBadge = row.querySelector('.badge');
						if (statusBadge && statusBadge.textContent.trim() === 'ABSENT') {
							row.style.display = '';
						} else {
							row.style.display = 'none';
						}
					});
				});
				
				document.getElementById('filter-all').addEventListener('click', function() {
					rows.forEach(row => {
						row.style.display = '';
					});
				});
				
				// Export to CSV functionality
				document.getElementById('export-employee-csv').addEventListener('click', function() {
					let csvContent = "Name,Department,Position,Status,Last Activity,Email,Mobile\n";
					
					// Get only visible rows
					const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
					
					visibleRows.forEach(row => {
						const cells = row.querySelectorAll('td');
						
						// Extract data from each cell
						const name = cells[0].textContent.trim();
						const department = cells[1].textContent.trim();
						const position = cells[2].textContent.trim();
						const status = cells[3].textContent.trim();
						const lastActivity = cells[4].textContent.trim();
						const email = cells[5].querySelector('a[href^="mailto:"]').getAttribute('href').replace('mailto:', '');
						const mobile = cells[5].querySelector('a[href^="tel:"]').getAttribute('href').replace('tel:', '');
						
						// Add to CSV
						csvContent += `"${name}","${department}","${position}","${status}","${lastActivity}","${email}","${mobile}"\n`;
					});
					
					// Create and download the CSV file
					const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
					const url = URL.createObjectURL(blob);
					const link = document.createElement('a');
					link.setAttribute('href', url);
					link.setAttribute('download', 'employees_<?php echo date('Y-m-d'); ?>.csv');
					link.style.visibility = 'hidden';
					document.body.appendChild(link);
					link.click();
					document.body.removeChild(link);
				});
			}
		});
	</script>
</body>
</html>