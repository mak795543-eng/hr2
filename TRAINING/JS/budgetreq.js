// ============================================================================
// BUDGET REQUEST FUNCTIONS
// ============================================================================

let budgetItemCount = 1;
const EXPENSE_CATEGORIES = [
    { value: 'trainer_speaker', label: 'Trainer / Speaker Costs' },
    { value: 'venue_location', label: 'Venue & Location' },
    { value: 'food_beverages', label: 'Food & Beverages' },
    { value: 'equipment_tech', label: 'Equipment & Technology' },
    { value: 'transportation', label: 'Transportation & Travel' },
    { value: 'accommodation', label: 'Accommodation' },
    { value: 'other', label: 'Other Expenses' }
];

// Initialize budget form when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize budget form
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
        
        // Initialize first budget item
        initializeBudgetForm();
        calculateTotalBudget();
    }
});

function initializeBudgetForm() {
    budgetItemCount = 1;
    const container = document.getElementById('budgetItemsContainer');
    if (container) {
        container.innerHTML = getBudgetItemHTML(0);
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
    
    // Find department IDs
    const departmentId = getDepartmentId(data.department);
    const requestedToDeptId = getDepartmentId(data.requested_to_department);
    
    const requestData = {
        reference_type: data.reference_type,
        reference_id: data.reference_id,
        department: data.department,
        department_id: departmentId,
        requested_to_department_id: requestedToDeptId,
        status: status,
        purpose: data.purpose || data.training_title,
        remarks: data.remarks || '',
        justification: data.justification || '',
        event_date: data.event_date,
        participants: parseInt(data.participants) || 0,
        estimated_amount: total,
        budget_items: budgetItems
    };
    
    try {
        const response = await makeApiRequest(
            window.API_CONFIG.endpoints.budgetRequest,
            'POST',
            requestData
        );
        
        if (response.success) {
            closeModal('budget');
            form.reset();
            loadRequests();
            
            if (status === 'SUBMITTED') {
                alert('Budget request submitted successfully! Sent for approval.');
            } else {
                alert('Budget request saved as draft successfully!');
            }
        } else {
            alert('Error: ' + (response.error || 'Failed to submit request'));
        }
    } catch (error) {
        console.error('Error submitting budget request:', error);
        alert('Network error. Please try again.');
    } finally {
        if (submitBtn) submitBtn.classList.remove('hidden');
        if (loadingSpinner) loadingSpinner.classList.add('hidden');
    }
}

// Save budget as draft
function saveBudgetAsDraft() {
    submitBudgetRequest('DRAFT');
}
const test=true;
// ============================================================================
// GLOBAL FUNCTION EXPORTS
// ============================================================================

window.addBudgetItem = addBudgetItem;
window.removeBudgetItem = removeBudgetItem;
window.calculateTotalBudget = calculateTotalBudget;
window.saveBudgetAsDraft = saveBudgetAsDraft;
window.initializeBudgetForm = initializeBudgetForm;