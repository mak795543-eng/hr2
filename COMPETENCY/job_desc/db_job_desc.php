<?php
function dd($data)
{
    echo "<pre>";
    echo var_dump($data);
    echo "</pre>";
}
function job_desc_mysqli(): mysqli
{
    $dbPrefix = getenv('DB_PREFIX') ?: '';
    $host = getenv('JOB_DESC_DB_HOST') ?: (getenv('DB_HOST') ?: 'localhost');
    $username = getenv('JOB_DESC_DB_USER') ?: (getenv('DB_USER') ?: 'root');
    $passwordEnv = getenv('JOB_DESC_DB_PASS');
    $passwordGlobal = getenv('DB_PASS');
    $password = $passwordEnv !== false
        ? $passwordEnv
        : ($passwordGlobal !== false
            ? $passwordGlobal
            : (($username === 'root' && ($host === 'localhost' || $host === '127.0.0.1')) ? '' : 'makmak01'));
    $database = getenv('JOB_DESC_DB_NAME') ?: ($dbPrefix !== '' ? ($dbPrefix . 'job_desc') : 'job_desc');

    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
        throw new RuntimeException("Database connection failed: " . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}
