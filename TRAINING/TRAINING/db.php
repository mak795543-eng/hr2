<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$dbPrefix = getenv('DB_PREFIX') ?: '';
$DB_HOST = getenv('TRAINING_DB_HOST') ?: (getenv('DB_HOST') ?: '127.0.0.1');
$DB_USER = getenv('TRAINING_DB_USER') ?: (getenv('DB_USER') ?: 'root');
$DB_PASS_ENV = getenv('TRAINING_DB_PASS');
$DB_PASS_GLOBAL = getenv('DB_PASS');
$DB_PASS = $DB_PASS_ENV !== false
    ? $DB_PASS_ENV
    : ($DB_PASS_GLOBAL !== false
        ? $DB_PASS_GLOBAL
        : (($DB_USER === 'root' && ($DB_HOST === 'localhost' || $DB_HOST === '127.0.0.1')) ? '' : 'makmak01'));
$DB_NAME = getenv('TRAINING_DB_NAME') ?: ($dbPrefix !== '' ? ($dbPrefix . 'schema_training_request') : 'schema_training_request');

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
$conn->set_charset('utf8mb4');
