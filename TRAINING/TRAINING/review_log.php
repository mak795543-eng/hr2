<?php
session_start();
require_once __DIR__ . '/db.php';

$programId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;

$logs = [];
try {
    $sql = "SELECT l.id, l.program_id, p.training_title, l.old_status, l.new_status, l.reason, l.created_at
            FROM training_program_status_logs l
            LEFT JOIN training_programs p ON p.id = l.program_id";

    if ($programId > 0) {
        $sql .= " WHERE l.program_id = " . $programId;
    }

    $sql .= " ORDER BY l.created_at DESC, l.id DESC";

    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) {
        $logs[] = $row;
    }
} catch (Throwable $e) {
    $logs = [];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Action Logs</title>
 <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Action Logs</h1>
                    <p class="text-gray-600">History of review actions (Approve / Reject / For Compliance).</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="review.php" class="btn btn-outline">Review</a>
                    <a href="trainingprogram.php" class="btn btn-ghost">Back</a>
                </div>
            </div>
            <form method="GET" class="mb-4">
                <div class="flex flex-col md:flex-row md:items-end gap-2">
                    <div class="form-control w-full md:w-64">
                        <label class="label"><span class="label-text">Filter by Program ID</span></label>
                        <input type="number" name="program_id" class="input input-bordered w-full" value="<?= $programId > 0 ? htmlspecialchars((string)$programId) : '' ?>" placeholder="e.g. 12">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="review_log.php" class="btn btn-ghost">Clear</a>
                    </div>
                </div>
            </form>

            <div>
                <table class="table card-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Program</th>
                            <th>Old Status</th>
                            <th>New Status</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)) : ?>
                            <tr class="card-empty"><td colspan="5">No logs found.</td></tr>
                        <?php else : ?>
                            <?php foreach ($logs as $l) : ?>
                                <tr>
                                    <td data-label="Time"><?= htmlspecialchars((string)($l['created_at'] ?? '')) ?></td>
                                    <td data-label="Program">
                                        <div class="font-semibold">#<?= (int)($l['program_id'] ?? 0) ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars((string)($l['training_title'] ?? '')) ?></div>
                                    </td>
                                    <td data-label="Old Status"><span class="badge badge-outline"><?= htmlspecialchars((string)($l['old_status'] ?? '')) ?></span></td>
                                    <td data-label="New Status"><span class="badge badge-outline"><?= htmlspecialchars((string)($l['new_status'] ?? '')) ?></span></td>
                                    <td data-label="Reason" class="whitespace-pre-line"><?= htmlspecialchars((string)($l['reason'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
     <script src="../../soliera.js"></script>
  <script src="../../sidebar.js"></script>
</body>
</html>
