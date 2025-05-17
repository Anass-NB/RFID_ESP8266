<?php
/**
 *   Registration.php

 * Employee Registration Page
 * Used to register new employees and associate RFID cards
 */

// Include header with proper page title
$pageTitle = "Employee Registration";
require_once 'includes/header.php';

// Reset UID container
$Write="<?php $" . "UIDresult=''; " . "echo $" . "UIDresult;" . " ?>";
file_put_contents('UIDContainer.php',$Write);

// Get departments for dropdown
$departments = [];
$pdo = Database::connect();
$sql = "SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department";
foreach ($pdo->query($sql) as $row) {
    $departments[] = $row['department'];
}
Database::disconnect();

// Check if the form was submitted, to show success message
$formSubmitted = false;
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $formSubmitted = true;
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-0"><i class="fas fa-user-plus me-2"></i> Employee Registration</h3>
        <p class="text-muted mb-0">Register new employees and associate RFID cards</p>
    </div>
    <a href="user-data.php" class="btn btn-outline-primary">
        <i class="fas fa-users me-2"></i> View All Employees
    </a>
</div>

<?php if ($formSubmitted): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <h4 class="alert-heading"><i class="fas fa-check-circle me-2"></i>Registration Successful!</h4>
    <p>The employee has been registered successfully and can now use their RFID card to clock in and out.</p>
    <hr>
    <p class="mb-0">You can <a href="user-data.php" class="alert-link">view all employees</a> or register another employee below.</p>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8 mb-4 mb-lg-0">
        <!-- Registration Form -->
        <div class="app-card">
            <div class="card-header bg-primary">
                <h5 class="mb-0 text-white"><i class="fas fa-clipboard-list me-2"></i>Registration Form</h5>
            </div>
            <div class="card-body">
                <form class="app-form needs-validation" action="insertDB.php" method="post" novalidate>
                    <div class="mb-4">
                        <label for="getUID" class="form-label">RFID Card ID</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            <textarea name="id" id="getUID" class="form-control" rows="1" placeholder="Scan RFID card to capture ID" readonly required></textarea>
                            <button type="button" class="btn btn-outline-secondary" id="clear-uid">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="form-text"><i class="fas fa-info-circle me-1"></i> Hold the card near the reader to automatically capture the ID</div>
                        <div class="invalid-feedback">RFID Card ID is required</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Enter employee name" required>
                            </div>
                            <div class="invalid-feedback">Please enter employee name</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="" disabled selected>Select gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="invalid-feedback">Please select a gender</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department" class="form-label">Department</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-building"></i></span>
                                <select class="form-select" id="department" name="department">
                                    <option value="" selected>Select department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept); ?>">
                                            <?php echo htmlspecialchars($dept); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="new">+ Add New Department</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3" id="new-department-container" style="display: none;">
                            <label for="new-department" class="form-label">New Department Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-plus-circle"></i></span>
                                <input type="text" class="form-control" id="new-department" name="new_department" placeholder="Enter department name">
                            </div>
                            <div class="invalid-feedback">Please enter new department name</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="position" class="form-label">Position/Job Title</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                                <input type="text" class="form-control" id="position" name="position" placeholder="Enter position">
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="hire-date" class="form-label">Hire Date</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                <input type="date" class="form-control" id="hire-date" name="hire_date" 
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter email address" required>
                            </div>
                            <div class="invalid-feedback">Please enter a valid email address</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="mobile" class="form-label">Mobile Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" class="form-control" id="mobile" name="mobile" placeholder="Enter mobile number" required>
                            </div>
                            <div class="invalid-feedback">Please enter a mobile number</div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-2"></i> Reset
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Register Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- RFID Scanning Instructions -->
        <div class="app-card mb-4">
            <div class="card-header bg-info">
                <h5 class="mb-0 text-white"><i class="fas fa-info-circle me-2"></i>RFID Scanning Instructions</h5>
            </div>
            <div class="card-body text-center">
                <div class="scan-animation mb-4">
                    <i class="fas fa-id-card scan-icon"></i>
                </div>
                
                <div class="alert alert-info">
                    <p class="mb-0"><i class="fas fa-info-circle me-2"></i> Hold the RFID card or key fob near the reader to capture its ID automatically.</p>
                </div>
                
                <ol class="text-start">
                    <li class="mb-2">Place the new RFID card near the reader device</li>
                    <li class="mb-2">Wait for the ID to appear in the RFID Card ID field</li>
                    <li class="mb-2">Fill in the employee information</li>
                    <li class="mb-2">Click "Register Employee" to complete the registration</li>
                    <li>The employee can now use this card to clock in and out</li>
                </ol>
            </div>
        </div>
        
        <!-- Recently Registered -->
        <div class="app-card">
            <div class="card-header bg-success">
                <h5 class="mb-0 text-white"><i class="fas fa-check-circle me-2"></i>Recently Registered Employees</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php
                    $pdo = Database::connect();
                    $sql = "SELECT * FROM employees ORDER BY created_at DESC LIMIT 5";
                    try {
                        $stmt = $pdo->query($sql);
                        $recentEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        // If the created_at column doesn't exist yet, use a simpler query
                        $sql = "SELECT * FROM table_the_iot_projects ORDER BY id DESC LIMIT 5";
                        $stmt = $pdo->query($sql);
                        $recentEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                    Database::disconnect();
                    
                    if (count($recentEmployees) > 0):
                        foreach ($recentEmployees as $employee):
                            // Handle old table structure if needed
                            if (!isset($employee['name']) && isset($employee['id'])) {
                                $employee['name'] = $employee['name'] ?? '(Unknown)';
                                $employee['department'] = null;
                                $employee['position'] = null;
                                $employee['created_at'] = date('Y-m-d H:i:s');
                                $employee['employee_id'] = $employee['id'];
                            }
                    ?>
                    <div class="list-group-item">
                        <div class="d-flex align-items-center">
                            <?php if (!empty($employee['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($employee['profile_image']); ?>" class="employee-avatar me-3">
                            <?php else: ?>
                                <div class="employee-initial-small me-3"><?php echo substr($employee['name'], 0, 1); ?></div>
                            <?php endif; ?>
                            
                            <div class="flex-grow-1">
                                <h6 class="mb-0"><?php echo htmlspecialchars($employee['name']); ?></h6>
                                <div class="small text-muted">
                                    <?php if (isset($employee['department']) && $employee['department']): ?>
                                        <i class="fas fa-building me-1"></i>
                                        <?php echo htmlspecialchars($employee['department']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">No Department</span>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($employee['position']) && $employee['position']): ?>
                                        <span class="mx-1">|</span>
                                        <i class="fas fa-briefcase me-1"></i>
                                        <?php echo htmlspecialchars($employee['position']); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="small text-muted mt-1">
                                    <i class="fas fa-clock me-1"></i> 
                                    Registered: <?php echo isset($employee['created_at']) ? date('M j, Y', strtotime($employee['created_at'])) : 'Unknown'; ?>
                                </div>
                            </div>
                            
                            <a href="employee-details.php?id=<?php echo $employee['employee_id'] ?? $employee['id']; ?>" 
                               class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                    <?php
                        endforeach;
                    else:
                    ?>
                    <div class="list-group-item text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="mb-0">No employees registered yet</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="user-data.php" class="btn btn-primary">
                    <i class="fas fa-users me-2"></i> View All Employees
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation
        const form = document.querySelector('.needs-validation');
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
        
        // Initialize tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        
        // Load UID from UIDContainer and refresh every 500ms
        $("#getUID").load("UIDContainer.php");
        setInterval(function() {
            $("#getUID").load("UIDContainer.php");
        }, 500);
        
        // Clear UID button
        $("#clear-uid").click(function() {
            // Reset the UID container
            $.ajax({
                url: 'getUID.php',
                type: 'POST',
                data: {UIDresult: ''},
                success: function() {
                    $("#getUID").val("");
                }
            });
        });
        
        // Handle department dropdown
        $("#department").change(function() {
            if ($(this).val() === "new") {
                $("#new-department-container").slideDown();
                $("#new-department").prop("required", true);
                $("#new-department").focus();
            } else {
                $("#new-department-container").slideUp();
                $("#new-department").prop("required", false);
            }
        });
    });
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>