<?php
require_once __DIR__ . '/../../COMPETENCY/criticalgaps/criticalgaps/config.php';

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $skillIdRaw = trim((string)($_POST['skill_id'] ?? ''));
    $pctRaw = trim((string)($_POST['standard_percentage'] ?? ''));

    try {
        if ($skillIdRaw === '' || !ctype_digit($skillIdRaw)) {
            throw new RuntimeException('Invalid skill.');
        }
        $skillId = (int)$skillIdRaw;

        if ($pctRaw === '' || !is_numeric($pctRaw)) {
            throw new RuntimeException('Invalid percentage.');
        }
        $pct = (float)$pctRaw;
        if ($pct < 0) $pct = 0;
        if ($pct > 100) $pct = 100;

        $stmt = $pdo->prepare(
            "INSERT INTO general_skill_standards (skill_id, standard_percentage)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE standard_percentage = VALUES(standard_percentage)"
        );
        $stmt->execute([$skillId, $pct]);

        $flash = ['type' => 'success', 'message' => 'Standard updated.'];
    } catch (Throwable $e) {
        $flash = ['type' => 'error', 'message' => ($e->getMessage() !== '' ? $e->getMessage() : 'Request failed.')];
    }
}

$departmentFilter = (string)($_GET['department'] ?? 'all');
$search = trim((string)($_GET['search'] ?? ''));

$departments = [];
try {
    $departments = getDepartments();
} catch (Throwable $e) {
    $departments = [];
}

$where = ["s.category = 'General Skills'"];
$params = [];

if ($departmentFilter !== 'all' && $departmentFilter !== '') {
    $where[] = 's.department = ?';
    $params[] = $departmentFilter;
}

if ($search !== '') {
    $like = '%' . $search . '%';
    $where[] = '(s.skill_name LIKE ? OR s.department LIKE ?)';
    $params[] = $like;
    $params[] = $like;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare(
    "SELECT s.id AS skill_id,
            s.skill_name,
            s.department,
            COALESCE(gss.standard_percentage, 80) AS standard_percentage
     FROM skills s
     LEFT JOIN general_skill_standards gss ON gss.skill_id = s.id
     $whereSql
     ORDER BY s.department ASC, s.skill_name ASC"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>General Skill Standards</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
</head>
<body class="bg-base-200 min-h-screen">
  <div class=\"flex h-screen\">
    <!-- Sidebar -->
    <?php 
    // Use relative path or absolute path based on your directory structure
    include '../../../USM/sidebarr.php'; 
    ?>
    <!-- Content Area -->
    <div class=\"flex flex-col flex-1 overflow-auto\">      <!-- Navbar -->
      <?php include '../../../USM/navbar.php'; ?>

    <div class="max-w-7xl mx-auto p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold">General Skill Standards</h1>
                <div class="text-sm opacity-70">Edit standard competency % per General Skill (scalable).</div>
            </div>
            <div class="flex gap-2">
                <a href="../../COMPETENCY/criticalgaps/criticalgaps/gap_analysis.php" class="btn btn-outline btn-sm">Back to Gap Analysis</a>
                <a href="../../COMPETENCY/criticalgaps/criticalgaps/general_skills_management.php" class="btn btn-primary btn-sm">General Skills Management</a>
                <a href="succession_dashboard.php" class="btn btn-outline btn-sm">Succession Dashboard</a>
            </div>
        </div>

        <?php if (is_array($flash)): ?>
            <div class="alert <?php echo $flash['type'] === 'success' ? 'alert-success' : 'alert-error'; ?> mb-6">
                <span><?php echo h($flash['message'] ?? ''); ?></span>
            </div>
        <?php endif; ?>

        <div class="card bg-base-100 shadow mb-6">
            <div class="card-body">
                <form method="GET" class="flex flex-col md:flex-row gap-3 md:items-end">
                    <div class="flex-1">
                        <label class="label"><span class="label-text">Search</span></label>
                        <input type="text" name="search" value="<?php echo h($search); ?>" placeholder="Search skill or department" class="input input-bordered w-full" />
                    </div>

                    <div class="w-full md:w-64">
                        <label class="label"><span class="label-text">Department</span></label>
                        <select name="department" class="select select-bordered w-full">
                            <option value="all" <?php echo $departmentFilter === 'all' ? 'selected' : ''; ?>>All Departments</option>
                            <?php foreach (($departments ?? []) as $dept): ?>
                                <option value="<?php echo h($dept); ?>" <?php echo $departmentFilter === $dept ? 'selected' : ''; ?>><?php echo h($dept); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="general_skill_standards.php" class="btn btn-outline">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card bg-base-100 shadow">
            <div class="card-body p-0">
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>General Skill</th>
                                <th class="text-right">Standard %</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($rows) === 0): ?>
                                <tr><td colspan="4" class="text-center py-10 opacity-70">No skills found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td><?php echo h($r['department']); ?></td>
                                        <td>
                                            <div class="font-semibold"><?php echo h($r['skill_name']); ?></div>
                                            <div class="text-xs opacity-70">Skill ID: <?php echo h($r['skill_id']); ?></div>
                                        </td>
                                        <td class="text-right">
                                            <form method="post" class="flex items-center justify-end gap-2">
                                                <input type="hidden" name="skill_id" value="<?php echo h($r['skill_id']); ?>" />
                                                <input type="number" step="0.1" min="0" max="100" name="standard_percentage" value="<?php echo h(number_format((float)$r['standard_percentage'], 1, '.', '')); ?>" class="input input-bordered input-sm w-28 text-right" required />
                                        </td>
                                        <td class="text-right">
                                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>
  </div>
  <script src="../../../soliera.js"></script>
  <script src="../../../sidebar.js"></script>
</body>
</html>

