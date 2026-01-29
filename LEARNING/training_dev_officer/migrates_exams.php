<?php
// Database connection
require_once __DIR__ . '/../db.php';

// Create connection
$conn = usm_db_connect('hr2_learning_db');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

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

    $sql = "SELECT COALESCE(MAX(`$column`), 0) + 1 AS next_id FROM `$table`";
    $res = $conn->query($sql);
    $row = $res ? $res->fetch_assoc() : null;
    return (int)($row['next_id'] ?? 0);
}

echo "<h2>Migrating Examinations to Repository</h2>";

// Get all examinations that are approved, rejected, or compliance
$sql = "SELECT e.*, COUNT(eq.id) as question_count 
        FROM examinations e 
        LEFT JOIN examination_questions eq ON e.id = eq.examination_id 
        WHERE e.status IN ('approved', 'rejected', 'compliance', 'for-compliance')
        GROUP BY e.id
        ORDER BY e.created_at DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $migrated = 0;
    $skipped = 0;
    
    while($exam = $result->fetch_assoc()) {
        // Check if exam already exists in repository
        $check_sql = "SELECT id FROM exam_repository WHERE original_exam_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $exam['id']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $exists = $check_result->num_rows > 0;
        $check_stmt->close();
        
        if (!$exists) {
            $examStatus = ($exam['status'] ?? 'approved') === 'for-compliance' ? 'compliance' : ($exam['status'] ?? 'approved');

            $repoIdAutoIncrement = isAutoIncrement($conn, 'exam_repository', 'id');
            $generatedRepoId = $repoIdAutoIncrement ? 0 : getNextId($conn, 'exam_repository', 'id');

            $moduleId = isset($exam['module_id']) ? (int)$exam['module_id'] : null;

            // Insert into repository
            $insertCols = [
                'exam_id',
                'original_exam_id',
                'title',
                'description',
                'department',
                'roles',
                'duration',
                'passing_score',
                'module_id',
                'status',
                'created_by',
                'approved_by',
                'created_at'
            ];
            if (!$repoIdAutoIncrement) {
                array_splice($insertCols, 1, 0, ['id']);
            }

            $placeholders = array_fill(0, count($insertCols), '?');
            $insert_sql = "INSERT INTO exam_repository (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $conn->prepare($insert_sql);
            if ($repoIdAutoIncrement) {
                $stmt->bind_param(
                    "iisssssidisiiis",
                    $zero = 0,
                    $exam['id'],
                    $exam['title'],
                    $exam['description'] ?? '',
                    $exam['department'] ?? 'general',
                    $exam['roles'] ?? 'all-employees',
                    $exam['duration'] ?? 60,
                    $exam['passing_score'] ?? 70,
                    $moduleId,
                    $examStatus,
                    $exam['created_by'] ?? 1,
                    $exam['approved_by'] ?? 1,
                    $exam['created_at']
                );
            } else {
                $stmt->bind_param(
                    "iiisssssidisiiis",
                    $zero = 0,
                    $generatedRepoId,
                    $exam['id'],
                    $exam['title'],
                    $exam['description'] ?? '',
                    $exam['department'] ?? 'general',
                    $exam['roles'] ?? 'all-employees',
                    $exam['duration'] ?? 60,
                    $exam['passing_score'] ?? 70,
                    $moduleId,
                    $examStatus,
                    $exam['created_by'] ?? 1,
                    $exam['approved_by'] ?? 1,
                    $exam['created_at']
                );
            }
            
            if ($stmt->execute()) {
                $migrated++;
                echo "<p style='color: green;'>✓ Migrated: " . htmlspecialchars($exam['title']) . " (ID: {$exam['id']})</p>";
            } else {
                echo "<p style='color: red;'>✗ Failed: " . htmlspecialchars($exam['title']) . " - " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            $skipped++;
            echo "<p style='color: orange;'>○ Skipped (already in repository): " . htmlspecialchars($exam['title']) . "</p>";
        }
    }
    
    echo "<h3>Migration Summary</h3>";
    echo "<p>Migrated: $migrated exams</p>";
    echo "<p>Skipped: $skipped exams (already in repository)</p>";
    echo "<p>Total processed: " . ($migrated + $skipped) . " exams</p>";
} else {
    echo "<p>No examinations to migrate.</p>";
}

$conn->close();
?>