<?php
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

<!DOCTYPE html>
<html lang="en">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta charset="utf-8">
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="css/styles.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
	<script src="js/bootstrap.bundle.min.js"></script>
	<script src="jquery.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<script src="js/time-tracking.js"></script>
	
	<title>Employee Details - Time Tracking System</title>
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
							<a class="nav-link active" href="user-data.php">Employees</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="registration.php">Registration</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="read-tag.php">Scan Card</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="dashboard.php">Dashboard</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="reports.php">Reports</a>
						</li>
					</ul>
				</div>
			</div>
		</nav>
		
		<?php if (isset($error)): ?>
			<div class="alert alert-danger">
				<?php echo $error; ?>
			</div>
		<?php elseif (!$employee): ?>
			<div class="alert alert-danger">
				Employee not found
			</div>
		<?php else: ?>
			
			<!-- Employee Profile -->
			<div class="card mb-4">
				<div class="card-header bg-primary text-white">
					<h4 class="mb-0">Employee Profile</h4>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-md-3 text-center">
							<!-- Profile image placeholder -->
							<div class="profile-image mb-3">
								<?php if (!empty($employee['profile_image'])): ?>
									<img src="<?php echo $employee['profile_image']; ?>" alt="Profile Image" class="img-fluid rounded-circle">
								<?php else: ?>
									<div class="profile-placeholder">
										<?php echo substr($employee['name'], 0, 1); ?>
									</div>
								<?php endif; ?>
							</div>
							
							<!-- Current status badge -->
							<div class="badge <?php echo ($employee['current_status'] == 'in') ? 'bg-success' : 'bg-danger'; ?> status-badge mb-3">
								<?php echo ($employee['current_status'] == 'in') ? 'CLOCKED IN' : 'CLOCKED OUT'; ?>
							</div>
							
							<!-- Actions -->
							<div class="d-grid gap-2">
								<a href="user-data-edit-page.php?id=<?php echo $employee['rfid_uid']; ?>" class="btn btn-primary">
									<i class="fas fa-edit"></i> Edit
								</a>
								<button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#addTimeLogModal">
									<i class="fas fa-clock"></i> Add Time Log
								</button>
							</div>
						</div>
						
						<div class="col-md-9">
							<div class="row">
								<div class="col-md-6">
									<h5>Personal Information</h5>
									<table class="table table-striped">
										<tr>
											<th>Name:</th>
											<td><?php echo $employee['name']; ?></td>
										</tr>
										<tr>
											<th>Gender:</th>
											<td><?php echo $employee['gender']; ?></td>
										</tr>
										<tr>
											<th>Email:</th>
											<td><?php echo $employee['email']; ?></td>
										</tr>
										<tr>
											<th>Mobile:</th>
											<td><?php echo $employee['mobile']; ?></td>
										</tr>
										<tr>
											<th>RFID Card:</th>
											<td><code><?php echo $employee['rfid_uid']; ?></code></td>
										</tr>
									</table>
								</div>
								
								<div class="col-md-6">
									<h5>Employment Information</h5>
									<table class="table table-striped">
										<tr>
											<th>Department:</th>
											<td><?php echo $employee['department'] ?? 'Not assigned'; ?></td>
										</tr>
										<tr>
											<th>Position:</th>
											<td><?php echo $employee['position'] ?? 'Not assigned'; ?></td>
										</tr>
										<tr>
											<th>Hire Date:</th>
											<td><?php echo $employee['hire_date'] ? date('M d, Y', strtotime($employee['hire_date'])) : 'Not set'; ?></td>
										</tr>
										<tr>
											<th>Status:</th>
											<td>
												<span class="badge <?php echo ($employee['employment_status'] == 'active') ? 'bg-success' : 'bg-danger'; ?>">
													<?php echo strtoupper($employee['employment_status']); ?>
												</span>
											</td>
										</tr>
										<tr>
											<th>Employee ID:</th>
											<td>#<?php echo $employee['employee_id']; ?></td>
										</tr>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<!-- Today's Activity -->
			<div class="card mb-4">
				<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
					<h4 class="mb-0">Today's Activity</h4>
					<span class="badge bg-light text-dark"><?php echo date('l, F j, Y'); ?></span>
				</div>
				<div class="card-body">
					<?php if ($todayLogs && isset($todayLogs['logs']) && count($todayLogs['logs']) > 0): ?>
						<div class="row">
							<div class="col-md-6">
								<div class="card mb-3">
									<div class="card-header bg-secondary text-white">
										<h5 class="mb-0">Time Logs</h5>
									</div>
									<div class="card-body p-0">
										<div class="table-responsive">
											<table class="table table-striped mb-0">
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
																<?php echo formatTime($log['timestamp']); ?>
															</td>
															<td>
																<span class="badge <?php echo ($log['log_type'] == 'entry') ? 'bg-success' : 'bg-danger'; ?>">
																	<?php echo ($log['log_type'] == 'entry') ? 'CLOCK IN' : 'CLOCK OUT'; ?>
																</span>
															</td>
															<td><?php echo $log['notes'] ?? ''; ?></td>
														</tr>
													<?php endforeach; ?>
												</tbody>
											</table>
										</div>
									</div>
								</div>
							</div>
							
							<div class="col-md-6">
								<div class="card">
									<div class="card-header bg-secondary text-white">
										<h5 class="mb-0">Summary</h5>
									</div>
									<div class="card-body">
										<?php if (isset($todayLogs['daily_record'])): ?>
											<?php $record = $todayLogs['daily_record']; ?>
											<div class="row text-center">
												<div class="col-6 mb-3">
													<h6>First Clock In</h6>
													<h4 class="text-primary">
														<?php echo $record['first_entry'] ? formatTime($record['first_entry']) : 'N/A'; ?>
													</h4>
												</div>
												<div class="col-6 mb-3">
													<h6>Last Clock Out</h6>
													<h4 class="text-primary">
														<?php echo $record['last_exit'] ? formatTime($record['last_exit']) : 'N/A'; ?>
													</h4>
												</div>
												<div class="col-6">
													<h6>Status</h6>
													<span class="badge 
														<?php if ($record['status'] == 'present') echo 'bg-success';
															  else if ($record['status'] == 'late') echo 'bg-warning text-dark';
															  else echo 'bg-danger'; ?>
														px-3 py-2">
														<?php echo strtoupper($record['status']); ?>
													</span>
												</div>
												<div class="col-6">
													<h6>Work Hours</h6>
													<h4 class="text-primary">
														<?php echo number_format($record['work_hours'], 2); ?>
													</h4>
												</div>
											</div>
										<?php else: ?>
											<p class="text-center">No activity summary available for today.</p>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					<?php else: ?>
						<p class="text-center">No activity recorded for today.</p>
					<?php endif; ?>
				</div>
			</div>
			
			<!-- Weekly Summary -->
			<div class="card mb-4">
				<div class="card-header bg-primary text-white">
					<h4 class="mb-0">Weekly Summary</h4>
				</div>
				<div class="card-body">
					<?php if ($weeklySummary && $weeklySummary['status'] === 'success'): ?>
						<div class="row">
							<div class="col-md-5">
								<div class="card">
									<div class="card-header bg-secondary text-white">
										<h5 class="mb-0">Weekly Stats</h5>
									</div>
									<div class="card-body text-center">
										<div class="row">
											<div class="col-6 mb-3">
												<h6>Present Days</h6>
												<h3 class="text-success">
													<?php echo $weeklySummary['data']['summary']['present_days']; ?>
													<small class="text-muted">/ <?php echo $weeklySummary['data']['summary']['total_days']; ?></small>
												</h3>
											</div>
											<div class="col-6 mb-3">
												<h6>Late Days</h6>
												<h3 class="text-warning">
													<?php echo $weeklySummary['data']['summary']['late_days']; ?>
												</h3>
											</div>
											<div class="col-6">
												<h6>Absences</h6>
												<h3 class="text-danger">
													<?php echo $weeklySummary['data']['summary']['absent_days']; ?>
												</h3>
											</div>
											<div class="col-6">
												<h6>Total Hours</h6>
												<h3 class="text-primary">
													<?php echo $weeklySummary['data']['summary']['total_hours']; ?>
												</h3>
											</div>
										</div>
										
										<div class="mt-3">
											<h6>Attendance Rate</h6>
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
							</div>
							
							<div class="col-md-7">
								<div class="card">
									<div class="card-header bg-secondary text-white">
										<h5 class="mb-0">Daily Records</h5>
									</div>
									<div class="card-body p-0">
										<div class="table-responsive">
											<table class="table table-striped mb-0">
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
																			  else echo 'bg-danger'; ?>">
																		<?php echo strtoupper($record['status']); ?>
																	</span>
																<?php elseif (!$isWeekend): ?>
																	<span class="badge bg-danger">ABSENT</span>
																<?php else: ?>
																	<span class="badge bg-secondary">N/A</span>
																<?php endif; ?>
															</td>
															<td>
																<?php echo $record && $record['first_entry'] ? formatTime($record['first_entry']) : '---'; ?>
															</td>
															<td>
																<?php echo $record && $record['last_exit'] ? formatTime($record['last_exit']) : '---'; ?>
															</td>
															<td>
																<?php echo $record ? number_format($record['work_hours'], 2) : '0.00'; ?>
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
						<p class="text-center">Weekly summary data not available.</p>
					<?php endif; ?>
				</div>
			</div>
			
			<!-- Add Time Log Modal -->
			<div class="modal fade" id="addTimeLogModal" tabindex="-1" aria-labelledby="addTimeLogModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header bg-primary text-white">
							<h5 class="modal-title" id="addTimeLogModalLabel">Add Manual Time Log</h5>
							<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<form id="addTimeLogForm">
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
									<input type="date" class="form-control" id="logDate" name="log_date" value="<?php echo date('Y-m-d'); ?>" required>
								</div>
								
								<div class="mb-3">
									<label for="logTime" class="form-label">Time</label>
									<input type="time" class="form-control" id="logTime" name="log_time" value="<?php echo date('H:i'); ?>" required>
								</div>
								
								<div class="mb-3">
									<label for="logNotes" class="form-label">Notes</label>
									<textarea class="form-control" id="logNotes" name="notes" rows="3" placeholder="Reason for manual entry">Manual entry by administrator</textarea>
								</div>
								
								<input type="hidden" name="employee_id" value="<?php echo $employee['employee_id']; ?>">
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
								<button type="submit" class="btn btn-primary">Add Log</button>
							</div>
						</form>
					</div>
				</div>
			</div>
			
			<script>
				// Handle manual time log form submission
				document.getElementById('addTimeLogForm').addEventListener('submit', function(e) {
					e.preventDefault();
					
					const formData = new FormData(this);
					const employeeId = formData.get('employee_id');
					const logType = formData.get('log_type');
					const logDate = formData.get('log_date');
					const logTime = formData.get('log_time');
					const notes = formData.get('notes');
					
					// Create timestamp in format YYYY-MM-DD HH:MM:SS
					const timestamp = `${logDate} ${logTime}:00`;
					
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
						if (data.status === 'success') {
							alert('Time log added successfully');
							// Reload the page to show the new log
							window.location.reload();
						} else {
							alert('Error: ' + data.message);
						}
					})
					.catch(error => {
						console.error('Error:', error);
						alert('An error occurred while adding the time log');
					});
				});
			</script>
		<?php endif; ?>
	</div>
</body>
</html>