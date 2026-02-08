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

    try {
        if ($action === 'edit_idp' && $idpId > 0) {
            $developmentPlan = trim((string)($_POST['development_plan'] ?? ''));
            $targetScoreRaw = trim((string)($_POST['target_score'] ?? ''));
            $targetDateRaw = trim((string)($_POST['target_date'] ?? ''));

            $targetScore = null;
            if ($targetScoreRaw !== '' && is_numeric($targetScoreRaw)) {
                $targetScore = (float)$targetScoreRaw;
            }

            $targetDate = null;
            if ($targetDateRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDateRaw)) {
                $targetDate = $targetDateRaw;
            }

            $stmt = $pdo->prepare(
                "UPDATE individual_development_plans
                 SET development_plan = ?,
                     target_score = ?,
                     target_date = ?,
                     idp_status = CASE WHEN idp_status IN ('requested','approved') THEN idp_status ELSE 'under_review' END
                 WHERE id = ?"
            );
            $stmt->execute([$developmentPlan, $targetScore, $targetDate, $idpId]);

            header('Location: individual_development_plans.php?ok=updated');
            exit;
        }

        if ($action === 'delete_idp' && $idpId > 0) {
            $stmt = $pdo->prepare("DELETE FROM individual_development_plans WHERE id = ?");
            $stmt->execute([$idpId]);

            header('Location: individual_development_plans.php?ok=deleted');
            exit;
        }

        if ($action === 'cancel_under_review' && $idpId > 0) {
            $stmt = $pdo->prepare(
                "UPDATE individual_development_plans
                 SET idp_status = 'cancelled'
                 WHERE id = ? AND idp_status = 'under_review'"
            );
            $stmt->execute([$idpId]);

            header('Location: individual_development_plans.php?ok=cancelled');
            exit;
        }

        if ($action === 'request_training' && $idpId > 0) {
            try {
                $pdo->beginTransaction();

                $stmtFetch = $pdo->prepare(
                    "SELECT *
                     FROM individual_development_plans
                     WHERE id = ? AND idp_status IN ('approved','under_review')
                     LIMIT 1
                     FOR UPDATE"
                );
                $stmtFetch->execute([$idpId]);
                $row = $stmtFetch->fetch(PDO::FETCH_ASSOC);

                if (!$row) {
                    $pdo->rollBack();
                    header('Location: individual_development_plans.php?err=invalid');
                    exit;
                }

                $now = (new DateTime())->format('Y-m-d H:i:s');
                $deliveryMode = (string)($row['delivery_mode'] ?? 'Onsite');

                $learningRequestedAt = $row['learning_requested_at'];
                $trainingRequestedAt = $row['training_requested_at'];

                if ($deliveryMode === 'Online' || $deliveryMode === 'Hybrid') {
                    $learningRequestedAt = $now;
                }
                if ($deliveryMode === 'Onsite' || $deliveryMode === 'Hybrid') {
                    $trainingRequestedAt = $now;
                }

                $stmtInsert = $pdo->prepare(
                    "INSERT INTO requested_idps_repository
                        (id, employee_id, employee_name, position, department, competency, succession_status,
                         development_plan, target_score, target_date, delivery_mode,
                         requested_training_type, requested_training_mode, requested_start_datetime, requested_end_datetime,
                         idp_status, training_requested_at, learning_requested_at, created_at, updated_at)
                     VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'requested', ?, ?, ?, ?)"
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
                    $deliveryMode,
                    $row['requested_training_type'],
                    $row['requested_training_mode'],
                    $row['requested_start_datetime'],
                    $row['requested_end_datetime'],
                    $trainingRequestedAt,
                    $learningRequestedAt,
                    $row['created_at'],
                    $now,
                ]);

                $stmtDel = $pdo->prepare("DELETE FROM individual_development_plans WHERE id = ?");
                $stmtDel->execute([$idpId]);

                $pdo->commit();
                header('Location: individual_development_plans.php?ok=requested');
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
        }

        header('Location: individual_development_plans.php?err=invalid');
        exit;
    } catch (Throwable $e) {
        error_log('IDP repo action error: ' . $e->getMessage());
        header('Location: individual_development_plans.php?err=failed');
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
            idp.training_requested_at,
            idp.created_at,
            idp.updated_at
     FROM individual_development_plans idp
     LEFT JOIN (
         SELECT s2.employee_id, AVG(COALESCE(s2.score, 0)) / 5 * 100 AS competency
         FROM employee_kpi_scores s2
         WHERE s2.evaluation_period = ?
         GROUP BY s2.employee_id
     ) gs ON gs.employee_id = idp.employee_id
     ORDER BY idp.updated_at DESC, idp.created_at DESC"
);
$stmt->execute([$period]);
$rows = $stmt->fetchAll();

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function idpStatusLabel($status)
{
    $status = (string)$status;
    $status = str_replace('_', ' ', $status);
    return ucwords($status);
}

function idpBadgeClass($status)
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

<body class="bg-base-200 min-h-screen">
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


            <div class="max-w-7xl mx-auto p-6 space-y-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold">IDP Repository</h1>
                        <div class="text-sm opacity-70">Total: <span class="font-semibold"><?php echo count($rows); ?></span></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="review_page_idp.php" class="btn btn-primary btn-sm">Review Page</a>
                        <a href="requested_idps_repository.php" class="btn btn-outline btn-sm">Requested IDPs</a>
                        <a href="succession_dashboard.php" class="btn btn-outline btn-sm">Back to Dashboard</a>
                    </div>
                </div>

                <?php if (count($rows) === 0): ?>
                    <div class="card bg-base-100 shadow">
                        <div class="card-body">
                            <div class="opacity-70">No IDPs created yet.</div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 items-start">
                        <?php foreach ($rows as $r): ?>
                            <?php
                            $status = (string)($r['idp_status'] ?? 'under_review');
                            ?>
                            <div class="card bg-base-100 shadow card-bordered">
                                <div class="card-body">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-bold text-lg"><?php echo h($r['employee_name']); ?></div>
                                            <div class="text-xs opacity-70"><?php echo h($r['employee_id']); ?></div>
                                        </div>
                                        <span class="badge badge-sm <?php echo h(idpBadgeClass($status)); ?>"><?php echo h(idpStatusLabel($status)); ?></span>
                                    </div>

                                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                        <div class="rounded-lg bg-base-200 p-3">
                                            <div class="text-xs opacity-70">Position</div>
                                            <div class="font-semibold"><?php echo h($r['position']); ?></div>
                                        </div>
                                        <div class="rounded-lg bg-base-200 p-3">
                                            <div class="text-xs opacity-70">Department</div>
                                            <div class="font-semibold"><?php echo h($r['department']); ?></div>
                                        </div>
                                        <div class="rounded-lg bg-base-200 p-3">
                                            <div class="text-xs opacity-70">General Skills %</div>
                                            <div class="font-semibold"><?php echo number_format((float)($r['competency'] ?? 0), 1); ?>%</div>
                                        </div>
                                        <div class="rounded-lg bg-base-200 p-3">
                                            <div class="text-xs opacity-70">Succession Status</div>
                                            <div class="font-semibold"><?php echo h($r['succession_status']); ?></div>
                                        </div>
                                    </div>

                                    <div class="mt-4 flex justify-end">
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
                <div class="modal-box max-w-3xl">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="font-bold text-lg">IDP Details</h3>
                        <span id="idp_view_status_badge" class="badge badge-sm"></span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3" id="idp_view_body"></div>

                    <div class="mt-4">
                        <div class="text-sm font-semibold">Development Plan</div>
                        <div id="idp_view_plan" class="max-h-60 overflow-auto rounded-lg bg-base-200 p-3 mt-2">
                            <div id="idp_view_plan_bubbles" class="flex flex-wrap gap-2"></div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="text-sm font-semibold">Skill Gap Analysis</div>
                        <div id="idp_view_gap" class="max-h-96 overflow-auto rounded-lg bg-base-200 p-3 mt-2">
                            <div class="text-sm opacity-70">Analyze to compute gaps</div>
                        </div>
                    </div>

                    <div class="modal-action flex flex-wrap justify-between gap-2">
                        <div class="flex flex-wrap gap-2">
                            <button type="button" id="idp_view_edit_btn" class="btn btn-warning btn-sm hidden" data-edit-idp="1" data-idp="">Edit</button>

                            <form method="post" id="idp_view_form_request_training" class="inline hidden" data-swal-confirm="Request this IDP?">
                                <input type="hidden" name="action" value="request_training" />
                                <input type="hidden" name="idp_id" id="idp_view_id_request_training" value="" />
                                <button type="submit" class="btn btn-success btn-sm">Request</button>
                            </form>

                            <form method="post" id="idp_view_form_cancel" class="inline hidden" data-swal-confirm="Cancel this Under Review IDP?">
                                <input type="hidden" name="action" value="cancel_under_review" />
                                <input type="hidden" name="idp_id" id="idp_view_id_cancel" value="" />
                                <button type="submit" class="btn btn-outline btn-sm">Cancel</button>
                            </form>

                            <form method="post" id="idp_view_form_delete" class="inline hidden" data-swal-confirm="Delete this IDP? This cannot be undone.">
                                <input type="hidden" name="action" value="delete_idp" />
                                <input type="hidden" name="idp_id" id="idp_view_id_delete" value="" />
                                <button type="submit" class="btn btn-error btn-sm">Delete</button>
                            </form>
                        </div>

                        <label for="idp_view_modal" class="btn">Close</label>
                    </div>
                </div>
                <label class="modal-backdrop" for="idp_view_modal">Close</label>
            </div>

            <input type="checkbox" id="idp_edit_modal" class="modal-toggle" />
            <div class="modal" role="dialog">
                <div class="modal-box">
                    <h3 class="font-bold text-lg">Edit IDP</h3>
                    <form method="post" id="idp_edit_form" class="mt-4" data-swal-confirm="Save changes? Status will be set to Under Review.">
                        <input type="hidden" name="action" value="edit_idp" />
                        <input type="hidden" name="idp_id" id="idp_edit_id" value="" />

                        <div class="form-control mb-3">
                            <label class="label"><span class="label-text">Development Plan</span></label>
                            <textarea class="textarea textarea-bordered" rows="6" name="development_plan" id="idp_edit_plan"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="form-control">
                                <label class="label"><span class="label-text">Target Score (%)</span></label>
                                <input type="number" step="0.1" min="0" max="100" class="input input-bordered" name="target_score" id="idp_edit_target_score" />
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text">Target Date</span></label>
                                <input type="date" class="input input-bordered" name="target_date" id="idp_edit_target_date" />
                            </div>
                        </div>

                        <div class="modal-action">
                            <label for="idp_edit_modal" class="btn btn-ghost">Close</label>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
                <label class="modal-backdrop" for="idp_edit_modal">Close</label>
            </div>

            <script>
                (function() {
                    var ok = <?php echo json_encode($flashOk); ?>;
                    var err = <?php echo json_encode($flashErr); ?>;

                    var okMap = {
                        created: 'IDP created successfully.',
                        updated: 'IDP updated successfully.',
                        deleted: 'IDP deleted successfully.',
                        cancelled: 'IDP cancelled.',
                        training_requested: 'Training request sent.',
                        learning_requested: 'Learning request sent.',
                        requested: 'IDP requested.'
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

                    var modalToggle = document.getElementById('idp_edit_modal');
                    var editId = document.getElementById('idp_edit_id');
                    var editPlan = document.getElementById('idp_edit_plan');
                    var editScore = document.getElementById('idp_edit_target_score');
                    var editDate = document.getElementById('idp_edit_target_date');

                    var viewToggle = document.getElementById('idp_view_modal');
                    var viewBadge = document.getElementById('idp_view_status_badge');
                    var viewBody = document.getElementById('idp_view_body');
                    var viewPlan = document.getElementById('idp_view_plan');
                    var viewGap = document.getElementById('idp_view_gap');
                    var viewEditBtn = document.getElementById('idp_view_edit_btn');
                    var formReq = document.getElementById('idp_view_form_request_training');
                    var formCancel = document.getElementById('idp_view_form_cancel');
                    var formDelete = document.getElementById('idp_view_form_delete');
                    var idReq = document.getElementById('idp_view_id_request_training');
                    var idCancel = document.getElementById('idp_view_id_cancel');
                    var idDelete = document.getElementById('idp_view_id_delete');

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

                    function loadGapAnalysis(employeeId) {
                        if (viewGap) {
                            viewGap.innerHTML = '<div class="text-sm opacity-70">Loading analysis...</div>';
                        }
                        var url = '../../COMPETENCY/criticalgaps/get_employee_details.php?id=' + encodeURIComponent(String(employeeId || ''));
                        fetch(url).then(function(res) {
                            return res.json();
                        }).then(function(data) {
                            if (!viewGap) return;
                            if (!data || data.error) {
                                viewGap.innerHTML = '<div class="text-sm opacity-70">Failed to load analysis.</div>';
                                return;
                            }
                            var analysis = data.analysis || {};
                            var overall = analysis.overall || {};
                            var computed = Array.isArray(analysis.computed) ? analysis.computed : [];
                            var overallPct = Number(overall.pct || 0);
                            var status = String(overall.status || 'Retrain');
                            var head = document.createElement('div');
                            head.className = 'flex items-center justify-between mb-3';
                            head.innerHTML = '<div><div class="text-xs opacity-70">Overall Competency</div><div class="text-xl font-bold">' + (Number.isFinite(overallPct) ? overallPct.toFixed(1) : '0.0') + '%</div></div>' +
                                '<div class="text-right"><div class="text-xs opacity-70">Status</div><div><span class="badge">' + esc(status) + '</span></div></div>';
                            var tbl = document.createElement('table');
                            tbl.className = 'table table-sm w-full';
                            tbl.innerHTML = '<thead><tr><th>KPI</th><th class="text-right">Actual</th><th class="text-right">Required</th><th class="text-right">Gap</th></tr></thead>';
                            var tb = document.createElement('tbody');
                            if (!computed.length) {
                                tb.innerHTML = '<tr><td colspan="4" class="py-6 text-center opacity-70">No analysis available</td></tr>';
                            } else {
                                computed.forEach(function(r) {
                                    var kpiPct = Number(r.kpi_pct || 0);
                                    var reqPct = Number(r.required_pct || 0);
                                    var gapPct = Number(r.gap_pct || 0);
                                    var tr = document.createElement('tr');
                                    tr.innerHTML =
                                        '<td>' + esc(String(r.kpi_name || '')) + '</td>' +
                                        '<td class="text-right font-semibold">' + (Number.isFinite(kpiPct) ? kpiPct.toFixed(1) : '0.0') + '%</td>' +
                                        '<td class="text-right font-semibold">' + (Number.isFinite(reqPct) ? reqPct.toFixed(1) : '0.0') + '%</td>' +
                                        '<td class="text-right"><span class="badge ' + (gapPct > 0 ? 'badge-error' : 'badge-success') + '">' + (Number.isFinite(gapPct) ? gapPct.toFixed(1) : '0.0') + '%</span></td>';
                                    tb.appendChild(tr);
                                });
                            }
                            tbl.appendChild(tb);
                            viewGap.innerHTML = '';
                            viewGap.appendChild(head);
                            viewGap.appendChild(tbl);
                        }).catch(function() {
                            if (viewGap) viewGap.innerHTML = '<div class="text-sm opacity-70">Failed to load analysis.</div>';
                        });
                    }

                    function statusLabel(status) {
                        status = String(status || '');
                        return status.replace(/_/g, ' ').replace(/\b\w/g, function(m) {
                            return m.toUpperCase();
                        });
                    }

                    function badgeClass(status) {
                        status = String(status || '');
                        switch (status) {
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

                    function box(label, value) {
                        var el = document.createElement('div');
                        el.className = 'rounded-lg bg-base-200 p-3';
                        el.innerHTML = '<div class="text-xs opacity-70">' + label + '</div>' +
                            '<div class="font-semibold whitespace-pre-line">' + value + '</div>';
                        return el;
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

                    function show(el, on) {
                        if (!el) return;
                        if (on) {
                            el.classList.remove('hidden');
                        } else {
                            el.classList.add('hidden');
                        }
                    }

                    document.querySelectorAll('[data-edit-idp="1"]').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var raw = btn.getAttribute('data-idp') || '';
                            try {
                                var r = JSON.parse(raw);
                                editId.value = String(r.id || '');
                                editPlan.value = String(r.development_plan || '');
                                editScore.value = r.target_score === null ? '' : String(r.target_score);
                                editDate.value = String(r.target_date || '');
                                if (viewToggle) {
                                    viewToggle.checked = false;
                                }
                                modalToggle.checked = true;
                            } catch (e) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to open editor.'
                                });
                            }
                        });
                    });

                    document.querySelectorAll('[data-view-idp="1"]').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var raw = btn.getAttribute('data-idp') || '';
                            try {
                                var r = JSON.parse(raw);

                                var id = String(r.id || '');
                                var status = String(r.idp_status || 'under_review');
                                var score = r.target_score === null || typeof r.target_score === 'undefined' ? 'â€”' : String(r.target_score);

                                if (viewBody) {
                                    viewBody.innerHTML = '';
                                    viewBody.appendChild(box('Employee', String(r.employee_name || 'â€”')));
                                    viewBody.appendChild(box('Employee ID', String(r.employee_id || 'â€”')));
                                    viewBody.appendChild(box('Position', String(r.position || 'â€”')));
                                    viewBody.appendChild(box('Department', String(r.department || 'â€”')));
                                    var compPct = Number(r.competency || 0);
                                    var compFmt = Number.isFinite(compPct) ? compPct.toFixed(1) : '0.0';
                                    viewBody.appendChild(box('Competency %', compFmt + '%'));
                                    viewBody.appendChild(box('Succession Status', String(r.succession_status || 'â€”')));
                                    viewBody.appendChild(box('IDP Status', statusLabel(status)));
                                    viewBody.appendChild(box('Target Score', score));
                                    viewBody.appendChild(box('Target Date', String(r.target_date || 'â€”')));
                                    viewBody.appendChild(box('Training Requested At', String(r.training_requested_at || 'â€”')));
                                    viewBody.appendChild(box('Created At', String(r.created_at || 'â€”')));
                                    viewBody.appendChild(box('Updated At', String(r.updated_at || 'â€”')));
                                }

                                if (viewPlan) {
                                    var bubbles = document.getElementById('idp_view_plan_bubbles');
                                    renderPlanBubbles(bubbles, String(r.development_plan || ''));
                                }

                                loadGapAnalysis(String(r.employee_id || ''));

                                if (viewBadge) {
                                    viewBadge.className = 'badge badge-sm ' + badgeClass(status);
                                    viewBadge.textContent = statusLabel(status);
                                }

                                if (idReq) idReq.value = id;
                                if (idCancel) idCancel.value = id;
                                if (idDelete) idDelete.value = id;

                                show(formReq, status === 'approved');
                                show(formCancel, status === 'under_review');
                                show(formDelete, status === 'for_compliance' || status === 'cancelled' || status === 'rejected');

                                if (viewEditBtn) {
                                    viewEditBtn.setAttribute('data-idp', raw);
                                    show(viewEditBtn, status === 'on_hold' || status === 'for_compliance' || status === 'cancelled');
                                    viewEditBtn.classList.remove('btn-warning', 'btn-info', 'btn-neutral');
                                    if (status === 'for_compliance') {
                                        viewEditBtn.classList.add('btn-info');
                                    } else if (status === 'cancelled') {
                                        viewEditBtn.classList.add('btn-neutral');
                                    } else {
                                        viewEditBtn.classList.add('btn-warning');
                                    }
                                }

                                if (viewToggle) {
                                    viewToggle.checked = true;
                                }
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