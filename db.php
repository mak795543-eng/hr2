<?php
// main_connection.php

$dbHost = getenv('DB_HOST') ?: "localhost";
$dbUser = getenv('DB_USER') ?: "hr2_usm";       // or "hr2_usm" if you have that MySQL user
$dbPassEnv = getenv('DB_PASSWORD') ?? 'hr2.soliera';
$dbPass = $dbPassEnv !== false
    ? $dbPassEnv
    : (($dbUser === 'root' && ($dbHost === 'localhost' || $dbHost === 'localhost')) ? '' : 'hr2.soliera');
$dbPrefix = getenv('DB_PREFIX') ?: '';
$dbName = "hr2_usm";

$getDbEnv = function (string $dbName, string $suffix) use ($dbPrefix) {
    $envKeyDbName = $dbName;
    if ($dbPrefix !== '' && strpos($envKeyDbName, $dbPrefix) === 0) {
        $envKeyDbName = substr($envKeyDbName, strlen($dbPrefix));
    }
    $key = strtoupper(preg_replace('/[^A-Z0-9]+/', '_', $envKeyDbName));
    $v = getenv("DB_{$suffix}_{$key}");
    if ($v !== false) {
        return $v;
    }
    $v = getenv("{$key}_DB_{$suffix}");
    if ($v !== false) {
        return $v;
    }
    return false;
};

// $resolvePass = function (string $host, string $user, $passEnvValue) {
//     if ($passEnvValue !== false) {
//         return $passEnvValue;
//     }
//     $global = getenv('DB_PASS');
//     if ($global !== false) {
//         return $global;
//     }
//     return (($user === 'root' && ($host === 'localhost' || $host === '127.0.0.1')) ? '' : 'makmak01');
// };

// ✅ List only the databases you want to connect to
if (!isset($targetDatabases) || !is_array($targetDatabases)) {
    $targetDatabases = [
        "hr2_usm",
    ];
}

$connections = [];
$errors = [];

foreach ($targetDatabases as $dbName) {
    $perHost = $getDbEnv($dbName, 'HOST');
    $perUser = $getDbEnv($dbName, 'USER');
    $perPass = $getDbEnv($dbName, 'PASS');
    $perName = $getDbEnv($dbName, 'NAME');

    $connectHost = $perHost !== false ? $perHost : $dbHost;
    $connectUser = $perUser !== false ? $perUser : $dbUser;
    $connectPass = $perPass !== false ? $perPass : $dbPass;
    $connectDbName = $perName !== false
        ? $perName
        : ($dbPrefix !== '' && strpos($dbName, $dbPrefix) !== 0 ? ($dbPrefix . $dbName) : $dbName);

    $conn = @mysqli_connect($connectHost, $connectUser, $connectPass, $connectDbName);

    if ($conn) {
        $connections[$dbName] = $conn;
    } else {
        $errors[] = "❌ Failed to connect to <strong>$connectDbName</strong>: " . mysqli_connect_error();
    }
}

// Optional: Show connection errors (for debugging only)
if (!empty($errors) && !defined('SUPPRESS_DB_ERRORS')) {
    echo "<h2 style='color:red;'>❌ Connection Errors:</h2><ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
}
