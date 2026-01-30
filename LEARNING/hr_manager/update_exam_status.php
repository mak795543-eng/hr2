<?php

session_start();

function isAutoIncrement(mysqli $conn, string $table, string $column = 'id'): bool {
    $dbResult = $conn->query('SELECT DATABASE() AS db');
    $dbRow = $dbResult ? $dbResult->fetch_assoc() : null;
    $dbName = is_array($dbRow) ? ($dbRow['db'] ?? '') : '';
    if ($dbName === '') {
        return false;
    }

    $sql = "SELECT EXTRA
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('sss', $dbName, $table, $column);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    $extra = is_array($row) ? (string)($row['EXTRA'] ?? '') : '';
    return stripos($extra, 'auto_increment') !== false;
}

function getNextId(mysqli $conn, string $table, string $column = 'id'): int {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        throw new Exception('Invalid table/column name');
    }

    $sql = "SELECT COALESCE(MAX(`$column`), 0) + 1 AS next_id FROM `$table` FOR UPDATE";
    $res = $conn->query($sql);
    $row = $res ? $res->fetch_assoc() : null;
    return is_array($row) ? (int)($row['next_id'] ?? 0) : 0;
}

header('Content-Type: application/json');

function columnExists($conn, $table, $column) {
    $dbResult = $conn->query('SELECT DATABASE() AS db');
    $dbRow = $dbResult ? $dbResult->fetch_assoc() : null;
    $dbName = is_array($dbRow) ? ($dbRow['db'] ?? '') : '';

    if ($dbName === '') {
        return false;
    }

    $sql = "SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('sss', $dbName, $table, $column);
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = $res && $res->num_rows > 0;
    $stmt->close();
    return $exists;
}

// Database connection
require_once __DIR__ . '/../db.php';

// Create connection
$conn = usm_db_connect('hr2_learning_db');

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$conn->set_charset('utf8mb4');

// Get POST data
$examId = isset($_POST['exam_id']) ? intval($_POST['exam_id']) : 0;
$newStatus = isset($_POST['new_status']) ? $_POST['new_status'] : '';
$remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';

if ($examId <= 0 || empty($newStatus)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Valid statuses
$newStatus = $newStatus === 'for-compliance' ? 'compliance' : $newStatus;
$validStatuses = ['approved', 'rejected', 'compliance', 'hold', 'cancelled'];
if (!in_array($newStatus, $validStatuses, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    // Update exam status
    $stmt = $conn->prepare("UPDATE examinations SET status = ?, remarks = ?, reviewed_at = NOW() WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("ssi", $newStatus, $remarks, $examId);

    if ($stmt->execute()) {
        // If approved, also update the exam repository
        if ($newStatus === 'approved') {
            $repoId = null;

            $repoHasTotalPoints = columnExists($conn, 'exam_repository', 'total_points');
            $repoHasPassingScore = columnExists($conn, 'exam_repository', 'passing_score');
            $repoHasDuration = columnExists($conn, 'exam_repository', 'duration');

            // Check if already exists in repository
            $existsStmt = $conn->prepare("SELECT id FROM exam_repository WHERE original_exam_id = ? LIMIT 1");
            if (!$existsStmt) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $existsStmt->bind_param("i", $examId);
            $existsStmt->execute();
            $existsResult = $existsStmt->get_result();
            if ($existsResult && $existsResult->num_rows > 0) {
                $repoRow = $existsResult->fetch_assoc();
                $repoId = is_array($repoRow) ? (int)($repoRow['id'] ?? 0) : 0;
            }
            $existsStmt->close();

            if ($repoId) {
                $setParts = [
                    "er.title = e.title",
                    "er.description = e.description",
                    "er.module_id = e.module_id",
                    "er.department = e.department",
                    "er.roles = e.roles",
                    "er.status = 'approved'"
                ];

                if ($repoHasTotalPoints) {
                    $setParts[] = "er.total_points = e.total_points";
                }
                if ($repoHasPassingScore) {
                    $setParts[] = "er.passing_score = e.passing_score";
                }
                if ($repoHasDuration) {
                    $setParts[] = "er.duration = e.duration";
                }

                $updateRepoSql = "
                    UPDATE exam_repository er
                    JOIN examinations e ON e.id = er.original_exam_id
                    SET " . implode(", ", $setParts) . "
                    WHERE er.id = ?
                ";

                $updateRepoStmt = $conn->prepare($updateRepoSql);
                if (!$updateRepoStmt) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }
                $updateRepoStmt->bind_param("i", $repoId);
                $updateRepoStmt->execute();
                $updateRepoStmt->close();
            } else {
                $repoIdAutoIncrement = isAutoIncrement($conn, 'exam_repository', 'id');
                $generatedRepoId = 0;
                if (!$repoIdAutoIncrement) {
                    $generatedRepoId = getNextId($conn, 'exam_repository', 'id');
                }

                $insertCols = [
                    'exam_id',
                    'original_exam_id',
                    'title',
                    'description',
                    'module_id',
                    'department',
                    'roles',
                    'created_at',
                    'status'
                ];
                $selectCols = [
                    '0',
                    'id',
                    'title',
                    'description',
                    'module_id',
                    'department',
                    'roles',
                    'created_at',
                    "'approved'"
                ];

                if (!$repoIdAutoIncrement) {
                    array_splice($insertCols, 1, 0, ['id']);
                    array_splice($selectCols, 1, 0, [(string)$generatedRepoId]);
                }

                if ($repoHasTotalPoints) {
                    $insertCols[] = 'total_points';
                    $selectCols[] = 'total_points';
                }
                if ($repoHasPassingScore) {
                    $insertCols[] = 'passing_score';
                    $selectCols[] = 'passing_score';
                }
                if ($repoHasDuration) {
                    $insertCols[] = 'duration';
                    $selectCols[] = 'duration';
                }

                $copySql = "
                    INSERT INTO exam_repository
                      (" . implode(', ', $insertCols) . ")
                    SELECT " . implode(', ', $selectCols) . "
                    FROM examinations
                    WHERE id = ?
                ";

                $copyStmt = $conn->prepare($copySql);
                if (!$copyStmt) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }
                $copyStmt->bind_param("i", $examId);
                try {
                    $copyStmt->execute();
                } catch (Throwable $copyErr) {
                    $msg = strtolower((string)$copyErr->getMessage());
                    if (str_contains($msg, "field 'id'") && str_contains($msg, 'default value') && $repoIdAutoIncrement) {
                        $copyStmt->close();
                        $repoIdAutoIncrement = false;
                        if ($generatedRepoId <= 0) {
                            $generatedRepoId = getNextId($conn, 'exam_repository', 'id');
                        }
                        array_splice($insertCols, 1, 0, ['id']);
                        array_splice($selectCols, 1, 0, [(string)$generatedRepoId]);
                        $copySql = "
                            INSERT INTO exam_repository
                              (" . implode(', ', $insertCols) . ")
                            SELECT " . implode(', ', $selectCols) . "
                            FROM examinations
                            WHERE id = ?
                        ";
                        $copyStmt = $conn->prepare($copySql);
                        if (!$copyStmt) {
                            throw new Exception('Prepare failed: ' . $conn->error);
                        }
                        $copyStmt->bind_param("i", $examId);
                        $copyStmt->execute();
                    } else {
                        throw $copyErr;
                    }
                }
                $repoId = $repoIdAutoIncrement ? (int) $copyStmt->insert_id : (int) $generatedRepoId;
                $copyStmt->close();
            }

            if ($repoId) {
                $deleteRepoQuestionsStmt = $conn->prepare("DELETE FROM exam_repository_questions WHERE exam_id = ?");
                if (!$deleteRepoQuestionsStmt) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }
                $deleteRepoQuestionsStmt->bind_param("i", $repoId);
                $deleteRepoQuestionsStmt->execute();
                $deleteRepoQuestionsStmt->close();

                $copyQuestionsStmt = $conn->prepare("
                    INSERT INTO exam_repository_questions
                      (exam_id, question_number, question_type, question_text,
                       points, answer_key, options, expected_answer)
                    SELECT ?, question_number, question_type, question_text,
                           points, answer_key, options, expected_answer
                    FROM examination_questions
                    WHERE examination_id = ?
                ");
                if (!$copyQuestionsStmt) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }
                $copyQuestionsStmt->bind_param("ii", $repoId, $examId);
                $copyQuestionsStmt->execute();
                $copyQuestionsStmt->close();
            }
        }

        if ($newStatus === 'cancelled') {
            $cancelRepoStmt = $conn->prepare("UPDATE exam_repository SET status = 'cancelled' WHERE original_exam_id = ?");
            if (!$cancelRepoStmt) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $cancelRepoStmt->bind_param('i', $examId);
            $cancelRepoStmt->execute();
            $cancelRepoStmt->close();
        }

        echo json_encode([
            'success' => true,
            'message' => 'Examination status updated successfully'
        ]);
    } else {
        throw new Exception("Failed to update examination status: " . $stmt->error);
    }

    $stmt->close();
} catch (Exception $e) {
    error_log("Error updating exam status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>