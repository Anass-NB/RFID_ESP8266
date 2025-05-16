# Database Schema Changes

## Current Database Structure
```sql
CREATE TABLE `table_the_iot_projects` (
  `name` varchar(100) NOT NULL,
  `id` varchar(100) NOT NULL,
  `gender` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
```

## Proposed Changes

### 1. Rename and Modify Current Table
Rename the current table to `employees` and add additional fields for employee information:

```sql
CREATE TABLE `employees` (
  `employee_id` INT AUTO_INCREMENT PRIMARY KEY,
  `rfid_uid` varchar(100) NOT NULL UNIQUE,
  `name` varchar(100) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `department` varchar(50) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `hire_date` DATE DEFAULT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2. Create Time Logs Table
Create a new table to store entry and exit records:

```sql
CREATE TABLE `time_logs` (
  `log_id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `rfid_uid` varchar(100) NOT NULL,
  `log_type` ENUM('entry', 'exit') NOT NULL,
  `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT DEFAULT NULL,
  FOREIGN KEY (employee_id) REFERENCES employees(employee_id),
  FOREIGN KEY (rfid_uid) REFERENCES employees(rfid_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3. Create Daily Summary Table
Create a table to store daily work hour summaries:

```sql
CREATE TABLE `daily_records` (
  `record_id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `work_date` DATE NOT NULL,
  `first_entry` DATETIME DEFAULT NULL,
  `last_exit` DATETIME DEFAULT NULL,
  `work_hours` DECIMAL(5,2) DEFAULT 0,
  `status` ENUM('present', 'absent', 'late', 'half-day') DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  FOREIGN KEY (employee_id) REFERENCES employees(employee_id),
  UNIQUE KEY `employee_date` (`employee_id`, `work_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4. Create Department Table
Create a table to manage departments:

```sql
CREATE TABLE `departments` (
  `department_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(50) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `manager_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (manager_id) REFERENCES employees(employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 5. Create User Accounts Table (for admin access)
```sql
CREATE TABLE `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `role` ENUM('admin', 'manager', 'viewer') DEFAULT 'viewer',
  `employee_id` INT DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES employees(employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Data Migration Plan
1. Create the new tables
2. Copy existing data from `table_the_iot_projects` to the new `employees` table
3. Set up foreign key relationships
4. Verify data integrity after migration