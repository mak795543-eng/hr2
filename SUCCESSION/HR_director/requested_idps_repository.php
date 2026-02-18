<?php

require_once __DIR__ . '/../../COMPETENCY/criticalgaps/config.php';

$flashOk = (string)($_GET['ok'] ?? '');
$flashErr = (string)($_GET['err'] ?? '');

$period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $idpId = (int)($_POST['idp_id'] ?? 0);

    if ($action === 'get_general_skills') {
        header('Content-Type: application/json; charset=utf-8');
        $employeeId = trim((string)($_POST['employee_id'] ?? ''));
        $department = trim((string)($_POST['department'] ?? ''));

        if ($employeeId === '' || $department === '') {
            echo json_encode(['success' => false, 'message' => 'Missing request.']);
            exit;
        }

        try {
            $stmtSkills = $pdo->prepare(
                "SELECT k.kpi_name AS skill_name,
                        AVG(COALESCE(s.score, 0)) / 5 * 100 AS skill_score,
                        NULL AS assessment_date
                 FROM employee_kpi_scores s
                 JOIN kpis k
                   ON k.id = s.kpi_id
                 WHERE s.employee_id = ?
                   AND s.evaluation_period = ?
                 GROUP BY k.kpi_name
                 ORDER BY k.kpi_name ASC"
            );
            seedMissingKpiEvaluations($employeeId, $period);
            $stmtSkills->execute([$employeeId, $period]);
            $skills = $stmtSkills->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'skills' => $skills]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to load skills.']);
            exit;
        }
    }

    if ($idpId <= 0) {
        header('Location: requested_idps_repository.php?err=invalid');
        exit;
    }

    try {
        if ($action === 'mark_approved' || $action === 'cancel_request') {
            $newStatus = $action === 'mark_approved' ? 'approved' : 'cancelled';

            $pdo->beginTransaction();

            $stmtFetch = $pdo->prepare(
                "SELECT *
                 FROM requested_idps_repository
                 WHERE id = ? AND idp_status = 'requested'
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmtFetch->execute([$idpId]);
            $row = $stmtFetch->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $pdo->rollBack();
                header('Location: requested_idps_repository.php?err=invalid');
                exit;
            }

            $now = (new DateTime())->format('Y-m-d H:i:s');

            $stmtInsert = $pdo->prepare(
                "INSERT INTO individual_development_plans
                    (id, employee_id, employee_name, position, department, competency, succession_status,
                     development_plan, target_score, target_date, delivery_mode,
                     requested_training_type, requested_training_mode, requested_start_datetime, requested_end_datetime,
                     idp_status, training_requested_at, learning_requested_at, created_at, updated_at)
                 VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmtInsert->execute([
                (int)$row['id'],
                $row['employee_id'],
                $row['employee_name'],
                $row['position'],
                $row['department'],
                $row['competency'],
                $row['succession_status'],
                $row['development_plan'],
                $row['target_score'],
                $row['target_date'],
                $row['delivery_mode'],
                $row['requested_training_type'],
                $row['requested_training_mode'],
                $row['requested_start_datetime'],
                $row['requested_end_datetime'],
                $newStatus,
                $row['training_requested_at'],
                $row['learning_requested_at'],
                $row['created_at'],
                $now,
            ]);

            $stmtDel = $pdo->prepare("DELETE FROM requested_idps_repository WHERE id = ?");
            $stmtDel->execute([$idpId]);

            $pdo->commit();

            header('Location: requested_idps_repository.php?ok=' . ($action === 'mark_approved' ? 'approved' : 'cancelled'));
            exit;
        }

        header('Location: requested_idps_repository.php?err=invalid');
        exit;
    } catch (Throwable $e) {
        error_log('requested_idps_repository action error: ' . $e->getMessage());
        header('Location: requested_idps_repository.php?err=failed');
        exit;
    }
}

$stmt = $pdo->prepare(
    "SELECT idp.id,
            idp.employee_id,
            idp.employee_name,
            idp.position,
            idp.department,
            COALESCE(gs.competency, 0) AS competency,
            idp.succession_status,
            idp.development_plan,
            idp.target_score,
            idp.target_date,
            idp.idp_status,
            idp.delivery_mode,
            idp.training_requested_at,
            idp.learning_requested_at,
            idp.created_at,
            idp.updated_at
     FROM requested_idps_repository idp
     LEFT JOIN (
         SELECT s2.employee_id, AVG(COALESCE(s2.score, 0)) / 5 * 100 AS competency
         FROM employee_kpi_scores s2
         WHERE s2.evaluation_period = ?
         GROUP BY s2.employee_id
     ) gs ON gs.employee_id = idp.employee_id
     WHERE idp.idp_status = 'requested'
     ORDER BY COALESCE(idp.learning_requested_at, idp.training_requested_at, idp.updated_at) DESC"
);
$stmt->execute([$period]);
$rows = $stmt->fetchAll();

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function statusLabel($status)
{
    $status = (string)$status;
    $status = str_replace('_', ' ', $status);
    return ucwords($status);
}

function badgeClass($status)
{
    $status = (string)$status;
    switch ($status) {
        case 'requested':
            return 'badge-accent';
        case 'approved':
            return 'badge-success';
        case 'on_hold':
            return 'badge-warning';
        case 'for_compliance':
            return 'badge-info';
        case 'cancelled':
            return 'badge-neutral';
        case 'rejected':
            return 'badge-error';
        case 'under_review':
        default:
            return 'badge-primary';
    }
}
require('../../partials/header.php');
?>
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


            <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
                <div class="max-w-7xl mx-auto p-6 space-y-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold">Requested IDPs</h1>
                            <div class="text-sm opacity-70">Total: <span class="font-semibold"><?php echo count($rows); ?></span></div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="individual_development_plans.php" class="btn btn-outline btn-sm">IDP Repository</a>
                            <a href="review_page_idp.php" class="btn btn-outline btn-sm">Review Page</a>
                            <a href="succession_dashboard.php" class="btn btn-outline btn-sm">Dashboard</a>
                        </div>
                    </div>

                    <script>
                        (function() {
                            var ok = <?php echo json_encode($flashOk); ?>;
                            var err = <?php echo json_encode($flashErr); ?>;

                            var okMap = {
                                approved: 'IDP moved to Approved.',
                                cancelled: 'Request cancelled.'
                            };

                            if (ok) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: okMap[ok] || 'Action completed.',
                                    timer: 1600,
                                    showConfirmButton: false
                                });
                            }

                            if (err) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Something went wrong.'
                                });
                            }
                        })();
                    </script>

                    <?php if (count($rows) === 0): ?>
                        <div class="card bg-base-100 shadow-md">
                            <div class="card-body">
                                <div class="opacity-70">No requested IDPs yet.</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 items-start">
                            <?php foreach ($rows as $r): ?>
                                <?php $status = (string)($r['idp_status'] ?? 'requested'); ?>
                                <div class="card bg-base-100 shadow-md">
                                    <div class="card-body">
                                        <div class="flex justify-between items-start">
                                            <h3 class="card-title"><?php echo h($r['employee_name']); ?></h3>
                                            <div class="badge badge-sm <?php echo h(badgeClass($status)); ?>"><?php echo h(statusLabel($status)); ?></div>
                                        </div>

                                        <div class="flex flex-wrap gap-2 my-2">
                                            <div class="badge badge-outline"><?php echo h($r['department']); ?></div>
                                            <div class="badge badge-outline"><?php echo h($r['position']); ?></div>
                                            <div class="badge badge-outline"><?php echo h($r['delivery_mode'] ?? ''); ?></div>
                                            <div class="badge badge-outline"><?php echo h($r['succession_status'] ?? ''); ?></div>
                                        </div>

                                        <p class="text-sm text-gray-500">Employee ID: <?php echo h($r['employee_id']); ?></p>
                                        <p class="text-sm text-gray-500">Requested: <?php echo h($r['learning_requested_at'] ?? $r['training_requested_at'] ?? $r['updated_at'] ?? ''); ?></p>

                                        <div class="card-actions justify-end mt-4">
                                            <button type="button" class="btn btn-outline btn-sm" data-view-idp="1" data-idp='<?php echo h(json_encode($r)); ?>'>View</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <input type="checkbox" id="idp_view_modal" class="modal-toggle" />
                <div class="modal" role="dialog">
                    <div class="modal-box w-11/12 max-w-6xl max-h-[85vh] overflow-y-auto">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="font-bold text-lg">IDP Details</h3>
                            <span id="idp_view_status_badge" class="badge badge-sm"></span>
                        </div>

                        <div class="mt-4" id="idp_view_body"></div>

                        <div class="mt-4">
                            <div class="text-sm font-semibold">Development Plan</div>
                            <div id="idp_view_plan" class="max-h-60 overflow-auto rounded-lg bg-base-200 p-3 mt-2">
                                <div id="idp_view_plan_bubbles" class="flex flex-wrap gap-2"></div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="text-sm font-semibold">General Skills Breakdown</div>
                            <div id="idp_view_skills" class="max-h-60 overflow-auto rounded-lg bg-base-200 p-3 mt-2">
                                <div class="text-sm opacity-70" id="idp_view_skills_status">â€”</div>
                            </div>
                        </div>

                        <div class="modal-action flex flex-wrap justify-between gap-2">
                            <div class="flex flex-wrap gap-2">
                                <form method="post" class="inline" data-swal-confirm="Mark this requested IDP as Approved?">
                                    <input type="hidden" name="action" value="mark_approved" />
                                    <input type="hidden" name="idp_id" id="idp_view_id_approve" value="" />
                                    <button type="submit" class="btn btn-success btn-sm">Mark Approved</button>
                                </form>

                                <form method="post" class="inline" data-swal-confirm="Cancel this request?">
                                    <input type="hidden" name="action" value="cancel_request" />
                                    <input type="hidden" name="idp_id" id="idp_view_id_cancel" value="" />
                                    <button type="submit" class="btn btn-outline btn-sm">Cancel Request</button>
                                </form>
                            </div>

                            <label for="idp_view_modal" class="btn">Close</label>
                        </div>
                    </div>
                    <label class="modal-backdrop" for="idp_view_modal">Close</label>
                </div>

                <script>
                    (function() {
                        document.querySelectorAll('form[data-swal-confirm]').forEach(function(form) {
                            form.addEventListener('submit', function(e) {
                                e.preventDefault();
                                var msg = form.getAttribute('data-swal-confirm') || 'Are you sure?';
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Please confirm',
                                    text: msg,
                                    showCancelButton: true,
                                    confirmButtonText: 'Yes',
                                    cancelButtonText: 'No'
                                }).then(function(res) {
                                    if (res.isConfirmed) {
                                        form.submit();
                                    }
                                });
                            });
                        });

                        var modalToggle = document.getElementById('idp_view_modal');
                        var viewBadge = document.getElementById('idp_view_status_badge');
                        var body = document.getElementById('idp_view_body');
                        var viewSkills = document.getElementById('idp_view_skills');
                        var idApprove = document.getElementById('idp_view_id_approve');
                        var idCancel = document.getElementById('idp_view_id_cancel');

                        function esc(s) {
                            return String(s || '').replace(/[&<>"']/g, function(c) {
                                return ({
                                    '&': '&amp;',
                                    '<': '&lt;',
                                    '>': '&gt;',
                                    '"': '&quot;',
                                    '\'': '&#39;'
                                } [c]) || c;
                            });
                        }

                        function renderPlanBubbles(containerEl, planText) {
                            if (!containerEl) return;
                            var raw = String(planText || '');
                            var items = raw
                                .split(/\r?\n/)
                                .map(function(l) {
                                    return String(l || '').trim();
                                })
                                .filter(function(l) {
                                    return l !== '';
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

                            if (!items.length) {
                                items = raw
                                    .split(/\r?\n/)
                                    .map(function(l) {
                                        return String(l || '').trim();
                                    })
                                    .filter(function(l) {
                                        return l !== '';
                                    });
                            }

                            containerEl.innerHTML = '';
                            if (!items.length) {
                                var empty = document.createElement('div');
                                empty.className = 'text-sm opacity-70';
                                empty.textContent = 'â€”';
                                containerEl.appendChild(empty);
                                return;
                            }

                            items.forEach(function(t) {
                                var s = document.createElement('span');
                                s.className = 'badge badge-outline whitespace-normal h-auto py-3';
                                s.textContent = t;
                                containerEl.appendChild(s);
                            });
                        }

                        function renderSkills(skills) {
                            if (!viewSkills) return;
                            viewSkills.innerHTML = '';

                            if (!skills || !skills.length) {
                                var empty = document.createElement('div');
                                empty.className = 'text-sm opacity-70';
                                empty.textContent = 'No skills found.';
                                viewSkills.appendChild(empty);
                                return;
                            }

                            var tbl = document.createElement('table');
                            tbl.className = 'table table-sm w-full';
                            tbl.innerHTML = '<thead><tr><th>Skill</th><th class="text-right">%</th></tr></thead>';
                            var tb = document.createElement('tbody');
                            skills.forEach(function(s) {
                                var tr = document.createElement('tr');
                                tr.innerHTML = '<td>' + esc(s.skill_name) + '</td><td class="text-right">' + esc(s.skill_score) + '%</td>';
                                tb.appendChild(tr);
                            });
                            tbl.appendChild(tb);
                            viewSkills.appendChild(tbl);
                        }

                        function loadSkills(employeeId, department) {
                            if (viewSkills) {
                                viewSkills.innerHTML = '<div class="text-sm opacity-70">Loading...</div>';
                            }

                            var fd = new URLSearchParams();
                            fd.set('action', 'get_general_skills');
                            fd.set('employee_id', String(employeeId || ''));
                            fd.set('department', String(department || ''));

                            fetch(window.location.pathname, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                                },
                                body: fd.toString()
                            }).then(function(res) {
                                return res.json();
                            }).then(function(data) {
                                if (data && data.success) {
                                    renderSkills(data.skills || []);
                                } else {
                                    if (viewSkills) {
                                        viewSkills.innerHTML = '<div class="text-sm opacity-70">Failed to load skills.</div>';
                                    }
                                }
                            }).catch(function() {
                                if (viewSkills) {
                                    viewSkills.innerHTML = '<div class="text-sm opacity-70">Failed to load skills.</div>';
                                }
                            });
                        }

                        function badgeClass(status) {
                            status = String(status || '');
                            switch (status) {
                                case 'requested':
                                    return 'badge-accent';
                                case 'approved':
                                    return 'badge-success';
                                case 'on_hold':
                                    return 'badge-warning';
                                case 'for_compliance':
                                    return 'badge-info';
                                case 'cancelled':
                                    return 'badge-neutral';
                                case 'rejected':
                                    return 'badge-error';
                                case 'under_review':
                                default:
                                    return 'badge-primary';
                            }
                        }

                        function labelize(status) {
                            status = String(status || '');
                            return status.replace(/_/g, ' ').replace(/\b\w/g, function(m) {
                                return m.toUpperCase();
                            });
                        }

                        function box(label, value) {
                            var el = document.createElement('div');
                            el.className = 'rounded-lg bg-base-200 p-3';
                            el.innerHTML = '<div class="text-xs opacity-70">' + esc(label) + '</div>' +
                                '<div class="font-semibold whitespace-pre-line">' + esc(value) + '</div>';
                            return el;
                        }

                        document.querySelectorAll('[data-view-idp="1"]').forEach(function(btn) {
                            btn.addEventListener('click', function() {
                                var raw = btn.getAttribute('data-idp') || '';
                                try {
                                    var r = JSON.parse(raw);
                                    var id = String(r.id || '');
                                    var status = String(r.idp_status || 'requested');

                                    if (body) {
                                        var empName = String(r.employee_name || 'â€”');
                                        var empId = String(r.employee_id || 'â€”');
                                        var dept = String(r.department || 'â€”');
                                        var position = String(r.position || 'â€”');
                                        var successionStatus = String(r.succession_status || 'â€”');
                                        var deliveryMode = String(r.delivery_mode || 'â€”');
                                        var requestedAt = String(r.learning_requested_at || r.training_requested_at || r.updated_at || 'â€”');

                                        var compPct = Number(r.competency || 0);
                                        var compFmt = Number.isFinite(compPct) ? compPct.toFixed(1) : '0.0';

                                        var initials = empName
                                            .split(/\s+/)
                                            .filter(function(p) {
                                                return p;
                                            })
                                            .slice(0, 2)
                                            .map(function(p) {
                                                return p.charAt(0).toUpperCase();
                                            })
                                            .join('');
                                        if (!initials) initials = 'IDP';

                                        body.innerHTML =
                                            '<div class="rounded-xl bg-base-200 border border-base-300 p-4">' +
                                            '<div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">' +
                                            '<div class="flex items-center gap-4">' +
                                            '<div class="avatar placeholder">' +
                                            '<div class="bg-base-300 text-base-content rounded-full w-14">' +
                                            '<span class="font-bold">' + esc(initials) + '</span>' +
                                            '</div>' +
                                            '</div>' +
                                            '<div>' +
                                            '<div class="text-xl font-bold leading-tight">' + esc(empName) + '</div>' +
                                            '<div class="text-sm text-base-content/70">' + esc(empId) + '</div>' +
                                            '</div>' +
                                            '</div>' +
                                            '<div class="flex flex-wrap items-center justify-start lg:justify-end gap-2">' +
                                            '<span class="badge badge-outline">' + esc(dept) + '</span>' +
                                            '<span class="badge badge-outline">' + esc(position) + '</span>' +
                                            '<span class="badge badge-outline">' + esc(successionStatus) + '</span>' +
                                            '<span class="badge badge-outline">' + esc(compFmt) + '% Skills</span>' +
                                            '</div>' +
                                            '</div>' +

                                            '<div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">' +
                                            '<div class="rounded-lg bg-base-100 border border-base-300 p-3">' +
                                            '<div class="text-[11px] font-semibold text-base-content/60 tracking-wide">IDP STATUS</div>' +
                                            '<div class="font-semibold mt-1">' + esc(labelize(status)) + '</div>' +
                                            '</div>' +
                                            '<div class="rounded-lg bg-base-100 border border-base-300 p-3">' +
                                            '<div class="text-[11px] font-semibold text-base-content/60 tracking-wide">DELIVERY MODE</div>' +
                                            '<div class="font-semibold mt-1">' + esc(deliveryMode) + '</div>' +
                                            '</div>' +
                                            '<div class="rounded-lg bg-base-100 border border-base-300 p-3">' +
                                            '<div class="text-[11px] font-semibold text-base-content/60 tracking-wide">REQUESTED AT</div>' +
                                            '<div class="font-semibold mt-1">' + esc(requestedAt) + '</div>' +
                                            '</div>' +
                                            '<div class="rounded-lg bg-base-100 border border-base-300 p-3">' +
                                            '<div class="text-[11px] font-semibold text-base-content/60 tracking-wide">REQUEST TYPE</div>' +
                                            '<div class="font-semibold mt-1">Requested</div>' +
                                            '</div>' +
                                            '</div>' +
                                            '</div>';
                                    }

                                    var bubbles = document.getElementById('idp_view_plan_bubbles');
                                    renderPlanBubbles(bubbles, String(r.development_plan || ''));

                                    loadSkills(String(r.employee_id || ''), String(r.department || ''));

                                    if (viewBadge) {
                                        viewBadge.className = 'badge badge-sm ' + badgeClass(status);
                                        viewBadge.textContent = labelize(status);
                                    }

                                    if (idApprove) idApprove.value = id;
                                    if (idCancel) idCancel.value = id;

                                    modalToggle.checked = true;
                                } catch (e) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'Failed to open viewer.'
                                    });
                                }
                            });
                        });
                    })();
                </script>
        </div>
    </div>
    <?php require('../../partials/footer.php') ?>
