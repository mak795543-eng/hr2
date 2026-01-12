// ============================================================================
// LOGISTICS REQUEST FUNCTIONS
// ============================================================================

let logisticsItemCount = 1;

// Initialize logistics form when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize logistics form
    const logisticsForm = document.getElementById('logisticsForm');
    if (logisticsForm) {
        logisticsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitLogisticsRequest('SUBMITTED');
        });
        
        // Initialize first logistics item
        initializeLogisticsForm();
    }
});

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
    // Get logistics items from main.js
    const logisticsItems = window.logisticsItems || [];
    
    const itemOptions = logisticsItems.map(item => 
        `<option value="${item.item_id}">${item.item_name} (${item.item_category}, Available: ${item.available_quantity})</option>`
    ).join('');
    
    return `
        <div class="bg-gray-50 p-3 lg:p-4 rounded-lg mb-3 lg:mb-4">
            <div class="flex justify-between items-center mb-3 lg:mb-4">
                <h5 class="font-medium text-gray-700">Item ${index + 1}</h5>
                ${index > 0 ? `<button type="button" onclick="removeLogisticsItem(${index})" class="btn btn-xs btn-error">
                    <i class="fas fa-trash"></i>
                </button>` : ''}
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-3 lg:mb-4">
                <div class="md:col-span-2 lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                        Item *
                    </label>
                    <select class="form-input-white" name="logistics_items[${index}][item_id]" required>
                        <option value="">Select Item</option>
                        ${itemOptions}
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                        Quantity *
                    </label>
                    <input type="number" class="form-input-white" name="logistics_items[${index}][quantity]" 
                           min="1" step="1" value="1" required>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 lg:mb-2">
                    Remarks
                </label>
                <input type="text" class="form-input-white" name="logistics_items[${index}][remarks]" 
                       placeholder="Special requirements or notes...">
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
        const itemId = data[`logistics_items[${i}][item_id]`];
        const quantity = data[`logistics_items[${i}][quantity]`];
        
        if (itemId && quantity) {
            const item = {
                item_id: parseInt(itemId),
                quantity: parseInt(quantity) || 0,
                remarks: data[`logistics_items[${i}][remarks]`] || ''
            };
            logisticsItems.push(item);
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
        event_location: data.event_location,
        borrow_date: data.borrow_date,
        return_date: data.return_date,
        participants: parseInt(data.participants) || 0,
        logistics_items: logisticsItems
    };
    
    try {
        const response = await makeApiRequest(
            window.API_CONFIG.endpoints.logisticsRequest,
            'POST',
            requestData
        );
        
        if (response.success) {
            closeModal('logistics');
            form.reset();
            loadRequests();
            
            if (status === 'SUBMITTED') {
                alert('Logistics request submitted successfully! Sent for approval.');
            } else {
                alert('Logistics request saved as draft successfully!');
            }
        } else {
            alert('Error: ' + (response.error || 'Failed to submit request'));
        }
    } catch (error) {
        console.error('Error submitting logistics request:', error);
        alert('Network error. Please try again.');
    } finally {
        if (submitBtn) submitBtn.classList.remove('hidden');
        if (loadingSpinner) loadingSpinner.classList.add('hidden');
    }
}

// Save logistics as draft
function saveLogisticsAsDraft() {
    submitLogisticsRequest('DRAFT');
}

// ============================================================================
// GLOBAL FUNCTION EXPORTS
// ============================================================================

window.addLogisticsItem = addLogisticsItem;
window.removeLogisticsItem = removeLogisticsItem;
window.saveLogisticsAsDraft = saveLogisticsAsDraft;
window.initializeLogisticsForm = initializeLogisticsForm;