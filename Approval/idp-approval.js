// idp-approval.js

// ====================
// IDP SPECIFIC FUNCTIONS
// ====================

// Sample IDP data
const sampleIdpData = [
    {
        id: 'idp_001',
        employee_name: 'John Smith',
        employee_id: 'EMP-00123',
        department: 'Sales',
        position: 'Sales Executive',
        manager_name: 'Robert Johnson',
        approval_level: 'HIGH',
        current_status: 'PENDING',
        development_focus: 'Leadership & Strategic Thinking',
        career_goal: 'Sales Manager within 2 years',
        timeline: '12 months',
        submitted_by_name: 'John Smith',
        requested_role: 'Employee',
        submitted_at: '2024-01-15T09:30:00Z',
        development_areas: [
            {
                area: 'Leadership Skills',
                activities: [
                    'Complete Leadership Development Program',
                    'Mentor junior sales staff',
                    'Lead team meetings'
                ],
                timeline: '6 months'
            },
            {
                area: 'Strategic Thinking',
                activities: [
                    'Attend Strategic Planning Workshop',
                    'Develop territory expansion plan',
                    'Analyze market trends'
                ],
                timeline: '9 months'
            },
            {
                area: 'Technical Skills',
                activities: [
                    'Advanced CRM Training',
                    'Data Analysis Course',
                    'Sales Automation Tools'
                ],
                timeline: '3 months'
            }
        ],
        support_needed: [
            'Training budget approval',
            'Time off for courses',
            'Mentorship program access'
        ],
        urgency_reason: 'Employee is being groomed for managerial role'
    },
    {
        id: 'idp_002',
        employee_name: 'Sarah Chen',
        employee_id: 'EMP-00456',
        department: 'IT',
        position: 'Software Developer',
        manager_name: 'Michael Brown',
        approval_level: 'MEDIUM',
        current_status: 'PENDING',
        development_focus: 'Cloud Architecture & DevOps',
        career_goal: 'Senior Cloud Architect within 18 months',
        timeline: '12 months',
        submitted_by_name: 'Sarah Chen',
        requested_role: 'Employee',
        submitted_at: '2024-01-14T14:20:00Z',
        development_areas: [
            {
                area: 'Cloud Certification',
                activities: [
                    'AWS Solutions Architect Associate',
                    'Azure Administrator',
                    'Google Cloud Professional'
                ],
                timeline: '6 months'
            },
            {
                area: 'DevOps Practices',
                activities: [
                    'CI/CD Pipeline Implementation',
                    'Containerization with Docker',
                    'Kubernetes Administration'
                ],
                timeline: '9 months'
            }
        ],
        support_needed: [
            'Certification exam fees',
            'Access to cloud sandbox environment',
            'Conference attendance approval'
        ],
        urgency_reason: 'Critical project requiring cloud expertise starting Q2'
    },
    {
        id: 'idp_003',
        employee_name: 'David Wilson',
        employee_id: 'EMP-00789',
        department: 'HR',
        position: 'HR Generalist',
        manager_name: 'Lisa Taylor',
        approval_level: 'LOW',
        current_status: 'PENDING',
        development_focus: 'HR Analytics & Compliance',
        career_goal: 'HR Specialist in Analytics',
        timeline: '12 months',
        submitted_by_name: 'David Wilson',
        requested_role: 'Employee',
        submitted_at: '2024-01-12T11:15:00Z',
        development_areas: [
            {
                area: 'HR Analytics',
                activities: [
                    'People Analytics Course',
                    'HR Metrics Dashboard Development',
                    'Predictive Modeling Workshop'
                ],
                timeline: '8 months'
            },
            {
                area: 'Compliance Updates',
                activities: [
                    'Labor Law Updates Seminar',
                    'Data Privacy Regulations',
                    'Workplace Safety Compliance'
                ],
                timeline: '4 months'
            }
        ],
        support_needed: [
            'Software license for HR analytics tool',
            'Workshop registration',
            'Reference materials'
        ],
        urgency_reason: 'Standard professional development'
    },
    {
        id: 'idp_004',
        employee_name: 'Emily Davis',
        employee_id: 'EMP-00912',
        department: 'Marketing',
        position: 'Marketing Coordinator',
        manager_name: 'James Wilson',
        approval_level: 'MEDIUM',
        current_status: 'APPROVED',
        development_focus: 'Digital Marketing & Analytics',
        career_goal: 'Digital Marketing Manager',
        timeline: '12 months',
        submitted_by_name: 'Emily Davis',
        requested_role: 'Employee',
        submitted_at: '2024-01-10T10:00:00Z',
        development_areas: [
            {
                area: 'Digital Marketing',
                activities: [
                    'Google Analytics Certification',
                    'Social Media Marketing Course',
                    'SEO Best Practices Workshop'
                ],
                timeline: '6 months'
            }
        ],
        support_needed: [
            'Course enrollment fees',
            'Marketing tool access'
        ],
        urgency_reason: 'Company expanding digital presence'
    }
];

// ====================
// IDP RENDER FUNCTIONS
// ====================
function renderIdpTable() {
    const tbody = document.getElementById('idp-tbody');
    if (!tbody || !state || !state.idpData) return;
    
    tbody.innerHTML = '';
    
    // Sort by approval level (HIGH first, then MEDIUM, then LOW)
    const sortedData = [...state.idpData].sort((a, b) => {
        const order = { HIGH: 1, MEDIUM: 2, LOW: 3 };
        return order[a.approval_level] - order[b.approval_level];
    });
    
    if (sortedData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="py-8 px-4 text-center text-gray-500">
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-user-graduate text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No IDP requests</h3>
                        <p class="text-gray-600">No Individual Development Plans requiring approval</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    sortedData.forEach(item => {
        const tr = document.createElement('tr');
        tr.className = 'table-row';
        tr.innerHTML = `
            <td class="py-4 px-4">
                <div class="font-medium text-gray-900">${escapeHtml(item.employee_name)}</div>
                <div class="text-xs text-gray-500 mt-0.5">ID: ${escapeHtml(item.employee_id)}</div>
            </td>
            <td class="py-4 px-4">
                <div class="text-sm text-gray-900">${escapeHtml(item.department)}</div>
                <div class="text-xs text-gray-500">Manager: ${escapeHtml(item.manager_name)}</div>
            </td>
            <td class="py-4 px-4">
                <div class="text-sm text-gray-900">${escapeHtml(item.position)}</div>
            </td>
            <td class="py-4 px-4">
                <span class="level-badge ${getLevelClass(item.approval_level)}">
                    <span class="priority-indicator ${getPriorityClass(item.approval_level)}"></span>
                    ${item.approval_level}
                </span>
            </td>
            <td class="py-4 px-4">
                <span class="text-sm text-gray-600">
                    ${formatDate(item.submitted_at)}
                </span>
            </td>
            <td class="py-4 px-4">
                <span class="status-badge ${getStatusClass(item.current_status)}">
                    ${item.current_status}
                </span>
            </td>
            <td class="py-4 px-4">
                <div class="flex items-center space-x-1">
                    <button class="action-btn view-idp-btn" 
                            onclick="viewIdp('${item.id}')"
                            title="View Details">
                        <i class="fas fa-eye text-gray-600"></i>
                    </button>
                    ${item.current_status === 'PENDING' ? `
                        <button class="action-btn approve-idp-btn" 
                                onclick="approveIdpRequest('${item.id}')"
                                title="Approve">
                            <i class="fas fa-check text-green-600"></i>
                        </button>
                        <button class="action-btn decline-idp-btn" 
                                onclick="declineIdpRequest('${item.id}')"
                                title="Reject">
                            <i class="fas fa-times text-red-600"></i>
                        </button>
                    ` : ''}
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// ====================
// IDP VIEW FUNCTION
// ====================
function viewIdp(id) {
    const item = state.idpData.find(i => i.id === id);
    if (!item) return;
    
    state.modalRequestId = id;
    state.modalType = 'idp';
    
    document.getElementById('modal-idp-request-id').textContent = item.id.toUpperCase();
    
    const content = document.getElementById('idp-modal-content');
    
    // Build development areas HTML
    let developmentAreasHTML = '';
    (item.development_areas || []).forEach((area, index) => {
        let activitiesHTML = '';
        (area.activities || []).forEach(activity => {
            activitiesHTML += `
                <li class="flex items-start gap-2 py-1">
                    <i class="fas fa-check text-green-500 mt-1 text-xs"></i>
                    <span class="text-sm text-gray-700">${escapeHtml(activity)}</span>
                </li>
            `;
        });
        
        developmentAreasHTML += `
            <div class="level-card">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                            <span class="text-blue-600 font-semibold">${index + 1}</span>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-800">${escapeHtml(area.area)}</div>
                            <div class="text-xs text-gray-500 mt-0.5">Timeline: ${escapeHtml(area.timeline)}</div>
                        </div>
                    </div>
                    <span class="text-xs bg-blue-100 text-blue-600 px-2 py-1 rounded">
                        ${area.timeline}
                    </span>
                </div>
                <div class="pl-10">
                    <p class="text-sm font-medium text-gray-700 mb-2">Activities:</p>
                    <ul class="space-y-1 mb-3">
                        ${activitiesHTML}
                    </ul>
                </div>
            </div>
        `;
    });
    
    // Build support needed HTML
    let supportNeededHTML = '';
    (item.support_needed || []).forEach(support => {
        supportNeededHTML += `
            <li class="flex items-center gap-2 py-1.5">
                <i class="fas fa-handshake text-blue-500"></i>
                <span class="text-sm text-gray-700">${escapeHtml(support)}</span>
            </li>
        `;
    });
    
    content.innerHTML = `
        <div class="section-title">
            <i class="fas fa-info-circle"></i>
            APPROVAL REQUEST DETAILS
        </div>
        
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="info-card">
                <div class="info-label">Request ID</div>
                <div class="info-value-large">${item.id.toUpperCase()}</div>
            </div>
            
            <div class="info-card">
                <div class="info-label">Approval Level</div>
                <div class="info-value-large">
                    <span class="level-badge ${getLevelClass(item.approval_level)}">
                        <span class="priority-indicator ${getPriorityClass(item.approval_level)}"></span>
                        ${item.approval_level}
                    </span>
                </div>
            </div>
        </div>

        <div class="section-divider"></div>

        <div class="section-title">
            <i class="fas fa-user"></i>
            EMPLOYEE INFORMATION
        </div>
        
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="info-card">
                <div class="info-label">EMPLOYEE NAME</div>
                <div class="info-value-large">${escapeHtml(item.employee_name)}</div>
            </div>
            
            <div class="info-card">
                <div class="info-label">EMPLOYEE ID</div>
                <div class="info-value">${escapeHtml(item.employee_id)}</div>
            </div>
        </div>
        
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="info-card">
                <div class="info-label">DEPARTMENT</div>
                <div class="info-value">
                    <span class="category-badge ${getDepartmentColor(item.department)}">
                        ${escapeHtml(item.department)}
                    </span>
                </div>
            </div>
            
            <div class="info-card">
                <div class="info-label">POSITION</div>
                <div class="info-value">${escapeHtml(item.position)}</div>
            </div>
        </div>
        
        <div class="info-card">
            <div class="info-label">MANAGER</div>
            <div class="info-value">${escapeHtml(item.manager_name)}</div>
        </div>

        <div class="section-divider"></div>

        <div class="section-title">
            <i class="fas fa-bullseye"></i>
            DEVELOPMENT OBJECTIVES
        </div>
        
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="info-card">
                <div class="info-label">DEVELOPMENT FOCUS</div>
                <div class="info-value-large">${escapeHtml(item.development_focus)}</div>
            </div>
            
            <div class="info-card">
                <div class="info-label">CAREER GOAL</div>
                <div class="info-value">${escapeHtml(item.career_goal)}</div>
            </div>
        </div>
        
        <div class="info-card">
            <div class="info-label">TIMELINE</div>
            <div class="info-value">
                <div class="flex items-center gap-2">
                    <i class="fas fa-calendar-alt text-blue-500"></i>
                    <span>${escapeHtml(item.timeline)}</span>
                </div>
            </div>
        </div>

        <div class="section-divider"></div>

        <div class="section-title">
            <i class="fas fa-exclamation-circle"></i>
            URGENCY REASON
        </div>
        
        <div class="info-card">
            <div class="info-value">
                <i class="fas ${getUrgencyIcon(item.approval_level)} text-${getUrgencyColor(item.approval_level)} mr-2"></i>
                ${escapeHtml(item.urgency_reason)}
            </div>
        </div>

        <div class="section-divider"></div>

        <div class="section-title">
            <i class="fas fa-chart-line"></i>
            DEVELOPMENT AREAS
        </div>
        
        <div class="space-y-4 mb-6">
            ${developmentAreasHTML || '<p class="text-gray-500 text-sm p-4 text-center">No development areas defined</p>'}
        </div>

        <div class="section-divider"></div>

        <div class="section-title">
            <i class="fas fa-hands-helping"></i>
            SUPPORT REQUIRED
        </div>
        
        <div class="info-card">
            <ul class="space-y-1">
                ${supportNeededHTML || '<p class="text-gray-500 text-sm">No specific support requested</p>'}
            </ul>
        </div>

        <div class="section-divider"></div>

        <div class="section-title">
            <i class="fas fa-user"></i>
            SUBMISSION DETAILS
        </div>
        
        <div class="grid grid-cols-2 gap-4">
            <div class="info-card">
                <div class="info-label">SUBMITTED BY</div>
                <div class="info-value">${escapeHtml(item.submitted_by_name)}</div>
            </div>
            
            <div class="info-card">
                <div class="info-label">REQUESTED ROLE</div>
                <div class="info-value">${escapeHtml(item.requested_role)}</div>
            </div>
        </div>
        
        <div class="info-card">
            <div class="info-label">SUBMITTED AT</div>
            <div class="info-value">${formatDateTime(item.submitted_at)}</div>
        </div>

        <div class="section-divider"></div>

        <div class="section-title">
            <i class="fas fa-clipboard-check"></i>
            APPROVAL STATUS
        </div>
        
        <div class="info-card">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full ${item.current_status === 'PENDING' ? 'bg-yellow-500 animate-pulse' : item.current_status === 'APPROVED' ? 'bg-green-500' : 'bg-red-500'}"></div>
                    <div class="info-value-large">${item.current_status}</div>
                </div>
                <span class="status-badge ${getStatusClass(item.current_status)}">
                    ${item.current_status === 'PENDING' ? 'AWAITING REVIEW' : item.current_status}
                </span>
            </div>
        </div>
    `;
    
    // Show/hide action buttons
    const approveBtn = document.getElementById('idp-approve-btn');
    const declineBtn = document.getElementById('idp-decline-btn');
    
    if (item.current_status === 'PENDING') {
        approveBtn.style.display = 'inline-flex';
        declineBtn.style.display = 'inline-flex';
    } else {
        approveBtn.style.display = 'none';
        declineBtn.style.display = 'none';
    }
    
    document.getElementById('idp-view-modal').classList.add('modal-open');
}

// ====================
// IDP ACTION FUNCTIONS
// ====================
function approveIdpRequest(id) {
    const item = state.idpData.find(i => i.id === id);
    if (item && item.current_status === 'PENDING') {
        item.current_status = 'APPROVED';
        showAlert('Individual Development Plan approved successfully!', 'success');
        renderIdpTable();
        updateStats();
        closeModal();
    }
}

function declineIdpRequest(id) {
    Swal.fire({
        title: 'Reject IDP Request',
        html: '<textarea id="rejection-reason" class="w-full p-2 border rounded mt-2" placeholder="Please provide a reason for rejection..." rows="3"></textarea>',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Reject',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const reason = document.getElementById('rejection-reason').value;
            if (!reason) {
                Swal.showValidationMessage('Please provide a reason for rejection');
                return false;
            }
            return reason;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const item = state.idpData.find(i => i.id === id);
            if (item && item.current_status === 'PENDING') {
                item.current_status = 'REJECTED';
                item.rejection_reason = result.value;
                showAlert('Individual Development Plan rejected.', 'error');
                renderIdpTable();
                updateStats();
                closeModal();
            }
        }
    });
}

// ====================
// DATA LOADER
// ====================
function loadIdpData() {
    return sampleIdpData;
}