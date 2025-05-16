# Database Migration Plan for Employee Time Tracking System

## Migration Overview

This document outlines the process for migrating from the existing RFID user tracking database to the new employee time tracking system database structure.

## Migration Steps

### 1. Backup Existing Data
Before making any changes, create a backup of the existing database:
```sql
CREATE TABLE backup_table_the_iot_projects AS SELECT * FROM table_the_iot_projects;
```

### 2. Create New Tables
The migration script will:
1. Create the new tables with proper relationships:
   - `employees` (enhanced version of the current table)
   - `time_logs` (for entry/exit tracking)
   - `daily_records` (for work hours summary)
   - `departments` (for organizational structure)
   - `users` (for system access)
   - `settings` (for system configuration)

### 3. Migrate Existing Data
Transfer data from the old table structure to the new one:
```sql
INSERT INTO employees (rfid_uid, name, gender, email, mobile, current_status)
SELECT id, name, gender, email, mobile, 'out'
FROM table_the_iot_projects
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    gender = VALUES(gender),
    email = VALUES(email),
    mobile = VALUES(mobile);
```

### 4. Verify Data Integrity
After migration, verify that:
- All records were transferred correctly
- Foreign key relationships are properly established
- No data was lost or corrupted

### 5. Initialize Default Values
Set up default configuration values in the `settings` table:
- Default working hours
- Late arrival threshold
- Company information
- Weekend days configuration

### 6. Create Admin Account
Create a default administrator account for system management:
```sql
INSERT INTO users (username, password, email, role)
VALUES ('admin', '[hashed_password]', 'admin@example.com', 'admin');
```

### 7. Testing Phase
Test all functionality with the new database structure:
- RFID scanning and time logging
- Employee management features
- Reporting and analytics
- User authentication and authorization

### 8. Rollback Plan
In case of issues, a rollback procedure is available:
```sql
-- Drop all new tables
DROP TABLE IF EXISTS time_logs, daily_records, departments, users, settings, employees;

-- Restore original table if needed
-- CREATE TABLE table_the_iot_projects AS SELECT * FROM backup_table_the_iot_projects;
```

## Database Schema Changes

The migration will transform the simple user tracking database into a comprehensive employee time tracking system with:

1. **Extended Employee Information**
   - Professional details (department, position)
   - Employment status tracking
   - Current presence status (in/out)

2. **Time Tracking Capabilities**
   - Entry and exit timestamps
   - Work hour calculation
   - Daily attendance records

3. **Organizational Structure**
   - Departments management
   - Reporting relationships

4. **System Administration**
   - User accounts with role-based access
   - System settings and configuration

## Implementation

The migration will be implemented through the `update_database.php` script, which:
1. Takes a backup of existing data
2. Executes the SQL schema changes
3. Migrates the data to the new structure
4. Verifies the integrity of the migration
5. Reports on the success or failure of each step

## Accessing the Migration Tool

To perform the migration:
1. Navigate to `update_database.php` in your web browser
2. Review the proposed changes
3. Click the "Update Database Schema" button to start the migration
4. Monitor the results for any errors or warnings