<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

$conn = usm_db_connect('hr2_learning_db');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$conn->set_charset('utf8mb4');

$action = $_POST['action'] ?? 'post';
$exam_id = $_POST['exam_id'] ?? null; // exam_repository.id
$original_exam_id = $_POST['original_exam_id'] ?? null; // examinations.id

$reviewer_id = (int) ($_SESSION['user_id'] ?? 1);

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

function isAutoIncrement(mysqli $conn, string $table, string $column): bool {
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
    $extra = $res && $res->num_rows > 0 ? $res->fetch_assoc()['EXTRA'] : '';
    $stmt->close();
    return strpos($extra, 'auto_increment') !== false;
}

function getNextId(mysqli $conn, string $table, string $column): int {
    $dbResult = $conn->query('SELECT DATABASE() AS db');
    $dbRow = $dbResult ? $dbResult->fetch_assoc() : null;
    $dbName = $dbRow['db'] ?? '';
    if ($dbName === '') {
        return 0;
    }

    $sql = "SELECT MAX($column) + 1 AS next_id
            FROM $table";
    $res = $conn->query($sql);
    $nextId = $res && $res->num_rows > 0 ? (int) $res->fetch_assoc()['next_id'] : 1;
    return $nextId;
}

function ensureRepositoryExam(mysqli $conn, int $originalExamId): int {
    $repoId = 0;

    $existsStmt = $conn->prepare('SELECT id FROM exam_repository WHERE original_exam_id = ? LIMIT 1');
    $existsStmt->bind_param('i', $originalExamId);
    $existsStmt->execute();
    $existsRes = $existsStmt->get_result();
    if ($existsRes && $existsRes->num_rows > 0) {
        $repoId = (int) $existsRes->fetch_assoc()['id'];
    }
    $existsStmt->close();

    if ($repoId > 0) {
        return $repoId;
    }

    $repoHasTotalPoints = columnExists($conn, 'exam_repository', 'total_points');
    $repoHasPassingScore = columnExists($conn, 'exam_repository', 'passing_score');
    $repoHasDuration = columnExists($conn, 'exam_repository', 'duration');

    $examHasTotalPoints = columnExists($conn, 'examinations', 'total_points');
    $examHasPassingScore = columnExists($conn, 'examinations', 'passing_score');
    $examHasDuration = columnExists($conn, 'examinations', 'duration');

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

    if ($repoHasTotalPoints && $examHasTotalPoints) {
        $insertCols[] = 'total_points';
        $selectCols[] = 'total_points';
    }
    if ($repoHasPassingScore && $examHasPassingScore) {
        $insertCols[] = 'passing_score';
        $selectCols[] = 'passing_score';
    }
    if ($repoHasDuration && $examHasDuration) {
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
    $copyStmt->bind_param('i', $originalExamId);
    try {
        if (!$copyStmt->execute()) {
            throw new Exception('Failed to copy exam into repository');
        }
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
            $copyStmt->bind_param('i', $originalExamId);
            $copyStmt->execute();
        } else {
            $copyStmt->close();
            throw $copyErr;
        }
    }

    $repoId = $repoIdAutoIncrement ? (int) $copyStmt->insert_id : (int) $generatedRepoId;
    $copyStmt->close();

    if ($repoId <= 0) {
        throw new Exception('Repository insert did not return an id');
    }

    // Copy questions
    $deleteStmt = $conn->prepare('DELETE FROM exam_repository_questions WHERE exam_id = ?');
    $deleteStmt->bind_param('i', $repoId);
    $deleteStmt->execute();
    $deleteStmt->close();

    $copyQuestionsStmt = $conn->prepare("
        INSERT INTO exam_repository_questions
          (exam_id, question_number, question_type, question_text,
           points, answer_key, options, expected_answer)
        SELECT ?, question_number, question_type, question_text,
               points, answer_key, options, expected_answer
        FROM examination_questions
        WHERE examination_id = ?
    ");
    $copyQuestionsStmt->bind_param('ii', $repoId, $originalExamId);
    $copyQuestionsStmt->execute();
    $copyQuestionsStmt->close();

    return $repoId;
}

try {
    if (!in_array($action, ['post', 'unpost'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }

    if ($action === 'post') {
        if ($original_exam_id !== null) {
            $origId = (int) $original_exam_id;
            if ($origId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid original_exam_id']);
                exit;
            }

            $repoId = ensureRepositoryExam($conn, $origId);

            $postStmt = $conn->prepare("UPDATE exam_repository SET status = 'posted', posted_by = ?, posted_at = NOW() WHERE id = ?");
            $postStmt->bind_param('ii', $reviewer_id, $repoId);
            if (!$postStmt->execute()) {
                $postStmt->close();
                throw new Exception('Failed to post examination');
            }
            $postStmt->close();

            // Also mark original exam as posted
            $origStmt = $conn->prepare("UPDATE examinations SET status = 'posted' WHERE id = ?");
            $origStmt->bind_param('i', $origId);
            $origStmt->execute();
            $origStmt->close();

            echo json_encode(['success' => true, 'message' => 'Examination posted', 'repo_id' => $repoId]);
            exit;
        }

        $repoId = (int) $exam_id;
        if ($repoId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No exam id provided']);
            exit;
        }

        $postStmt = $conn->prepare("UPDATE exam_repository SET status = 'posted', posted_by = ?, posted_at = NOW() WHERE id = ?");
        $postStmt->bind_param('ii', $reviewer_id, $repoId);
        if (!$postStmt->execute()) {
            $postStmt->close();
            throw new Exception('Failed to post examination');
        }
        $postStmt->close();

        echo json_encode(['success' => true, 'message' => 'Examination posted', 'repo_id' => $repoId]);
        exit;
    }

    // action === unpost
    if ($original_exam_id !== null) {
        $origId = (int) $original_exam_id;
        if ($origId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid original_exam_id']);
            exit;
        }

        $findRepo = $conn->prepare('SELECT id FROM exam_repository WHERE original_exam_id = ? LIMIT 1');
        $findRepo->bind_param('i', $origId);
        $findRepo->execute();
        $findRes = $findRepo->get_result();
        $repoId = $findRes && $findRes->num_rows > 0 ? (int) $findRes->fetch_assoc()['id'] : 0;
        $findRepo->close();

        if ($repoId > 0) {
            $unpostStmt = $conn->prepare("UPDATE exam_repository SET status = 'approved', posted_by = NULL, posted_at = NULL WHERE id = ?");
            $unpostStmt->bind_param('i', $repoId);
            $unpostStmt->execute();
            $unpostStmt->close();
        }

        $origStmt = $conn->prepare("UPDATE examinations SET status = 'approved' WHERE id = ?");
        $origStmt->bind_param('i', $origId);
        $origStmt->execute();
        $origStmt->close();

        echo json_encode(['success' => true, 'message' => 'Examination unposted']);
        exit;
    }

    $repoId = (int) $exam_id;
    if ($repoId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No exam id provided']);
        exit;
    }

    $unpostStmt = $conn->prepare("UPDATE exam_repository SET status = 'approved', posted_by = NULL, posted_at = NULL WHERE id = ?");
    $unpostStmt->bind_param('i', $repoId);
    if (!$unpostStmt->execute()) {
        $unpostStmt->close();
        throw new Exception('Failed to unpost examination');
    }
    $unpostStmt->close();

    echo json_encode(['success' => true, 'message' => 'Examination unposted']);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
} finally {
    $conn->close();
}
?>