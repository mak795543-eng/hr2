<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

$conn = usm_db_connect('learning_db');

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$exam_id = isset($_POST['exam_id']) ? (int) $_POST['exam_id'] : 0;
$original_exam_id = isset($_POST['original_exam_id']) ? (int) $_POST['original_exam_id'] : 0;

if ($original_exam_id > 0) {
    try {
        $conn->begin_transaction();

        $checkStmt = $conn->prepare("SELECT status FROM examinations WHERE id = ? LIMIT 1");
        $checkStmt->bind_param('i', $original_exam_id);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();
        $row = $checkRes ? $checkRes->fetch_assoc() : null;
        $checkStmt->close();

        if (!$row) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Examination not found']);
            exit();
        }

        if (($row['status'] ?? '') !== 'cancelled') {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Only cancelled examinations can be deleted']);
            exit();
        }

        $deleteQuestionsStmt = $conn->prepare('DELETE FROM examination_questions WHERE examination_id = ?');
        $deleteQuestionsStmt->bind_param('i', $original_exam_id);
        $deleteQuestionsStmt->execute();
        $deleteQuestionsStmt->close();

        $repoId = 0;
        $repoStmt = $conn->prepare('SELECT id FROM exam_repository WHERE original_exam_id = ? LIMIT 1');
        $repoStmt->bind_param('i', $original_exam_id);
        $repoStmt->execute();
        $repoRes = $repoStmt->get_result();
        if ($repoRes && $repoRes->num_rows > 0) {
            $repoId = (int) $repoRes->fetch_assoc()['id'];
        }
        $repoStmt->close();

        if ($repoId > 0) {
            $deleteRepoQuestionsStmt = $conn->prepare('DELETE FROM exam_repository_questions WHERE exam_id = ?');
            $deleteRepoQuestionsStmt->bind_param('i', $repoId);
            $deleteRepoQuestionsStmt->execute();
            $deleteRepoQuestionsStmt->close();

            $deleteRepoStmt = $conn->prepare('DELETE FROM exam_repository WHERE id = ?');
            $deleteRepoStmt->bind_param('i', $repoId);
            $deleteRepoStmt->execute();
            $deleteRepoStmt->close();
        }

        $deleteExamStmt = $conn->prepare("DELETE FROM examinations WHERE id = ? AND status = 'cancelled'");
        $deleteExamStmt->bind_param('i', $original_exam_id);
        $deleteExamStmt->execute();
        $deletedRows = $deleteExamStmt->affected_rows;
        $deleteExamStmt->close();

        if ($deletedRows <= 0) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
            exit();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Examination deleted']);
    } catch (Throwable $e) {
        if ($conn->errno) {
            $conn->rollback();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        $conn->close();
    }
    exit();
}

if ($exam_id > 0) {
    $deleteRepoQuestionsStmt = $conn->prepare('DELETE FROM exam_repository_questions WHERE exam_id = ?');
    $deleteRepoQuestionsStmt->bind_param('i', $exam_id);
    $deleteRepoQuestionsStmt->execute();
    $deleteRepoQuestionsStmt->close();

    $stmt = $conn->prepare('DELETE FROM exam_repository WHERE id = ?');
    $stmt->bind_param('i', $exam_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Examination deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Delete failed']);
    }

    $stmt->close();
    $conn->close();
    exit();
}

echo json_encode(['success' => false, 'message' => 'No exam ID provided']);
$conn->close();

?>