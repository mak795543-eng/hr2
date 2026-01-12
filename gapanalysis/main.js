// main.js

// Global variables
let filteredData = [];
let currentPage = 1;
const itemsPerPage = 10;
let barChart, radarChart;
let currentGapId = null;

// DOM Elements
let totalEmployeesEl, averageGapEl, criticalGapsEl, activePlansEl;
let departmentFilter, typeFilter, priorityFilter, applyFiltersBtn, resetFiltersBtn;
let prevPageBtn, nextPageBtn, currentPageEl;
let tableBody, tableCount, tableSummary;

// Modal elements
let actionPlanModal, modalCompetency, modalEmployee, modalGapScore;
let planNameInput, actionTypeSelect, descriptionTextarea, resourcesTextarea;
let startDateInput, endDateInput, estimatedHoursInput, createdByInput;
let savePlanBtn, closeModalBtn;

// Initialize the application
document.addEventListener('DOMContentLoaded', async function() {
    await initializeApp();
    setupEventListeners();
});

async function initializeApp() {
    // Cache DOM elements
    cacheDOMElements();
    
    // Initialize modal
    initializeModal();
    
    // Load initial data
    await loadInitialData();
    
    // Initialize charts
    initializeCharts();
    
    // Apply initial filters
    await applyFilters();
}

function cacheDOMElements() {
    // Summary cards
    totalEmployeesEl = document.getElementById('totalEmployees');
    averageGapEl = document.getElementById('averageGap');
    criticalGapsEl = document.getElementById('criticalGaps');
    activePlansEl = document.getElementById('activePlans');
    
    // Filters
    departmentFilter = document.getElementById('departmentFilter');
    typeFilter = document.getElementById('typeFilter');
    priorityFilter = document.getElementById('priorityFilter');
    applyFiltersBtn = document.getElementById('applyFilters');
    resetFiltersBtn = document.getElementById('resetFilters');
    
    // Table elements
    prevPageBtn = document.getElementById('prevPage');
    nextPageBtn = document.getElementById('nextPage');
    currentPageEl = document.getElementById('currentPage');
    tableBody = document.getElementById('gapTableBody');
    tableCount = document.getElementById('tableCount');
    tableSummary = document.getElementById('tableSummary');
}

function initializeModal() {
    // Get modal elements
    actionPlanModal = document.getElementById('actionPlanModal');
    modalCompetency = document.getElementById('modalCompetency');
    modalEmployee = document.getElementById('modalEmployee');
    modalGapScore = document.getElementById('modalGapScore');
    
    // Form elements
    planNameInput = document.getElementById('planName');
    actionTypeSelect = document.getElementById('actionType');
    descriptionTextarea = document.getElementById('description');
    resourcesTextarea = document.getElementById('resources');
    startDateInput = document.getElementById('startDate');
    endDateInput = document.getElementById('endDate');
    estimatedHoursInput = document.getElementById('estimatedHours');
    createdByInput = document.getElementById('createdBy');
    
    // Buttons
    savePlanBtn = document.getElementById('savePlan');
    closeModalBtn = document.getElementById('closeModal');
    
    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    const nextMonthStr = nextMonth.toISOString().split('T')[0];
    
    startDateInput.value = today;
    endDateInput.value = nextMonthStr;
    createdByInput.value = 'Admin User';
    
    // Set minimum dates
    startDateInput.min = today;
    endDateInput.min = today;
    
    // Add event listeners for modal
    savePlanBtn.addEventListener('click', saveActionPlan);
    closeModalBtn.addEventListener('click', () => actionPlanModal.close());
    
    // Close modal when clicking outside
    actionPlanModal.addEventListener('click', (e) => {
        if (e.target === actionPlanModal) {
            actionPlanModal.close();
        }
    });
}

async function loadInitialData() {
    try {
        // Load summary statistics
        const statsResponse = await fetch('config.php?action=getSummaryStats');
        const stats = await statsResponse.json();
        
        // Update summary cards
        totalEmployeesEl.textContent = stats.totalEmployees;
        averageGapEl.textContent = stats.averageGap;
        criticalGapsEl.textContent = stats.criticalGaps;
        activePlansEl.textContent = stats.activePlans;
        
        // Populate department filter
        stats.departments.forEach(dept => {
            const option = document.createElement('option');
            option.value = dept;
            option.textContent = dept;
            departmentFilter.appendChild(option);
        });
        
        // Populate type filter
        stats.types.forEach(type => {
            const option = document.createElement('option');
            option.value = type;
            option.textContent = type;
            typeFilter.appendChild(option);
        });
        
    } catch (error) {
        console.error('Error loading data:', error);
        showToast('Error loading data from database', 'error');
    }
}

function setupEventListeners() {
    applyFiltersBtn.addEventListener('click', applyFilters);
    resetFiltersBtn.addEventListener('click', resetFilters);
    prevPageBtn.addEventListener('click', () => changePage(-1));
    nextPageBtn.addEventListener('click', () => changePage(1));
    
    // Add event listeners to filter dropdowns
    [departmentFilter, typeFilter, priorityFilter].forEach(filter => {
        filter.addEventListener('change', applyFilters);
    });
}

async function applyFilters() {
    const department = departmentFilter.value;
    const type = typeFilter.value;
    const priority = priorityFilter.value;
    
    try {
        // Build query string for filters
        const params = new URLSearchParams();
        params.append('action', 'getFilteredData');
        if (department !== 'all') params.append('department', department);
        if (type !== 'all') params.append('type', type);
        if (priority !== 'all') params.append('priority', priority);
        
        const response = await fetch(`config.php?${params.toString()}`);
        filteredData = await response.json();
        
        // Reset to first page
        currentPage = 1;
        
        // Update table
        renderTable();
        
        // Update charts with filtered data
        updateCharts();
        
        showToast(`${filteredData.length} records found with current filters`, 'success');
        
    } catch (error) {
        console.error('Error applying filters:', error);
        showToast('Error applying filters', 'error');
    }
}

function resetFilters() {
    departmentFilter.value = 'all';
    typeFilter.value = 'all';
    priorityFilter.value = 'all';
    
    applyFilters();
    showToast('Filters reset to default', 'info');
}

function renderTable() {
    // Clear table
    tableBody.innerHTML = '';
    
    if (!filteredData || filteredData.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center py-8 text-gray-500">
                    <i class="fas fa-search fa-2x mb-4"></i>
                    <p>No competency gaps found with the current filters.</p>
                    <p class="text-sm mt-2">Try adjusting your filter criteria.</p>
                </td>
            </tr>
        `;
        tableCount.textContent = 'Showing 0 of 0 records';
        tableSummary.textContent = 'No data available';
        currentPageEl.textContent = '1';
        prevPageBtn.disabled = true;
        nextPageBtn.disabled = true;
        return;
    }
    
    // Calculate pagination
    const totalPages = Math.ceil(filteredData.length / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, filteredData.length);
    const pageData = filteredData.slice(startIndex, endIndex);
    
    // Populate table
    pageData.forEach(row => {
        // Determine gap color and priority badge
        let gapColor = 'green-500';
        let priorityClass = 'badge-success';
        
        if (row.gap_score >= 2) {
            gapColor = 'red-500';
            priorityClass = 'badge-error';
        } else if (row.gap_score === 1) {
            gapColor = 'amber-500';
            priorityClass = 'badge-warning';
        }
        
        if (row.priority === 'Critical') priorityClass = 'badge-error';
        else if (row.priority === 'High') priorityClass = 'badge-warning';
        else if (row.priority === 'Medium') priorityClass = 'badge-info';
        else if (row.priority === 'Low') priorityClass = 'badge-success';
        
        // Determine plan status
        let planStatus = row.plan_status || 'No Plan';
        let planClass = 'text-gray-500';
        if (planStatus === 'In Progress') planClass = 'text-blue-600';
        else if (planStatus === 'Completed') planClass = 'text-green-600';
        else if (planStatus === 'Planned') planClass = 'text-amber-600';
        else if (planStatus === 'Delayed') planClass = 'text-red-600';
        
        const tableRow = document.createElement('tr');
        tableRow.innerHTML = `
            <td class="font-medium">${row.competency_name}</td>
            <td><span class="badge badge-outline">${row.competency_type}</span></td>
            <td>${row.required_level}/5</td>
            <td>${row.actual_level}/5</td>
            <td>
                <div class="flex items-center">
                    <div class="gap-indicator bg-${gapColor}"></div>
                    <span class="font-semibold">${row.gap_score}</span>
                </div>
            </td>
            <td><span class="badge ${priorityClass}">${row.priority}</span></td>
            <td>
                <button class="btn btn-xs btn-primary view-plan-btn" data-gap-id="${row.gap_id}">
                    <i class="fas fa-eye mr-1"></i> View Plan
                </button>
                <button class="btn btn-xs btn-outline ml-2 create-plan-btn" data-gap-id="${row.gap_id}">
                    <i class="fas fa-plus mr-1"></i> Create
                </button>
            </td>
            <td><span class="${planClass}">${planStatus}</span></td>
            <td>${row.employee_name}</td>
            <td>${row.employee_department}</td>
        `;
        
        // Add event listeners to buttons
        const viewBtn = tableRow.querySelector('.view-plan-btn');
        const createBtn = tableRow.querySelector('.create-plan-btn');
        
        viewBtn.addEventListener('click', () => openActionPlanModal(row.gap_id, 'view'));
        createBtn.addEventListener('click', () => openActionPlanModal(row.gap_id, 'create'));
        
        tableBody.appendChild(tableRow);
    });
    
    // Update pagination info
    tableCount.textContent = `Showing ${startIndex + 1}-${endIndex} of ${filteredData.length} records`;
    tableSummary.textContent = `${filteredData.length} competency gaps found`;
    currentPageEl.textContent = currentPage;
    
    // Update pagination buttons
    prevPageBtn.disabled = currentPage === 1;
    nextPageBtn.disabled = currentPage === totalPages;
}

async function openActionPlanModal(gapId, mode) {
    currentGapId = gapId;
    
    try {
        // Find the row data
        const rowData = filteredData.find(row => row.gap_id == gapId);
        
        if (!rowData) {
            showToast('Could not find competency gap data', 'error');
            return;
        }
        
        // Update modal header with gap details
        modalCompetency.textContent = rowData.competency_name;
        modalEmployee.textContent = `${rowData.employee_name} (${rowData.employee_department})`;
        modalGapScore.textContent = `Gap Score: ${rowData.gap_score}`;
        
        // Set modal title based on mode
        document.getElementById('modalTitle').textContent = 
            mode === 'view' ? 'View Action Plan' : 'Create Action Plan';
        
        // Load existing action plan if viewing
        if (mode === 'view') {
            const response = await fetch(`config.php?action=getActionPlan&gap_id=${gapId}`);
            const planData = await response.json();
            
            if (planData && !planData.error) {
                // Fill form with existing data
                planNameInput.value = planData.plan_name || '';
                actionTypeSelect.value = planData.action_type || '';
                descriptionTextarea.value = planData.description || '';
                resourcesTextarea.value = planData.resources_needed || '';
                startDateInput.value = planData.start_date || '';
                endDateInput.value = planData.end_date || '';
                estimatedHoursInput.value = planData.estimated_hours || '';
                createdByInput.value = planData.created_by || '';
                
                // Show status info
                document.getElementById('planStatus').textContent = `Status: ${planData.status}`;
                document.getElementById('planProgress').textContent = `Progress: ${planData.progress_percentage}%`;
            } else {
                // No existing plan
                resetForm();
                document.getElementById('planStatus').textContent = 'Status: No Plan Created';
                document.getElementById('planProgress').textContent = '';
            }
        } else {
            // Create mode - reset form
            resetForm();
            document.getElementById('planStatus').textContent = '';
            document.getElementById('planProgress').textContent = '';
        }
        
        // Show the modal
        actionPlanModal.showModal();
        
    } catch (error) {
        console.error('Error opening modal:', error);
        showToast('Error loading action plan details', 'error');
    }
}

function resetForm() {
    const today = new Date().toISOString().split('T')[0];
    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    const nextMonthStr = nextMonth.toISOString().split('T')[0];
    
    planNameInput.value = '';
    actionTypeSelect.value = 'Training';
    descriptionTextarea.value = '';
    resourcesTextarea.value = '';
    startDateInput.value = today;
    endDateInput.value = nextMonthStr;
    estimatedHoursInput.value = '8';
    createdByInput.value = 'Admin User';
}

async function saveActionPlan() {
    if (!currentGapId) {
        showToast('No competency gap selected', 'error');
        return;
    }
    
    // Validate form
    if (!planNameInput.value.trim()) {
        showToast('Please enter a plan name', 'error');
        return;
    }
    
    if (!startDateInput.value || !endDateInput.value) {
        showToast('Please select start and end dates', 'error');
        return;
    }
    
    // Prepare form data
    const formData = new FormData();
    formData.append('action', 'saveActionPlan');
    formData.append('gap_id', currentGapId);
    formData.append('plan_name', planNameInput.value);
    formData.append('action_type', actionTypeSelect.value);
    formData.append('description', descriptionTextarea.value);
    formData.append('resources', resourcesTextarea.value);
    formData.append('start_date', startDateInput.value);
    formData.append('end_date', endDateInput.value);
    formData.append('estimated_hours', estimatedHoursInput.value);
    formData.append('created_by', createdByInput.value);
    
    try {
        const response = await fetch('config.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Action plan saved successfully!', 'success');
            actionPlanModal.close();
            
            // Refresh the table to show updated plan status
            applyFilters();
        } else {
            showToast(result.error || 'Failed to save action plan', 'error');
        }
    } catch (error) {
        console.error('Error saving action plan:', error);
        showToast('Error saving action plan', 'error');
    }
}

function changePage(direction) {
    const totalPages = Math.ceil(filteredData.length / itemsPerPage);
    const newPage = currentPage + direction;
    
    if (newPage < 1 || newPage > totalPages) return;
    
    currentPage = newPage;
    renderTable();
}

function initializeCharts() {
    // Initialize Bar Chart
    const barCtx = document.getElementById('barChart').getContext('2d');
    barChart = new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Required Level',
                    data: [],
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 1
                },
                {
                    label: 'Actual Level',
                    data: [],
                    backgroundColor: 'rgba(255, 159, 64, 0.7)',
                    borderColor: 'rgb(255, 159, 64)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.raw}/5`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 5,
                    title: {
                        display: true,
                        text: 'Proficiency Level (1-5)'
                    }
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 0
                    }
                }
            }
        }
    });

    // Initialize Radar Chart
    const radarCtx = document.getElementById('radarChart').getContext('2d');
    radarChart = new Chart(radarCtx, {
        type: 'radar',
        data: {
            labels: ['Hotel', 'Restaurant', 'General Skill', 'Core Skill', 'Soft Skill'],
            datasets: [
                {
                    label: 'Average Gap by Type',
                    data: [0, 0, 0, 0, 0],
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgb(255, 99, 132)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgb(255, 99, 132)'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                r: {
                    beginAtZero: true,
                    max: 3,
                    ticks: {
                        stepSize: 0.5
                    }
                }
            }
        }
    });
    
    // Load initial chart data
    updateCharts();
}

async function updateCharts() {
    try {
        // Get chart data with current filters
        const department = departmentFilter.value;
        const params = new URLSearchParams();
        params.append('action', 'getChartData');
        if (department !== 'all') params.append('department', department);
        
        const response = await fetch(`config.php?${params.toString()}`);
        const chartData = await response.json();
        
        // Update bar chart
        if (chartData.barChart && chartData.barChart.length > 0) {
            const labels = chartData.barChart.map(item => item.competency_name);
            const avgTarget = chartData.barChart.map(item => parseFloat(item.avg_target));
            const avgActual = chartData.barChart.map(item => parseFloat(item.avg_actual));
            
            updateBarChart(labels, avgTarget, avgActual);
        }
        
        // Update radar chart
        if (chartData.radarChart && chartData.radarChart.length > 0) {
            // Map types to expected order
            const typeOrder = ['Hotel', 'Restaurant', 'General Skill', 'Core Skill', 'Soft Skill'];
            const radarData = typeOrder.map(type => {
                const typeData = chartData.radarChart.find(item => item.type === type);
                return typeData ? parseFloat(typeData.avg_gap) : 0;
            });
            
            updateRadarChart(radarData);
        }
        
    } catch (error) {
        console.error('Error updating charts:', error);
    }
}

function updateBarChart(labels, targetData, currentData) {
    barChart.data.labels = labels;
    barChart.data.datasets[0].data = targetData;
    barChart.data.datasets[1].data = currentData;
    barChart.update();
}

function updateRadarChart(data) {
    radarChart.data.datasets[0].data = data;
    radarChart.update();
}

function showToast(message, type = 'info') {
    // Remove any existing toast
    const existingToast = document.querySelector('.toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = 'toast toast-top toast-end';
    
    let alertClass = 'alert-info';
    if (type === 'success') alertClass = 'alert-success';
    else if (type === 'error') alertClass = 'alert-error';
    else if (type === 'warning') alertClass = 'alert-warning';
    
    toast.innerHTML = `
        <div class="alert ${alertClass} shadow-lg">
            <div>
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Remove toast after 3 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 3000);
}

// Export functions for chart export
function exportChart(chartId, filename) {
    const chartCanvas = document.getElementById(chartId);
    const link = document.createElement('a');
    link.download = filename;
    link.href = chartCanvas.toDataURL('image/png');
    link.click();
    showToast(`Chart exported as ${filename}`, 'success');
}

function toggleFullscreen(containerId) {
    const container = document.getElementById(containerId);
    if (!document.fullscreenElement) {
        if (container.requestFullscreen) {
            container.requestFullscreen();
        }
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
}

// Make functions available globally
window.exportChart = exportChart;
window.toggleFullscreen = toggleFullscreen;