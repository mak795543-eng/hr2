// ====================
// STATE
// ====================
let competencies = Array.isArray(window.__DB_COMPETENCIES__) ? window.__DB_COMPETENCIES__ : [];
let filters = {
    category: 'all',
    role: 'all',
    status: 'posted',
    priority: 'all',
    search: ''
};
let currentCompetencyId = null;

function buildJobTitleToDepartmentMap() {
    const map = window.__JOB_TITLES_BY_DEPARTMENT__ || {};
    const reverse = {};
    Object.keys(map).forEach((deptId) => {
        const titles = Array.isArray(map[deptId]) ? map[deptId] : [];
        titles.forEach((t) => {
            const key = String(t || '').trim();
            if (!key) return;
            if (!reverse[key]) {
                reverse[key] = String(deptId);
            }
        });
    });
    return reverse;
}

function populateAllJobTitles(selectedTitle = '') {
    const jobTitleSelect = document.getElementById('edit-job-title');
    if (!jobTitleSelect) return;

    const map = window.__JOB_TITLES_BY_DEPARTMENT__ || {};
    const depts = Array.isArray(window.__STANDARD_DEPARTMENTS__) ? window.__STANDARD_DEPARTMENTS__ : [];

    jobTitleSelect.innerHTML = '';

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Select Job Title';
    jobTitleSelect.appendChild(placeholder);

    depts.forEach((d) => {
        const deptId = String(d.request_id);
        const deptName = String(d.name || deptId);
        const titles = Array.isArray(map[deptId]) ? map[deptId] : [];
        if (!titles.length) return;
        const group = document.createElement('optgroup');
        group.label = deptName;
        titles.forEach((title) => {
            const t = String(title || '').trim();
            if (!t) return;
            const opt = document.createElement('option');
            opt.value = t;
            opt.textContent = t;
            group.appendChild(opt);
        });
        jobTitleSelect.appendChild(group);
    });

    if (selectedTitle) {
        jobTitleSelect.value = selectedTitle;
    }
}

// ====================
// INITIALIZATION
// ====================
function init() {
    setupEventListeners();
    initDepartments();
    loadStats();
    loadCompetencies();
    updateLevelInputs();

    setFilter('status', filters.status);
}

function initDepartments() {
    const deptSelect = document.getElementById('edit-department');
    if (!deptSelect) return;
    const list = Array.isArray(window.__STANDARD_DEPARTMENTS__) ? window.__STANDARD_DEPARTMENTS__ : [];
    deptSelect.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Select Department';
    deptSelect.appendChild(placeholder);
    list.forEach((d) => {
        const opt = document.createElement('option');
        opt.value = d.request_id;
        opt.textContent = d.name;
        deptSelect.appendChild(opt);
    });
}

function updateJobTitlesForDepartment(deptId, selectedTitle = '') {
    const jobTitleSelect = document.getElementById('edit-job-title');
    if (!jobTitleSelect) return;

    const map = window.__JOB_TITLES_BY_DEPARTMENT__ || {};
    const titles = Array.isArray(map[deptId]) ? map[deptId] : [];

    if (!deptId) {
        populateAllJobTitles(selectedTitle);
        return;
    }

    jobTitleSelect.innerHTML = '';

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Select Job Title';
    jobTitleSelect.appendChild(placeholder);

    titles.forEach((title) => {
        const opt = document.createElement('option');
        opt.value = title;
        opt.textContent = title;
        jobTitleSelect.appendChild(opt);
    });

    if (selectedTitle) {
        jobTitleSelect.value = selectedTitle;
    }
}

function addQualificationItem(text = '', type = 'Education') {
    const container = document.getElementById('qualificationsContainer');
    const tpl = document.getElementById('qualificationTemplate');
    if (!container || !tpl) return;
    const node = tpl.content.cloneNode(true);
    const input = node.querySelector('.qualification-input');
    const typeSelect = node.querySelector('.qualification-type');
    const removeBtn = node.querySelector('.remove-btn');
    if (input) input.value = text;
    if (typeSelect) typeSelect.value = type;
    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            const item = this.closest('.draggable-item');
            if (item) item.remove();
        });
    }
    container.appendChild(node);
}

function addRequirementItem(text = '', category = 'Skill', essential = true) {
    const container = document.getElementById('requirementsContainer');
    const tpl = document.getElementById('requirementTemplate');
    if (!container || !tpl) return;
    const node = tpl.content.cloneNode(true);
    const input = node.querySelector('.requirement-input');
    const categorySelect = node.querySelector('.requirement-category');
    const essentialCheckbox = node.querySelector('.requirement-essential');
    const removeBtn = node.querySelector('.remove-btn');
    if (input) input.value = text;
    if (categorySelect) categorySelect.value = category;
    if (essentialCheckbox) essentialCheckbox.checked = !!essential;
    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            const item = this.closest('.draggable-item');
            if (item) item.remove();
        });
    }
    container.appendChild(node);
}

function initializeSortable() {
    const qualificationsContainer = document.getElementById('qualificationsContainer');
    const requirementsContainer = document.getElementById('requirementsContainer');
    if (!qualificationsContainer || !requirementsContainer || typeof Sortable === 'undefined') return;

    if (!qualificationsContainer.__sortable) {
        qualificationsContainer.__sortable = new Sortable(qualificationsContainer, {
            animation: 150,
            handle: '.cursor-move'
        });
    }
    if (!requirementsContainer.__sortable) {
        requirementsContainer.__sortable = new Sortable(requirementsContainer, {
            animation: 150,
            handle: '.cursor-move'
        });
    }
}

function setupEventListeners() {
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown-container')) {
            closeAllDropdowns();
        }
    });

    const deptSelect = document.getElementById('edit-department');
    if (deptSelect) {
        deptSelect.addEventListener('change', function() {
            const jobTitleSelect = document.getElementById('edit-job-title');
            const currentTitle = jobTitleSelect ? String(jobTitleSelect.value || '').trim() : '';
            updateJobTitlesForDepartment(this.value, currentTitle);
        });
    }

    const jobTitleSelect = document.getElementById('edit-job-title');
    if (jobTitleSelect) {
        jobTitleSelect.addEventListener('change', function() {
            const title = String(this.value || '').trim();
            if (!title) return;
            const reverse = buildJobTitleToDepartmentMap();
            const deptId = reverse[title] || '';
            const dept = document.getElementById('edit-department');
            if (dept && deptId) {
                dept.value = deptId;
            }
        });
    }

    const addQualificationBtn = document.getElementById('addQualificationBtn');
    if (addQualificationBtn) {
        addQualificationBtn.addEventListener('click', function() {
            addQualificationItem('', 'Education');
            initializeSortable();
        });
    }

    const addRequirementBtn = document.getElementById('addRequirementBtn');
    if (addRequirementBtn) {
        addRequirementBtn.addEventListener('click', function() {
            addRequirementItem('', 'Skill', true);
            initializeSortable();
        });
    }
}

// ====================
// DROPDOWN FUNCTIONS
// ====================
function toggleDropdown(type) {
    const dropdown = document.getElementById(`${type}-dropdown`);
    const icon = document.getElementById(`${type}-icon`);

    // Close all other dropdowns
    closeAllDropdowns();

    // Toggle current dropdown
    dropdown.classList.toggle('hidden');

    // Rotate icon
    if (dropdown.classList.contains('hidden')) {
        icon.style.transform = 'rotate(0deg)';
    } else {
        icon.style.transform = 'rotate(180deg)';
    }

    // Prevent event from bubbling to document click handler
    event.stopPropagation();
}

function closeAllDropdowns() {
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.classList.add('hidden');
    });

    // Reset all icons
    document.querySelectorAll('.dropdown-btn i.fa-chevron-down').forEach(icon => {
        icon.style.transform = 'rotate(0deg)';
    });
}

// ====================
// FILTER FUNCTIONS
// ====================
function setFilter(type, value) {
    filters[type] = value;

    // Update labels
    const labels = {
        category: 'category-label',
        role: 'role-label',
        status: 'status-label',
        priority: 'priority-label'
    };

    const displayTexts = {
        category: {
            all: 'All Categories',
            core: 'Core',
            leadership: 'Leadership',
            technical: 'Technical'
        },
        role: {
            all: 'All Roles',
            hotel: 'Hotel',
            restaurant: 'Restaurant',
            both: 'Both'
        },
        status: {
            all: 'All Status',
            posted: 'Posted',
            pending: 'Pending',
            rejected: 'Rejected',
            compliance: 'For Compliance'
        },
        priority: {
            all: 'All Priorities',
            high: 'High (4-5)',
            medium: 'Medium (3)',
            low: 'Low (1-2)'
        }
    };

    document.getElementById(labels[type]).textContent = displayTexts[type][value];

    // Close dropdown
    closeAllDropdowns();

    loadCompetencies();
}

function searchCompetencies() {
    filters.search = document.getElementById('search-input').value.toLowerCase();
    loadCompetencies();
}

function clearFilters() {
    filters = {
        category: 'all',
        role: 'all',
        status: 'all',
        priority: 'all',
        search: ''
    };

    document.getElementById('category-label').textContent = 'All Categories';
    document.getElementById('role-label').textContent = 'All Roles';
    document.getElementById('status-label').textContent = 'All Status';
    document.getElementById('priority-label').textContent = 'All Priorities';
    document.getElementById('search-input').value = '';

    loadCompetencies();
}

// ====================
// DATA LOADING FUNCTIONS
// ====================
function loadStats() {
    const total = competencies.length;
    const active = competencies.filter(c => c.status === 'active').length;
    const hotel = competencies.filter(c => c.role === 'hotel').length;
    const restaurant = competencies.filter(c => c.role === 'restaurant').length;
    const both = competencies.filter(c => c.role === 'both').length;

    document.getElementById('total-count').textContent = total;
    document.getElementById('active-count').textContent = active;
    document.getElementById('hotel-count').textContent = hotel;
    document.getElementById('restaurant-count').textContent = restaurant;
    document.getElementById('both-count').textContent = both;
}

function loadCompetencies() {
    let filtered = [...competencies];

    // Apply filters
    if (filters.category !== 'all') {
        filtered = filtered.filter(c => c.category === filters.category);
    }

    if (filters.role !== 'all') {
        filtered = filtered.filter(c => c.role === filters.role);
    }

    if (filters.status !== 'all') {
        filtered = filtered.filter(c => String(c.approval_status || '').toLowerCase() === String(filters.status || '').toLowerCase());
    }

    if (filters.priority !== 'all') {
        if (filters.priority === 'high') {
            filtered = filtered.filter(c => c.priority >= 4);
        } else if (filters.priority === 'medium') {
            filtered = filtered.filter(c => c.priority === 3);
        } else if (filters.priority === 'low') {
            filtered = filtered.filter(c => c.priority <= 2);
        }
    }

    if (filters.search) {
        const searchTerm = filters.search.toLowerCase();
        filtered = filtered.filter(c =>
            c.name.toLowerCase().includes(searchTerm) ||
            c.description.toLowerCase().includes(searchTerm) ||
            (c.hotel_context && c.hotel_context.toLowerCase().includes(searchTerm)) ||
            (c.restaurant_context && c.restaurant_context.toLowerCase().includes(searchTerm))
        );
    }

    renderCompetencies(filtered);
}

function renderCompetencies(filteredCompetencies) {
    const containerEl = document.getElementById('competencies-container');

    if (filteredCompetencies.length === 0) {
        containerEl.innerHTML = `
            <div class="container-box p-10 text-center">
                <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-search text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No competencies found</h3>
                <p class="text-gray-600 mb-4">Try adjusting your filters or search terms</p>
                <div class="flex gap-2 justify-center">
                    <button onclick="clearFilters()" class="btn-outline">
                        <i class="fas fa-refresh mr-2"></i>Clear Filters
                    </button>
                    <button onclick="openAddModal()" class="btn-primary">
                        <i class="fas fa-plus mr-2"></i>Add Competency
                    </button>
                </div>
            </div>
        `;
        return;
    }

    document.getElementById('filter-count').textContent = `Showing ${filteredCompetencies.length} competencies`;

    // Render competency cards
    containerEl.innerHTML = `
        <div class="container-box p-5">
            <div class="flex justify-between items-center mb-6">
                <h3 class="section-title">Competency Standards</h3>
                <div class="text-sm text-gray-500">
                    <span>${filteredCompetencies.length} standards defined</span>
                </div>
            </div>

            <div id="competency-cards" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                ${filteredCompetencies.map(comp => renderCompetencyCard(comp)).join('')}
            </div>
        </div>
    `;
}

function renderCompetencyCard(comp) {
    const deptName = getDepartmentNameById(comp.department_id);
    const qCount = Array.isArray(comp.qualifications) ? comp.qualifications.length : 0;
    const rCount = Array.isArray(comp.requirements) ? comp.requirements.length : 0;
    const approvalStatus = String(comp.approval_status || 'posted').toLowerCase();
    const pendingAction = String(comp.pending_action || 'upsert').toLowerCase();
    const approvalBadgeText = approvalStatus === 'pending'
        ? 'Pending'
        : (approvalStatus === 'rejected'
            ? 'Rejected'
            : (approvalStatus === 'compliance'
                ? 'For Compliance'
                : 'Posted'));
    const approvalBadgeClass = approvalStatus === 'pending'
        ? 'status-pending'
        : (approvalStatus === 'rejected'
            ? 'status-rejected'
            : (approvalStatus === 'compliance'
                ? 'status-compliance'
                : 'status-posted'));
    const deleteNote = (approvalStatus === 'pending' && pendingAction === 'delete') ? `<div class="text-xs text-red-600 mt-2">Delete requested: ${escapeHtml(comp.delete_reason || '')}</div>` : '';
    const needsReviewNote = (approvalStatus === 'rejected' || approvalStatus === 'compliance');
    const reviewBtn = needsReviewNote ? `
        <button onclick="showReviewReason(${Number(comp.id)})" class="btn-outline px-3 py-1.5 text-xs" title="View Notes">
            <i class="fas fa-circle-info"></i>
        </button>
    ` : '';
    return `
        <div class="competency-card stat-card p-5 hover:shadow-lg transition-all duration-200">
            <!-- Card Header -->
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center bg-blue-50">
                        <i class="fas fa-briefcase text-blue-500"></i>
                    </div>
                    <div>
                        <div class="font-bold text-gray-900 text-lg truncate max-w-[200px]">${comp.name}</div>
                        <div class="text-xs text-gray-500">${deptName} • ${comp.job_title_pattern || '%'}</div>
                    </div>
                </div>
                <span class="status-badge ${approvalBadgeClass}">${approvalBadgeText}</span>
            </div>

            <!-- Description -->
            <p class="text-gray-600 text-sm mb-4 line-clamp-2">${comp.description}</p>
            ${deleteNote}
            <div class="mb-4">
                <div class="flex flex-wrap gap-2">
                    <span class="role-tag tag-both">
                        <i class="fas fa-graduation-cap mr-1"></i>
                        Qualifications: ${qCount}
                    </span>
                    <span class="role-tag tag-both">
                        <i class="fas fa-list-check mr-1"></i>
                        Requirements: ${rCount}
                    </span>
                </div>
            </div>

            <!-- Card Footer - Actions -->
            <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                <div class="text-xs text-gray-500">
                    Updated: ${formatDate(comp.last_updated)}
                </div>
                <div class="flex gap-2">
                    ${reviewBtn}
                    <button onclick="viewCompetency(${comp.id})" class="btn-outline px-3 py-1.5 text-xs" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="editCompetency(${comp.id})" class="btn-outline px-3 py-1.5 text-xs" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="confirmDelete(${comp.id})" class="btn-outline px-3 py-1.5 text-xs text-red-600 hover:text-red-700" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
}

function showReviewReason(id) {
    const comp = competencies.find(c => String(c.id) === String(id));
    if (!comp) return;

    const approvalStatus = String(comp.approval_status || '').toLowerCase();
    const title = approvalStatus === 'compliance' ? 'For Compliance' : 'Rejected';
    const reason = String(comp.review_reason || '').trim() || 'No notes provided.';

    Swal.fire({
        title,
        text: reason,
        icon: approvalStatus === 'compliance' ? 'info' : 'warning',
        confirmButtonColor: '#1f3a8a'
    });
}

// ====================
// COMPETENCY CRUD FUNCTIONS
// ====================
function openAddModal() {
    currentCompetencyId = null;
    document.getElementById('edit-title').textContent = 'Add Competency Standard';
    document.getElementById('edit-id').value = '';
    document.getElementById('competency-form').reset();
    const deptSelect = document.getElementById('edit-department');
    if (deptSelect) {
        deptSelect.value = '';
    }
    updateJobTitlesForDepartment('', '');

    const qualificationsContainer = document.getElementById('qualificationsContainer');
    if (qualificationsContainer) qualificationsContainer.innerHTML = '';
    const requirementsContainer = document.getElementById('requirementsContainer');
    if (requirementsContainer) requirementsContainer.innerHTML = '';
    initializeSortable();

    document.getElementById('edit-modal').showModal();
}

function editCompetency(id) {
    const competency = competencies.find(c => c.id == id);
    if (!competency) return;

    currentCompetencyId = id;
    document.getElementById('edit-title').textContent = 'Edit Competency Standard';
    document.getElementById('edit-id').value = id;

    const titleValue = String(competency.job_title_pattern || competency.name || '').trim();
    const reverse = buildJobTitleToDepartmentMap();

    const deptSelect = document.getElementById('edit-department');
    if (deptSelect) {
        deptSelect.value = competency.department_id || reverse[titleValue] || deptSelect.value;
    }
    updateJobTitlesForDepartment('', titleValue);
    document.getElementById('edit-description').value = competency.description;

    const qualificationsContainer = document.getElementById('qualificationsContainer');
    if (qualificationsContainer) {
        qualificationsContainer.innerHTML = '';
        const list = Array.isArray(competency.qualifications) ? competency.qualifications : [];
        list.forEach((q) => addQualificationItem(q.text || '', q.type || 'Education'));
    }
    const requirementsContainer = document.getElementById('requirementsContainer');
    if (requirementsContainer) {
        requirementsContainer.innerHTML = '';
        const list = Array.isArray(competency.requirements) ? competency.requirements : [];
        list.forEach((r) => addRequirementItem(r.text || '', r.category || 'Skill', !!r.essential));
    }
    initializeSortable();

    document.getElementById('edit-modal').showModal();
}

function viewCompetency(id) {
    const competency = competencies.find(c => c.id == id);
    if (!competency) return;

    const deptName = getDepartmentNameById(competency.department_id);

    const qualifications = Array.isArray(competency.qualifications) ? competency.qualifications : [];
    const requirements = Array.isArray(competency.requirements) ? competency.requirements : [];

    // Show competency details in a modal
    Swal.fire({
        title: competency.name,
        html: `
            <div class="text-left space-y-4">
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="role-tag tag-both">
                        <i class="fas fa-building mr-1"></i>
                        ${deptName}
                    </span>
                    <span class="role-tag tag-both">
                        <i class="fas fa-tag mr-1"></i>
                        ${competency.job_title_pattern || '%'}
                    </span>
                </div>

                <div>
                    <h6 class="font-medium text-gray-700 mb-1">Job Description</h6>
                    <p class="text-gray-600">${competency.description}</p>
                </div>

                <div class="border-t pt-4">
                    <h6 class="font-medium text-gray-700 mb-3">Qualifications</h6>
                    <div class="space-y-2">
                        ${qualifications.length ? qualifications.map(q => `
                            <div class="border rounded-lg p-3">
                                <div class="text-xs text-gray-500 mb-1">${q.type || 'Qualification'}</div>
                                <div class="text-sm text-gray-700">${q.text || ''}</div>
                            </div>
                        `).join('') : '<div class="text-sm text-gray-500">No qualifications defined</div>'}
                    </div>
                </div>

                <div class="border-t pt-4">
                    <h6 class="font-medium text-gray-700 mb-3">Job Requirements</h6>
                    <div class="space-y-2">
                        ${requirements.length ? requirements.map(r => `
                            <div class="border rounded-lg p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-xs text-gray-500 mb-1">${r.category || 'Requirement'}</div>
                                        <div class="text-sm text-gray-700">${r.text || ''}</div>
                                    </div>
                                    <div class="text-xs ${r.essential ? 'text-red-600' : 'text-gray-400'}">${r.essential ? 'Essential' : 'Optional'}</div>
                                </div>
                            </div>
                        `).join('') : '<div class="text-sm text-gray-500">No requirements defined</div>'}
                    </div>
                </div>
            </div>
        `,
        width: '800px',
        showCloseButton: true,
        showCancelButton: false,
        confirmButtonText: 'Close',
        confirmButtonColor: '#1f3a8a'
    });
}

function getDepartmentNameById(deptId) {
    const list = Array.isArray(window.__STANDARD_DEPARTMENTS__) ? window.__STANDARD_DEPARTMENTS__ : [];
    const found = list.find(d => String(d.request_id) === String(deptId));
    return found ? found.name : (deptId || '');
}

function saveCompetency() {
    const form = document.getElementById('competency-form');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const id = document.getElementById('edit-id').value;

    const qualifications = Array.from(document.querySelectorAll('#qualificationsContainer .draggable-item')).map(item => {
        const input = item.querySelector('.qualification-input');
        const typeSelect = item.querySelector('.qualification-type');
        return {
            text: input ? input.value.trim() : '',
            type: typeSelect ? typeSelect.value : 'Education'
        };
    }).filter(q => q.text);

    const requirements = Array.from(document.querySelectorAll('#requirementsContainer .draggable-item')).map(item => {
        const input = item.querySelector('.requirement-input');
        const categorySelect = item.querySelector('.requirement-category');
        const essentialCheckbox = item.querySelector('.requirement-essential');
        return {
            text: input ? input.value.trim() : '',
            category: categorySelect ? categorySelect.value : 'Skill',
            essential: essentialCheckbox ? !!essentialCheckbox.checked : true
        };
    }).filter(r => r.text);

    const competencyData = {
        name: document.getElementById('edit-job-title').value,
        department_id: document.getElementById('edit-department').value,
        job_title_pattern: document.getElementById('edit-job-title').value,
        description: document.getElementById('edit-description').value,
        qualifications,
        requirements,
        last_updated: new Date().toISOString().split('T')[0]
    };

    const payload = {
        ...competencyData,
        id: id ? Number(id) : null
    };

    fetch('addcriteria.php?api=1&action=upsert_competency', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
    })
        .then(r => r.json())
        .then(json => {
            if (!json || json.success !== true || !Array.isArray(json.data)) {
                throw new Error(json && json.message ? json.message : 'Failed to save');
            }

            competencies = json.data;
            Swal.fire({
                title: 'Submitted for Approval',
                text: 'This criteria is now Pending and will be available for Auto-Generate once approved.',
                icon: 'success',
                confirmButtonColor: '#1f3a8a',
                showDenyButton: true,
                confirmButtonText: 'OK',
                denyButtonText: 'Go to Approval',
                denyButtonColor: '#1f3a8a'
            }).then((result) => {
                if (result && result.isDenied) {
                    window.location.href = 'approval.php';
                }
            });

            closeModal('edit');
            loadCompetencies();
            loadStats();
        })
        .catch(err => {
            Swal.fire({
                title: 'Error',
                text: err && err.message ? err.message : 'Failed to save',
                icon: 'error',
                confirmButtonColor: '#1f3a8a'
            });
        });
}

function confirmDelete(id) {
    Swal.fire({
        title: 'Request Delete',
        text: 'Provide a reason. This will be sent for approval.',
        input: 'textarea',
        inputPlaceholder: 'Enter reason for deletion...',
        inputAttributes: {
            'aria-label': 'Reason for deletion'
        },
        inputValidator: (value) => {
            if (!value || !String(value).trim()) {
                return 'Reason is required';
            }
            return null;
        },
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Submit Delete Request',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('addcriteria.php?api=1&action=request_delete_competency', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ id: Number(id), reason: String(result.value || '').trim() })
            })
                .then(r => r.json())
                .then(json => {
                    if (!json || json.success !== true || !Array.isArray(json.data)) {
                        throw new Error(json && json.message ? json.message : 'Failed to submit delete request');
                    }

                    competencies = json.data;
                    Swal.fire({
                        title: 'Submitted for Approval',
                        text: 'Delete request submitted. Go to Approval page to approve/reject.',
                        icon: 'success',
                        confirmButtonColor: '#1f3a8a',
                        showDenyButton: true,
                        confirmButtonText: 'OK',
                        denyButtonText: 'Go to Approval',
                        denyButtonColor: '#1f3a8a'
                    }).then((result) => {
                        if (result && result.isDenied) {
                            window.location.href = 'approval.php';
                        }
                    });

                    loadCompetencies();
                    loadStats();
                })
                .catch(err => {
                    Swal.fire({
                        title: 'Error',
                        text: err && err.message ? err.message : 'Failed to submit delete request',
                        icon: 'error',
                        confirmButtonColor: '#1f3a8a'
                    });
                });
        }
    });
}

function escapeHtml(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ====================
// UTILITY FUNCTIONS
// ====================

// Function to get CSS class for role tags
function getRoleClass(role) {
    switch (role) {
        case 'hotel':
            return 'tag-hotel';
        case 'restaurant':
            return 'tag-restaurant';
        case 'both':
            return 'tag-both';
        default:
            return 'tag-both';
    }
}

// Function to get icon for role
function getRoleIcon(role) {
    switch (role) {
        case 'hotel':
            return 'fas fa-hotel';
        case 'restaurant':
            return 'fas fa-utensils';
        case 'both':
            return 'fas fa-building';
        default:
            return 'fas fa-building';
    }
}

// Function to get CSS class for category tags
function getCategoryClass(category) {
    switch (category) {
        case 'core':
            return 'tag-core';
        case 'leadership':
            return 'tag-leadership';
        case 'technical':
            return 'tag-technical';
        default:
            return 'tag-core';
    }
}

// Function to get CSS class for status
function getStatusClass(status) {
    switch (status) {
        case 'active':
            return 'status-active';
        case 'inactive':
            return 'status-inactive';
        default:
            return 'status-active';
    }
}

// Function to get display text for role
function getRoleDisplay(role) {
    switch (role) {
        case 'hotel':
            return 'Hotel';
        case 'restaurant':
            return 'Restaurant';
        case 'both':
            return 'Both Roles';
        default:
            return 'Both Roles';
    }
}

// Function to get display text for category
function getCategoryDisplay(category) {
    switch (category) {
        case 'core':
            return 'Core';
        case 'leadership':
            return 'Leadership';
        case 'technical':
            return 'Technical';
        default:
            return 'Core';
    }
}

// Function to get display text for status
function getStatusDisplay(status) {
    switch (status) {
        case 'active':
            return 'Active';
        case 'inactive':
            return 'Inactive';
        default:
            return 'Active';
    }
}

// Function to get level name
function getLevelName(level) {
    const levelNames = {
        1: 'Novice',
        2: 'Beginner',
        3: 'Competent',
        4: 'Proficient',
        5: 'Expert'
    };
    return levelNames[level] || `Level ${level}`;
}

// Function to format date
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    } catch (e) {
        return dateString;
    }
}

// Function to update level inputs
function updateLevelInputs(criteria = {}) {
    const levelInputs = document.getElementById('level-inputs');
    if (!levelInputs) return;

    levelInputs.innerHTML = '';

    for (let i = 1; i <= 5; i++) {
        levelInputs.innerHTML += `
            <div class="criteria-card p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-3">
                        <div class="level-badge level-${i}">${i}</div>
                        <div>
                            <h5 class="font-medium text-gray-900">${getLevelName(i)}</h5>
                            <p class="text-xs text-gray-500">Level ${i} performance criteria</p>
                        </div>
                    </div>
                </div>
                <textarea id="level-${i}-textarea" class="textarea textarea-bordered w-full h-20" 
                          placeholder="Describe what this level looks like in practice..." required>${criteria[i] || ''}</textarea>
            </div>
        `;
    }
}

// Function to open levels modal
function openLevelsModal() {
    document.getElementById('levels-modal').showModal();

    // Load levels content
    document.getElementById('levels-content').innerHTML = `
        <div class="space-y-4">
            <div class="criteria-card p-4">
                <div class="flex items-center gap-3 mb-3">
                    <div class="level-badge level-1">1</div>
                    <div>
                        <h5 class="font-medium text-gray-900">Novice</h5>
                        <p class="text-sm text-gray-600">Limited or no experience, requires close supervision</p>
                    </div>
                </div>
                <ul class="text-sm text-gray-600 space-y-1 pl-4">
                    <li>• Requires step-by-step instructions</li>
                    <li>• Needs frequent guidance and supervision</li>
                    <li>• Basic understanding of concepts</li>
                </ul>
            </div>

            <div class="criteria-card p-4">
                <div class="flex items-center gap-3 mb-3">
                    <div class="level-badge level-2">2</div>
                    <div>
                        <h5 class="font-medium text-gray-900">Beginner</h5>
                        <p class="text-sm text-gray-600">Some experience, can perform tasks with moderate supervision</p>
                    </div>
                </div>
                <ul class="text-sm text-gray-600 space-y-1 pl-4">
                    <li>• Can perform routine tasks independently</li>
                    <li>• Understands basic principles and procedures</li>
                    <li>• May need occasional guidance</li>
                </ul>
            </div>

            <div class="criteria-card p-4">
                <div class="flex items-center gap-3 mb-3">
                    <div class="level-badge level-3">3</div>
                    <div>
                        <h5 class="font-medium text-gray-900">Competent</h5>
                        <p class="text-sm text-gray-600">Solid experience, can perform tasks independently</p>
                    </div>
                </div>
                <ul class="text-sm text-gray-600 space-y-1 pl-4">
                    <li>• Works independently on most tasks</li>
                    <li>• Can handle non-routine situations</li>
                    <li>• May train or guide beginners</li>
                </ul>
            </div>

            <div class="criteria-card p-4">
                <div class="flex items-center gap-3 mb-3">
                    <div class="level-badge level-4">4</div>
                    <div>
                        <h5 class="font-medium text-gray-900">Proficient</h5>
                        <p class="text-sm text-gray-600">Advanced experience, can handle complex situations</p>
                    </div>
                </div>
                <ul class="text-sm text-gray-600 space-y-1 pl-4">
                    <li>• Handles complex tasks and problems</li>
                    <li>• Can train and mentor others</li>
                    <li>• Contributes to process improvements</li>
                </ul>
            </div>

            <div class="criteria-card p-4">
                <div class="flex items-center gap-3 mb-3">
                    <div class="level-badge level-5">5</div>
                    <div>
                        <h5 class="font-medium text-gray-900">Expert</h5>
                        <p class="text-sm text-gray-600">Mastery level, recognized authority in the area</p>
                    </div>
                </div>
                <ul class="text-sm text-gray-600 space-y-1 pl-4">
                    <li>• Recognized as an expert in the field</li>
                    <li>• Develops new approaches and methods</li>
                    <li>• Guides strategic direction and innovation</li>
                </ul>
            </div>
        </div>
    `;
}

// Function to close modal
function closeModal(modalName) {
    const modal = document.getElementById(`${modalName}-modal`);
    if (modal) {
        modal.close();
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', init);
