<?php

declare(strict_types=1);

function usm_db_connect(string $dbName = 'learning_db'): mysqli {
    static $pool = [];

    if (isset($pool[$dbName]) && $pool[$dbName] instanceof mysqli) {
        return $pool[$dbName];
    }

    $dbPrefix = getenv('DB_PREFIX') ?: '';
    $host = getenv('LEARNING_DB_HOST') ?: (getenv('DB_HOST') ?: 'localhost');
    $user = getenv('LEARNING_DB_USER') ?: (getenv('DB_USER') ?: 'root');
    $passEnv = getenv('LEARNING_DB_PASS');
    $passGlobal = getenv('DB_PASS');
    $pass = $passEnv !== false
        ? $passEnv
        : ($passGlobal !== false
            ? $passGlobal
            : (($user === 'root' && ($host === 'localhost' || $host === '127.0.0.1')) ? '' : 'makmak01'));

    $envDbName = getenv('LEARNING_DB_NAME');
    if ($envDbName !== false) {
        $dbName = $envDbName;
    } elseif ($dbPrefix !== '' && strpos($dbName, $dbPrefix) !== 0) {
        $dbName = $dbPrefix . $dbName;
    }

    $conn = new mysqli($host, $user, $pass, $dbName);

    if (!$conn->connect_error) {
        $conn->set_charset('utf8mb4');
    }

    $pool[$dbName] = $conn;
    return $conn;
}
