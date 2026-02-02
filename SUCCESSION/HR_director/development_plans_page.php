<?php
require_once __DIR__ . '/../../COMPETENCY/criticalgaps/config.php';

$flashOk = (string)($_GET['ok'] ?? '');
$flashErr = (string)($_GET['err'] ?? '');

$criteriaRows = [];
$plansByCriteria = [];

try {
    ensureDevelopmentPlanLibrarySchema();
    seedDevelopmentPlanLibrary();

    $stmtC = $pdo->query(
        "SELECT id, name, description
         FROM development_plan_criteria
         ORDER BY name ASC"
    );
    $criteriaRows = $stmtC->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $stmtP = $pdo->query(
        "SELECT t.criteria_id, t.status, t.plan_text
         FROM development_plan_templates t
         ORDER BY t.criteria_id ASC, FIELD(t.status,'Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready')"
    );
    $tpl = $stmtP->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($tpl as $r) {
        $cid = (int)($r['criteria_id'] ?? 0);
        $st = (string)($r['status'] ?? '');
        if ($cid <= 0 || $st === '') continue;
        if (!isset($plansByCriteria[$cid])) $plansByCriteria[$cid] = [];
        $plansByCriteria[$cid][$st] = (string)($r['plan_text'] ?? '');
    }
} catch (Throwable $e) {
    $criteriaRows = [];
    $plansByCriteria = [];
}

$statusCounts = [
    'Retrain' => 0,
    'Reskilling' => 0,
    'Refresher Training' => 0,
    'Upskilling' => 0,
    'Succession Ready' => 0,
];
try {
    $stmtS = $pdo->query(
        "SELECT COALESCE(e.status, ss.status, 'Retrain') AS status, COUNT(*) AS c
         FROM succession_submissions ss
         LEFT JOIN employees e ON e.employee_id = ss.employee_id
         WHERE ss.is_pushed = 1
         GROUP BY COALESCE(e.status, ss.status, 'Retrain')"
    );
    $rowsS = $stmtS->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($rowsS as $r) {
        $st = (string)($r['status'] ?? '');
        $c = (int)($r['c'] ?? 0);
        if ($st !== '') {
            $statusCounts[$st] = $c;
        }
    }
} catch (Throwable $e) {
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$allowedStatuses = ['Retrain', 'Reskilling', 'Refresher Training', 'Upskilling', 'Succession Ready'];

function splitPlanItemsLocal(string $planText): array
{
    $lines = preg_split('/\r?\n/', (string)$planText);
    $out = [];
    foreach (($lines ?: []) as $ln) {
        $ln = trim((string)$ln);
        if ($ln === '') continue;
        if (strpos($ln, '- ') === 0) {
            $ln = trim(substr($ln, 2));
        }
        if ($ln !== '') $out[] = $ln;
    }
    return $out;
}

require('../../partials/header.php');
?>
<body class="bg-gray-50 min-h-screen">
  <div class="flex h-screen">
    <?php include '../../USM/sidebarr.php'; ?>

    <div class="flex flex-col flex-1 overflow-auto">
      <?php include '../../USM/navbar.php'; ?>

      <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
        <div class="max-w-7xl mx-auto p-6 space-y-6">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h1 class="text-2xl font-bold">Development Plans</h1>
              <div class="text-sm opacity-70">Criteria: <span class="font-semibold"><?php echo (int)count($criteriaRows); ?></span></div>
            </div>
            <div class="flex items-center gap-2">
              <a href="succession_dashboard.php" class="btn btn-outline btn-sm">Dashboard</a>
            </div>
          </div>

          <div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
              <div class="card bg-base-100 shadow"><div class="card-body p-5"><div class="text-xs opacity-70">Retrain</div><div class="text-3xl font-bold"><?php echo (int)($statusCounts['Retrain'] ?? 0); ?></div></div></div>
              <div class="card bg-base-100 shadow"><div class="card-body p-5"><div class="text-xs opacity-70">Reskilling</div><div class="text-3xl font-bold"><?php echo (int)($statusCounts['Reskilling'] ?? 0); ?></div></div></div>
              <div class="card bg-base-100 shadow"><div class="card-body p-5"><div class="text-xs opacity-70">Refresher Training</div><div class="text-3xl font-bold"><?php echo (int)($statusCounts['Refresher Training'] ?? 0); ?></div></div></div>
              <div class="card bg-base-100 shadow"><div class="card-body p-5"><div class="text-xs opacity-70">Upskilling</div><div class="text-3xl font-bold"><?php echo (int)($statusCounts['Upskilling'] ?? 0); ?></div></div></div>
              <div class="card bg-base-100 shadow"><div class="card-body p-5"><div class="text-xs opacity-70">Succession Ready</div><div class="text-3xl font-bold"><?php echo (int)($statusCounts['Succession Ready'] ?? 0); ?></div></div></div>
            </div>
          </div>

          <?php if ($flashOk !== ''): ?>
            <div class="alert alert-success shadow">
              <div>Action completed.</div>
            </div>
          <?php endif; ?>
          <?php if ($flashErr !== ''): ?>
            <div class="alert alert-error shadow">
              <div>Something went wrong.</div>
            </div>
          <?php endif; ?>

          <?php if (count($criteriaRows) === 0): ?>
            <div class="card bg-base-100 shadow">
              <div class="card-body">
                <div class="opacity-70">No development plan criteria found.</div>
              </div>
            </div>
          <?php else: ?>
            <div class="space-y-4">
              <?php foreach ($criteriaRows as $c): ?>
                <?php
                  $cid = (int)($c['id'] ?? 0);
                  $cname = (string)($c['name'] ?? '');
                  $cdesc = (string)($c['description'] ?? '');
                  $tpl = $plansByCriteria[$cid] ?? [];
                ?>
                <div class="card bg-base-100 shadow card-bordered">
                  <div class="card-body">
                    <div class="flex items-start justify-between gap-3">
                      <div>
                        <div class="text-lg font-bold"><?php echo h($cname); ?></div>
                        <?php if ($cdesc !== ''): ?>
                          <div class="text-sm opacity-70 mt-1"><?php echo h($cdesc); ?></div>
                        <?php endif; ?>
                      </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3 mt-4">
                      <?php foreach ($allowedStatuses as $st): ?>
                        <?php
                          $planText = (string)($tpl[$st] ?? '');
                          $items = $planText !== '' ? splitPlanItemsLocal($planText) : [];
                        ?>
                        <div class="rounded-lg border border-base-200 bg-base-50 p-4">
                          <div class="text-sm font-semibold mb-2"><?php echo h($st); ?></div>
                          <?php if (count($items) === 0): ?>
                            <div class="text-sm opacity-60">No items.</div>
                          <?php else: ?>
                            <ul class="list-disc ml-5 text-sm space-y-1">
                              <?php foreach ($items as $it): ?>
                                <li><?php echo h($it); ?></li>
                              <?php endforeach; ?>
                            </ul>
                          <?php endif; ?>
                        </div>
                      <?php endforeach; ?>
                    </div>

                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        </div>
      </main>
    </div>
  </div>
<?php require('../../partials/footer.php');
