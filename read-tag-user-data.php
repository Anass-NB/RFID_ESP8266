<?php
    require 'database.php';
    require_once 'time_tracking.php';
    
    $id = null;
    if (!empty($_GET['id'])) {
        $id = $_REQUEST['id'];
    }
     
    $pdo = Database::connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = "SELECT * FROM employees WHERE rfid_uid = ?";
    $q = $pdo->prepare($sql);
    $q->execute(array($id));
    $data = $q->fetch(PDO::FETCH_ASSOC);
    
    // If employee found, get their current status and time logs for today
    $employeeStatus = null;
    $todayLogs = null;
    
    if ($data && isset($data['employee_id'])) {
        // Get employee status
        $statusResult = TimeTracking::getEmployeeStatus($data['employee_id']);
        if ($statusResult['status'] === 'success') {
            $employeeStatus = $statusResult['data'];
        }
        
        // Get today's logs
        $today = date('Y-m-d');
        $logsResult = TimeTracking::getDailyTimeLogs($data['employee_id'], $today);
        if ($logsResult['status'] === 'success') {
            $todayLogs = $logsResult['data'];
        }
    }
    
    Database::disconnect();
    
    $msg = null;
    if (null == $data['name']) {
        $msg = "The ID of your Card / KeyChain is not registered !!!";
        $data['rfid_uid'] = $id;
        $data['name'] = "--------";
        $data['gender'] = "--------";
        $data['email'] = "--------";
        $data['mobile'] = "--------";
        $data['department'] = "--------";
        $data['position'] = "--------";
    } else {
        $msg = null;
    }
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <script src="js/bootstrap.min.js"></script>
    <title>Employee Information</title>
</head>
 
<body>    
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Employee Information</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <!-- Profile image placeholder -->
                                <div class="profile-image mb-3">
                                    <?php if (!empty($data['profile_image'])): ?>
                                        <img src="<?php echo $data['profile_image']; ?>" alt="Profile Image" class="img-fluid rounded-circle">
                                    <?php else: ?>
                                        <div class="profile-placeholder">
                                            <?php echo substr($data['name'], 0, 1); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Current status badge -->
                                <?php if (isset($data['current_status'])): ?>
                                    <div class="badge <?php echo ($data['current_status'] == 'in') ? 'bg-success' : 'bg-danger'; ?> status-badge">
                                        <?php echo ($data['current_status'] == 'in') ? 'CLOCKED IN' : 'CLOCKED OUT'; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-8">
                                <table class="table table-striped">
                                    <tr>
                                        <th>ID:</th>
                                        <td><?php echo $data['rfid_uid']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Name:</th>
                                        <td><?php echo $data['name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Gender:</th>
                                        <td><?php echo $data['gender']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Department:</th>
                                        <td><?php echo $data['department'] ?? '--------'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Position:</th>
                                        <td><?php echo $data['position'] ?? '--------'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email:</th>
                                        <td><?php echo $data['email']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Mobile:</th>
                                        <td><?php echo $data['mobile']; ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <?php if ($msg): ?>
                            <div class="alert alert-danger mt-3">
                                <?php echo $msg; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($todayLogs && count($todayLogs['logs']) > 0): ?>
                            <div class="mt-4">
                                <h5>Today's Activity</h5>
                                <table class="table table-sm">
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
                                                    <?php 
                                                        $timestamp = new DateTime($log['timestamp']);
                                                        echo $timestamp->format('h:i A'); 
                                                    ?>
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
                                
                                <?php if (isset($todayLogs['daily_record']) && $todayLogs['daily_record']['work_hours'] > 0): ?>
                                    <div class="alert alert-info">
                                        <strong>Work Hours Today:</strong> 
                                        <?php echo number_format($todayLogs['daily_record']['work_hours'], 2); ?> hours
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer">
                        <a href="read-tag.php" class="btn btn-primary">Back to Scanner</a>
                        <?php if (isset($data['employee_id'])): ?>
                            <a href="employee-details.php?id=<?php echo $data['employee_id']; ?>" class="btn btn-info">View Full Details</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>