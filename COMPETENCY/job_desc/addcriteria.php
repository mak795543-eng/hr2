<?php
session_start();

require_once __DIR__ . '/db_job_desc.php';

$standardDepartments = [
    ['request_id' => 'DPT-FO', 'name' => 'Front Office / Reception'],
    ['request_id' => 'DPT-HK', 'name' => 'Housekeeping'],
    ['request_id' => 'DPT-FBS', 'name' => 'Food & Beverage (F&B)'],
    ['request_id' => 'DPT-KC', 'name' => 'Kitchen / Culinary'],
    ['request_id' => 'DPT-SM', 'name' => 'Sales & Marketing'],
    ['request_id' => 'DPT-HR', 'name' => 'Human Resources (HR)'],
    ['request_id' => 'DPT-FIN', 'name' => 'Finance / Accounting'],
    ['request_id' => 'DPT-ENG', 'name' => 'Engineering / Maintenance'],
    ['request_id' => 'DPT-SEC', 'name' => 'Security'],
];

function addcriteria_json_response($success, $message = '', $data = [], $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function addcriteria_load_competencies_from_db(mysqli $conn): array {
    $sql = "SELECT cs.*,
                cl.level,
                cl.criteria_text
            FROM competency_standards cs
            LEFT JOIN competency_level_criteria cl ON cl.competency_id = cs.id
            ORDER BY cs.id ASC";

    $res = $conn->query($sql);
    if (!$res) {
        return [];
    }

    $map = [];
    while ($row = $res->fetch_assoc()) {
        $id = (int)$row['id'];
        if (!isset($map[$id])) {
            $qualifications = [];
            $requirements = [];
            if (isset($row['qualifications_json']) && $row['qualifications_json'] !== null && $row['qualifications_json'] !== '') {
                $decoded = json_decode((string)$row['qualifications_json'], true);
                if (is_array($decoded)) {
                    $qualifications = $decoded;
                }
            }
            if (isset($row['requirements_json']) && $row['requirements_json'] !== null && $row['requirements_json'] !== '') {
                $decoded = json_decode((string)$row['requirements_json'], true);
                if (is_array($decoded)) {
                    $requirements = $decoded;
                }
            }
            $map[$id] = [
                'id' => $id,
                'name' => $row['name'],
                'category' => $row['category'],
                'status' => $row['status'],
                'approval_status' => isset($row['approval_status']) ? $row['approval_status'] : 'posted',
                'pending_action' => isset($row['pending_action']) ? $row['pending_action'] : 'upsert',
                'delete_reason' => isset($row['delete_reason']) ? ($row['delete_reason'] ?? '') : '',
                'review_reason' => isset($row['review_reason']) ? ($row['review_reason'] ?? '') : '',
                'requested_at' => isset($row['requested_at']) ? $row['requested_at'] : null,
                'approved_at' => isset($row['approved_at']) ? $row['approved_at'] : null,
                'rejected_at' => isset($row['rejected_at']) ? $row['rejected_at'] : null,
                'description' => $row['description'] ?? '',
                'priority' => (int)$row['priority'],
                'role' => $row['role'],
                'hotel_context' => $row['hotel_context'],
                'restaurant_context' => $row['restaurant_context'],
                'education' => isset($row['education']) ? ($row['education'] ?? '') : '',
                'certifications' => isset($row['certifications']) ? ($row['certifications'] ?? '') : '',
                'tech_skills' => isset($row['tech_skills']) ? ($row['tech_skills'] ?? '') : '',
                'soft_skills' => isset($row['soft_skills']) ? ($row['soft_skills'] ?? '') : '',
                'experience' => isset($row['experience']) ? ($row['experience'] ?? '') : '',
                'physical' => isset($row['physical']) ? ($row['physical'] ?? '') : '',
                'qualifications' => $qualifications,
                'requirements' => $requirements,
                'criteria' => [],
                'last_updated' => $row['last_updated']
            ];
        }

        if ($row['level'] !== null) {
            $lvl = (int)$row['level'];
            $map[$id]['criteria'][(string)$lvl] = $row['criteria_text'];
        }
    }
    $res->free();

    $mapTable = $conn->query("SHOW TABLES LIKE 'job_criteria_mappings'");
    if ($mapTable && $mapTable->num_rows > 0) {
        $mapRes = $conn->query("SELECT competency_id, department_id, job_title_pattern
                                FROM job_criteria_mappings
                                ORDER BY priority DESC, id ASC");
        if ($mapRes) {
            while ($m = $mapRes->fetch_assoc()) {
                $cid = (int)$m['competency_id'];
                if (!isset($map[$cid])) {
                    continue;
                }
                if (!isset($map[$cid]['department_id'])) {
                    $map[$cid]['department_id'] = $m['department_id'];
                    $map[$cid]['job_title_pattern'] = $m['job_title_pattern'];
                }
            }
            $mapRes->free();
        }
    }

    return array_values($map);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['api'] ?? '') === '1') {
    $action = $_GET['action'] ?? '';
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    try {
        $conn = job_desc_mysqli();
        $conn->begin_transaction();

        if ($action === 'upsert_competency') {
            $id = isset($payload['id']) && $payload['id'] !== '' ? (int)$payload['id'] : 0;
            $name = trim((string)($payload['name'] ?? ''));
            $department_id = trim((string)($payload['department_id'] ?? ''));
            $job_title_pattern = trim((string)($payload['job_title_pattern'] ?? ''));
            $category = 'core';
            $status = 'active';
            $approval_status = 'pending';
            $pending_action = 'upsert';
            $delete_reason = null;
            $description = trim((string)($payload['description'] ?? ''));
            $priority = 3;
            $role = 'both';

            $hotel_context = null;
            $restaurant_context = null;
            $education = null;
            $certifications = null;
            $tech_skills = null;
            $soft_skills = null;
            $experience = null;
            $physical = null;
            $criteria = isset($payload['criteria']) && is_array($payload['criteria']) ? $payload['criteria'] : null;
            $last_updated = isset($payload['last_updated']) ? (string)$payload['last_updated'] : null;

            $qualifications = isset($payload['qualifications']) && is_array($payload['qualifications']) ? $payload['qualifications'] : [];
            $requirements = isset($payload['requirements']) && is_array($payload['requirements']) ? $payload['requirements'] : [];
            $qualifications_json = json_encode($qualifications, JSON_UNESCAPED_UNICODE);
            $requirements_json = json_encode($requirements, JSON_UNESCAPED_UNICODE);

            $hasQualificationsJson = false;
            $hasRequirementsJson = false;
            $hasApprovalWorkflow = false;
            $colRes = $conn->query("SHOW COLUMNS FROM competency_standards LIKE 'qualifications_json'");
            if ($colRes && $colRes->num_rows > 0) {
                $hasQualificationsJson = true;
            }
            $colRes2 = $conn->query("SHOW COLUMNS FROM competency_standards LIKE 'requirements_json'");
            if ($colRes2 && $colRes2->num_rows > 0) {
                $hasRequirementsJson = true;
            }

            $colRes3 = $conn->query("SHOW COLUMNS FROM competency_standards LIKE 'approval_status'");
            if ($colRes3 && $colRes3->num_rows > 0) {
                $hasApprovalWorkflow = true;
            }

            $hasReviewReason = false;
            $colRes4 = $conn->query("SHOW COLUMNS FROM competency_standards LIKE 'review_reason'");
            if ($colRes4 && $colRes4->num_rows > 0) {
                $hasReviewReason = true;
            }

            if ($name === '' || $description === '' || $department_id === '' || $job_title_pattern === '') {
                $conn->rollback();
                addcriteria_json_response(false, 'Missing required fields', [], 422);
            }

            if ($id > 0) {
                if ($hasQualificationsJson && $hasRequirementsJson) {
                    if ($hasApprovalWorkflow) {
                        $stmt = $conn->prepare(
                            "UPDATE competency_standards
                             SET name = ?, category = ?, status = ?, priority = ?, role = ?,
                                 approval_status = ?, pending_action = ?, delete_reason = NULL,
                                 requested_at = CURRENT_TIMESTAMP, approved_at = NULL, rejected_at = NULL,
                                 hotel_context = ?, restaurant_context = ?,
                                 education = ?, certifications = ?, tech_skills = ?, soft_skills = ?,
                                 experience = ?, physical = ?, qualifications_json = ?, requirements_json = ?,
                                 last_updated = ?, description = ?
                             WHERE id = ?"
                        );
                        $types = 'sssi' . str_repeat('s', 15) . 'i';
                        $stmt->bind_param(
                            $types,
                            $name,
                            $category,
                            $status,
                            $priority,
                            $role,
                            $approval_status,
                            $pending_action,
                            $hotel_context,
                            $restaurant_context,
                            $education,
                            $certifications,
                            $tech_skills,
                            $soft_skills,
                            $experience,
                            $physical,
                            $qualifications_json,
                            $requirements_json,
                            $last_updated,
                            $description,
                            $id
                        );
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        $stmt = $conn->prepare(
                            "UPDATE competency_standards
                             SET name = ?, category = ?, status = ?, priority = ?, role = ?,
                                 hotel_context = ?, restaurant_context = ?,
                                 education = ?, certifications = ?, tech_skills = ?, soft_skills = ?,
                                 experience = ?, physical = ?, qualifications_json = ?, requirements_json = ?,
                                 last_updated = ?, description = ?
                             WHERE id = ?"
                        );
                        $types = 'sssi' . str_repeat('s', 13) . 'i';
                        $stmt->bind_param(
                            $types,
                            $name,
                            $category,
                            $status,
                            $priority,
                            $role,
                            $hotel_context,
                            $restaurant_context,
                            $education,
                            $certifications,
                            $tech_skills,
                            $soft_skills,
                            $experience,
                            $physical,
                            $qualifications_json,
                            $requirements_json,
                            $last_updated,
                            $description,
                            $id
                        );
                        $stmt->execute();
                        $stmt->close();
                    }
                } else {
                    if ($hasApprovalWorkflow) {
                        $stmt = $conn->prepare(
                            "UPDATE competency_standards
                             SET name = ?, category = ?, status = ?, priority = ?, role = ?,
                                 approval_status = ?, pending_action = ?, delete_reason = NULL,
                                 requested_at = CURRENT_TIMESTAMP, approved_at = NULL, rejected_at = NULL,
                                 hotel_context = ?, restaurant_context = ?,
                                 education = ?, certifications = ?, tech_skills = ?, soft_skills = ?,
                                 experience = ?, physical = ?, last_updated = ?, description = ?
                             WHERE id = ?"
                        );
                        $types = 'sssi' . str_repeat('s', 13) . 'i';
                        $stmt->bind_param(
                            $types,
                            $name,
                            $category,
                            $status,
                            $priority,
                            $role,
                            $approval_status,
                            $pending_action,
                            $hotel_context,
                            $restaurant_context,
                            $education,
                            $certifications,
                            $tech_skills,
                            $soft_skills,
                            $experience,
                            $physical,
                            $last_updated,
                            $description,
                            $id
                        );
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        $stmt = $conn->prepare(
                            "UPDATE competency_standards
                             SET name = ?, category = ?, status = ?, priority = ?, role = ?,
                                 hotel_context = ?, restaurant_context = ?,
                                 education = ?, certifications = ?, tech_skills = ?, soft_skills = ?,
                                 experience = ?, physical = ?, last_updated = ?, description = ?
                             WHERE id = ?"
                        );
                        $types = 'sssi' . str_repeat('s', 11) . 'i';
                        $stmt->bind_param(
                            $types,
                            $name,
                            $category,
                            $status,
                            $priority,
                            $role,
                            $hotel_context,
                            $restaurant_context,
                            $education,
                            $certifications,
                            $tech_skills,
                            $soft_skills,
                            $experience,
                            $physical,
                            $last_updated,
                            $description,
                            $id
                        );
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            } else {
                if ($hasQualificationsJson && $hasRequirementsJson) {
                    if ($hasApprovalWorkflow) {
                        $stmt = $conn->prepare(
                            "INSERT INTO competency_standards
                                (name, category, status, approval_status, pending_action, delete_reason, requested_at, approved_at, rejected_at, priority, role, hotel_context, restaurant_context, education, certifications, tech_skills, soft_skills, experience, physical, qualifications_json, requirements_json, last_updated, description)
                             VALUES
                                (?, ?, ?, ?, ?, NULL, CURRENT_TIMESTAMP, NULL, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                        );
                        $types = str_repeat('s', 5) . 'i' . str_repeat('s', 13);
                        $stmt->bind_param(
                            $types,
                            $name,
                            $category,
                            $status,
                            $approval_status,
                            $pending_action,
                            $priority,
                            $role,
                            $hotel_context,
                            $restaurant_context,
                            $education,
                            $certifications,
                            $tech_skills,
                            $soft_skills,
                            $experience,
                            $physical,
                            $qualifications_json,
                            $requirements_json,
                            $last_updated,
                            $description
                        );
                        $stmt->execute();
                        $stmt->close();
                        $id = (int)$conn->insert_id;
                    } else {
                        $stmt = $conn->prepare(
                            "INSERT INTO competency_standards
                                (name, category, status, priority, role, hotel_context, restaurant_context, education, certifications, tech_skills, soft_skills, experience, physical, qualifications_json, requirements_json, last_updated, description)
                             VALUES
                                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                        );
                        $types = 'sssi' . str_repeat('s', 13);
                        $stmt->bind_param(
                            $types,
                            $name,
                            $category,
                            $status,
                            $priority,
                            $role,
                            $hotel_context,
                            $restaurant_context,
                            $education,
                            $certifications,
                            $tech_skills,
                            $soft_skills,
                            $experience,
                            $physical,
                            $qualifications_json,
                            $requirements_json,
                            $last_updated,
                            $description
                        );
                        $stmt->execute();
                        $stmt->close();
                        $id = (int)$conn->insert_id;
                    }
                } else {
                    if ($hasApprovalWorkflow) {
                        $stmt = $conn->prepare(
                            "INSERT INTO competency_standards
                                (name, category, status, approval_status, pending_action, delete_reason, requested_at, approved_at, rejected_at, priority, role, hotel_context, restaurant_context, education, certifications, tech_skills, soft_skills, experience, physical, last_updated, description)
                             VALUES
                                (?, ?, ?, ?, ?, NULL, CURRENT_TIMESTAMP, NULL, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                        );
                        $types = str_repeat('s', 5) . 'i' . str_repeat('s', 11);
                        $stmt->bind_param(
                            $types,
                            $name,
                            $category,
                            $status,
                            $approval_status,
                            $pending_action,
                            $priority,
                            $role,
                            $hotel_context,
                            $restaurant_context,
                            $education,
                            $certifications,
                            $tech_skills,
                            $soft_skills,
                            $experience,
                            $physical,
                            $last_updated,
                            $description
                        );
                        $stmt->execute();
                        $stmt->close();
                        $id = (int)$conn->insert_id;
                    } else {
                        $stmt = $conn->prepare(
                            "INSERT INTO competency_standards
                                (name, category, status, priority, role, hotel_context, restaurant_context, education, certifications, tech_skills, soft_skills, experience, physical, last_updated, description)
                             VALUES
                                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                        );
                        $types = 'sssi' . str_repeat('s', 11);
                        $stmt->bind_param(
                            $types,
                            $name,
                            $category,
                            $status,
                            $priority,
                            $role,
                            $hotel_context,
                            $restaurant_context,
                            $education,
                            $certifications,
                            $tech_skills,
                            $soft_skills,
                            $experience,
                            $physical,
                            $last_updated,
                            $description
                        );
                        $stmt->execute();
                        $stmt->close();
                        $id = (int)$conn->insert_id;
                    }
                }
            }

            if (is_array($criteria)) {
                $stmtDel = $conn->prepare("DELETE FROM competency_level_criteria WHERE competency_id = ?");
                $stmtDel->bind_param('i', $id);
                $stmtDel->execute();
                $stmtDel->close();

                $stmtIns = $conn->prepare("INSERT INTO competency_level_criteria (competency_id, level, criteria_text) VALUES (?, ?, ?)");
                for ($lvl = 1; $lvl <= 5; $lvl++) {
                    $txt = '';
                    if (isset($criteria[(string)$lvl])) {
                        $txt = (string)$criteria[(string)$lvl];
                    } elseif (isset($criteria[$lvl])) {
                        $txt = (string)$criteria[$lvl];
                    }
                    $stmtIns->bind_param('iis', $id, $lvl, $txt);
                    $stmtIns->execute();
                }
                $stmtIns->close();
            }

            $mapTable = $conn->query("SHOW TABLES LIKE 'job_criteria_mappings'");
            if ($mapTable && $mapTable->num_rows > 0) {
                $stmtMapDel = $conn->prepare("DELETE FROM job_criteria_mappings WHERE competency_id = ?");
                if ($stmtMapDel) {
                    $stmtMapDel->bind_param('i', $id);
                    $stmtMapDel->execute();
                    $stmtMapDel->close();
                }

                $stmtMapIns = $conn->prepare("INSERT INTO job_criteria_mappings (department_id, job_title_pattern, competency_id, priority, is_active)
                                              VALUES (?, ?, ?, ?, 0)");
                if ($stmtMapIns) {
                    $stmtMapIns->bind_param('ssii', $department_id, $job_title_pattern, $id, $priority);
                    $stmtMapIns->execute();
                    $stmtMapIns->close();
                }
            }

            $conn->commit();

            if ($hasApprovalWorkflow && $hasReviewReason && $id > 0) {
                $stmtClr = $conn->prepare("UPDATE competency_standards SET review_reason = NULL WHERE id = ?");
                if ($stmtClr) {
                    $stmtClr->bind_param('i', $id);
                    $stmtClr->execute();
                    $stmtClr->close();
                }
            }

            $data = addcriteria_load_competencies_from_db($conn);
            $conn->close();
            addcriteria_json_response(true, $hasApprovalWorkflow ? 'Submitted for approval' : 'Saved', $data);
        }

        if ($action === 'request_delete_competency') {
            $id = isset($payload['id']) ? (int)$payload['id'] : 0;
            $reason = trim((string)($payload['reason'] ?? ''));
            if ($id <= 0) {
                $conn->rollback();
                addcriteria_json_response(false, 'Missing id', [], 422);
            }
            if ($reason === '') {
                $conn->rollback();
                addcriteria_json_response(false, 'Please provide a reason for deletion', [], 422);
            }

            $col = $conn->query("SHOW COLUMNS FROM competency_standards LIKE 'approval_status'");
            if (!$col || $col->num_rows === 0) {
                $conn->rollback();
                addcriteria_json_response(false, 'Approval workflow not installed (missing approval_status column).', [], 500);
            }

            $hasReviewReason = false;
            $colR = $conn->query("SHOW COLUMNS FROM competency_standards LIKE 'review_reason'");
            if ($colR && $colR->num_rows > 0) {
                $hasReviewReason = true;
            }

            $stmt = $conn->prepare("UPDATE competency_standards
                                    SET approval_status = 'pending', pending_action = 'delete', delete_reason = ?,
                                        requested_at = CURRENT_TIMESTAMP, approved_at = NULL, rejected_at = NULL
                                    WHERE id = ?");
            $stmt->bind_param('si', $reason, $id);
            $stmt->execute();
            $stmt->close();

            if ($hasReviewReason) {
                $stmtClr = $conn->prepare("UPDATE competency_standards SET review_reason = NULL WHERE id = ?");
                if ($stmtClr) {
                    $stmtClr->bind_param('i', $id);
                    $stmtClr->execute();
                    $stmtClr->close();
                }
            }

            $stmtMap = $conn->prepare("UPDATE job_criteria_mappings SET is_active = 0 WHERE competency_id = ?");
            if ($stmtMap) {
                $stmtMap->bind_param('i', $id);
                $stmtMap->execute();
                $stmtMap->close();
            }
            $conn->commit();
            $data = addcriteria_load_competencies_from_db($conn);
            $conn->close();
            addcriteria_json_response(true, 'Delete request submitted for approval', $data);
        }

        if ($action === 'load_competencies') {
            $conn->commit();
            $data = addcriteria_load_competencies_from_db($conn);
            $conn->close();
            addcriteria_json_response(true, 'Loaded', $data);
        }

        $conn->rollback();
        $conn->close();
        addcriteria_json_response(false, 'Unknown action', [], 400);
    } catch (Throwable $e) {
        if (isset($conn) && $conn instanceof mysqli) {
            try { $conn->rollback(); } catch (Throwable $t) {}
            try { $conn->close(); } catch (Throwable $t) {}
        }
        addcriteria_json_response(false, 'Server error: ' . $e->getMessage(), [], 500);
    }
}

$dbCompetencies = [];
$jobTitlesByDepartment = [];
try {
    $conn = job_desc_mysqli();

    $stmtDept = $conn->prepare("INSERT INTO departments (request_id, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
    if ($stmtDept) {
        foreach ($standardDepartments as $d) {
            $rid = $d['request_id'];
            $nm = $d['name'];
            $stmtDept->bind_param('ss', $rid, $nm);
            $stmtDept->execute();
        }
        $stmtDept->close();
    }

    $dbCompetencies = addcriteria_load_competencies_from_db($conn);

    $jtTable = $conn->query("SHOW TABLES LIKE 'job_titles'");
    if ($jtTable && $jtTable->num_rows > 0) {
        $jtRes = $conn->query("SELECT department_id, title FROM job_titles ORDER BY department_id ASC, title ASC");
        if ($jtRes) {
            while ($r = $jtRes->fetch_assoc()) {
                $deptId = (string)$r['department_id'];
                $title = (string)$r['title'];
                if (!isset($jobTitlesByDepartment[$deptId])) {
                    $jobTitlesByDepartment[$deptId] = [];
                }
                $jobTitlesByDepartment[$deptId][] = $title;
            }
            $jtRes->free();
        }
    }

    if (empty($jobTitlesByDepartment)) {
        $jobTitlesByDepartment = [
            'DPT-FO' => [
                'Front Desk Manager',
                'Receptionist / Front Desk Officer',
                'Guest Service Agent / Concierge',
                'Reservation Agent',
                'Bellhop / Porter'
            ],
            'DPT-HK' => [
                'Executive Housekeeper / Housekeeping Manager',
                'Floor Supervisor',
                'Room Attendant / Housekeeper',
                'Laundry Attendant',
                'Public Area Attendant'
            ],
            'DPT-FBS' => [
                'F&B Manager / Director',
                'Restaurant Manager / Captain',
                'Waiter / Waitress / Server'
            ],
            'DPT-KC' => [
                'Executive Chef / Head Chef',
                'Sous Chef (assistant to head chef)',
                'Line Cook / Station Chef',
                'Pastry Chef / Baker',
                'Kitchen Steward / Dishwasher'
            ],
            'DPT-SM' => [
                'Sales & Marketing Manager',
                'Revenue Manager',
                'Event / Banquet Sales Coordinator',
                'Social Media / Marketing Executive'
            ],
            'DPT-HR' => [
                'HR Manager / Director',
                'Recruitment Officer',
                'Training & Development Specialist',
                'Payroll / HR Assistant'
            ],
            'DPT-FIN' => [
                'Finance Manager / Controller',
                'Accountant',
                'Payroll Officer',
                'Cost Controller'
            ],
            'DPT-SEC' => [
                'Security Manager / Supervisor',
                'Security Guard',
                'CCTV / Surveillance Officer'
            ]
        ];
    }

    $conn->close();
} catch (Throwable $e) {
    $dbCompetencies = [];
    $jobTitlesByDepartment = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competency Management</title>
    <!-- DaisyUI & Tailwind -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <script>
        window.__DB_COMPETENCIES__ = <?php echo json_encode($dbCompetencies, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.__STANDARD_DEPARTMENTS__ = <?php echo json_encode($standardDepartments, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.__JOB_TITLES_BY_DEPARTMENT__ = <?php echo json_encode($jobTitlesByDepartment, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#1f3a8a',
                        'primary-dark': '#1b3280',
                        'secondary': '#f5c542',
                        'leadership': '#8b5cf6',
                        'core': '#1f3a8a',
                        'technical': '#f59e0b',
                        'hotel': '#06b6d4',
                        'restaurant': '#10b981',
                        'light-bg': '#f8fafc',
                        'border-light': '#e2e8f0'
                    }
                }
            }
        }
    </script>
    <style>
        * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        :root {
            --brand-primary: #1f3a8a;
            --brand-primary-hover: #1b3280;
            --brand-accent: #f5c542;
            --brand-bg: #f3f4f6;
        }
        
        body {
            background-color: #ffffff;
        }
        
        /* White Background Elements */
        .container-box,
        .stat-card,
        .modal-content,
        .dropdown-menu,
        .select,
        .input,
        .textarea,
        .search-input,
        .dropdown-btn {
            background-color: #ffffff !important;
        }
        
        /* Input Styling */
        .input, 
        .textarea,
        .select,
        .search-input {
            background-color: #ffffff !important;
            border: 1px solid #d1d5db !important;
            border-radius: 8px !important;
            color: #1f2937 !important;
        }
        
        .input:focus,
        .textarea:focus,
        .select:focus,
        .search-input:focus {
            outline: none !important;
            border-color: var(--brand-primary) !important;
            box-shadow: 0 0 0 3px rgba(31, 58, 138, 0.12) !important;
            background-color: #ffffff !important;
        }
        
        .container-box {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        
        .stat-card {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        /* Card-specific styles */
        .competency-card {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: white;
            transition: all 0.3s ease;
        }
        
        .competency-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-color: var(--brand-primary);
        }
        
        .line-clamp-1 {
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .competency-row {
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.2s ease;
        }
        
        .competency-row:hover {
            background-color: #f8fafc;
        }
        
        .level-badge {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 13px;
        }
        
        .level-1 { background: #fef3c7 !important; color: #92400e !important; }
        .level-2 { background: #fde68a !important; color: #92400e !important; }
        .level-3 { background: #fbbf24 !important; color: #92400e !important; }
        .level-4 { background: #f59e0b !important; color: #92400e !important; }
        .level-5 { background: #d97706 !important; color: white !important; }
        
        /* Role Tags */
        .role-tag {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .tag-hotel { background: #cffafe !important; color: #0e7490 !important; }
        .tag-restaurant { background: #d1fae5 !important; color: #065f46 !important; }
        .tag-both { background: #fef3c7 !important; color: #92400e !important; }
        
        /* Category Tags */
        .category-tag {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .tag-core { background: #dbeafe !important; color: #1d4ed8 !important; }
        .tag-leadership { background: #ede9fe !important; color: #6d28d9 !important; }
        .tag-technical { background: #fef3c7 !important; color: #92400e !important; }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active { background: #d1fae5 !important; color: #065f46 !important; }
        .status-inactive { background: #fef3c7 !important; color: #92400e !important; }
        .status-posted { background: #d1fae5 !important; color: #065f46 !important; }
        .status-pending { background: #fef3c7 !important; color: #92400e !important; }
        .status-rejected { background: #fee2e2 !important; color: #991b1b !important; }
        .status-compliance { background: #cffafe !important; color: #0e7490 !important; }
        
        .priority-indicator {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .modal-content {
            border-radius: 16px;
            border: 1px solid #e2e8f0;
        }
        
        .section-title {
            color: #1e293b;
            font-weight: 600;
        }
        
        .divider {
            border-top: 1px solid #e2e8f0;
        }
        
        .btn-primary {
            background: var(--brand-primary) !important;
            color: white !important;
            border: none !important;
            border-radius: 8px !important;
            font-weight: 500 !important;
            padding: 10px 20px !important;
            transition: all 0.2s ease !important;
        }
        
        .btn-primary:hover {
            background: var(--brand-primary-hover) !important;
            transform: translateY(-1px);
        }
        
        .btn-outline {
            background: transparent !important;
            color: var(--brand-primary) !important;
            border: 1px solid var(--brand-primary) !important;
            border-radius: 8px !important;
            font-weight: 500 !important;
            padding: 10px 20px !important;
            transition: all 0.2s ease !important;
        }
        
        .btn-outline:hover {
            background: var(--brand-primary) !important;
            color: #ffffff !important;
        }
        
        .tab-active {
            background: var(--brand-primary) !important;
            color: white !important;
            border-radius: 8px !important;
        }
        
        .criteria-card {
            background: white !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 10px !important;
            transition: all 0.2s ease !important;
        }
        
        .criteria-card:hover {
            border-color: var(--brand-primary) !important;
            box-shadow: 0 4px 12px rgba(31, 58, 138, 0.12) !important;
        }
        
        .search-input {
            border: 1px solid #d1d5db !important;
            border-radius: 8px !important;
            padding: 10px 16px !important;
            padding-left: 40px !important;
            width: 100% !important;
            background-color: #ffffff !important;
            color: #1f2937 !important;
        }
        
        .dropdown-btn {
            border: 1px solid #d1d5db !important;
            border-radius: 8px !important;
            padding: 10px 16px !important;
            background: white !important;
            width: 100% !important;
            text-align: left !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            color: #1f2937 !important;
        }
        
        .dropdown-btn:hover {
            border-color: #cbd5e1 !important;
        }
        
        .dropdown-menu {
            position: absolute !important;
            z-index: 50 !important;
            width: 100% !important;
            margin-top: 4px !important;
            background: white !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
            max-height: 300px !important;
            overflow-y: auto !important;
        }
        
        .dropdown-item {
            display: block !important;
            padding: 12px 16px !important;
            color: #374151 !important;
            text-decoration: none !important;
            background: white !important;
            border: none !important;
            width: 100% !important;
            text-align: left !important;
            cursor: pointer !important;
        }
        
        .dropdown-item:hover {
            background: #f8fafc !important;
        }
        
        .info-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
        }
        
        .info-row i {
            color: #64748b;
            width: 20px;
        }
        
        .role-icon {
            width: 20px;
            text-align: center;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
 
                <div class="max-w-7xl mx-auto">
                    <!-- Header -->
                    <div class="mb-8">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                            <div>
                                <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Competency Management</h1>
                                <p class="text-gray-600 mt-1">Define standard competency criteria for Hotel & Restaurant</p>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" onclick="openLevelsModal()" class="btn-outline">
                                    <i class="fas fa-layer-group mr-2"></i>View Levels
                                </button>
                                <button type="button" id="addCriteriaBtn" class="btn-primary">
                                    <i class="fas fa-plus mr-2"></i>Add Criteria
                                </button>
                            </div>
                        </div>
                        
                        <!-- Stats -->
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6" id="stats-container">
                            <div class="stat-card p-5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-600">Total Competencies</p>
                                        <h3 class="text-2xl font-bold text-gray-900" id="total-count">0</h3>
                                    </div>
                                    <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-list-check text-blue-500 text-xl"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="stat-card p-5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-600">Hotel</p>
                                        <h3 class="text-2xl font-bold text-gray-900" id="hotel-count">0</h3>
                                    </div>
                                    <div class="w-12 h-12 bg-cyan-50 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-hotel text-cyan-500 text-xl"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="stat-card p-5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-600">Restaurant</p>
                                        <h3 class="text-2xl font-bold text-gray-900" id="restaurant-count">0</h3>
                                    </div>
                                    <div class="w-12 h-12 bg-emerald-50 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-utensils text-emerald-500 text-xl"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="stat-card p-5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-600">Both Roles</p>
                                        <h3 class="text-2xl font-bold text-gray-900" id="both-count">0</h3>
                                    </div>
                                    <div class="w-12 h-12 bg-amber-50 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-building text-amber-500 text-xl"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="stat-card p-5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-600">Active</p>
                                        <h3 class="text-2xl font-bold text-gray-900" id="active-count">0</h3>
                                    </div>
                                    <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-check-circle text-green-500 text-xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="container-box p-5 mb-6">
                        <h3 class="section-title mb-4">Filters</h3>
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <!-- Category Dropdown -->
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                <div class="dropdown-container">
                                    <button type="button" class="dropdown-btn" onclick="toggleDropdown('category')">
                                        <span id="category-label">All Categories</span>
                                        <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200" id="category-icon"></i>
                                    </button>
                                    <div class="dropdown-menu hidden" id="category-dropdown">
                                        <a onclick="setFilter('category', 'all')" class="dropdown-item">All Categories</a>
                                        <a onclick="setFilter('category', 'core')" class="dropdown-item">
                                            <i class="fas fa-star text-blue-500 mr-2"></i>Core
                                        </a>
                                        <a onclick="setFilter('category', 'leadership')" class="dropdown-item">
                                            <i class="fas fa-flag text-purple-500 mr-2"></i>Leadership
                                        </a>
                                        <a onclick="setFilter('category', 'technical')" class="dropdown-item">
                                            <i class="fas fa-laptop-code text-amber-500 mr-2"></i>Technical
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Role Dropdown -->
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                                <div class="dropdown-container">
                                    <button type="button" class="dropdown-btn" onclick="toggleDropdown('role')">
                                        <span id="role-label">All Roles</span>
                                        <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200" id="role-icon"></i>
                                    </button>
                                    <div class="dropdown-menu hidden" id="role-dropdown">
                                        <a onclick="setFilter('role', 'all')" class="dropdown-item">All Roles</a>
                                        <a onclick="setFilter('role', 'hotel')" class="dropdown-item">
                                            <i class="fas fa-hotel text-cyan-500 mr-2"></i>Hotel
                                        </a>
                                        <a onclick="setFilter('role', 'restaurant')" class="dropdown-item">
                                            <i class="fas fa-utensils text-emerald-500 mr-2"></i>Restaurant
                                        </a>
                                        <a onclick="setFilter('role', 'both')" class="dropdown-item">
                                            <i class="fas fa-building text-amber-500 mr-2"></i>Both
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Status Dropdown -->
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <div class="dropdown-container">
                                    <button type="button" class="dropdown-btn" onclick="toggleDropdown('status')">
                                        <span id="status-label">All Status</span>
                                        <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200" id="status-icon"></i>
                                    </button>
                                    <div class="dropdown-menu hidden" id="status-dropdown">
                                        <a onclick="setFilter('status', 'all')" class="dropdown-item">All Status</a>
                                        <a onclick="setFilter('status', 'posted')" class="dropdown-item">
                                            <i class="fas fa-circle-check text-green-500 mr-2"></i>Posted
                                        </a>
                                        <a onclick="setFilter('status', 'pending')" class="dropdown-item">
                                            <i class="fas fa-clock text-amber-500 mr-2"></i>Pending
                                        </a>
                                        <a onclick="setFilter('status', 'rejected')" class="dropdown-item">
                                            <i class="fas fa-circle-xmark text-red-500 mr-2"></i>Rejected
                                        </a>
                                        <a onclick="setFilter('status', 'compliance')" class="dropdown-item">
                                            <i class="fas fa-circle-exclamation text-blue-500 mr-2"></i>For Compliance
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Priority Dropdown -->
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                                <div class="dropdown-container">
                                    <button type="button" class="dropdown-btn" onclick="toggleDropdown('priority')">
                                        <span id="priority-label">All Priorities</span>
                                        <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200" id="priority-icon"></i>
                                    </button>
                                    <div class="dropdown-menu hidden" id="priority-dropdown">
                                        <a onclick="setFilter('priority', 'all')" class="dropdown-item">All Priorities</a>
                                        <a onclick="setFilter('priority', 'high')" class="dropdown-item">
                                            <i class="fas fa-key text-red-500 mr-2"></i>High (4-5)
                                        </a>
                                        <a onclick="setFilter('priority', 'medium')" class="dropdown-item">
                                            <i class="fas fa-key text-amber-500 mr-2"></i>Medium (3)
                                        </a>
                                        <a onclick="setFilter('priority', 'low')" class="dropdown-item">
                                            <i class="fas fa-key text-blue-500 mr-2"></i>Low (1-2)
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Search Bar -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                                <div class="relative">
                                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                                    <input type="text" id="search-input" placeholder="Search competencies..." 
                                           class="search-input" oninput="searchCompetencies()">
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center mt-4">
                            <div class="text-sm text-gray-500">
                                <span id="filter-count">Loading competencies...</span>
                            </div>
                            <button onclick="clearFilters()" class="text-sm text-primary hover:text-primary-dark font-medium">
                                <i class="fas fa-refresh mr-1"></i>Clear Filters
                            </button>
                        </div>
                    </div>

                    <!-- Competency Cards Container -->
                    <div id="competencies-container">
                        <!-- Will be loaded dynamically -->
                    </div>
                    
                </div>

                <!-- Modal: Competency Levels -->
                <dialog id="levels-modal" class="modal">
                    <div class="modal-box modal-content max-w-2xl p-0">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h3 class="text-xl font-bold text-gray-900">Competency Levels</h3>
                                <button onclick="closeModal('levels')" class="btn btn-sm btn-circle btn-ghost">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <p class="text-gray-600 mb-6">Each competency is measured across 5 levels of proficiency</p>
                            
                            <div id="levels-content">
                                <!-- Levels content -->
                            </div>
                        </div>
                        
                        <div class="modal-action p-6 border-t border-gray-200">
                            <button onclick="closeModal('levels')" class="btn-outline">Close</button>
                        </div>
                    </div>
                    <form method="dialog" class="modal-backdrop">
                        <button onclick="closeModal('levels')">close</button>
                    </form>
                </dialog>

                <!-- Modal: Add/Edit Competency -->
                <dialog id="edit-modal" class="modal">
                    <div class="modal-box modal-content max-w-4xl p-0">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h3 class="text-xl font-bold text-gray-900" id="edit-title">Add Competency</h3>
                                <button onclick="closeModal('edit')" class="btn btn-sm btn-circle btn-ghost">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        
                        <form id="competency-form" class="p-6">
                            <input type="hidden" id="edit-id" value="">
                            
                            <div class="space-y-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Job Title *</label>
                                        <select id="edit-job-title" class="select select-bordered w-full" required>
                                            <option value="">Select Job Title</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Target Department *</label>
                                        <select id="edit-department" class="select select-bordered w-full" required>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Job Description *</label>
                                    <textarea id="edit-description" class="textarea textarea-bordered w-full h-24" 
                                              placeholder="Describe this competency and its importance..." required></textarea>
                                </div>

                                <div class="divider"></div>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="flex justify-between items-center mb-4">
                                            <h4 class="font-bold text-lg text-gray-700 flex items-center">
                                                <i class="fas fa-graduation-cap mr-2 text-green-600"></i>
                                                Qualifications
                                            </h4>
                                            <button type="button" id="addQualificationBtn" class="btn btn-sm btn-success text-white">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <div id="qualificationsContainer" class="space-y-3"></div>
                                    </div>

                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="flex justify-between items-center mb-4">
                                            <h4 class="font-bold text-lg text-gray-700 flex items-center">
                                                <i class="fas fa-list-check mr-2 text-purple-600"></i>
                                                Job Requirements
                                            </h4>
                                            <button type="button" id="addRequirementBtn" class="btn btn-sm btn-primary text-white">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <div id="requirementsContainer" class="space-y-3"></div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        
                        <div class="modal-action p-6 border-t border-gray-200">
                            <button type="button" onclick="closeModal('edit')" class="btn-outline">Cancel</button>
                            <button type="button" onclick="saveCompetency()" class="btn-primary">
                                <i class="fas fa-save mr-2"></i>Save Criteria
                            </button>
                        </div>
                    </div>
                    <form method="dialog" class="modal-backdrop">
                        <button onclick="closeModal('edit')">close</button>
                    </form>
                </dialog>

                <template id="qualificationTemplate">
                    <div class="draggable-item bg-white rounded-lg border border-gray-200 p-3">
                        <div class="flex items-center gap-3">
                            <div class="cursor-move text-gray-400">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <input type="text" class="input input-bordered w-full qualification-input" placeholder="Enter qualification">
                            <button type="button" class="btn btn-ghost btn-sm remove-btn text-gray-400 hover:text-red-500">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="flex items-center gap-3 mt-2">
                            <select class="select select-bordered select-sm qualification-type">
                                <option value="Education">Education</option>
                                <option value="Certification">Certification</option>
                                <option value="Skill">Skill</option>
                                <option value="Experience">Experience</option>
                            </select>
                        </div>
                    </div>
                </template>

                <template id="requirementTemplate">
                    <div class="draggable-item bg-white rounded-lg border border-gray-200 p-3">
                        <div class="flex items-center gap-3">
                            <div class="cursor-move text-gray-400">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <input type="text" class="input input-bordered w-full requirement-input" placeholder="Enter requirement">
                            <button type="button" class="btn btn-ghost btn-sm remove-btn text-gray-400 hover:text-red-500">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="flex items-center gap-3 mt-2">
                            <select class="select select-bordered select-sm requirement-category">
                                <option value="Skill">Skill</option>
                                <option value="Physical">Physical</option>
                                <option value="Other">Other</option>
                            </select>
                            <label class="flex items-center gap-2 text-sm text-gray-600">
                                <input type="checkbox" class="checkbox checkbox-sm requirement-essential" checked>
                                Essential
                            </label>
                        </div>
                    </div>
                </template>
                
            </main>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="criteria.js"></script>
    <script>
        (function () {
            function safeOpenAddModal() {
                try {
                    if (typeof window.openAddModal === 'function') {
                        window.openAddModal();
                        return;
                    }
                } catch (e) {}

                try {
                    const modal = document.getElementById('edit-modal');
                    const form = document.getElementById('competency-form');
                    if (form && typeof form.reset === 'function') form.reset();
                    if (modal && typeof modal.showModal === 'function') modal.showModal();
                } catch (e) {}
            }

            document.addEventListener('DOMContentLoaded', function () {
                const btn = document.getElementById('addCriteriaBtn');
                if (!btn) return;
                btn.addEventListener('click', safeOpenAddModal);
            });
        })();
    </script>
</body>
</html>