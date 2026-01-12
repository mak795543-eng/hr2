// ============================================================================
// ADMIN/LOCATION REQUEST FUNCTIONS
// ============================================================================

// This file is now integrated into main.js
// Keeping it for backward compatibility

// Save location as draft
function saveLocationAsDraft() {
    // This function is now handled by main.js
    if (typeof saveAsDraft === 'function') {
        saveAsDraft('location');
    }
}

// Make functions available globally
window.saveLocationAsDraft = saveLocationAsDraft;