/**
 * Time Tracking JavaScript Functions
 * 
 * This file contains client-side functionality for the Employee Time Tracking System
 */

// Update the current time display
function updateClock() {
    const now = new Date();
    const timeElement = document.getElementById('current-time');
    const dateElement = document.getElementById('current-date');
    
    if (timeElement && dateElement) {
        timeElement.textContent = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'});
        dateElement.textContent = now.toLocaleDateString([], {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'});
    }
    
    setTimeout(updateClock, 1000);
}

// Format a date for display
function formatDate(dateString, includeTime = false) {
    const date = new Date(dateString);
    
    if (includeTime) {
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }
    
    return date.toLocaleDateString();
}

// Format time from a date string
function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

// Calculate time difference between two dates in hours and minutes
function calculateTimeDifference(startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    const diff = Math.abs(end - start) / 1000; // difference in seconds
    
    const hours = Math.floor(diff / 3600);
    const minutes = Math.floor((diff % 3600) / 60);
    
    return `${hours}h ${minutes}m`;
}

// Format work hours as hours and minutes
function formatWorkHours(hours) {
    const wholeHours = Math.floor(hours);
    const minutes = Math.round((hours - wholeHours) * 60);
    
    return `${wholeHours}h ${minutes}m`;
}

// Load current status of employees
function loadCurrentStatus(containerId = 'status-container') {
    const container = document.getElementById(containerId);
    
    if (!container) return;
    
    // Show loading indicator
    container.innerHTML = '<p class="text-center">Loading current status...</p>';
    
    // Fetch data from API
    fetch('api.php?endpoint=present_employees')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data) {
                let html = `<h6 class="mb-3">${data.message}</h6>`;
                
                if (data.data.length > 0) {
                    html += '<div class="table-responsive">';
                    html += '<table class="table table-sm table-striped">';
                    html += '<thead><tr><th>Employee</th><th>Department</th><th>Clocked In At</th></tr></thead>';
                    html += '<tbody>';
                    
                    data.data.forEach(employee => {
                        const entryTime = new Date(employee.entry_time);
                        html += '<tr>';
                        html += `<td>${employee.name}</td>`;
                        html += `<td>${employee.department || 'N/A'}</td>`;
                        html += `<td>${entryTime.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</td>`;
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table></div>';
                } else {
                    html += '<p>No employees are currently clocked in.</p>';
                }
                
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p class="text-danger">Error loading current status.</p>';
            }
        })
        .catch(error => {
            console.error('Error fetching data:', error);
            container.innerHTML = '<p class="text-danger">Error loading current status.</p>';
        });
}

// Process RFID scan
function processScan(uid, resultContainerId = 'scan-result') {
    const resultContainer = document.getElementById(resultContainerId);
    
    if (!resultContainer) return;
    
    // Show processing indicator
    resultContainer.innerHTML = '<div class="scanning-indicator">Processing scan...</div>';
    
    // Send the UID to the server
    fetch('scan_processor.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `UIDresult=${uid}`
    })
    .then(response => response.json())
    .then(data => {
        let resultHtml = '';
        
        if (data.status === 'success') {
            // Create success message with animation
            resultHtml = '<div class="alert alert-success" role="alert">';
            resultHtml += '<div class="success-animation">';
            resultHtml += '<i class="fa fa-check"></i>';
            resultHtml += '</div>';
            resultHtml += `<h4 class="alert-heading">Success!</h4>`;
            resultHtml += `<p>${data.message}</p>`;
            
            if (data.data && data.data.log_type) {
                resultHtml += '<hr>';
                resultHtml += `<p class="mb-0">Action: ${data.data.log_type === 'entry' ? 'Clock In' : 'Clock Out'}</p>`;
            }
            
            resultHtml += '</div>';
        } else {
            // Create error message
            resultHtml = '<div class="alert alert-danger" role="alert">';
            resultHtml += '<h4 class="alert-heading">Error</h4>';
            resultHtml += `<p>${data.message}</p>`;
            resultHtml += '</div>';
        }
        
        resultContainer.innerHTML = resultHtml;
        
        // Also update the status container if it exists
        loadCurrentStatus();
    })
    .catch(error => {
        console.error('Error processing scan:', error);
        resultContainer.innerHTML = '<div class="alert alert-danger" role="alert">' +
            '<h4 class="alert-heading">System Error</h4>' +
            '<p>Unable to process scan. Please try again or contact administrator.</p>' +
            '</div>';
    });
}

// Load employee data for dashboard
function loadEmployeeData(containerId = 'employee-list') {
    const container = document.getElementById(containerId);
    
    if (!container) return;
    
    // Show loading indicator
    container.innerHTML = '<p class="text-center">Loading employee data...</p>';
    
    // Fetch data from API
    fetch('api.php?endpoint=all_employees')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data) {
                let html = '<div class="table-responsive">';
                html += '<table class="table table-hover employee-table">';
                html += `<thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Status</th>
                        <th>Last Activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>`;
                html += '<tbody>';
                
                data.data.forEach(employee => {
                    const lastActivity = employee.last_scan ? formatDate(employee.last_scan, true) : 'No activity';
                    
                    html += '<tr>';
                    html += '<td>';
                    // Profile image or placeholder
                    if (employee.profile_image) {
                        html += `<img src="${employee.profile_image}" alt="${employee.name}" class="me-2">`;
                    } else {
                        html += `<div class="status-dot ${employee.current_status === 'in' ? 'status-in' : 'status-out'}"></div>`;
                    }
                    html += employee.name;
                    html += '</td>';
                    html += `<td>${employee.department || 'N/A'}</td>`;
                    html += `<td>${employee.position || 'N/A'}</td>`;
                    html += `<td>
                        <span class="badge ${employee.current_status === 'in' ? 'bg-success' : 'bg-danger'}">
                            ${employee.current_status === 'in' ? 'Present' : 'Absent'}
                        </span>
                    </td>`;
                    html += `<td>${lastActivity}</td>`;
                    html += `<td>
                        <a href="employee-details.php?id=${employee.employee_id}" class="btn btn-sm btn-info">View</a>
                    </td>`;
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
                
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="alert alert-danger">Error loading employee data.</div>';
            }
        })
        .catch(error => {
            console.error('Error fetching employee data:', error);
            container.innerHTML = '<div class="alert alert-danger">Error loading employee data.</div>';
        });
}

// Load daily attendance summary for dashboard
function loadAttendanceSummary(date = null, containerId = 'attendance-summary') {
    const container = document.getElementById(containerId);
    
    if (!container) return;
    
    // Show loading indicator
    container.innerHTML = '<p class="text-center">Loading attendance data...</p>';
    
    // Use today's date if none provided
    const queryDate = date || new Date().toISOString().split('T')[0];
    
    // Fetch data from API
    fetch(`api.php?endpoint=daily_attendance&date=${queryDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data) {
                const summary = data.data;
                
                // Create HTML for the summary cards
                let html = '<div class="row">';
                
                // Present employees card
                html += '<div class="col-md-3 col-sm-6">';
                html += '<div class="stat-card present-card">';
                html += '<span class="stat-label">Present</span>';
                html += `<div class="stat-number">${summary.present}</div>`;
                html += `<span class="stat-label">of ${summary.total_employees}</span>`;
                html += '</div>';
                html += '</div>';
                
                // Late employees card
                html += '<div class="col-md-3 col-sm-6">';
                html += '<div class="stat-card late-card">';
                html += '<span class="stat-label">Late</span>';
                html += `<div class="stat-number">${summary.late}</div>`;
                html += '<span class="stat-label">employees</span>';
                html += '</div>';
                html += '</div>';
                
                // Absent employees card
                html += '<div class="col-md-3 col-sm-6">';
                html += '<div class="stat-card absent-card">';
                html += '<span class="stat-label">Absent</span>';
                html += `<div class="stat-number">${summary.absent}</div>`;
                html += '<span class="stat-label">employees</span>';
                html += '</div>';
                html += '</div>';
                
                // Total hours card
                html += '<div class="col-md-3 col-sm-6">';
                html += '<div class="stat-card hours-card">';
                html += '<span class="stat-label">Total Hours</span>';
                html += `<div class="stat-number">${summary.total_hours}</div>`;
                html += '<span class="stat-label">worked today</span>';
                html += '</div>';
                html += '</div>';
                
                html += '</div>'; // end row
                
                // Department breakdown
                if (summary.department_breakdown && summary.department_breakdown.length > 0) {
                    html += '<div class="card mt-4">';
                    html += '<div class="card-header bg-secondary text-white">';
                    html += '<h5 class="mb-0">Department Breakdown</h5>';
                    html += '</div>';
                    html += '<div class="card-body">';
                    html += '<div class="table-responsive">';
                    html += '<table class="table table-sm table-striped">';
                    html += '<thead><tr><th>Department</th><th>Present</th><th>Late</th><th>Absent</th><th>Attendance %</th></tr></thead>';
                    html += '<tbody>';
                    
                    summary.department_breakdown.forEach(dept => {
                        const present = dept.present_in_dept || 0;
                        const total = dept.total_in_dept || 0;
                        const late = dept.late_in_dept || 0;
                        const absent = total - present;
                        const percentage = total > 0 ? Math.round((present / total) * 100) : 0;
                        
                        html += '<tr>';
                        html += `<td>${dept.department || 'Unassigned'}</td>`;
                        html += `<td>${present}</td>`;
                        html += `<td>${late}</td>`;
                        html += `<td>${absent}</td>`;
                        html += `<td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: ${percentage}%;" 
                                     aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100">${percentage}%</div>
                            </div>
                        </td>`;
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table></div>';
                    html += '</div>';
                    html += '</div>';
                }
                
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="alert alert-danger">Error loading attendance summary.</div>';
            }
        })
        .catch(error => {
            console.error('Error fetching attendance summary:', error);
            container.innerHTML = '<div class="alert alert-danger">Error loading attendance summary.</div>';
        });
}

// Initialize date pickers for reports
function initDatePickers() {
    // This requires a date picker library like flatpickr or bootstrap-datepicker
    // Implementation depends on which library you choose
    
    // Example using flatpickr if included:
    if (typeof flatpickr === 'function') {
        flatpickr('.date-picker', {
            dateFormat: 'Y-m-d',
            defaultDate: 'today'
        });
    }
}

// Document ready function
document.addEventListener('DOMContentLoaded', function() {
    // Initialize clock on pages that need it
    if (document.getElementById('current-time')) {
        updateClock();
    }
    
    // Initialize date pickers
    initDatePickers();
    
    // Load current status on scan page
    if (document.getElementById('status-container')) {
        loadCurrentStatus();
        // Refresh status every 30 seconds
        setInterval(loadCurrentStatus, 30000);
    }
    
    // Load employee data on dashboard
    if (document.getElementById('employee-list')) {
        loadEmployeeData();
    }
    
    // Load attendance summary on dashboard
    if (document.getElementById('attendance-summary')) {
        loadAttendanceSummary();
    }
});