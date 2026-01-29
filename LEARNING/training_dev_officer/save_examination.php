<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Database connection
require_once __DIR__ . '/../db.php';

function columnExists(mysqli $conn, string $table, string $column): bool {
    $dbResult = $conn->query('SELECT DATABASE() AS db');
    $dbRow = $dbResult ? $dbResult->fetch_assoc() : null;
    $dbName = $dbRow['db'] ?? '';

    if ($dbName === '') {
        return false;
    }

    $sql = "SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $dbName, $table, $column);
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = $res && $res->num_rows > 0;
    $stmt->close();
    return $exists;
}

function isAutoIncrement(mysqli $conn, string $table, string $column = 'id'): bool {
    $dbResult = $conn->query('SELECT DATABASE() AS db');
    $dbRow = $dbResult ? $dbResult->fetch_assoc() : null;
    $dbName = $dbRow['db'] ?? '';
    if ($dbName === '') {
        return false;
    }

    $sql = "SELECT EXTRA
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $dbName, $table, $column);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    $extra = (string)($row['EXTRA'] ?? '');
    return stripos($extra, 'auto_increment') !== false;
}

function getNextId(mysqli $conn, string $table, string $column = 'id'): int {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        throw new Exception('Invalid table/column name');
    }

    $sql = "SELECT COALESCE(MAX(`$column`), 0) + 1 AS next_id FROM `$table` FOR UPDATE";
    $res = $conn->query($sql);
    $row = $res ? $res->fetch_assoc() : null;
    return (int)($row['next_id'] ?? 0);
}

// Create connection
$conn = usm_db_connect('hr2_learning_db');

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// Set charset
$conn->set_charset("utf8mb4");

// Check if exam data is provided
if (!isset($_POST['exam_data'])) {
    echo json_encode(['success' => false, 'message' => 'No exam data provided']);
    exit;
}

$action = $_POST['action'] ?? '';
$draftId = isset($_POST['draft_id']) ? (int) $_POST['draft_id'] : 0;
$editExamId = isset($_POST['exam_id']) ? (int) $_POST['exam_id'] : 0;

// Decode the exam data
$examData = json_decode($_POST['exam_data'], true);

if (!$examData) {
    echo json_encode(['success' => false, 'message' => 'Invalid exam data format']);
    exit;
}

if (!isset($examData['questions']) || !is_array($examData['questions'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid exam questions data']);
    exit;
}

$requiredKeys = ['title', 'description', 'module_id', 'module_title', 'department', 'roles', 'status', 'total_points', 'passing_score', 'duration', 'created_by'];
foreach ($requiredKeys as $key) {
    if (!array_key_exists($key, $examData)) {
        echo json_encode(['success' => false, 'message' => 'Missing required field: ' . $key]);
        exit;
    }
}

try {
    $step = 'init';
    $conn->begin_transaction();

    $step = 'schema_check';
    $examsIdAutoIncrement = isAutoIncrement($conn, 'examinations', 'id');
    $examQuestionsHasId = columnExists($conn, 'examination_questions', 'id');
    $examQuestionsIdAutoIncrement = $examQuestionsHasId ? isAutoIncrement($conn, 'examination_questions', 'id') : true;

    $questionsExamIdCol = columnExists($conn, 'examination_questions', 'exam_id') ? 'exam_id' : 'examination_id';
    if ($questionsExamIdCol !== 'exam_id' && $questionsExamIdCol !== 'examination_id') {
        throw new Exception('Invalid examination_questions FK column');
    }

    $isDraftAction = ($examData['status'] ?? '') === 'draft' || $action === 'save_draft';
    $isUpdateFromDraftToSubmit = $action === 'create_exam' && $draftId > 0;
    $isEditAction = $action === 'update_exam' && $editExamId > 0;
    $shouldUpdateExistingDraft = $draftId > 0 && ($isDraftAction || $isUpdateFromDraftToSubmit);

    if ($isEditAction) {
        $newStatus = ($examData['status'] ?? 'pending');

        $stmt = $conn->prepare("UPDATE examinations
                                SET title = ?, description = ?, module_id = ?, module_title = ?, department = ?, roles = ?,
                                    status = ?, total_points = ?, passing_score = ?, duration = ?, created_by = ?
                                WHERE id = ? AND status IN ('pending', 'cancelled')");

        $stmt->bind_param(
            "ssisssssidiii",
            $examData['title'],
            $examData['description'],
            $examData['module_id'],
            $examData['module_title'],
            $examData['department'],
            $examData['roles'],
            $newStatus,
            $examData['total_points'],
            $examData['passing_score'],
            $examData['duration'],
            $examData['created_by'],
            $editExamId
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to update examination: " . $stmt->error);
        }

        if ($stmt->affected_rows === 0) {
            throw new Exception('Examination not found or not editable');
        }

        $examId = $editExamId;
        $stmt->close();
    } elseif ($shouldUpdateExistingDraft) {
        $step = 'update_draft';
        $newStatus = $isDraftAction ? 'draft' : ($examData['status'] ?? 'pending');

        $stmt = $conn->prepare("UPDATE examinations
                                SET title = ?, description = ?, module_id = ?, module_title = ?, department = ?, roles = ?,
                                    status = ?, total_points = ?, passing_score = ?, duration = ?, created_by = ?
                                WHERE id = ? AND status = 'draft'");

        $stmt->bind_param(
            "ssisssssidiii",
            $examData['title'],
            $examData['description'],
            $examData['module_id'],
            $examData['module_title'],
            $examData['department'],
            $examData['roles'],
            $newStatus,
            $examData['total_points'],
            $examData['passing_score'],
            $examData['duration'],
            $examData['created_by'],
            $draftId
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to update draft: " . $stmt->error);
        }

        if ($stmt->affected_rows === 0) {
            throw new Exception('Draft not found or not editable');
        }

        $examId = $draftId;
        $stmt->close();
    } else {
        $step = 'insert_examination';
 if ($examsIdAutoIncrement) {
            $stmt = $conn->prepare("INSERT INTO examinations
                                    (title, description, module_id, module_title, department, roles,
                                     status, total_points, passing_score, duration, created_by )
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )");
 
            $stmt->bind_param(
                "ssisssssidi",
                $examData['title'],
                $examData['description'],
                $examData['module_id'],
                $examData['module_title'],
                $examData['department'],
                $examData['roles'],
                $examData['status'],
                $examData['total_points'],
                $examData['passing_score'],
                $examData['duration'],
                $examData['created_by'],
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to insert examination: " . $stmt->error);
            }

            $examId = $stmt->insert_id;
            $stmt->close();
        } else {
            $generatedExamId = getNextId($conn, 'examinations', 'id');

            $stmt = $conn->prepare("INSERT INTO examinations
                                    (id, title, description, module_id, module_title, department, roles,
                                     status, total_points, passing_score, duration, created_by, created_at)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

            $stmt->bind_param(
                "ississsssidii",
                $generatedExamId,
                $examData['title'],
                $examData['description'],
                $examData['module_id'],
                $examData['module_title'],
                $examData['department'],
                $examData['roles'],
                $examData['status'],
                $examData['total_points'],
                $examData['passing_score'],
                $examData['duration'],
                $examData['created_by']
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to insert examination: " . $stmt->error);
            }

            $examId = $generatedExamId;
            $stmt->close();
        }
    }

    if ($shouldUpdateExistingDraft || $isEditAction) {
        $deleteStmt = $conn->prepare('DELETE FROM examination_questions WHERE ' . $questionsExamIdCol . ' = ?');
        $deleteStmt->bind_param('i', $examId);
        $deleteStmt->execute();
        $deleteStmt->close();
    }

    // Insert questions
    foreach ($examData['questions'] as $question) {
        $step = 'insert_questions';

        $optionsRaw = $question['options'] ?? '';
        $options = is_array($optionsRaw) ? json_encode($optionsRaw) : (string) $optionsRaw;
        $expectedAnswer = isset($question['expected_answer']) ? $question['expected_answer'] : '';

        if ($examQuestionsHasId && !$examQuestionsIdAutoIncrement) {
            $generatedQuestionId = getNextId($conn, 'examination_questions', 'id');
            $stmt = $conn->prepare('INSERT INTO examination_questions (id, ' . $questionsExamIdCol . ', question_number, question_type, question_text, points, answer_key, options, expected_answer)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');

            $stmt->bind_param("iiississs",
                $generatedQuestionId,
                $examId,
                $question['question_number'],
                $question['question_type'],
                $question['question_text'],
                $question['points'],
                $question['answer_key'],
                $options,
                $expectedAnswer
            );
        } else {
            $stmt = $conn->prepare('INSERT INTO examination_questions (' . $questionsExamIdCol . ', question_number, question_type, question_text, points, answer_key, options, expected_answer)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)');

            $stmt->bind_param("iississs",
                $examId,
                $question['question_number'],
                $question['question_type'],
                $question['question_text'],
                $question['points'],
                $question['answer_key'],
                $options,
                $expectedAnswer
            );
        }

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert question: " . $stmt->error);
        }

        $stmt->close();
    }

    if (!$isDraftAction && ($examData['status'] ?? '') === 'pending') {
        $repoId = 0;

        $repoHasTotalPoints = columnExists($conn, 'exam_repository', 'total_points');
        $repoHasPassingScore = columnExists($conn, 'exam_repository', 'passing_score');
        $repoHasDuration = columnExists($conn, 'exam_repository', 'duration');

        $existsStmt = $conn->prepare('SELECT id FROM exam_repository WHERE original_exam_id = ? LIMIT 1');
        $existsStmt->bind_param('i', $examId);
        $existsStmt->execute();
        $existsRes = $existsStmt->get_result();
        if ($existsRes && $existsRes->num_rows > 0) {
            $repoId = (int) $existsRes->fetch_assoc()['id'];
        }
        $existsStmt->close();

        if ($repoId > 0) {
            $deleteRepoQuestionsStmt = $conn->prepare('DELETE FROM exam_repository_questions WHERE exam_id = ?');
            $deleteRepoQuestionsStmt->bind_param('i', $repoId);
            $deleteRepoQuestionsStmt->execute();
            $deleteRepoQuestionsStmt->close();

            $copyQuestionsStmt = $conn->prepare("INSERT INTO exam_repository_questions
                                                  (exam_id, question_number, question_type, question_text, points, answer_key, options, expected_answer)
                                                  SELECT ?, question_number, question_type, question_text, points, answer_key, options, expected_answer
                                                  FROM examination_questions
                                                  WHERE " . $questionsExamIdCol . " = ?");
            $copyQuestionsStmt->bind_param('ii', $repoId, $examId);
            $copyQuestionsStmt->execute();
            $copyQuestionsStmt->close();
        } else {
            $insertCols = [
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
                'id',
                'title',
                'description',
                'module_id',
                'department',
                'roles',
                'created_at',
                "'pending'"
            ];

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

            $copySql = "INSERT INTO exam_repository (" . implode(', ', $insertCols) . ")
                        SELECT " . implode(', ', $selectCols) . "
                        FROM examinations
                        WHERE id = ?";

            $copyStmt = $conn->prepare($copySql);
            $copyStmt->bind_param('i', $examId);
            $copyStmt->execute();
            $repoId = (int) $copyStmt->insert_id;
            $copyStmt->close();
        }

        if ($repoId > 0) {
            $updateRepoSql = 'UPDATE exam_repository SET ' . implode(', ', [
                "title = ?",
                "description = ?",
                "module_id = ?",
                "department = ?",
                "roles = ?",
                "status = 'pending'"
            ]) . ' WHERE id = ?';
            $updateRepoStmt = $conn->prepare($updateRepoSql);
            $updateRepoStmt->bind_param('ssissi', $examData['title'], $examData['description'], $examData['module_id'], $examData['department'], $examData['roles'], $repoId);
            $updateRepoStmt->execute();
            $updateRepoStmt->close();
        }
    }

    // Commit transaction
    $conn->commit();

    if ($isDraftAction) {
        echo json_encode([
            'success' => true,
            'draft_id' => $examId,
            'message' => 'Draft saved successfully'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'exam_id' => $examId,
            'message' => 'Examination created successfully'
        ]);
    }

} catch (Throwable $e) {
    // Rollback transaction on error
    if ($conn && $conn->errno === 0) {
        // no-op
    }

    try {
        $conn->rollback();
    } catch (Throwable $rollbackErr) {
        // ignore rollback errors
    }
    
    $stepSafe = isset($step) ? (string)$step : 'unknown';
    error_log("Error creating examination ({$stepSafe}): " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error (' . $stepSafe . '): ' . $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?>