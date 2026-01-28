<?php


header('Content-Type: application/json');

function columnExists($conn, $table, $column) {
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

// Database connection
require_once __DIR__ . '/../db.php';

// Create connection
$conn = usm_db_connect('learning_db');

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$conn->set_charset('utf8mb4');

// Get POST data
$examId = isset($_POST['exam_id']) ? intval($_POST['exam_id']) : 0;
$newStatus = isset($_POST['new_status']) ? $_POST['new_status'] : '';
$remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';

if ($examId <= 0 || empty($newStatus)) {
    die(json_encode(['success' => false, 'message' => 'Invalid parameters']));
}

// Valid statuses
$newStatus = $newStatus === 'for-compliance' ? 'compliance' : $newStatus;
$validStatuses = ['approved', 'rejected', 'compliance', 'hold', 'cancelled'];
if (!in_array($newStatus, $validStatuses, true)) {
    die(json_encode(['success' => false, 'message' => 'Invalid status']));
}

try {
    // Update exam status
    $stmt = $conn->prepare("UPDATE examinations SET status = ?, remarks = ?, reviewed_at = NOW() WHERE id = ?");
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
            $existsStmt->bind_param("i", $examId);
            $existsStmt->execute();
            $existsResult = $existsStmt->get_result();
            if ($existsResult && $existsResult->num_rows > 0) {
                $repoId = (int) $existsResult->fetch_assoc()['id'];
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
                $updateRepoStmt->bind_param("i", $repoId);
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
                    "'approved'"
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

                $copySql = "
                    INSERT INTO exam_repository
                      (" . implode(', ', $insertCols) . ")
                    SELECT " . implode(', ', $selectCols) . "
                    FROM examinations
                    WHERE id = ?
                ";

                $copyStmt = $conn->prepare($copySql);
                $copyStmt->bind_param("i", $examId);
                $copyStmt->execute();
                $repoId = (int) $copyStmt->insert_id;
                $copyStmt->close();
            }

            if ($repoId) {
                $deleteRepoQuestionsStmt = $conn->prepare("DELETE FROM exam_repository_questions WHERE exam_id = ?");
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
                $copyQuestionsStmt->bind_param("ii", $repoId, $examId);
                $copyQuestionsStmt->execute();
                $copyQuestionsStmt->close();
            }
        }

        if ($newStatus === 'cancelled') {
            $cancelRepoStmt = $conn->prepare("UPDATE exam_repository SET status = 'cancelled' WHERE original_exam_id = ?");
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
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>