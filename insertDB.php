<?php
/**
 * Insert Employee Data
 * 
 * This file processes the employee registration form and inserts data into the database.
 * It handles both old and new database structures.
 */
     
require 'database.php';

if (!empty($_POST)) {
    // Keep track of post values
    $name = $_POST['name'];
    $id = $_POST['id'];
    $gender = $_POST['gender'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    
    // Get additional fields (from updated form)
    $department = isset($_POST['department']) ? $_POST['department'] : null;
    $position = isset($_POST['position']) ? $_POST['position'] : null;
    $hireDate = isset($_POST['hire_date']) ? $_POST['hire_date'] : null;
    
    // Check if user wants to add a new department
    if ($department === 'new' && isset($_POST['new_department']) && !empty($_POST['new_department'])) {
        $department = $_POST['new_department'];
    }
    
    // Connect to database
    $pdo = Database::connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if employees table exists (new schema)
    $useNewSchema = false;
    try {
        $result = $pdo->query("SELECT 1 FROM employees LIMIT 1");
        $useNewSchema = true;
    } catch (Exception $e) {
        // Table doesn't exist, use old schema
        $useNewSchema = false;
    }
    
    if ($useNewSchema) {
        // Use new schema with employees table
        try {
            $sql = "INSERT INTO employees (rfid_uid, name, gender, email, mobile, department, position, hire_date, current_status, employment_status) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'out', 'active')";
            $q = $pdo->prepare($sql);
            $q->execute(array($id, $name, $gender, $email, $mobile, $department, $position, $hireDate));
            
            // Also insert into old table for backward compatibility if it exists
            try {
                $pdo->query("SELECT 1 FROM table_the_iot_projects LIMIT 1");
                $sql = "INSERT INTO table_the_iot_projects (name, id, gender, email, mobile) values(?, ?, ?, ?, ?)";
                $q = $pdo->prepare($sql);
                $q->execute(array($name, $id, $gender, $email, $mobile));
            } catch (Exception $e) {
                // Old table doesn't exist, ignore
            }
        } catch (PDOException $e) {
            // Handle duplicate RFID card
            if ($e->getCode() == 23000) { // MySQL duplicate entry error
                // Redirect with error
                header("Location: registration.php?error=1&message=".urlencode("RFID card already registered!"));
                Database::disconnect();
                exit;
            } else {
                throw $e; // Re-throw the exception if it's not a duplicate entry
            }
        }
    } else {
        // Use old schema with table_the_iot_projects
        $sql = "INSERT INTO table_the_iot_projects (name, id, gender, email, mobile) values(?, ?, ?, ?, ?)";
        $q = $pdo->prepare($sql);
        $q->execute(array($name, $id, $gender, $email, $mobile));
    }
    
    Database::disconnect();
    
    // Redirect to the user data page with success message
    header("Location: registration.php?success=1");
}
?>