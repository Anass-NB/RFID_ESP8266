<?php
/**
 *  Dashboard.php
 *
 * Main Dashboard Page (Redesigned with Bootstrap)
 * Shows attendance overview and key metrics with a modern UI
 */

// Include header
require_once 'includes/header.php';
require_once 'time_tracking.php';

// Get today's date
$today = date('Y-m-d');

// Fetch data
$attendanceSummary = TimeTracking::getDailyAttendanceSummary($today);
$employeesResult      = TimeTracking::getAllEmployees();
$employees            = $employeesResult['status'] === 'success' ? $employeesResult['data'] : [];
$presentResult        = TimeTracking::getPresentEmployees();
$presentEmployees     = $presentResult['status'] === 'success' ? $presentResult['data'] : [];
?>

<div class="container-fluid px-4 py-3">
  <!-- Header -->
  <div class="row mb-4">
    <div class="col-12 col-md-8">
      <h1 class="h3 mb-1 text-gray-800">
        <i class="fas fa-tachometer-alt me-2 text-primary"></i>Dashboard
      </h1>
      <p class="text-muted">Overview of today's attendance and key metrics</p>
    </div>
    <div class="col-12 col-md-4 text-md-end">
      <div id="current-date" class="fs-6 text-muted"></div>
      <div id="current-time" class="fs-5 fw-light font-monospace"></div>
    </div>
  </div>

  <!-- Summary Cards -->
  <?php if ($attendanceSummary['status'] === 'success'): ?>
  <div class="row g-3 mb-4">
    <!-- Present Card -->
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-shrink-0 bg-success-subtle p-3 rounded-3 text-success">
              <i class="fas fa-user-check fa-lg"></i>
            </div>
            <div class="flex-grow-1 ms-3">
              <h2 class="h3 fw-bold mb-0"><?php echo $attendanceSummary['data']['present']; ?></h2>
              <p class="text-muted mb-0">Present Today</p>
            </div>
          </div>
          <p class="mt-3 mb-0 small text-muted">of <?php echo $attendanceSummary['data']['total_employees']; ?> employees</p>
        </div>
      </div>
    </div>

    <!-- Late Card -->
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-shrink-0 bg-warning-subtle p-3 rounded-3 text-warning">
              <i class="fas fa-user-clock fa-lg"></i>
            </div>
            <div class="flex-grow-1 ms-3">
              <h2 class="h3 fw-bold mb-0"><?php echo $attendanceSummary['data']['late']; ?></h2>
              <p class="text-muted mb-0">Late Arrivals</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Absent Card -->
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-shrink-0 bg-danger-subtle p-3 rounded-3 text-danger">
              <i class="fas fa-user-slash fa-lg"></i>
            </div>
            <div class="flex-grow-1 ms-3">
              <h2 class="h3 fw-bold mb-0"><?php echo $attendanceSummary['data']['absent']; ?></h2>
              <p class="text-muted mb-0">Absent Today</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Hours Card -->
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-shrink-0 bg-primary-subtle p-3 rounded-3 text-primary">
              <i class="fas fa-clock fa-lg"></i>
            </div>
            <div class="flex-grow-1 ms-3">
              <h2 class="h3 fw-bold mb-0"><?php echo $attendanceSummary['data']['total_hours']; ?></h2>
              <p class="text-muted mb-0">Hours Worked</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Charts & Breakdown -->
  <div class="row g-4 mb-4">
    <!-- Attendance Chart -->
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">
            <i class="fas fa-chart-pie me-2"></i> Today's Attendance
          </h5>
        </div>
        <div class="card-body">
          <canvas id="attendanceChart" height="200"></canvas>
        </div>
      </div>
    </div>

    <!-- Department Breakdown -->
    <?php if ($attendanceSummary['status'] === 'success' && !empty($attendanceSummary['data']['department_breakdown'])): ?>
    <div class="col-12 col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">
            <i class="fas fa-building me-2"></i> Department Attendance
          </h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr class="table-light">
                  <th class="py-2">Dept</th>
                  <th class="py-2 text-center">Present</th>
                  <th class="py-2 text-center">Late</th>
                  <th class="py-2 text-center">Absent</th>
                  <th class="py-2">%</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($attendanceSummary['data']['department_breakdown'] as $dept):
                  $present = $dept['present_in_dept'] ?? 0;
                  $total   = $dept['total_in_dept'] ?? 0;
                  $late    = $dept['late_in_dept'] ?? 0;
                  $absent  = $total - $present;
                  $perc    = $total > 0 ? round(($present / $total) * 100) : 0;
                ?>
                <tr>
                  <td class="py-2">
                    <a href="admin-dashboard.php?department=<?php echo urlencode($dept['department'] ?? 'Unassigned'); ?>" 
                       class="text-decoration-none">
                      <i class="fas fa-folder me-1 text-muted"></i>
                      <?php echo htmlspecialchars($dept['department'] ?? 'Unassigned'); ?>
                    </a>
                  </td>
                  <td class="py-2 text-center"><?php echo $present; ?></td>
                  <td class="py-2 text-center"><?php echo $late; ?></td>
                  <td class="py-2 text-center"><?php echo $absent; ?></td>
                  <td class="py-2">
                    <div class="progress" style="height: 8px;">
                      <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $perc; ?>%" 
                           aria-valuenow="<?php echo $perc; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <span class="small text-muted"><?php echo $perc; ?>%</span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div class="card-footer text-center bg-white">
          <a href="reports.php" class="text-primary text-decoration-none fw-medium">
            View Detailed Reports <i class="fas fa-arrow-right ms-1"></i>
          </a>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Present Employees & Quick Actions -->
  <div class="row g-4 mb-4">
    <!-- Present Employees -->
    <div class="col-12 col-lg-5">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="fas fa-users me-2"></i> Currently Present
          </h5>
          <span class="badge bg-white text-success rounded-pill"><?php echo count($presentEmployees); ?></span>
        </div>
        <div class="card-body p-0" style="max-height: 320px; overflow-y: auto;">
          <ul class="list-group list-group-flush">
            <?php if (!empty($presentEmployees)): foreach($presentEmployees as $employee): ?>
            <li class="list-group-item">
              <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                  <?php if (!empty($employee['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($employee['profile_image']); ?>" alt="<?php echo htmlspecialchars($employee['name']); ?>" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                  <?php else: ?>
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-secondary fw-bold" style="width: 40px; height: 40px;">
                      <?php echo substr($employee['name'], 0, 1); ?>
                    </div>
                  <?php endif; ?>
                  <div class="ms-3">
                    <h6 class="mb-0"><?php echo htmlspecialchars($employee['name']); ?></h6>
                    <p class="text-muted mb-0 small">
                      <i class="far fa-clock me-1"></i>
                      <?php echo date('g:i A', strtotime($employee['entry_time'])); ?>
                    </p>
                  </div>
                </div>
                <a href="employee-details.php?id=<?php echo $employee['employee_id']; ?>" 
                   class="btn btn-sm btn-outline-primary rounded-pill">
                  <i class="fas fa-eye"></i>
                </a>
              </div>
            </li>
            <?php endforeach; else: ?>
            <li class="list-group-item py-5 text-center text-muted">
              No employees are currently present
            </li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-12 col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white">
          <h5 class="mb-0 text-dark">
            <i class="fas fa-bolt me-2 text-warning"></i>
            Quick Actions
          </h5>
        </div>
        <div class="card-body">
          <div class="d-grid gap-3">
            <a href="rfid-scan.php" class="btn btn-outline-secondary">
              <i class="fas fa-id-card me-2"></i> Scan RFID Card
            </a>
            <a href="registration.php" class="btn btn-outline-secondary">
              <i class="fas fa-user-plus me-2"></i> Add New Employee
            </a>
            <a href="reports.php" class="btn btn-outline-secondary">
              <i class="fas fa-file-export me-2"></i> Generate Reports
            </a>
            <a href="admin-dashboard.php" class="btn btn-outline-secondary">
              <i class="fas fa-cogs me-2"></i> Admin Dashboard
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-12 col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">
            <i class="fas fa-history me-2"></i> Recent Activity
          </h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
            <table class="table table-hover mb-0 small">
              <thead class="table-light">
                <tr>
                  <th class="py-2">Time</th>
                  <th class="py-2">Employee</th>
                  <th class="py-2">Action</th>
                  <th class="py-2">Department</th>
                </tr>
              </thead>
              <tbody>
                <?php
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
                  <td class="py-2"><?php echo date('g:i:s A', strtotime($log['timestamp'])); ?></td>
                  <td class="py-2">
                    <div class="d-flex align-items-center">
                      <?php if (!empty($log['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($log['profile_image']); ?>" alt="<?php echo htmlspecialchars($log['name']); ?>" 
                             class="rounded-circle me-2" width="24" height="24" style="object-fit: cover;">
                      <?php else: ?>
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-secondary fw-bold me-2" 
                             style="width: 24px; height: 24px; font-size: 12px;">
                          <?php echo substr($log['name'], 0, 1); ?>
                        </div>
                      <?php endif; ?>
                      <?php echo htmlspecialchars($log['name']); ?>
                    </div>
                  </td>
                  <td class="py-2">
                    <span class="badge <?php echo $log['log_type'] == 'entry' ? 'bg-success' : 'bg-danger'; ?>">
                      <?php echo $log['log_type'] == 'entry' ? 'Clock In' : 'Clock Out'; ?>
                    </span>
                  </td>
                  <td class="py-2">
                    <?php if (!empty($log['department'])): ?>
                      <?php echo htmlspecialchars($log['department']); ?>
                    <?php else: ?>
                      <span class="text-muted">Not Assigned</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="4" class="py-5 text-center text-muted">No activity recorded today</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php if (!empty($logs)): ?>
        <div class="card-footer text-center bg-white">
          <a href="reports.php" class="text-primary text-decoration-none fw-medium">
            View All Activity <i class="fas fa-arrow-right ms-1"></i>
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Clock update
    function updateClock() {
      const now = new Date();
      document.getElementById('current-time').textContent = now.toLocaleTimeString();
      document.getElementById('current-date').textContent = now.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
      setTimeout(updateClock, 1000);
    }
    updateClock();

    // Attendance Chart
    <?php if ($attendanceSummary['status'] === 'success'): ?>
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const attendanceChart = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['On-Time', 'Late', 'Absent'],
        datasets: [{
          data: [
            <?php echo $attendanceSummary['data']['present'] - $attendanceSummary['data']['late']; ?>, 
            <?php echo $attendanceSummary['data']['late']; ?>, 
            <?php echo $attendanceSummary['data']['absent']; ?>
          ],
          backgroundColor: [
            'rgba(40, 167, 69, 0.8)',
            'rgba(255, 193, 7, 0.8)',
            'rgba(220, 53, 69, 0.8)'
          ],
          borderColor: [
            'rgba(40, 167, 69, 1)',
            'rgba(255, 193, 7, 1)',
            'rgba(220, 53, 69, 1)'
          ],
          borderWidth: 1,
          hoverOffset: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '60%',
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });
    <?php endif; ?>
  });
</script>

<?php require_once 'includes/footer.php'; ?>
