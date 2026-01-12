// ============================================================================
// CONFIGURATION
// ============================================================================

const STATUS_CONFIG = {
    DRAFT: { text: 'Draft', color: 'bg-gray-100 text-gray-800', icon: 'fa-file' },
    SUBMITTED: { text: 'Submitted', color: 'bg-blue-100 text-blue-800', icon: 'fa-paper-plane' },
    APPROVED: { text: 'Approved', color: 'bg-green-100 text-green-800', icon: 'fa-check-circle' },
    REJECTED: { text: 'Rejected', color: 'bg-red-100 text-red-800', icon: 'fa-times-circle' },
    CANCELLED: { text: 'Cancelled', color: 'bg-gray-100 text-gray-800', icon: 'fa-ban' }
};

const TYPE_CONFIG = {
    LOCATION: { 
        text: 'Location', 
        color: 'bg-blue-100 text-blue-800', 
        icon: 'fa-map-marker-alt'
    },
    BUDGET: { 
        text: 'Budget', 
        color: 'bg-green-100 text-green-800', 
        icon: 'fa-money-bill'
    },
    LOGISTICS: { 
        text: 'Logistics', 
        color: 'bg-purple-100 text-purple-800', 
        icon: 'fa-box'
    }
};

const EXPENSE_CATEGORIES = [
    { value: 'trainer_speaker', label: 'Trainer / Speaker Costs' },
    { value: 'venue_location', label: 'Venue & Location' },
    { value: 'food_beverages', label: 'Food & Beverages' },
    { value: 'equipment_tech', label: 'Equipment & Technology' },
    { value: 'transportation', label: 'Transportation & Travel' },
    { value: 'accommodation', label: 'Accommodation' },
    { value: 'other', label: 'Other Expenses' }
];

let currentRequests = [];
let filteredRequests = [];
let requestCounter = 1;
let budgetItemCount = 1;
let logisticsItemCount = 1;

// ============================================================================
// INITIALIZATION
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    initializeSampleData();
    loadRequests();
    setupResponsiveView();
});

function setupEventListeners() {
    // New Request button dropdown
    const newRequestBtn = document.getElementById('newRequestBtn');
    if (newRequestBtn) {
        newRequestBtn.addEventListener('click', function() {
            const dropdown = document.getElementById('requestTypeDropdown');
            dropdown.classList.toggle('hidden');
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('requestTypeDropdown');
        const button = document.getElementById('newRequestBtn');
        
        if (dropdown && button && !button.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.add('hidden');
        }
    });
    
    // Form submissions
    const locationForm = document.getElementById('locationForm');
    if (locationForm) {
        locationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitLocationRequest('SUBMITTED');
        });
    }
    
    const budgetForm = document.getElementById('budgetForm');
    if (budgetForm) {
        budgetForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitBudgetRequest('SUBMITTED');
        });
        
        // Calculate total budget on input change
        budgetForm.addEventListener('input', function(e) {
            if (e.target.name && e.target.name.includes('budget_items')) {
                calculateTotalBudget();
            }
        });
    }
    
    const logisticsForm = document.getElementById('logisticsForm');
    if (logisticsForm) {
        logisticsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitLogisticsRequest('SUBMITTED');
        });
    }
    
    // Window resize handler for responsive view
    window.addEventListener('resize', setupResponsiveView);
}

function setupResponsiveView() {
    const isMobile = window.innerWidth < 768;
    const tableElement = document.querySelector('table');
    const mobileViewElement = document.getElementById('mobileRequestsView');
    
    if (isMobile) {
        // On mobile, show card view and hide table
        if (tableElement) tableElement.classList.add('hidden');
        if (mobileViewElement) mobileViewElement.classList.remove('hidden');
        renderMobileRequestsView();
    } else {
        // On desktop, show table and hide card view
        if (tableElement) tableElement.classList.remove('hidden');
        if (mobileViewElement) mobileViewElement.classList.add('hidden');
    }
}

function initializeSampleData() {
    // Check if we have existing data in localStorage
    const savedRequests = localStorage.getItem('training_requests');
    if (!savedRequests) {
        // Create some sample requests
        const sampleRequests = [
            {
                request_id: 1,
                request_type: 'LOCATION',
                training_title: 'Annual Company Training',
                purpose: 'Annual skills development training for all employees',
                department: 'HR Department',
                requested_to_department: 'Admin Department',
                request_status: 'APPROVED',
                participants: 50,
                event_date: '2024-01-15',
                created_at: '2024-01-10T09:00:00',
                updated_at: '2024-01-12T14:30:00'
            },
            {
                request_id: 2,
                request_type: 'BUDGET',
                training_title: 'Leadership Seminar',
                purpose: 'Leadership training for managers',
                department: 'HR Department',
                requested_to_department: 'Finance Department',
                request_status: 'SUBMITTED',
                participants: 20,
                event_date: '2024-02-01',
                created_at: '2024-01-20T11:00:00',
                updated_at: '2024-01-20T11:00:00'
            },
            {
                request_id: 3,
                request_type: 'LOGISTICS',
                training_title: 'IT Security Workshop',
                purpose: 'Cybersecurity awareness training',
                department: 'IT Department',
                requested_to_department: 'Logistics Department',
                request_status: 'DRAFT',
                participants: 30,
                event_date: '2024-01-25',
                created_at: '2024-01-18T15:45:00',
                updated_at: '2024-01-18T15:45:00'
            }
        ];
        
        localStorage.setItem('training_requests', JSON.stringify(sampleRequests));
        localStorage.setItem('request_counter', '4'); // Start from 4 for new requests
    }
    
    // Initialize request counter
    const counter = localStorage.getItem('request_counter');
    if (counter) {
        requestCounter = parseInt(counter);
    }
}

// ============================================================================
// REQUEST MANAGEMENT FUNCTIONS
// ============================================================================

function loadRequests() {
    showLoading();
    
    // Simulate loading delay
    setTimeout(() => {
        try {
            const savedRequests = localStorage.getItem('training_requests');
            currentRequests = savedRequests ? JSON.parse(savedRequests) : [];
            filteredRequests = [...currentRequests];
            renderRequestsTable();
            renderMobileRequestsView();
            updateStats();
            showTable();
        } catch (error) {
            console.error('Error loading requests:', error);
            showError('Failed to load requests from storage');
        }
    }, 500);
}

function saveRequest(requestData) {
    try {
        // Get existing requests
        const savedRequests = localStorage.getItem('training_requests');
        let requests = savedRequests ? JSON.parse(savedRequests) : [];
        
        // Add new request
        requests.push(requestData);
        
        // Save back to localStorage
        localStorage.setItem('training_requests', JSON.stringify(requests));
        
        // Update request counter
        requestCounter++;
        localStorage.setItem('request_counter', requestCounter.toString());
        
        return true;
    } catch (error) {
        console.error('Error saving request:', error);
        return false;
    }
}

function updateRequestStatus(requestId, newStatus) {
    try {
        const savedRequests = localStorage.getItem('training_requests');
        let requests = savedRequests ? JSON.parse(savedRequests) : [];
        
        const requestIndex = requests.findIndex(r => r.request_id == requestId);
        if (requestIndex !== -1) {
            requests[requestIndex].request_status = newStatus;
            requests[requestIndex].updated_at = new Date().toISOString();
            
            localStorage.setItem('training_requests', JSON.stringify(requests));
            loadRequests();
            return true;
        }
        return false;
    } catch (error) {
        console.error('Error updating request:', error);
        return false;
    }
}

function deleteRequest(requestId) {
    if (!confirm('Are you sure you want to delete this draft request?')) {
        return;
    }
    
    try {
        const savedRequests = localStorage.getItem('training_requests');
        let requests = savedRequests ? JSON.parse(savedRequests) : [];
        
        // Filter out the request to delete
        requests = requests.filter(r => r.request_id != requestId);
        
        localStorage.setItem('training_requests', JSON.stringify(requests));
        loadRequests();
        return true;
    } catch (error) {
        console.error('Error deleting request:', error);
        return false;
    }
}

// ============================================================================
// LOCATION REQUEST FUNCTIONS
// ============================================================================

async function submitLocationRequest(status) {
    const form = document.getElementById('locationForm');
    const submitBtn = document.getElementById('locationSubmitText');
    const loadingSpinner = document.getElementById('locationLoading');
    
    if (submitBtn) submitBtn.classList.add('hidden');
    if (loadingSpinner) loadingSpinner.classList.remove('hidden');
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    const requestData = {
        request_id: requestCounter,
        request_type: 'LOCATION',
        training_title: data.training_title,
        purpose: data.purpose,
        department: data.department,
        requested_to_department: data.requested_to_department,
        request_status: status,
        participants: parseInt(data.participants) || 0,
        event_date: data.event_date,
        preferred_location: data.preferred_location,
        start_time: data.start_time,
        end_time: data.end_time,
        special_requirements: data.special_requirements || '',
        remarks: data.remarks || '',
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
    };
    
    // Simulate API call delay
    setTimeout(() => {
        if (saveRequest(requestData)) {
            closeModal('location');
            form.reset();
            loadRequests();
            
            if (status === 'SUBMITTED') {
                alert('Location request submitted successfully! Sent for approval.');
            } else {
                alert('Location request saved as draft successfully!');
            }
        } else {
            alert('Error: Failed to save request');
        }
        
        if (submitBtn) submitBtn.classList.remove('hidden');
        if (loadingSpinner) loadingSpinner.classList.add('hidden');
    }, 1000);
}

// ============================================================================
// BUDGET REQUEST FUNCTIONS
// ============================================================================

function initializeBudgetForm() {
    budgetItemCount = 1;
    const container = document.getElementById('budgetItemsContainer');
    if (container) {
        container.innerHTML = getBudgetItemHTML(0);
        calculateTotalBudget();
    }
}

function addBudgetItem() {
    const container = document.getElementById('budgetItemsContainer');
    if (!container) return;
    
    const newItem = getBudgetItemHTML(budgetItemCount);
    container.insertAdjacentHTML('beforeend', newItem);
    budgetItemCount++;
}

function removeBudgetItem(index) {
    const items = document.querySelectorAll('#budgetItemsContainer > div');
    if (items[index]) {
        items[index].remove();
    }
    calculateTotalBudget();
}

function getBudgetItemHTML(index) {
    const categoryOptions = EXPENSE_CATEGORIES.map(cat => 
        `<option value="${cat.value}">${cat.label}</option>`
    ).join('');
    
    return `
        <div class="bg-gray-50 p-3 lg:p-4 rounded-lg mb-3 lg:mb-4">
            <div class="flex justify-between items-center mb-3 lg:mb-4">
                <h5 class="font-medium text-gray-700">Budget Item ${index + 1}</h5>
                ${index > 0 ? `<button type="button" onclick="removeBudgetItem(${index})" class="btn btn-xs btn-error">
                    <i class="fas fa-trash"></i>
                </button>` : ''}
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-3 lg:mb-4">
                <div class="md:col-span-2 lg:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                        Expense Category *
                    </label>
                    <select class="form-input-white" name="budget_items[${index}][category]" required>
                        <option value="">Select Category</option>
                        ${categoryOptions}
                    </select>
                </div>
                
                <div class="md:col-span-2 lg:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                        Description *
                    </label>
                    <input type="text" class="form-input-white" name="budget_items[${index}][description]" 
                           placeholder="e.g., Conference hall rental" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                        Quantity *
                    </label>
                    <input type="number" class="form-input-white" name="budget_items[${index}][quantity]" 
                           min="1" step="1" value="1" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                        Unit Cost (₱) *
                    </label>
                    <input type="number" class="form-input-white" name="budget_items[${index}][unit_cost]" 
                           min="0" step="0.01" placeholder="0.00" required>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                    Remarks
                </label>
                <input type="text" class="form-input-white" name="budget_items[${index}][remarks]" 
                       placeholder="Additional details...">
            </div>
        </div>
    `;
}

function calculateTotalBudget() {
    let total = 0;
    const quantityInputs = document.querySelectorAll('input[name*="budget_items"][name*="quantity"]');
    const costInputs = document.querySelectorAll('input[name*="budget_items"][name*="unit_cost"]');
    
    for (let i = 0; i < quantityInputs.length; i++) {
        const quantity = parseFloat(quantityInputs[i].value) || 0;
        const cost = parseFloat(costInputs[i].value) || 0;
        total += quantity * cost;
    }
    
    const totalElement = document.getElementById('totalBudgetAmount');
    if (totalElement) {
        totalElement.textContent = '₱' + total.toFixed(2);
    }
}

async function submitBudgetRequest(status) {
    const form = document.getElementById('budgetForm');
    const submitBtn = document.getElementById('budgetSubmitText');
    const loadingSpinner = document.getElementById('budgetLoading');
    
    if (submitBtn) submitBtn.classList.add('hidden');
    if (loadingSpinner) loadingSpinner.classList.remove('hidden');
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    // Process budget items
    const budgetItems = [];
    let total = 0;
    
    for (let i = 0; i < budgetItemCount; i++) {
        const category = data[`budget_items[${i}][category]`];
        const description = data[`budget_items[${i}][description]`];
        const quantity = data[`budget_items[${i}][quantity]`];
        const unitCost = data[`budget_items[${i}][unit_cost]`];
        
        if (category && description && quantity && unitCost) {
            const item = {
                category: category,
                description: description,
                quantity: parseFloat(quantity) || 0,
                unit_cost: parseFloat(unitCost) || 0,
                remarks: data[`budget_items[${i}][remarks]`] || ''
            };
            item.total_cost = item.quantity * item.unit_cost;
            total += item.total_cost;
            budgetItems.push(item);
        }
    }
    
    const requestData = {
        request_id: requestCounter,
        request_type: 'BUDGET',
        training_title: data.training_title,
        purpose: data.purpose,
        department: data.department,
        requested_to_department: data.requested_to_department,
        request_status: status,
        participants: parseInt(data.participants) || 0,
        event_date: data.event_date,
        justification: data.justification || '',
        remarks: data.remarks || '',
        total_budget: total,
        budget_items: budgetItems,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
    };
    
    // Simulate API call delay
    setTimeout(() => {
        if (saveRequest(requestData)) {
            closeModal('budget');
            form.reset();
            initializeBudgetForm();
            loadRequests();
            
            if (status === 'SUBMITTED') {
                alert('Budget request submitted successfully! Sent for approval.');
            } else {
                alert('Budget request saved as draft successfully!');
            }
        } else {
            alert('Error: Failed to save request');
        }
        
        if (submitBtn) submitBtn.classList.remove('hidden');
        if (loadingSpinner) loadingSpinner.classList.add('hidden');
    }, 1000);
}

// ============================================================================
// LOGISTICS REQUEST FUNCTIONS
// ============================================================================

function initializeLogisticsForm() {
    logisticsItemCount = 1;
    const container = document.getElementById('logisticsItemsContainer');
    if (container) {
        container.innerHTML = getLogisticsItemHTML(0);
    }
}

function addLogisticsItem() {
    const container = document.getElementById('logisticsItemsContainer');
    if (!container) return;
    
    const newItem = getLogisticsItemHTML(logisticsItemCount);
    container.insertAdjacentHTML('beforeend', newItem);
    logisticsItemCount++;
}

function removeLogisticsItem(index) {
    const items = document.querySelectorAll('#logisticsItemsContainer > div');
    if (items[index]) {
        items[index].remove();
    }
}

function getLogisticsItemHTML(index) {
    return `
        <div class="bg-gray-50 p-3 lg:p-4 rounded-lg mb-3 lg:mb-4">
            <div class="flex justify-between items-center mb-3 lg:mb-4">
                <h5 class="font-medium text-gray-700">Item ${index + 1}</h5>
                ${index > 0 ? `<button type="button" onclick="removeLogisticsItem(${index})" class="btn btn-xs btn-error">
                    <i class="fas fa-trash"></i>
                </button>` : ''}
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-3 lg:mb-4">
                <div class="md:col-span-2 lg:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                        Item Category *
                    </label>
                    <select class="form-input-white" name="logistics_items[${index}][category]" required>
                        <option value="electronics">Electronics</option>
                        <option value="stationery">Stationery</option>
                        <option value="furniture">Furniture</option>
                        <option value="catering">Catering</option>
                        <option value="av_equipment">AV Equipment</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="md:col-span-2 lg:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                        Item Name *
                    </label>
                    <input type="text" class="form-input-white" name="logistics_items[${index}][item_name]" 
                           placeholder="e.g., Laptop, Projector, Chair" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                        Quantity *
                    </label>
                    <input type="number" class="form-input-white" name="logistics_items[${index}][quantity]" 
                           min="1" step="1" value="1" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                        Unit *
                    </label>
                    <select class="form-input-white" name="logistics_items[${index}][unit]" required>
                        <option value="pcs">Pieces</option>
                        <option value="set">Set</option>
                        <option value="box">Box</option>
                        <option value="pack">Pack</option>
                        <option value="unit">Unit</option>
                    </select>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                    Remarks
                </label>
                <input type="text" class="form-input-white" name="logistics_items[${index}][remarks]" 
                       placeholder="Specifications, brand preferences, or special requirements...">
            </div>
        </div>
    `;
}

async function submitLogisticsRequest(status) {
    const form = document.getElementById('logisticsForm');
    const submitBtn = document.getElementById('logisticsSubmitText');
    const loadingSpinner = document.getElementById('logisticsLoading');
    
    if (submitBtn) submitBtn.classList.add('hidden');
    if (loadingSpinner) loadingSpinner.classList.remove('hidden');
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    // Process logistics items
    const logisticsItems = [];
    
    for (let i = 0; i < logisticsItemCount; i++) {
        const category = data[`logistics_items[${i}][category]`];
        const itemName = data[`logistics_items[${i}][item_name]`];
        const quantity = data[`logistics_items[${i}][quantity]`];
        const unit = data[`logistics_items[${i}][unit]`];
        
        if (category && itemName && quantity && unit) {
            const item = {
                category: category,
                item_name: itemName,
                quantity: parseInt(quantity) || 0,
                unit: unit,
                remarks: data[`logistics_items[${i}][remarks]`] || ''
            };
            logisticsItems.push(item);
        }
    }
    
    const requestData = {
        request_id: requestCounter,
        request_type: 'LOGISTICS',
        training_title: data.training_title,
        purpose: data.purpose,
        department: data.department,
        requested_to_department: data.requested_to_department,
        request_status: status,
        participants: parseInt(data.participants) || 0,
        event_date: data.event_date,
        needed_by_date: data.needed_by_date,
        delivery_location: data.delivery_location,
        contact_person: data.contact_person,
        remarks: data.remarks || '',
        logistics_items: logisticsItems,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
    };
    
    // Simulate API call delay
    setTimeout(() => {
        if (saveRequest(requestData)) {
            closeModal('logistics');
            form.reset();
            initializeLogisticsForm();
            loadRequests();
            
            if (status === 'SUBMITTED') {
                alert('Logistics request submitted successfully! Sent for approval.');
            } else {
                alert('Logistics request saved as draft successfully!');
            }
        } else {
            alert('Error: Failed to save request');
        }
        
        if (submitBtn) submitBtn.classList.remove('hidden');
        if (loadingSpinner) loadingSpinner.classList.add('hidden');
    }, 1000);
}

// ============================================================================
// UI FUNCTIONS
// ============================================================================

function openRequestModal(type) {
    document.getElementById('requestTypeDropdown').classList.add('hidden');
    
    // Open modal
    document.getElementById(type.toLowerCase() + 'Modal').showModal();
    
    // Initialize form if needed
    if (type === 'budget') {
        initializeBudgetForm();
    }
    if (type === 'logistics') {
        initializeLogisticsForm();
    }
}

function closeModal(type) {
    document.getElementById(type.toLowerCase() + 'Modal').close();
}

function renderMobileRequestsView() {
    const container = document.getElementById('mobileRequestsView');
    
    if (!container || filteredRequests.length === 0) {
        return;
    }
    
    container.innerHTML = filteredRequests.map(request => {
        const requestType = request.request_type || 'LOCATION';
        const requestStatus = request.request_status || 'DRAFT';
        
        return `
        <div class="request-card p-4">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <div class="font-mono text-sm font-bold text-gray-800">REQ-${request.request_id.toString().padStart(5, '0')}</div>
                    <span class="type-badge ${TYPE_CONFIG[requestType]?.color || 'bg-gray-100 text-gray-800'} mt-1">
                        <i class="fas ${TYPE_CONFIG[requestType]?.icon || 'fa-file'} mr-1"></i>
                        ${TYPE_CONFIG[requestType]?.text || requestType}
                    </span>
                </div>
                <span class="status-badge ${STATUS_CONFIG[requestStatus]?.color || 'bg-gray-100 text-gray-800'}">
                    <i class="fas ${STATUS_CONFIG[requestStatus]?.icon || 'fa-file'} mr-1"></i>
                    ${STATUS_CONFIG[requestStatus]?.text || requestStatus}
                </span>
            </div>
            
            <div class="mb-3">
                <div class="font-medium text-gray-800 mb-1">${request.training_title || 'No Title'}</div>
                <div class="text-sm text-gray-600">${request.purpose || 'No purpose specified'}</div>
            </div>
            
            <div class="grid grid-cols-2 gap-2 mb-3 text-sm">
                <div>
                    <div class="text-gray-500">Department</div>
                    <div class="font-medium">${request.department || 'Unknown'}</div>
                </div>
                <div>
                    <div class="text-gray-500">To</div>
                    <div class="font-medium">${request.requested_to_department || 'Unknown'}</div>
                </div>
            </div>
            
            <div class="flex gap-2 action-buttons">
                <button class="btn btn-xs btn-outline flex-1" onclick="viewRequest('${request.request_id}')">
                    <i class="fas fa-eye"></i> View
                </button>
                ${requestStatus === 'DRAFT' ? `
                    <button class="btn btn-xs btn-outline flex-1" onclick="editRequest('${request.request_id}')">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                ` : ''}
                ${requestStatus === 'SUBMITTED' ? `
                    <button class="btn btn-xs btn-success flex-1" onclick="updateRequestStatus('${request.request_id}', 'APPROVED')">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button class="btn btn-xs btn-error flex-1" onclick="updateRequestStatus('${request.request_id}', 'REJECTED')">
                        <i class="fas fa-times"></i> Reject
                    </button>
                ` : ''}
                ${requestStatus === 'DRAFT' ? `
                    <button class="btn btn-xs btn-error flex-1" onclick="deleteRequest('${request.request_id}')">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                ` : ''}
            </div>
        </div>
    `}).join('');
}

function searchRequests() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    filterRequests();
    
    if (searchTerm) {
        filteredRequests = filteredRequests.filter(request =>
            (request.training_title || '').toLowerCase().includes(searchTerm) ||
            (request.purpose || '').toLowerCase().includes(searchTerm) ||
            (request.department || '').toLowerCase().includes(searchTerm)
        );
    }
    
    renderRequestsTable();
    renderMobileRequestsView();
    updateStats();
}

function filterRequests() {
    const typeFilter = document.getElementById('typeFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const departmentFilter = document.getElementById('departmentFilter').value;
    
    filteredRequests = currentRequests.filter(request => {
        const matchesType = !typeFilter || (request.request_type || '').toUpperCase() === typeFilter.toUpperCase();
        const matchesStatus = !statusFilter || (request.request_status || '').toUpperCase() === statusFilter.toUpperCase();
        const matchesDepartment = !departmentFilter || 
            (request.department && 
             request.department.toLowerCase().includes(departmentFilter.toLowerCase()));
        return matchesType && matchesStatus && matchesDepartment;
    });
    
    renderRequestsTable();
    renderMobileRequestsView();
    updateStats();
}

function renderRequestsTable() {
    const tbody = document.getElementById('requestsTableBody');
    
    if (!tbody) return;
    
    if (filteredRequests.length === 0) {
        showEmptyState();
        return;
    }
    
    tbody.innerHTML = filteredRequests.map(request => {
        const requestType = request.request_type || 'LOCATION';
        const requestStatus = request.request_status || 'DRAFT';
        
        return `
        <tr>
            <td class="table-cell-mobile">
                <div class="font-mono text-sm mobile-truncate">REQ-${request.request_id.toString().padStart(5, '0')}</div>
            </td>
            <td class="table-cell-mobile hidden lg:table-cell">
                <span class="type-badge ${TYPE_CONFIG[requestType]?.color || 'bg-gray-100 text-gray-800'}">
                    <i class="fas ${TYPE_CONFIG[requestType]?.icon || 'fa-file'} mr-1"></i>
                    ${TYPE_CONFIG[requestType]?.text || requestType}
                </span>
            </td>
            <td class="table-cell-mobile">
                <div class="font-medium mobile-truncate">${request.training_title || 'No Title'}</div>
                <div class="text-xs text-gray-500 mt-1 mobile-hidden lg:block">${request.purpose || 'No purpose specified'}</div>
            </td>
            <td class="table-cell-mobile hidden md:table-cell">
                <div>${request.department || 'Unknown'}</div>
                <div class="text-xs text-gray-500">To: ${request.requested_to_department || 'Unknown'}</div>
            </td>
            <td class="table-cell-mobile hidden lg:table-cell">${formatDate(request.created_at)}</td>
            <td class="table-cell-mobile">
                <span class="status-badge ${STATUS_CONFIG[requestStatus]?.color || 'bg-gray-100 text-gray-800'}">
                    <i class="fas ${STATUS_CONFIG[requestStatus]?.icon || 'fa-file'} mr-1"></i>
                    ${STATUS_CONFIG[requestStatus]?.text || requestStatus}
                </span>
            </td>
            <td class="table-cell-mobile">
                <div class="flex gap-1 lg:gap-2 action-buttons">
                    <button class="btn btn-xs btn-outline" onclick="viewRequest('${request.request_id}')" title="View">
                        <i class="fas fa-eye"></i>
                        <span class="hidden lg:inline ml-1">View</span>
                    </button>
                    ${requestStatus === 'DRAFT' ? `
                        <button class="btn btn-xs btn-outline" onclick="editRequest('${request.request_id}')" title="Edit">
                            <i class="fas fa-edit"></i>
                            <span class="hidden lg:inline ml-1">Edit</span>
                        </button>
                    ` : ''}
                    ${requestStatus === 'SUBMITTED' ? `
                        <button class="btn btn-xs btn-success" onclick="updateRequestStatus('${request.request_id}', 'APPROVED')" title="Approve">
                            <i class="fas fa-check"></i>
                            <span class="hidden lg:inline ml-1">Approve</span>
                        </button>
                        <button class="btn btn-xs btn-error" onclick="updateRequestStatus('${request.request_id}', 'REJECTED')" title="Reject">
                            <i class="fas fa-times"></i>
                            <span class="hidden lg:inline ml-1">Reject</span>
                        </button>
                    ` : ''}
                    ${requestStatus === 'DRAFT' ? `
                        <button class="btn btn-xs btn-error" onclick="deleteRequest('${request.request_id}')" title="Delete">
                            <i class="fas fa-trash"></i>
                            <span class="hidden lg:inline ml-1">Delete</span>
                        </button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `}).join('');
    
    showTable();
}

function updateStats() {
    const total = currentRequests.length;
    const draft = currentRequests.filter(r => r.request_status === 'DRAFT').length;
    const submitted = currentRequests.filter(r => r.request_status === 'SUBMITTED').length;
    const approved = currentRequests.filter(r => r.request_status === 'APPROVED').length;
    
    const totalEl = document.getElementById('totalRequests');
    const draftEl = document.getElementById('draftRequests');
    const pendingEl = document.getElementById('pendingRequests');
    const approvedEl = document.getElementById('approvedRequests');
    
    if (totalEl) totalEl.textContent = total;
    if (draftEl) draftEl.textContent = draft;
    if (pendingEl) pendingEl.textContent = submitted;
    if (approvedEl) approvedEl.textContent = approved;
}

function viewRequest(requestId) {
    const request = currentRequests.find(r => r.request_id == requestId);
    if (request) {
        let details = `Request Details:\n\n` +
              `ID: REQ-${request.request_id.toString().padStart(5, '0')}\n` +
              `Type: ${request.request_type || 'N/A'}\n` +
              `Training: ${request.training_title || 'N/A'}\n` +
              `Purpose: ${request.purpose || 'N/A'}\n` +
              `Status: ${request.request_status || 'N/A'}\n` +
              `Department: ${request.department || 'N/A'}\n` +
              `Requested To: ${request.requested_to_department || 'N/A'}\n` +
              `Participants: ${request.participants || 'N/A'}\n` +
              `Event Date: ${request.event_date || 'N/A'}\n` +
              `Created: ${formatDate(request.created_at)}\n` +
              `Updated: ${formatDate(request.updated_at)}\n`;
        
        // Add type-specific details
        if (request.request_type === 'LOCATION' && request.preferred_location) {
            details += `\nLocation Details:\n` +
                       `Location: ${request.preferred_location}\n` +
                       `Time: ${request.start_time || 'N/A'} - ${request.end_time || 'N/A'}`;
        } else if (request.request_type === 'BUDGET' && request.total_budget) {
            details += `\nBudget Details:\n` +
                       `Total Budget: ₱${parseFloat(request.total_budget || 0).toFixed(2)}`;
        } else if (request.request_type === 'LOGISTICS' && request.delivery_location) {
            details += `\nLogistics Details:\n` +
                       `Delivery: ${request.delivery_location}\n` +
                       `Contact: ${request.contact_person || 'N/A'}`;
        }
        
        alert(details);
    }
}

function editRequest(requestId) {
    const request = currentRequests.find(r => r.request_id == requestId);
    if (request) {
        alert(`Editing request ${requestId} - This would open an edit form in a real application.\n\n` +
              `Note: Only DRAFT requests can be edited.`);
    }
}

function saveAsDraft(type) {
    if (type === 'location') {
        submitLocationRequest('DRAFT');
    } else if (type === 'budget') {
        submitBudgetRequest('DRAFT');
    } else if (type === 'logistics') {
        submitLogisticsRequest('DRAFT');
    }
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function showLoading() {
    const loadingState = document.getElementById('loadingState');
    const emptyState = document.getElementById('emptyState');
    const errorState = document.getElementById('errorState');
    
    if (loadingState) loadingState.classList.remove('hidden');
    if (emptyState) emptyState.classList.add('hidden');
    if (errorState) errorState.classList.add('hidden');
    
    const tbody = document.getElementById('requestsTableBody');
    const mobileView = document.getElementById('mobileRequestsView');
    
    if (tbody) tbody.innerHTML = '';
    if (mobileView) mobileView.innerHTML = '';
}

function showTable() {
    const loadingState = document.getElementById('loadingState');
    const emptyState = document.getElementById('emptyState');
    const errorState = document.getElementById('errorState');
    
    if (loadingState) loadingState.classList.add('hidden');
    if (emptyState) emptyState.classList.add('hidden');
    if (errorState) errorState.classList.add('hidden');
}

function showEmptyState() {
    const loadingState = document.getElementById('loadingState');
    const emptyState = document.getElementById('emptyState');
    const errorState = document.getElementById('errorState');
    
    if (loadingState) loadingState.classList.add('hidden');
    if (emptyState) emptyState.classList.remove('hidden');
    if (errorState) errorState.classList.add('hidden');
    
    const tbody = document.getElementById('requestsTableBody');
    const mobileView = document.getElementById('mobileRequestsView');
    
    if (tbody) tbody.innerHTML = '';
    if (mobileView) mobileView.innerHTML = '';
}

function showError(message) {
    const loadingState = document.getElementById('loadingState');
    const emptyState = document.getElementById('emptyState');
    const errorState = document.getElementById('errorState');
    const errorMessage = document.getElementById('errorMessage');
    
    if (loadingState) loadingState.classList.add('hidden');
    if (emptyState) emptyState.classList.add('hidden');
    if (errorState) errorState.classList.remove('hidden');
    if (errorMessage) errorMessage.textContent = message;
    
    const tbody = document.getElementById('requestsTableBody');
    const mobileView = document.getElementById('mobileRequestsView');
    
    if (tbody) tbody.innerHTML = '';
    if (mobileView) mobileView.innerHTML = '';
}

// ============================================================================
// GLOBAL FUNCTION EXPORTS
// ============================================================================

window.openRequestModal = openRequestModal;
window.closeModal = closeModal;
window.saveAsDraft = saveAsDraft;
window.searchRequests = searchRequests;
window.filterRequests = filterRequests;
window.viewRequest = viewRequest;
window.editRequest = editRequest;
window.updateRequestStatus = updateRequestStatus;
window.deleteRequest = deleteRequest;
window.loadRequests = loadRequests;
window.addBudgetItem = addBudgetItem;
window.removeBudgetItem = removeBudgetItem;
window.calculateTotalBudget = calculateTotalBudget;
window.addLogisticsItem = addLogisticsItem;
window.removeLogisticsItem = removeLogisticsItem;
window.initializeBudgetForm = initializeBudgetForm;
window.initializeLogisticsForm = initializeLogisticsForm;