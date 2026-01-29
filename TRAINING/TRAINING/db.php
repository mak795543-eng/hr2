<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$dbPrefix = getenv('DB_PREFIX') ?: '';
$DB_HOST = getenv('TRAINING_DB_HOST') ?: (getenv('DB_HOST') ?: '127.0.0.1');
$DB_USER = getenv('TRAINING_DB_USER') ?: (getenv('DB_USER') ?: 'hr2_usm');
$DB_PASS_ENV = getenv('TRAINING_DB_PASS');
$DB_PASS_GLOBAL = getenv('DB_PASS');
$DB_PASS_PASSWORD = getenv('DB_PASSWORD');
$PASS = $DB_PASS_ENV !== false
    ? $DB_PASS_ENV
    : ($DB_PASS_PASSWORD !== false
        ? $DB_PASS_PASSWORD
        : ($DB_PASS_GLOBAL !== false
            ? $DB_PASS_GLOBAL
            : (($DB_USER === 'root' && ($DB_HOST === 'localhost' || $DB_HOST === '127.0.0.1')) ? '' : 'hr2.soliera')));
$DB_NAME = getenv('TRAINING_DB_NAME') ?: ($dbPrefix !== '' ? ($dbPrefix . 'hr2_schema_training_request') : 'hr2_schema_training_request');

$TRAINING_DB_NAME = $DB_NAME;
$REQUESTS_DB_NAME = getenv('TRAINING_REQUESTS_DB_NAME') ?: ($dbPrefix !== '' ? ($dbPrefix . 'hr2_training_requests') : 'hr2_training_requests');
if (!preg_match('/^[A-Za-z0-9_]+$/', $TRAINING_DB_NAME)) {
    $TRAINING_DB_NAME = $DB_NAME;
}
if (!preg_match('/^[A-Za-z0-9_]+$/', $REQUESTS_DB_NAME)) {
    $REQUESTS_DB_NAME = $TRAINING_DB_NAME;
}

$conn = new mysqli($DB_HOST, $DB_USER, $PASS, $DB_NAME);
$conn->set_charset('utf8mb4');

try {
    if ($REQUESTS_DB_NAME !== '' && $REQUESTS_DB_NAME !== $TRAINING_DB_NAME) {
        $conn->query("CREATE DATABASE IF NOT EXISTS `{$REQUESTS_DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    }
} catch (Throwable $e) {
}
?>