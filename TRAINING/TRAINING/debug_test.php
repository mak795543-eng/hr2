<?php
session_start();
require_once __DIR__ . '/db.php';

echo "Database Connection Test\n";
echo "========================\n";

// Test 1: Check if training_programs table exists
try {
    $result = $conn->query("DESCRIBE training_programs");
    echo "✓ training_programs table exists\n";
} catch (Exception $e) {
    echo "✗ training_programs table error: " . $e->getMessage() . "\n";
}

// Test 2: Check if training_program_status_logs table exists
try {
    $result = $conn->query("DESCRIBE training_program_status_logs");
    echo "✓ training_program_status_logs table exists\n";
} catch (Exception $e) {
    echo "✗ training_program_status_logs table error: " . $e->getMessage() . "\n";
}

// Test 3: Try to insert a test log entry
try {
    $conn->begin_transaction();
    
    // Get a sample program
    $stmt = $conn->query("SELECT id, status FROM training_programs LIMIT 1");
    $program = $stmt->fetch_assoc();
    
    if ($program) {
        $testStmt = $conn->prepare("INSERT INTO training_program_status_logs (program_id, old_status, new_status, reason) VALUES (?, ?, ?, NULLIF(?, ''))");
        $testReason = 'Test log entry';
        $testStmt->bind_param('isss', $program['id'], $program['status'], 'Test Status', $testReason);
        $testStmt->execute();
        
        $conn->rollback(); // Rollback the test
        echo "✓ Test log insertion successful\n";
    } else {
        echo "! No training programs found to test with\n";
    }
} catch (Exception $e) {
    $conn->rollback();
    echo "✗ Test log insertion failed: " . $e->getMessage() . "\n";
}

// Test 4: Check current programs
try {
    $result = $conn->query("SELECT id, training_title, status FROM training_programs WHERE status IN ('Under Review', 'Pending') ORDER BY created_at DESC LIMIT 5");
    $programs = $result->fetch_all(MYSQLI_ASSOC);
    echo "\nCurrent programs needing review:\n";
    foreach ($programs as $program) {
        echo "- ID: {$program['id']}, Title: {$program['training_title']}, Status: {$program['status']}\n";
    }
} catch (Exception $e) {
    echo "✗ Query failed: " . $e->getMessage() . "\n";
}

echo "\nDebug complete.\n";
?>
