<?php
require_once __DIR__ . '/../../COMPETENCY/criticalgaps/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'get_idp_details') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $idpId = (int)($_POST['idp_id'] ?? 0);
            if ($idpId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid idp_id']);
                exit;
            }
            $stmt = $pdo->prepare("SELECT * FROM requested_idps_repository WHERE id = ? LIMIT 1");
            $stmt->execute([$idpId]);
            $row = $stmt->fetch();
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Not found']);
                exit;
            }
            echo json_encode(['success' => true, 'idp' => $row]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Failed']);
            exit;
        }
    } elseif ($action === 'list_idps') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $stmt = $pdo->prepare(
                "SELECT id, employee_id, employee_name, department, position, competency, succession_status,
                        requested_training_type, requested_training_mode,
                        requested_start_datetime, requested_end_datetime, idp_status,
                        delivery_mode, training_requested_at
                 FROM requested_idps_repository
                 WHERE (delivery_mode IN ('Onsite','Hybrid') AND training_requested_at IS NOT NULL)
                 ORDER BY employee_name ASC"
            );
            $stmt->execute();
            $rows = $stmt->fetchAll();

            try {
                require_once __DIR__ . '/db.php';
                if (isset($conn) && $conn instanceof mysqli) {
                    foreach ($rows as &$r) {
                        $currentStatus = (string)($r['idp_status'] ?? '');
                        $empName = trim((string)($r['employee_name'] ?? ''));
                        if ($empName === '') {
                            continue;
                        }

                        $title = 'IDP Training - ' . $empName;
                        $stmtTp = $conn->prepare(
                            "SELECT status FROM training_programs
                             WHERE training_title = ?
                             LIMIT 1"
                        );
                        if ($stmtTp) {
                            $stmtTp->bind_param('s', $title);
                            $stmtTp->execute();
                            $resTp = $stmtTp->get_result();
                            $tpRow = $resTp ? $resTp->fetch_assoc() : null;
                            if ($tpRow && isset($tpRow['status'])) {
                                $tpStatus = strtolower((string)$tpRow['status']);
                                $mapped = null;
                                if ($tpStatus === 'approved') {
                                    $mapped = 'approved';
                                } elseif ($tpStatus === 'under review') {
                                    $mapped = 'under_review';
                                } elseif ($tpStatus === 'for compliance') {
                                    $mapped = 'for_compliance';
                                } elseif ($tpStatus === 'on hold') {
                                    $mapped = 'on_hold';
                                } elseif ($tpStatus === 'rejected') {
                                    $mapped = 'rejected';
                                }

                                if ($mapped !== null && $mapped !== $currentStatus) {
                                    $upd = $pdo->prepare("UPDATE requested_idps_repository SET idp_status = ? WHERE id = ?");
                                    $upd->execute([$mapped, (int)$r['id']]);
                                    $r['idp_status'] = $mapped;
                                }
                            }
                        }
                    }
                    unset($r);
                }
            } catch (Throwable $e) {
            }

            echo json_encode(['success' => true, 'items' => $rows]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Failed']);
            exit;
        }
    } elseif ($action === 'list_trainees') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            require_once __DIR__ . '/db.php';
            $rows = [];
            $sqlT = "SELECT id, employee_no, first_name, last_name, department, role FROM employees";
            try {
                $chk = $conn->query("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'employees' AND column_name = 'employment_status' LIMIT 1");
                if ($chk && $chk->num_rows > 0) {
                    $sqlT .= " WHERE employment_status = 'New Hire'";
                }
            } catch (Throwable $e2) {
            }
            $sqlT .= " ORDER BY last_name, first_name";
            $resT = $conn->query($sqlT);
            if ($resT && $resT->num_rows > 0) {
                while ($row = $resT->fetch_assoc()) {
                    $rows[] = $row;
                }
            }
            echo json_encode(['success' => true, 'items' => $rows]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'items' => []]);
            exit;
        }
    }
}

require('../../partials/header.php');
?>

<body class="bg-base-200 min-h-screen">
    <div class="flex h-screen">
        <?php include '../../USM/sidebarr.php'; ?>
        <div class="flex flex-col flex-1 overflow-auto">
            <?php include '../../USM/navbar.php'; ?>
            <div class="max-w-7xl mx-auto p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
                    <div>
                        <h1 class="text-2xl font-bold">Training Requests</h1>
                        <div class="text-sm opacity-70">Manage IDP and Trainee training requests.</div>
                    </div>
                </div>

                <div class="mb-4 bg-base-100 rounded-xl shadow w-full">
                    <div class="flex items-center gap-2 p-3">
                        <button type="button" id="tab-page-idp" class="btn btn-sm btn-active">IDP Requests</button>
                        <button type="button" id="tab-page-trainee" class="btn btn-sm btn-ghost">Trainee Requests</button>
                    </div>
                </div>

                <div id="idp-requests-section" class="bg-base-100 rounded-xl shadow w-full">
                    <div class="card-body" style="min-height: 420px;">
                        <form method="get" class="flex flex-col md:flex-row gap-3 md:items-end">
                            <div class="w-full md:w-64">
                                <label class="label"><span class="label-text">Department</span></label>
                                <input type="text" name="department" value="<?php echo htmlspecialchars((string)($_GET['department'] ?? '')); ?>" class="input input-bordered w-full" />
                            </div>
                            <div class="w-full md:w-64">
                                <label class="label"><span class="label-text">Status</span></label>
                                <select name="status" class="select select-bordered w-full">
                                    <?php
                                    $allowedStatuses = ['all', 'requested', 'under_review', 'approved', 'on_hold', 'for_compliance', 'cancelled', 'rejected'];
                                    $status = (string)($_GET['status'] ?? 'all');
                                    if (!in_array($status, $allowedStatuses, true)) $status = 'all';
                                    foreach ($allowedStatuses as $st) {
                                        $sel = $status === $st ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($st) . '" ' . $sel . '>' . htmlspecialchars(ucwords(str_replace('_', ' ', $st))) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="trainingrequest.php" class="btn btn-outline">Reset</a>
                            </div>
                        </form>
                        <div class="overflow-x-auto mt-4">
                            <table class="table table-zebra table-sm">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Position</th>
                                        <th class="text-right">Competency</th>
                                        <th>Succession</th>
                                        <th>Request Type</th>
                                        <th>Mode</th>
                                        <th>Schedule</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        $deptFilter = trim((string)($_GET['department'] ?? ''));
                                        $statusFilter = trim((string)($_GET['status'] ?? 'all'));
                                        $sql = "SELECT id, employee_id, employee_name, department, position, competency, succession_status,
                                                       requested_training_type, requested_training_mode,
                                                       requested_start_datetime, requested_end_datetime, idp_status,
                                                       delivery_mode, training_requested_at
                                                FROM requested_idps_repository";
                                        $conds = [];
                                        $params = [];
                                        if ($deptFilter !== '') {
                                            $conds[] = "department = ?";
                                            $params[] = $deptFilter;
                                        }
                                        if ($statusFilter !== '' && $statusFilter !== 'all') {
                                            $conds[] = "idp_status = ?";
                                            $params[] = $statusFilter;
                                        }
                                        // Only show Onsite/Hybrid requests that have a training request timestamp
                                        $conds[] = "(delivery_mode IN ('Onsite','Hybrid') AND training_requested_at IS NOT NULL)";
                                        if (!empty($conds)) {
                                            $sql .= " WHERE " . implode(" AND ", $conds);
                                        }
                                        $sql .= " ORDER BY employee_name ASC";
                                        $stmt = $pdo->prepare($sql);
                                        $stmt->execute($params);
                                        $rows = $stmt->fetchAll();
                                        if (!$rows) {
                                            echo '<tr><td colspan="9" class="text-center py-10 opacity-70">No requested IDPs found.</td></tr>';
                                        } else {
                                            foreach ($rows as $r) {
                                                $competency = is_numeric($r['competency']) ? number_format((float)$r['competency'], 1) . '%' : '0%';
                                                $sched = '';
                                                if (!empty($r['requested_start_datetime']) && !empty($r['requested_end_datetime'])) {
                                                    $sched = date('M d, Y H:i', strtotime($r['requested_start_datetime'])) . ' - ' . date('M d, Y H:i', strtotime($r['requested_end_datetime']));
                                                } elseif (!empty($r['requested_start_datetime'])) {
                                                    $sched = date('M d, Y H:i', strtotime($r['requested_start_datetime']));
                                                } else {
                                                    $sched = 'N/A';
                                                }
                                                echo '<tr>';
                                                echo '<td><div class="font-semibold">' . htmlspecialchars((string)$r['employee_name']) . '</div><div class="text-xs opacity-70">' . htmlspecialchars((string)$r['employee_id']) . '</div></td>';
                                                echo '<td>' . htmlspecialchars((string)$r['department']) . '</td>';
                                                echo '<td>' . htmlspecialchars((string)$r['position']) . '</td>';
                                                echo '<td class="text-right font-semibold">' . $competency . '</td>';
                                                echo '<td>' . htmlspecialchars((string)$r['succession_status']) . '</td>';
                                                echo '<td>' . htmlspecialchars((string)$r['requested_training_type']) . '</td>';
                                                echo '<td>' . htmlspecialchars((string)$r['requested_training_mode']) . '</td>';
                                                echo '<td>' . htmlspecialchars($sched) . '</td>';
                                                $prefillUrl = 'add_training.php?idp_id=' . urlencode((string)$r['id']);
                                                echo '<td><span class="badge">' . htmlspecialchars((string)$r['idp_status']) . '</span></td>';
                                                echo '</tr><tr><td colspan="9" class="py-2">';
                                                echo '<a href="' . $prefillUrl . '" class="btn btn-sm bg-gray-900 text-white hover:bg-gray-800 border-0">';
                                                echo '<i data-lucide="plus" class="w-4 h-4 mr-2"></i>Create Training</a>';
                                                echo '</td>';
                                                echo '</tr>';
                                            }
                                        }
                                    } catch (Throwable $e) {
                                        echo '<tr><td colspan="9" class="text-center py-10 opacity-70">Failed to load requests.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="trainee-requests-section" class="bg-base-100 rounded-xl shadow hidden w-full">
                    <div class="card-body" style="min-height: 420px;">
                        <div class="flex flex-col md:flex-row md:items-end gap-3 mb-4">
                            <div class="w-full md:w-64">
                                <label class="label"><span class="label-text">Department</span></label>
                                <input type="text" class="input input-bordered w-full" placeholder="All" disabled>
                            </div>
                            <div class="w-full md:w-64">
                                <label class="label"><span class="label-text">Status</span></label>
                                <select class="select select-bordered w-full" disabled>
                                    <option>All</option>
                                </select>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" class="btn btn-primary" disabled>Filter</button>
                                <button type="button" class="btn btn-outline" disabled>Reset</button>
                            </div>
                        </div>

                        <table class="table table-zebra table-sm">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    require_once __DIR__ . '/db.php';
                                    $sqlT = "SELECT id, employee_no, first_name, last_name, department, role FROM employees";
                                    try {
                                        $chk = $conn->query("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'employees' AND column_name = 'employment_status' LIMIT 1");
                                        if ($chk && $chk->num_rows > 0) {
                                            $sqlT .= " WHERE employment_status = 'New Hire'";
                                        }
                                    } catch (Throwable $e2) {
                                    }
                                    $sqlT .= " ORDER BY last_name, first_name";
                                    $resT = $conn->query($sqlT);
                                    if ($resT && $resT->num_rows > 0) {
                                        while ($rowT = $resT->fetch_assoc()) {
                                            $fullName = trim((string)($rowT['last_name'] ?? '') . ', ' . (string)($rowT['first_name'] ?? ''));
                                            $empNo = (string)($rowT['employee_no'] ?? '');
                                            $dept = (string)($rowT['department'] ?? '');
                                            $role = (string)($rowT['role'] ?? '');
                                            $empId = (int)($rowT['id'] ?? 0);
                                            echo '<tr>';
                                            echo '<td><div class="font-semibold">' . htmlspecialchars($fullName) . '</div><div class="text-xs opacity-70">' . htmlspecialchars($empNo) . '</div></td>';
                                            echo '<td>' . htmlspecialchars($dept) . '</td>';
                                            echo '<td>' . htmlspecialchars($role) . '</td>';
                                            echo '<td><button type="button" class="btn btn-sm bg-gray-900 text-white hover:bg-gray-800 border-0" data-assign-trainee-id="' . $empId . '" data-trainee-name="' . htmlspecialchars($fullName) . '" data-trainee-empno="' . htmlspecialchars($empNo) . '">Assign Training</button></td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="4" class="text-center text-sm opacity-70">No trainee records found.</td></tr>';
                                    }
                                } catch (Throwable $e) {
                                    echo '<tr><td colspan="4" class="text-center text-sm opacity-70">Failed to load trainees.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <dialog id="assign-training-modal" class="modal">
                <div class="modal-box w-11/12 max-w-5xl">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold" id="assign-modal-title">Assign Training</h2>
                            <div class="text-sm text-gray-600 mt-1" id="assign-modal-subtitle"></div>
                        </div>
                        <button type="button" class="btn btn-ghost btn-sm" id="assign-modal-close">✕</button>
                    </div>
                    <div class="mt-4">
                        <div id="assign-training-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm text-gray-800">
                            <div class="col-span-full text-center py-6 opacity-70">Loading posted trainings...</div>
                        </div>
                    </div>
                </div>
                <form method="dialog" class="modal-backdrop"><button>close</button></form>
            </dialog>
        </div>
    </div>
    </div>
    <script>
        if (window.lucide) lucide.createIcons();
        document.addEventListener('DOMContentLoaded', function() {
            var tabIdp = document.getElementById('tab-page-idp');
            var tabTrainee = document.getElementById('tab-page-trainee');
            var idpSection = document.getElementById('idp-requests-section');
            var traineeSection = document.getElementById('trainee-requests-section');
            var assignModal = document.getElementById('assign-training-modal');
            var assignClose = document.getElementById('assign-modal-close');
            var assignTitle = document.getElementById('assign-modal-title');
            var assignSubtitle = document.getElementById('assign-modal-subtitle');
            var assignList = document.getElementById('assign-training-list');
            var activeTraineeId = 0;

            function setTab(tab) {
                var isIdp = tab === 'idp';
                if (tabIdp) {
                    if (isIdp) {
                        tabIdp.classList.add('btn-active');
                        tabIdp.classList.remove('btn-ghost');
                    } else {
                        tabIdp.classList.remove('btn-active');
                        tabIdp.classList.add('btn-ghost');
                    }
                }
                if (tabTrainee) {
                    if (!isIdp) {
                        tabTrainee.classList.add('btn-active');
                        tabTrainee.classList.remove('btn-ghost');
                    } else {
                        tabTrainee.classList.remove('btn-active');
                        tabTrainee.classList.add('btn-ghost');
                    }
                }
                if (idpSection) idpSection.classList.toggle('hidden', !isIdp);
                if (traineeSection) traineeSection.classList.toggle('hidden', isIdp);
            }

            function loadPostedTrainings() {
                if (!assignList) return;
                assignList.innerHTML = '<div class="col-span-full text-center py-6 opacity-70">Loading posted trainings...</div>';
                var url = 'posted_trainings.php?action=list_posts&filter=department';
                fetch(url, {
                        credentials: 'same-origin'
                    })
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(data) {
                        var items = data && data.success && Array.isArray(data.posts) ? data.posts : [];
                        if (!items.length) {
                            assignList.innerHTML = '<div class="col-span-full text-center py-6 opacity-70">No posted trainings found.</div>';
                            return;
                        }
                        assignList.innerHTML = items.map(function(p) {
                            var title = String(p.training_title || '');
                            var type = String(p.training_type || '');
                            var audience = String(p.target_audience || '');
                            var sched = String(p.start_datetime || '') + ' to ' + String(p.end_datetime || '');
                            var postedAt = String(p.posted_at || '');
                            var programId = String(p.program_id || '');
                            var submissionNo = String(p.submission_no || '1');
                            return '' +
                                '<div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 flex flex-col justify-between">' +
                                '<div>' +
                                '<div class="font-semibold text-sm text-gray-900">' + title.replace(/</g, '&lt;') + '</div>' +
                                '<div class="mt-1 text-xs text-gray-600">Type: ' + type.replace(/</g, '&lt;') + '</div>' +
                                '<div class="mt-1 text-xs text-gray-600">Audience: ' + audience.replace(/</g, '&lt;') + '</div>' +
                                '<div class="mt-1 text-xs text-gray-600">Schedule: ' + sched.replace(/</g, '&lt;') + '</div>' +
                                '<div class="mt-1 text-xs text-gray-500">Posted: ' + postedAt.replace(/</g, '&lt;') + '</div>' +
                                '</div>' +
                                '<div class="mt-3 flex justify-end">' +
                                '<button type="button" class="btn btn-sm hr2-primary-btn" data-assign-program-id="' + programId + '" data-submission-no="' + submissionNo + '">Assign</button>' +
                                '</div>' +
                                '</div>';
                        }).join('');
                    })
                    .catch(function() {
                        assignList.innerHTML = '<div class="col-span-full text-center py-6 opacity-70">Failed to load posted trainings.</div>';
                    });
            }

            function openAssignModal(traineeId, name, empNo) {
                activeTraineeId = traineeId > 0 ? traineeId : 0;
                if (assignTitle) {
                    assignTitle.textContent = 'Assign Training';
                }
                if (assignSubtitle) {
                    assignSubtitle.textContent = name + ' (' + empNo + ')';
                }
                loadPostedTrainings();
                if (assignModal && typeof assignModal.showModal === 'function') {
                    assignModal.showModal();
                }
            }

            function assignToProgram(programId, submissionNo) {
                if (!activeTraineeId || activeTraineeId <= 0) {
                    alert('Cannot assign training to this sample trainee.');
                    return;
                }
                var pid = String(programId || '');
                var subNo = String(submissionNo || '1');
                var getUrl = 'posted_trainings.php?action=get_assignments&program_id=' + encodeURIComponent(pid) + '&submission_no=' + encodeURIComponent(subNo);
                fetch(getUrl, {
                        credentials: 'same-origin'
                    })
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(data) {
                        var ids = (data && data.success && Array.isArray(data.employee_ids)) ? data.employee_ids.slice() : [];
                        var tid = activeTraineeId;
                        var has = ids.some(function(n) {
                            return parseInt(n, 10) === tid;
                        });
                        if (!has) ids.push(tid);
                        var fd = new FormData();
                        fd.append('action', 'save_assignments');
                        fd.append('program_id', pid);
                        fd.append('submission_no', subNo);
                        fd.append('employee_ids_json', JSON.stringify(ids));
                        return fetch('posted_trainings.php', {
                            method: 'POST',
                            body: fd,
                            credentials: 'same-origin'
                        });
                    })
                    .then(function(res) {
                        if (!res) return null;
                        return res.json();
                    })
                    .then(function(r) {
                        if (!r) return;
                        if (!r.success) {
                            alert((r && r.message) ? r.message : 'Failed to assign training.');
                            return;
                        }
                        alert('Training assigned successfully.');
                        if (assignModal) assignModal.close();
                    })
                    .catch(function() {
                        alert('Failed to assign training.');
                    });
            }

            if (tabIdp) {
                tabIdp.addEventListener('click', function(e) {
                    e.preventDefault();
                    setTab('idp');
                });
            }
            if (tabTrainee) {
                tabTrainee.addEventListener('click', function(e) {
                    e.preventDefault();
                    setTab('trainee');
                });
            }
            setTab('idp');

            if (assignClose && assignModal) {
                assignClose.addEventListener('click', function() {
                    assignModal.close();
                });
            }

            document.addEventListener('click', function(e) {
                var t = e.target;
                if (!t) return;
                var btnAssignTrainee = t.closest('button[data-assign-trainee-id]');
                if (btnAssignTrainee) {
                    e.preventDefault();
                    var traineeId = parseInt(btnAssignTrainee.getAttribute('data-assign-trainee-id') || '0', 10);
                    var name = btnAssignTrainee.getAttribute('data-trainee-name') || '';
                    var empNo = btnAssignTrainee.getAttribute('data-trainee-empno') || '';
                    openAssignModal(traineeId, name, empNo);
                    return;
                }
                var btnAssignProgram = t.closest('button[data-assign-program-id]');
                if (btnAssignProgram) {
                    e.preventDefault();
                    var programId = btnAssignProgram.getAttribute('data-assign-program-id') || '';
                    var submissionNo = btnAssignProgram.getAttribute('data-submission-no') || '1';
                    assignToProgram(programId, submissionNo);
                }
            });
        });
    </script>
    <script src="../../soliera.js"></script>
    <script src="../../sidebar.js"></script>
</body>

</html>