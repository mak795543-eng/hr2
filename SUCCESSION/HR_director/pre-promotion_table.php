<?php
require_once __DIR__ . '/../../COMPETENCY/criticalgaps/config.php';

$flashOk = (string)($_GET['ok'] ?? '');
$flashErr = (string)($_GET['err'] ?? '');

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $employeeId = trim((string)($_POST['employee_id'] ?? ''));

    if ($action === 'send_promotion' && $employeeId !== '') {
        try {
            $stmt = $pdo->prepare(
                "UPDATE pre_promotion_employees
                 SET promotion_status = 'sent',
                     promotion_sent_at = CURRENT_TIMESTAMP
                 WHERE employee_id = ?"
            );
            $stmt->execute([$employeeId]);

            logAction($employeeId, 'Succession', 'Promotion Sent', 'Pre-promotion notification sent');

            header('Location: pre-promotion_table.php?ok=sent');
            exit;
        } catch (Throwable $e) {
            header('Location: pre-promotion_table.php?err=failed');
            exit;
        }
    }

    header('Location: pre-promotion_table.php?err=invalid');
    exit;
}

$rows = [];
try {
    $stmt = $pdo->query(
        "SELECT employee_id, name, department, current_position, competency, succession_status,
                target_role, readiness_level, expected_transition_date, mentor_coach,
                promotion_status, promotion_sent_at, date_added
         FROM pre_promotion_employees
         ORDER BY date_added DESC"
    );
    $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
} catch (Throwable $e) {
    $rows = [];
}

require('../../partials/header.php');
?>

<body class="bg-gray-50 min-h-screen">
  <div class="flex h-screen">
    <?php include '../../USM/sidebarr.php'; ?>

    <div class="flex flex-col flex-1 overflow-auto">
      <?php include '../../USM/navbar.php'; ?>

      <main class="flex-1 p-4 md:p-6">
        <div class="max-w-7xl mx-auto">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Pre-Promotion Candidates</h1>
              <p class="text-sm text-gray-500">Succession Ready employees with readiness level and competency stats.</p>
            </div>
            <a href="succession_dashboard.php" class="btn btn-outline btn-sm">Back</a>
          </div>

          <div class="card bg-base-100 shadow">
            <div class="card-body">
              <div class="overflow-auto">
                <table class="table table-zebra w-full">
                  <thead>
                    <tr>
                      <th>Employee</th>
                      <th>Department</th>
                      <th>Current Position</th>
                      <th>Target Role</th>
                      <th class="text-right">KPI %</th>
                      <th>Readiness</th>
                      <th>Promotion Status</th>
                      <th>Sent At</th>
                      <th class="text-right">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (count($rows) === 0): ?>
                      <tr>
                        <td colspan="9" class="text-center text-gray-500">No pre-promotion candidates.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($rows as $r): ?>
                        <?php
                          $empId = (string)($r['employee_id'] ?? '');
                          $st = strtolower(trim((string)($r['promotion_status'] ?? 'pending')));
                          $badge = 'badge-ghost';
                          if ($st === 'pending') $badge = 'badge-warning';
                          if ($st === 'sent') $badge = 'badge-info';
                          if ($st === 'promoted') $badge = 'badge-success';
                          if ($st === 'cancelled') $badge = 'badge-error';
                          $sentAt = (string)($r['promotion_sent_at'] ?? '');
                        ?>
                        <tr>
                          <td>
                            <div class="font-semibold text-gray-900"><?php echo h($r['name'] ?? ''); ?></div>
                            <div class="text-xs text-gray-500"><?php echo h($empId); ?></div>
                          </td>
                          <td><?php echo h($r['department'] ?? ''); ?></td>
                          <td><?php echo h($r['current_position'] ?? ''); ?></td>
                          <td><?php echo h($r['target_role'] ?? ''); ?></td>
                          <td class="text-right"><?php echo number_format((float)($r['competency'] ?? 0), 1); ?>%</td>
                          <td><?php echo h($r['readiness_level'] ?? ''); ?></td>
                          <td><span class="badge <?php echo $badge; ?>"><?php echo h(strtoupper($st)); ?></span></td>
                          <td class="text-xs text-gray-600"><?php echo $sentAt !== '' ? h($sentAt) : '-'; ?></td>
                          <td class="text-right">
                            <?php if ($st === 'pending'): ?>
                              <form method="post" class="inline send-promotion-form">
                                <input type="hidden" name="action" value="send_promotion" />
                                <input type="hidden" name="employee_id" value="<?php echo h($empId); ?>" />
                                <button type="submit" class="btn btn-primary btn-sm">Send Promotion</button>
                              </form>
                            <?php else: ?>
                              <span class="text-xs text-gray-400">—</span>
                            <?php endif; ?>
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
      </main>
    </div>
  </div>

  <script>
    (function () {
      var ok = <?php echo json_encode($flashOk); ?>;
      var err = <?php echo json_encode($flashErr); ?>;

      if (window.Swal) {
        if (ok === 'sent') {
          Swal.fire({ icon: 'success', title: 'Promotion Sent', text: 'Promotion has been sent.', timer: 1600, showConfirmButton: false });
        }
        if (ok === 'saved') {
          Swal.fire({ icon: 'success', title: 'Saved', text: 'Saved to Pre-Promotion list.', timer: 1600, showConfirmButton: false });
        }
        if (err === 'failed') {
          Swal.fire({ icon: 'error', title: 'Failed', text: 'Failed to send promotion.' });
        }
        if (err === 'invalid') {
          Swal.fire({ icon: 'error', title: 'Invalid', text: 'Invalid request.' });
        }
      }

      Array.from(document.querySelectorAll('.send-promotion-form')).forEach(function (form) {
        form.addEventListener('submit', function (e) {
          if (!window.Swal) return;
          e.preventDefault();
          Swal.fire({
            icon: 'question',
            title: 'Send Promotion?',
            text: 'This will notify the employee for promotion. Continue?',
            showCancelButton: true,
            confirmButtonText: 'Yes, Send',
            cancelButtonText: 'Cancel'
          }).then(function (r) {
            if (r.isConfirmed) {
              form.submit();
            }
          });
        });
      });
    })();
  </script>

<?php require('../../partials/footer.php') ?>