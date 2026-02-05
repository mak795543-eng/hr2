<!-- View Document Modal -->
<dialog id="view_document_modal" class="modal">
    <div class="modal-box max-w-6xl p-0">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-start">
                <h3 class="font-bold text-lg text-gray-800" id="document_title">Module Title</h3>
                <button class="btn btn-sm btn-circle" onclick="view_document_modal.close()">
                    ✕
                </button>
            </div>
        </div>
        
        <div class="p-6 max-h-[70vh] overflow-y-auto">
            <!-- Module Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="space-y-1">
                    <p class="text-sm text-gray-500">Department</p>
                    <p class="font-medium text-gray-800" id="document_department">Department</p>
                </div>
                <div class="space-y-1">
                    <p class="text-sm text-gray-500">Roles</p>
                    <p class="font-medium text-gray-800" id="document_roles">Roles</p>
                </div>
                <div class="space-y-1">
                    <p class="text-sm text-gray-500">Topic</p>
                    <p class="font-medium text-gray-800" id="document_topic">Topic</p>
                </div>
            </div>
            
            <!-- Module Content -->
            <div class="mb-6">
                <p class="text-sm text-gray-500 mb-2">Module Content</p>
                <div class="content-preview">
                    <div id="document_content" class="prose max-w-none text-gray-700">
                        <!-- Content will be inserted here -->
                    </div>
                </div>
            </div>
            
            <!-- Action Section -->
            <div class="action-section">
                <p class="text-sm text-gray-500 mb-3">Module Actions</p>
                <div class="action-buttons">
                    <button class="action-btn approve" onclick="approveModule()">
                        <i class="fas fa-check mr-2"></i>Approve
                    </button>
                    <button class="action-btn reject" onclick="rejectModule()">
                        <i class="fas fa-times mr-2"></i>Reject
                    </button>
                    <button class="action-btn compliance" onclick="forCompliance()">
                        <i class="fas fa-exclamation-triangle mr-2"></i>For Compliance
                    </button>
                    <button class="action-btn hold" onclick="holdModule()">
                        <i class="fas fa-pause mr-2"></i>Hold
                    </button>
                </div>
            </div>
        </div>
    </div>
</dialog>

<!-- View Examination Modal -->
<dialog id="view_exam_modal" class="modal">
    <div class="modal-box max-w-6xl p-0">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-start">
                <h3 class="font-bold text-lg text-gray-800" id="exam_title">Examination Title</h3>
                <button class="btn btn-sm btn-circle" onclick="view_exam_modal.close()">
                    ✕
                </button>
            </div>
        </div>
        
        <div class="p-6 max-h-[70vh] overflow-y-auto">
            <!-- Exam Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="space-y-1">
                    <p class="text-sm text-gray-500">Department</p>
                    <p class="font-medium text-gray-800" id="exam_department">Department</p>
                </div>
                <div class="space-y-1">
                    <p class="text-sm text-gray-500">Roles</p>
                    <p class="font-medium text-gray-800" id="exam_roles">Roles</p>
                </div>
                <div class="space-y-1">
                    <p class="text-sm text-gray-500">Duration</p>
                    <p class="font-medium text-gray-800" id="exam_duration">Duration</p>
                </div>
            </div>
            
            <!-- Exam Questions -->
            <div class="mb-6">
                <p class="text-sm text-gray-500 mb-2">Examination Questions</p>
                <div id="exam_questions" class="space-y-4">
                    <!-- Questions will be inserted here -->
                </div>
            </div>
            
            <!-- Action Section -->
            <div class="action-section">
                <p class="text-sm text-gray-500 mb-3">Examination Actions</p>
                <div class="action-buttons">
                    <button class="action-btn approve" onclick="approveExam()">
                        <i class="fas fa-check mr-2"></i>Approve
                    </button>
                    <button class="action-btn reject" onclick="rejectExam()">
                        <i class="fas fa-times mr-2"></i>Reject
                    </button>
                    <button class="action-btn compliance" onclick="forExamCompliance()">
                        <i class="fas fa-exclamation-triangle mr-2"></i>For Compliance
                    </button>
                    <button class="action-btn hold" onclick="holdExam()">
                        <i class="fas fa-pause mr-2"></i>Hold
                    </button>
                </div>
            </div>
        </div>
    </div>
</dialog>

<!-- Reject Module Modal -->
<dialog id="reject_modal" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg text-gray-800 mb-4">Reject Module</h3>
        <div class="mb-4">
            <p class="text-gray-600 mb-3">Are you sure you want to reject <span id="reject_module_name" class="font-semibold">this module</span>?</p>
            <div class="form-control">
                <label class="label">
                    <span class="label-text text-gray-700">Reason for Rejection (Optional)</span>
                </label>
                <textarea class="textarea textarea-bordered w-full h-24 border-gray-300" id="reject_reason" placeholder="Enter reason for rejection..."></textarea>
            </div>
        </div>
        <div class="modal-action">
            <button class="btn btn-ghost" onclick="reject_modal.close()">Cancel</button>
            <button class="btn btn-error" onclick="confirmReject()">Reject Module</button>
        </div>
    </div>
</dialog>

<!-- For Compliance Module Modal -->
<dialog id="compliance_modal" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg text-gray-800 mb-4">Mark for Compliance</h3>
        <div class="mb-4">
            <p class="text-gray-600 mb-3">Mark <span id="compliance_module_name" class="font-semibold">this module</span> for compliance updates?</p>
            <div class="form-control">
                <label class="label">
                    <span class="label-text text-gray-700">Compliance Requirements</span>
                </label>
                <textarea class="textarea textarea-bordered w-full h-32 border-gray-300" id="compliance_requirements" placeholder="Specify compliance requirements..."></textarea>
            </div>
        </div>
        <div class="modal-action">
            <button class="btn btn-ghost" onclick="compliance_modal.close()">Cancel</button>
            <button class="btn btn-warning" onclick="confirmCompliance()">Mark for Compliance</button>
        </div>
    </div>
</dialog>

<!-- Hold Module Modal -->
<dialog id="hold_modal" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg text-gray-800 mb-4">Hold Module</h3>
        <div class="mb-4">
            <p class="text-gray-600 mb-3">Place <span id="hold_module_name" class="font-semibold">this module</span> on hold?</p>
            <div class="form-control">
                <label class="label">
                    <span class="label-text text-gray-700">Reason for Hold (Optional)</span>
                </label>
                <textarea class="textarea textarea-bordered w-full h-24 border-gray-300" id="hold_reason" placeholder="Enter reason for placing on hold..."></textarea>
            </div>
        </div>
        <div class="modal-action">
            <button class="btn btn-ghost" onclick="hold_modal.close()">Cancel</button>
            <button class="btn btn-secondary" onclick="confirmHold()">Place on Hold</button>
        </div>
    </div>
</dialog>

<!-- Reject Exam Modal -->
<dialog id="reject_exam_modal" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg text-gray-800 mb-4">Reject Examination</h3>
        <div class="mb-4">
            <p class="text-gray-600 mb-3">Are you sure you want to reject <span id="reject_exam_name" class="font-semibold">this examination</span>?</p>
            <div class="form-control">
                <label class="label">
                    <span class="label-text text-gray-700">Reason for Rejection (Optional)</span>
                </label>
                <textarea class="textarea textarea-bordered w-full h-24 border-gray-300" id="reject_exam_reason" placeholder="Enter reason for rejection..."></textarea>
            </div>
        </div>
        <div class="modal-action">
            <button class="btn btn-ghost" onclick="reject_exam_modal.close()">Cancel</button>
            <button class="btn btn-error" onclick="confirmExamReject()">Reject Examination</button>
        </div>
    </div>
</dialog>

<!-- For Exam Compliance Modal -->
<dialog id="compliance_exam_modal" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg text-gray-800 mb-4">Mark for Compliance</h3>
        <div class="mb-4">
            <p class="text-gray-600 mb-3">Mark <span id="compliance_exam_name" class="font-semibold">this examination</span> for compliance updates?</p>
            <div class="form-control">
                <label class="label">
                    <span class="label-text text-gray-700">Compliance Requirements</span>
                </label>
                <textarea class="textarea textarea-bordered w-full h-32 border-gray-300" id="compliance_exam_requirements" placeholder="Specify compliance requirements..."></textarea>
            </div>
        </div>
        <div class="modal-action">
            <button class="btn btn-ghost" onclick="compliance_exam_modal.close()">Cancel</button>
            <button class="btn btn-warning" onclick="confirmExamCompliance()">Mark for Compliance</button>
        </div>
    </div>
</dialog>

<!-- Hold Exam Modal -->
<dialog id="hold_exam_modal" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg text-gray-800 mb-4">Hold Examination</h3>
        <div class="mb-4">
            <p class="text-gray-600 mb-3">Place <span id="hold_exam_name" class="font-semibold">this examination</span> on hold?</p>
            <div class="form-control">
                <label class="label">
                    <span class="label-text text-gray-700">Reason for Hold (Optional)</span>
                </label>
                <textarea class="textarea textarea-bordered w-full h-24 border-gray-300" id="hold_exam_reason" placeholder="Enter reason for placing on hold..."></textarea>
            </div>
        </div>
        <div class="modal-action">
            <button class="btn btn-ghost" onclick="hold_exam_modal.close()">Cancel</button>
            <button class="btn btn-secondary" onclick="confirmExamHold()">Place on Hold</button>
        </div>
    </div>
</dialog>

<script>
// Global variables
let currentModuleId = '';
let currentModuleName = '';
let currentModuleDepartment = '';
let currentModuleRoles = '';
let currentModuleTopic = '';

let currentExamId = '';
let currentExamName = '';
let currentExamDepartment = '';
let currentExamRoles = '';
let currentExamDuration = '';
let currentExamQuestionCount = '';

// Module Functions
function viewDocument(moduleId, moduleName, department, roles, topic, content) {
    console.log('View document called:', moduleId, moduleName);
    
    currentModuleId = moduleId;
    currentModuleName = moduleName;
    currentModuleDepartment = department;
    currentModuleRoles = roles;
    currentModuleTopic = topic;
    
    document.getElementById('document_title').textContent = moduleName;
    document.getElementById('document_department').textContent = department.replace(/-/g, ' ');
    document.getElementById('document_roles').textContent = roles;
    document.getElementById('document_topic').textContent = topic;
    document.getElementById('document_content').innerHTML = content;
    
    const modal = document.getElementById('view_document_modal');
    if (modal) {
        modal.showModal();
    } else {
        console.error('Modal not found');
    }
}

function approveModule() {
    if (confirm(`Approve "${currentModuleName}"? This module will be ready for posting.`)) {
        updateModuleStatus(currentModuleId, 'approved', `Module "${currentModuleName}" has been approved.`);
    }
}

function rejectModule() {
    document.getElementById('reject_module_name').textContent = currentModuleName;
    document.getElementById('reject_reason').value = '';
    document.getElementById('view_document_modal').close();
    document.getElementById('reject_modal').showModal();
}

function confirmReject() {
    const reason = document.getElementById('reject_reason').value;
    updateModuleStatus(currentModuleId, 'rejected', `Module "${currentModuleName}" has been rejected.`, reason);
    document.getElementById('reject_modal').close();
}

function forCompliance() {
    document.getElementById('compliance_module_name').textContent = currentModuleName;
    document.getElementById('compliance_requirements').value = '';
    document.getElementById('view_document_modal').close();
    document.getElementById('compliance_modal').showModal();
}

function confirmCompliance() {
    const requirements = document.getElementById('compliance_requirements').value;
    updateModuleStatus(currentModuleId, 'compliance', `Module "${currentModuleName}" has been marked for compliance.`, requirements);
    document.getElementById('compliance_modal').close();
}

function holdModule() {
    document.getElementById('hold_module_name').textContent = currentModuleName;
    document.getElementById('hold_reason').value = '';
    document.getElementById('view_document_modal').close();
    document.getElementById('hold_modal').showModal();
}

function confirmHold() {
    const reason = document.getElementById('hold_reason').value;
    updateModuleStatus(currentModuleId, 'hold', `Module "${currentModuleName}" has been placed on hold.`, reason);
    document.getElementById('hold_modal').close();
}

// Examination Functions
function viewExam(examId, examName, department, roles, duration, questionCount) {
    console.log('View exam called:', examId, examName);
    
    currentExamId = examId;
    currentExamName = examName;
    currentExamDepartment = department;
    currentExamRoles = roles;
    currentExamDuration = duration;
    currentExamQuestionCount = questionCount;
    
    document.getElementById('exam_title').textContent = examName;
    document.getElementById('exam_department').textContent = department.replace(/-/g, ' ');
    document.getElementById('exam_roles').textContent = roles;
    document.getElementById('exam_duration').textContent = duration + ' minutes';
    
    // Load exam questions
    loadExamQuestions(examId);
    
    const modal = document.getElementById('view_exam_modal');
    if (modal) {
        modal.showModal();
    } else {
        console.error('Exam modal not found');
    }
}

function loadExamQuestions(examId) {
    // For now, use dummy data. Replace with actual AJAX call if needed.
    const questionsContainer = document.getElementById('exam_questions');
    questionsContainer.innerHTML = `
        <div class="question-item">
            <p class="font-medium mb-2">1. What is the capital of France?</p>
            <div class="space-y-1 ml-4">
                <div class="flex items-center">
                    <input type="radio" class="mr-2" checked disabled>
                    <span class="correct-answer px-2 py-1 rounded">Paris</span>
                </div>
                <div class="flex items-center">
                    <input type="radio" class="mr-2" disabled>
                    <span>London</span>
                </div>
                <div class="flex items-center">
                    <input type="radio" class="mr-2" disabled>
                    <span>Berlin</span>
                </div>
                <div class="flex items-center">
                    <input type="radio" class="mr-2" disabled>
                    <span>Madrid</span>
                </div>
            </div>
        </div>
        <div class="question-item">
            <p class="font-medium mb-2">2. What is 2 + 2?</p>
            <div class="space-y-1 ml-4">
                <div class="flex items-center">
                    <input type="radio" class="mr-2" disabled>
                    <span>3</span>
                </div>
                <div class="flex items-center">
                    <input type="radio" class="mr-2" checked disabled>
                    <span class="correct-answer px-2 py-1 rounded">4</span>
                </div>
                <div class="flex items-center">
                    <input type="radio" class="mr-2" disabled>
                    <span>5</span>
                </div>
                <div class="flex items-center">
                    <input type="radio" class="mr-2" disabled>
                    <span>6</span>
                </div>
            </div>
        </div>
    `;
}

function approveExam() {
    if (confirm(`Approve "${currentExamName}"? This examination will be ready for posting.`)) {
        updateExamStatus(currentExamId, 'approved', `Examination "${currentExamName}" has been approved.`);
    }
}

function rejectExam() {
    document.getElementById('reject_exam_name').textContent = currentExamName;
    document.getElementById('reject_exam_reason').value = '';
    document.getElementById('view_exam_modal').close();
    document.getElementById('reject_exam_modal').showModal();
}

function confirmExamReject() {
    const reason = document.getElementById('reject_exam_reason').value;
    updateExamStatus(currentExamId, 'rejected', `Examination "${currentExamName}" has been rejected.`, reason);
    document.getElementById('reject_exam_modal').close();
}

function forExamCompliance() {
    document.getElementById('compliance_exam_name').textContent = currentExamName;
    document.getElementById('compliance_exam_requirements').value = '';
    document.getElementById('view_exam_modal').close();
    document.getElementById('compliance_exam_modal').showModal();
}

function confirmExamCompliance() {
    const requirements = document.getElementById('compliance_exam_requirements').value;
    updateExamStatus(currentExamId, 'compliance', `Examination "${currentExamName}" has been marked for compliance.`, requirements);
    document.getElementById('compliance_exam_modal').close();
}

function holdExam() {
    document.getElementById('hold_exam_name').textContent = currentExamName;
    document.getElementById('hold_exam_reason').value = '';
    document.getElementById('view_exam_modal').close();
    document.getElementById('hold_exam_modal').showModal();
}

function confirmExamHold() {
    const reason = document.getElementById('hold_exam_reason').value;
    updateExamStatus(currentExamId, 'hold', `Examination "${currentExamName}" has been placed on hold.`, reason);
    document.getElementById('hold_exam_modal').close();
}

// AJAX Functions
function updateModuleStatus(moduleId, newStatus, successMessage, remarks = '') {
    const formData = new FormData();
    formData.append('module_id', moduleId);
    formData.append('new_status', newStatus);
    formData.append('remarks', remarks);
    
    fetch('update_module_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(successMessage);
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert('Error updating module: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating module status. Please try again.');
    });
}

function updateExamStatus(examId, newStatus, successMessage, remarks = '') {
    const formData = new FormData();
    formData.append('exam_id', examId);
    formData.append('new_status', newStatus);
    formData.append('remarks', remarks);
    
    fetch('update_exam_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(successMessage);
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert('Error updating examination: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating examination status. Please try again.');
    });
}

// Filter Functions
function applyModuleFilter() {
    const departmentFilter = document.getElementById('departmentFilter');
    const selectedDepartment = departmentFilter.value;
    const urlParams = new URLSearchParams(window.location.search);
    const examDepartment = urlParams.get('exam_department') || 'all';
    window.location.href = `?department=${selectedDepartment}&exam_department=${examDepartment}`;
}

function clearModuleFilter() {
    const urlParams = new URLSearchParams(window.location.search);
    const examDepartment = urlParams.get('exam_department') || 'all';
    window.location.href = `?department=all&exam_department=${examDepartment}`;
}

function applyExamFilter() {
    const examDepartmentFilter = document.getElementById('examDepartmentFilter');
    const selectedExamDepartment = examDepartmentFilter.value;
    const urlParams = new URLSearchParams(window.location.search);
    const department = urlParams.get('department') || 'all';
    window.location.href = `?department=${department}&exam_department=${selectedExamDepartment}`;
}

function clearExamFilter() {
    const urlParams = new URLSearchParams(window.location.search);
    const department = urlParams.get('department') || 'all';
    window.location.href = `?department=${department}&exam_department=all`;
}
</script>