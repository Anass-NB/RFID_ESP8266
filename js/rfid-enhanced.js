/**
 * Enhanced JavaScript for RFID Employee Attendance System
 * Provides improved functionality for UI elements, animations, and data handling
 */

// Initialize the application when DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all tooltips
    initTooltips();
    
    // Initialize animation effects
    initAnimations();
    
    // Initialize the clock if present
    initClock();
    
    // Initialize form validation
    initFormValidation();
    
    // Initialize RFID scanning functionality if present
    initRFIDScanning();
    
    // Initialize data tables
    initDataTables();
    
    // Initialize kiosk mode if applicable
    initKioskMode();
});

/**
 * Initialize Bootstrap tooltips for enhanced UI
 */
function initTooltips() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    if (tooltipTriggerList.length > 0) {
        [...tooltipTriggerList].map(tooltipTriggerEl => {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                boundary: document.body
            });
        });
    }
}

/**
 * Initialize animation effects for UI elements
 */
function initAnimations() {
    // Card hover effects - applies to app-card that don't have .no-hover class
    const cards = document.querySelectorAll('.app-card:not(.no-hover)');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Stat card hover effects
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = 'var(--box-shadow-lg)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'var(--box-shadow)';
        });
    });
    
    // Button hover animations
    const buttons = document.querySelectorAll('.btn:not(.btn-link)');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            if (!this.classList.contains('no-hover')) {
                this.style.transform = 'translateY(-2px)';
            }
        });
        button.addEventListener('mouseleave', function() {
            if (!this.classList.contains('no-hover')) {
                this.style.transform = 'translateY(0)';
            }
        });
    });
    
    // Add smooth scrolling to page anchors
    document.querySelectorAll('a[href^="#"]:not([data-bs-toggle])').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * Initialize the live clock display
 */
function initClock() {
    const timeElement = document.getElementById('current-time');
    const dateElement = document.getElementById('current-date');
    
    if (timeElement || dateElement) {
        updateClock();
        // Update the clock every second
        setInterval(updateClock, 1000);
    }
    
    function updateClock() {
        const now = new Date();
        
        if (timeElement) {
            timeElement.textContent = formatTime(now);
        }
        
        if (dateElement) {
            dateElement.textContent = formatDate(now);
        }
    }
    
    function formatTime(date) {
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        const seconds = date.getSeconds().toString().padStart(2, '0');
        
        // Check if we should use 12-hour format based on locale
        if (isPrefer12Hour()) {
            const hour12 = date.getHours() % 12 || 12;
            const ampm = date.getHours() >= 12 ? 'PM' : 'AM';
            return `${hour12}:${minutes}:${seconds} ${ampm}`;
        } else {
            return `${hours}:${minutes}:${seconds}`;
        }
    }
    
    function formatDate(date) {
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        return date.toLocaleDateString(undefined, options);
    }
    
    function isPrefer12Hour() {
        // Simple check for whether user is likely to prefer 12-hour format
        const format = new Intl.DateTimeFormat(navigator.language, { 
            hour: 'numeric'
        }).format(new Date());
        return format.includes('AM') || format.includes('PM');
    }
}

/**
 * Initialize form validation for all forms with the 'needs-validation' class
 */
function initFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    if (forms.length > 0) {
        // Loop over them and prevent submission
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    // Find the first invalid element and focus it
                    const invalidElement = form.querySelector(':invalid');
                    if (invalidElement) {
                        invalidElement.focus();
                    }
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    }
    
    // Add custom validation styles for file inputs
    const fileInputs = document.querySelectorAll('input[type="file"].form-control');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.files.length) {
                let fileName = this.files[0].name;
                if (fileName.length > 25) {
                    fileName = fileName.substring(0, 22) + '...';
                }
                
                // Create or update file name display
                let fileNameElement = this.nextElementSibling;
                if (!fileNameElement || !fileNameElement.classList.contains('file-name')) {
                    fileNameElement = document.createElement('small');
                    fileNameElement.classList.add('file-name', 'text-muted', 'd-block', 'mt-1');
                    this.parentNode.insertBefore(fileNameElement, this.nextSibling);
                }
                
                fileNameElement.textContent = fileName;
            }
        });
    });
}

/**
 * Initialize RFID scanning functionality if the relevant elements exist
 */
function initRFIDScanning() {
    const uidElement = document.getElementById('getUID');
    
    if (uidElement) {
        // Load UID from UIDContainer and refresh every 500ms
        loadUID();
        const uidInterval = setInterval(loadUID, 500);
        
        // Clear UID button
        const clearButton = document.getElementById('clear-uid');
        if (clearButton) {
            clearButton.addEventListener('click', function() {
                clearUID();
            });
        }
        
        // Department dropdown handling
        const deptDropdown = document.getElementById('department');
        const newDeptContainer = document.getElementById('new-department-container');
        const newDeptInput = document.getElementById('new-department');
        
        if (deptDropdown && newDeptContainer && newDeptInput) {
            deptDropdown.addEventListener('change', function() {
                if (this.value === "new") {
                    newDeptContainer.style.display = 'block';
                    newDeptInput.required = true;
                    newDeptInput.focus();
                } else {
                    newDeptContainer.style.display = 'none';
                    newDeptInput.required = false;
                }
            });
        }
    }
    
    function loadUID() {
        fetch('UIDContainer.php')
            .then(response => response.text())
            .then(data => {
                if (uidElement.value !== data) {
                    uidElement.value = data;
                    // Add animation if UID is received
                    if (data.trim() !== '') {
                        uidElement.classList.add('bg-success', 'text-white');
                        setTimeout(() => {
                            uidElement.classList.remove('bg-success', 'text-white');
                        }, 1000);
                    }
                }
            })
            .catch(error => console.error('Error loading UID:', error));
    }
    
    function clearUID() {
        fetch('getUID.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'UIDresult='
        })
        .then(response => {
            if (response.ok) {
                uidElement.value = '';
                uidElement.classList.remove('bg-success', 'text-white');
            }
        })
        .catch(error => console.error('Error clearing UID:', error));
    }
}

/**
 * Initialize enhanced data tables with search, sort, and pagination
 */
function initDataTables() {
    // Simple table search functionality
    const searchInputs = document.querySelectorAll('.table-search');
    
    searchInputs.forEach(input => {
        const tableId = input.getAttribute('data-table');
        const table = document.getElementById(tableId);
        
        if (table) {
            input.addEventListener('keyup', function() {
                const searchText = this.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchText)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });
    
    // Table export functionality
    const exportButtons = document.querySelectorAll('.export-table');
    
    exportButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tableId = this.getAttribute('data-table');
            const table = document.getElementById(tableId);
            
            if (table) {
                exportTableToCSV(table);
            }
        });
    });
    
    // Table print functionality
    const printButtons = document.querySelectorAll('.print-table');
    
    printButtons.forEach(button => {
        button.addEventListener('click', function() {
            window.print();
        });
    });
}

/**
 * Export table data to CSV file
 */
function exportTableToCSV(table) {
    const rows = table.querySelectorAll('tr');
    const csvContent = [];
    
    rows.forEach(row => {
        const rowData = [];
        const cells = row.querySelectorAll('th, td');
        
        cells.forEach(cell => {
            // Skip cells with the "no-export" class or that are part of the actions column
            if (!cell.classList.contains('no-export') && !cell.classList.contains('actions')) {
                // Get the text content and clean it up
                let text = cell.textContent.trim()
                    .replace(/\\n/g, ' ')
                    .replace(/,/g, ';')
                    .replace(/"/g, '""');
                
                rowData.push(`"${text}"`);
            }
        });
        
        csvContent.push(rowData.join(','));
    });
    
    // Create and download the CSV file
    const csv = csvContent.join('\\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    
    link.setAttribute('href', url);
    link.setAttribute('download', 'export.csv');
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Initialize kiosk mode functionality
 */
function initKioskMode() {
    const kioskContainer = document.querySelector('.kiosk-container');
    
    if (kioskContainer) {
        // Initialize kiosk clock
        const kioskTimeElement = document.querySelector('.kiosk-clock .time');
        const kioskDateElement = document.querySelector('.kiosk-clock .date');
        
        if (kioskTimeElement || kioskDateElement) {
            updateKioskClock();
            // Update the clock every second
            setInterval(updateKioskClock, 1000);
        }
        
        // Fullscreen toggle
        const fullscreenToggle = document.querySelector('.fullscreen-toggle');
        
        if (fullscreenToggle) {
            fullscreenToggle.addEventListener('click', function() {
                toggleFullscreen();
            });
        }
        
        // Handle Escape key to exit fullscreen
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isFullscreen()) {
                exitFullscreen();
            }
        });
        
        // Auto-refresh kiosk mode to keep it active
        setInterval(function() {
            // Reload the UID container to keep checking for new scans
            if (document.getElementById('getUID')) {
                fetch('UIDContainer.php')
                    .then(response => response.text())
                    .then(data => {
                        const uidElement = document.getElementById('getUID');
                        if (uidElement && uidElement.value !== data) {
                            uidElement.value = data;
                        }
                    })
                    .catch(error => console.error('Error reloading UID:', error));
            }
        }, 1000);
    }
    
    function updateKioskClock() {
        const now = new Date();
        
        if (kioskTimeElement) {
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const seconds = now.getSeconds().toString().padStart(2, '0');
            
            // Check if we should use 12-hour format based on locale
            if (isPrefer12Hour()) {
                const hour12 = now.getHours() % 12 || 12;
                const ampm = now.getHours() >= 12 ? 'PM' : 'AM';
                kioskTimeElement.textContent = `${hour12}:${minutes}:${seconds} ${ampm}`;
            } else {
                kioskTimeElement.textContent = `${hours}:${minutes}:${seconds}`;
            }
        }
        
        if (kioskDateElement) {
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            kioskDateElement.textContent = now.toLocaleDateString(undefined, options);
        }
    }
    
    function isPrefer12Hour() {
        const format = new Intl.DateTimeFormat(navigator.language, { 
            hour: 'numeric'
        }).format(new Date());
        return format.includes('AM') || format.includes('PM');
    }
    
    function toggleFullscreen() {
        if (isFullscreen()) {
            exitFullscreen();
        } else {
            enterFullscreen(kioskContainer);
        }
    }
    
    function enterFullscreen(element) {
        if (element.requestFullscreen) {
            element.requestFullscreen();
        } else if (element.mozRequestFullScreen) {
            element.mozRequestFullScreen();
        } else if (element.webkitRequestFullscreen) {
            element.webkitRequestFullscreen();
        } else if (element.msRequestFullscreen) {
            element.msRequestFullscreen();
        }
    }
    
    function exitFullscreen() {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
    }
    
    function isFullscreen() {
        return !!(
            document.fullscreenElement ||
            document.mozFullScreenElement ||
            document.webkitFullscreenElement ||
            document.msFullscreenElement
        );
    }
}

/**
 * Generate a random ID for use with dynamic elements
 */
function generateId(prefix = 'el') {
    return `${prefix}-${Math.random().toString(36).substring(2, 11)}`;
}

/**
 * Format date for inputs (YYYY-MM-DD)
 */
function formatDateForInput(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * Format a number with commas for thousands separators
 */
function formatNumber(num) {
    return num.toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, ",");
}

/**
 * Show a toast notification
 */
function showToast(message, type = 'info', duration = 3000) {
    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    // Create a unique ID for this toast
    const toastId = generateId('toast');
    
    // Create the toast element
    const toast = document.createElement('div');
    toast.className = `toast bg-${type} text-white`;
    toast.setAttribute('id', toastId);
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    // Set the toast content
    toast.innerHTML = `
        <div class="toast-header bg-${type} text-white">
            <strong class="me-auto">Notification</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            ${message}
        </div>
    `;
    
    // Add the toast to the container
    toastContainer.appendChild(toast);
    
    // Initialize the Bootstrap toast
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: duration
    });
    
    // Show the toast
    bsToast.show();
    
    // Remove the toast element after it's hidden
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

/**
 * Create a loading spinner
 */
function createLoadingSpinner(container, text = 'Loading...') {
    const spinner = document.createElement('div');
    spinner.className = 'text-center py-4';
    spinner.innerHTML = `
        <div class="spinner-border text-primary mb-2" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mb-0">${text}</p>
    `;
    
    if (typeof container === 'string') {
        container = document.querySelector(container);
    }
    
    if (container) {
        container.innerHTML = '';
        container.appendChild(spinner);
    }
    
    return spinner;
}

/**
 * Remove a loading spinner
 */
function removeLoadingSpinner(spinner) {
    if (spinner && spinner.parentNode) {
        spinner.parentNode.removeChild(spinner);
    }
}