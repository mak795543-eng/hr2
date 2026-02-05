<?php
session_start();

require __DIR__ . '/db.php';

$employeeId = ess_employee_id($conn);

$role = trim((string)($_SESSION['role'] ?? ''));
$roleLower = strtolower($role);

$notifications = [];

$sinceTs = time() - 86400;
$since = date('Y-m-d H:i:s', $sinceTs);

$learningConn = null;
if ($roleLower !== '') {
    require_once __DIR__ . '/../LEARNING/db.php';
    $learningConn = usm_db_connect('hr2_learning_db');
    if ($learningConn && !$learningConn->connect_error) {
        $learningConn->set_charset('utf8mb4');
    } else {
        $learningConn = null;
    }
}

$summary = [
    'documents' => ['count' => 0, 'label' => 'Documents', 'link' => 'mydocuments.php'],
    'leave' => ['count' => 0, 'label' => 'Leave Requests', 'link' => 'leaverequest.php'],
    'payments' => ['count' => 0, 'label' => 'Payment History', 'link' => 'paymenthistory.php'],
    'claims' => ['count' => 0, 'label' => 'Claims', 'link' => 'submitclaim.php'],
];

$recentActivities = [];

if ($conn && $employeeId) {
    $stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS c FROM employee_documents WHERE employee_id = ?');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        $summary['documents']['count'] = (int)($row['c'] ?? 0);
        mysqli_stmt_close($stmt);
    }

if ($learningConn && $roleLower !== '') {
    $stmt = $learningConn->prepare(
        "SELECT id, title, topic, created_at
         FROM learning_modules
         WHERE status = 'posted'
           AND created_at >= ?
           AND (LOWER(TRIM(roles)) = ? OR FIND_IN_SET(?, LOWER(REPLACE(roles, ', ', ','))) > 0)
         ORDER BY created_at DESC
         LIMIT 3"
    );
    if ($stmt) {
        $stmt->bind_param('sss', $since, $roleLower, $roleLower);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) {
            $id = (int)($row['id'] ?? 0);
            $dt = (string)($row['created_at'] ?? '');
            $notifications[] = [
                'type' => 'Learning Module',
                'title' => (string)($row['title'] ?? 'Module'),
                'meta' => (string)($row['topic'] ?? ''),
                'date' => $dt,
                'link' => 'mymodule.php?view=' . $id,
                'key' => sha1('learning_module|' . $id . '|' . $dt),
                'status' => 'unread',
            ];
        }
        $stmt->close();
    }

    $stmt = $learningConn->prepare(
        "SELECT er.id, er.title, er.created_at
         FROM exam_repository er
         INNER JOIN exam_repository_assignments a
           ON a.exam_id = er.id
          AND a.audience = 'employee'
          AND a.status = 'active'
         WHERE er.status = 'posted'
           AND er.created_at >= ?
           AND LOWER(a.role) = ?
         ORDER BY er.created_at DESC
         LIMIT 3"
    );
    if ($stmt) {
        $stmt->bind_param('ss', $since, $roleLower);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) {
            $id = (int)($row['id'] ?? 0);
            $dt = (string)($row['created_at'] ?? '');
            $notifications[] = [
                'type' => 'Examination',
                'title' => (string)($row['title'] ?? 'Examination'),
                'meta' => 'Assigned for your role',
                'date' => $dt,
                'link' => 'myexamination.php?view=' . $id,
                'key' => sha1('examination|' . $id . '|' . $dt),
                'status' => 'unread',
            ];
        }
        $stmt->close();
    }

    usort($notifications, static function ($a, $b) {
        $ad = strtotime((string)($a['date'] ?? '')) ?: 0;
        $bd = strtotime((string)($b['date'] ?? '')) ?: 0;
        return $bd <=> $ad;
    });
    $notifications = array_slice($notifications, 0, 6);

    if ($conn && $employeeId && count($notifications) > 0) {
        $keys = array_values(array_unique(array_map(static fn($n) => (string)($n['key'] ?? ''), $notifications)));
        $keys = array_values(array_filter($keys, static fn($k) => $k !== ''));
        if (count($keys) > 0) {
            $escaped = array_map(static function ($k) use ($conn) {
                return "'" . mysqli_real_escape_string($conn, $k) . "'";
            }, $keys);
            $sql = "SELECT notif_key, status, deleted FROM notification_states WHERE employee_id = " . (int)$employeeId . " AND notif_key IN (" . implode(',', $escaped) . ")";
            $res = @mysqli_query($conn, $sql);
            $byKey = [];
            while ($res && ($row = mysqli_fetch_assoc($res))) {
                $k = (string)($row['notif_key'] ?? '');
                if ($k === '') continue;
                $byKey[$k] = [
                    'status' => (string)($row['status'] ?? ''),
                    'deleted' => (int)($row['deleted'] ?? 0),
                ];
            }

            $filtered = [];
            foreach ($notifications as $n) {
                $k = (string)($n['key'] ?? '');
                $st = $k !== '' ? ($byKey[$k] ?? null) : null;
                if (is_array($st) && (int)($st['deleted'] ?? 0) === 1) {
                    continue;
                }
                if (is_array($st) && (string)($st['status'] ?? '') !== '') {
                    $n['status'] = (string)$st['status'];
                }
                $filtered[] = $n;
            }
            $notifications = $filtered;
        }
    }
}

if ($learningConn) {
    $learningConn->close();
}

    $stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS c FROM leave_requests WHERE employee_id = ?');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        $summary['leave']['count'] = (int)($row['c'] ?? 0);
        mysqli_stmt_close($stmt);
    }

    $stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS c FROM payment_history WHERE employee_id = ?');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        $summary['payments']['count'] = (int)($row['c'] ?? 0);
        mysqli_stmt_close($stmt);
    }

    $stmt = mysqli_prepare($conn, 'SELECT document_title, status, submitted_at FROM submitted_documents WHERE employee_id = ? ORDER BY submitted_at DESC LIMIT 1');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        if (is_array($row)) {
            $recentActivities[] = [
                'type' => 'Document',
                'title' => (string)($row['document_title'] ?? 'Document Submission'),
                'status' => (string)($row['status'] ?? 'Pending'),
                'date' => date('Y-m-d', strtotime((string)($row['submitted_at'] ?? 'now'))),
                'link' => 'submitdocument.php',
            ];
        }
        mysqli_stmt_close($stmt);
    }

    $stmt = mysqli_prepare($conn, 'SELECT leave_type, start_date, end_date, status, created_at FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC LIMIT 1');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        if (is_array($row)) {
            $title = (string)($row['leave_type'] ?? 'Leave') . ' (' . (string)($row['start_date'] ?? '') . ' - ' . (string)($row['end_date'] ?? '') . ')';
            $recentActivities[] = [
                'type' => 'Leave',
                'title' => $title,
                'status' => (string)($row['status'] ?? 'Pending'),
                'date' => date('Y-m-d', strtotime((string)($row['created_at'] ?? 'now'))),
                'link' => 'leaverequest.php',
            ];
        }
        mysqli_stmt_close($stmt);
    }

    $stmt = mysqli_prepare($conn, 'SELECT pay_period_start, pay_period_end, payment_date, status, net_pay FROM payment_history WHERE employee_id = ? ORDER BY payment_date DESC LIMIT 1');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        if (is_array($row)) {
            $title = 'Pay Period ' . (string)($row['pay_period_start'] ?? '') . ' - ' . (string)($row['pay_period_end'] ?? '');
            $recentActivities[] = [
                'type' => 'Payment',
                'title' => $title,
                'status' => (string)($row['status'] ?? 'Paid'),
                'date' => date('Y-m-d', strtotime((string)($row['payment_date'] ?? 'now'))),
                'link' => 'paymenthistory.php',
            ];
        }
        mysqli_stmt_close($stmt);
    }
}

function badgeClassForType($type) {
    $t = strtolower(trim((string)$type));
    return match ($t) {
        'document' => 'badge-info',
        'leave' => 'badge-warning',
        'payment' => 'badge-success',
        'claim' => 'badge-ghost',
        default => 'badge-ghost',
    };
}

function badgeClassForStatus($status) {
    $s = strtolower(trim((string)$status));
    return match ($s) {
        'uploaded' => 'badge-info',
        'for approval' => 'badge-warning',
        'approved' => 'badge-success',
        'paid' => 'badge-success',
        'pending' => 'badge-warning',
        'rejected' => 'badge-error',
        default => 'badge-ghost',
    };
}

function badgeClassForNotifType($type) {
    $t = strtolower(trim((string)$type));
    return match ($t) {
        'learning module' => 'bg-sky-50 text-sky-700 border border-sky-200',
        'examination' => 'bg-amber-50 text-amber-700 border border-amber-200',
        'training schedule' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
        'promotion' => 'bg-violet-50 text-violet-700 border border-violet-200',
        'approval update' => 'bg-rose-50 text-rose-700 border border-rose-200',
        default => 'bg-gray-50 text-gray-700 border border-gray-200',
    };
}

function viewLabelForNotifType($type) {
    $t = strtolower(trim((string)$type));
    return match ($t) {
        'learning module' => 'View Module',
        'examination' => 'View Exam',
        'training schedule' => 'View Training',
        'promotion' => 'View Promotion',
        'approval update' => 'View Profile',
        default => 'View',
    };
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ESS Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="flex h-screen">
    <?php include '../USM/sidebarr.php'; ?>

    <div class="flex flex-col flex-1 overflow-auto">
      <?php include '../USM/navbar.php'; ?>

      <main class="flex-1 p-4 md:p-6">
        <div class="max-w-6xl mx-auto">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h1 class="text-xl md:text-2xl font-bold text-gray-800">Employee Self Service</h1>
              <p class="text-sm text-gray-500">Quick summary and recent activities for your requests and records.</p>
            </div>
           
          </div>

          <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <a href="<?php echo htmlspecialchars($summary['documents']['link']); ?>" class="card bg-base-100 border border-base-200 shadow-sm hover:shadow transition-shadow">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div class="p-2 rounded-xl bg-base-200">
                    <i data-lucide="file-text" class="w-5 h-5"></i>
                  </div>
                  <span class="badge badge-info badge-outline">Documents</span>
                </div>
                <div class="mt-3">
                  <div class="text-3xl font-bold text-gray-900"><?php echo (int)$summary['documents']['count']; ?></div>
                  <div class="text-sm text-gray-500">Items on file</div>
                </div>
                <div class="mt-3 text-sm text-blue-600 flex items-center gap-1">
                  <span>View</span>
                  <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </div>
              </div>
            </a>

            <a href="<?php echo htmlspecialchars($summary['leave']['link']); ?>" class="card bg-base-100 border border-base-200 shadow-sm hover:shadow transition-shadow">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div class="p-2 rounded-xl bg-base-200">
                    <i data-lucide="calendar-check" class="w-5 h-5"></i>
                  </div>
                  <span class="badge badge-warning badge-outline">Leave</span>
                </div>
                <div class="mt-3">
                  <div class="text-3xl font-bold text-gray-900"><?php echo (int)$summary['leave']['count']; ?></div>
                  <div class="text-sm text-gray-500">Recent requests</div>
                </div>
                <div class="mt-3 text-sm text-blue-600 flex items-center gap-1">
                  <span>View</span>
                  <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </div>
              </div>
            </a>

            <a href="<?php echo htmlspecialchars($summary['payments']['link']); ?>" class="card bg-base-100 border border-base-200 shadow-sm hover:shadow transition-shadow">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div class="p-2 rounded-xl bg-base-200">
                    <i data-lucide="receipt" class="w-5 h-5"></i>
                  </div>
                  <span class="badge badge-success badge-outline">Payments</span>
                </div>
                <div class="mt-3">
                  <div class="text-3xl font-bold text-gray-900"><?php echo (int)$summary['payments']['count']; ?></div>
                  <div class="text-sm text-gray-500">Records</div>
                </div>
                <div class="mt-3 text-sm text-blue-600 flex items-center gap-1">
                  <span>View</span>
                  <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </div>
              </div>
            </a>

            <a href="<?php echo htmlspecialchars($summary['claims']['link']); ?>" class="card bg-base-100 border border-base-200 shadow-sm hover:shadow transition-shadow">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div class="p-2 rounded-xl bg-base-200">
                    <i data-lucide="hand-coins" class="w-5 h-5"></i>
                  </div>
                  <span class="badge badge-ghost badge-outline">Claims</span>
                </div>
                <div class="mt-3">
                  <div class="text-3xl font-bold text-gray-900"><?php echo (int)$summary['claims']['count']; ?></div>
                  <div class="text-sm text-gray-500">Active submissions</div>
                </div>
                <div class="mt-3 text-sm text-blue-600 flex items-center gap-1">
                  <span>View</span>
                  <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </div>
              </div>
            </a>
          </div>

          <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2">
              <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body">
                  <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                      <div class="p-2 rounded-xl bg-base-200">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                      </div>
                      <div>
                        <h2 class="font-semibold text-gray-800">Notifications</h2>
                        <p class="text-sm text-gray-500">Latest notifications (last 24 hours).</p>
                      </div>
                    </div>
                  </div>

                  <div class="mt-3">
                    <div class="join">
                      <button type="button" class="btn btn-sm join-item notif-tab btn-active" data-filter="all">All</button>
                      <button type="button" class="btn btn-sm join-item notif-tab" data-filter="unread">Unread</button>
                      <button type="button" class="btn btn-sm join-item notif-tab" data-filter="archived">Archived</button>
                    </div>
                  </div>

                  <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if ($roleLower === ''): ?>
                      <div class="alert alert-warning">
                        <span>Your session role is missing. Please log in again.</span>
                      </div>
                    <?php elseif (count($notifications) === 0): ?>
                      <div class="text-sm text-gray-500">No new notifications yet.</div>
                    <?php else: ?>
                      <?php foreach ($notifications as $n): ?>
                        <div class="card bg-base-100 border border-base-200 shadow-sm hover:shadow transition-shadow notif-card" data-key="<?php echo htmlspecialchars((string)($n['key'] ?? '')); ?>" data-status="<?php echo htmlspecialchars((string)($n['status'] ?? 'unread')); ?>">
                          <div class="card-body p-4">
                            <div class="flex items-start gap-3">
                              <div class="p-2 rounded-xl bg-base-200 mt-0.5">
                                <i data-lucide="bell" class="w-5 h-5"></i>
                              </div>

                              <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                  <div class="flex items-center gap-2 flex-wrap">
                                    <span class="badge badge-sm <?php echo badgeClassForNotifType($n['type']); ?>"><?php echo htmlspecialchars($n['type']); ?></span>
                                    <span class="text-xs text-gray-500">
                                      <?php echo htmlspecialchars($n['date'] !== '' ? date('M d, Y', strtotime($n['date'])) : ''); ?>
                                    </span>
                                    <?php if (((string)($n['status'] ?? 'unread')) === 'unread'): ?>
                                      <span class="badge badge-sm bg-blue-100 text-blue-700 border border-blue-200">NEW</span>
                                    <?php endif; ?>
                                  </div>

                                  <div class="flex items-center gap-2">
                                    <button type="button" class="btn btn-ghost btn-xs notif-action" data-action="archive" title="Archive">
                                      <i data-lucide="archive" class="w-4 h-4"></i>
                                    </button>
                                    <button type="button" class="btn btn-ghost btn-xs notif-action" data-action="delete" title="Delete">
                                      <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                  </div>
                                </div>
                                <div class="font-semibold text-gray-900 mt-1 truncate"><?php echo htmlspecialchars($n['title']); ?></div>
                                <?php if ((string)($n['meta'] ?? '') !== ''): ?>
                                  <div class="text-sm text-gray-500 mt-1 line-clamp-2"><?php echo htmlspecialchars((string)$n['meta']); ?></div>
                                <?php endif; ?>

                                <div class="mt-2 flex items-center justify-between">
                                  <button type="button" class="link link-primary text-sm notif-action" data-action="read">Mark Read</button>
                                </div>

                                <div class="mt-3">
                                  <a class="btn btn-sm btn-outline w-full notif-view" data-key="<?php echo htmlspecialchars((string)($n['key'] ?? '')); ?>" href="<?php echo htmlspecialchars($n['link']); ?>">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                    <span class="ml-2"><?php echo htmlspecialchars(viewLabelForNotifType((string)($n['type'] ?? ''))); ?></span>
                                  </a>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>

            <div>
              <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body">
                  <div class="flex items-center gap-2">
                    <div class="p-2 rounded-xl bg-base-200">
                      <i data-lucide="sparkles" class="w-5 h-5"></i>
                    </div>
                    <div>
                      <h2 class="font-semibold text-gray-800">Quick Actions</h2>
                      <p class="text-sm text-gray-500">Jump to common tasks.</p>
                    </div>
                  </div>

                  <div class="mt-4 grid grid-cols-1 gap-2">
                    <a href="submitdocument.php" class="btn btn-outline justify-start">
                      <i data-lucide="upload" class="w-4 h-4"></i>
                      <span class="ml-2">Upload Document</span>
                    </a>
                    <a href="leaverequest.php" class="btn btn-outline justify-start">
                      <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                      <span class="ml-2">Request Leave</span>
                    </a>
                    <a href="paymenthistory.php" class="btn btn-outline justify-start">
                      <i data-lucide="receipt" class="w-4 h-4"></i>
                      <span class="ml-2">View Payment History</span>
                    </a>
                    <a href="submitclaim.php" class="btn btn-outline justify-start">
                      <i data-lucide="file-plus" class="w-4 h-4"></i>
                      <span class="ml-2">Submit Claim</span>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </main>
    </div>
  </div>

  <script>
    lucide.createIcons();
  </script>

  <script>
    (function () {
      const stateUrl = 'notification_state.php';
      const tabs = Array.from(document.querySelectorAll('.notif-tab'));
      const cards = () => Array.from(document.querySelectorAll('.notif-card'));

      function setActiveTab(filter) {
        tabs.forEach((t) => {
          const isActive = t.getAttribute('data-filter') === filter;
          t.classList.toggle('btn-active', isActive);
        });
      }

      function applyFilter(filter) {
        cards().forEach((card) => {
          const st = (card.getAttribute('data-status') || 'unread').toLowerCase();
          const show = filter === 'all' || st === filter;
          card.style.display = show ? '' : 'none';
        });
      }

      function updateNewBadge(card, status) {
        const badges = card.querySelectorAll('.badge');
        badges.forEach((b) => {
          if ((b.textContent || '').trim().toUpperCase() === 'NEW') {
            b.style.display = status === 'unread' ? '' : 'none';
          }
        });
      }

      async function postState(action, key) {
        const fd = new FormData();
        fd.append('action', action);
        fd.append('key', key);
        const res = await fetch(stateUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
        return await res.json();
      }

      tabs.forEach((t) => {
        t.addEventListener('click', () => {
          const filter = t.getAttribute('data-filter') || 'all';
          setActiveTab(filter);
          applyFilter(filter);
        });
      });

      document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.notif-action');
        if (!btn) return;
        const card = btn.closest('.notif-card');
        if (!card) return;
        const key = card.getAttribute('data-key') || '';
        const action = btn.getAttribute('data-action') || '';
        if (!key || !action) return;

        e.preventDefault();
        try {
          const data = await postState(action, key);
          if (!data || !data.success) return;

          if (action === 'delete') {
            card.remove();
            return;
          }

          if (action === 'archive') {
            card.setAttribute('data-status', 'archived');
            updateNewBadge(card, 'archived');
          }

          if (action === 'read') {
            card.setAttribute('data-status', 'read');
            updateNewBadge(card, 'read');
          }

          const active = document.querySelector('.notif-tab.btn-active');
          const filter = active ? (active.getAttribute('data-filter') || 'all') : 'all';
          applyFilter(filter);
        } catch (err) {
        }
      });

      document.addEventListener('click', async (e) => {
        const link = e.target.closest('.notif-view');
        if (!link) return;
        const key = link.getAttribute('data-key') || '';
        if (!key) return;

        try {
          const data = await postState('read', key);
          if (!data || !data.success) return;
          const card = link.closest('.notif-card');
          if (!card) return;
          card.setAttribute('data-status', 'read');
          updateNewBadge(card, 'read');
        } catch (err) {
        }
      });

      setActiveTab('all');
      applyFilter('all');
    })();
  </script>
</body>
</html>
