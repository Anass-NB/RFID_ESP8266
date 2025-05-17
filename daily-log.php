<?php
	// Reset UID container
	$Write="<?php $" . "UIDresult=''; " . "echo $" . "UIDresult;" . " ?>";
	file_put_contents('UIDContainer.php',$Write);
	
	// Include time tracking functions
	require_once 'time_tracking.php';
	
	// Get request parameters
	$employeeId = isset($_GET['employee_id']) ? $_GET['employee_id'] : null;
	$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
	
	// If no employee ID, redirect to main dashboard
	if (!$employeeId) {
		header("Location: dashboard.php");
		exit;
	}
	
	// Get employee information
	$employeeResult = TimeTracking::getEmployeeStatus($employeeId);
	$employee = ($employeeResult['status'] === 'success') ? $employeeResult['data']['employee'] : null;
	
	if (!$employee) {
		$error = "Employee not found";
	}
	
	// Get time logs for the specified date
	$logsResult = TimeTracking::getDailyTimeLogs($employeeId, $date);
	$timeLogs = [];
	$dailyRecord = null;
	
	if ($logsResult['status'] === 'success') {
		$timeLogs = $logsResult['data']['logs'];
		$dailyRecord = $logsResult['data']['daily_record'];
	}
	
	// Format time from datetime
	function formatTime($datetime) {
		if (!$datetime) return '--:--';
		return date('h:i:s A', strtotime($datetime));
	}
	
	// Format date for display
	$displayDate = date('l, F j, Y', strtotime($date));
	
	// Calculate time difference between two timestamps
	function calculateDuration($start, $end) {
		if (!$start || !$end) return '--:--';
		
		$startTime = new DateTime($start);
		$endTime = new DateTime($end);
		$interval = $startTime->diff($endTime);
		
		$hours = $interval->h;
		$minutes = $interval->i;
		$seconds = $interval->s;
		
		return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
	}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta charset="utf-8">
	<title>Daily Time Log - Employee Time Tracking System</title>
	
	<!-- Stylesheets -->
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="css/styles.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
	<!-- Scripts -->
	<script src="js/bootstrap.bundle.min.js"></script>
	<script src="jquery.min.js"></script>
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
		<?php else: ?>
			<!-- Header section with employee info and date -->
			<div class="card mb-4">
				<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
					<h4 class="mb-0">
						<?php echo $employee['name']; ?> - Daily Time Log
					</h4>
					<nav aria-label="Date navigation">
						<ul class="pagination mb-0">
							<?php 
								$prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
								$nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
								$today = date('Y-m-d');
								// Don't allow navigating to future dates
								$canGoNext = $nextDate <= $today;
							?>
							<li class="page-item">
								<a class="page-link" href="daily-log.php?employee_id=<?php echo $employeeId; ?>&date=<?php echo $prevDate; ?>" aria-label="Previous">
									<span aria-hidden="true">&laquo;</span>
								</a>
							</li>
							<li class="page-item">
								<span class="page-link bg-white text-primary">
									<?php echo $displayDate; ?>
								</span>
							</li>
							<li class="page-item <?php echo $canGoNext ? '' : 'disabled'; ?>">
								<a class="page-link" href="<?php echo $canGoNext ? 'daily-log.php?employee_id=' . $employeeId . '&date=' . $nextDate : '#'; ?>" aria-label="Next">
									<span aria-hidden="true">&raquo;</span>
								</a>
							</li>
						</ul>
					</nav>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-md-4 text-center">
							<!-- Profile image -->
							<div class="profile-image mx-auto mb-3">
								<?php if (!empty($employee['profile_image'])): ?>
									<img src="<?php echo $employee['profile_image']; ?>" alt="Profile Image" class="img-fluid rounded-circle">
								<?php else: ?>
									<div class="profile-placeholder">
										<?php echo substr($employee['name'], 0, 1); ?>
									</div>
								<?php endif; ?>
							</div>
							
							<h5><?php echo $employee['name']; ?></h5>
							<p>
								<?php if ($employee['department']): ?>
									<span class="badge bg-secondary"><?php echo $employee['department']; ?></span>
								<?php endif; ?>
								
								<?php if ($employee['position']): ?>
									<span class="badge bg-info"><?php echo $employee['position']; ?></span>
								<?php endif; ?>
							</p>
						</div>
						
						<div class="col-md-8">
							<div class="row">
								<div class="col-md-6">
									<div class="card mb-3">
										<div class="card-body p-3 text-center">
											<h6 class="card-title text-muted mb-1">Status</h6>
											<?php if ($dailyRecord): ?>
												<span class="badge 
													<?php if ($dailyRecord['status'] == 'present') echo 'bg-success';
														  else if ($dailyRecord['status'] == 'late') echo 'bg-warning text-dark';
														  else echo 'bg-danger'; ?> px-4 py-2 fs-6">
													<?php echo strtoupper($dailyRecord['status']); ?>
												</span>
											<?php else: ?>
												<span class="badge bg-danger px-4 py-2 fs-6">ABSENT</span>
											<?php endif; ?>
										</div>
									</div>
								</div>
								
								<div class="col-md-6">
									<div class="card mb-3">
										<div class="card-body p-3 text-center">
											<h6 class="card-title text-muted mb-1">Total Work Hours</h6>
											<h3 class="text-primary">
												<?php echo $dailyRecord ? number_format($dailyRecord['work_hours'], 2) : '0.00'; ?>
											</h3>
										</div>
									</div>
								</div>
								
								<div class="col-md-6">
									<div class="card mb-3">
										<div class="card-body p-3 text-center">
											<h6 class="card-title text-muted mb-1">First Clock In</h6>
											<h5 class="text-success">
												<?php echo $dailyRecord && $dailyRecord['first_entry'] ? formatTime($dailyRecord['first_entry']) : 'Not Recorded'; ?>
											</h5>
										</div>
									</div>
								</div>
								
								<div class="col-md-6">
									<div class="card mb-3">
										<div class="card-body p-3 text-center">
											<h6 class="card-title text-muted mb-1">Last Clock Out</h6>
											<h5 class="text-danger">
												<?php echo $dailyRecord && $dailyRecord['last_exit'] ? formatTime($dailyRecord['last_exit']) : 'Not Recorded'; ?>
											</h5>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<div class="card-footer">
					<div class="btn-group" role="group">
						<a href="employee-details.php?id=<?php echo $employeeId; ?>" class="btn btn-primary">
							<i class="fas fa-user"></i> Employee Profile
						</a>
						<a href="employee-dashboard.php?id=<?php echo $employeeId; ?>" class="btn btn-info">
							<i class="fas fa-tachometer-alt"></i> Dashboard
						</a>
						<a href="reports.php?employee_id=<?php echo $employeeId; ?>&start_date=<?php echo $date; ?>&end_date=<?php echo $date; ?>" class="btn btn-success">
							<i class="fas fa-file-export"></i> Generate Report
						</a>
					</div>
					
					<button type="button" class="btn btn-secondary float-end" data-bs-toggle="modal" data-bs-target="#addLogModal">
						<i class="fas fa-plus"></i> Add Manual Entry
					</button>
				</div>
			</div>
			
			<!-- Time log entries -->
			<div class="card mb-4">
				<div class="card-header bg-primary text-white">
					<h5 class="mb-0">Detailed Time Log Entries</h5>
				</div>
				<div class="card-body p-0">
					<?php if (!empty($timeLogs)): ?>
						<div class="table-responsive">
							<table class="table table-striped table-hover mb-0">
								<thead class="table-light">
									<tr>
										<th>Time</th>
										<th>Action</th>
										<th>Duration</th>
										<th>Notes</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php 
										$entryTime = null;
										foreach($timeLogs as $index => $log): 
											$duration = '--:--';
											
											// Calculate duration for pairs of entry and exit
											if ($log['log_type'] === 'entry') {
												$entryTime = $log['timestamp'];
											} elseif ($log['log_type'] === 'exit' && $entryTime) {
												$duration = calculateDuration($entryTime, $log['timestamp']);
												$entryTime = null;
											}
									?>
										<tr>
											<td><?php echo formatTime($log['timestamp']); ?></td>
											<td>
												<span class="badge <?php echo ($log['log_type'] == 'entry') ? 'bg-success' : 'bg-danger'; ?> px-3">
													<?php echo ($log['log_type'] == 'entry') ? 'CLOCK IN' : 'CLOCK OUT'; ?>
												</span>
											</td>
											<td>
												<?php if ($log['log_type'] === 'exit'): ?>
													<?php echo $duration; ?>
												<?php else: ?>
													--:--
												<?php endif; ?>
											</td>
											<td><?php echo $log['notes'] ?: '--'; ?></td>
											<td>
												<button type="button" class="btn btn-sm btn-warning edit-log-btn" 
														data-log-id="<?php echo $log['log_id']; ?>" 
														data-log-type="<?php echo $log['log_type']; ?>"
														data-timestamp="<?php echo $log['timestamp']; ?>"
														data-notes="<?php echo $log['notes']; ?>">
													<i class="fas fa-edit"></i> Edit
												</button>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php else: ?>
						<div class="alert alert-info m-3">
							<i class="fas fa-info-circle"></i> No time log entries recorded for this date.
						</div>
					<?php endif; ?>
				</div>
				
				<div class="card-footer">
					<p class="text-muted">
						<i class="fas fa-info-circle"></i> 
						Duration is calculated between each entry and exit pair.
					</p>
				</div>
			</div>
			
			<!-- Timeline visualization -->
			<div class="card mb-4">
				<div class="card-header bg-primary text-white">
					<h5 class="mb-0">Time Log Timeline</h5>
				</div>
				<div class="card-body">
					<?php if (!empty($timeLogs)): ?>
						<div class="timeline-container">
							<?php 
								// Get work day boundaries from settings
								$workStart = '09:00';
								$workEnd = '17:00';
								
								// Calculate timeline range (full day)
								$dayStart = '08:00';
								$dayEnd = '18:00';
								
								// Calculate the total minutes in the timeline
								$timelineStart = strtotime($date . ' ' . $dayStart);
								$timelineEnd = strtotime($date . ' ' . $dayEnd);
								$totalMinutes = ($timelineEnd - $timelineStart) / 60;
								
								// Work hours boundaries 
								$workStartTime = strtotime($date . ' ' . $workStart);
								$workEndTime = strtotime($date . ' ' . $workEnd);
								$workStartPercent = (($workStartTime - $timelineStart) / 60) / $totalMinutes * 100;
								$workEndPercent = (($workEndTime - $timelineStart) / 60) / $totalMinutes * 100;
								$workWidthPercent = $workEndPercent - $workStartPercent;
							?>
							
							<!-- Timeline scale -->
							<div class="timeline-scale">
								<?php
									// Generate hour marks on the timeline
									$startHour = (int)substr($dayStart, 0, 2);
									$endHour = (int)substr($dayEnd, 0, 2);
									
									for ($hour = $startHour; $hour <= $endHour; $hour++) {
										$time = sprintf('%02d:00', $hour);
										$timePoint = strtotime($date . ' ' . $time);
										$positionPercent = (($timePoint - $timelineStart) / 60) / $totalMinutes * 100;
										
										echo '<div class="timeline-hour" style="left: ' . $positionPercent . '%;">';
										echo '<div class="timeline-hour-mark"></div>';
										echo '<div class="timeline-hour-label">' . date('g:i A', $timePoint) . '</div>';
										echo '</div>';
									}
								?>
							</div>
							
							<!-- Work hours background -->
							<div class="timeline-work-hours" style="left: <?php echo $workStartPercent; ?>%; width: <?php echo $workWidthPercent; ?>%;">
								<span>Work Hours</span>
							</div>
							
							<!-- Timeline events -->
							<div class="timeline-events">
								<?php foreach($timeLogs as $log): 
									$logTime = strtotime($log['timestamp']);
									$positionPercent = (($logTime - $timelineStart) / 60) / $totalMinutes * 100;
									
									// Keep positions within the timeline
									if ($positionPercent < 0) $positionPercent = 0;
									if ($positionPercent > 100) $positionPercent = 100;
									
									$isEntry = $log['log_type'] == 'entry';
								?>
									<div class="timeline-event <?php echo $isEntry ? 'event-entry' : 'event-exit'; ?>" 
										 style="left: <?php echo $positionPercent; ?>%;"
										 title="<?php echo ($isEntry ? 'Clock In: ' : 'Clock Out: ') . formatTime($log['timestamp']); ?>">
										<div class="timeline-event-marker"></div>
										<div class="timeline-event-label">
											<?php echo formatTime($log['timestamp']); ?>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php else: ?>
						<div class="alert alert-info">
							<i class="fas fa-info-circle"></i> No time log data available to display on timeline.
						</div>
					<?php endif; ?>
				</div>
			</div>
			
			<!-- Add Manual Time Log Modal -->
			<div class="modal fade" id="addLogModal" tabindex="-1" aria-labelledby="addLogModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header bg-primary text-white">
							<h5 class="modal-title" id="addLogModalLabel">Add Manual Time Log Entry</h5>
							<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<form id="addLogForm">
							<div class="modal-body">
								<div class="mb-3">
									<label for="logType" class="form-label">Log Type</label>
									<select class="form-select" id="logType" name="log_type" required>
										<option value="entry">Clock In</option>
										<option value="exit">Clock Out</option>
									</select>
								</div>
								
								<div class="mb-3">
									<label for="logTime" class="form-label">Time</label>
									<input type="time" class="form-control" id="logTime" name="log_time" value="<?php echo date('H:i'); ?>" required>
								</div>
								
								<div class="mb-3">
									<label for="logNotes" class="form-label">Notes</label>
									<textarea class="form-control" id="logNotes" name="notes" rows="3">Manual entry by administrator</textarea>
								</div>
								
								<input type="hidden" name="employee_id" value="<?php echo $employeeId; ?>">
								<input type="hidden" name="log_date" value="<?php echo $date; ?>">
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
								<button type="submit" class="btn btn-primary">Add Entry</button>
							</div>
						</form>
					</div>
				</div>
			</div>
			
			<!-- Edit Time Log Modal -->
			<div class="modal fade" id="editLogModal" tabindex="-1" aria-labelledby="editLogModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header bg-warning">
							<h5 class="modal-title" id="editLogModalLabel">Edit Time Log Entry</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<form id="editLogForm">
							<div class="modal-body">
								<div class="mb-3">
									<label for="editLogType" class="form-label">Log Type</label>
									<select class="form-select" id="editLogType" name="log_type" disabled>
										<option value="entry">Clock In</option>
										<option value="exit">Clock Out</option>
									</select>
								</div>
								
								<div class="mb-3">
									<label for="editLogTime" class="form-label">Time</label>
									<input type="time" class="form-control" id="editLogTime" name="log_time" required>
								</div>
								
								<div class="mb-3">
									<label for="editLogNotes" class="form-label">Notes</label>
									<textarea class="form-control" id="editLogNotes" name="notes" rows="3"></textarea>
								</div>
								
								<input type="hidden" name="log_id" id="editLogId">
								<input type="hidden" name="employee_id" value="<?php echo $employeeId; ?>">
								<input type="hidden" name="log_date" value="<?php echo $date; ?>">
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
								<button type="submit" class="btn btn-warning">Update Entry</button>
							</div>
						</form>
					</div>
				</div>
			</div>
			
			<style>
				/* Timeline styles */
				.timeline-container {
					position: relative;
					height: 120px;
					background-color: #f8f9fa;
					border-radius: 4px;
					margin: 20px 0;
					padding: 20px 10px;
				}
				
				.timeline-scale {
					position: relative;
					height: 30px;
					border-bottom: 2px solid #ddd;
				}
				
				.timeline-hour {
					position: absolute;
					transform: translateX(-50%);
				}
				
				.timeline-hour-mark {
					height: 8px;
					width: 2px;
					background-color: #aaa;
					margin: 0 auto;
				}
				
				.timeline-hour-label {
					font-size: 10px;
					text-align: center;
					margin-top: 4px;
					color: #666;
				}
				
				.timeline-work-hours {
					position: absolute;
					height: 30px;
					top: 40px;
					background-color: rgba(23, 162, 184, 0.1);
					border: 1px dashed #17a2b8;
					border-radius: 4px;
					display: flex;
					align-items: center;
					justify-content: center;
				}
				
				.timeline-work-hours span {
					font-size: 12px;
					color: #17a2b8;
				}
				
				.timeline-events {
					position: relative;
					height: 60px;
					margin-top: 10px;
				}
				
				.timeline-event {
					position: absolute;
					transform: translateX(-50%);
				}
				
				.timeline-event-marker {
					width: 12px;
					height: 12px;
					border-radius: 50%;
					margin: 0 auto;
				}
				
				.timeline-event-label {
					font-size: 10px;
					text-align: center;
					margin-top: 4px;
					white-space: nowrap;
				}
				
				.event-entry .timeline-event-marker {
					background-color: #28a745;
				}
				
				.event-exit .timeline-event-marker {
					background-color: #dc3545;
				}
				
				.event-entry .timeline-event-label {
					color: #28a745;
					font-weight: bold;
				}
				
				.event-exit .timeline-event-label {
					color: #dc3545;
					font-weight: bold;
				}
			</style>
			
			<script>
				document.addEventListener('DOMContentLoaded', function() {
					// Form submission for adding manual time log
					document.getElementById('addLogForm').addEventListener('submit', function(e) {
						e.preventDefault();
						
						const formData = new FormData(this);
						const employeeId = formData.get('employee_id');
						const logType = formData.get('log_type');
						const logTime = formData.get('log_time');
						const logDate = formData.get('log_date');
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
					
					// Initialize edit log buttons
					document.querySelectorAll('.edit-log-btn').forEach(button => {
						button.addEventListener('click', function() {
							const logId = this.getAttribute('data-log-id');
							const logType = this.getAttribute('data-log-type');
							const timestamp = this.getAttribute('data-timestamp');
							const notes = this.getAttribute('data-notes');
							
							// Extract time from timestamp (format: HH:MM)
							const time = timestamp.split(' ')[1].substring(0, 5);
							
							// Populate edit form
							document.getElementById('editLogId').value = logId;
							document.getElementById('editLogType').value = logType;
							document.getElementById('editLogTime').value = time;
							document.getElementById('editLogNotes').value = notes;
							
							// Show the modal
							new bootstrap.Modal(document.getElementById('editLogModal')).show();
						});
					});
					
					// Edit log form submission
					document.getElementById('editLogForm').addEventListener('submit', function(e) {
						e.preventDefault();
						
						// Get form data
						const formData = new FormData(this);
						
						// Alert the user about the unimplemented feature
						alert('This feature is under development. Time log updates will be available in a future update.');
						
						// Close the modal
						bootstrap.Modal.getInstance(document.getElementById('editLogModal')).hide();
					});
				});
			</script>
		<?php endif; ?>
	</div>
</body>
</html>