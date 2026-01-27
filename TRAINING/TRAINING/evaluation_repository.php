<?php

require_once __DIR__ . '/db.php';

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$debug = isset($_GET['debug']);
$debugDbName = '';
$debugEvalCount = null;

try {
    $conn->query(
        "CREATE TABLE IF NOT EXISTS training_program_evaluations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            program_id INT NOT NULL,
            submission_no INT NOT NULL DEFAULT 1,
            evaluator_name VARCHAR(150) NULL,
            overall_percentage DECIMAL(5,2) NOT NULL,
            remarks TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_tpe (program_id, submission_no, evaluator_name),
            INDEX idx_tpe_program (program_id, submission_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
} catch (Throwable $e) {
}

$rows = [];
$queryError = '';
try {
    $sql = "SELECT te.id,
                   te.program_id,
                   te.submission_no,
                   te.evaluator_name,
                   te.overall_percentage,
                   te.remarks,
                   te.created_at,
                   p.training_title,
                   p.training_type,
                   p.training_mode,
                   p.target_audience,
                   p.start_datetime,
                   p.end_datetime,
                   p.status,
                   p.category,
                   p.description,
                   m.mentor_name
            FROM training_program_evaluations te
            JOIN training_programs p ON p.id = te.program_id
            LEFT JOIN mentors m ON m.id = p.mentor_id
            ORDER BY te.created_at DESC";
    $res = $conn->query($sql);
    if ($res) {
        $rows = $res->fetch_all(MYSQLI_ASSOC);
    }
} catch (Throwable $e) {
    $queryError = $e->getMessage();
    $rows = [];
    try {
        $sql2 = "SELECT te.id,
                        te.program_id,
                        te.submission_no,
                        te.evaluator_name,
                        te.overall_percentage,
                        te.remarks,
                        te.created_at,
                        p.training_title,
                        p.training_type,
                        p.training_mode,
                        p.target_audience,
                        p.start_datetime,
                        p.end_datetime,
                        p.status,
                        p.category,
                        p.description,
                        NULL AS mentor_name
                 FROM training_program_evaluations te
                 JOIN training_programs p ON p.id = te.program_id
                 ORDER BY te.created_at DESC";
        $res2 = $conn->query($sql2);
        if ($res2) {
            $rows = $res2->fetch_all(MYSQLI_ASSOC);
        }
    } catch (Throwable $e2) {
        $queryError = $queryError !== '' ? ($queryError . ' | ' . $e2->getMessage()) : $e2->getMessage();
        $rows = [];
    }
}

if ($debug) {
    try {
        $resDb = $conn->query('SELECT DATABASE() AS db');
        if ($resDb) {
            $debugDbName = (string)(($resDb->fetch_assoc()['db'] ?? ''));
        }
    } catch (Throwable $e) {
        $debugDbName = '';
    }

    try {
        $resCnt = $conn->query('SELECT COUNT(*) AS c FROM training_program_evaluations');
        if ($resCnt) {
            $debugEvalCount = (int)(($resCnt->fetch_assoc()['c'] ?? 0));
        }
    } catch (Throwable $e) {
        $debugEvalCount = null;
    }
}

$groups = [];
foreach ($rows as $r) {
    $pid = (int)($r['program_id'] ?? 0);
    $sub = (int)($r['submission_no'] ?? 1);
    if ($sub <= 0) $sub = 1;
    if ($pid <= 0) continue;

    $k = (string)$pid;
    if (!isset($groups[$k])) {
        $groups[$k] = [
            'program_id' => $pid,
            'training_title' => (string)($r['training_title'] ?? ''),
            'training_type' => (string)($r['training_type'] ?? ''),
            'training_mode' => (string)($r['training_mode'] ?? ''),
            'target_audience' => (string)($r['target_audience'] ?? ''),
            'start_datetime' => (string)($r['start_datetime'] ?? ''),
            'end_datetime' => (string)($r['end_datetime'] ?? ''),
            'status' => (string)($r['status'] ?? ''),
            'category' => (string)($r['category'] ?? ''),
            'description' => (string)($r['description'] ?? ''),
            'latest_submission_no' => $sub,
            'submission_nos' => [],
            'evaluations' => [],
        ];
    }

    if ($sub > (int)$groups[$k]['latest_submission_no']) {
        $groups[$k]['latest_submission_no'] = $sub;
    }
    $groups[$k]['submission_nos'][$sub] = true;

    $groups[$k]['evaluations'][] = $r;
}

$groups = array_values($groups);

$evaluationItems = [];
if (!empty($rows)) {
    try {
        $evalIds = [];
        foreach ($rows as $r) {
            $eid = (int)($r['id'] ?? 0);
            if ($eid > 0) $evalIds[$eid] = true;
        }
        if (!empty($evalIds)) {
            $idList = implode(',', array_map('intval', array_keys($evalIds)));
            $sqlIt = "SELECT evaluation_id, item_type, item_label, rating_percentage
                      FROM training_program_evaluation_items
                      WHERE evaluation_id IN ($idList)
                      ORDER BY evaluation_id, item_type, id";
            $resIt = $conn->query($sqlIt);
            if ($resIt) {
                while ($it = $resIt->fetch_assoc()) {
                    $eid = (int)($it['evaluation_id'] ?? 0);
                    if ($eid <= 0) continue;
                    if (!isset($evaluationItems[$eid])) $evaluationItems[$eid] = [];
                    $evaluationItems[$eid][] = $it;
                }
            }
        }
    } catch (Throwable $e) {
        $evaluationItems = [];
    }
}

if (isset($_GET['print']) && (string)$_GET['print'] === '1') {
    $printEvalId = isset($_GET['eval_id']) ? (int)$_GET['eval_id'] : 0;
    if ($printEvalId > 0) {
        $report = null;
        $items = [];
        $printErr = '';

        try {
            $sql = "SELECT te.id,
                           te.program_id,
                           te.submission_no,
                           te.evaluator_name,
                           te.overall_percentage,
                           te.remarks,
                           te.created_at,
                           p.training_title,
                           p.training_type,
                           p.training_mode,
                           p.target_audience,
                           p.start_datetime,
                           p.end_datetime,
                           p.status,
                           p.category,
                           p.description,
                           m.mentor_name
                    FROM training_program_evaluations te
                    JOIN training_programs p ON p.id = te.program_id
                    LEFT JOIN mentors m ON m.id = p.mentor_id
                    WHERE te.id = " . (int)$printEvalId . "
                    LIMIT 1";
            $res = $conn->query($sql);
            if ($res) {
                $report = $res->fetch_assoc();
            }
        } catch (Throwable $e) {
            $printErr = $e->getMessage();
            $report = null;
        }

        if (!$report) {
            try {
                $sql = "SELECT te.id,
                               te.program_id,
                               te.submission_no,
                               te.evaluator_name,
                               te.overall_percentage,
                               te.remarks,
                               te.created_at,
                               p.training_title,
                               p.training_type,
                               p.training_mode,
                               p.target_audience,
                               p.start_datetime,
                               p.end_datetime,
                               p.status,
                               p.category,
                               p.description,
                               NULL AS mentor_name
                        FROM training_program_evaluations te
                        JOIN training_programs p ON p.id = te.program_id
                        WHERE te.id = " . (int)$printEvalId . "
                        LIMIT 1";
                $res = $conn->query($sql);
                if ($res) {
                    $report = $res->fetch_assoc();
                }
            } catch (Throwable $e) {
                $printErr = $printErr !== '' ? ($printErr . ' | ' . $e->getMessage()) : $e->getMessage();
                $report = null;
            }
        }

        try {
            $sqlItems = "SELECT item_type, item_label, rating_percentage
                         FROM training_program_evaluation_items
                         WHERE evaluation_id = " . (int)$printEvalId . "
                         ORDER BY item_type, id";
            $resIt = $conn->query($sqlItems);
            if ($resIt) {
                while ($r = $resIt->fetch_assoc()) {
                    $items[] = $r;
                }
            }
        } catch (Throwable $e) {
        }

        $evaluator = '';
        if ($report) {
            $evaluator = trim((string)($report['mentor_name'] ?? '')) !== '' ? (string)$report['mentor_name'] : (string)($report['evaluator_name'] ?? '');
        }

        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" /><title>Evaluation Report</title><script src="https://cdn.tailwindcss.com"></script><link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" /><style media="print">.no-print{display:none!important}.print-two-col{display:grid!important;grid-template-columns:1fr 1fr!important;gap:1rem!important}</style></head><body class="bg-white">';
        echo '<div class="max-w-5xl mx-auto p-6 space-y-6">';
        echo '<div class="flex items-center justify-between gap-3">';
        echo '<div><div class="text-2xl font-bold">Evaluation Report</div><div class="text-sm text-gray-500">Evaluation ID: ' . h($printEvalId) . '</div></div>';
        echo '<div class="no-print flex gap-2"><a class="btn btn-outline btn-sm" href="evaluation_repository.php">Back</a><button type="button" class="btn btn-primary btn-sm" onclick="window.print()">Print</button></div>';
        echo '</div>';

        if (!$report) {
            echo '<div class="alert alert-error"><div><div class="font-bold">Not found</div><div class="text-sm">Evaluation not found.' . ($printErr !== '' ? (' ' . h($printErr)) : '') . '</div></div></div>';
            echo '</div></body></html>';
            exit;
        }

        echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-4 print-two-col">';
        echo '<div class="bg-base-100 border rounded-xl p-5">';
        echo '<div class="text-lg font-bold">' . h($report['training_title'] ?? '') . '</div>';
        echo '<div class="text-sm text-gray-600 mt-1">' . h($report['training_type'] ?? '') . ' · ' . h($report['training_mode'] ?? '') . ' · ' . h($report['target_audience'] ?? '') . '</div>';
        echo '<div class="text-sm text-gray-600">' . h($report['start_datetime'] ?? '') . ' to ' . h($report['end_datetime'] ?? '') . '</div>';
        echo '<div class="text-xs text-gray-500 mt-1">Submission No: ' . h($report['submission_no'] ?? '') . ' · Status: ' . h($report['status'] ?? '') . '</div>';
        echo '<div class="mt-3"><div class="text-sm font-semibold text-gray-700">Description</div><div class="text-sm text-gray-700 whitespace-pre-line mt-1">' . h($report['description'] ?? '') . '</div></div>';
        echo '</div>';

        echo '<div class="bg-base-100 border rounded-xl p-5">';
        echo '<div class="grid grid-cols-1 md:grid-cols-3 gap-4">';
        echo '<div><div class="text-xs text-gray-500">Evaluator</div><div class="font-semibold">' . h($evaluator) . '</div></div>';
        echo '<div><div class="text-xs text-gray-500">Overall</div><div class="font-bold">' . h($report['overall_percentage'] ?? '') . '%</div></div>';
        echo '<div><div class="text-xs text-gray-500">Submitted</div><div class="font-semibold">' . h($report['created_at'] ?? '') . '</div></div>';
        echo '</div>';
        if (trim((string)($report['remarks'] ?? '')) !== '') {
            echo '<div class="mt-4"><div class="text-sm font-semibold">Remarks</div><div class="text-sm">' . h($report['remarks']) . '</div></div>';
        }
        echo '</div>';
        echo '</div>';

        echo '<div class="bg-base-100 border rounded-xl p-5">';
        echo '<div class="text-sm font-semibold">Ratings</div>';
        if (empty($items)) {
            echo '<div class="text-sm text-gray-500 mt-2">No rating items found.</div>';
        } else {
            echo '<div class="overflow-x-auto mt-3"><table class="table table-sm"><thead><tr><th>Type</th><th>Item</th><th class="text-right">Rating (%)</th></tr></thead><tbody>';
            foreach ($items as $it) {
                echo '<tr><td class="text-xs text-gray-600">' . h($it['item_type'] ?? '') . '</td><td class="text-sm">' . h($it['item_label'] ?? '') . '</td><td class="text-right font-semibold">' . h($it['rating_percentage'] ?? '') . '%</td></tr>';
            }
            echo '</tbody></table></div>';
        }
        echo '</div>';
        echo '</div>';
        echo '<script>setTimeout(function(){window.print();}, 400);</script>';
        echo '</body></html>';
        exit;
    }
}
require('../../partials/header.php');
?>
    <style media="print">
        .no-print { display: none !important; }
        details.collapse > summary { list-style: none; }
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

        <main class="container mx-auto px-4 py-6 space-y-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Evaluation Repository</h1>
                    <div class="text-sm text-gray-500">All submitted training evaluations per posted training</div>
                </div>
                <div class="flex gap-2">
                    
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between gap-3">
                    <div class="font-semibold text-gray-800">Summary</div>
                    <div class="text-sm text-gray-500">Trainings with evaluations: <?php echo count($groups); ?> · Total evaluations: <?php echo count($rows); ?></div>
                </div>
                <?php if (trim($queryError) !== ''): ?>
                    <div class="mt-3 text-sm text-red-600">
                        Failed to fetch evaluation data: <?php echo h($queryError); ?>
                    </div>
                <?php endif; ?>
                <?php if ($debug): ?>
                    <div class="mt-3 text-xs text-gray-500">
                        DB: <?php echo h($debugDbName); ?> · training_program_evaluations rows: <?php echo $debugEvalCount === null ? 'n/a' : (int)$debugEvalCount; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($groups)): ?>
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="text-gray-600">No evaluations submitted yet.</div>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($groups as $g): ?>
                        <details class="collapse collapse-arrow bg-white shadow-md">
                            <summary class="collapse-title">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                    <div>
                                        <div class="text-lg font-bold text-gray-900"><?php echo h($g['training_title']); ?></div>
                                        <div class="text-sm text-gray-600 mt-1"><?php echo h($g['training_type']); ?> · <?php echo h($g['training_mode']); ?> · <?php echo h($g['target_audience']); ?></div>
                                        <div class="text-sm text-gray-600"><?php echo h($g['start_datetime']); ?> to <?php echo h($g['end_datetime']); ?></div>
                                        <div class="text-xs text-gray-500 mt-1">Submissions: <?php echo (int)count($g['submission_nos']); ?> · Status: <?php echo h($g['status']); ?></div>
                                    </div>
                                    <div class="flex flex-wrap gap-2 items-center">
                                        <span class="badge badge-outline">Evaluations: <?php echo (int)count($g['evaluations']); ?></span>
                                        <a class="btn btn-sm btn-accent" href="evaluatio.php?program_id=<?php echo (int)$g['program_id']; ?>&submission_no=<?php echo (int)($g['latest_submission_no'] ?? 1); ?>">Evaluate</a>
                                    </div>
                                </div>
                            </summary>
                            <div class="collapse-content">
                                <div class="mt-2">
                                    <div class="text-sm font-semibold text-gray-700">Description</div>
                                    <div class="text-sm text-gray-700 whitespace-pre-line mt-1"><?php echo h($g['description']); ?></div>
                                </div>

                                <div class="overflow-x-auto mt-4">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Submission No</th>
                                                <th>Evaluator</th>
                                                <th>Overall</th>
                                                <th>Submitted</th>
                                                <th class="no-print">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (($g['evaluations'] ?? []) as $ev): ?>
                                                <tr>
                                                    <td><?php echo (int)($ev['submission_no'] ?? 1); ?></td>
                                                    <td><?php echo h(trim((string)($ev['mentor_name'] ?? '')) !== '' ? $ev['mentor_name'] : ($ev['evaluator_name'] ?? '')); ?></td>
                                                    <td class="font-semibold"><?php echo h($ev['overall_percentage'] ?? ''); ?>%</td>
                                                    <td class="text-sm text-gray-500"><?php echo h($ev['created_at'] ?? ''); ?></td>
                                                    <td class="no-print">
                                                        <div class="flex gap-2">
                                                            <a class="btn btn-xs btn-outline" target="_blank" href="evaluation_repository.php?print=1&eval_id=<?php echo (int)($ev['id'] ?? 0); ?>">Print</a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php
                                                    $eid = (int)($ev['id'] ?? 0);
                                                    $items = $eid > 0 ? ($evaluationItems[$eid] ?? []) : [];
                                                ?>
                                                <?php if (!empty($items)): ?>
                                                    <tr>
                                                        <td colspan="5" class="bg-base-200">
                                                            <div class="text-sm font-semibold">Ratings</div>
                                                            <div class="overflow-x-auto mt-2">
                                                                <table class="table table-sm">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Type</th>
                                                                            <th>Item</th>
                                                                            <th class="text-right">Rating (%)</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($items as $it): ?>
                                                                            <tr>
                                                                                <td class="text-xs text-gray-600"><?php echo h($it['item_type'] ?? ''); ?></td>
                                                                                <td class="text-sm"><?php echo h($it['item_label'] ?? ''); ?></td>
                                                                                <td class="text-right font-semibold"><?php echo h($it['rating_percentage'] ?? ''); ?>%</td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                                <?php if (trim((string)($ev['remarks'] ?? '')) !== ''): ?>
                                                    <tr>
                                                        <td colspan="5" class="bg-base-200">
                                                            <div class="text-sm"><span class="font-semibold">Remarks:</span> <?php echo h($ev['remarks']); ?></div>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
  <?php require('../../partials/footer.php') ?>
