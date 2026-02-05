<?php

// data_fetcher.php

// Initialize variables to avoid undefined errors
$modules = [];
$examinations = [];

// Fetch learning modules data
$modules_sql = "SELECT * FROM learning_modules WHERE status = 'pending'";
if ($selected_department !== 'all') {
    $modules_sql .= " AND department = ?";
}

$modules_stmt = $conn->prepare($modules_sql);
if ($selected_department !== 'all') {
    $modules_stmt->bind_param("s", $selected_department);
}
$modules_stmt->execute();
$modules_result = $modules_stmt->get_result();
$modules = $modules_result->fetch_all(MYSQLI_ASSOC);
$modules_stmt->close();

// Fetch examinations data
$examinations_sql = "SELECT * FROM examinations WHERE status = 'pending'";
if ($selected_exam_department !== 'all') {
    $examinations_sql .= " AND department = ?";
}

$examinations_stmt = $conn->prepare($examinations_sql);
if ($selected_exam_department !== 'all') {
    $examinations_stmt->bind_param("s", $selected_exam_department);
}
$examinations_stmt->execute();
$examinations_result = $examinations_stmt->get_result();
$examinations = $examinations_result->fetch_all(MYSQLI_ASSOC);
$examinations_stmt->close();



// Get unique departments for modules filter
$departments_sql = "SELECT DISTINCT department FROM learning_modules WHERE status = 'pending' ORDER BY department";
$departments_result = $conn->query($departments_sql);
$departments = [];
if ($departments_result && $departments_result->num_rows > 0) {
    while($row = $departments_result->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

// Fetch examinations data
if ($selected_exam_department === 'all') {
    $exam_sql = "SELECT * FROM examinations WHERE status = 'pending' ORDER BY created_at DESC";
    $exam_stmt = $conn->prepare($exam_sql);
} else {
    $exam_sql = "SELECT * FROM examinations WHERE status = 'pending' AND department = ? ORDER BY created_at DESC";
    $exam_stmt = $conn->prepare($exam_sql);
    $exam_stmt->bind_param("s", $selected_exam_department);
}

$pending_examinations = [];
if ($exam_stmt->execute()) {
    $exam_result = $exam_stmt->get_result();
    if ($exam_result && $exam_result->num_rows > 0) {
        while($row = $exam_result->fetch_assoc()) {
            $pending_examinations[] = $row;
        }
    }
}
$exam_stmt->close();

// Get unique departments for examinations filter
$exam_departments_sql = "SELECT DISTINCT department FROM examinations WHERE status = 'pending' ORDER BY department";
$exam_departments_result = $conn->query($exam_departments_sql);
$exam_departments = [];
if ($exam_departments_result && $exam_departments_result->num_rows > 0) {
    while($row = $exam_departments_result->fetch_assoc()) {
        $exam_departments[] = $row['department'];
    }
}

$conn->close();
?>