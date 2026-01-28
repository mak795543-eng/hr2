<?php
require_once __DIR__ . '/../../COMPETENCY/criticalgaps/criticalgaps/config.php';

$flashOk = (string)($_GET['ok'] ?? '');
$flashErr = (string)($_GET['err'] ?? '');

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
                "SELECT s.skill_name,
                        COALESCE(es.skill_score, 0) AS skill_score,
                        es.assessment_date
                 FROM skills s
                 LEFT JOIN employee_skills es
                   ON es.skill_id = s.id AND es.employee_id = ?
                 WHERE s.category = 'General Skills'
                   AND s.department = ?
                 ORDER BY s.skill_name ASC"
            );
            $stmtSkills->execute([$employeeId, $department]);
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

$stmt = $pdo->query(
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
         SELECT idp2.employee_id,
                idp2.department,
                AVG(COALESCE(es2.skill_score, 0)) AS competency
         FROM individual_development_plans idp2
         JOIN skills s2
           ON s2.category = 'General Skills'
          AND s2.department = idp2.department
         LEFT JOIN employee_skills es2
           ON es2.employee_id = idp2.employee_id
          AND es2.skill_id = s2.id
         GROUP BY idp2.employee_id, idp2.department
     ) gs ON gs.employee_id = idp.employee_id AND gs.department = idp.department
     WHERE idp.idp_status = 'under_review'
     ORDER BY idp.updated_at DESC, idp.created_at DESC"
);
$rows = $stmt->fetchAll();

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function idpBadgeClass($status) {
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
                <a href="individual_development_plans.php" class="btn btn-outline btn-sm">IDP Repository</a>
                <a href="succession_dashboard.php" class="btn btn-outline btn-sm">Dashboard</a>
            </div>
        </div>

        <?php if (count($rows) === 0): ?>
            <div class="card bg-base-100 shadow">
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
                    <div class="card bg-base-100 shadow card-bordered">
                        <div class="card-body">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-bold text-lg"><?php echo h($r['employee_name']); ?></div>
                                    <div class="text-xs opacity-70"><?php echo h($r['employee_id']); ?></div>
                                </div>
                                <span class="badge badge-sm <?php echo h(idpBadgeClass($status)); ?>"><?php echo h('Under Review'); ?></span>
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
                <div class="text-sm font-semibold">General Skills Breakdown</div>
                <div id="idp_view_skills" class="max-h-60 overflow-auto rounded-lg bg-base-200 p-3 mt-2">
                    <div class="text-sm opacity-70" id="idp_view_skills_status">â€”</div>
                </div>
            </div>

            <div class="modal-action flex flex-wrap justify-between gap-2">
                <div class="flex flex-wrap gap-2">
                    <form method="post" id="idp_view_form_approve" class="inline" data-swal-confirm="Approve this IDP?">
                        <input type="hidden" name="action" value="approve" />
                        <input type="hidden" name="idp_id" id="idp_view_id_approve" value="" />
                        <button type="submit" class="btn btn-success btn-sm">Approve</button>
                    </form>

                    <form method="post" id="idp_view_form_reject" class="inline" data-swal-confirm="Reject this IDP?">
                        <input type="hidden" name="action" value="reject" />
                        <input type="hidden" name="idp_id" id="idp_view_id_reject" value="" />
                        <button type="submit" class="btn btn-error btn-sm">Reject</button>
                    </form>

                    <form method="post" id="idp_view_form_compliance" class="inline" data-swal-confirm="Mark this IDP as For Compliance?">
                        <input type="hidden" name="action" value="for_compliance" />
                        <input type="hidden" name="idp_id" id="idp_view_id_compliance" value="" />
                        <button type="submit" class="btn btn-info btn-sm">For Compliance</button>
                    </form>
                </div>

                <label for="idp_view_modal" class="btn">Close</label>
            </div>
        </div>
        <label class="modal-backdrop" for="idp_view_modal">Close</label>
    </div>

    <script>
        (function () {
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

            document.querySelectorAll('form[data-swal-confirm]').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var msg = form.getAttribute('data-swal-confirm') || 'Are you sure?';
                    Swal.fire({
                        icon: 'warning',
                        title: 'Please confirm',
                        text: msg,
                        showCancelButton: true,
                        confirmButtonText: 'Yes',
                        cancelButtonText: 'No'
                    }).then(function (res) {
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
                return String(s || '').replace(/[&<>"']/g, function (c) {
                    return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c]) || c;
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
                skills.forEach(function (s) {
                    var tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + esc(s.skill_name) + '</td><td class="text-right">' + esc(s.skill_score) + '%</td>';
                    tb.appendChild(tr);
                });
                tbl.appendChild(tb);
                viewSkills.appendChild(tbl);
            }

            function loadSkills(employeeId, department) {
                if (viewSkillsStatus) {
                    viewSkillsStatus.textContent = 'Loading...';
                }
                if (viewSkills) {
                    viewSkills.innerHTML = '<div class="text-sm opacity-70">Loading...</div>';
                }

                var fd = new URLSearchParams();
                fd.set('action', 'get_general_skills');
                fd.set('employee_id', String(employeeId || ''));
                fd.set('department', String(department || ''));

                fetch(window.location.pathname, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: fd.toString()
                }).then(function (res) {
                    return res.json();
                }).then(function (data) {
                    if (data && data.success) {
                        renderSkills(data.skills || []);
                    } else {
                        if (viewSkills) {
                            viewSkills.innerHTML = '<div class="text-sm opacity-70">Failed to load skills.</div>';
                        }
                    }
                }).catch(function () {
                    if (viewSkills) {
                        viewSkills.innerHTML = '<div class="text-sm opacity-70">Failed to load skills.</div>';
                    }
                });
            }

            function statusLabel(status) {
                status = String(status || '');
                return status.replace(/_/g, ' ').replace(/\b\w/g, function (m) { return m.toUpperCase(); });
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
                    .map(function (l) { return String(l || '').trim(); })
                    .filter(function (l) { return l !== ''; })
                    .filter(function (l) { return l.indexOf('- ') === 0; })
                    .map(function (l) { return l.slice(2).trim(); })
                    .filter(function (l) { return l !== ''; });

                if (!items.length) {
                    items = raw
                        .split(/\r?\n/)
                        .map(function (l) { return String(l || '').trim(); })
                        .filter(function (l) { return l !== ''; });
                }

                containerEl.innerHTML = '';
                if (!items.length) {
                    var empty = document.createElement('div');
                    empty.className = 'text-sm opacity-70';
                    empty.textContent = 'â€”';
                    containerEl.appendChild(empty);
                    return;
                }

                items.forEach(function (t) {
                    var s = document.createElement('span');
                    s.className = 'badge badge-outline whitespace-normal h-auto py-3';
                    s.textContent = t;
                    containerEl.appendChild(s);
                });
            }

            document.querySelectorAll('[data-view-idp="1"]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var raw = btn.getAttribute('data-idp') || '';
                    try {
                        var r = JSON.parse(raw);

                        var id = String(r.id || '');
                        var status = String(r.idp_status || 'under_review');
                        var score = r.target_score === null || typeof r.target_score === 'undefined' ? 'â€”' : String(r.target_score);

                        if (body) {
                            body.innerHTML = '';
                            body.appendChild(box('Employee', String(r.employee_name || 'â€”')));
                            body.appendChild(box('Employee ID', String(r.employee_id || 'â€”')));
                            body.appendChild(box('Position', String(r.position || 'â€”')));
                            body.appendChild(box('Department', String(r.department || 'â€”')));
                            body.appendChild(box('General Skills %', String(r.competency || '0') + '%'));
                            body.appendChild(box('Succession Status', String(r.succession_status || 'â€”')));
                            body.appendChild(box('IDP Status', statusLabel(status)));
                            body.appendChild(box('Target Score', score));
                            body.appendChild(box('Target Date', String(r.target_date || 'â€”')));
                            body.appendChild(box('Created At', String(r.created_at || 'â€”')));
                            body.appendChild(box('Updated At', String(r.updated_at || 'â€”')));
                        }

                        if (viewPlan) {
                            var bubbles = document.getElementById('idp_view_plan_bubbles');
                            renderPlanBubbles(bubbles, String(r.development_plan || ''));
                        }

                        loadSkills(String(r.employee_id || ''), String(r.department || ''));

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
