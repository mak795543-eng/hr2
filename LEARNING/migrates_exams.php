<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hr2_soliera_usm";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Migrating Examinations to Repository</h2>";

// Get all examinations that are approved, rejected, or for-compliance
$sql = "SELECT e.*, COUNT(eq.id) as question_count 
        FROM examinations e 
        LEFT JOIN examination_questions eq ON e.id = eq.examination_id 
        WHERE e.status IN ('approved', 'rejected', 'for-compliance')
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
            // Insert into repository
            $insert_sql = "INSERT INTO exam_repository (
                original_exam_id, 
                title, 
                description, 
                department, 
                roles, 
                duration, 
                passing_score,
                module_id,
                status,
                created_by,
                approved_by,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param(
                "isssssiissss",
                $exam['id'],
                $exam['title'],
                $exam['description'] ?? '',
                $exam['department'] ?? 'general',
                $exam['roles'] ?? 'all-employees',
                $exam['duration'] ?? 60,
                $exam['passing_score'] ?? 70,
                $exam['module_id'] ?? NULL,
                $exam['status'],
                $exam['created_by'] ?? 1,
                $exam['approved_by'] ?? 1,
                $exam['created_at']
            );
            
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