<?php
/**
 * user-data.php
 * Lists all employees and provides management functions
 */

// Include header with proper page title
$pageTitle = "Employee Data";
require_once 'includes/header.php';

// Get department filter if set
$departmentFilter = isset($_GET['department']) ? $_GET['department'] : '';

// Get departments for filter dropdown
$departments = [];
$pdo = Database::connect();
$sql = "SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department";
foreach ($pdo->query($sql) as $row) {
    $departments[] = $row['department'];
}

// Get search term if provided
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Construct SQL query with filters
$sql = 'SELECT * FROM employees WHERE employment_status = "active"';

if (!empty($departmentFilter)) {
    $sql .= ' AND department = "' . $departmentFilter . '"';
}

if (!empty($searchTerm)) {
    $sql .= ' AND (name LIKE "%' . $searchTerm . '%" OR 
                  department LIKE "%' . $searchTerm . '%" OR 
                  position LIKE "%' . $searchTerm . '%" OR 
                  email LIKE "%' . $searchTerm . '%")';
}

$sql .= ' ORDER BY name ASC';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-0"><i class="fas fa-users me-2"></i> Employee Management</h3>
        <p class="text-muted mb-0">View, edit and manage all employee records</p>
    </div>
    <a href="registration.php" class="btn btn-primary">
        <i class="fas fa-user-plus me-2"></i> Add New Employee
    </a>
</div>

<!-- Filters and Search -->
<div class="app-card mb-4">
    <div class="card-header bg-primary">
        <h5 class="mb-0 text-white"><i class="fas fa-filter me-2"></i> Search & Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="user-data.php" class="row g-3">
            <div class="col-md-5">
                <label for="search" class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Search by name, email, position..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
            </div>
            
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
            
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-filter me-2"></i> Apply Filters
                </button>
                <a href="user-data.php" class="btn btn-outline-secondary">
                    <i class="fas fa-redo me-2"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Employee Data Table -->
<div class="app-card">
    <div class="card-header bg-primary">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white"><i class="fas fa-list me-2"></i> Employee List</h5>
            <div>
                <button type="button" id="exportCSV" class="btn btn-sm btn-light me-2" data-bs-toggle="tooltip" title="Export to CSV">
                    <i class="fas fa-file-csv me-1"></i> Export
                </button>
                <button type="button" id="printList" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Print List">
                    <i class="fas fa-print me-1"></i> Print
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover app-table" id="employeeTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Status</th>
                        <th>Contact</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                try {
                    foreach ($pdo->query($sql) as $row) {
                        echo '<tr>';
                        echo '<td class="align-middle">';
                        
                        // Profile image or initial
                        if (!empty($row['profile_image'])) {
                            echo '<img src="' . htmlspecialchars($row['profile_image']) . '" class="employee-avatar me-2" alt="Profile">';
                        } else {
                            echo '<div class="employee-initial-small me-2">' . substr($row['name'], 0, 1) . '</div>';
                        }
                        
                        echo '<span class="fw-bold">' . htmlspecialchars($row['name']) . '</span>';
                        echo '</td>';
                        
                        echo '<td class="align-middle">';
                        if (!empty($row['department'])) {
                            echo '<i class="fas fa-building me-1 text-muted"></i> ' . htmlspecialchars($row['department']);
                        } else {
                            echo '<span class="text-muted">Not Assigned</span>';
                        }
                        echo '</td>';
                        
                        echo '<td class="align-middle">';
                        if (!empty($row['position'])) {
                            echo '<i class="fas fa-briefcase me-1 text-muted"></i> ' . htmlspecialchars($row['position']);
                        } else {
                            echo '<span class="text-muted">Not Assigned</span>';
                        }
                        echo '</td>';
                        
                        echo '<td class="align-middle">';
                        echo '<span class="badge ' . ($row['current_status'] == 'in' ? 'bg-success' : 'bg-danger') . ' p-2">';
                        echo '<i class="fas ' . ($row['current_status'] == 'in' ? 'fa-check-circle' : 'fa-times-circle') . ' me-1"></i>';
                        echo ($row['current_status'] == 'in') ? 'PRESENT' : 'ABSENT';
                        echo '</span></td>';
                        
                        echo '<td class="align-middle">';
                        echo '<div><i class="fas fa-envelope text-muted me-1"></i> ' . htmlspecialchars($row['email']) . '</div>';
                        echo '<div><i class="fas fa-phone text-muted me-1"></i> ' . htmlspecialchars($row['mobile']) . '</div>';
                        echo '</td>';
                        
                        echo '<td class="text-center align-middle">';
                        echo '<div class="btn-group" role="group">';
                        echo '<a href="employee-details.php?id=' . $row['employee_id'] . '" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="View Details"><i class="fas fa-eye"></i></a>';
                        echo '<a href="employee-dashboard.php?id=' . $row['employee_id'] . '" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Dashboard"><i class="fas fa-tachometer-alt"></i></a>';
                        echo '<a href="user-data-edit-page.php?id=' . $row['rfid_uid'] . '" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Edit"><i class="fas fa-edit"></i></a>';
                        echo '<a href="user-data-delete-page.php?id=' . $row['rfid_uid'] . '" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Delete"><i class="fas fa-trash"></i></a>';
                        echo '</div>';
                        echo '</td>';
                        
                        echo '</tr>';
                    }
                } catch (PDOException $e) {
                    echo '<tr><td colspan="6" class="text-danger">Error: ' . $e->getMessage() . '</td></tr>';
                }
                
                Database::disconnect();
                ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle me-1"></i> 
                    Showing all employees<?php if (!empty($departmentFilter)) echo ' in ' . htmlspecialchars($departmentFilter) . ' department'; ?>
                    <?php if (!empty($searchTerm)) echo ' matching "' . htmlspecialchars($searchTerm) . '"'; ?>
                </p>
            </div>
            <nav aria-label="Employee list pagination">
                <!-- Pagination placeholder for future implementation -->
                <ul class="pagination mb-0">
                    <li class="page-item disabled">
                        <span class="page-link">Page 1</span>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        
        // Export to CSV functionality
        document.getElementById('exportCSV').addEventListener('click', function() {
            // Get table data
            const table = document.getElementById('employeeTable');
            let csv = [];
            
            // Add header row
            const headers = [];
            const headerCells = table.querySelectorAll('thead th');
            headerCells.forEach(cell => {
                if (cell.textContent != 'Actions') { // Skip actions column
                    headers.push(cell.textContent.trim());
                }
            });
            csv.push(headers.join(','));
            
            // Add data rows
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const rowData = [];
                const cells = row.querySelectorAll('td');
                
                // Extract name (skip the image)
                const nameCell = cells[0];
                rowData.push('"' + nameCell.textContent.trim() + '"');
                
                // Department
                rowData.push('"' + cells[1].textContent.trim() + '"');
                
                // Position
                rowData.push('"' + cells[2].textContent.trim() + '"');
                
                // Status (clean up badge text)
                const statusCell = cells[3];
                rowData.push('"' + statusCell.textContent.trim() + '"');
                
                // Contact (combine email and phone)
                const contactCell = cells[4];
                rowData.push('"' + contactCell.textContent.trim().replace(/\n/g, ' | ') + '"');
                
                // Skip actions column
                
                csv.push(rowData.join(','));
            });
            
            // Create and download the CSV file
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', 'employees.csv');
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
        
        // Print functionality
        document.getElementById('printList').addEventListener('click', function() {
            window.print();
        });
    });
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>