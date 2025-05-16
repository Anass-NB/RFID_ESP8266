# Employee Time Tracking Database Schema Diagram

```
+------------------+       +------------------+       +------------------+
|    employees     |       |    time_logs     |       |  daily_records   |
+------------------+       +------------------+       +------------------+
| PK employee_id   |<----->| PK log_id        |       | PK record_id     |
|    rfid_uid      |<----->|    employee_id   |<----->|    employee_id   |
|    name          |       |    rfid_uid      |       |    work_date     |
|    gender        |       |    log_type      |       |    first_entry   |
|    email         |       |    timestamp     |       |    last_exit     |
|    mobile        |       |    notes         |       |    work_hours    |
|    department    |       +------------------+       |    status        |
|    position      |                                  |    notes         |
|    hire_date     |                                  +------------------+
|    current_status|
|    emp_status    |       +------------------+       +------------------+
|    profile_image |       |   departments    |       |     users        |
|    created_at    |       +------------------+       +------------------+
|    updated_at    |<----->| PK department_id |       | PK user_id       |
+------------------+       |    name          |       |    username      |
        ^                  |    description   |       |    password      |
        |                  |    manager_id    |------>|    email         |
        |                  |    created_at    |       |    role          |
        |                  +------------------+       |    employee_id   |
        |                                             |    last_login    |
        +-------------------------------------------->|    created_at    |
                                                      |    updated_at    |
                                                      +------------------+
                           +------------------+
                           |    settings      |
                           +------------------+
                           | PK setting_id    |
                           |    setting_key   |
                           |    setting_value |
                           |    description   |
                           |    updated_at    |
                           +------------------+
```

## Table Relationships

1. **employees ↔ time_logs**
   - One-to-many relationship
   - An employee can have multiple time log entries
   - Each time log entry belongs to exactly one employee

2. **employees ↔ daily_records**
   - One-to-many relationship
   - An employee can have multiple daily records (one per day)
   - Each daily record belongs to exactly one employee

3. **employees ↔ departments**
   - Many-to-one relationship
   - Many employees can belong to one department
   - The manager_id field in departments references an employee

4. **employees ↔ users**
   - One-to-one relationship
   - An employee can have an associated user account for system access
   - Each user account is associated with at most one employee

## Primary Keys and Foreign Keys

### Primary Keys
- `employee_id` in employees table
- `log_id` in time_logs table
- `record_id` in daily_records table
- `department_id` in departments table
- `user_id` in users table
- `setting_id` in settings table

### Foreign Keys
- `employee_id` in time_logs references employees(employee_id)
- `rfid_uid` in time_logs references employees(rfid_uid)
- `employee_id` in daily_records references employees(employee_id)
- `manager_id` in departments references employees(employee_id)
- `employee_id` in users references employees(employee_id)

## Special Fields

- `current_status` in employees tracks if an employee is currently in or out
- `log_type` in time_logs differentiates between entry and exit events
- `work_hours` in daily_records stores the calculated total work hours for a day
- `role` in users determines access levels within the system
- `settings` table stores system-wide configuration values

## Indexes and Constraints

- Unique index on `rfid_uid` in employees table
- Unique index on `employee_id` and `work_date` combination in daily_records
- Unique indexes on `username` and `email` in users table
- Unique index on `setting_key` in settings table
- Foreign key constraints to maintain referential integrity across tables