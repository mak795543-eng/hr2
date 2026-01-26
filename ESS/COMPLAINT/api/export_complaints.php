<?php
require_once 'config/db.php';

// Check admin authentication
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.0 403 Forbidden');
    exit();
}

// Get filters from query parameters
$filters = [];
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
    $filters['category_id'] = $_GET['category_id'];
}
if (isset($_GET['department']) && !empty($_GET['department'])) {
    $filters['department'] = $_GET['department'];
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Get complaints
$complaintModel = new ComplaintModel($db);
$complaints = $complaintModel->getComplaints($filters);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=complaints_' . date('Y-m-d_H-i-s') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, [
    'Complaint ID',
    'Category',
    'Title',
    'Description',
    'Employee',
    'Department',
    'Status',
    'Priority',
    'Incident Date',
    'Submitted Date',
    'Confidential',
    'Anonymous',
    'Admin Notes',
    'Assigned To',
    'Target Resolution Date',
    'Actual Resolution Date'
]);

// Add data rows
foreach ($complaints as $complaint) {
    fputcsv($output, [
        $complaint['complaint_code'],
        $complaint['category_name'],
        $complaint['title'],
        $complaint['description'],
        $complaint['employee_first_name'] . ' ' . $complaint['employee_last_name'],
        $complaint['department'],
        ucfirst(str_replace('_', ' ', $complaint['status'])),
        ucfirst($complaint['priority']),
        $complaint['incident_date'],
        $complaint['created_at'],
        $complaint['is_confidential'] ? 'Yes' : 'No',
        $complaint['is_anonymous'] ? 'Yes' : 'No',
        $complaint['admin_notes'] ?? '',
        $complaint['assigned_first_name'] . ' ' . $complaint['assigned_last_name'],
        $complaint['target_resolution_date'],
        $complaint['actual_resolution_date']
    ]);
}

fclose($output);
?>