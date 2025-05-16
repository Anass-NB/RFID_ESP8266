-- Database Schema Modifications for Employee Time Tracking System

-- Drop foreign key constraints first (if migrating from existing database)
-- SET FOREIGN_KEY_CHECKS=0;

-- Create or modify employees table from the existing table
CREATE TABLE IF NOT EXISTS `employees` (
  `employee_id` INT AUTO_INCREMENT PRIMARY KEY,
  `rfid_uid` varchar(100) NOT NULL UNIQUE,
  `name` varchar(100) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `department` varchar(50) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `hire_date` DATE DEFAULT NULL,
  `current_status` ENUM('in', 'out') DEFAULT 'out',
  `employment_status` ENUM('active', 'inactive') DEFAULT 'active',
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create time_logs table to store entry and exit records
CREATE TABLE IF NOT EXISTS `time_logs` (
  `log_id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `rfid_uid` varchar(100) NOT NULL,
  `log_type` ENUM('entry', 'exit') NOT NULL,
  `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT DEFAULT NULL,
  FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
  FOREIGN KEY (rfid_uid) REFERENCES employees(rfid_uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create daily_records table to store daily work hour summaries
CREATE TABLE IF NOT EXISTS `daily_records` (
  `record_id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `work_date` DATE NOT NULL,
  `first_entry` DATETIME DEFAULT NULL,
  `last_exit` DATETIME DEFAULT NULL,
  `work_hours` DECIMAL(5,2) DEFAULT 0,
  `status` ENUM('present', 'absent', 'late', 'half-day') DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
  UNIQUE KEY `employee_date` (`employee_id`, `work_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create departments table to manage departments
CREATE TABLE IF NOT EXISTS `departments` (
  `department_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(50) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `manager_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (manager_id) REFERENCES employees(employee_id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create users table for admin access
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `role` ENUM('admin', 'manager', 'viewer') DEFAULT 'viewer',
  `employee_id` INT DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create settings table for system configuration
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` varchar(50) NOT NULL UNIQUE,
  `setting_value` TEXT NOT NULL,
  `description` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
('workday_start', '09:00:00', 'Default work day start time'),
('workday_end', '17:00:00', 'Default work day end time'),
('late_threshold', '00:15:00', 'Minutes after workday start to mark as late'),
('company_name', 'Your Company', 'Company name for reports and display'),
('weekend_days', '0,6', 'Weekend days (0=Sunday, 6=Saturday)');

-- Create migration script to move data from old table to new structure
-- This will be run only if the old table exists and migration is needed
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS migrate_data()
BEGIN
    -- Check if old table exists
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'table_the_iot_projects') THEN
        -- Insert data from old table to employees table
        INSERT INTO employees (rfid_uid, name, gender, email, mobile, current_status)
        SELECT id, name, gender, email, mobile, 'out'
        FROM table_the_iot_projects
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            gender = VALUES(gender),
            email = VALUES(email),
            mobile = VALUES(mobile);
            
        -- Optionally drop the old table after successful migration
        -- DROP TABLE table_the_iot_projects;
    END IF;
END //
DELIMITER ;

-- Run the migration procedure
CALL migrate_data();

-- Drop the procedure after use
DROP PROCEDURE IF EXISTS migrate_data;

-- Create a default admin user for system access
INSERT INTO `employees` (`rfid_uid`, `name`, `gender`, `email`, `mobile`, `department`, `position`)
VALUES ('ADMIN_RFID', 'System Administrator', 'Other', 'admin@example.com', '0000000000', 'Administration', 'Administrator')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    email = VALUES(email);

-- Get the employee_id of the admin
SET @admin_id = (SELECT employee_id FROM employees WHERE rfid_uid = 'ADMIN_RFID');

-- Create admin user credentials
INSERT INTO `users` (`username`, `password`, `email`, `role`, `employee_id`)
VALUES ('admin', '$2y$10$GtQSp.P8OU50YTXMOZsYJe00CGFQsyPwqmrXjBZ.CU5vABAKRJGOi', 'admin@example.com', 'admin', @admin_id)
ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    role = VALUES(role),
    employee_id = VALUES(employee_id);

-- Reset foreign key checks
-- SET FOREIGN_KEY_CHECKS=1;