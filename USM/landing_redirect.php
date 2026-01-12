<?php
session_start();

function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die;
}

// Check if user is logged in
if (!isset($_SESSION['role'])) {
    header("Location: login_main.php");
    exit;
}

// Normalize role key (convert to lowercase and replace spaces with underscores)
$session_role = strtolower(str_replace(' ', '_', $_SESSION['role']));
$permissions = include 'role_permissions.php';
$allowed_modules = $permissions[$session_role] ?? [];

// Debugging (uncomment when needed)
// dd([
//     'session_role' => $_SESSION['Role'],
//     'normalized_role' => $session_role,
//     'allowed_modules' => $allowed_modules
// ]);

// Mapping of modules to their landing pages
$module_to_landing = [

    // CORE 2 MODULES
    'dashboard' => '../ui.php',
    'Exam' => '../Training/learning.php',
    
    
];

// Find the first allowed module with a defined landing page
foreach ($allowed_modules as $module) {
    if (isset($module_to_landing[$module])) {
        header("Location: " . $module_to_landing[$module]);
        exit;
    }
}


// Fallback for all other cases
header("Location: ../index.php");
exit;