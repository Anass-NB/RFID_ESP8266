<?php
// Database schema update script for the Employee Time Tracking System
require 'database.php';

// Function to execute SQL from a file
function executeSQLFile($pdo, $filePath) {
    try {
        // Read the SQL file
        $sql = file_get_contents($filePath);
        
        // Remove comments and keep only SQL commands
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Split commands by semicolon
        $commands = explode(';', $sql);
        
        $successCount = 0;
        $errorCount = 0;
        $errorMessages = [];
        
        // Execute each command
        foreach ($commands as $command) {
            $command = trim($command);
            if (!empty($command)) {
                try {
                    $pdo->exec($command);
                    $successCount++;
                } catch (PDOException $e) {
                    $errorCount++;
                    $errorMessages[] = $e->getMessage();
                }
            }
        }
        
        return [
            'success' => true,
            'message' => "SQL execution completed: $successCount commands successful, $errorCount failed.",
            'errors' => $errorMessages
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Error processing SQL file: " . $e->getMessage()
        ];
    }
}

// Function to check if a table exists
function tableExists($pdo, $tableName) {
    try {
        $result = $pdo->query("SELECT 1 FROM $tableName LIMIT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Function to display results as HTML
function displayResults($results) {
    echo "<h2>Database Update Results</h2>";
    
    if ($results['success']) {
        echo "<div style='background-color: #dff0d8; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
        echo "<h3 style='color: #3c763d;'>Success</h3>";
        echo "<p>" . htmlspecialchars($results['message']) . "</p>";
        echo "</div>";
    } else {
        echo "<div style='background-color: #f2dede; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
        echo "<h3 style='color: #a94442;'>Error</h3>";
        echo "<p>" . htmlspecialchars($results['message']) . "</p>";
        echo "</div>";
    }
    
    if (!empty($results['errors'])) {
        echo "<h3>Error Details:</h3>";
        echo "<ul style='background-color: #fcf8e3; padding: 15px; border-radius: 5px;'>";
        foreach ($results['errors'] as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
    }
}

// Main execution
$results = ['success' => false, 'message' => 'No action taken', 'errors' => []];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_db'])) {
    try {
        // Connect to database
        $pdo = Database::connect();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Set to disable foreign key checks temporarily
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        
        // Execute the SQL file
        $results = executeSQLFile($pdo, 'employee_time_tracking.sql');
        
        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        
        // Additional checks after update
        $tableChecks = [
            'employees' => tableExists($pdo, 'employees'),
            'time_logs' => tableExists($pdo, 'time_logs'),
            'daily_records' => tableExists($pdo, 'daily_records'),
            'departments' => tableExists($pdo, 'departments'),
            'users' => tableExists($pdo, 'users'),
            'settings' => tableExists($pdo, 'settings')
        ];
        
        $allTablesExist = !in_array(false, $tableChecks);
        
        if ($allTablesExist) {
            $results['message'] .= " All required tables have been created successfully.";
        } else {
            $missingTables = array_keys(array_filter($tableChecks, function($exists) { return !$exists; }));
            $results['message'] .= " Warning: Some tables were not created: " . implode(', ', $missingTables);
        }
        
        Database::disconnect();
    } catch (Exception $e) {
        $results = [
            'success' => false,
            'message' => "Database update failed: " . $e->getMessage(),
            'errors' => [$e->getMessage()]
        ];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Time Tracking System - Database Update</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .container {
            background-color: #f9f9f9;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .warning {
            background-color: #fcf8e3;
            border-left: 4px solid #f39c12;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 5px 5px 0;
        }
        .button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .button:hover {
            background-color: #2980b9;
        }
        .button.danger {
            background-color: #e74c3c;
        }
        .button.danger:hover {
            background-color: #c0392b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #3498db;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Employee Time Tracking System - Database Update</h1>
        
        <div class="warning">
            <h3>⚠️ Warning</h3>
            <p>This script will modify your database structure to support the Employee Time Tracking System. Please make sure you have a backup of your database before proceeding.</p>
            <p>The following changes will be made:</p>
            <ul>
                <li>Create or modify the <strong>employees</strong> table</li>
                <li>Create the <strong>time_logs</strong> table</li>
                <li>Create the <strong>daily_records</strong> table</li>
                <li>Create the <strong>departments</strong> table</li>
                <li>Create the <strong>users</strong> table</li>
                <li>Create the <strong>settings</strong> table</li>
                <li>Migrate existing data from the old table structure</li>
            </ul>
        </div>
        
        <?php
        // Display results if any action was taken
        if ($results['message'] !== 'No action taken') {
            displayResults($results);
        }
        ?>
        
        <h2>Database Update Actions</h2>
        <form method="post">
            <p>Click the button below to update your database schema:</p>
            <button type="submit" name="update_db" class="button">Update Database Schema</button>
        </form>
        
        <h2>Database Structure</h2>
        <table>
            <thead>
                <tr>
                    <th>Table Name</th>
                    <th>Description</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>employees</td>
                    <td>Stores employee information and RFID association</td>
                    <td>
                        <?php
                        $pdo = Database::connect();
                        echo tableExists($pdo, 'employees') ? '✅ Created' : '❌ Not Created';
                        Database::disconnect();
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>time_logs</td>
                    <td>Records all entry and exit timestamps</td>
                    <td>
                        <?php
                        $pdo = Database::connect();
                        echo tableExists($pdo, 'time_logs') ? '✅ Created' : '❌ Not Created';
                        Database::disconnect();
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>daily_records</td>
                    <td>Summarizes daily attendance and work hours</td>
                    <td>
                        <?php
                        $pdo = Database::connect();
                        echo tableExists($pdo, 'daily_records') ? '✅ Created' : '❌ Not Created';
                        Database::disconnect();
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>departments</td>
                    <td>Manages department information</td>
                    <td>
                        <?php
                        $pdo = Database::connect();
                        echo tableExists($pdo, 'departments') ? '✅ Created' : '❌ Not Created';
                        Database::disconnect();
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>users</td>
                    <td>Stores user accounts for system access</td>
                    <td>
                        <?php
                        $pdo = Database::connect();
                        echo tableExists($pdo, 'users') ? '✅ Created' : '❌ Not Created';
                        Database::disconnect();
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>settings</td>
                    <td>Stores system configuration</td>
                    <td>
                        <?php
                        $pdo = Database::connect();
                        echo tableExists($pdo, 'settings') ? '✅ Created' : '❌ Not Created';
                        Database::disconnect();
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <div style="margin-top: 30px;">
            <a href="home.php" class="button">Return to Home</a>
        </div>
    </div>
</body>
</html>