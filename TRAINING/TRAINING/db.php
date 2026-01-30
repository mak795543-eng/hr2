<?php
declare(strict_types=1);

function training_db_connect(string $dbName = 'hr2_schema_training_request'): mysqli
{
    static $pool = [];

    $dbPrefix = getenv('DB_PREFIX') ?: '';
    $host = getenv('TRAINING_DB_HOST') ?: (getenv('DB_HOST') ?: 'localhost');
    $user = getenv('TRAINING_DB_USER') ?: (getenv('DB_USER') ?: 'hr2_schema_training_request');
    $passEnv = getenv('TRAINING_DB_PASS');
    $passGlobal = getenv('DB_PASS');
    $passPassword = getenv('DB_PASSWORD');

    $pass = 'hr2.soliera';
    $pass = $passEnv !== false
        ? $passEnv
        : ($passPassword !== false
            ? $passPassword
            : ($passGlobal !== false
                ? $passGlobal
                : (($user === 'root' && ($host === 'localhost' || $host === '127.0.0.1')) ? '' : 'hr2.soliera')));

    $envDbName = getenv('TRAINING_DB_NAME');
    if ($envDbName !== false) {
        $dbName = $envDbName;
    } elseif ($dbPrefix !== '' && strpos($dbName, $dbPrefix) !== 0) {
        $dbName = $dbPrefix . $dbName;
    }

    if (isset($pool[$dbName]) && $pool[$dbName] instanceof mysqli) {
        return $pool[$dbName];
    }

    $conn = new mysqli($host, $user, $pass, $dbName);

    if (!$conn->connect_error) {
        $conn->set_charset('utf8mb4');
    }

    $pool[$dbName] = $conn;
    return $conn;
}

$dbPrefix = getenv('DB_PREFIX') ?: '';

$TRAINING_DB_NAME = getenv('TRAINING_DB_NAME') ?: ($dbPrefix !== '' ? ($dbPrefix . 'hr2_schema_training_request') : 'hr2_schema_training_request');
$REQUESTS_DB_NAME = getenv('TRAINING_REQUESTS_DB_NAME') ?: ($dbPrefix !== '' ? ($dbPrefix . 'hr2_training_requests') : 'hr2_training_requests');

if (!preg_match('/^[A-Za-z0-9_]+$/', $TRAINING_DB_NAME)) {
    $TRAINING_DB_NAME = $dbPrefix !== '' ? ($dbPrefix . 'hr2_schema_training_request') : 'hr2_schema_training_request';
}
if (!preg_match('/^[A-Za-z0-9_]+$/', $REQUESTS_DB_NAME)) {
    $REQUESTS_DB_NAME = $TRAINING_DB_NAME;
}

$conn = training_db_connect($TRAINING_DB_NAME);

try {
    if ($REQUESTS_DB_NAME !== '' && $REQUESTS_DB_NAME !== $TRAINING_DB_NAME) {
        $conn->query("CREATE DATABASE IF NOT EXISTS `{$REQUESTS_DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    }
} catch (Throwable $e) {
}
