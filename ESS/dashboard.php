<?php
session_start();

require __DIR__ . '/db.php';

$employeeId = ess_employee_id($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_api'])) {
    header('Content-Type: application/json; charset=utf-8');

    if (!$conn || !is_int($employeeId) || $employeeId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $employeeNo = ess_current_employee_no();
    $action = strtolower(trim((string)($_POST['action'] ?? '')));
    $cid = (int)($_POST['complaint_id'] ?? 0);
    if (!in_array($action, ['details', 'schedule'], true) || $cid <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    $stmt = mysqli_prepare(
        $conn,
        'SELECT id, employee_id, subject, description, category, category_other, incident_date, attachment_path, workflow_status, assigned_to_employee_no, meeting_date, meeting_time, meeting_place FROM complaints WHERE id = ? LIMIT 1'
    );
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Server error']);
        exit;
    }
    mysqli_stmt_bind_param($stmt, 'i', $cid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    if (!is_array($row)) {
        echo json_encode(['success' => false, 'message' => 'Not found']);
        exit;
    }

    $assignedTo = (string)($row['assigned_to_employee_no'] ?? '');
    $isAssignee = ($employeeNo !== '' && $assignedTo !== '' && $assignedTo === $employeeNo);
    $isOwner = ((int)($row['employee_id'] ?? 0) === (int)$employeeId);

    if (!$isAssignee && !$isOwner) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    if ($action === 'details') {
        $cat = (string)($row['category'] ?? '');
        $catOther = (string)($row['category_other'] ?? '');
        $categoryDisplay = $cat;
        $catLower = strtolower(trim($cat));
        if (in_array($catLower, ['other', 'others'], true) && $catOther !== '') {
            $categoryDisplay = 'Other - ' . $catOther;
        }

        echo json_encode([
            'success' => true,
            'complaint' => [
                'id' => (int)($row['id'] ?? 0),
                'subject' => (string)($row['subject'] ?? ''),
                'description' => (string)($row['description'] ?? ''),
                'category' => $categoryDisplay,
                'incident_date' => (string)($row['incident_date'] ?? ''),
                'workflow_status' => (string)($row['workflow_status'] ?? ''),
                'attachment_path' => (string)($row['attachment_path'] ?? ''),
                'meeting_date' => (string)($row['meeting_date'] ?? ''),
                'meeting_time' => (string)($row['meeting_time'] ?? ''),
                'meeting_place' => (string)($row['meeting_place'] ?? ''),
            ],
            'is_assignee' => $isAssignee,
            'is_owner' => $isOwner,
        ]);
        exit;
    }

    $meetingDate = trim((string)($_POST['meeting_date'] ?? ''));
    $meetingTime = trim((string)($_POST['meeting_time'] ?? ''));
    $meetingPlace = trim((string)($_POST['meeting_place'] ?? ''));

    if (!$isAssignee) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    if ($meetingDate === '' || $meetingTime === '' || $meetingPlace === '') {
        echo json_encode(['success' => false, 'message' => 'Meeting date, time, and place are required']);
        exit;
    }
    if ($meetingDate < date('Y-m-d')) {
        echo json_encode(['success' => false, 'message' => 'Meeting date cannot be in the past']);
        exit;
    }

    $actorId = ess_employee_id($conn);
    $actor = is_int($actorId) ? $actorId : 0;
    $now = date('Y-m-d H:i:s');

    $stmtUp = mysqli_prepare(
        $conn,
        'UPDATE complaints SET meeting_date = ?, meeting_time = ?, meeting_place = ?, meeting_scheduled_by = ?, meeting_scheduled_at = ?, seen_by_employee = 0 WHERE id = ?'
    );
    if (!$stmtUp) {
        echo json_encode(['success' => false, 'message' => 'Server error']);
        exit;
    }
    mysqli_stmt_bind_param($stmtUp, 'sssisi', $meetingDate, $meetingTime, $meetingPlace, $actor, $now, $cid);
    $ok = mysqli_stmt_execute($stmtUp);
    mysqli_stmt_close($stmtUp);

    if (!$ok) {
        echo json_encode(['success' => false, 'message' => 'Failed to schedule meeting']);
        exit;
    }

    $targetEmployeeId = (int)($row['employee_id'] ?? 0);
    $subject = (string)($row['subject'] ?? '');
    if ($targetEmployeeId > 0) {
        $notifKey = sha1('complaint_meeting|' . $cid . '|' . $meetingDate . '|' . $meetingTime . '|' . $now);
        $notifType = 'Complaint';
        $notifTitle = 'Meeting Scheduled';
        $shortSubj = $subject !== '' ? (' - ' . $subject) : '';
        $notifMeta = 'Meeting scheduled. Complaint ID: ' . $cid . '. Date/Time: ' . $meetingDate . ' ' . $meetingTime . '. Place: ' . $meetingPlace . $shortSubj . '.';
        $notifLink = 'dashboard.php';
        $notifDate = $now;

        $stmtNotif = mysqli_prepare(
            $conn,
            "INSERT INTO notification_states (employee_id, notif_key, status, deleted, notif_type, notif_title, notif_meta, notif_link, notif_date)
             VALUES (?, ?, 'unread', 0, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               status = 'unread',
               deleted = 0,
               notif_type = VALUES(notif_type),
               notif_title = VALUES(notif_title),
               notif_meta = VALUES(notif_meta),
               notif_link = VALUES(notif_link),
               notif_date = VALUES(notif_date),
               updated_at = CURRENT_TIMESTAMP"
        );
        if ($stmtNotif) {
            mysqli_stmt_bind_param($stmtNotif, 'issssss', $targetEmployeeId, $notifKey, $notifType, $notifTitle, $notifMeta, $notifLink, $notifDate);
            @mysqli_stmt_execute($stmtNotif);
            mysqli_stmt_close($stmtNotif);
        }
    }

    echo json_encode(['success' => true]);
    exit;
}

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
    'exams_completed' => ['count' => 0, 'label' => 'Completed Examinations', 'link' => 'myexamination.php'],
];

$recentActivities = [];

if ($learningConn) {
    $empNo = ess_current_employee_no();
    if ($empNo !== '') {
        $stmt = $learningConn->prepare("SELECT COUNT(DISTINCT exam_id) AS c FROM exam_results WHERE employee_id = ? AND taker_type = 'employee'");
        if ($stmt) {
            $stmt->bind_param('s', $empNo);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $summary['exams_completed']['count'] = (int)($row['c'] ?? 0);
            $stmt->close();
        }
    }
}

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

    $persistedByKey = [];
    $stmt = mysqli_prepare(
        $conn,
        "SELECT notif_key, status, deleted, notif_type, notif_title, notif_meta, notif_link, notif_date
         FROM notification_states
         WHERE employee_id = ?
           AND deleted = 0
           AND (status = 'unread' OR notif_date >= ?)
         ORDER BY COALESCE(notif_date, updated_at) DESC
         LIMIT 50"
    );
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'is', $employeeId, $since);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($res && ($row = mysqli_fetch_assoc($res))) {
            $k = (string)($row['notif_key'] ?? '');
            if ($k === '') continue;
            $persistedByKey[$k] = [
                'type' => (string)($row['notif_type'] ?? ''),
                'title' => (string)($row['notif_title'] ?? ''),
                'meta' => (string)($row['notif_meta'] ?? ''),
                'date' => (string)($row['notif_date'] ?? ''),
                'link' => (string)($row['notif_link'] ?? ''),
                'key' => $k,
                'status' => (string)($row['status'] ?? 'unread'),
            ];
        }
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
        $upsert = mysqli_prepare(
            $conn,
            "INSERT INTO notification_states (employee_id, notif_key, status, deleted, notif_type, notif_title, notif_meta, notif_link, notif_date)
             VALUES (?, ?, 'unread', 0, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               notif_type = COALESCE(notif_type, VALUES(notif_type)),
               notif_title = COALESCE(notif_title, VALUES(notif_title)),
               notif_meta = COALESCE(notif_meta, VALUES(notif_meta)),
               notif_link = COALESCE(notif_link, VALUES(notif_link)),
               notif_date = COALESCE(notif_date, VALUES(notif_date))"
        );
        foreach ($notifications as $n) {
            if (!$upsert) break;
            $k = (string)($n['key'] ?? '');
            if ($k === '') continue;
            $type = (string)($n['type'] ?? '');
            $title = (string)($n['title'] ?? '');
            $meta = (string)($n['meta'] ?? '');
            $link = (string)($n['link'] ?? '');
            $dt = (string)($n['date'] ?? '');
            if ($dt === '') {
                $dt = date('Y-m-d H:i:s');
            }
            mysqli_stmt_bind_param($upsert, 'issssss', $employeeId, $k, $type, $title, $meta, $link, $dt);
            @mysqli_stmt_execute($upsert);
        }
        if ($upsert) {
            mysqli_stmt_close($upsert);
        }
    }
}

if ($learningConn) {
    $learningConn->close();
}

if ($conn && $employeeId && !empty($persistedByKey)) {
    foreach ($persistedByKey as $k => $p) {
        if (!isset($p['key']) || (string)$p['key'] === '') continue;
        $notifications[] = $p;
    }
}

if (!empty($notifications)) {
    $uniq = [];
    $merged = [];
    foreach ($notifications as $n) {
        $k = (string)($n['key'] ?? '');
        if ($k === '') continue;
        if (isset($uniq[$k])) continue;
        $uniq[$k] = true;
        $merged[] = $n;
    }
    $notifications = $merged;

    usort($notifications, static function ($a, $b) {
        $ad = strtotime((string)($a['date'] ?? '')) ?: 0;
        $bd = strtotime((string)($b['date'] ?? '')) ?: 0;
        return $bd <=> $ad;
    });
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
        'leave update' => 'bg-blue-50 text-blue-700 border border-blue-200',
        'complaint' => 'bg-orange-50 text-orange-700 border border-orange-200',
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
        'leave update' => 'View',
        'complaint' => 'View',
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
            <a href="<?php echo htmlspecialchars($summary['documents']['link']); ?>" class="card hr2-summary-card border border-base-200 shadow-sm hover:shadow transition-shadow">
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

            <a href="<?php echo htmlspecialchars($summary['leave']['link']); ?>" class="card hr2-summary-card border border-base-200 shadow-sm hover:shadow transition-shadow">
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

            <a href="<?php echo htmlspecialchars($summary['payments']['link']); ?>" class="card hr2-summary-card border border-base-200 shadow-sm hover:shadow transition-shadow">
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

            <a href="<?php echo htmlspecialchars($summary['exams_completed']['link']); ?>" class="card hr2-summary-card border border-base-200 shadow-sm hover:shadow transition-shadow">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div class="p-2 rounded-xl bg-base-200">
                    <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                  </div>
                  <span class="badge badge-warning badge-outline">Examinations</span>
                </div>
                <div class="mt-3">
                  <div class="text-3xl font-bold text-gray-900"><?php echo (int)$summary['exams_completed']['count']; ?></div>
                  <div class="text-sm text-gray-500">Completed examinations</div>
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
                                  <button
                                    type="button"
                                    class="btn btn-sm btn-outline w-full notif-view"
                                    data-key="<?php echo htmlspecialchars((string)($n['key'] ?? '')); ?>"
                                    data-type="<?php echo htmlspecialchars((string)($n['type'] ?? '')); ?>"
                                    data-title="<?php echo htmlspecialchars((string)($n['title'] ?? '')); ?>"
                                    data-meta="<?php echo htmlspecialchars((string)($n['meta'] ?? '')); ?>"
                                    data-date="<?php echo htmlspecialchars((string)($n['date'] ?? '')); ?>"
                                    data-link="<?php echo htmlspecialchars((string)($n['link'] ?? '')); ?>"
                                  >
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                    <span class="ml-2"><?php echo htmlspecialchars(viewLabelForNotifType((string)($n['type'] ?? ''))); ?></span>
                                  </button>
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
                    <a href="complaint.php" class="btn btn-outline justify-start">
                      <i data-lucide="message-square-warning" class="w-4 h-4"></i>
                      <span class="ml-2">File a Complaint</span>
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

  <dialog id="notifViewModal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 class="font-bold text-lg" id="notifModalTitle">Notification</h3>
          <div class="text-sm text-gray-500" id="notifModalDate"></div>
        </div>
        <button type="button" class="btn btn-sm hr2-outline-btn" id="notifModalClose">✕</button>
      </div>

      <div class="divider my-4"></div>

      <div class="text-sm text-gray-700 whitespace-pre-line" id="notifModalBody"></div>

      <div class="modal-action">
        <button type="button" class="btn hr2-primary-btn" id="notifModalOk">OK</button>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
  </dialog>

  <dialog id="complaintNotifModal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 class="font-bold text-lg" id="complaintModalTitle">Complaint</h3>
          <div class="text-sm text-gray-500" id="complaintModalSub"></div>
        </div>
        <button type="button" class="btn btn-sm hr2-outline-btn" id="complaintModalClose">✕</button>
      </div>

      <div class="divider my-4"></div>

      <div class="space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <div class="text-xs font-semibold text-gray-500">SUBJECT</div>
            <div id="complaintModalSubject" class="mt-1 text-gray-800"></div>
          </div>
          <div>
            <div class="text-xs font-semibold text-gray-500">STATUS</div>
            <div id="complaintModalStatus" class="mt-1 text-gray-800"></div>
          </div>
          <div>
            <div class="text-xs font-semibold text-gray-500">CATEGORY</div>
            <div id="complaintModalCategory" class="mt-1 text-gray-800"></div>
          </div>
          <div>
            <div class="text-xs font-semibold text-gray-500">INCIDENT DATE</div>
            <div id="complaintModalIncident" class="mt-1 text-gray-800"></div>
          </div>
        </div>

        <div>
          <div class="text-xs font-semibold text-gray-500">DETAILS</div>
          <div id="complaintModalDesc" class="mt-1 whitespace-pre-line text-gray-800"></div>
        </div>

        <div>
          <div class="text-xs font-semibold text-gray-500">ATTACHMENT</div>
          <div id="complaintModalAttachmentWrap" class="mt-2">
            <img id="complaintModalAttachmentImg" class="hidden w-full max-h-[320px] object-contain rounded-lg border border-base-200 bg-white" alt="Attachment" />
            <div id="complaintModalAttachmentNone" class="text-sm text-gray-500">No attachment.</div>
            <div class="mt-2 flex items-center gap-2" id="complaintModalAttachmentActions">
              <a id="complaintModalAttachmentDownload" class="btn btn-sm hr2-primary-btn" href="#">Download</a>
            </div>
          </div>
        </div>

        <div class="divider my-2"></div>

        <form id="complaintScheduleForm" class="space-y-3">
          <input type="hidden" id="complaintScheduleId" value="" />

          <div class="text-xs font-semibold text-gray-500">SCHEDULE A MEETING</div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="form-control">
              <label class="label"><span class="label-text">Meeting Date</span></label>
              <input type="date" id="complaintScheduleDate" class="input input-bordered" />
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text">Call Time</span></label>
              <input type="time" id="complaintScheduleTime" class="input input-bordered" />
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text">Where can you meet?</span></label>
              <input type="text" id="complaintSchedulePlace" class="input input-bordered" placeholder="e.g., HR Office" />
            </div>
          </div>
        </form>
      </div>

      <div class="modal-action">
        <button type="button" class="btn hr2-primary-btn" id="complaintScheduleBtn">Schedule a Meeting</button>
        <button type="button" class="btn hr2-outline-btn" id="complaintModalOk">Close</button>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
  </dialog>
<?php require('../partials/footer.php') ?>
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

        const type = (link.getAttribute('data-type') || '').toLowerCase();
        const title = link.getAttribute('data-title') || '';
        const meta = link.getAttribute('data-meta') || '';
        const dt = link.getAttribute('data-date') || '';
        const href = link.getAttribute('data-link') || '';

        e.preventDefault();
        try {
          const data = await postState('read', key);
          if (!data || !data.success) return;
          const card = link.closest('.notif-card');
          if (!card) return;
          card.setAttribute('data-status', 'read');
          updateNewBadge(card, 'read');

          if (type === 'leave update') {
            const dlg = document.getElementById('notifViewModal');
            const tEl = document.getElementById('notifModalTitle');
            const dEl = document.getElementById('notifModalDate');
            const bEl = document.getElementById('notifModalBody');
            if (tEl) tEl.textContent = title || 'Leave Update';
            if (dEl) dEl.textContent = dt ? dt : '';
            if (bEl) bEl.textContent = meta || '';
            if (dlg) dlg.showModal();
            return;
          }

          if (type === 'complaint') {
            const idMatch = String(meta || '').match(/complaint\s*id\s*:\s*(\d+)/i) || String(title || '').match(/complaint\s*id\s*:\s*(\d+)/i);
            const cid = idMatch ? (idMatch[1] || '') : '';
            const dlg = document.getElementById('complaintNotifModal');
            if (!dlg || !cid) {
              const d2 = document.getElementById('notifViewModal');
              const tEl = document.getElementById('notifModalTitle');
              const dEl = document.getElementById('notifModalDate');
              const bEl = document.getElementById('notifModalBody');
              if (tEl) tEl.textContent = title || 'Complaint';
              if (dEl) dEl.textContent = dt ? dt : '';
              if (bEl) bEl.textContent = meta || '';
              if (d2) d2.showModal();
              return;
            }

            const fd = new FormData();
            fd.append('complaint_api', '1');
            fd.append('action', 'details');
            fd.append('complaint_id', cid);
            const res2 = await fetch(window.location.href, { method: 'POST', body: fd, credentials: 'same-origin' });
            const data2 = await res2.json();
            if (!data2 || !data2.success) return;

            const c = data2.complaint || {};
            const tEl = document.getElementById('complaintModalTitle');
            const sEl = document.getElementById('complaintModalSub');
            const subjEl = document.getElementById('complaintModalSubject');
            const stEl = document.getElementById('complaintModalStatus');
            const catEl = document.getElementById('complaintModalCategory');
            const incEl = document.getElementById('complaintModalIncident');
            const descEl = document.getElementById('complaintModalDesc');
            const attWrap = document.getElementById('complaintModalAttachmentWrap');
            const attImg = document.getElementById('complaintModalAttachmentImg');
            const attNone = document.getElementById('complaintModalAttachmentNone');
            const attActions = document.getElementById('complaintModalAttachmentActions');
            const attDl = document.getElementById('complaintModalAttachmentDownload');
            const idEl = document.getElementById('complaintScheduleId');
            const dateEl = document.getElementById('complaintScheduleDate');
            const timeEl = document.getElementById('complaintScheduleTime');
            const placeEl = document.getElementById('complaintSchedulePlace');
            const schedBtn = document.getElementById('complaintScheduleBtn');

            if (tEl) tEl.textContent = title || 'Complaint';
            if (sEl) sEl.textContent = dt ? dt : '';
            if (subjEl) subjEl.textContent = c.subject || '';
            if (stEl) stEl.textContent = c.workflow_status || '';
            if (catEl) catEl.textContent = c.category || '';
            if (incEl) incEl.textContent = c.incident_date || '';
            if (descEl) descEl.textContent = c.description || '';
            if (idEl) idEl.value = String(c.id || cid);

            const attPath = String(c.attachment_path || '').trim();
            const hasAtt = attPath !== '';
            if (attNone) attNone.style.display = hasAtt ? 'none' : '';
            if (attActions) attActions.style.display = hasAtt ? '' : 'none';
            if (attImg) attImg.classList.add('hidden');
            if (attDl) attDl.setAttribute('href', '#');

            if (hasAtt) {
              const lower = attPath.toLowerCase();
              const isImg = lower.endsWith('.png') || lower.endsWith('.jpg') || lower.endsWith('.jpeg') || lower.endsWith('.gif') || lower.endsWith('.webp');
              const dlUrl = 'approval.php?section=complaints&complaint_attachment_download=' + encodeURIComponent(String(c.id || cid));
              if (attDl) attDl.setAttribute('href', dlUrl);
              if (attImg) {
                if (isImg) {
                  attImg.src = attPath;
                  attImg.classList.remove('hidden');
                } else {
                  attImg.classList.add('hidden');
                }
              }
            }

            const today = (() => {
              const d = new Date();
              const yyyy = d.getFullYear();
              const mm = String(d.getMonth() + 1).padStart(2, '0');
              const dd = String(d.getDate()).padStart(2, '0');
              return `${yyyy}-${mm}-${dd}`;
            })();

            if (dateEl) {
              dateEl.min = today;
              dateEl.value = c.meeting_date || today;
            }
            if (timeEl) timeEl.value = (c.meeting_time || '').slice(0, 5);
            if (placeEl) placeEl.value = c.meeting_place || '';

            const canSchedule = !!data2.is_assignee;
            if (dateEl) dateEl.disabled = !canSchedule;
            if (timeEl) timeEl.disabled = !canSchedule;
            if (placeEl) placeEl.disabled = !canSchedule;
            if (schedBtn) schedBtn.style.display = canSchedule ? '' : 'none';

            dlg.showModal();
            return;
          }

          if (href) {
            window.location.href = href;
          }
        } catch (err) {
        }
      });

      const closeNotifModal = () => {
        const dlg = document.getElementById('notifViewModal');
        if (dlg) dlg.close();
      };
      const closeBtn = document.getElementById('notifModalClose');
      const okBtn = document.getElementById('notifModalOk');
      if (closeBtn) closeBtn.addEventListener('click', closeNotifModal);
      if (okBtn) okBtn.addEventListener('click', closeNotifModal);

      const closeComplaintModal = () => {
        const dlg = document.getElementById('complaintNotifModal');
        if (dlg) dlg.close();
      };
      const complaintCloseBtn = document.getElementById('complaintModalClose');
      const complaintOkBtn = document.getElementById('complaintModalOk');
      if (complaintCloseBtn) complaintCloseBtn.addEventListener('click', closeComplaintModal);
      if (complaintOkBtn) complaintOkBtn.addEventListener('click', closeComplaintModal);

      const scheduleBtn = document.getElementById('complaintScheduleBtn');
      if (scheduleBtn) {
        scheduleBtn.addEventListener('click', async () => {
          const idEl = document.getElementById('complaintScheduleId');
          const dateEl = document.getElementById('complaintScheduleDate');
          const timeEl = document.getElementById('complaintScheduleTime');
          const placeEl = document.getElementById('complaintSchedulePlace');
          const cid = idEl ? (idEl.value || '') : '';
          const meetingDate = dateEl ? (dateEl.value || '') : '';
          const meetingTime = timeEl ? (timeEl.value || '') : '';
          const meetingPlace = placeEl ? (placeEl.value || '') : '';
          if (!cid) return;

          const fd = new FormData();
          fd.append('complaint_api', '1');
          fd.append('action', 'schedule');
          fd.append('complaint_id', cid);
          fd.append('meeting_date', meetingDate);
          fd.append('meeting_time', meetingTime);
          fd.append('meeting_place', meetingPlace);

          try {
            const res = await fetch(window.location.href, { method: 'POST', body: fd, credentials: 'same-origin' });
            const data = await res.json();
            if (!data || !data.success) {
              const msg = data && data.message ? data.message : 'Failed to schedule meeting.';
              alert(msg);
              return;
            }
            alert('Meeting scheduled.');
            window.location.reload();
          } catch (e2) {
          }
        });
      }

      setActiveTab('all');
      applyFilter('all');
    })();
  </script>
</body>
</html>
