<?php
session_start();

define('SUPPRESS_DB_ERRORS', true);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/db.php';

    $dbName = 'hr2_critical_gaps';
    $conn = $connections[$dbName] ?? null;
    if (!($conn instanceof mysqli)) {
        throw new RuntimeException("Connection not found for {$dbName}");
    }

    $conn->set_charset('utf8mb4');

    $action = isset($_GET['action']) ? trim((string)$_GET['action']) : 'list';

    if ($action !== 'list' && !isset($_SESSION['employee_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    if ($action === 'list') {
        $sql = "SELECT id, name, description, required_level FROM competency_criteria ORDER BY id DESC";
        $res = $conn->query($sql);
        if (!$res) {
            throw new RuntimeException($conn->error ?: 'Query failed');
        }

        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'id' => (int)($r['id'] ?? 0),
                'name' => (string)($r['name'] ?? ''),
                'description' => (string)($r['description'] ?? ''),
                'required_level' => (float)($r['required_level'] ?? 0),
            ];
        }
        $res->free();

        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    }

    if ($action === 'create') {
        $name = trim((string)($payload['name'] ?? ''));
        $description = trim((string)($payload['description'] ?? ''));
        $requiredLevelRaw = $payload['required_level'] ?? null;

        if ($name === '' || $description === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Name and description are required']);
            exit;
        }

        if (!is_numeric($requiredLevelRaw)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Required level is required']);
            exit;
        }

        $requiredLevel = (float)$requiredLevelRaw;
        if ($requiredLevel < 0) $requiredLevel = 0;
        if ($requiredLevel > 100) $requiredLevel = 100;

        $stmt = $conn->prepare("INSERT INTO competency_criteria (name, description, required_level) VALUES (?, ?, ?)");
        if (!$stmt) throw new RuntimeException($conn->error);
        $stmt->bind_param('ssd', $name, $description, $requiredLevel);

        $ok = $stmt->execute();
        if (!$ok) {
            $errno = (int)$stmt->errno;
            $err = $stmt->error ?: 'Insert failed';
            $stmt->close();

            if ($errno === 1062) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'This competency name already exists']);
                exit;
            }

            throw new RuntimeException($err);
        }

        $newId = (int)$stmt->insert_id;
        $stmt->close();

        echo json_encode(['success' => true, 'data' => ['id' => $newId]]);
        exit;
    }

    if ($action === 'update') {
        $id = isset($payload['id']) ? (int)$payload['id'] : 0;
        $name = trim((string)($payload['name'] ?? ''));
        $description = trim((string)($payload['description'] ?? ''));
        $requiredLevelRaw = $payload['required_level'] ?? null;

        if ($id <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid id']);
            exit;
        }

        if ($name === '' || $description === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Name and description are required']);
            exit;
        }

        if (!is_numeric($requiredLevelRaw)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Required level is required']);
            exit;
        }

        $requiredLevel = (float)$requiredLevelRaw;
        if ($requiredLevel < 0) $requiredLevel = 0;
        if ($requiredLevel > 100) $requiredLevel = 100;

        $stmt = $conn->prepare("UPDATE competency_criteria SET name = ?, description = ?, required_level = ? WHERE id = ?");
        if (!$stmt) throw new RuntimeException($conn->error);
        $stmt->bind_param('ssdi', $name, $description, $requiredLevel, $id);

        $ok = $stmt->execute();
        if (!$ok) {
            $errno = (int)$stmt->errno;
            $err = $stmt->error ?: 'Update failed';
            $stmt->close();

            if ($errno === 1062) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'This competency name already exists']);
                exit;
            }

            throw new RuntimeException($err);
        }

        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete') {
        $id = isset($payload['id']) ? (int)$payload['id'] : 0;
        if ($id <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid id']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM competency_criteria WHERE id = ?");
        if (!$stmt) throw new RuntimeException($conn->error);
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        if (!$ok) {
            $err = $stmt->error ?: 'Delete failed';
            $stmt->close();
            throw new RuntimeException($err);
        }

        if ($stmt->affected_rows === 0) {
            $stmt->close();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Not found']);
            exit;
        }
        $stmt->close();

        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
