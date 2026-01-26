<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('h')) {
    function h($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$allowedStatuses = ['Retrain', 'Reskilling', 'Refresher Training', 'Upskilling', 'Succession Ready'];
$allowedModes = ['Onsite', 'Online'];

$flash = null;
if (isset($_SESSION['gsm_flash']) && is_array($_SESSION['gsm_flash'])) {
    $flash = $_SESSION['gsm_flash'];
    unset($_SESSION['gsm_flash']);
}

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS development_plan_items (
            id INT PRIMARY KEY AUTO_INCREMENT,
            department VARCHAR(100) NOT NULL,
            role VARCHAR(100) NOT NULL DEFAULT '',
            skill_id INT NOT NULL,
            status ENUM('Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready') NOT NULL,
            plan_text TEXT NOT NULL,
            delivery_mode ENUM('Onsite','Online') NOT NULL DEFAULT 'Onsite',
            target_percentage DECIMAL(5,2) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_devplan (department, role, skill_id, status),
            INDEX idx_dept_status (department, status),
            CONSTRAINT fk_devplan_skill FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
} catch (Throwable $e) {
}

try { $pdo->exec("ALTER TABLE development_plan_items ADD COLUMN delivery_mode ENUM('Onsite','Online') NOT NULL DEFAULT 'Onsite'"); } catch (Throwable $e) {}
try { $pdo->exec("ALTER TABLE development_plan_items ADD COLUMN target_percentage DECIMAL(5,2) NULL"); } catch (Throwable $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $redirectDept = trim((string)($_POST['department'] ?? ''));

    try {
        if ($action === 'save_standard') {
            $skillIdRaw = trim((string)($_POST['skill_id'] ?? ''));
            $pctRaw = trim((string)($_POST['standard_percentage'] ?? ''));
            if ($skillIdRaw === '' || !ctype_digit($skillIdRaw)) throw new RuntimeException('Invalid skill.');
            if ($pctRaw === '' || !is_numeric($pctRaw)) throw new RuntimeException('Invalid percentage.');

            $skillId = (int)$skillIdRaw;
            $pct = (float)$pctRaw;
            if ($pct < 0) $pct = 0;
            if ($pct > 100) $pct = 100;

            $stmt = $pdo->prepare(
                "INSERT INTO general_skill_standards (skill_id, standard_percentage)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE standard_percentage = VALUES(standard_percentage)"
            );
            $stmt->execute([$skillId, $pct]);
            $_SESSION['gsm_flash'] = ['type' => 'success', 'message' => 'Standard updated.'];
        }

        if ($action === 'save_plan') {
            $department = trim((string)($_POST['department'] ?? ''));
            $status = trim((string)($_POST['status'] ?? ''));
            $planText = trim((string)($_POST['plan_text'] ?? ''));
            $deliveryMode = trim((string)($_POST['delivery_mode'] ?? 'Onsite'));
            $skillIdRaw = trim((string)($_POST['skill_id'] ?? ''));

            if ($department === '' || $status === '' || !in_array($status, $allowedStatuses, true)) throw new RuntimeException('Invalid request.');
            if ($skillIdRaw === '' || !ctype_digit($skillIdRaw)) throw new RuntimeException('Invalid skill.');
            if (!in_array($deliveryMode, $allowedModes, true)) $deliveryMode = 'Onsite';

            $skillId = (int)$skillIdRaw;

            $stmt = $pdo->prepare(
                "INSERT INTO development_plan_items (department, role, skill_id, status, plan_text, delivery_mode)
                 VALUES (?, '', ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE plan_text = VALUES(plan_text), delivery_mode = VALUES(delivery_mode)"
            );
            $stmt->execute([$department, $skillId, $status, $planText, $deliveryMode]);
            $_SESSION['gsm_flash'] = ['type' => 'success', 'message' => 'Plan updated.'];
        }

        if ($action === 'add_general_skill') {
            $department = trim((string)($_POST['department'] ?? ''));
            $skillName = trim((string)($_POST['skill_name'] ?? ''));
            if ($department === '' || $skillName === '') throw new RuntimeException('Missing required fields.');

            $stmtSkill = $pdo->prepare(
                "INSERT INTO skills (skill_name, category, department)
                 VALUES (?, 'General Skills', ?)
                 ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)"
            );
            $stmtSkill->execute([$skillName, $department]);
            $skillId = (int)$pdo->lastInsertId();

            $defaultTarget = 80.0;
            $stmtStd = $pdo->prepare(
                "INSERT INTO general_skill_standards (skill_id, standard_percentage)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE standard_percentage = VALUES(standard_percentage)"
            );
            $stmtStd->execute([$skillId, $defaultTarget]);

            $stmtPlan = $pdo->prepare(
                "INSERT IGNORE INTO development_plan_items (department, role, skill_id, status, plan_text, delivery_mode, target_percentage)
                 VALUES (?, '', ?, ?, '', 'Onsite', ?)"
            );
            foreach ($allowedStatuses as $st) {
                $stmtPlan->execute([$department, $skillId, $st, $defaultTarget]);
            }

            $_SESSION['gsm_flash'] = ['type' => 'success', 'message' => 'General skill added.'];
        }
    } catch (Throwable $e) {
        $_SESSION['gsm_flash'] = ['type' => 'error', 'message' => ($e->getMessage() !== '' ? $e->getMessage() : 'Request failed.')];
    }

    $target = $_SERVER['PHP_SELF'] ?? '';
    if ($redirectDept !== '') {
        $target .= '?open_dept=' . rawurlencode($redirectDept);
    }
    header('Location: ' . $target);
    exit;
}

$repo = [];
try {
    $stmt = $pdo->query(
        "SELECT s.department,
                s.id AS skill_id,
                s.skill_name,
                COALESCE(gss.standard_percentage, 80) AS standard_percentage,
                dpi.status,
                dpi.plan_text,
                COALESCE(dpi.delivery_mode, 'Onsite') AS delivery_mode
         FROM skills s
         LEFT JOIN general_skill_standards gss ON gss.skill_id = s.id
         LEFT JOIN development_plan_items dpi
           ON dpi.skill_id = s.id
          AND COALESCE(dpi.role, '') = ''
          AND dpi.department = s.department
         WHERE s.category = 'General Skills'
         ORDER BY s.department ASC, s.skill_name ASC, dpi.status ASC"
    );
    $rows = $stmt->fetchAll();

    foreach ($rows as $r) {
        $dept = (string)($r['department'] ?? '');
        $skillId = (int)($r['skill_id'] ?? 0);
        $skillName = (string)($r['skill_name'] ?? '');
        $standardPct = (float)($r['standard_percentage'] ?? 80);
        $status = (string)($r['status'] ?? '');
        $planText = (string)($r['plan_text'] ?? '');
        $deliveryMode = (string)($r['delivery_mode'] ?? 'Onsite');

        if ($dept === '' || $skillId <= 0 || $skillName === '') continue;
        if (!isset($repo[$dept])) $repo[$dept] = [];
        if (!isset($repo[$dept][$skillId])) {
            $repo[$dept][$skillId] = [
                'skill_name' => $skillName,
                'standard_percentage' => $standardPct,
                'plans' => [],
                'modes' => [],
            ];
        }

        if ($status !== '') {
            $repo[$dept][$skillId]['plans'][$status] = $planText;
            $repo[$dept][$skillId]['modes'][$status] = $deliveryMode;
        }
    }

    foreach ($repo as $dept => $skillsById) {
        foreach ($skillsById as $sid => $sd) {
            foreach ($allowedStatuses as $st) {
                if (!isset($repo[$dept][$sid]['plans'][$st])) $repo[$dept][$sid]['plans'][$st] = '';
                if (!isset($repo[$dept][$sid]['modes'][$st])) $repo[$dept][$sid]['modes'][$st] = 'Onsite';
            }
        }
    }
} catch (Throwable $e) {
    $repo = [];
}

ksort($repo);
$openDept = trim((string)($_GET['open_dept'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>General Skills Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://unpkg.com/lucide@latest"></script>
         <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-base-200 min-h-screen">
    <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include '../../USM/sidebarr.php'; ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../../USM/navbar.php'; ?>
    <div class="max-w-7xl mx-auto p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-base-content">Development Plans Repository</h1>
                <div class="text-sm text-base-content/70">Editable repository per Department / General Skill / Status.</div>
            </div>
            <div class="w-full md:w-auto flex flex-col gap-2">
                <div class="flex gap-2 justify-end">
                    <a href="criticalgaps.php" class="btn btn-outline btn-sm">Critical Roles</a>
                    <a href="gap_analysis.php" class="btn btn-outline btn-sm">Gap Analysis</a>
                </div>
                <input id="search" type="text" placeholder="Search department, skill, or plan..." class="input input-bordered w-full md:w-96" />
            </div>
        </div>

        <?php if (is_array($flash)): ?>
            <div class="alert <?php echo ($flash['type'] ?? '') === 'success' ? 'alert-success' : 'alert-error'; ?> mb-6">
                <span><?php echo h($flash['message'] ?? ''); ?></span>
            </div>
        <?php endif; ?>

        <div id="dept-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <?php foreach ($repo as $deptName => $skillsById): ?>
                <?php
                    $deptNameStr = (string)$deptName;
                    $skillCount = is_array($skillsById) ? count($skillsById) : 0;
                    $deptModalId = 'modal_dept_' . substr(md5($deptNameStr), 0, 10);
                    $deptSearchText = $deptNameStr;
                    if (is_array($skillsById)) {
                        foreach ($skillsById as $sid => $sd) {
                            $deptSearchText .= ' ' . (string)($sd['skill_name'] ?? '');
                        }
                    }
                ?>
                <div class="dept-card card bg-base-100 shadow overflow-hidden" data-search="<?php echo h($deptSearchText); ?>" data-department="<?php echo h($deptNameStr); ?>">
                    <div class="card-body p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-lg font-semibold text-base-content"><?php echo h($deptNameStr); ?></div>
                                <div class="text-xs text-base-content/70 mt-1"><?php echo (int)$skillCount; ?> skills</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" onclick="openDepartmentModal(<?php echo h(json_encode($deptModalId)); ?>, <?php echo h(json_encode($deptNameStr)); ?>);">View</button>
                        </div>
                    </div>
                </div>

                <dialog id="<?php echo h($deptModalId); ?>" class="modal" data-department="<?php echo h($deptNameStr); ?>">
                    <div class="modal-box max-w-5xl">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-bold text-lg"><?php echo h($deptNameStr); ?></h3>
                                <div class="text-xs text-base-content/70 mt-1"><?php echo (int)$skillCount; ?> skills</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline" onclick="openAddGeneralSkillModal(<?php echo h(json_encode($deptNameStr)); ?>);">Add General Skill</button>
                                <form method="dialog"><button class="btn btn-sm">Close</button></form>
                            </div>
                        </div>

                        <div class="mt-4 max-h-[70vh] overflow-y-auto">
                            <div class="space-y-3">
                                <?php foreach ($skillsById as $skillId => $skillData): ?>
                                    <?php
                                        $skillName = (string)($skillData['skill_name'] ?? '');
                                        $plans = (array)($skillData['plans'] ?? []);
                                        $modes = (array)($skillData['modes'] ?? []);
                                        $standardPct = (float)($skillData['standard_percentage'] ?? 80);
                                    ?>
                                    <details class="border border-base-300 rounded-md">
                                        <summary class="cursor-pointer px-3 py-2 text-sm font-semibold text-base-content bg-base-200">
                                            <div class="flex items-center gap-3">
                                                <form method="post" class="flex items-end gap-2" style="margin:0;" onclick="event.stopPropagation();" onkeydown="event.stopPropagation();">
                                                    <input type="hidden" name="action" value="save_standard" />
                                                    <input type="hidden" name="department" value="<?php echo h($deptNameStr); ?>" />
                                                    <input type="hidden" name="skill_id" value="<?php echo h((string)$skillId); ?>" />
                                                    <div>
                                                        <div class="text-[10px] font-semibold text-base-content/60">Standard Percentage</div>
                                                        <input type="number" step="0.1" min="0" max="100" name="standard_percentage" value="<?php echo h(number_format((float)$standardPct, 1, '.', '')); ?>" class="input input-bordered input-sm w-28" onclick="event.stopPropagation();" onkeydown="event.stopPropagation();" />
                                                    </div>
                                                    <button type="submit" class="btn btn-sm btn-primary" onclick="event.stopPropagation();">Save</button>
                                                </form>
                                                <div class="text-sm font-semibold"><?php echo h($skillName); ?></div>
                                            </div>
                                        </summary>
                                        <div class="p-3 space-y-3 text-sm">
                                            <?php foreach ($allowedStatuses as $stKey): ?>
                                                <?php
                                                    $planText = (string)($plans[$stKey] ?? '');
                                                    $mode = (string)($modes[$stKey] ?? 'Onsite');
                                                ?>
                                                <form method="post" class="border border-base-300 rounded-md p-3 bg-base-100">
                                                    <input type="hidden" name="action" value="save_plan" />
                                                    <input type="hidden" name="department" value="<?php echo h($deptNameStr); ?>" />
                                                    <input type="hidden" name="skill_id" value="<?php echo h((string)$skillId); ?>" />
                                                    <input type="hidden" name="status" value="<?php echo h($stKey); ?>" />

                                                    <div class="flex items-center justify-between gap-2 mb-2">
                                                        <div class="text-xs font-semibold text-base-content/70"><?php echo h($stKey); ?></div>
                                                        <button type="submit" class="btn btn-xs btn-primary">Save</button>
                                                    </div>

                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                                                        <div>
                                                            <label class="label"><span class="label-text">Delivery Mode</span></label>
                                                            <select name="delivery_mode" class="select select-bordered w-full text-sm">
                                                                <option value="Onsite" <?php echo $mode === 'Onsite' ? 'selected' : ''; ?>>Onsite</option>
                                                                <option value="Online" <?php echo $mode === 'Online' ? 'selected' : ''; ?>>Online</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <textarea name="plan_text" class="textarea textarea-bordered w-full text-sm" rows="2"><?php echo h($planText); ?></textarea>
                                                </form>
                                            <?php endforeach; ?>
                                        </div>
                                    </details>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <form method="dialog" class="modal-backdrop"><button>close</button></form>
                </dialog>
            <?php endforeach; ?>
        </div>
    </div>

    <dialog id="modal_add_general_skill" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Add General Skill</h3>
            <form method="post" class="mt-4 space-y-3">
                <input type="hidden" name="action" value="add_general_skill" />
                <input type="hidden" name="department" id="add_gs_department" value="" />

                <div>
                    <label class="label"><span class="label-text">Department</span></label>
                    <input id="add_gs_department_display" class="input input-bordered w-full" value="" readonly />
                </div>

                <div>
                    <label class="label"><span class="label-text">General Skill</span></label>
                    <input id="add_gs_skill" name="skill_name" class="input input-bordered w-full" required />
                </div>

                <div class="modal-action">
                    <button type="button" class="btn" onclick="document.getElementById('modal_add_general_skill').close();">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <script>
        const searchEl = document.getElementById('search');
        const deptCards = Array.from(document.querySelectorAll('.dept-card'));
        const normalize = (s) => String(s || '').toLowerCase();

        function applySearch() {
            const q = normalize(searchEl.value);
            deptCards.forEach(card => {
                const t = normalize(card.getAttribute('data-search'));
                card.style.display = (q === '' || t.includes(q)) ? '' : 'none';
            });
        }
        searchEl.addEventListener('input', applySearch);
        applySearch();

        function setOpenDeptParam(dept) {
            const url = new URL(window.location.href);
            const d = String(dept || '').trim();
            if (d !== '') url.searchParams.set('open_dept', d);
            else url.searchParams.delete('open_dept');
            window.history.replaceState({}, '', url.toString());
        }

        function findDeptModalByName(dept) {
            const d = String(dept || '').trim();
            if (d === '') return null;
            let found = null;
            Array.from(document.querySelectorAll('dialog.modal[data-department]')).some(function (dlg) {
                if (String(dlg.getAttribute('data-department') || '') === d) {
                    found = dlg;
                    return true;
                }
                return false;
            });
            return found;
        }

        window.openDepartmentModal = function (modalId, deptName) {
            const id = String(modalId || '');
            const dept = String(deptName || '').trim();
            const dlg = id ? document.getElementById(id) : findDeptModalByName(dept);
            if (dlg && typeof dlg.showModal === 'function') {
                setOpenDeptParam(dept);
                dlg.showModal();
            }
        };

        window.openAddGeneralSkillModal = function (dept) {
            const modal = document.getElementById('modal_add_general_skill');
            const deptHidden = document.getElementById('add_gs_department');
            const deptDisplay = document.getElementById('add_gs_department_display');
            const skillInput = document.getElementById('add_gs_skill');

            const d = String(dept || '').trim();
            if (deptHidden) deptHidden.value = d;
            if (deptDisplay) deptDisplay.value = d;
            if (skillInput) skillInput.value = '';
            if (modal && typeof modal.showModal === 'function') modal.showModal();
        };

        (function autoOpenDeptModal() {
            const openDept = <?php echo json_encode($openDept, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const dlg = findDeptModalByName(openDept);
            if (dlg && typeof dlg.showModal === 'function') dlg.showModal();
        })();
    </script>
    <script>
    lucide.createIcons();
  </script>
   <script src="../../soliera.js"></script>
  <script src="../../sidebar.js"></script>
</body>
</html>
