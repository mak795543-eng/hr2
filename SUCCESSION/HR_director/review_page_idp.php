<?php
require_once __DIR__ . '/../../COMPETENCY/criticalgaps/config.php';

$flashOk = (string)($_GET['ok'] ?? '');
$flashErr = (string)($_GET['err'] ?? '');

$period = date('Y') . '-Q' . (string)ceil((int)date('n') / 3);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $idpId = (int)($_POST['idp_id'] ?? 0);

    if ($action === 'get_kpi_breakdown') {
        header('Content-Type: application/json; charset=utf-8');
        $employeeId = trim((string)($_POST['employee_id'] ?? ''));

        if ($employeeId === '') {
            echo json_encode(['success' => false, 'message' => 'Missing request.']);
            exit;
        }

        try {
            $analysis = function_exists('computeEmployeeKpiAnalysis') ? computeEmployeeKpiAnalysis($employeeId, $period) : ['computed' => [], 'overall' => null];
            echo json_encode([
                'success' => true,
                'evaluation_period' => $period,
                'computed' => $analysis['computed'] ?? [],
                'overall' => $analysis['overall'] ?? null,
            ]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to load evaluation.']);
            exit;
        }
    }

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
        header('Location: review_page_idp.php?err=invalid');
        exit;
    }

    try {
        if ($action === 'approve') {
            $stmt = $pdo->prepare(
                "UPDATE individual_development_plans
                 SET idp_status = 'approved',
                     learning_requested_at = CASE WHEN delivery_mode IN ('Online','Hybrid') THEN CURRENT_TIMESTAMP ELSE learning_requested_at END,
                     training_requested_at = CASE WHEN delivery_mode IN ('Onsite','Hybrid') THEN CURRENT_TIMESTAMP ELSE training_requested_at END
                 WHERE id = ? AND idp_status = 'under_review'"
            );
            $stmt->execute([$idpId]);
            header('Location: review_page_idp.php?ok=approved');
            exit;
        }

        if ($action === 'reject') {
            $stmt = $pdo->prepare(
                "UPDATE individual_development_plans
                 SET idp_status = 'rejected'
                 WHERE id = ? AND idp_status = 'under_review'"
            );
            $stmt->execute([$idpId]);
            header('Location: review_page_idp.php?ok=rejected');
            exit;
        }

        if ($action === 'for_compliance') {
            $stmt = $pdo->prepare(
                "UPDATE individual_development_plans
                 SET idp_status = 'for_compliance'
                 WHERE id = ? AND idp_status = 'under_review'"
            );
            $stmt->execute([$idpId]);
            header('Location: review_page_idp.php?ok=for_compliance');
            exit;
        }

        header('Location: review_page_idp.php?err=invalid');
        exit;
    } catch (Throwable $e) {
        error_log('review_page_idp action error: ' . $e->getMessage());
        header('Location: review_page_idp.php?err=failed');
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
            idp.created_at,
            idp.updated_at
     FROM individual_development_plans idp
     LEFT JOIN (
         SELECT s2.employee_id, AVG(COALESCE(s2.score, 0)) / 5 * 100 AS competency
         FROM employee_kpi_scores s2
         WHERE s2.evaluation_period = ?
         GROUP BY s2.employee_id
     ) gs ON gs.employee_id = idp.employee_id
     WHERE idp.idp_status = 'under_review'
     ORDER BY idp.updated_at DESC, idp.created_at DESC"
);
$stmt->execute([$period]);
$rows = $stmt->fetchAll();

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function idpBadgeClass($status)
{
    $status = (string)$status;
    switch ($status) {
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
                            <h1 class="text-2xl font-bold">IDP Review</h1>
                            <div class="text-sm opacity-70">Under Review: <span class="font-semibold"><?php echo count($rows); ?></span></div>
                        </div>
                        <div class="flex items-center gap-2">

                        </div>
                    </div>

                    <?php if (count($rows) === 0): ?>
                        <div class="card bg-base-100 shadow-md">
                            <div class="card-body">
                                <div class="opacity-70">No IDPs pending review.</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 items-start">
                            <?php foreach ($rows as $r): ?>
                                <?php
                                $status = (string)($r['idp_status'] ?? 'under_review');
                                ?>
                                <div class="card bg-base-100 shadow-md">
                                    <div class="card-body">
                                        <div class="flex justify-between items-start">
                                            <h3 class="card-title"><?php echo h($r['employee_name']); ?></h3>
                                            <div class="badge badge-sm <?php echo h(idpBadgeClass($status)); ?>"><?php echo h('Under Review'); ?></div>
                                        </div>

                                        <div class="flex flex-wrap gap-2 my-2">
                                            <div class="badge badge-outline"><?php echo h($r['department']); ?></div>
                                            <div class="badge badge-outline"><?php echo h($r['position']); ?></div>
                                            <div class="badge badge-outline"><?php echo number_format((float)($r['competency'] ?? 0), 1); ?>% Competency</div>
                                            <div class="badge badge-outline"><?php echo h($r['succession_status']); ?></div>
                                        </div>

                                        <p class="text-sm text-gray-500">Employee ID: <?php echo h($r['employee_id']); ?></p>
                                        <p class="text-sm text-gray-500">Date Added: <?php echo h(date('Y-m-d', strtotime((string)($r['created_at'] ?? 'now')))); ?></p>

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
                            <div class="text-sm font-semibold">Criteria / KPI Evaluation</div>
                            <div id="idp_view_skills" class="max-h-60 overflow-auto rounded-lg bg-base-200 p-3 mt-2">
                                <div class="text-sm opacity-70" id="idp_view_skills_status">â€”</div>
                            </div>
                        </div>

                        <div class="modal-action flex flex-wrap justify-between gap-2">
                            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                                <form method="post" id="idp_view_form_approve" class="inline" data-swal-confirm="Approve this IDP?">
                                    <input type="hidden" name="action" value="approve" />
                                    <input type="hidden" name="idp_id" id="idp_view_id_approve" value="" />
                                    <button type="submit" class="btn btn-success flex-1">Approve</button>
                                </form>

                                <form method="post" id="idp_view_form_reject" class="inline" data-swal-confirm="Reject this IDP?">
                                    <input type="hidden" name="action" value="reject" />
                                    <input type="hidden" name="idp_id" id="idp_view_id_reject" value="" />
                                    <button type="submit" class="btn btn-error flex-1">Reject</button>
                                </form>

                                <form method="post" id="idp_view_form_compliance" class="inline" data-swal-confirm="Mark this IDP as For Compliance?">
                                    <input type="hidden" name="action" value="for_compliance" />
                                    <input type="hidden" name="idp_id" id="idp_view_id_compliance" value="" />
                                    <button type="submit" class="btn btn-warning flex-1">For Compliance</button>
                                </form>
                            </div>

                            <label for="idp_view_modal" class="btn btn-outline">Close</label>
                        </div>
                    </div>
                    <label class="modal-backdrop" for="idp_view_modal">Close</label>
                </div>

                <script>
                    (function() {
                        var ok = <?php echo json_encode($flashOk); ?>;
                        var err = <?php echo json_encode($flashErr); ?>;

                        var okMap = {
                            approved: 'IDP approved.',
                            rejected: 'IDP rejected.',
                            for_compliance: 'IDP marked for compliance.'
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

                        var modalToggle = document.getElementById('idp_view_modal');
                        var viewBadge = document.getElementById('idp_view_status_badge');
                        var body = document.getElementById('idp_view_body');
                        var viewPlan = document.getElementById('idp_view_plan');
                        var viewSkills = document.getElementById('idp_view_skills');
                        var viewSkillsStatus = document.getElementById('idp_view_skills_status');
                        var idApprove = document.getElementById('idp_view_id_approve');
                        var idReject = document.getElementById('idp_view_id_reject');
                        var idCompliance = document.getElementById('idp_view_id_compliance');

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

                        function renderKpiBreakdown(computed, overall) {
                            if (!viewSkills) return;
                            viewSkills.innerHTML = '';

                            if (viewSkillsStatus) {
                                if (overall && typeof overall === 'object') {
                                    var oPct = Number(overall.pct || 0);
                                    var oStatus = String(overall.status || '');
                                    viewSkillsStatus.textContent = 'Overall: ' + (isFinite(oPct) ? oPct.toFixed(1) : '0.0') + '% ' + (oStatus ? '(' + oStatus + ')' : '');
                                } else {
                                    viewSkillsStatus.textContent = 'â€”';
                                }
                            }

                            if (!computed || !computed.length) {
                                var empty = document.createElement('div');
                                empty.className = 'text-sm opacity-70';
                                empty.textContent = 'No evaluation found.';
                                viewSkills.appendChild(empty);
                                return;
                            }

                            computed.forEach(function(k) {
                                var kpiName = String(k.kpi_name || '');
                                var kpiPct = Number(k.kpi_pct || 0);
                                var reqPct = Number(k.required_pct || 0);
                                var gapPct = Number(k.gap_pct || 0);
                                var evals = Array.isArray(k.evaluations) ? k.evaluations : [];

                                var gapBadge = gapPct > 0 ? 'badge-error' : 'badge-success';
                                var details = document.createElement('details');
                                details.className = 'collapse collapse-arrow bg-base-100 border border-base-300 mb-2';

                                var summary = document.createElement('summary');
                                summary.className = 'collapse-title text-sm';
                                summary.innerHTML =
                                    '<div class="flex items-center justify-between gap-3">' +
                                    '<div class="font-semibold">' + esc(kpiName) + '</div>' +
                                    '<div class="flex flex-wrap items-center justify-end gap-2">' +
                                    '<span class="badge badge-ghost badge-sm">' + (isFinite(kpiPct) ? kpiPct.toFixed(1) : '0.0') + '%</span>' +
                                    '<span class="badge badge-outline badge-sm">Req ' + (isFinite(reqPct) ? reqPct.toFixed(1) : '0.0') + '%</span>' +
                                    '<span class="badge ' + gapBadge + ' badge-sm">Gap ' + (isFinite(gapPct) ? gapPct.toFixed(1) : '0.0') + '%</span>' +
                                    '</div>' +
                                    '</div>';

                                var content = document.createElement('div');
                                content.className = 'collapse-content';

                                if (!evals.length) {
                                    content.innerHTML = '<div class="text-sm opacity-70">No criteria evaluations.</div>';
                                } else {
                                    var rows = evals.map(function(ev) {
                                        var c = String(ev.criteria || '');
                                        var s = Number(ev.score || 0);
                                        var pct = (s / 5) * 100;
                                        return '<tr><td>' + esc(c) + '</td><td class="text-right">' + (isFinite(s) ? s.toFixed(1) : '0.0') + ' / 5</td><td class="text-right">' + (isFinite(pct) ? pct.toFixed(1) : '0.0') + '%</td></tr>';
                                    }).join('');

                                    content.innerHTML =
                                        '<div class="overflow-x-auto">' +
                                        '<table class="table table-sm w-full">' +
                                        '<thead><tr><th>Criteria</th><th class="text-right">Score</th><th class="text-right">%</th></tr></thead>' +
                                        '<tbody>' + rows + '</tbody>' +
                                        '</table>' +
                                        '</div>';
                                }

                                details.appendChild(summary);
                                details.appendChild(content);
                                viewSkills.appendChild(details);
                            });
                        }

                        function loadKpiBreakdown(employeeId) {
                            if (viewSkillsStatus) {
                                viewSkillsStatus.textContent = 'Loading...';
                            }
                            if (viewSkills) {
                                viewSkills.innerHTML = '<div class="text-sm opacity-70">Loading...</div>';
                            }

                            var fd = new URLSearchParams();
                            fd.set('action', 'get_kpi_breakdown');
                            fd.set('employee_id', String(employeeId || ''));

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
                                    renderKpiBreakdown(data.computed || [], data.overall || null);
                                } else {
                                    if (viewSkills) {
                                        viewSkills.innerHTML = '<div class="text-sm opacity-70">Failed to load evaluation.</div>';
                                    }
                                }
                            }).catch(function() {
                                if (viewSkills) {
                                    viewSkills.innerHTML = '<div class="text-sm opacity-70">Failed to load evaluation.</div>';
                                }
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

                        document.querySelectorAll('[data-view-idp="1"]').forEach(function(btn) {
                            btn.addEventListener('click', function() {
                                var raw = btn.getAttribute('data-idp') || '';
                                try {
                                    var r = JSON.parse(raw);

                                    var id = String(r.id || '');
                                    var status = String(r.idp_status || 'under_review');
                                    var score = r.target_score === null || typeof r.target_score === 'undefined' ? 'â€”' : String(r.target_score);

                                    if (body) {
                                        var empName = String(r.employee_name || 'â€”');
                                        var empId = String(r.employee_id || 'â€”');
                                        var dept = String(r.department || 'â€”');
                                        var position = String(r.position || 'â€”');
                                        var successionStatus = String(r.succession_status || 'â€”');
                                        var targetDate = String(r.target_date || 'â€”');
                                        var createdAt = String(r.created_at || 'â€”');
                                        var updatedAt = String(r.updated_at || 'â€”');

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
                                            '<span class="badge badge-outline">' + esc(compFmt) + '% Competency</span>' +
                                            '</div>' +
                                            '</div>' +

                                            '<div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">' +
                                            '<div class="rounded-lg bg-base-100 border border-base-300 p-3">' +
                                            '<div class="text-[11px] font-semibold text-base-content/60 tracking-wide">IDP STATUS</div>' +
                                            '<div class="font-semibold mt-1">' + esc(statusLabel(status)) + '</div>' +
                                            '</div>' +
                                            '<div class="rounded-lg bg-base-100 border border-base-300 p-3">' +
                                            '<div class="text-[11px] font-semibold text-base-content/60 tracking-wide">TARGET SCORE</div>' +
                                            '<div class="font-semibold mt-1">' + esc(score) + '</div>' +
                                            '</div>' +
                                            '<div class="rounded-lg bg-base-100 border border-base-300 p-3">' +
                                            '<div class="text-[11px] font-semibold text-base-content/60 tracking-wide">TARGET DATE</div>' +
                                            '<div class="font-semibold mt-1">' + esc(targetDate) + '</div>' +
                                            '</div>' +
                                            '<div class="rounded-lg bg-base-100 border border-base-300 p-3">' +
                                            '<div class="text-[11px] font-semibold text-base-content/60 tracking-wide">REVIEW STATUS</div>' +
                                            '<div class="font-semibold mt-1">Under Review</div>' +
                                            '</div>' +
                                            '</div>' +

                                            '<div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">' +
                                            '<div class="rounded-lg bg-base-100 border border-base-300 p-3">' +
                                            '<div class="text-[11px] font-semibold text-base-content/60 tracking-wide">CREATED</div>' +
                                            '<div class="font-semibold mt-1">' + esc(createdAt) + '</div>' +
                                            '</div>' +
                                            '<div class="rounded-lg bg-base-100 border border-base-300 p-3">' +
                                            '<div class="text-[11px] font-semibold text-base-content/60 tracking-wide">UPDATED</div>' +
                                            '<div class="font-semibold mt-1">' + esc(updatedAt) + '</div>' +
                                            '</div>' +
                                            '</div>' +
                                            '</div>';
                                    }

                                    if (viewPlan) {
                                        var bubbles = document.getElementById('idp_view_plan_bubbles');
                                        renderPlanBubbles(bubbles, String(r.development_plan || ''));
                                    }

                                    loadKpiBreakdown(String(r.employee_id || ''));

                                    if (viewBadge) {
                                        viewBadge.className = 'badge badge-sm ' + badgeClass(status);
                                        viewBadge.textContent = statusLabel(status);
                                    }

                                    if (idApprove) idApprove.value = id;
                                    if (idReject) idReject.value = id;
                                    if (idCompliance) idCompliance.value = id;
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