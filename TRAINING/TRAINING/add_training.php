<?php
session_start();
require_once __DIR__ . '/db.php';

$getOwnerKey = function(): string {
    $candidates = [
        'user_id' => 'user:',
        'employee_id' => 'emp:',
        'employee_no' => 'empno:',
        'username' => 'user:',
        'email' => 'user:',
    ];
    foreach ($candidates as $k => $prefix) {
        if (isset($_SESSION[$k]) && trim((string)$_SESSION[$k]) !== '') {
            return $prefix . trim((string)$_SESSION[$k]);
        }
    }
    return 'sess:' . session_id();
};

$ownerKey = $getOwnerKey();

$employees = [];
try {
    $resEmp = $conn->query("SELECT id, employee_no, first_name, last_name FROM employees ORDER BY last_name, first_name");
    while ($row = $resEmp->fetch_assoc()) {
        $employees[] = $row;
    }
} catch (Throwable $e) {
    $employees = [];
}

$mentors = [];
try {
    $conn->query("CREATE TABLE IF NOT EXISTS mentors (id INT AUTO_INCREMENT PRIMARY KEY, mentor_name VARCHAR(150) NOT NULL, expertise VARCHAR(150) NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_mentor_name (mentor_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("INSERT IGNORE INTO mentors (mentor_name, expertise) VALUES ('Juan Dela Cruz', 'Leadership'), ('Maria Santos', 'Technical Skills'), ('Jose Reyes', 'Customer Service')");

    $conn->query("CREATE TABLE IF NOT EXISTS department_heads (department_id INT PRIMARY KEY, mentor_id INT NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_dh_mentor (mentor_id), CONSTRAINT fk_department_heads_mentor FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS department_managers (department_id INT PRIMARY KEY, mentor_id INT NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_dm_mentor (mentor_id), CONSTRAINT fk_department_managers_mentor FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("INSERT IGNORE INTO mentors (mentor_name, expertise) VALUES ('Front Office / Reception Department Head', 'Department Head'), ('Housekeeping Department Head', 'Department Head'), ('Food & Beverage (F&B) Department Head', 'Department Head'), ('Kitchen / Culinary Department Head', 'Department Head'), ('Sales & Marketing Department Head', 'Department Head'), ('Human Resources (HR) Department Head', 'Department Head'), ('Finance / Accounting Department Head', 'Department Head'), ('Engineering / Maintenance Department Head', 'Department Head'), ('Security Department Head', 'Department Head')");

    $conn->query("INSERT IGNORE INTO mentors (mentor_name, expertise) VALUES ('Front Office / Reception Manager', 'Manager'), ('Housekeeping Manager', 'Manager'), ('Food & Beverage (F&B) Manager', 'Manager'), ('Kitchen / Culinary Manager', 'Manager'), ('Sales & Marketing Manager', 'Manager'), ('Human Resources (HR) Manager', 'Manager'), ('Finance / Accounting Manager', 'Manager'), ('Engineering / Maintenance Manager', 'Manager'), ('Security Manager', 'Manager')");

    $conn->query("INSERT INTO department_heads (department_id, mentor_id) VALUES (1, (SELECT id FROM mentors WHERE mentor_name = 'Front Office / Reception Department Head' LIMIT 1)), (2, (SELECT id FROM mentors WHERE mentor_name = 'Housekeeping Department Head' LIMIT 1)), (3, (SELECT id FROM mentors WHERE mentor_name = 'Food & Beverage (F&B) Department Head' LIMIT 1)), (4, (SELECT id FROM mentors WHERE mentor_name = 'Kitchen / Culinary Department Head' LIMIT 1)), (5, (SELECT id FROM mentors WHERE mentor_name = 'Sales & Marketing Department Head' LIMIT 1)), (6, (SELECT id FROM mentors WHERE mentor_name = 'Human Resources (HR) Department Head' LIMIT 1)), (7, (SELECT id FROM mentors WHERE mentor_name = 'Finance / Accounting Department Head' LIMIT 1)), (8, (SELECT id FROM mentors WHERE mentor_name = 'Engineering / Maintenance Department Head' LIMIT 1)), (9, (SELECT id FROM mentors WHERE mentor_name = 'Security Department Head' LIMIT 1)) ON DUPLICATE KEY UPDATE mentor_id = VALUES(mentor_id)");

    $conn->query("INSERT INTO department_managers (department_id, mentor_id) VALUES (1, (SELECT id FROM mentors WHERE mentor_name = 'Front Office / Reception Manager' LIMIT 1)), (2, (SELECT id FROM mentors WHERE mentor_name = 'Housekeeping Manager' LIMIT 1)), (3, (SELECT id FROM mentors WHERE mentor_name = 'Food & Beverage (F&B) Manager' LIMIT 1)), (4, (SELECT id FROM mentors WHERE mentor_name = 'Kitchen / Culinary Manager' LIMIT 1)), (5, (SELECT id FROM mentors WHERE mentor_name = 'Sales & Marketing Manager' LIMIT 1)), (6, (SELECT id FROM mentors WHERE mentor_name = 'Human Resources (HR) Manager' LIMIT 1)), (7, (SELECT id FROM mentors WHERE mentor_name = 'Finance / Accounting Manager' LIMIT 1)), (8, (SELECT id FROM mentors WHERE mentor_name = 'Engineering / Maintenance Manager' LIMIT 1)), (9, (SELECT id FROM mentors WHERE mentor_name = 'Security Manager' LIMIT 1)) ON DUPLICATE KEY UPDATE mentor_id = VALUES(mentor_id)");

    $resMentor = $conn->query("SELECT id, mentor_name, expertise FROM mentors ORDER BY mentor_name");
    while ($row = $resMentor->fetch_assoc()) {
        $mentors[] = $row;
    }
} catch (Throwable $e) {
    $mentors = [];
}

$departmentHeads = [];
try {
    $resDh = $conn->query("SELECT department_id, mentor_id FROM department_heads");
    while ($row = $resDh->fetch_assoc()) {
        $did = (int)($row['department_id'] ?? 0);
        $mid = (int)($row['mentor_id'] ?? 0);
        if ($did > 0 && $mid > 0) $departmentHeads[(string)$did] = $mid;
    }
} catch (Throwable $e) {
    $departmentHeads = [];
}

$departmentManagers = [];
try {
    $resDm = $conn->query("SELECT department_id, mentor_id FROM department_managers");
    while ($row = $resDm->fetch_assoc()) {
        $did = (int)($row['department_id'] ?? 0);
        $mid = (int)($row['mentor_id'] ?? 0);
        if ($did > 0 && $mid > 0) $departmentManagers[(string)$did] = $mid;
    }
} catch (Throwable $e) {
    $departmentManagers = [];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Training Program</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .swal2-container { z-index: 2147483647 !important; }
        .datetime-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        @media (max-width: 640px) {
            .datetime-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen" data-page="add-training" data-owner-key="<?= htmlspecialchars($ownerKey) ?>">
<div class="flex h-screen">
    <!-- Sidebar -->
    <?php 
    // Use relative path or absolute path based on your directory structure
    include '../../USM/sidebarr.php'; 
    ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../../USM/navbar.php'; ?>
        
<main class="max-w-6xl mx-auto px-4 py-6">
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Create New Training Program</h1>
                    <p class="text-gray-600">Fill in all required information to create a new training program</p>
                </div>
                <a id="add-training-back" href="trainingprogram.php" class="btn btn-ghost">Back</a>
            </div>

            <form id="training-form" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-6">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Training Program Title <span class="text-red-500">*</span></span>
                            </label>
                            <input id="training-title" type="text" placeholder="Enter training title" class="input input-bordered w-full" required>
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Training Type <span class="text-red-500">*</span></span>
                            </label>
                            <select id="training-type" class="select select-bordered w-full" required>
                                <option value="" disabled selected>Select training type</option>
                                <option value="Orientation">Orientation</option>
                                <option value="Training">Training</option>
                                <option value="Seminar">Seminar</option>
                                <option value="Workshop">Workshop</option>
                                <option value="Refresher">Refresher</option>
                            </select>
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Training Mode <span class="text-red-500">*</span></span>
                            </label>
                            <select id="training-mode" class="select select-bordered w-full" required>
                                <option value="Onsite" selected>Onsite</option>
                                <option value="Online">Online</option>
                                <option value="Hybrid">Hybrid</option>
                            </select>
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Requested By</span>
                            </label>
                            <select id="requested-by" class="select select-bordered w-full">
                                <option value="" selected>Select request type</option>
                                <option value="IDP">IDP</option>
                                <option value="New Hire Onboarding">New Hire Onboarding</option>
                            </select>
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Target Audience <span class="text-red-500">*</span></span>
                            </label>
                            <select id="target-audience" class="select select-bordered w-full" required>
                                <option value="" disabled selected>Select target audience</option>
                                <option value="By Department">By Department</option>
                                <option value="Managers">Managers</option>
                                <option value="Trainee">Trainee</option>
                                <option value="New Hires">New Hires</option>
                                <option value="Specific Employee">Specific Employee</option>
                                <option value="Mentor">Mentor</option>
                            </select>
                        </div>

                        <div id="department-container" class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Select Department <span class="text-red-500">*</span></span>
                            </label>
                            <select id="training-department" class="select select-bordered w-full" required>
                                <option value="" selected>Select a department</option>
                                <option value="1">Front Office / Reception</option>
                                <option value="2">Housekeeping</option>
                                <option value="3">Food &amp; Beverage (F&amp;B)</option>
                                <option value="4">Kitchen / Culinary</option>
                                <option value="5">Sales &amp; Marketing</option>
                                <option value="6">Human Resources (HR)</option>
                                <option value="7">Finance / Accounting</option>
                                <option value="8">Engineering / Maintenance</option>
                                <option value="9">Security</option>
                            </select>
                        </div>

                        <div id="sub-department-container" class="form-control hidden">
                            <label class="label">
                                <span class="label-text font-semibold">Select Sub-Department <span class="text-red-500">*</span></span>
                            </label>
                            <select id="training-sub-department" class="select select-bordered w-full">
                                <option value="" selected>Select sub-department</option>
                            </select>
                        </div>

                        <div id="role-container" class="form-control hidden">
                            <label class="label">
                                <span class="label-text font-semibold">Select Role <span class="text-red-500">*</span></span>
                            </label>
                            <select id="training-role" class="select select-bordered w-full">
                                <option value="" selected>Select a role</option>
                            </select>
                        </div>

                        <div id="employee-container" class="form-control hidden">
                            <label class="label">
                                <span class="label-text font-semibold">Select Employee <span class="text-red-500">*</span></span>
                            </label>
                            <select id="training-employee" class="select select-bordered w-full">
                                <option value="" selected>Select employee</option>
                                <?php foreach ($employees as $emp): ?>
                                    <?php
                                        $empId = (int)($emp['id'] ?? 0);
                                        $empNo = trim((string)($emp['employee_no'] ?? ''));
                                        $fn = trim((string)($emp['first_name'] ?? ''));
                                        $ln = trim((string)($emp['last_name'] ?? ''));
                                        $label = trim($ln . ', ' . $fn);
                                        if ($empNo !== '') $label .= ' (' . $empNo . ')';
                                    ?>
                                    <option value="<?= htmlspecialchars($empId) ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="space-y-4">
                            <div>
                                <label class="label">
                                    <span class="label-text font-semibold">Schedule <span class="text-red-500">*</span></span>
                                </label>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Participants Needed</span></label>
                                        <input id="participants-needed" type="number" min="1" value="1" class="input input-bordered w-full" required>
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Max Participants</span></label>
                                        <input id="max-participants" type="number" min="1" class="input input-bordered w-full">
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Competency Level</span></label>
                                        <select id="competency-level" class="select select-bordered w-full">
                                            <option value="Reskilling" selected>Reskilling</option>
                                            <option value="Upskilling">Upskilling</option>
                                            <option value="Retraining">Retraining</option>
                                            <option value="Succession Ready">Succession Ready</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="datetime-container">
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Start Date</span></label>
                                        <input id="start-date" type="date" class="input input-bordered w-full" required>
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Start Time</span></label>
                                        <input id="start-time" type="time" class="input input-bordered w-full" required>
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">End Date</span></label>
                                        <input id="end-date" type="date" class="input input-bordered w-full" required>
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">End Time</span></label>
                                        <input id="end-time" type="time" class="input input-bordered w-full" required>
                                    </div>
                                </div>
                                <div class="mt-2 text-sm text-gray-500" id="schedule-validation"></div>

                                <div id="training-category-container" class="form-control mt-4">
                                    <label class="label">
                                        <span class="label-text font-semibold">Development Plans</span>
                                    </label>
                                    <select id="training-category" class="select select-bordered w-full hidden">
                                        <option value="IDP" selected>IDP</option>
                                        <optgroup label="By Department" id="training-category-by-department"></optgroup>
                                    </select>
                                    <div id="idp-development-plans" class="mt-1 flex flex-wrap gap-2"></div>
                                    <div id="idp-development-plans-empty" class="text-xs text-gray-500 mt-1">No development plans loaded.</div>
                                </div>

                                <div class="form-control mt-4">
                                    <label class="label">
                                        <span class="label-text font-semibold">Mentor</span>
                                    </label>
                                    <select id="training-mentor" class="select select-bordered w-full">
                                        <option value="" selected>Select mentor</option>
                                        <?php foreach ($mentors as $m): ?>
                                            <?php
                                                $mid = (int)($m['id'] ?? 0);
                                                $mn = trim((string)($m['mentor_name'] ?? ''));
                                                $ex = trim((string)($m['expertise'] ?? ''));
                                                $label = $mn;
                                                if ($ex !== '') $label .= ' - ' . $ex;
                                            ?>
                                            <?php if ($mid > 0 && $mn !== ''): ?>
                                                <option value="<?= htmlspecialchars($mid) ?>"><?= htmlspecialchars($label) ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Description / Overview <span class="text-red-500">*</span></span>
                    </label>
                    <textarea id="description" class="textarea textarea-bordered h-32 w-full" placeholder="Provide a brief explanation of the training program" required></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Training Objectives</span>
                        </label>
                        <div class="bg-base-200 rounded-lg p-4 space-y-3">
                            <button type="button" id="objectives-open-btn" class="btn btn-outline btn-sm w-full">Select Objectives</button>
                            <div id="objectives-summary" class="text-sm text-gray-600">No objectives selected.</div>
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Other Objective (Optional)</span>
                        </label>
                        <textarea id="training-objectives-other" class="textarea textarea-bordered h-32 w-full" placeholder="Type additional objective(s) here..."></textarea>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Need Budget? (Financial)</span>
                        </label>
                        <select id="need-budget" class="select select-bordered w-full">
                            <option value="0" selected>No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Need Items? (Logistics)</span>
                        </label>
                        <select id="need-items" class="select select-bordered w-full">
                            <option value="0" selected>No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Need Facility? (Admin)</span>
                        </label>
                        <select id="need-facility" class="select select-bordered w-full">
                            <option value="0" selected>No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                </div>

                <div id="request-summary" class="space-y-4">
                    <div class="text-sm font-semibold text-gray-700">Request Summary</div>
                    <div id="budget-summary" class="hidden"></div>
                    <div id="logistics-summary" class="hidden"></div>
                    <div id="facility-summary" class="hidden"></div>
                </div>
            </form>

            <div class="flex justify-end gap-2 pt-6 border-t border-gray-100">
                <a id="add-training-cancel" href="trainingprogram.php" class="btn btn-ghost">Cancel</a>
                <button type="button" id="save-training-btn" class="btn btn-primary">Save Training Program</button>
            </div>
        </div>
    </main>

    <dialog id="budget-request-modal" class="modal">
        <div class="modal-box w-11/12 max-w-4xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-bold text-xl mb-1">Budget Request</h3>
                    <p class="text-gray-600">Request budget for training, seminar, or orientation</p>
                </div>
                <button type="button" id="budget-cancel-btn" class="btn btn-ghost btn-sm">✕</button>
            </div>
            <form id="budget-request-form" class="space-y-5">
                <div>
                    <div class="text-sm font-semibold text-gray-700 mb-3">Basic Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text">Training/Seminar Title <span class="text-red-500">*</span></span></label>
                            <input id="budget-title" type="text" class="input input-bordered w-full" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Purpose <span class="text-red-500">*</span></span></label>
                            <input id="budget-purpose" type="text" class="input input-bordered w-full" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Department <span class="text-red-500">*</span></span></label>
                            <select id="budget-department" class="select select-bordered w-full" required>
                                <option value="" selected>Select Department</option>
                                <option value="1">Front Office / Reception</option>
                                <option value="2">Housekeeping</option>
                                <option value="3">Food &amp; Beverage (F&amp;B)</option>
                                <option value="4">Kitchen / Culinary</option>
                                <option value="5">Sales &amp; Marketing</option>
                                <option value="6">Human Resources (HR)</option>
                                <option value="7">Finance / Accounting</option>
                                <option value="8">Engineering / Maintenance</option>
                                <option value="9">Security</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Event Date <span class="text-red-500">*</span></span></label>
                            <input id="budget-event-date" type="date" class="input input-bordered w-full" required>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="text-sm font-semibold text-gray-700 mb-3">Budget Items</div>
                    <div class="bg-base-200 rounded-lg p-4 space-y-4">
                        <div id="budget-items-container" class="space-y-4"></div>
                        <button type="button" id="budget-add-item-btn" class="btn btn-outline btn-sm w-full">+ Add Another Budget Item</button>
                    </div>
                </div>

                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-semibold text-gray-700">Total Estimated Cost</div>
                            <div class="text-xs text-gray-500">Sum of all budget items</div>
                        </div>
                        <div class="text-lg font-bold text-blue-600">₱<span id="budget-total-cost">0.00</span></div>
                    </div>
                </div>

                <div>
                    <label class="label"><span class="label-text">Justification <span class="text-red-500">*</span></span></label>
                    <textarea id="budget-justification" class="textarea textarea-bordered h-24 w-full" required placeholder="Explain why this budget is needed and how it will benefit the training..."></textarea>
                </div>
                <div>
                    <label class="label"><span class="label-text">Remarks</span></label>
                    <textarea id="budget-remarks" class="textarea textarea-bordered h-24 w-full" placeholder="Additional notes or comments..."></textarea>
                </div>
            </form>

            <div class="modal-action">
                <button type="button" id="budget-cancel-action-btn" class="btn btn-ghost">Cancel</button>
                <button type="button" id="budget-save-btn" class="btn btn-primary">Save Budget Request</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <dialog id="logistics-request-modal" class="modal">
        <div class="modal-box w-11/12 max-w-4xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-bold text-xl mb-1">Logistics Request</h3>
                    <p class="text-gray-600">Request items for training, seminar, or orientation</p>
                </div>
                <button type="button" id="logistics-cancel-btn" class="btn btn-ghost btn-sm">✕</button>
            </div>
            <form id="logistics-request-form" class="space-y-5">
                <div>
                    <div class="text-sm font-semibold text-gray-700 mb-3">Basic Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text">Training/Seminar Title <span class="text-red-500">*</span></span></label>
                            <input id="logistics-title" type="text" class="input input-bordered w-full" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Purpose <span class="text-red-500">*</span></span></label>
                            <input id="logistics-purpose" type="text" class="input input-bordered w-full" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Department <span class="text-red-500">*</span></span></label>
                            <select id="logistics-department" class="select select-bordered w-full" required>
                                <option value="" selected>Select Department</option>
                                <option value="1">Front Office / Reception</option>
                                <option value="2">Housekeeping</option>
                                <option value="3">Food &amp; Beverage (F&amp;B)</option>
                                <option value="4">Kitchen / Culinary</option>
                                <option value="5">Sales &amp; Marketing</option>
                                <option value="6">Human Resources (HR)</option>
                                <option value="7">Finance / Accounting</option>
                                <option value="8">Engineering / Maintenance</option>
                                <option value="9">Security</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Event Date <span class="text-red-500">*</span></span></label>
                            <input id="logistics-event-date" type="date" class="input input-bordered w-full" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Needed By Date <span class="text-red-500">*</span></span></label>
                            <input id="logistics-needed-by-date" type="date" class="input input-bordered w-full" required>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="text-sm font-semibold text-gray-700 mb-3">Requested Items</div>
                    <div class="bg-base-200 rounded-lg p-4 space-y-4">
                        <div id="logistics-items-container" class="space-y-4"></div>
                        <button type="button" id="logistics-add-item-btn" class="btn btn-outline btn-sm w-full">+ Add Another Item</button>
                    </div>
                </div>

                <div>
                    <div class="text-sm font-semibold text-gray-700 mb-3">Delivery Information</div>
                    <div class="bg-blue-50 rounded-lg p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text">Delivery Location <span class="text-red-500">*</span></span></label>
                            <input id="logistics-delivery-location" type="text" class="input input-bordered w-full" required placeholder="E.g., Training Room A, 3rd Floor">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Contact Person <span class="text-red-500">*</span></span></label>
                            <input id="logistics-contact-person" type="text" class="input input-bordered w-full" required placeholder="Name of person to receive items">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="label"><span class="label-text">Remarks</span></label>
                    <textarea id="logistics-remarks" class="textarea textarea-bordered h-24 w-full" placeholder="Additional notes, special handling instructions, or comments..."></textarea>
                </div>
            </form>

            <div class="modal-action">
                <button type="button" id="logistics-cancel-action-btn" class="btn btn-ghost">Cancel</button>
                <button type="button" id="logistics-save-btn" class="btn btn-primary">Save Logistics Request</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <dialog id="facility-request-modal" class="modal">
        <div class="modal-box w-11/12 max-w-4xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-bold text-xl mb-1">Location Request</h3>
                    <p class="text-gray-600">Request venue for training, seminar, or orientation</p>
                </div>
                <button type="button" id="facility-cancel-btn" class="btn btn-ghost btn-sm">✕</button>
            </div>
            <form id="facility-request-form" class="space-y-5">
                <div>
                    <div class="text-sm font-semibold text-gray-700 mb-3">Basic Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text">Training/Seminar Title <span class="text-red-500">*</span></span></label>
                            <input id="facility-title" type="text" class="input input-bordered w-full" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Purpose <span class="text-red-500">*</span></span></label>
                            <input id="facility-purpose" type="text" class="input input-bordered w-full" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Department <span class="text-red-500">*</span></span></label>
                            <select id="facility-department" class="select select-bordered w-full" required>
                                <option value="" selected>Select Department</option>
                                <option value="1">Front Office / Reception</option>
                                <option value="2">Housekeeping</option>
                                <option value="3">Food &amp; Beverage (F&amp;B)</option>
                                <option value="4">Kitchen / Culinary</option>
                                <option value="5">Sales &amp; Marketing</option>
                                <option value="6">Human Resources (HR)</option>
                                <option value="7">Finance / Accounting</option>
                                <option value="8">Engineering / Maintenance</option>
                                <option value="9">Security</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Event Date <span class="text-red-500">*</span></span></label>
                            <input id="facility-event-date" type="date" class="input input-bordered w-full" required>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="text-sm font-semibold text-gray-700 mb-3">Location Details</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text">Preferred Location <span class="text-red-500">*</span></span></label>
                            <select id="facility-preferred-location" class="select select-bordered w-full" required>
                                <option value="" selected>Select Location</option>
                                <option value="Training Room A">Training Room A</option>
                                <option value="Training Room B">Training Room B</option>
                                <option value="Conference Hall">Conference Hall</option>
                                <option value="Auditorium">Auditorium</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label"><span class="label-text">Start Time <span class="text-red-500">*</span></span></label>
                                <input id="facility-start-time" type="time" class="input input-bordered w-full" required>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text">End Time <span class="text-red-500">*</span></span></label>
                                <input id="facility-end-time" type="time" class="input input-bordered w-full" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="label"><span class="label-text">Special Requirements</span></label>
                    <textarea id="facility-special-requirements" class="textarea textarea-bordered h-24 w-full" placeholder="Audio-visual equipment, seating arrangement, internet access, etc."></textarea>
                </div>
                <div>
                    <label class="label"><span class="label-text">Remarks</span></label>
                    <textarea id="facility-remarks" class="textarea textarea-bordered h-24 w-full" placeholder="Additional notes or comments..."></textarea>
                </div>
            </form>

            <div class="modal-action">
                <button type="button" id="facility-cancel-action-btn" class="btn btn-ghost">Cancel</button>
                <button type="button" id="facility-save-btn" class="btn btn-primary">Save Location Request</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <dialog id="objectives-modal" class="modal">
        <div class="modal-box w-11/12 max-w-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-bold text-xl mb-1">Training Objectives</h3>
                    <p class="text-gray-600">Select the objectives for this training program</p>
                </div>
                <button type="button" id="objectives-close-btn" class="btn btn-ghost btn-sm">✕</button>
            </div>

            <div class="mt-4 space-y-4">
                <div>
                    <div class="text-sm font-semibold text-gray-700 mb-2">General</div>
                    <div class="bg-base-200 rounded-lg p-4 space-y-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="checkbox checkbox-sm js-training-objective" value="Improve service quality">
                            <span class="text-sm">Improve service quality</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="checkbox checkbox-sm js-training-objective" value="Enhance safety awareness">
                            <span class="text-sm">Enhance safety awareness</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="checkbox checkbox-sm js-training-objective" value="Standardize procedures">
                            <span class="text-sm">Standardize procedures</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="checkbox checkbox-sm js-training-objective" value="Improve compliance">
                            <span class="text-sm">Improve compliance</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="checkbox checkbox-sm js-training-objective" value="Increase productivity">
                            <span class="text-sm">Increase productivity</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="checkbox checkbox-sm js-training-objective" value="Reduce guest complaints">
                            <span class="text-sm">Reduce guest complaints</span>
                        </label>
                    </div>
                </div>

                <div>
                    <div class="text-sm font-semibold text-gray-700 mb-2">Hotel</div>
                    <div class="bg-base-200 rounded-lg p-4 space-y-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="checkbox checkbox-sm js-training-objective" data-objective-dept="1" value="Front desk check-in/check-out excellence">
                            <span class="text-sm">Front desk check-in/check-out excellence</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="checkbox checkbox-sm js-training-objective" data-objective-dept="1" value="Housekeeping standards and room inspection">
                            <span class="text-sm">Housekeeping standards and room inspection</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="checkbox checkbox-sm js-training-objective" data-objective-dept="1" value="Guest relations and concierge service">
                            <span class="text-sm">Guest relations and concierge service</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="checkbox checkbox-sm js-training-objective" data-objective-dept="1" value="Hotel safety and emergency procedures">
                            <span class="text-sm">Hotel safety and emergency procedures</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="checkbox checkbox-sm js-training-objective" data-objective-dept="1" value="Upselling and reservation handling">
                            <span class="text-sm">Upselling and reservation handling</span>
                        </label>
                    </div>
                </div>

                <div>
                    <div class="text-sm font-semibold text-gray-700 mb-2">Restaurant</div>
                    <div class="bg-base-200 rounded-lg p-4 space-y-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="checkbox checkbox-sm js-training-objective" data-objective-dept="2" value="Table service standards and sequence of service">
                            <span class="text-sm">Table service standards and sequence of service</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="checkbox checkbox-sm js-training-objective" data-objective-dept="2" value="Food handling and hygiene compliance">
                            <span class="text-sm">Food handling and hygiene compliance</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="checkbox checkbox-sm js-training-objective" data-objective-dept="2" value="POS accuracy and cash handling">
                            <span class="text-sm">POS accuracy and cash handling</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="checkbox checkbox-sm js-training-objective" data-objective-dept="2" value="Kitchen workflow and coordination">
                            <span class="text-sm">Kitchen workflow and coordination</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="checkbox checkbox-sm js-training-objective" data-objective-dept="2" value="Restaurant guest complaint handling">
                            <span class="text-sm">Restaurant guest complaint handling</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="modal-action">
                <button type="button" id="objectives-cancel-btn" class="btn btn-ghost">Cancel</button>
                <button type="button" id="objectives-apply-btn" class="btn btn-primary">Apply</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <script>
        window.DEPARTMENT_HEADS = <?= json_encode($departmentHeads, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        window.DEPARTMENT_MANAGERS = <?= json_encode($departmentManagers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="main.js"></script>
    <script src="maintwo.js"></script>
</body>
</html>
