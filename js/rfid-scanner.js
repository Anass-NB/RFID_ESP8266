/**
 * RFID Scanner JavaScript Functions
 * 
 * This file contains client-side functionality for the RFID scanning interface
 */

// Variables to track card scan state
let scanTimer = null;
let scanInterval = null;
let oldUID = "";
let scanning = false;

/**
 * Initialize the RFID scanner functionality
 */
function initializeScanner() {
    // Load UID from container and check every 500ms for changes
    $("#getUID").load("UIDContainer.php");
    setInterval(function() {
        $("#getUID").load("UIDContainer.php");
        checkForNewScan();
    }, 500);
    
    // Initialize clock display
    updateClock();
    setInterval(updateClock, 1000);
    
    // Initial load of present employees
    updatePresentEmployees();
    // Periodically update the list of present employees
    setInterval(updatePresentEmployees, 30000);
}

/**
 * Update the clock display
 */
function updateClock() {
    const now = new Date();
    const timeElement = document.getElementById('current-time');
    const dateElement = document.getElementById('current-date');
    
    if (timeElement && dateElement) {
        timeElement.textContent = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'});
        dateElement.textContent = now.toLocaleDateString([], {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'});
    }
    
    // Update welcome message based on time of day
    updateWelcomeMessage(now.getHours());
}

/**
 * Update the welcome message based on time of day
 * @param {number} hour - Current hour (0-23)
 */
function updateWelcomeMessage(hour) {
    const welcomeElement = document.getElementById('welcome-message');
    if (!welcomeElement) return;
    
    let greeting = "Please Scan Your RFID Card";
    
    if (hour >= 5 && hour < 12) {
        greeting = "Good Morning! Please Scan Your RFID Card";
    } else if (hour >= 12 && hour < 17) {
        greeting = "Good Afternoon! Please Scan Your RFID Card";
    } else {
        greeting = "Good Evening! Please Scan Your RFID Card";
    }
    
    welcomeElement.textContent = greeting;
}

/**
 * Check for new RFID card scans
 */
function checkForNewScan() {
    if (scanning) return;
    
    const uidElement = document.getElementById("getUID");
    if (!uidElement) return;
    
    const currentUID = uidElement.textContent;
    
    // If UID has changed and is not empty
    if (currentUID !== oldUID && currentUID !== "") {
        scanning = true;
        oldUID = currentUID;
        
        // Process the scan
        processScan(currentUID);
        
        // Set a timeout to reset the scanning state
        setTimeout(function() {
            scanning = false;
        }, 3000);
    }
}

/**
 * Process an RFID card scan
 * @param {string} uid - RFID card UID
 */
function processScan(uid) {
    // Show scanning animation
    document.getElementById('scan-result').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Processing scan...</p>
        </div>
    `;
    
    // Play scan sound if available
    const scanSound = document.getElementById('scan-sound');
    if (scanSound) {
        scanSound.play().catch(e => console.log('Sound play error:', e));
    }
    
    // Send UID to server for processing
    fetch('scan_processor.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `UIDresult=${uid}`
    })
    .then(response => response.json())
    .then(handleScanResponse)
    .catch(handleScanError);
}

/**
 * Handle the scan response from the server
 * @param {Object} response - Server response object
 */
function handleScanResponse(response) {
    let resultHtml = '';
    
    if (response.status === 'success') {
        // Play success sound if available
        const successSound = document.getElementById('success-sound');
        if (successSound) {
            successSound.play().catch(e => console.log('Sound play error:', e));
        }
        
        // Success response
        resultHtml = '<div class="text-center">';
        
        // If employee data is available
        if (response.data && response.data.employee) {
            const employee = response.data.employee;
            const action = response.data.log_type === 'entry' ? 'Clock In' : 'Clock Out';
            const actionColor = response.data.log_type === 'entry' ? 'success' : 'danger';
            
            // Employee image or initial
            resultHtml += '<div class="employee-image">';
            if (employee.profile_image) {
                resultHtml += `<img src="${employee.profile_image}" alt="${employee.name}">`;
            } else {
                resultHtml += `<div class="employee-initial">${employee.name.charAt(0)}</div>`;
            }
            resultHtml += '</div>';
            
            // Success checkmark
            resultHtml += '<i class="fas fa-check-circle success-checkmark"></i>';
            
            // Greeting and status message
            resultHtml += `<h4>${response.message}</h4>`;
            
            // Action badge
            resultHtml += '<div class="mt-3 mb-3">';
            resultHtml += `<span class="badge bg-${actionColor} px-4 py-2 fs-6">${action}</span>`;
            resultHtml += '</div>';
            
            // Department and position if available
            if (employee.department || employee.position) {
                resultHtml += '<p class="text-muted">';
                if (employee.department) {
                    resultHtml += employee.department;
                }
                if (employee.position) {
                    resultHtml += employee.department ? ' | ' : '';
                    resultHtml += employee.position;
                }
                resultHtml += '</p>';
            }
            
            // Timestamp display
            if (response.data.timestamp) {
                const timestamp = new Date(response.data.timestamp);
                resultHtml += `<p class="text-muted">${timestamp.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>`;
            }
        } else {
            // Basic success message if employee data is not available
            resultHtml += '<i class="fas fa-check-circle success-checkmark"></i>';
            resultHtml += `<h4>${response.message}</h4>`;
        }
        
        resultHtml += '</div>';
        
        // Clear the result after 5 seconds
        setTimeout(function() {
            document.getElementById('scan-result').innerHTML = '';
        }, 5000);
    } else {
        // Play error sound if available
        const errorSound = document.getElementById('error-sound');
        if (errorSound) {
            errorSound.play().catch(e => console.log('Sound play error:', e));
        }
        
        // Error response
        resultHtml = '<div class="text-center">';
        resultHtml += '<i class="fas fa-times-circle error-xmark"></i>';
        resultHtml += '<h4>Error</h4>';
        resultHtml += `<p class="text-danger">${response.message}</p>`;
        resultHtml += '</div>';
        
        // For unregistered cards, show registration link
        if (response.data && response.data.rfid_uid) {
            resultHtml += '<div class="text-center mt-3">';
            resultHtml += '<a href="registration.php" class="btn btn-primary">Register this card</a>';
            resultHtml += '</div>';
        }
    }
    
    // Update the scan result area
    document.getElementById('scan-result').innerHTML = resultHtml;
    
    // Update the present employees list
    updatePresentEmployees();
}

/**
 * Handle errors during scan processing
 */
function handleScanError() {
    // Show error message if AJAX request fails
    document.getElementById('scan-result').innerHTML = `
        <div class="text-center">
            <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
            <h4 class="mt-3">System Error</h4>
            <p>Unable to process scan. Please try again or contact administrator.</p>
        </div>
    `;
    
    // Play error sound if available
    const errorSound = document.getElementById('error-sound');
    if (errorSound) {
        errorSound.play().catch(e => console.log('Sound play error:', e));
    }
}

/**
 * Update the list of currently present employees
 */
function updatePresentEmployees() {
    const employeesContainer = document.getElementById('present-employees');
    if (!employeesContainer) return;
    
    fetch('api.php?endpoint=present_employees')
        .then(response => response.json())
        .then(response => {
            if (response.status === 'success') {
                let html = '';
                const employees = response.data || [];
                const countElement = document.getElementById('present-count');
                
                if (countElement) {
                    countElement.textContent = employees.length;
                }
                
                if (employees.length > 0) {
                    employees.forEach(employee => {
                        html += '<div class="employee-row d-flex align-items-center">';
                        
                        if (employee.profile_image) {
                            html += `<img src="${employee.profile_image}" alt="${employee.name}" class="employee-avatar">`;
                        } else {
                            html += `<div class="employee-initial-small">${employee.name.charAt(0)}</div>`;
                        }
                        
                        html += '<div>';
                        html += `<div class="name">${employee.name}</div>`;
                        html += '<div class="time"><i class="far fa-clock"></i> In since ';
                        html += new Date(employee.entry_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                    });
                } else {
                    html = '<div class="p-3 text-center text-muted">No employees currently present</div>';
                }
                
                employeesContainer.innerHTML = html;
            }
        })
        .catch(error => {
            console.error('Error fetching present employees:', error);
            employeesContainer.innerHTML = '<div class="p-3 text-center text-danger">Error loading employee data</div>';
        });
}

// Initialize scanner when document is ready
document.addEventListener('DOMContentLoaded', initializeScanner);