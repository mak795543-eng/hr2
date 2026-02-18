<?php
session_start();
require_once __DIR__ . '/../../COMPETENCY/criticalgaps/config.php';

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function normalize_department_slug(string $v): string
{
    $s = mb_strtolower(trim($v));
    if ($s === '') return '';

    if ($s === 'hr' || (str_contains($s, 'human') && str_contains($s, 'resource'))) return 'hr';
    if (str_contains($s, 'front') && str_contains($s, 'office')) return 'front-office';
    if (str_contains($s, 'house')) return 'housekeeping';
    if (str_contains($s, 'food') || str_contains($s, 'beverage') || str_contains($s, 'f&b')) return 'food-beverage';
    if (str_contains($s, 'kitchen') || str_contains($s, 'culinary')) return 'kitchen';
    if (str_contains($s, 'sales') || str_contains($s, 'marketing')) return 'sales-marketing';
    if (str_contains($s, 'finance') || str_contains($s, 'accounting')) return 'finance';
    if (str_contains($s, 'engineering') || str_contains($s, 'maintenance')) return 'engineering';
    if (str_contains($s, 'security')) return 'security';

    $s = preg_replace('/\([^\)]*\)/', '', $s);
    $s = str_replace(['&', '/', '_'], ['and', '-', '-'], $s);
    $s = preg_replace('/[^a-z0-9\s\-]/', '', $s);
    $s = preg_replace('/\s+/', '-', $s);
    $s = preg_replace('/-+/', '-', $s);
    return trim($s, '-');
}


$requests = [];
try {
    $sql = "SELECT id, employee_id, employee_name, position, department, succession_status, idp_status, delivery_mode,
                   development_plan,
                   learning_requested_at, training_requested_at
            FROM requested_idps_repository
            WHERE idp_status = 'requested'
              AND delivery_mode IN ('Online','Hybrid')
              AND learning_requested_at IS NOT NULL
            ORDER BY COALESCE(learning_requested_at, training_requested_at, updated_at) DESC";
    $stmt = $pdo->query($sql);
    $requests = $stmt ? ($stmt->fetchAll() ?: []) : [];
} catch (Throwable $e) {
    $requests = [];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Modules</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>

<body class="bg-gray-50 min-h-screen">
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

            <main class="container mx-auto px-4 py-6">
                <div class="flex items-center justify-between gap-3 mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Learning Requests</h1>
                        <p class="text-sm text-gray-500">Requested learnings from approved Individual Development Plans (Online / Hybrid)</p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-4">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Employee</th>
                                <th>Employee ID</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Mode</th>
                                <th>Requested</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-gray-500 py-6">No learning requests yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($requests as $i => $r): ?>
                                    <?php
                                    $idpId = (int)($r['id'] ?? 0);
                                    $deptSlug = normalize_department_slug((string)($r['department'] ?? ''));
                                    $roleName = (string)($r['position'] ?? '');
                                    ?>
                                    <tr>
                                        <td><?= (int)($i + 1) ?></td>
                                        <td><?= h($r['employee_name'] ?? '') ?></td>
                                        <td><?= h($r['employee_id'] ?? '') ?></td>
                                        <td><?= h($r['department'] ?? '') ?></td>
                                        <td><?= h($r['position'] ?? '') ?></td>
                                        <td><?= h($r['delivery_mode'] ?? '') ?></td>
                                        <td><?= h($r['learning_requested_at'] ?? ($r['training_requested_at'] ?? '')) ?></td>
                                        <td>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-primary"
                                                onclick='openRequestModal(<?php echo json_encode([
                                                                                "idp_id" => $idpId,
                                                                                "employee_id" => (string)($r["employee_id"] ?? ""),
                                                                                "employee_name" => (string)($r["employee_name"] ?? ""),
                                                                                "department" => (string)($r["department"] ?? ""),
                                                                                "department_slug" => $deptSlug,
                                                                                "position" => (string)($r["position"] ?? ""),
                                                                                "role" => $roleName,
                                                                                "delivery_mode" => (string)($r["delivery_mode"] ?? ""),
                                                                                "succession_status" => (string)($r["succession_status"] ?? ""),
                                                                                "idp_status" => (string)($r["idp_status"] ?? ""),
                                                                                "requested_at" => (string)($r["learning_requested_at"] ?? ($r["training_requested_at"] ?? "")),
                                                                                "development_plan" => (string)($r["development_plan"] ?? ""),
                                                                            ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>View</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <dialog id="request_modal" class="modal">
        <div class="modal-box max-w-2xl">
            <h3 class="font-bold text-lg mb-3">IDP Preview</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                <div class="bg-base-200 rounded-lg p-3">
                    <div class="text-xs opacity-70">Employee</div>
                    <div class="font-semibold" id="m_employee"></div>
                    <div class="text-xs opacity-70" id="m_employee_id"></div>
                </div>
                <div class="bg-base-200 rounded-lg p-3">
                    <div class="text-xs opacity-70">Department / Role</div>
                    <div class="font-semibold" id="m_department"></div>
                    <div class="text-xs opacity-70" id="m_role"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                <div class="bg-base-200 rounded-lg p-3">
                    <div class="text-xs opacity-70">Mode / Status</div>
                    <div class="font-semibold" id="m_mode"></div>
                    <div class="text-xs opacity-70" id="m_status"></div>
                </div>
                <div class="bg-base-200 rounded-lg p-3">
                    <div class="text-xs opacity-70">Requested</div>
                    <div class="font-semibold" id="m_requested_at"></div>
                    <div class="text-xs opacity-70" id="m_succession"></div>
                </div>
            </div>

            <div class="mt-5">
                <div class="text-sm font-semibold mb-2">Online Development Plans</div>
                <div id="m_plans" class="space-y-2"></div>
            </div>

            <div class="modal-action">
                <form method="dialog"><button class="btn">Close</button></form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <dialog id="create_module_modal" class="modal modal-middle">
        <div class="modal-box max-w-4xl">
            <h3 class="font-bold text-lg mb-6">Upload Learning Module</h3>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div>
                    <h4 class="text-lg font-medium mb-4">Upload File <span class="text-gray-500 text-sm italic">(Optional)</span></h4>
                    <div id="cm_dropZone" class="border-2 border-dashed border-gray-300 rounded-lg transition-all duration-300 relative p-8 text-center cursor-pointer mb-4">
                        <div class="flex flex-col items-center justify-center gap-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <p class="text-lg">Drag and drop files here</p>
                            <p class="text-sm text-gray-500">Optional - you can create content manually</p>
                            <button type="button" class="btn btn-outline mt-2" id="cm_browse_btn">Browse Files</button>
                        </div>
                        <input type="file" id="cm_fileInput" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.rtf,.html,.htm,.xls,.xlsx,.csv">
                    </div>

                    <div class="mt-4">
                        <div class="text-sm text-gray-500 mb-2">Uploaded file:</div>
                        <div class="space-y-2" id="cm_fileList"></div>
                    </div>
                </div>

                <div>
                    <h4 class="text-lg font-medium mb-4">Module Details</h4>
                    <form class="space-y-4" id="cm_form">
                        <div class="form-control">
                            <label class="label"><span class="label-text">Title</span></label>
                            <input type="text" id="cm_title" class="input input-bordered" placeholder="Enter module title" required>
                        </div>

                        <div class="form-control">
                            <label class="label"><span class="label-text">Department</span></label>
                            <select class="select select-bordered" id="departmentSelect" required>
                                <option disabled selected>Select Department</option>
                                <option value="front-office">Front Office / Reception</option>
                                <option value="housekeeping">Housekeeping</option>
                                <option value="food-beverage">Food &amp; Beverage (F&amp;B)</option>
                                <option value="kitchen">Kitchen / Culinary</option>
                                <option value="sales-marketing">Sales &amp; Marketing</option>
                                <option value="hr">Human Resources (HR)</option>
                                <option value="finance">Finance / Accounting</option>
                                <option value="engineering">Engineering / Maintenance</option>
                                <option value="security">Security</option>
                            </select>
                        </div>

                        <div class="form-control">
                            <label class="label"><span class="label-text">Role</span></label>
                            <select class="select select-bordered" id="roleSelect" disabled required>
                                <option disabled selected>Select Department First</option>
                            </select>
                        </div>

                        <div class="form-control">
                            <label class="label"><span class="label-text">Topic</span></label>
                            <textarea id="cm_topic" class="textarea textarea-bordered" placeholder="Enter topic" rows="2" required></textarea>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal-action mt-6">
                <form method="dialog">
                    <button class="btn btn-outline">Cancel</button>
                </form>
                <button type="button" class="btn btn-primary" id="cm_create_btn">
                    <i class="fas fa-play mr-2"></i>Create Module
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <script>
        const requestModal = document.getElementById('request_modal');
        const createModuleModal = document.getElementById('create_module_modal');
        const cmTitle = document.getElementById('cm_title');
        const cmTopic = document.getElementById('cm_topic');
        const cmCreateBtn = document.getElementById('cm_create_btn');
        const cmDept = document.getElementById('departmentSelect');
        const cmRole = document.getElementById('roleSelect');
        const cmDropZone = document.getElementById('cm_dropZone');
        const cmFileInput = document.getElementById('cm_fileInput');
        const cmFileList = document.getElementById('cm_fileList');
        const cmBrowseBtn = document.getElementById('cm_browse_btn');
        const departmentRoles = {
            'front-office': [
                'Front Desk Officer',
                'Receptionist',
                'Bellman',
                'Concierge',
                'Guest Relations Officer',
                'Front Office Supervisor',
                'Reservation Agent',
                'Porter'
            ],
            'housekeeping': [
                'Housekeeping Attendant',
                'Room Attendant',
                'Public Area Cleaner',
                'Laundry Staff',
                'Housekeeping Supervisor',
                'Housekeeping Manager',
                'Linen Attendant'
            ],
            'food-beverage': [
                'Service Crew',
                'Waiter/Waitress',
                'Bartender',
                'Restaurant Supervisor',
                'Banquet Staff',
                'F&B Manager',
                'Barista'
            ],
            'kitchen': [
                'Sous Chef',
                'Line Cook',
                'Pastry Chef',
                'Kitchen Helper',
                'Head Chef',
                'Dishwasher',
                'Commis Chef'
            ],
            'sales-marketing': [
                'Sales Executive',
                'Marketing Officer',
                'Digital Marketing Specialist',
                'Sales Manager',
                'Events Coordinator',
                'PR Officer'
            ],
            'hr': [
                'HR Manager / Director',
                'Recruitment Officer',
                'Training & Development Specialist',
                'Payroll / HR Assistant',
                'HR Coordinator',
                'Employee Relations Specialist'
            ],
            'finance': [
                'Finance Manager / Controller',
                'Accountant',
                'Payroll Officer',
                'Cost Controller',
                'Accounts Payable/Receivable Clerk',
                'Financial Analyst'
            ],
            'engineering': [
                'Chief Engineer / Engineering Manager',
                'Maintenance Technician',
                'Electrician / Plumber',
                'HVAC Technician',
                'Carpenter',
                'Painter'
            ],
            'security': [
                'Security Manager / Supervisor',
                'Security Guard',
                'CCTV / Surveillance Officer',
                'Security Officer',
                'Surveillance Operator',
                'Access Control Officer'
            ]
        };

        function populateRoles(dept) {
            if (!cmRole) return;
            cmRole.innerHTML = '';
            const list = departmentRoles[String(dept || '')] || [];
            if (!dept || list.length === 0) {
                cmRole.disabled = true;
                const opt = document.createElement('option');
                opt.disabled = true;
                opt.selected = true;
                opt.textContent = 'Select Department First';
                opt.value = '';
                cmRole.appendChild(opt);
                return;
            }
            cmRole.disabled = false;
            const opt0 = document.createElement('option');
            opt0.disabled = true;
            opt0.selected = true;
            opt0.textContent = 'Select Role';
            opt0.value = '';
            cmRole.appendChild(opt0);
            list.forEach(function(r) {
                const o = document.createElement('option');
                o.value = r;
                o.textContent = r;
                cmRole.appendChild(o);
            });
        }

        if (cmDept) {
            cmDept.addEventListener('change', function() {
                populateRoles(cmDept.value);
            });
        }

        function openCreateModuleModal(payload, planTitle) {
            if (!createModuleModal || !cmTitle || !cmDept || !cmRole || !cmTopic) return;
            try {
                if (requestModal && typeof requestModal.close === 'function') requestModal.close();
            } catch (e) {}

            let deptSlug = String(payload && payload.department_slug ? payload.department_slug : '').trim();
            if (deptSlug === 'human-resources') deptSlug = 'hr';
            const roleName = String(payload && payload.role ? payload.role : '').trim();
            const titleStr = String(planTitle || '').trim();

            cmTitle.value = titleStr;
            cmTopic.value = titleStr;

            if (deptSlug !== '') {
                cmDept.value = deptSlug;
                populateRoles(deptSlug);
            } else {
                cmDept.value = '';
                populateRoles('');
            }

            if (roleName !== '' && !cmRole.disabled) {
                let found = false;
                Array.from(cmRole.options || []).forEach(function(opt) {
                    if (String(opt.value || '') === roleName) found = true;
                });
                if (!found) {
                    const opt = document.createElement('option');
                    opt.value = roleName;
                    opt.textContent = roleName;
                    cmRole.appendChild(opt);
                }
                cmRole.value = roleName;
            }

            const lock = deptSlug !== '' && roleName !== '';
            cmDept.disabled = lock;
            cmRole.disabled = lock ? true : cmRole.disabled;

            if (cmFileInput) cmFileInput.value = '';
            if (cmFileList) cmFileList.innerHTML = '';

            createModuleModal.showModal();
        }

        if (cmCreateBtn) {
            cmCreateBtn.addEventListener('click', function() {
                if (!cmTitle || !cmDept || !cmRole || !cmTopic) return;
                const title = String(cmTitle.value || '').trim();
                const dept = String(cmDept.value || '').trim();
                const role = String(cmRole.value || '').trim();
                const topic = String(cmTopic.value || '').trim();
                if (title === '' || dept === '' || role === '' || topic === '') return;
                const qs = new URLSearchParams();
                qs.set('title', title);
                qs.set('department', dept);
                qs.set('role', role);
                qs.set('topic', topic);
                window.location.href = 'create_learning_modules.php?' + qs.toString();
            });
        }

        if (cmBrowseBtn && cmFileInput) {
            cmBrowseBtn.addEventListener('click', function() {
                cmFileInput.click();
            });
        }

        if (cmFileInput && cmFileList) {
            cmFileInput.addEventListener('change', function() {
                cmFileList.innerHTML = '';
                const f = cmFileInput.files && cmFileInput.files.length ? cmFileInput.files[0] : null;
                if (!f) return;
                const item = document.createElement('div');
                item.className = 'flex items-center justify-between p-3 bg-slate-50 rounded-md';
                item.innerHTML = '<div><div class="font-medium">' + String(f.name || '') + '</div><div class="text-sm text-gray-500">' + Math.round((f.size || 0) / 1024) + ' KB</div></div>';
                cmFileList.appendChild(item);
            });
        }

        window.openRequestModal = function(payload) {
            if (!payload || !requestModal) return;
            document.getElementById('m_employee').textContent = payload.employee_name || '';
            document.getElementById('m_employee_id').textContent = payload.employee_id || '';
            document.getElementById('m_department').textContent = payload.department || '';
            document.getElementById('m_role').textContent = payload.role || payload.position || '';
            document.getElementById('m_mode').textContent = payload.delivery_mode || '';
            document.getElementById('m_status').textContent = payload.idp_status || '';
            document.getElementById('m_requested_at').textContent = payload.requested_at || '';
            document.getElementById('m_succession').textContent = payload.succession_status || '';

            const plansEl = document.getElementById('m_plans');
            if (plansEl) {
                plansEl.innerHTML = '';
                const dm = String(payload.delivery_mode || '');
                const rawPlan = String(payload.development_plan || '');
                const planItems = rawPlan
                    .split(/\r?\n/)
                    .map(function(l) {
                        return String(l || '').trim();
                    })
                    .filter(function(l) {
                        return l.indexOf('- ') === 0;
                    })
                    .map(function(l) {
                        return l.slice(2).trim();
                    })
                    .filter(function(l) {
                        return l !== '';
                    });

                if (dm !== 'Online' && dm !== 'Hybrid') {
                    plansEl.innerHTML = '<div class="text-sm opacity-70">Development plans are shown only for Online/Hybrid IDPs.</div>';
                } else if (planItems.length === 0) {
                    plansEl.innerHTML = '<div class="text-sm opacity-70">No development plans found.</div>';
                } else {
                    planItems.forEach(function(itemText) {
                        const row = document.createElement('div');
                        row.className = 'flex items-center justify-between gap-3 bg-base-200 rounded-lg p-3';

                        const left = document.createElement('div');
                        left.className = 'text-sm';
                        left.textContent = itemText;

                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'btn btn-sm btn-outline';
                        btn.textContent = 'Create Module';
                        btn.addEventListener('click', function() {
                            openCreateModuleModal(payload, itemText);
                        });

                        row.appendChild(left);
                        row.appendChild(btn);
                        plansEl.appendChild(row);
                    });
                }
            }

            requestModal.showModal();
        };
    </script>
    <script src="../../soliera.js"></script>
    <script src="../../sidebar.js"></script>
</body>

</html>
