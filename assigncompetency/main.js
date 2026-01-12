// main.js

// Database configuration - UPDATED DATABASE NAME
const DB_CONFIG = {
    host: 'localhost',
    db: 'assign_competency',  // Changed from 'gap_analysis'
    user: 'root',
    pass: ''
};

// Application state
let employees = [];
let competencies = [];
let currentEmployeeId = null;
let currentCompetencyId = null;
let currentAction = 'add';

// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    init();
});

// Initialize application
async function init() {
    setupEventListeners();
    await loadAllData();
    renderAll();
}

// Setup event listeners
function setupEventListeners() {
    // Search input for employees
    const searchEmployee = document.getElementById('searchEmployee');
    const filterDepartment = document.getElementById('filterDepartment');
    const searchCompetency = document.getElementById('searchCompetency');
    const filterCompetencyType = document.getElementById('filterCompetencyType');
    const proficiencyRange = document.getElementById('modalAssignProficiency');
    
    if (searchEmployee) {
        searchEmployee.addEventListener('input', function(e) {
            renderEmployeeTable(e.target.value, filterDepartment.value);
        });
    }
    
    if (filterDepartment) {
        filterDepartment.addEventListener('change', function(e) {
            renderEmployeeTable(searchEmployee.value, e.target.value);
        });
    }
    
    if (searchCompetency) {
        searchCompetency.addEventListener('input', function(e) {
            renderCompetencyTable(e.target.value, filterCompetencyType.value);
        });
    }
    
    if (filterCompetencyType) {
        filterCompetencyType.addEventListener('change', function(e) {
            renderCompetencyTable(searchCompetency.value, e.target.value);
        });
    }
    
    if (proficiencyRange) {
        proficiencyRange.addEventListener('input', function(e) {
            updateProficiencyDisplay(e.target.value);
        });
    }
}

// Load all data
async function loadAllData() {
    try {
        // Load employees with their competencies
        const employeeResult = await queryDatabase(`
            SELECT e.*, 
                   GROUP_CONCAT(CONCAT(c.name, ' (', ec.proficiency_level, '/5)') SEPARATOR '|') as competency_info,
                   AVG(ec.proficiency_level) as avg_proficiency,
                   COUNT(ec.competency_id) as competency_count
            FROM employees e 
            LEFT JOIN employee_competencies ec ON e.id = ec.employee_id 
            LEFT JOIN competencies c ON ec.competency_id = c.id
            GROUP BY e.id
        `);
        
        employees = employeeResult.map(emp => ({
            ...emp,
            competencies: emp.competency_info ? emp.competency_info.split('|') : []
        }));
        
        // Load competencies
        competencies = await queryDatabase('SELECT * FROM competencies ORDER BY type, name');
        
        // Load departments for filter
        const departments = await queryDatabase('SELECT DISTINCT department FROM employees ORDER BY department');
        populateDepartmentFilter(departments);
        
        // Calculate statistics
        calculateStatistics();
        
    } catch (error) {
        console.error('Error loading data:', error);
        alert('Failed to load data. Please check your database connection.');
    }
}

// Query database
async function queryDatabase(sql, params = []) {
    try {
        const response = await fetch('config/db.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'query',
                sql: sql,
                params: params
            })
        });
        
        const result = await response.json();
        
        if (result.error) {
            throw new Error(result.error);
        }
        
        return result.data || [];
    } catch (error) {
        console.error('Database query error:', error);
        throw error;
    }
}

// Execute database command
async function executeDatabase(sql, params = []) {
    try {
        const response = await fetch('config/db.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'execute',
                sql: sql,
                params: params
            })
        });
        
        const result = await response.json();
        
        if (result.error) {
            throw new Error(result.error);
        }
        
        return result;
    } catch (error) {
        console.error('Database execute error:', error);
        throw error;
    }
}

// Populate department filter
function populateDepartmentFilter(departments) {
    const filter = document.getElementById('filterDepartment');
    if (!filter) return;
    
    filter.innerHTML = '<option value="">All Departments</option>';
    
    departments.forEach(dept => {
        if (dept.department) {
            const option = document.createElement('option');
            option.value = dept.department;
            option.textContent = dept.department;
            filter.appendChild(option);
        }
    });
}

// Calculate statistics
function calculateStatistics() {
    // Total employees
    const totalEmployees = document.getElementById('totalEmployees');
    if (totalEmployees) totalEmployees.textContent = employees.length;
    
    // Total competencies
    const totalCompetencies = document.getElementById('totalCompetencies');
    if (totalCompetencies) totalCompetencies.textContent = competencies.length;
    
    // Calculate average proficiency
    let totalProficiency = 0;
    let proficiencyCount = 0;
    let highPerformers = 0;
    
    employees.forEach(employee => {
        const avgProficiency = employee.avg_proficiency || 0;
        if (avgProficiency > 0) {
            totalProficiency += parseFloat(avgProficiency);
            proficiencyCount++;
            
            if (avgProficiency >= 4) {
                highPerformers++;
            }
        }
    });
    
    const avgProficiency = proficiencyCount > 0 ? (totalProficiency / proficiencyCount).toFixed(1) : '0.0';
    
    const avgProficiencyEl = document.getElementById('avgProficiency');
    const highPerformersEl = document.getElementById('highPerformers');
    
    if (avgProficiencyEl) avgProficiencyEl.textContent = avgProficiency;
    if (highPerformersEl) highPerformersEl.textContent = highPerformers;
}

// Render all components
function renderAll() {
    renderEmployeeTable();
    renderCompetencyTable();
}

// Render employee table
function renderEmployeeTable(searchText = '', department = '') {
    const tbody = document.getElementById('employeeTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    let filteredEmployees = employees;
    
    // Apply search filter
    if (searchText) {
        filteredEmployees = filteredEmployees.filter(emp =>
            emp.name.toLowerCase().includes(searchText.toLowerCase()) ||
            emp.position.toLowerCase().includes(searchText.toLowerCase()) ||
            emp.email.toLowerCase().includes(searchText.toLowerCase())
        );
    }
    
    // Apply department filter
    if (department) {
        filteredEmployees = filteredEmployees.filter(emp =>
            emp.department === department
        );
    }
    
    // Render each employee
    filteredEmployees.forEach(employee => {
        const avgProficiency = employee.avg_proficiency ? parseFloat(employee.avg_proficiency).toFixed(1) : 'N/A';
        const proficiencyLevel = getProficiencyLevel(employee.avg_proficiency);
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="font-bold">${employee.name}</div>
                <div class="text-sm text-gray-600">${employee.email}</div>
                <div class="text-xs">${employee.department}</div>
            </td>
            <td class="font-medium">${employee.position}</td>
            <td>
                <div class="max-h-24 overflow-y-auto mb-2">
                    ${employee.competencies && employee.competencies.length > 0 && employee.competencies[0] !== ''
                        ? employee.competencies.map(comp => 
                            `<div class="text-sm mb-1">• ${comp}</div>`
                        ).join('')
                        : '<span class="text-gray-400">No competencies assigned</span>'
                    }
                </div>
                <button onclick="assignCompetency(${employee.id})" class="btn btn-xs btn-outline" style="background-color: white; color: black; border-color: black;">
                    <i class="fas fa-plus mr-1"></i>Assign Competency
                </button>
            </td>
            <td>
                <div class="badge">${avgProficiency}/5</div>
                <div class="text-sm text-gray-600 mt-1">${proficiencyLevel}</div>
            </td>
            <td>
                <div class="flex space-x-2">
                    <button onclick="editEmployee(${employee.id})" class="btn btn-xs btn-outline" style="background-color: white; color: black; border-color: black;">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteEmployee(${employee.id})" class="btn btn-xs btn-outline" style="background-color: white; color: black; border-color: black;">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Get proficiency level text
function getProficiencyLevel(score) {
    if (!score) return 'Not assessed';
    if (score >= 4.5) return 'Expert';
    if (score >= 3.5) return 'Advanced';
    if (score >= 2.5) return 'Competent';
    if (score >= 1.5) return 'Basic';
    return 'Novice';
}

// Render competency table
function renderCompetencyTable(searchText = '', type = '') {
    const tbody = document.getElementById('competencyTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    let filteredCompetencies = competencies;
    
    // Apply search filter
    if (searchText) {
        filteredCompetencies = filteredCompetencies.filter(comp =>
            comp.name.toLowerCase().includes(searchText.toLowerCase()) ||
            comp.category.toLowerCase().includes(searchText.toLowerCase()) ||
            (comp.description && comp.description.toLowerCase().includes(searchText.toLowerCase()))
        );
    }
    
    // Apply type filter
    if (type) {
        filteredCompetencies = filteredCompetencies.filter(comp =>
            comp.type === type
        );
    }
    
    // Render each competency
    filteredCompetencies.forEach(competency => {
        // Determine badge class based on type
        let badgeClass = 'badge';
        if (competency.type === 'hotel') {
            badgeClass = 'badge badge-hotel';
        } else if (competency.type === 'restaurant') {
            badgeClass = 'badge badge-restaurant';
        } else if (competency.type === 'general') {
            badgeClass = 'badge badge-general';
        }
        
        // Get display name for type
        let typeDisplay = competency.type;
        if (competency.type === 'hotel') typeDisplay = 'Hotel';
        else if (competency.type === 'restaurant') typeDisplay = 'Restaurant';
        else if (competency.type === 'general') typeDisplay = 'General Skill';
        else if (competency.type === 'core') typeDisplay = 'Core Skill';
        else if (competency.type === 'technical') typeDisplay = 'Technical Skill';
        else if (competency.type === 'soft') typeDisplay = 'Soft Skill';
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="font-bold">${competency.id}</td>
            <td class="font-medium">${competency.name}</td>
            <td><span class="${badgeClass}">${typeDisplay}</span></td>
            <td><span class="badge">${competency.category}</span></td>
            <td class="text-sm max-w-xs truncate">${competency.description || '-'}</td>
            <td>
                <div class="flex space-x-2">
                    <button onclick="editCompetency(${competency.id})" class="btn btn-xs btn-outline" style="background-color: white; color: black; border-color: black;">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteCompetency(${competency.id})" class="btn btn-xs btn-outline" style="background-color: white; color: black; border-color: black;">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Show modals
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('modal-open');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('modal-open');
}

// Show proficiency legend
function showProficiencyLegend() {
    showModal('legendModal');
}

// Show add employee modal
function showAddEmployeeModal() {
    currentAction = 'add';
    const title = document.getElementById('employeeModalTitle');
    if (title) title.textContent = 'Add New Employee';
    
    document.getElementById('modalEmployeeName').value = '';
    document.getElementById('modalEmployeePosition').value = 'Waiter';
    document.getElementById('modalEmployeeDepartment').value = '';
    document.getElementById('modalEmployeeEmail').value = '';
    document.getElementById('modalEmployeeHireDate').value = '';
    showModal('employeeModal');
}

// Edit employee
function editEmployee(id) {
    const employee = employees.find(e => e.id === id);
    if (!employee) return;
    
    currentAction = 'edit';
    currentEmployeeId = id;
    
    const title = document.getElementById('employeeModalTitle');
    if (title) title.textContent = 'Edit Employee';
    
    document.getElementById('modalEmployeeName').value = employee.name;
    document.getElementById('modalEmployeePosition').value = employee.position;
    document.getElementById('modalEmployeeDepartment').value = employee.department;
    document.getElementById('modalEmployeeEmail').value = employee.email;
    document.getElementById('modalEmployeeHireDate').value = employee.hire_date || '';
    showModal('employeeModal');
}

// Save employee
async function saveEmployee() {
    const name = document.getElementById('modalEmployeeName').value.trim();
    const position = document.getElementById('modalEmployeePosition').value;
    const department = document.getElementById('modalEmployeeDepartment').value.trim();
    const email = document.getElementById('modalEmployeeEmail').value.trim();
    const hireDate = document.getElementById('modalEmployeeHireDate').value;
    
    if (!name || !department || !email) {
        alert('Please fill in all required fields');
        return;
    }
    
    try {
        if (currentAction === 'add') {
            const sql = 'INSERT INTO employees (name, position, department, email, hire_date) VALUES (?, ?, ?, ?, ?)';
            await executeDatabase(sql, [name, position, department, email, hireDate || null]);
            alert('Employee added successfully!');
        } else {
            const sql = 'UPDATE employees SET name = ?, position = ?, department = ?, email = ?, hire_date = ? WHERE id = ?';
            await executeDatabase(sql, [name, position, department, email, hireDate || null, currentEmployeeId]);
            alert('Employee updated successfully!');
        }
        
        closeModal('employeeModal');
        await loadAllData();
        renderAll();
        
    } catch (error) {
        alert('Error saving employee: ' + error.message);
    }
}

// Delete employee
async function deleteEmployee(id) {
    if (!confirm('Are you sure you want to delete this employee?')) return;
    
    try {
        await executeDatabase('DELETE FROM employees WHERE id = ?', [id]);
        alert('Employee deleted successfully!');
        await loadAllData();
        renderAll();
    } catch (error) {
        alert('Error deleting employee: ' + error.message);
    }
}

// Show add competency modal
function showAddCompetencyModal() {
    currentAction = 'add';
    const title = document.getElementById('competencyModalTitle');
    if (title) title.textContent = 'Add New Competency';
    
    document.getElementById('modalCompetencyName').value = '';
    document.getElementById('modalCompetencyType').value = 'general';
    document.getElementById('modalCompetencyCategory').value = 'Teamwork';
    document.getElementById('modalCompetencyDescription').value = '';
    showModal('competencyModal');
}

// Edit competency
function editCompetency(id) {
    const competency = competencies.find(c => c.id === id);
    if (!competency) return;
    
    currentAction = 'edit';
    currentCompetencyId = id;
    
    const title = document.getElementById('competencyModalTitle');
    if (title) title.textContent = 'Edit Competency';
    
    document.getElementById('modalCompetencyName').value = competency.name;
    document.getElementById('modalCompetencyType').value = competency.type;
    document.getElementById('modalCompetencyCategory').value = competency.category;
    document.getElementById('modalCompetencyDescription').value = competency.description || '';
    showModal('competencyModal');
}

// Save competency
async function saveCompetency() {
    const name = document.getElementById('modalCompetencyName').value.trim();
    const type = document.getElementById('modalCompetencyType').value;
    const category = document.getElementById('modalCompetencyCategory').value;
    const description = document.getElementById('modalCompetencyDescription').value.trim();
    
    if (!name) {
        alert('Please enter a competency name');
        return;
    }
    
    try {
        if (currentAction === 'add') {
            const sql = 'INSERT INTO competencies (name, type, category, description) VALUES (?, ?, ?, ?)';
            await executeDatabase(sql, [name, type, category, description]);
            alert('Competency added successfully!');
        } else {
            const sql = 'UPDATE competencies SET name = ?, type = ?, category = ?, description = ? WHERE id = ?';
            await executeDatabase(sql, [name, type, category, description, currentCompetencyId]);
            alert('Competency updated successfully!');
        }
        
        closeModal('competencyModal');
        await loadAllData();
        renderAll();
        
    } catch (error) {
        alert('Error saving competency: ' + error.message);
    }
}

// Delete competency
async function deleteCompetency(id) {
    if (!confirm('Are you sure you want to delete this competency?')) return;
    
    try {
        // Check if competency is assigned to any employee
        const result = await queryDatabase('SELECT COUNT(*) as count FROM employee_competencies WHERE competency_id = ?', [id]);
        
        if (result[0].count > 0) {
            alert('Cannot delete competency. It is assigned to one or more employees.');
            return;
        }
        
        await executeDatabase('DELETE FROM competencies WHERE id = ?', [id]);
        alert('Competency deleted successfully!');
        await loadAllData();
        renderAll();
    } catch (error) {
        alert('Error deleting competency: ' + error.message);
    }
}

// Assign competency to employee
async function assignCompetency(employeeId) {
    currentEmployeeId = employeeId;
    
    // Populate competency dropdown
    const select = document.getElementById('modalAssignCompetency');
    if (select) {
        select.innerHTML = '<option value="">Choose a competency</option>';
        
        competencies.forEach(comp => {
            const option = document.createElement('option');
            option.value = comp.id;
            option.textContent = `${comp.name} (${comp.type})`;
            select.appendChild(option);
        });
    }
    
    // Reset form
    document.getElementById('modalAssignProficiency').value = 3;
    document.getElementById('modalAssignNotes').value = '';
    updateProficiencyDisplay(3);
    
    // Get employee name for modal title
    const employee = employees.find(e => e.id === employeeId);
    const title = document.getElementById('assignModalTitle');
    if (title && employee) title.textContent = `Assign Competency to ${employee.name}`;
    
    showModal('assignCompetencyModal');
}

// Update proficiency display
function updateProficiencyDisplay(value) {
    const display = document.getElementById('proficiencyValueDisplay');
    if (!display) return;
    
    const levels = ['Novice', 'Basic', 'Competent', 'Advanced', 'Expert'];
    display.textContent = `Level ${value} - ${levels[value-1]}`;
}

// Save assigned competency
async function saveAssignedCompetency() {
    const competencyId = parseInt(document.getElementById('modalAssignCompetency').value);
    const proficiency = parseInt(document.getElementById('modalAssignProficiency').value);
    const notes = document.getElementById('modalAssignNotes').value.trim();
    
    if (!competencyId) {
        alert('Please select a competency');
        return;
    }
    
    try {
        // Check if already assigned
        const existing = await queryDatabase(
            'SELECT id FROM employee_competencies WHERE employee_id = ? AND competency_id = ?',
            [currentEmployeeId, competencyId]
        );
        
        if (existing.length > 0) {
            // Update existing
            await executeDatabase(
                'UPDATE employee_competencies SET proficiency_level = ?, notes = ?, assessed_date = ? WHERE id = ?',
                [proficiency, notes, new Date().toISOString().split('T')[0], existing[0].id]
            );
            alert('Competency proficiency updated!');
        } else {
            // Insert new
            await executeDatabase(
                'INSERT INTO employee_competencies (employee_id, competency_id, proficiency_level, notes, assessed_date) VALUES (?, ?, ?, ?, ?)',
                [currentEmployeeId, competencyId, proficiency, notes, new Date().toISOString().split('T')[0]]
            );
            alert('Competency assigned successfully!');
        }
        
        closeModal('assignCompetencyModal');
        await loadAllData();
        renderAll();
        
    } catch (error) {
        alert('Error assigning competency: ' + error.message);
    }
}

// Refresh data
async function refreshData() {
    await loadAllData();
    renderAll();
    alert('Data refreshed successfully!');
}

// Export data
function exportData() {
    const data = {
        employees: employees,
        competencies: competencies,
        exportedAt: new Date().toISOString(),
        databaseName: 'assign_competency'
    };
    
    const dataStr = JSON.stringify(data, null, 2);
    const dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(dataStr);
    const fileName = `assign_competency-export-${new Date().toISOString().slice(0,10)}.json`;
    
    const link = document.createElement('a');
    link.setAttribute('href', dataUri);
    link.setAttribute('download', fileName);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}