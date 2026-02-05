// common-approval.js

// ====================
// SHARED UTILITY FUNCTIONS
// ====================

function updateStats() {
    if (!state || !state.trainingData || !state.competencyData) return;
    
    // Count by approval level
    let highPending = 0;
    let mediumPending = 0;
    let lowPending = 0;
    
    // Training counts
    state.trainingData.forEach(item => {
        if (item.current_status === 'PENDING') {
            if (item.approval_level === 'HIGH') highPending++;
            else if (item.approval_level === 'MEDIUM') mediumPending++;
            else if (item.approval_level === 'LOW') lowPending++;
        }
    });
    
    // Competency counts
    state.competencyData.forEach(item => {
        if (item.current_status === 'PENDING') {
            if (item.approval_level === 'HIGH') highPending++;
            else if (item.approval_level === 'MEDIUM') mediumPending++;
            else if (item.approval_level === 'LOW') lowPending++;
        }
    });
    
    // IDP counts
    if (state.idpData && state.idpData.length > 0) {
        state.idpData.forEach(item => {
            if (item.current_status === 'PENDING') {
                if (item.approval_level === 'HIGH') highPending++;
                else if (item.approval_level === 'MEDIUM') mediumPending++;
                else if (item.approval_level === 'LOW') lowPending++;
            }
        });
    }
    
    const totalPending = highPending + mediumPending + lowPending;
    
    // Update stats cards
    document.getElementById('pending-count').textContent = totalPending;
    document.getElementById('high-priority-count').textContent = highPending;
    document.getElementById('medium-priority-count').textContent = mediumPending;
    document.getElementById('low-priority-count').textContent = lowPending;
    
    // Update tab badges (show pending counts)
    const pendingTraining = state.trainingData.filter(t => t.current_status === 'PENDING').length;
    const pendingCompetency = state.competencyData.filter(c => c.current_status === 'PENDING').length;
    const pendingIdp = state.idpData ? state.idpData.filter(i => i.current_status === 'PENDING').length : 0;
    
    document.getElementById('training-badge').textContent = pendingTraining;
    document.getElementById('competency-badge').textContent = pendingCompetency;
    document.getElementById('idp-badge').textContent = pendingIdp;
}

function getLevelClass(level) {
    switch(level) {
        case 'LOW': return 'level-low';
        case 'MEDIUM': return 'level-medium';
        case 'HIGH': return 'level-high';
        default: return 'level-low';
    }
}

function getPriorityClass(level) {
    switch(level) {
        case 'LOW': return 'priority-low';
        case 'MEDIUM': return 'priority-medium';
        case 'HIGH': return 'priority-high';
        default: return 'priority-low';
    }
}

function getUrgencyIcon(level) {
    switch(level) {
        case 'HIGH': return 'fa-exclamation-triangle';
        case 'MEDIUM': return 'fa-flag';
        case 'LOW': return 'fa-info-circle';
        default: return 'fa-info-circle';
    }
}

function getUrgencyColor(level) {
    switch(level) {
        case 'HIGH': return 'red-500';
        case 'MEDIUM': return 'amber-500';
        case 'LOW': return 'green-500';
        default: return 'gray-500';
    }
}

function getStatusClass(status) {
    switch(status) {
        case 'PENDING': return 'status-pending';
        case 'APPROVED': return 'status-approved';
        case 'REJECTED': return 'status-rejected';
        default: return 'status-pending';
    }
}

function getCategoryColor(category) {
    const colors = {
        'Technical': 'bg-blue-100 text-blue-800',
        'Soft Skills': 'bg-green-100 text-green-800',
        'Management': 'bg-purple-100 text-purple-800',
        'Compliance': 'bg-red-100 text-red-800',
        'Core': 'bg-gray-100 text-gray-800',
        'Leadership': 'bg-indigo-100 text-indigo-800',
        'Communication': 'bg-pink-100 text-pink-800'
    };
    return colors[category] || 'bg-gray-100 text-gray-800';
}

function getRoleColor(role) {
    const colors = {
        'All Roles': 'bg-gray-100 text-gray-800',
        'Kitchen Staff': 'bg-orange-100 text-orange-800',
        'Supervisors': 'bg-purple-100 text-purple-800',
        'HR': 'bg-pink-100 text-pink-800'
    };
    return colors[role] || 'bg-gray-100 text-gray-800';
}

function getPriorityColor(priority) {
    const colors = {
        'High': 'bg-red-100 text-red-800',
        'Medium': 'bg-yellow-100 text-yellow-800',
        'Low': 'bg-green-100 text-green-800'
    };
    return colors[priority] || 'bg-gray-100 text-gray-800';
}

function getLevelBadge(level) {
    const badges = ['Basic', 'Developing', 'Proficient', 'Advanced', 'Mastery'];
    return badges[level - 1] || 'Level ' + level;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric' 
    });
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showAlert(message, type = 'success') {
    Swal.fire({
        title: type === 'success' ? 'Success!' : 'Rejected!',
        text: message,
        icon: type,
        confirmButtonColor: '#3b82f6',
        confirmButtonText: 'OK'
    });
}

function getDepartmentColor(department) {
    const colors = {
        'Sales': 'bg-red-100 text-red-800',
        'IT': 'bg-blue-100 text-blue-800',
        'HR': 'bg-purple-100 text-purple-800',
        'Marketing': 'bg-green-100 text-green-800',
        'Finance': 'bg-yellow-100 text-yellow-800',
        'Operations': 'bg-indigo-100 text-indigo-800',
        'Customer Service': 'bg-pink-100 text-pink-800'
    };
    return colors[department] || 'bg-gray-100 text-gray-800';
}