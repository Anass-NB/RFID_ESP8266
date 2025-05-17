<?php
/**
 * Department Management Page
 * Allows administrators to manage departments
 */

// Include header with proper page title
$pageTitle = "Manage Departments";
require_once 'includes/header.php';

// Initialize success/error messages
$successMessage = null;
$errorMessage = null;

// Process form submission for adding/editing department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $pdo = Database::connect();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if ($_POST['action'] === 'add' && isset($_POST['department_name'])) {
            // Add new department
            $departmentName = trim($_POST['department_name']);
            $departmentDesc = trim($_POST['department_description'] ?? '');
            
            if (empty($departmentName)) {
                throw new Exception("Department name cannot be empty.");
            }
            
            // Check if department already exists
            $sql = "SELECT COUNT(*) FROM departments WHERE name = ?";
            $q = $pdo->prepare($sql);
            $q->execute(array($departmentName));
            if ($q->fetchColumn() > 0) {
                throw new Exception("Department with this name already exists.");
            }
            
            // Insert new department
            $sql = "INSERT INTO departments (name, description) VALUES (?, ?)";
            $q = $pdo->prepare($sql);
            $q->execute(array($departmentName, $departmentDesc));
            
            $successMessage = "Department '$departmentName' has been added successfully.";
        } 
        elseif ($_POST['action'] === 'edit' && isset($_POST['department_id'])) {
            // Edit existing department
            $departmentId = $_POST['department_id'];
            $departmentName = trim($_POST['department_name']);
            $departmentDesc = trim($_POST['department_description'] ?? '');
            
            if (empty($departmentName)) {
                throw new Exception("Department name cannot be empty.");
            }
            
            // Check if department with this name already exists (excluding current department)
            $sql = "SELECT COUNT(*) FROM departments WHERE name = ? AND id != ?";
            $q = $pdo->prepare($sql);
            $q->execute(array($departmentName, $departmentId));
            if ($q->fetchColumn() > 0) {
                throw new Exception("Another department with this name already exists.");
            }
            
            // Update department
            $sql = "UPDATE departments SET name = ?, description = ? WHERE id = ?";
            $q = $pdo->prepare($sql);
            $q->execute(array($departmentName, $departmentDesc, $departmentId));
            
            $successMessage = "Department '$departmentName' has been updated successfully.";
        }
        elseif ($_POST['action'] === 'delete' && isset($_POST['department_id'])) {
            // Delete department
            $departmentId = $_POST['department_id'];
            
            // Get department name for success message
            $sql = "SELECT name FROM departments WHERE id = ?";
            $q = $pdo->prepare($sql);
            $q->execute(array($departmentId));
            $departmentName = $q->fetchColumn();
            
            // Check if employees are assigned to this department
            $sql = "SELECT COUNT(*) FROM employees WHERE department_id = ?";
            $q = $pdo->prepare($sql);
            $q->execute(array($departmentId));
            if ($q->fetchColumn() > 0) {
                throw new Exception("Cannot delete department as it has employees assigned to it.");
            }
            
            // Delete department
            $sql = "DELETE FROM departments WHERE id = ?";
            $q = $pdo->prepare($sql);
            $q->execute(array($departmentId));
            
            $successMessage = "Department '$departmentName' has been deleted successfully.";
        }
        
        Database::disconnect();
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

// Get all departments
$departments = array();
try {
    $pdo = Database::connect();
    
    // Check if departments table exists, if not, create it
    $sql = "SHOW TABLES LIKE 'departments'";
    $result = $pdo->query($sql);
    if ($result->rowCount() === 0) {
        // Create departments table
        $sql = "CREATE TABLE departments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
        $pdo->exec($sql);
        
        // Add foreign key to employees table if it exists
        $sql = "SHOW TABLES LIKE 'employees'";
        $result = $pdo->query($sql);
        if ($result->rowCount() > 0) {
            // Check if department_id column exists
            $sql = "SHOW COLUMNS FROM employees LIKE 'department_id'";
            $result = $pdo->query($sql);
            if ($result->rowCount() === 0) {
                // Add department_id column
                $sql = "ALTER TABLE employees ADD COLUMN department_id INT, ADD FOREIGN KEY (department_id) REFERENCES departments(id)";
                $pdo->exec($sql);
            }
        }
    }
    
    // Get departments with employee count
    $sql = "SELECT d.*, COUNT(e.employee_id) AS employee_count 
            FROM departments d 
            LEFT JOIN employees e ON d.name = e.department 
            GROUP BY d.id 
            ORDER BY d.name";
    $departments = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    // If departments table exists but no records, try to populate from unique department values in employees table
    if (count($departments) === 0) {
        $sql = "SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department";
        $uniqueDepartments = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($uniqueDepartments) > 0) {
            foreach ($uniqueDepartments as $deptName) {
                $sql = "INSERT INTO departments (name) VALUES (?)";
                $q = $pdo->prepare($sql);
                $q->execute(array($deptName));
            }
            
            // Get departments again
            $sql = "SELECT d.*, COUNT(e.employee_id) AS employee_count 
                    FROM departments d 
                    LEFT JOIN employees e ON d.name = e.department 
                    GROUP BY d.id 
                    ORDER BY d.name";
            $departments = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    Database::disconnect();
} catch (PDOException $e) {
    $errorMessage = "Error retrieving departments: " . $e->getMessage();
    
    // If error is due to table not existing, create a mock department list from the employees table
    try {
        $pdo = Database::connect();
        $sql = "SELECT department, COUNT(*) as employee_count 
                FROM employees 
                WHERE department IS NOT NULL AND department != '' 
                GROUP BY department 
                ORDER BY department";
        $departments = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        
        // Format departments to match expected structure
        foreach ($departments as &$dept) {
            $dept['id'] = null; // No actual ID since departments table doesn't exist
            $dept['name'] = $dept['department'];
            $dept['description'] = '';
            unset($dept['department']);
        }
        
        $errorMessage = "Departments table does not exist. Using department data from employees table.";
        Database::disconnect();
    } catch (PDOException $e2) {
        $errorMessage .= " Could not retrieve departments from employees table: " . $e2->getMessage();
    }
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-0"><i class="fas fa-building me-2"></i> Department Management</h3>
        <p class="text-muted mb-0">Create, edit, and manage company departments</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
        <i class="fas fa-plus-circle me-2"></i> Add New Department
    </button>
</div>

<!-- Notifications -->
<?php if ($successMessage): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> <?php echo $successMessage; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if ($errorMessage): ?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $errorMessage; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- Departments List -->
<div class="app-card">
    <div class="card-header bg-primary d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-white"><i class="fas fa-list me-2"></i>Departments</h5>
        <span class="badge bg-light text-dark"><?php echo count($departments); ?> departments</span>
    </div>
    <div class="card-body p-0">
        <?php if (count($departments) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover app-table mb-0">
                    <thead>
                        <tr>
                            <th>Department Name</th>
                            <th>Description</th>
                            <th class="text-center">Employees</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $department): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-primary me-2">
                                            <i class="fas fa-building"></i>
                                        </span>
                                        <span class="fw-medium"><?php echo htmlspecialchars($department['name']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($department['description'])): ?>
                                        <?php echo htmlspecialchars($department['description']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">No description</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="user-data.php?department=<?php echo urlencode($department['name']); ?>" class="badge bg-info text-decoration-none">
                                        <i class="fas fa-users me-1"></i> <?php echo $department['employee_count']; ?>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-department-btn" 
                                                data-id="<?php echo $department['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($department['name']); ?>"
                                                data-description="<?php echo htmlspecialchars($department['description'] ?? ''); ?>"
                                                data-bs-toggle="tooltip" title="Edit Department">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-department-btn"
                                                data-id="<?php echo $department['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($department['name']); ?>"
                                                data-count="<?php echo $department['employee_count']; ?>"
                                                data-bs-toggle="tooltip" title="Delete Department">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-building fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No Departments Found</h5>
                <p class="mb-4">You haven't created any departments yet.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                    <i class="fas fa-plus-circle me-2"></i> Add Your First Department
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Department Modal -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addDepartmentModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Add New Department
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="" class="app-form needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="department_name" class="form-label">Department Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                            <input type="text" class="form-control" id="department_name" name="department_name" required>
                        </div>
                        <div class="invalid-feedback">Please enter a department name</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="department_description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="department_description" name="department_description" rows="3" placeholder="Enter department description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Add Department
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Department Modal -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-labelledby="editDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editDepartmentModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Department
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="" class="app-form needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="department_id" id="edit_department_id">
                    
                    <div class="mb-3">
                        <label for="edit_department_name" class="form-label">Department Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                            <input type="text" class="form-control" id="edit_department_name" name="department_name" required>
                        </div>
                        <div class="invalid-feedback">Please enter a department name</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_department_description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="edit_department_description" name="department_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Department
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Department Modal -->
<div class="modal fade" id="deleteDepartmentModal" tabindex="-1" aria-labelledby="deleteDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteDepartmentModalLabel">
                    <i class="fas fa-trash me-2"></i>Delete Department
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="department_id" id="delete_department_id">
                    
                    <div id="delete_confirmation_message">
                        Are you sure you want to delete the department "<span id="delete_department_name" class="fw-bold"></span>"?
                    </div>
                    
                    <div id="delete_warning" class="alert alert-warning mt-3" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This department has <span id="employee_count" class="fw-bold"></span> employees assigned to it.
                        Please reassign these employees before deleting this department.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger" id="confirm_delete_btn">
                        <i class="fas fa-trash me-2"></i>Delete Department
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        
        // Form validation
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
        
        // Edit department button
        const editButtons = document.querySelectorAll('.edit-department-btn');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const departmentId = this.getAttribute('data-id');
                const departmentName = this.getAttribute('data-name');
                const departmentDescription = this.getAttribute('data-description');
                
                document.getElementById('edit_department_id').value = departmentId;
                document.getElementById('edit_department_name').value = departmentName;
                document.getElementById('edit_department_description').value = departmentDescription;
                
                const editModal = new bootstrap.Modal(document.getElementById('editDepartmentModal'));
                editModal.show();
            });
        });
        
        // Delete department button
        const deleteButtons = document.querySelectorAll('.delete-department-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const departmentId = this.getAttribute('data-id');
                const departmentName = this.getAttribute('data-name');
                const employeeCount = parseInt(this.getAttribute('data-count'), 10);
                
                document.getElementById('delete_department_id').value = departmentId;
                document.getElementById('delete_department_name').textContent = departmentName;
                
                // Show warning if department has employees
                const deleteWarning = document.getElementById('delete_warning');
                const confirmDeleteBtn = document.getElementById('confirm_delete_btn');
                
                if (employeeCount > 0) {
                    document.getElementById('employee_count').textContent = employeeCount;
                    deleteWarning.style.display = 'block';
                    confirmDeleteBtn.disabled = true;
                } else {
                    deleteWarning.style.display = 'none';
                    confirmDeleteBtn.disabled = false;
                }
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteDepartmentModal'));
                deleteModal.show();
            });
        });
    });
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>