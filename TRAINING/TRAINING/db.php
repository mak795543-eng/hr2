<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$dbPrefix = getenv('DB_PREFIX') ?: '';
$DB_HOST = getenv('TRAINING_DB_HOST') ?: (getenv('DB_HOST') ?: '127.0.0.1');
$DB_USER = getenv('TRAINING_DB_USER') ?: (getenv('DB_USER') ?: 'hr2_schema_training_request');
$DB_PASS_ENV = getenv('TRAINING_DB_PASS');
$DB_PASS_GLOBAL = getenv('DB_PASS');
$PASS = 'hr2.soliera';
$DB_NAME = getenv('TRAINING_DB_NAME') ?: ($dbPrefix !== '' ? ($dbPrefix . 'hr2_schema_training_request') : 'hr2_schema_training_request');

$conn = new mysqli($DB_HOST, $DB_USER, $PASS, $DB_NAME);
$conn->set_charset('utf8mb4');
