/**
 * Data Visualization Functions for Employee Time Tracking System
 * 
 * This file contains functions for creating various charts and visualizations
 * to display attendance patterns, work hours, and other employee metrics.
 * Uses Chart.js for rendering the visualizations.
 */

// Ensure Chart.js is loaded
if (typeof Chart === 'undefined') {
    console.error('Chart.js is required for visualizations. Please include it in your HTML.');
}

/**
 * Initialize all charts on the page based on data attributes
 */
function initializeCharts() {
    // Initialize attendance summary chart if present
    const attendanceSummaryElement = document.getElementById('attendance-summary-chart');
    if (attendanceSummaryElement) {
        createAttendanceSummaryChart(attendanceSummaryElement);
    }
    
    // Initialize work hours chart if present
    const workHoursChartElement = document.getElementById('work-hours-chart');
    if (workHoursChartElement) {
        createWorkHoursChart(workHoursChartElement);
    }
    
    // Initialize department comparison chart if present
    const departmentChartElement = document.getElementById('department-comparison-chart');
    if (departmentChartElement) {
        createDepartmentComparisonChart(departmentChartElement);
    }
    
    // Initialize punctuality chart if present
    const punctualityChartElement = document.getElementById('punctuality-chart');
    if (punctualityChartElement) {
        createPunctualityChart(punctualityChartElement);
    }
    
    // Initialize trend chart if present
    const trendChartElement = document.getElementById('attendance-trend-chart');
    if (trendChartElement) {
        createAttendanceTrendChart(trendChartElement);
    }
    
    // Initialize daily activity chart if present
    const dailyActivityElement = document.getElementById('daily-activity-chart');
    if (dailyActivityElement) {
        createDailyActivityChart(dailyActivityElement);
    }
}

/**
 * Create an attendance summary pie chart
 * @param {HTMLElement} element - The canvas element to render the chart in
 */
function createAttendanceSummaryChart(element) {
    try {
        // Get chart data from the element's data attributes
        const present = parseInt(element.dataset.present) || 0;
        const late = parseInt(element.dataset.late) || 0;
        const absent = parseInt(element.dataset.absent) || 0;
        
        const ctx = element.getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Present', 'Late', 'Absent'],
                datasets: [{
                    data: [present - late, late, absent],
                    backgroundColor: [
                        '#4caf50', // Present
                        '#ff9800', // Late
                        '#f44336'  // Absent
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                family: "'Poppins', sans-serif"
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Attendance Summary',
                        font: {
                            family: "'Poppins', sans-serif",
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const total = present + absent;
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${context.label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error creating attendance summary chart:', error);
        element.insertAdjacentHTML('afterend', '<div class="alert alert-danger">Error loading chart</div>');
    }
}

/**
 * Create a work hours bar chart showing hours worked by day
 * @param {HTMLElement} element - The canvas element to render the chart in
 */
function createWorkHoursChart(element) {
    try {
        // Check if data is provided via API endpoint or data attributes
        const dataEndpoint = element.dataset.endpoint;
        const employeeId = element.dataset.employeeId;
        const startDate = element.dataset.startDate;
        const endDate = element.dataset.endDate;
        
        if (dataEndpoint) {
            // Fetch data from API
            fetch(`${dataEndpoint}?employee_id=${employeeId}&start_date=${startDate}&end_date=${endDate}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        renderWorkHoursChart(element, data.data);
                    } else {
                        throw new Error(data.message || 'Failed to load data');
                    }
                })
                .catch(error => {
                    console.error('Error fetching work hours data:', error);
                    element.insertAdjacentHTML('afterend', '<div class="alert alert-danger">Error loading chart data</div>');
                });
        } else {
            // Use data from data attributes
            const dates = JSON.parse(element.dataset.dates || '[]');
            const hours = JSON.parse(element.dataset.hours || '[]');
            const statuses = JSON.parse(element.dataset.statuses || '[]');
            
            renderWorkHoursChart(element, { dates, hours, statuses });
        }
    } catch (error) {
        console.error('Error setting up work hours chart:', error);
        element.insertAdjacentHTML('afterend', '<div class="alert alert-danger">Error initializing chart</div>');
    }
}

/**
 * Render the work hours chart with the provided data
 * @param {HTMLElement} element - The canvas element
 * @param {Object} data - The chart data
 */
function renderWorkHoursChart(element, data) {
    // Extract or use the provided dates, hours, and statuses
    const dates = data.dates || (data.records ? data.records.map(r => r.work_date) : []);
    const hours = data.hours || (data.records ? data.records.map(r => r.work_hours) : []);
    const statuses = data.statuses || (data.records ? data.records.map(r => r.status) : []);
    
    // Generate bar colors based on status
    const barColors = hours.map((value, index) => {
        const status = statuses[index] || '';
        if (status === 'late') return '#ff9800'; // Late - Orange
        if (status === 'absent') return '#f44336'; // Absent - Red
        return '#4caf50'; // Present - Green
    });
    
    // Format dates for display
    const formattedDates = dates.map(date => {
        const d = new Date(date);
        return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    });
    
    const ctx = element.getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: formattedDates,
            datasets: [{
                label: 'Work Hours',
                data: hours,
                backgroundColor: barColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Hours',
                        font: {
                            family: "'Poppins', sans-serif"
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Daily Work Hours',
                    font: {
                        family: "'Poppins', sans-serif",
                        size: 16,
                        weight: 'bold'
                    }
                },
                tooltip: {
                    callbacks: {
                        title: function(tooltipItems) {
                            const index = tooltipItems[0].dataIndex;
                            return dates[index];
                        },
                        label: function(context) {
                            const index = context.dataIndex;
                            const status = statuses[index] || 'present';
                            return `Hours: ${context.raw} | Status: ${status.charAt(0).toUpperCase() + status.slice(1)}`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create a department comparison chart
 * @param {HTMLElement} element - The canvas element to render the chart in
 */
function createDepartmentComparisonChart(element) {
    try {
        // Parse department data from data attributes
        const departments = JSON.parse(element.dataset.departments || '[]');
        const presentCounts = JSON.parse(element.dataset.presentCounts || '[]');
        const lateCounts = JSON.parse(element.dataset.lateCounts || '[]');
        const absentCounts = JSON.parse(element.dataset.absentCounts || '[]');
        
        const ctx = element.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: departments,
                datasets: [
                    {
                        label: 'Present',
                        data: presentCounts.map((value, index) => value - lateCounts[index]),
                        backgroundColor: '#4caf50',
                        stack: 'Stack 0'
                    },
                    {
                        label: 'Late',
                        data: lateCounts,
                        backgroundColor: '#ff9800',
                        stack: 'Stack 0'
                    },
                    {
                        label: 'Absent',
                        data: absentCounts,
                        backgroundColor: '#f44336',
                        stack: 'Stack 0'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Departments',
                            font: {
                                family: "'Poppins', sans-serif"
                            }
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Employees',
                            font: {
                                family: "'Poppins', sans-serif"
                            }
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Department Attendance Comparison',
                        font: {
                            family: "'Poppins', sans-serif",
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                family: "'Poppins', sans-serif"
                            }
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error creating department comparison chart:', error);
        element.insertAdjacentHTML('afterend', '<div class="alert alert-danger">Error loading department comparison chart</div>');
    }
}

/**
 * Create a punctuality chart showing on-time vs late arrivals
 * @param {HTMLElement} element - The canvas element to render the chart in
 */
function createPunctualityChart(element) {
    try {
        // Get data from element data attributes
        const timePeriods = JSON.parse(element.dataset.periods || '[]');
        const onTimeData = JSON.parse(element.dataset.onTime || '[]');
        const lateData = JSON.parse(element.dataset.late || '[]');
        
        const ctx = element.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: timePeriods,
                datasets: [
                    {
                        label: 'On Time',
                        data: onTimeData,
                        borderColor: '#4caf50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Late',
                        data: lateData,
                        borderColor: '#ff9800',
                        backgroundColor: 'rgba(255, 152, 0, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Employees',
                            font: {
                                family: "'Poppins', sans-serif"
                            }
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Punctuality Trends',
                        font: {
                            family: "'Poppins', sans-serif",
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                family: "'Poppins', sans-serif"
                            }
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error creating punctuality chart:', error);
        element.insertAdjacentHTML('afterend', '<div class="alert alert-danger">Error loading punctuality chart</div>');
    }
}

/**
 * Create an attendance trend chart showing attendance patterns over time
 * @param {HTMLElement} element - The canvas element to render the chart in
 */
function createAttendanceTrendChart(element) {
    try {
        // Get data from element data attributes
        const dates = JSON.parse(element.dataset.dates || '[]');
        const attendanceRates = JSON.parse(element.dataset.rates || '[]');
        
        const ctx = element.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Attendance Rate (%)',
                    data: attendanceRates,
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Attendance Rate (%)',
                            font: {
                                family: "'Poppins', sans-serif"
                            }
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Attendance Trend',
                        font: {
                            family: "'Poppins', sans-serif",
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    legend: {
                        display: false
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error creating attendance trend chart:', error);
        element.insertAdjacentHTML('afterend', '<div class="alert alert-danger">Error loading attendance trend chart</div>');
    }
}

/**
 * Create a chart showing daily activity patterns (entries and exits by hour)
 * @param {HTMLElement} element - The canvas element to render the chart in
 */
function createDailyActivityChart(element) {
    try {
        // Get data from element data attributes or API
        const hours = ['8AM', '9AM', '10AM', '11AM', '12PM', '1PM', '2PM', '3PM', '4PM', '5PM', '6PM'];
        const entriesData = JSON.parse(element.dataset.entries || '[]');
        const exitsData = JSON.parse(element.dataset.exits || '[]');
        
        const ctx = element.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: hours,
                datasets: [
                    {
                        label: 'Entries',
                        data: entriesData,
                        backgroundColor: '#4caf50',
                        borderWidth: 1
                    },
                    {
                        label: 'Exits',
                        data: exitsData,
                        backgroundColor: '#f44336',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Employees',
                            font: {
                                family: "'Poppins', sans-serif"
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Hour of Day',
                            font: {
                                family: "'Poppins', sans-serif"
                            }
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Daily Activity Pattern',
                        font: {
                            family: "'Poppins', sans-serif",
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                family: "'Poppins', sans-serif"
                            }
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error creating daily activity chart:', error);
        element.insertAdjacentHTML('afterend', '<div class="alert alert-danger">Error loading daily activity chart</div>');
    }
}

/**
 * Create a heatmap showing attendance patterns by day of week
 * @param {HTMLElement} element - The canvas element to render the chart in
 */
function createAttendanceHeatmap(element) {
    try {
        // This is more complex and would require additional plugins
        // For simplicity, we'll display a message
        element.insertAdjacentHTML('afterend', 
            '<div class="alert alert-info">Heatmap visualization requires additional plugins. ' +
            'Consider using a dedicated heatmap library like CalHeatmap or D3.js for this.</div>');
    } catch (error) {
        console.error('Error with heatmap message:', error);
    }
}

/**
 * Generate random data for demo purposes
 * @param {number} count - Number of data points to generate
 * @param {number} min - Minimum value
 * @param {number} max - Maximum value
 * @returns {Array} Array of random values
 */
function generateRandomData(count, min, max) {
    return Array.from({length: count}, () => 
        Math.floor(Math.random() * (max - min + 1)) + min
    );
}

/**
 * Generate dates array for demo purposes
 * @param {number} daysAgo - Number of days to go back
 * @returns {Array} Array of date strings
 */
function generateDates(daysAgo) {
    const dates = [];
    const today = new Date();
    
    for (let i = daysAgo; i >= 0; i--) {
        const date = new Date();
        date.setDate(today.getDate() - i);
        dates.push(date.toISOString().split('T')[0]);
    }
    
    return dates;
}

/**
 * Create demo charts with random data
 * This is useful for prototyping and testing
 */
function createDemoCharts() {
    // Demo attendance summary chart
    const demoAttendanceElement = document.getElementById('demo-attendance-summary-chart');
    if (demoAttendanceElement) {
        demoAttendanceElement.setAttribute('data-present', '45');
        demoAttendanceElement.setAttribute('data-late', '8');
        demoAttendanceElement.setAttribute('data-absent', '7');
        createAttendanceSummaryChart(demoAttendanceElement);
    }
    
    // Demo work hours chart
    const demoWorkHoursElement = document.getElementById('demo-work-hours-chart');
    if (demoWorkHoursElement) {
        const dates = generateDates(6);
        const hours = generateRandomData(7, 6, 9);
        const statuses = hours.map(h => h < 7 ? 'late' : 'present');
        
        demoWorkHoursElement.setAttribute('data-dates', JSON.stringify(dates));
        demoWorkHoursElement.setAttribute('data-hours', JSON.stringify(hours));
        demoWorkHoursElement.setAttribute('data-statuses', JSON.stringify(statuses));
        
        createWorkHoursChart(demoWorkHoursElement);
    }
    
    // Demo department comparison chart
    const demoDeptElement = document.getElementById('demo-department-chart');
    if (demoDeptElement) {
        const departments = ['Engineering', 'Marketing', 'Sales', 'HR', 'Finance'];
        const presents = generateRandomData(5, 10, 20);
        const lates = generateRandomData(5, 2, 6);
        const absents = generateRandomData(5, 1, 5);
        
        demoDeptElement.setAttribute('data-departments', JSON.stringify(departments));
        demoDeptElement.setAttribute('data-present-counts', JSON.stringify(presents));
        demoDeptElement.setAttribute('data-late-counts', JSON.stringify(lates));
        demoDeptElement.setAttribute('data-absent-counts', JSON.stringify(absents));
        
        createDepartmentComparisonChart(demoDeptElement);
    }
    
    // Demo punctuality chart
    const demoPunctualityElement = document.getElementById('demo-punctuality-chart');
    if (demoPunctualityElement) {
        const periods = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
        const onTime = generateRandomData(5, 30, 45);
        const late = generateRandomData(5, 5, 15);
        
        demoPunctualityElement.setAttribute('data-periods', JSON.stringify(periods));
        demoPunctualityElement.setAttribute('data-on-time', JSON.stringify(onTime));
        demoPunctualityElement.setAttribute('data-late', JSON.stringify(late));
        
        createPunctualityChart(demoPunctualityElement);
    }
    
    // Demo attendance trend chart
    const demoTrendElement = document.getElementById('demo-trend-chart');
    if (demoTrendElement) {
        const dates = generateDates(29).filter((_, i) => i % 5 === 0); // Every 5 days
        const rates = generateRandomData(dates.length, 70, 98);
        
        demoTrendElement.setAttribute('data-dates', JSON.stringify(dates));
        demoTrendElement.setAttribute('data-rates', JSON.stringify(rates));
        
        createAttendanceTrendChart(demoTrendElement);
    }
    
    // Demo daily activity chart
    const demoActivityElement = document.getElementById('demo-activity-chart');
    if (demoActivityElement) {
        const entries = [3, 20, 8, 2, 5, 2, 1, 2, 1, 0, 0];
        const exits = [0, 1, 2, 3, 12, 3, 3, 4, 8, 9, 3];
        
        demoActivityElement.setAttribute('data-entries', JSON.stringify(entries));
        demoActivityElement.setAttribute('data-exits', JSON.stringify(exits));
        
        createDailyActivityChart(demoActivityElement);
    }
}

// Initialize charts when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if we should create demo charts or real charts
    if (document.querySelector('[data-demo-charts="true"]')) {
        createDemoCharts();
    } else {
        initializeCharts();
    }
});