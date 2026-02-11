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
            echo json_encode(['success' => true, 'items' => $rows]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Failed']);
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
                        <div class="text-sm opacity-70">Requested IDPs awaiting training scheduling and review.</div>
                    </div>
                </div>
                <div class="card bg-base-100 shadow">
                    <div class="card-body">
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
                                        <th>Dept</th>
                                        <th>Position</th>
                                        <th class="text-right">Competency</th>
                                        <th>Succession</th>
                                        <th>Req Type</th>
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
            </div>
        </div>
    </div>
    <script>
        if (window.lucide) lucide.createIcons();
    </script>
    <script src="../../soliera.js"></script>
    <script src="../../sidebar.js"></script>
</body>

</html>
