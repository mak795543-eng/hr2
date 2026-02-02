<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

$role = trim((string)($_SESSION['role'] ?? ''));
$roleLower = strtolower($role);
$employeeNo = trim((string)($_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? ''));

$isApprover = in_array($roleLower, ['admin', 'supervisor', 'manager', 'hr_manager', 'hr manager', 'trainer'], true);

$sinceTs = time() - 86400;
$since = date('Y-m-d H:i:s', $sinceTs);

$items = [];

$pushItem = static function (array &$items, string $type, string $title, string $meta, string $date, string $link): void {
    $items[] = [
        'type' => $type,
        'title' => $title,
        'meta' => $meta,
        'date' => $date,
        'link' => $link,
    ];
};

if ($roleLower !== '') {
    try {
        require_once __DIR__ . '/../LEARNING/db.php';
        $learningConn = usm_db_connect('hr2_learning_db');
        if ($learningConn && !$learningConn->connect_error) {
            $learningConn->set_charset('utf8mb4');

            $stmt = $learningConn->prepare(
                "SELECT id, title, topic, created_at
                 FROM learning_modules
                 WHERE status = 'posted'
                   AND created_at >= ?
                   AND (LOWER(TRIM(roles)) = ? OR FIND_IN_SET(?, LOWER(REPLACE(roles, ', ', ','))) > 0)
                 ORDER BY created_at DESC
                 LIMIT 10"
            );
            if ($stmt) {
                $stmt->bind_param('sss', $since, $roleLower, $roleLower);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($res && ($row = $res->fetch_assoc())) {
                    $pushItem(
                        $items,
                        'Learning Module',
                        (string)($row['title'] ?? 'Module'),
                        (string)($row['topic'] ?? ''),
                        (string)($row['created_at'] ?? ''),
                        '/hr2/ESS/mymodule.php?view=' . (int)($row['id'] ?? 0)
                    );
                }
                $stmt->close();
            }

            if ($isApprover) {
                $stmt = $learningConn->prepare(
                    "SELECT id, title, topic, created_at
                     FROM learning_modules
                     WHERE status = 'pending'
                       AND created_at >= ?
                     ORDER BY created_at DESC
                     LIMIT 10"
                );
                if ($stmt) {
                    $stmt->bind_param('s', $since);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($res && ($row = $res->fetch_assoc())) {
                        $pushItem(
                            $items,
                            'Requested Approval',
                            'Learning Module Approval Needed',
                            (string)($row['title'] ?? 'Module'),
                            (string)($row['created_at'] ?? ''),
                            '/hr2/LEARNING/hr_manager/review_dashboard.php'
                        );
                    }
                    $stmt->close();
                }
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
                 LIMIT 10"
            );
            if ($stmt) {
                $stmt->bind_param('ss', $since, $roleLower);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($res && ($row = $res->fetch_assoc())) {
                    $pushItem(
                        $items,
                        'Examination',
                        (string)($row['title'] ?? 'Examination'),
                        'Assigned for your role',
                        (string)($row['created_at'] ?? ''),
                        '/hr2/ESS/myexamination.php?view=' . (int)($row['id'] ?? 0)
                    );
                }
                $stmt->close();
            }

            if ($isApprover) {
                $stmt = $learningConn->prepare(
                    "SELECT id, title, created_at
                     FROM examinations
                     WHERE status = 'pending'
                       AND created_at >= ?
                     ORDER BY created_at DESC
                     LIMIT 10"
                );
                if ($stmt) {
                    $stmt->bind_param('s', $since);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($res && ($row = $res->fetch_assoc())) {
                        $pushItem(
                            $items,
                            'Requested Approval',
                            'Examination Approval Needed',
                            (string)($row['title'] ?? 'Examination'),
                            (string)($row['created_at'] ?? ''),
                            '/hr2/LEARNING/hr_manager/review_dashboard.php'
                        );
                    }
                    $stmt->close();
                }
            }

            $learningConn->close();
        }
    } catch (Throwable $e) {
    }
}

if ($employeeNo !== '') {
    try {
        require_once __DIR__ . '/../TRAINING/TRAINING/db.php';
        global $TRAINING_DB_NAME;
        $tconn = training_db_connect((string)($TRAINING_DB_NAME ?? 'hr2_schema_training_request'));
        if ($tconn && !$tconn->connect_error) {
            $tconn->set_charset('utf8mb4');

            $empId = 0;
            $stmt = $tconn->prepare('SELECT id FROM employees WHERE employee_no = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $employeeNo);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $empId = (int)($row['id'] ?? 0);
                $stmt->close();
            }

            if ($empId > 0) {
                $stmt = $tconn->prepare(
                    "SELECT p.id AS program_id, p.training_title, p.start_datetime, p.end_datetime, a.assigned_at
                     FROM training_post_assignments a
                     JOIN training_programs p ON p.id = a.program_id
                     WHERE a.employee_id = ?
                       AND a.assigned_at >= ?
                     ORDER BY a.assigned_at DESC
                     LIMIT 10"
                );
                if ($stmt) {
                    $stmt->bind_param('is', $empId, $since);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($res && ($row = $res->fetch_assoc())) {
                        $title = (string)($row['training_title'] ?? 'Training');
                        $meta = '';
                        $start = trim((string)($row['start_datetime'] ?? ''));
                        $end = trim((string)($row['end_datetime'] ?? ''));
                        if ($start !== '' && $end !== '') {
                            $meta = $start . ' - ' . $end;
                        } elseif ($start !== '') {
                            $meta = $start;
                        }

                        $pushItem(
                            $items,
                            'Training Schedule',
                            $title,
                            $meta,
                            (string)($row['assigned_at'] ?? ''),
                            '/hr2/TRAINING/TRAINING/employeedashboard.php'
                        );
                    }
                    $stmt->close();
                }
            }

            if ($isApprover) {
                $stmt = $tconn->prepare(
                    "SELECT id, training_title, created_at
                     FROM training_programs
                     WHERE status IN ('Under Review', 'Pending')
                       AND created_at >= ?
                     ORDER BY created_at DESC
                     LIMIT 10"
                );
                if ($stmt) {
                    $stmt->bind_param('s', $since);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($res && ($row = $res->fetch_assoc())) {
                        $pushItem(
                            $items,
                            'Requested Approval',
                            'Training Approval Needed',
                            (string)($row['training_title'] ?? 'Training Program'),
                            (string)($row['created_at'] ?? ''),
                            '/hr2/TRAINING/TRAINING/review.php'
                        );
                    }
                    $stmt->close();
                }
            }
        }
    } catch (Throwable $e) {
    }
}

if ($employeeNo !== '') {
    try {
        require_once __DIR__ . '/../COMPETENCY/criticalgaps/config.php';
        if (isset($pdo) && $pdo instanceof PDO) {
            $stmt = $pdo->prepare('SELECT employee_id, competency_level, date_added FROM pre_promotion_employees WHERE employee_id = ? AND date_added >= ? ORDER BY date_added DESC LIMIT 10');
            $stmt->execute([$employeeNo, $since]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $pushItem(
                    $items,
                    'Promotion',
                    'Promotion Notification',
                    'Competency Level: ' . (string)($row['competency_level'] ?? ''),
                    (string)($row['date_added'] ?? ''),
                    '/hr2/COMPETENCY/criticalgaps/criticalgaps.php'
                );
            }
        }
    } catch (Throwable $e) {
    }
}

try {
    define('SUPPRESS_DB_ERRORS', true);
} catch (Throwable $e) {
}

try {
    require_once __DIR__ . '/../ESS/db.php';
    if (isset($conn) && $conn instanceof mysqli && $employeeNo !== '') {
        $employeeId = ess_employee_id($conn);
        if (is_int($employeeId) && $employeeId > 0) {
            $stmt = mysqli_prepare(
                $conn,
                "SELECT id, status, remarks, reviewed_at, created_at
                 FROM profile_update_requests
                 WHERE employee_id = ?
                   AND status <> 'Pending'
                   AND seen_by_employee = 0
                   AND COALESCE(reviewed_at, created_at) >= ?
                 ORDER BY COALESCE(reviewed_at, created_at) DESC
                 LIMIT 10"
            );
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'is', $employeeId, $since);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                while ($res && ($row = mysqli_fetch_assoc($res))) {
                    $pushItem(
                        $items,
                        'Approval Update',
                        'Profile Request: ' . (string)($row['status'] ?? ''),
                        (string)($row['remarks'] ?? ''),
                        (string)($row['reviewed_at'] ?? $row['created_at'] ?? ''),
                        '/hr2/ESS/profile_management.php?ack=' . (int)($row['id'] ?? 0)
                    );
                }
                mysqli_stmt_close($stmt);
            }

            if ($isApprover) {
                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT id, document_title, submitted_at
                     FROM submitted_documents
                     WHERE status = 'Pending'
                       AND submitted_at >= ?
                     ORDER BY submitted_at DESC
                     LIMIT 10"
                );
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 's', $since);
                    mysqli_stmt_execute($stmt);
                    $res = mysqli_stmt_get_result($stmt);
                    while ($res && ($row = mysqli_fetch_assoc($res))) {
                        $pushItem(
                            $items,
                            'Requested Approval',
                            'Document Approval Needed',
                            (string)($row['document_title'] ?? 'Document'),
                            (string)($row['submitted_at'] ?? ''),
                            '/hr2/ESS/approval.php?section=documents&status=for+approval'
                        );
                    }
                    mysqli_stmt_close($stmt);
                }

                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT id, employee_id, created_at
                     FROM profile_update_requests
                     WHERE status = 'Pending'
                       AND created_at >= ?
                     ORDER BY created_at DESC
                     LIMIT 10"
                );
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 's', $since);
                    mysqli_stmt_execute($stmt);
                    $res = mysqli_stmt_get_result($stmt);
                    while ($res && ($row = mysqli_fetch_assoc($res))) {
                        $pushItem(
                            $items,
                            'Requested Approval',
                            'Profile Update Approval Needed',
                            'Employee ID: ' . (string)($row['employee_id'] ?? ''),
                            (string)($row['created_at'] ?? ''),
                            '/hr2/ESS/approval.php?section=profiles&pstatus=pending'
                        );
                    }
                    mysqli_stmt_close($stmt);
                }

                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT id, subject, created_at
                     FROM complaints
                     WHERE workflow_status = 'For Approval'
                       AND created_at >= ?
                     ORDER BY created_at DESC
                     LIMIT 10"
                );
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 's', $since);
                    mysqli_stmt_execute($stmt);
                    $res = mysqli_stmt_get_result($stmt);
                    while ($res && ($row = mysqli_fetch_assoc($res))) {
                        $pushItem(
                            $items,
                            'Requested Approval',
                            'Complaint Approval Needed',
                            (string)($row['subject'] ?? 'Complaint'),
                            (string)($row['created_at'] ?? ''),
                            '/hr2/ESS/approval.php?section=complaints&cstatus=for+approval'
                        );
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
} catch (Throwable $e) {
}

usort($items, static function (array $a, array $b) {
    $ad = strtotime((string)($a['date'] ?? '')) ?: 0;
    $bd = strtotime((string)($b['date'] ?? '')) ?: 0;
    return $bd <=> $ad;
});

$items = array_slice($items, 0, 12);

$latestKey = '';
if (!empty($items)) {
    $first = $items[0];
    $latestKey = sha1((string)($first['type'] ?? '') . '|' . (string)($first['title'] ?? '') . '|' . (string)($first['date'] ?? ''));
}

echo json_encode([
    'success' => true,
    'since' => $since,
    'count' => count($items),
    'latest_key' => $latestKey,
    'items' => $items,
]);
