<?php

require_once __DIR__ . '/db.php';

$programId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
$type = isset($_GET['type']) ? trim((string)$_GET['type']) : '';
$allowedTypes = ['financial', 'logistics', 'admin'];
if (!in_array($type, $allowedTypes, true)) $type = '';

$logs = [];
try {
    try {
        $conn->query("CREATE TABLE IF NOT EXISTS department_request_status_logs (id INT AUTO_INCREMENT PRIMARY KEY, request_type ENUM('financial','logistics','admin') NOT NULL, request_id INT NOT NULL, program_id INT NOT NULL, submission_no INT NOT NULL DEFAULT 1, old_status VARCHAR(50) NULL, new_status VARCHAR(50) NOT NULL, reason TEXT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_drl_program (program_id), INDEX idx_drl_type (request_type), INDEX idx_drl_created (created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
    }

    try {
        $stmtCol = $conn->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'department_request_status_logs' AND column_name = 'submission_no' LIMIT 1");
        $stmtCol->execute();
        $hasSub = (bool)$stmtCol->get_result()->fetch_row();
        if (!$hasSub) {
            $conn->query("ALTER TABLE department_request_status_logs ADD COLUMN submission_no INT NOT NULL DEFAULT 1");
        }
    } catch (Throwable $e) {
    }

    $submissionNo = 0;
    if ($programId > 0) {
        try {
            $stmtSub = $conn->prepare("SELECT submission_no FROM training_programs WHERE id = ?");
            $stmtSub->bind_param('i', $programId);
            $stmtSub->execute();
            $rowSub = $stmtSub->get_result()->fetch_assoc();
            if ($rowSub && isset($rowSub['submission_no'])) $submissionNo = (int)$rowSub['submission_no'];
            if ($submissionNo <= 0) $submissionNo = 1;
        } catch (Throwable $e) {
            $submissionNo = 1;
        }
    }

    $sql = "SELECT l.id, l.request_type, l.request_id, l.program_id, l.old_status, l.new_status, l.reason, l.created_at,
                   l.submission_no,
                   p.training_title
            FROM department_request_status_logs l
            LEFT JOIN training_programs p ON p.id = l.program_id
            WHERE 1=1";

    if ($programId > 0) {
        $sql .= " AND l.program_id = " . $programId;
        if ($submissionNo > 0) {
            $sql .= " AND l.submission_no = " . (int)$submissionNo;
        }
    }
    if ($type !== '') {
        $sql .= " AND l.request_type = '" . $conn->real_escape_string($type) . "'";
    }

    $sql .= " ORDER BY l.created_at DESC, l.id DESC";

    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) {
        $logs[] = $row;
    }
} catch (Throwable $e) {
    $logs = [];
}
require('../../partials/header.php');
?>

    <style>
        .card-table thead { display: none; }
        .card-table tbody {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 0.75rem;
        }
        @media (min-width: 768px) {
            .card-table tbody { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1280px) {
            .card-table tbody { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        .card-table tbody tr {
            display: block;
            background: #fff;
            border: 1px solid #eef2f7;
            border-radius: 0.75rem;
            padding: 1rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .card-table tbody tr.card-empty {
            grid-column: 1 / -1;
            text-align: center;
            color: #6b7280;
            padding: 2.25rem 1rem;
        }
        .card-table tbody tr.card-empty td { display: block; }
        .card-table td {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.35rem 0;
            border: 0;
            background: transparent;
            white-space: normal;
        }
        .card-table td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #374151;
            flex: 0 0 auto;
        }
        .card-table td[data-label="Reason"] {
            display: block;
            padding-top: 0.75rem;
            margin-top: 0.5rem;
            border-top: 1px solid #eef2f7;
        }
        .card-table td[data-label="Reason"]::before { display: none; }
    </style>
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
    <main class="max-w-6xl mx-auto px-4 py-6">
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Department Request Logs</h1>
                    <p class="text-gray-600">History of approve/reject actions (with rejection reasons).</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="trainingprogram.php" class="btn btn-ghost">Back</a>
                </div>
            </div>

            <form method="GET" class="mb-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="form-control">
                        <label class="label"><span class="label-text">Program ID</span></label>
                        <input type="number" name="program_id" class="input input-bordered" value="<?= $programId > 0 ? htmlspecialchars((string)$programId) : '' ?>" placeholder="e.g. 12">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Request Type</span></label>
                        <select name="type" class="select select-bordered">
                            <option value="" <?= $type === '' ? 'selected' : '' ?>>All</option>
                            <option value="financial" <?= $type === 'financial' ? 'selected' : '' ?>>Financial</option>
                            <option value="logistics" <?= $type === 'logistics' ? 'selected' : '' ?>>Logistics</option>
                            <option value="admin" <?= $type === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="request_logs.php" class="btn btn-ghost">Clear</a>
                    </div>
                </div>
            </form>

            <div>
                <table class="table card-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Program</th>
                            <th>Type</th>
                            <th>Request</th>
                            <th>Submission</th>
                            <th>Old</th>
                            <th>New</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)) : ?>
                            <tr class="card-empty"><td colspan="8">No logs found.</td></tr>
                        <?php else : ?>
                            <?php foreach ($logs as $l) : ?>
                                <tr>
                                    <td data-label="Time"><?= htmlspecialchars((string)($l['created_at'] ?? '')) ?></td>
                                    <td data-label="Program">
                                        <div class="font-semibold">#<?= (int)($l['program_id'] ?? 0) ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars((string)($l['training_title'] ?? '')) ?></div>
                                    </td>
                                    <td data-label="Type"><span class="badge badge-outline"><?= htmlspecialchars((string)($l['request_type'] ?? '')) ?></span></td>
                                    <td data-label="Request">#<?= (int)($l['request_id'] ?? 0) ?></td>
                                    <td data-label="Submission"><span class="badge badge-ghost"><?= (int)($l['submission_no'] ?? 1) ?></span></td>
                                    <td data-label="Old"><span class="badge badge-outline"><?= htmlspecialchars((string)($l['old_status'] ?? '')) ?></span></td>
                                    <td data-label="New"><span class="badge badge-outline"><?= htmlspecialchars((string)($l['new_status'] ?? '')) ?></span></td>
                                    <td data-label="Reason" class="whitespace-pre-line"><?= htmlspecialchars((string)($l['reason'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
      <?php require('../../partials/footer.php') ?>