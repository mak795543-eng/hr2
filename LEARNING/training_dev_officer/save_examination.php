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

function columnIsAutoIncrement(mysqli $conn, string $table, string $column): bool {
    $dbResult = $conn->query('SELECT DATABASE() AS db');
    $dbRow = $dbResult ? $dbResult->fetch_assoc() : null;
    $dbName = $dbRow['db'] ?? '';
    if ($dbName === '') return false;

    $sql = "SELECT EXTRA, COLUMN_DEFAULT
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $dbName, $table, $column);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$row) return false;
    $extra = $row['EXTRA'] ?? '';
    return stripos($extra, 'auto_increment') !== false;
}

function attemptFixAutoIncrement(mysqli $conn, string $table, string $column): bool {
    // Try to make the id column AUTO_INCREMENT PRIMARY KEY if possible
    try {
        $sql = "ALTER TABLE `" . $conn->real_escape_string($table) . "` MODIFY `" . $conn->real_escape_string($column) . "` INT NOT NULL AUTO_INCREMENT PRIMARY KEY";
        $conn->query($sql);
        error_log("Attempted to set AUTO_INCREMENT on {$table}.{$column}: {$sql}");
        return true;
    } catch (Throwable $e) {
        error_log("Failed to set AUTO_INCREMENT on {$table}.{$column}: " . $e->getMessage());
        return false;
    }
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
    $conn->begin_transaction();

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
            "ssissssiiisi",
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
        $newStatus = $isDraftAction ? 'draft' : ($examData['status'] ?? 'pending');

        $stmt = $conn->prepare("UPDATE examinations
                                SET title = ?, description = ?, module_id = ?, module_title = ?, department = ?, roles = ?,
                                    status = ?, total_points = ?, passing_score = ?, duration = ?, created_by = ?
                                WHERE id = ? AND status = 'draft'");

        $stmt->bind_param(
            "ssissssiiisi",
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
        $stmt = $conn->prepare("INSERT INTO examinations
                                (title, description, module_id, module_title, department, roles,
                                 status, total_points, passing_score, duration, created_by, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        $stmt->bind_param(
            "ssissssiiis",
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

        $examId = $stmt->insert_id;
        $stmt->close();
    }

    if ($shouldUpdateExistingDraft || $isEditAction) {
        $deleteStmt = $conn->prepare('DELETE FROM examination_questions WHERE examination_id = ?');
        $deleteStmt->bind_param('i', $examId);
        $deleteStmt->execute();
        $deleteStmt->close();
    }

    // Insert questions
    foreach ($examData['questions'] as $question) {
        $stmt = $conn->prepare("INSERT INTO examination_questions 
                                (examination_id, question_number, question_type, question_text, 
                                 points, answer_key, options, expected_answer) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $optionsRaw = $question['options'] ?? '';
        $options = is_array($optionsRaw) ? json_encode($optionsRaw) : (string) $optionsRaw;
        $expectedAnswer = isset($question['expected_answer']) ? $question['expected_answer'] : '';

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
            $setParts = [
                "title = ?",
                "description = ?",
                "module_id = ?",
                "department = ?",
                "roles = ?",
                "status = 'pending'"
            ];
            $bindTypes = 'ssiss';
            $bindValues = [
                $examData['title'],
                $examData['description'],
                $examData['module_id'],
                $examData['department'],
                $examData['roles']
            ];

            if ($repoHasTotalPoints) {
                $setParts[] = "total_points = ?";
                $bindTypes .= 'i';
                $bindValues[] = $examData['total_points'];
            }
            if ($repoHasPassingScore) {
                $setParts[] = "passing_score = ?";
                $bindTypes .= 'i';
                $bindValues[] = $examData['passing_score'];
            }
            if ($repoHasDuration) {
                $setParts[] = "duration = ?";
                $bindTypes .= 'i';
                $bindValues[] = $examData['duration'];
            }

            $bindTypes .= 'i';
            $bindValues[] = $repoId;

            $updateRepoSql = 'UPDATE exam_repository SET ' . implode(', ', $setParts) . ' WHERE id = ?';
            $updateRepoStmt = $conn->prepare($updateRepoSql);
            $updateRepoStmt->bind_param($bindTypes, ...$bindValues);
            $updateRepoStmt->execute();
            $updateRepoStmt->close();
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
            // Before executing the copy into `exam_repository`, ensure the `id` column can be auto-generated.
            if (!columnIsAutoIncrement($conn, 'exam_repository', 'id')) {
                error_log("Diagnostic: exam_repository.id is not AUTO_INCREMENT. Attempting to fix...");
                $attempted = attemptFixAutoIncrement($conn, 'exam_repository', 'id');
                // Re-check
                if (!$attempted || !columnIsAutoIncrement($conn, 'exam_repository', 'id')) {
                    // Dump schema for debugging
                    $show = $conn->query("SHOW CREATE TABLE `exam_repository`");
                    $row = $show ? $show->fetch_assoc() : null;
                    $createSql = $row['Create Table'] ?? 'N/A';
                    error_log("Error (copy_to_repository): exam_repository.id not AUTO_INCREMENT and automatic fix failed. Table definition:\n" . $createSql);
                    throw new Exception("Error (copy_to_repository): exam_repository.id is not AUTO_INCREMENT. Automatic fix failed. See server logs for CREATE TABLE output.");
                }
            }

            $copyStmt->execute();
            $repoId = (int) $copyStmt->insert_id;
            $copyStmt->close();
        }

        if ($repoId > 0) {
            $deleteRepoQuestionsStmt = $conn->prepare('DELETE FROM exam_repository_questions WHERE exam_id = ?');
            $deleteRepoQuestionsStmt->bind_param('i', $repoId);
            $deleteRepoQuestionsStmt->execute();
            $deleteRepoQuestionsStmt->close();

            $copyQuestionsStmt = $conn->prepare("INSERT INTO exam_repository_questions
                                                  (exam_id, question_number, question_type, question_text, points, answer_key, options, expected_answer)
                                                  SELECT ?, question_number, question_type, question_text, points, answer_key, options, expected_answer
                                                  FROM examination_questions
                                                  WHERE examination_id = ?");
            $copyQuestionsStmt->bind_param('ii', $repoId, $examId);
            $copyQuestionsStmt->execute();
            $copyQuestionsStmt->close();
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
    
    error_log("Error creating examination: " . $e->getMessage());
    
    // Also write a detailed diagnostic log file to assist debugging
    try {
        $logPath = __DIR__ . '/save_examination_error.log';
        $time = date('Y-m-d H:i:s');
        $details = "[{$time}] Exception: " . $e->getMessage() . "\n";
        $details .= "Trace:\n" . $e->getTraceAsString() . "\n\n";

        $tables = ['examinations', 'examination_questions', 'exam_repository', 'exam_repository_questions'];
        foreach ($tables as $t) {
            try {
                $res = $conn->query("SHOW CREATE TABLE `" . $conn->real_escape_string($t) . "`");
                if ($res) {
                    $row = $res->fetch_assoc();
                    $ct = $row['Create Table'] ?? json_encode($row);
                    $details .= "SHOW CREATE TABLE {$t}:\n" . $ct . "\n\n";
                } else {
                    $details .= "SHOW CREATE TABLE {$t}: FAILED or table does not exist\n\n";
                }
            } catch (Throwable $te) {
                $details .= "SHOW CREATE TABLE {$t} ERROR: " . $te->getMessage() . "\n\n";
            }
        }

        file_put_contents($logPath, $details, FILE_APPEND | LOCK_EX);
        error_log("Detailed diagnostic written to: " . $logPath);
    } catch (Throwable $logErr) {
        error_log("Failed to write diagnostic log: " . $logErr->getMessage());
    }

    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?>