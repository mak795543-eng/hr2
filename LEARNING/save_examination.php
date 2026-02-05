<?php
// save_examination.php - DEBUG VERSION
header('Content-Type: application/json');

// Enable error reporting but capture errors
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering to capture any stray output
ob_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "learning_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    $error = 'Database connection failed: ' . $conn->connect_error;
    ob_end_clean(); // Clear any output
    echo json_encode(['success' => false, 'message' => $error]);
    exit;
}

// Initialize response array
$response = ['success' => false, 'message' => 'Unknown error'];

try {
    // Get raw POST data
    $rawData = file_get_contents('php://input');
    
    // Log the raw input for debugging
    error_log("Raw POST data: " . substr($rawData, 0, 1000));
    
    if (empty($rawData)) {
        // Try to get data from $_POST
        if (isset($_POST['exam_data'])) {
            $rawData = $_POST['exam_data'];
        }
    }
    
    if (empty($rawData)) {
        throw new Exception('No exam data received');
    }
    
    $data = json_decode($rawData, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }
    
    error_log("Decoded data keys: " . implode(', ', array_keys($data)));

    // Start transaction
    $conn->begin_transaction();

    // Insert into examinations table
    $sql = "INSERT INTO examinations (title, description, module_id, status, total_points, department, created_by, duration, passing_score) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $status = 'pending'; // Default status
    $department = $data['department'] ?? 'General';
    $created_by = $data['created_by'] ?? 'System';
    $duration = $data['duration'] ?? 60;
    $passing_score = $data['passing_score'] ?? 70;
    $total_points = $data['total_points'] ?? 0;
    $module_id = $data['module_id'] ?? 0;
    
    // Validate required fields
    if (empty($data['title'])) {
        throw new Exception('Examination title is required');
    }
    
    $stmt->bind_param("ssisissii", 
        $data['title'],
        $data['description'] ?? '',
        $module_id,
        $status,
        $total_points,
        $department,
        $created_by,
        $duration,
        $passing_score
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to save examination: ' . $stmt->error);
    }

    $examId = $conn->insert_id;
    $stmt->close();

    // Insert questions if they exist in the data
    if (isset($data['questions']) && is_array($data['questions']) && !empty($data['questions'])) {
        error_log("Saving " . count($data['questions']) . " questions");
        
        foreach ($data['questions'] as $index => $question) {
            // Insert question
            $sql = "INSERT INTO exam_questions (exam_id, question_number, question_type, question_text, points, expected_answer) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Prepare failed for questions: ' . $conn->error);
            }
            
            $expectedAnswer = '';
            if (isset($question['expected_answer'])) {
                $expectedAnswer = $question['expected_answer'];
            } elseif (isset($question['correct_answers']) && is_array($question['correct_answers'])) {
                $expectedAnswer = implode(',', $question['correct_answers']);
            }
            
            $questionType = $question['type'] ?? 'multiple';
            $questionText = $question['text'] ?? '';
            $points = $question['points'] ?? 1;
            
            $stmt->bind_param("iissis", 
                $examId,
                $question['number'] ?? ($index + 1),
                $questionType,
                $questionText,
                $points,
                $expectedAnswer
            );

            if (!$stmt->execute()) {
                throw new Exception('Failed to save question: ' . $stmt->error);
            }

            $questionId = $conn->insert_id;
            $stmt->close();

            // Insert options for multiple choice/truefalse questions
            if (($questionType === 'multiple' || $questionType === 'truefalse') && isset($question['options']) && is_array($question['options'])) {
                foreach ($question['options'] as $optIndex => $optionText) {
                    $sql = "INSERT INTO exam_question_options (question_id, option_text, option_order) 
                            VALUES (?, ?, ?)";
                    
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        throw new Exception('Prepare failed for options: ' . $conn->error);
                    }
                    
                    $stmt->bind_param("isi", 
                        $questionId,
                        $optionText,
                        $optIndex + 1
                    );

                    if (!$stmt->execute()) {
                        throw new Exception('Failed to save option: ' . $stmt->error);
                    }
                    
                    $stmt->close();
                }
            }
        }
    } else {
        error_log("No questions found in data");
    }

    // Commit transaction
    $conn->commit();
    
    $response = [
        'success' => true,
        'message' => 'Examination created successfully',
        'exam_id' => $examId
    ];

} catch (Exception $e) {
    // Rollback on error
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
    }
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    error_log("Error in save_examination.php: " . $e->getMessage());
} finally {
    // Close connection
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}

// Clear any output buffers
ob_end_clean();

// Output JSON response
echo json_encode($response);
exit;
?>