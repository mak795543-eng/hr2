<?php
// main_connection.php

$dbHost = "127.0.0.1";
$dbUser = "root";       // or "hr2_usm" if you have that MySQL user
$dbPass = "";           // add your password if required

// ✅ List only the databases you want to connect to
$targetDatabases = [
    "hr2_soliera_usm",
    "hr2_succession",
    "hr2_soliera_usm",
    "hr2_training",
    "hr2_usm",
    "learning"
   
];

$connections = [];
$errors = [];

foreach ($targetDatabases as $dbName) {
    $conn = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

    if ($conn) {
        $connections[$dbName] = $conn;
    } else {
        $errors[] = "❌ Failed to connect to <strong>$dbName</strong>: " . mysqli_connect_error();
    }
}

// Optional: Show connection errors (for debugging only)
if (!empty($errors)) {
    echo "<h2 style='color:red;'>❌ Connection Errors:</h2><ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
}
?>
