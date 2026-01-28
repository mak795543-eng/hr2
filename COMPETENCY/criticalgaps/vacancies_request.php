<?php
 
 if (($_GET['action'] ?? '') === 'fetch_vacancies') {
     header('Content-Type: application/json; charset=utf-8');
 
     $url = 'https://hr4.soliera-hotel-restaurant.com/CHM/API/save_employee.php';
     $raw = null;
 
     if (function_exists('curl_init')) {
         $ch = curl_init($url);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
         curl_setopt($ch, CURLOPT_TIMEOUT, 15);
         $raw = curl_exec($ch);
         $err = curl_error($ch);
         $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
         curl_close($ch);
 
         if ($raw === false || $status >= 400) {
             echo json_encode([
                 'success' => false,
                 'message' => 'Failed to fetch vacancies from remote API',
                 'status' => $status,
                 'error' => $err,
             ]);
             exit;
         }
     } else {
         $context = stream_context_create([
             'http' => [
                 'method' => 'GET',
                 'timeout' => 15,
                 'header' => "Accept: application/json\r\n",
             ],
         ]);
         $raw = @file_get_contents($url, false, $context);
         if ($raw === false) {
             echo json_encode([
                 'success' => false,
                 'message' => 'Failed to fetch vacancies from remote API',
             ]);
             exit;
         }
     }
 
     $decoded = json_decode($raw, true);
     if (!is_array($decoded)) {
         echo json_encode([
             'success' => false,
             'message' => 'Remote API returned invalid JSON',
             'raw' => substr((string)$raw, 0, 500),
         ]);
         exit;
     }
 
     $mapped = [];
     foreach ($decoded as $key => $row) {
         if (!is_array($row)) {
             continue;
         }
         $id = (string)($row['id'] ?? $key);
         if ($id === '') {
             continue;
         }
         $row['request_id'] = $id;
         $mapped[$id] = $row;
     }
 
     echo json_encode([
         'success' => true,
         'data' => $mapped,
     ]);
     exit;
 }

 if (($_GET['action'] ?? '') === 'fetch_job_details') {
     header('Content-Type: application/json; charset=utf-8');

     $requestId = trim((string)($_GET['request_id'] ?? ''));
     if ($requestId === '') {
         echo json_encode(['success' => false, 'message' => 'Missing request_id']);
         exit;
     }

     $requestIdInt = ctype_digit($requestId) ? (int)$requestId : null;

     try {
         require_once __DIR__ . '/../job_desc/db_job_desc.php';
         $conn = job_desc_mysqli();

         $tableExists = function (mysqli $c, string $table): bool {
             $t = $c->real_escape_string($table);
             $res = $c->query("SHOW TABLES LIKE '{$t}'");
             if (!$res) {
                 return false;
             }
             $exists = $res->num_rows > 0;
             $res->free();
             return $exists;
         };

         $data = [
             'description' => '',
             'qualifications' => [],
             'requirements' => [],
         ];

         if ($tableExists($conn, 'job_description')) {
             $res = $conn->query("SHOW COLUMNS FROM job_description LIKE 'description'");
             $descField = ($res && $res->num_rows > 0) ? 'description' : 'job_description';
             if ($res) { $res->free(); }

             if ($requestIdInt !== null) {
                 $stmt = $conn->prepare("SELECT `{$descField}` FROM job_description WHERE request_id = ? ORDER BY created_at DESC, ID DESC LIMIT 1");
                 if (!$stmt) throw new RuntimeException($conn->error);
                 $stmt->bind_param('i', $requestIdInt);
             } else {
                 $stmt = $conn->prepare("SELECT `{$descField}` FROM job_description WHERE request_id = ? ORDER BY created_at DESC LIMIT 1");
                 if (!$stmt) throw new RuntimeException($conn->error);
                 $stmt->bind_param('s', $requestId);
             }
             $stmt->execute();
             $r = $stmt->get_result();
             $row = $r ? $r->fetch_assoc() : null;
             $stmt->close();
             if ($row) {
                 $data['description'] = (string)($row[$descField] ?? '');
             }
         }

         if ($tableExists($conn, 'qualifications')) {
             $stmt = $conn->prepare('SELECT qualification FROM qualifications WHERE request_id = ? ORDER BY created_at DESC, id DESC');
             if (!$stmt) throw new RuntimeException($conn->error);
             $stmt->bind_param('s', $requestId);
             $stmt->execute();
             $r = $stmt->get_result();
             if ($r) {
                 while ($row = $r->fetch_assoc()) {
                     $q = trim((string)($row['qualification'] ?? ''));
                     if ($q === '') continue;
                     $name = $q;
                     $desc = '';
                     if (strpos($q, '|') !== false) {
                         $parts = explode('|', $q, 2);
                         $name = trim((string)($parts[0] ?? ''));
                         $desc = trim((string)($parts[1] ?? ''));
                     }
                     if ($name === '') continue;
                     $data['qualifications'][] = [
                         'name' => $name,
                         'description' => $desc,
                     ];
                 }
             }
             $stmt->close();
         }

         if ($tableExists($conn, 'requirements')) {
             if ($requestIdInt !== null) {
                 $stmt = $conn->prepare('SELECT name, description FROM requirements WHERE request_id = ? ORDER BY created_at DESC, ID DESC');
                 if (!$stmt) throw new RuntimeException($conn->error);
                 $stmt->bind_param('i', $requestIdInt);
             } else {
                 $stmt = $conn->prepare('SELECT name, description FROM requirements WHERE request_id = ? ORDER BY created_at DESC');
                 if (!$stmt) throw new RuntimeException($conn->error);
                 $stmt->bind_param('s', $requestId);
             }
             $stmt->execute();
             $r = $stmt->get_result();
             if ($r) {
                 while ($row = $r->fetch_assoc()) {
                     $name = trim((string)($row['name'] ?? ''));
                     $desc = isset($row['description']) ? trim((string)$row['description']) : '';
                     if ($name === '') continue;
                     $data['requirements'][] = [
                         'name' => $name,
                         'description' => $desc,
                     ];
                 }
             }
             $stmt->close();
         }

         $conn->close();

         echo json_encode(['success' => true, 'data' => $data]);
         exit;
     } catch (Throwable $e) {
         if (isset($conn) && $conn instanceof mysqli) {
             try { $conn->close(); } catch (Throwable $t) {}
         }
         echo json_encode(['success' => false, 'message' => $e->getMessage()]);
         exit;
     }
 }

 if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'save_job_details') {
     header('Content-Type: application/json; charset=utf-8');

     $requestId = trim((string)($_POST['request_id'] ?? ''));
     $jobTitle = trim((string)($_POST['job_title'] ?? ''));
     $jobDescription = trim((string)($_POST['job_description'] ?? ''));
     $requestIdInt = ctype_digit($requestId) ? (int)$requestId : null;

     $parseList = function ($value) use (&$parseList): array {
         if (is_array($value)) {
             $out = [];
             foreach ($value as $v) {
                 $t = trim((string)$v);
                 if ($t !== '') {
                     $out[] = $t;
                 }
             }
             return $out;
         }

         $raw = trim((string)$value);
         if ($raw === '') {
             return [];
         }

         if ($raw[0] === '[') {
             $decoded = json_decode($raw, true);
             if (is_array($decoded)) {
                 return $parseList($decoded);
             }
         }

         $lines = preg_split('/\r\n|\r|\n/', $raw);
         $out = [];
         foreach ($lines as $line) {
             $t = trim((string)$line);
             if ($t !== '') {
                 $out[] = $t;
             }
         }
         return $out;
     };

     $qualifications = $parseList($_POST['qualifications'] ?? '');
     $requirements = $parseList($_POST['requirements'] ?? '');

     $qualItems = [];
     if (isset($_POST['qualifications_name']) || isset($_POST['qualifications_description'])) {
         $names = $_POST['qualifications_name'] ?? [];
         $descs = $_POST['qualifications_description'] ?? [];
         if (!is_array($names)) $names = [$names];
         if (!is_array($descs)) $descs = [$descs];
         $max = max(count($names), count($descs));
         for ($i = 0; $i < $max; $i++) {
             $n = trim((string)($names[$i] ?? ''));
             $d = trim((string)($descs[$i] ?? ''));
             if ($n === '' && $d === '') continue;
             if ($n === '') $n = $d;
             $qualItems[] = [
                 'name' => $n,
                 'description' => $d,
             ];
         }
     } else {
         foreach ($qualifications as $q) {
             $name = $q;
             $desc = '';
             if (strpos($q, '|') !== false) {
                 $parts = explode('|', $q, 2);
                 $name = trim((string)($parts[0] ?? ''));
                 $desc = trim((string)($parts[1] ?? ''));
             }
             $name = trim((string)$name);
             if ($name === '') continue;
             $qualItems[] = [
                 'name' => $name,
                 'description' => $desc,
             ];
         }
     }

     $reqItems = [];
     if (isset($_POST['requirements_name']) || isset($_POST['requirements_description'])) {
         $names = $_POST['requirements_name'] ?? [];
         $descs = $_POST['requirements_description'] ?? [];
         if (!is_array($names)) $names = [$names];
         if (!is_array($descs)) $descs = [$descs];
         $max = max(count($names), count($descs));
         for ($i = 0; $i < $max; $i++) {
             $n = trim((string)($names[$i] ?? ''));
             $d = trim((string)($descs[$i] ?? ''));
             if ($n === '' && $d === '') continue;
             if ($n === '') $n = $d;
             $reqItems[] = [
                 'name' => $n,
                 'description' => ($d === '' ? null : $d),
             ];
         }
     } else {
         foreach ($requirements as $r) {
             $name = $r;
             $desc = null;
             if (strpos($r, '|') !== false) {
                 $parts = explode('|', $r, 2);
                 $name = trim((string)($parts[0] ?? ''));
                 $desc = trim((string)($parts[1] ?? ''));
                 if ($desc === '') $desc = null;
             }
             $name = trim((string)$name);
             if ($name === '') continue;
             $reqItems[] = [
                 'name' => $name,
                 'description' => $desc,
             ];
         }
     }

     if ($requestId === '') {
         echo json_encode(['success' => false, 'message' => 'Missing request_id']);
         exit;
     }
     if ($jobDescription === '') {
         echo json_encode(['success' => false, 'message' => 'Job description is required']);
         exit;
     }

     try {
         require_once __DIR__ . '/../job_desc/db_job_desc.php';
         $conn = job_desc_mysqli();
         $conn->begin_transaction();

         $debug = [
             'db' => null,
             'job_vacancy_inserted' => 0,
             'job_description_inserts' => 0,
             'qualifications_inserts' => 0,
             'requirements_inserts' => 0,
         ];

         $dbRes = $conn->query('SELECT DATABASE() AS db');
         if ($dbRes) {
             $dbRow = $dbRes->fetch_assoc();
             $debug['db'] = $dbRow['db'] ?? null;
             $dbRes->free();
         }

         $exec = function (mysqli_stmt $stmt) {
             $ok = $stmt->execute();
             if (!$ok) {
                 throw new RuntimeException($stmt->error ?: 'SQL execute failed');
             }
         };

         $tableExists = function (mysqli $c, string $table): bool {
             $t = $c->real_escape_string($table);
             $res = $c->query("SHOW TABLES LIKE '{$t}'");
             if (!$res) {
                 return false;
             }
             $exists = $res->num_rows > 0;
             $res->free();
             return $exists;
         };

         $getColumns = function (mysqli $c, string $table): array {
             $cols = [];
             $res = $c->query("SHOW COLUMNS FROM `{$table}`");
             if (!$res) {
                 return $cols;
             }
             while ($row = $res->fetch_assoc()) {
                 $field = (string)($row['Field'] ?? '');
                 if ($field === '') continue;
                 $cols[$field] = $row;
             }
             $res->free();
             return $cols;
         };

         $isAutoIncrement = function (array $cols, string $field): bool {
             if (!isset($cols[$field])) return false;
             $extra = (string)($cols[$field]['Extra'] ?? '');
             return stripos($extra, 'auto_increment') !== false;
         };

         $nextId = function (mysqli $c, string $table, string $idField): int {
             $res = $c->query("SELECT IFNULL(MAX(`{$idField}`), 0) + 1 AS next_id FROM `{$table}`");
             if (!$res) {
                 return 1;
             }
             $row = $res->fetch_assoc();
             $res->free();
             return (int)($row['next_id'] ?? 1);
         };

         $colDefaultValue = function (array $col) {
             $nullAllowed = ((string)($col['Null'] ?? '')) === 'YES';
             $default = $col['Default'] ?? null;
             if ($default !== null) {
                 return $default;
             }
             if ($nullAllowed) {
                 return null;
             }
             $type = strtolower((string)($col['Type'] ?? ''));
             if (preg_match('/^(tinyint|smallint|mediumint|int|bigint)/', $type)) {
                 return 0;
             }
             if (preg_match('/^(decimal|float|double)/', $type)) {
                 return 0;
             }
             if (preg_match('/^enum\((.*)\)$/', $type, $m)) {
                 $vals = explode(',', $m[1]);
                 $first = trim((string)($vals[0] ?? ''), " '\"");
                 return $first;
             }
             if (strpos($type, 'date') !== false && strpos($type, 'time') === false) {
                 return date('Y-m-d');
             }
             if (strpos($type, 'datetime') !== false || strpos($type, 'timestamp') !== false) {
                 return date('Y-m-d H:i:s');
             }
             return '';
         };

         $paramTypeFor = function (array $col): string {
             $type = strtolower((string)($col['Type'] ?? ''));
             if (preg_match('/^(tinyint|smallint|mediumint|int|bigint)/', $type)) {
                 return 'i';
             }
             if (preg_match('/^(decimal|float|double)/', $type)) {
                 return 'd';
             }
             return 's';
         };

         if ($tableExists($conn, 'job_vacancy') && $requestIdInt !== null) {
             $jobVacCols = $getColumns($conn, 'job_vacancy');
             $idField = isset($jobVacCols['ID']) ? 'ID' : (isset($jobVacCols['id']) ? 'id' : 'ID');

             $stmtChk = $conn->prepare("SELECT 1 FROM job_vacancy WHERE `{$idField}` = ? LIMIT 1");
             if (!$stmtChk) throw new RuntimeException($conn->error);
             $stmtChk->bind_param('i', $requestIdInt);
             $exec($stmtChk);
             $chkRes = $stmtChk->get_result();
             $exists = $chkRes && $chkRes->num_rows > 0;
             $stmtChk->close();

             if (!$exists) {
                 $posted = [
                     'department_id' => $_POST['department_id'] ?? null,
                     'sub_department_id' => $_POST['sub_department_id'] ?? null,
                     'title' => $_POST['job_title'] ?? ($_POST['title'] ?? null),
                     'type' => $_POST['type'] ?? null,
                     'status' => $_POST['status'] ?? null,
                     'vacancies' => $_POST['vacancies'] ?? null,
                     'exam_required' => $_POST['exam_required'] ?? null,
                     'salary_min' => $_POST['salary_min'] ?? null,
                     'salary_max' => $_POST['salary_max'] ?? null,
                     'job_period_days' => $_POST['job_period_days'] ?? null,
                     'job_end_date' => $_POST['job_end_date'] ?? null,
                 ];

                 $cols = [];
                 $types = '';
                 $values = [];

                 foreach ($jobVacCols as $field => $meta) {
                     if ($field === $idField) {
                         $cols[] = "`{$field}`";
                         $types .= 'i';
                         $values[] = $requestIdInt;
                         continue;
                     }

                     if (stripos((string)($meta['Extra'] ?? ''), 'auto_increment') !== false) {
                         continue;
                     }

                     $hasUserValue = array_key_exists($field, $posted) && $posted[$field] !== null && $posted[$field] !== '';
                     if ($hasUserValue) {
                         $val = $posted[$field];
                         $cols[] = "`{$field}`";
                         $types .= $paramTypeFor($meta);
                         $values[] = $val;
                         continue;
                     }

                     $hasDefault = array_key_exists('Default', $meta) && $meta['Default'] !== null;
                     $nullAllowed = ((string)($meta['Null'] ?? '')) === 'YES';
                     if ($hasDefault || $nullAllowed) {
                         // Let MySQL apply DEFAULT / NULL
                         continue;
                     }

                     // Required column with no default: provide a safe placeholder
                     $val = $colDefaultValue($meta);
                     $cols[] = "`{$field}`";
                     $types .= $paramTypeFor($meta);
                     $values[] = $val;
                 }

                 $placeholders = implode(',', array_fill(0, count($cols), '?'));
                 $colList = implode(',', $cols);
                 $sql = "INSERT INTO job_vacancy ({$colList}) VALUES ({$placeholders})";
                 $stmtIns = $conn->prepare($sql);
                 if (!$stmtIns) {
                     throw new RuntimeException($conn->error);
                 }
                 $stmtIns->bind_param($types, ...$values);
                 $exec($stmtIns);
                 $stmtIns->close();
                 $debug['job_vacancy_inserted'] = 1;
             }
         }

         if ($tableExists($conn, 'job_description')) {
             $jobDescCols = $getColumns($conn, 'job_description');
             $descField = isset($jobDescCols['description']) ? 'description' : (isset($jobDescCols['job_description']) ? 'job_description' : 'description');
             $idField = isset($jobDescCols['ID']) ? 'ID' : (isset($jobDescCols['id']) ? 'id' : 'ID');

             $stmtDel = $conn->prepare('DELETE FROM job_description WHERE request_id = ?');
             if (!$stmtDel) throw new RuntimeException($conn->error);
             if ($requestIdInt !== null) {
                 $stmtDel->bind_param('i', $requestIdInt);
             } else {
                 $stmtDel->bind_param('s', $requestId);
             }
             $exec($stmtDel);
             $stmtDel->close();

             if (isset($jobDescCols[$idField]) && !$isAutoIncrement($jobDescCols, $idField)) {
                 $newId = $nextId($conn, 'job_description', $idField);
                 $stmtIns = $conn->prepare("INSERT INTO job_description (`{$idField}`, `request_id`, `{$descField}`) VALUES (?, ?, ?)");
                 if (!$stmtIns) throw new RuntimeException($conn->error);
                 if ($requestIdInt !== null) {
                     $stmtIns->bind_param('iis', $newId, $requestIdInt, $jobDescription);
                 } else {
                     $stmtIns->bind_param('iss', $newId, $requestId, $jobDescription);
                 }
                 $exec($stmtIns);
                 $debug['job_description_inserts']++;
                 $stmtIns->close();
             } else {
                 $stmtIns = $conn->prepare("INSERT INTO job_description (`request_id`, `{$descField}`) VALUES (?, ?)");
                 if (!$stmtIns) throw new RuntimeException($conn->error);
                 if ($requestIdInt !== null) {
                     $stmtIns->bind_param('is', $requestIdInt, $jobDescription);
                 } else {
                     $stmtIns->bind_param('ss', $requestId, $jobDescription);
                 }
                 $exec($stmtIns);
                 $debug['job_description_inserts']++;
                 $stmtIns->close();
             }
         } elseif ($tableExists($conn, 'job_roles')) {
             $stmtUp = $conn->prepare('UPDATE job_roles SET description = ? WHERE request_id = ?');
             $stmtUp->bind_param('ss', $jobDescription, $requestId);
             $stmtUp->execute();
             $affected = $stmtUp->affected_rows;
             $stmtUp->close();

             if ($affected === 0 && $jobTitle !== '') {
                 $vacancies = 1;
                 $stmtInsRole = $conn->prepare('INSERT INTO job_roles (request_id, name, vacancies, description) VALUES (?, ?, ?, ?)');
                 $stmtInsRole->bind_param('ssis', $requestId, $jobTitle, $vacancies, $jobDescription);
                 $stmtInsRole->execute();
                 $stmtInsRole->close();
             }
         } else {
             throw new RuntimeException('Missing job_description/job_roles table in job_desc database');
         }

         if ($tableExists($conn, 'qualifications')) {
             $qualCols = $getColumns($conn, 'qualifications');
             $idField = isset($qualCols['ID']) ? 'ID' : (isset($qualCols['id']) ? 'id' : 'id');

             $stmtDel = $conn->prepare('DELETE FROM qualifications WHERE request_id = ?');
             if (!$stmtDel) throw new RuntimeException($conn->error);
             $stmtDel->bind_param('s', $requestId);
             $exec($stmtDel);
             $stmtDel->close();

             if (isset($qualCols[$idField]) && !$isAutoIncrement($qualCols, $idField)) {
                 $stmtIns = $conn->prepare("INSERT INTO qualifications (`{$idField}`, `request_id`, `qualification`) VALUES (?, ?, ?)");
                 if (!$stmtIns) throw new RuntimeException($conn->error);
                 foreach ($qualItems as $q) {
                     $newId = $nextId($conn, 'qualifications', $idField);
                     $text = trim((string)($q['name'] ?? ''));
                     $desc = trim((string)($q['description'] ?? ''));
                     if ($text === '') continue;
                     if ($desc !== '') $text .= ' | ' . $desc;
                     $stmtIns->bind_param('iss', $newId, $requestId, $text);
                     $exec($stmtIns);
                     $debug['qualifications_inserts']++;
                 }
                 $stmtIns->close();
             } else {
                 $stmtIns = $conn->prepare('INSERT INTO qualifications (request_id, qualification) VALUES (?, ?)');
                 if (!$stmtIns) throw new RuntimeException($conn->error);
                 foreach ($qualItems as $q) {
                     $text = trim((string)($q['name'] ?? ''));
                     $desc = trim((string)($q['description'] ?? ''));
                     if ($text === '') continue;
                     if ($desc !== '') $text .= ' | ' . $desc;
                     $stmtIns->bind_param('ss', $requestId, $text);
                     $exec($stmtIns);
                     $debug['qualifications_inserts']++;
                 }
                 $stmtIns->close();
             }
         } elseif ($tableExists($conn, 'qualificcaion')) {
             $stmtDel = $conn->prepare('DELETE FROM qualificcaion WHERE request_id = ?');
             $stmtDel->bind_param('s', $requestId);
             $stmtDel->execute();
             $stmtDel->close();

             $stmtIns = $conn->prepare('INSERT INTO qualificcaion (request_id, qualification) VALUES (?, ?)');
             if (!$stmtIns) throw new RuntimeException($conn->error);
             foreach ($qualItems as $q) {
                 $text = trim((string)($q['name'] ?? ''));
                 $desc = trim((string)($q['description'] ?? ''));
                 if ($text === '') continue;
                 if ($desc !== '') $text .= ' | ' . $desc;
                 $stmtIns->bind_param('ss', $requestId, $text);
                 $stmtIns->execute();
                 $debug['qualifications_inserts']++;
             }
             $stmtIns->close();
         } elseif ($tableExists($conn, 'qualifications')) {
             $stmtDel = $conn->prepare('DELETE FROM qualifications WHERE request_id = ?');
             $stmtDel->bind_param('s', $requestId);
             $stmtDel->execute();
             $stmtDel->close();

             $stmtIns = $conn->prepare('INSERT INTO qualifications (request_id, qualification, type, priority) VALUES (?, ?, ?, ?)');
             if (!$stmtIns) throw new RuntimeException($conn->error);
             $type = 'General';
             $priority = 1;
             foreach ($qualItems as $q) {
                 $text = trim((string)($q['name'] ?? ''));
                 $desc = trim((string)($q['description'] ?? ''));
                 if ($text === '') continue;
                 if ($desc !== '') $text .= ' | ' . $desc;
                 $stmtIns->bind_param('sssi', $requestId, $text, $type, $priority);
                 $stmtIns->execute();
                 $debug['qualifications_inserts']++;
                 $priority++;
             }
             $stmtIns->close();
         }

         if ($tableExists($conn, 'requirements')) {
             $reqCols = $getColumns($conn, 'requirements');
             $idField = isset($reqCols['ID']) ? 'ID' : (isset($reqCols['id']) ? 'id' : 'ID');

             $stmtDel = $conn->prepare('DELETE FROM requirements WHERE request_id = ?');
             if (!$stmtDel) throw new RuntimeException($conn->error);
             if ($requestIdInt !== null) {
                 $stmtDel->bind_param('i', $requestIdInt);
             } else {
                 $stmtDel->bind_param('s', $requestId);
             }
             $exec($stmtDel);
             $stmtDel->close();

             $hasName = isset($reqCols['name']);
             $hasDesc = isset($reqCols['description']);
             $hasRequirement = isset($reqCols['requirement']);

             if ($hasName) {
                 if (isset($reqCols[$idField]) && !$isAutoIncrement($reqCols, $idField)) {
                     $stmtIns = $conn->prepare("INSERT INTO requirements (`{$idField}`, request_id, name, description) VALUES (?, ?, ?, ?)");
                     if (!$stmtIns) throw new RuntimeException($conn->error);
                     foreach ($reqItems as $r) {
                         $name = trim((string)($r['name'] ?? ''));
                         $desc = $r['description'] ?? null;
                         if ($name === '') continue;
                         $newId = $nextId($conn, 'requirements', $idField);
                         if ($requestIdInt !== null) {
                             $stmtIns->bind_param('iiss', $newId, $requestIdInt, $name, $desc);
                         } else {
                             $stmtIns->bind_param('isss', $newId, $requestId, $name, $desc);
                         }
                         $exec($stmtIns);
                         $debug['requirements_inserts']++;
                     }
                     $stmtIns->close();
                 } else {
                     if ($hasDesc) {
                         $stmtIns = $conn->prepare('INSERT INTO requirements (request_id, name, description) VALUES (?, ?, ?)');
                         if (!$stmtIns) throw new RuntimeException($conn->error);
                         foreach ($reqItems as $r) {
                             $name = trim((string)($r['name'] ?? ''));
                             $desc = $r['description'] ?? null;
                             if ($name === '') continue;
                             if ($requestIdInt !== null) {
                                 $stmtIns->bind_param('iss', $requestIdInt, $name, $desc);
                             } else {
                                 $stmtIns->bind_param('sss', $requestId, $name, $desc);
                             }
                             $exec($stmtIns);
                             $debug['requirements_inserts']++;
                         }
                         $stmtIns->close();
                     } else {
                         $stmtIns = $conn->prepare('INSERT INTO requirements (request_id, name) VALUES (?, ?)');
                         if (!$stmtIns) throw new RuntimeException($conn->error);
                         foreach ($reqItems as $r) {
                             $name = trim((string)($r['name'] ?? ''));
                             if ($name === '') continue;
                             if ($requestIdInt !== null) {
                                 $stmtIns->bind_param('is', $requestIdInt, $name);
                             } else {
                                 $stmtIns->bind_param('ss', $requestId, $name);
                             }
                             $exec($stmtIns);
                             $debug['requirements_inserts']++;
                         }
                         $stmtIns->close();
                     }
                 }
             } elseif ($hasRequirement) {
                 $stmtIns = $conn->prepare('INSERT INTO requirements (request_id, requirement) VALUES (?, ?)');
                 if (!$stmtIns) throw new RuntimeException($conn->error);
                 foreach ($reqItems as $r) {
                     $text = trim((string)($r['name'] ?? ''));
                     $desc = trim((string)($r['description'] ?? ''));
                     if ($text === '') continue;
                     if ($desc !== '') $text .= ' | ' . $desc;
                     $stmtIns->bind_param('ss', $requestId, $text);
                     $exec($stmtIns);
                     $debug['requirements_inserts']++;
                 }
                 $stmtIns->close();
             }
         } elseif ($tableExists($conn, 'job_requirements')) {
             $stmtDel = $conn->prepare('DELETE FROM job_requirements WHERE request_id = ?');
             $stmtDel->bind_param('s', $requestId);
             $stmtDel->execute();
             $stmtDel->close();

             $stmtIns = $conn->prepare('INSERT INTO job_requirements (request_id, requirement, category, is_essential) VALUES (?, ?, ?, ?)');
             if (!$stmtIns) throw new RuntimeException($conn->error);
             $category = 'General';
             $essential = 1;
             foreach ($reqItems as $r) {
                 $text = trim((string)($r['name'] ?? ''));
                 $desc = trim((string)($r['description'] ?? ''));
                 if ($text === '') continue;
                 if ($desc !== '') $text .= ' | ' . $desc;
                 $stmtIns->bind_param('sssi', $requestId, $text, $category, $essential);
                 $stmtIns->execute();
                 $debug['requirements_inserts']++;
             }
             $stmtIns->close();
         }

         $conn->commit();
         $conn->close();

         echo json_encode(['success' => true, 'message' => 'Saved', 'debug' => $debug]);
         exit;
     } catch (Throwable $e) {
         if (isset($conn) && $conn instanceof mysqli) {
             try { $conn->rollback(); } catch (Throwable $t) {}
             try { $conn->close(); } catch (Throwable $t) {}
         }
         echo json_encode(['success' => false, 'message' => $e->getMessage()]);
         exit;
     }
 }

 require('../../partials/header.php');
 ?>
<body class="bg-base-100 min-h-screen bg-white">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include '../../USM/sidebarr.php'; ?>

        <!-- Content Area -->
        <div class="flex flex-col flex-1 overflow-auto">
            <!-- Navbar -->
            <?php include '../../USM/navbar.php'; ?>

            <!-- Main Content -->
            <main class="flex-1 overflow-auto p-4 md:p-6">
                <!-- Page Header -->
                <div class="mb-6">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Job Vacancies</h1>
                    <p class="text-gray-600 mt-2">View and manage all available job vacancies</p>
                </div>

                <!-- Filters and Actions -->
                <div class="bg-base-100 rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <!-- Search Bar -->
                        <div class="flex-1 w-full md:w-auto">
                            <div class="relative">
                                <input
                                    type="text"
                                    placeholder="Search vacancies..."
                                    class="input input-bordered w-full pl-10"
                                    id="searchInput">
                                <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                            </div>
                        </div>

                        <!-- Filter Buttons -->
                        <div class="flex flex-wrap gap-2">
                            <select class="select select-bordered select-sm">
                                <option value="">All Departments</option>
                                <option value="1">Human Resources</option>
                                <option value="2">IT</option>
                                <option value="3">Finance</option>
                            </select>

                            <select class="select select-bordered select-sm">
                                <option value="">All Types</option>
                                <option value="contract">Contract</option>
                                <option value="full-time">Full-time</option>
                                <option value="part-time">Part-time</option>
                            </select>

                            <select class="select select-bordered select-sm">
                                <option value="">All Status</option>
                                <option value="open">Open</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Vacancies Table -->
                <div class="bg-base-100 rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="table table-auto w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">
                                        <div class="flex items-center gap-2">
                                            <span>Job Title</span>
                                            <button class="btn btn-ghost btn-xs">
                                                <i data-lucide="arrow-up-down" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </th>
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">Department</th>
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">Type</th>
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">Vacancies</th>
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">Salary Range</th>
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">Status</th>
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">Days Remaining</th>
                                    <th class="text-left py-4 px-6 font-semibold text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="vacanciesTableBody">
                                <!-- Data will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Loading State -->
                    <div id="loadingState" class="p-8 text-center">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto"></div>
                        <p class="mt-4 text-gray-600">Loading vacancies...</p>
                    </div>

                    <!-- Empty State -->
                    <div id="emptyState" class="hidden p-8 text-center">
                        <i data-lucide="briefcase" class="w-16 h-16 text-gray-300 mx-auto"></i>
                        <p class="mt-4 text-gray-600">No vacancies found</p>
                    </div>

                    <!-- Pagination -->
                    <div class="border-t border-gray-200 p-4">
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                            <div class="text-sm text-gray-600">
                                Showing <span id="startCount">1</span> to <span id="endCount">10</span> of
                                <span id="totalCount">0</span> entries
                            </div>
                            <div class="join">
                                <button class="join-item btn btn-sm" id="prevPage">
                                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                                </button>
                                <button class="join-item btn btn-sm btn-active">1</button>
                                <button class="join-item btn btn-sm">2</button>
                                <button class="join-item btn btn-sm">3</button>
                                <button class="join-item btn btn-sm" id="nextPage">
                                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detail Modal -->
                <dialog id="detailModal" class="modal">
                    <div class="modal-box max-w-4xl">
                        <form method="dialog">
                            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </form>
                        <h3 class="font-bold text-lg mb-4">Vacancy Details</h3>
                        <div id="modalContent">
                            <!-- Details will be populated here -->
                        </div>

                        <div class="mt-6 pt-6 border-t border-gray-200" id="savedJobDetailsBlock">
                            <h4 class="font-semibold mb-3">Saved Job Details</h4>
                            <div class="text-sm text-gray-500">Loading...</div>
                        </div>
                    </div>
                    <form method="dialog" class="modal-backdrop">
                        <button>close</button>
                    </form>
                </dialog>

                <dialog id="jobDetailsModal" class="modal">
                    <div class="modal-box max-w-3xl">
                        <form method="dialog">
                            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </form>
                        <h3 class="font-bold text-lg mb-4">Add Job Details</h3>

                        <form id="jobDetailsForm" class="space-y-4">
                            <input type="hidden" name="request_id" id="jobDetailsRequestId">
                            <input type="hidden" name="job_title" id="jobDetailsJobTitle">
                            <input type="hidden" name="department_id" id="jobDetailsDepartmentId">
                            <input type="hidden" name="sub_department_id" id="jobDetailsSubDepartmentId">
                            <input type="hidden" name="type" id="jobDetailsType">
                            <input type="hidden" name="status" id="jobDetailsStatus">
                            <input type="hidden" name="vacancies" id="jobDetailsVacancies">
                            <input type="hidden" name="exam_required" id="jobDetailsExamRequired">
                            <input type="hidden" name="salary_min" id="jobDetailsSalaryMin">
                            <input type="hidden" name="salary_max" id="jobDetailsSalaryMax">
                            <input type="hidden" name="job_period_days" id="jobDetailsJobPeriodDays">
                            <input type="hidden" name="job_end_date" id="jobDetailsJobEndDate">

                            <div>
                                <label class="label">
                                    <span class="label-text">Request ID</span>
                                </label>
                                <input type="text" class="input input-bordered w-full" id="jobDetailsRequestIdDisplay" disabled>
                            </div>

                            <div>
                                <label class="label">
                                    <span class="label-text">Job Title</span>
                                </label>
                                <input type="text" class="input input-bordered w-full" id="jobDetailsJobTitleDisplay" disabled>
                            </div>

                            <div>
                                <label class="label">
                                    <span class="label-text">Job Description</span>
                                </label>
                                <textarea name="job_description" id="jobDescriptionInput" class="textarea textarea-bordered w-full" rows="5" required></textarea>
                            </div>

                            <div>
                                <label class="label">
                                    <span class="label-text">Qualifications</span>
                                </label>
                                <div class="space-y-2" id="qualificationsList"></div>
                                <button type="button" class="btn btn-sm btn-outline w-full" id="addQualificationRowBtn">Add Qualification</button>
                            </div>

                            <div>
                                <label class="label">
                                    <span class="label-text">Requirements</span>
                                </label>
                                <div class="space-y-2" id="requirementsList"></div>
                                <button type="button" class="btn btn-sm btn-outline w-full" id="addRequirementRowBtn">Add Requirement</button>
                            </div>

                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" class="btn btn-ghost" onclick="document.getElementById('jobDetailsModal').close()">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="saveJobDetailsBtn">Save</button>
                            </div>
                        </form>
                    </div>
                    <form method="dialog" class="modal-backdrop">
                        <button>close</button>
                    </form>
                </dialog>

                <script>
                    let vacanciesData = {};

                    document.addEventListener('DOMContentLoaded', function() {
                        const vacanciesTableBody = document.getElementById('vacanciesTableBody');
                        const loadingState = document.getElementById('loadingState');
                        const emptyState = document.getElementById('emptyState');
                        const searchInput = document.getElementById('searchInput');
                        const detailModal = document.getElementById('detailModal');
                        const modalContent = document.getElementById('modalContent');

                        async function fetchJobDetails(requestId) {
                            const res = await fetch(window.location.pathname + '?action=fetch_job_details&request_id=' + encodeURIComponent(requestId), {
                                headers: { 'Accept': 'application/json' }
                            });
                            const json = await res.json();
                            if (!json || !json.success) {
                                throw new Error(json && json.message ? json.message : 'Failed to load job details');
                            }
                            return json.data;
                        }

                        // Initialize Lucide icons
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }

                        function safeText(value) {
                            if (value === null || value === undefined) return '';
                            return String(value);
                        }

                        function formatType(type) {
                            const t = safeText(type).toLowerCase().replace(/_/g, '-');
                            if (!t) return '';
                            return t;
                        }

                        function createArrayRow({
                            container,
                            nameField,
                            descField,
                            nameValue,
                            descValue
                        }) {
                            const row = document.createElement('div');
                            row.className = 'grid grid-cols-1 md:grid-cols-12 gap-2 items-start';

                            const nameWrap = document.createElement('div');
                            nameWrap.className = 'md:col-span-5';
                            const nameInput = document.createElement('input');
                            nameInput.type = 'text';
                            nameInput.name = nameField;
                            nameInput.className = 'input input-bordered w-full';
                            nameInput.placeholder = 'Name';
                            nameInput.value = safeText(nameValue);
                            nameWrap.appendChild(nameInput);

                            const descWrap = document.createElement('div');
                            descWrap.className = 'md:col-span-6';
                            const descInput = document.createElement('input');
                            descInput.type = 'text';
                            descInput.name = descField;
                            descInput.className = 'input input-bordered w-full';
                            descInput.placeholder = 'Description (optional)';
                            descInput.value = safeText(descValue);
                            descWrap.appendChild(descInput);

                            const btnWrap = document.createElement('div');
                            btnWrap.className = 'md:col-span-1 flex';
                            const removeBtn = document.createElement('button');
                            removeBtn.type = 'button';
                            removeBtn.className = 'btn btn-ghost btn-square';
                            removeBtn.title = 'Remove';
                            removeBtn.innerHTML = '<i data-lucide="trash-2" class="w-4 h-4"></i>';
                            removeBtn.addEventListener('click', () => {
                                row.remove();
                                if (typeof lucide !== 'undefined') {
                                    lucide.createIcons();
                                }
                            });
                            btnWrap.appendChild(removeBtn);

                            row.appendChild(nameWrap);
                            row.appendChild(descWrap);
                            row.appendChild(btnWrap);
                            container.appendChild(row);

                            if (typeof lucide !== 'undefined') {
                                lucide.createIcons();
                            }
                        }

                        function resetArrayList(container) {
                            if (!container) return;
                            container.innerHTML = '';
                        }

                        // Render vacancies table
                        function renderVacancies(data) {
                            const vacanciesArray = Object.values(data);

                            if (vacanciesArray.length === 0) {
                                loadingState.classList.add('hidden');
                                emptyState.classList.remove('hidden');
                                vacanciesTableBody.innerHTML = '';
                                return;
                            }

                            loadingState.classList.add('hidden');
                            emptyState.classList.add('hidden');

                            vacanciesTableBody.innerHTML = vacanciesArray.map(vacancy => {
                                const requestId = safeText(vacancy.request_id || vacancy.id);
                                const typeNormalized = formatType(vacancy.type);
                                const statusNormalized = safeText(vacancy.status).toLowerCase();
                                const isExpired = Boolean(vacancy.is_expired);
                                const daysRemaining = vacancy.days_remaining !== undefined && vacancy.days_remaining !== null
                                    ? Number(vacancy.days_remaining)
                                    : null;
                                const createdAt = vacancy.created_at ? new Date(vacancy.created_at) : null;
                                const createdAtLabel = createdAt && !isNaN(createdAt.getTime()) ? createdAt.toLocaleDateString() : '';

                                return `
                        <tr class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="py-4 px-6">
                                <div>
                                    <div class="font-medium text-gray-900">${safeText(vacancy.title)}</div>
                                    <div class="text-sm text-gray-500 mt-1">
                                        <i data-lucide="calendar" class="w-4 h-4 inline mr-1"></i>
                                        Created: ${createdAtLabel}
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-medium text-gray-700">${safeText(vacancy.department || vacancy.department_name)}</div>
                                ${vacancy.sub_department ? `<div class="text-sm text-gray-500">${safeText(vacancy.sub_department)}</div>` : ''}
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium
                                    ${typeNormalized === 'contract' ? 'bg-blue-50 text-blue-700' : 
                                      typeNormalized === 'full-time' || typeNormalized === 'full time' ? 'bg-green-50 text-green-700' : 
                                      'bg-purple-50 text-purple-700'}">
                                    <i data-lucide="${typeNormalized === 'contract' ? 'file-text' : 
                                                     typeNormalized === 'full-time' || typeNormalized === 'full time' ? 'briefcase' : 
                                                     'clock'}" 
                                       class="w-4 h-4"></i>
                                    ${safeText(vacancy.type)}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="users" class="w-5 h-5 text-gray-400"></i>
                                    <span class="font-semibold">${safeText(vacancy.vacancies)}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-medium text-gray-900">${safeText(vacancy.salary_range)}</div>
                                <div class="text-sm text-gray-500">per hour</div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium
                                    ${statusNormalized === 'open' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}">
                                    <i data-lucide="${statusNormalized === 'open' ? 'check-circle' : 'x-circle'}" 
                                       class="w-4 h-4"></i>
                                    ${safeText(vacancy.status)}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="calendar-clock" class="w-5 h-5 
                                        ${isExpired ? 'text-red-500' : 
                                         (daysRemaining !== null && daysRemaining <= 5) ? 'text-orange-500' : 
                                         'text-green-500'}"></i>
                                    <span class="font-medium ${isExpired ? 'text-red-600' : 
                                                              (daysRemaining !== null && daysRemaining <= 5) ? 'text-orange-600' : 
                                                              'text-green-600'}">
                                        ${isExpired ? 'Expired' : (daysRemaining === null ? '' : `${daysRemaining} days`)}
                                    </span>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-2">
                                    <button class="btn btn-sm btn-ghost btn-square" 
                                            onclick="viewVacancyDetails('${requestId}')"
                                            title="View Details">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                    <button class="btn btn-sm btn-ghost btn-square"
                                            onclick="openJobDetailsModal('${requestId}')"
                                            title="Add Job Details">
                                        <i data-lucide="file-plus" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                        }).join('');

                        // Update counts
                        document.getElementById('totalCount').textContent = vacanciesArray.length;
                        document.getElementById('endCount').textContent = vacanciesArray.length;

                        // Re-initialize icons for newly added elements
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    }

                    // Search functionality
                    searchInput.addEventListener('input', function(e) {
                        const searchTerm = e.target.value.toLowerCase();
                        const filteredData = Object.values(vacanciesData).filter(vacancy =>
                            safeText(vacancy.title).toLowerCase().includes(searchTerm) ||
                            safeText(vacancy.department || vacancy.department_name).toLowerCase().includes(searchTerm) ||
                            safeText(vacancy.type).toLowerCase().includes(searchTerm) ||
                            safeText(vacancy.status).toLowerCase().includes(searchTerm)
                        );

                        renderVacancies(filteredData.reduce((acc, vacancy) => {
                            const k = safeText(vacancy.request_id || vacancy.id);
                            acc[k] = vacancy;
                            return acc;
                        }, {}));
                    });

                    // View vacancy details
                    window.viewVacancyDetails = function(vacancyId) {
                        const vacancy = vacanciesData[vacancyId];
                        if (!vacancy) return;

                        const detailsRequestId = safeText(vacancy.request_id || vacancy.id);

                        const savedBlock = document.getElementById('savedJobDetailsBlock');
                        if (savedBlock) {
                            savedBlock.innerHTML = `
                                <h4 class="font-semibold mb-3">Saved Job Details</h4>
                                <div class="text-sm text-gray-500">Loading...</div>
                            `;
                        }

                        modalContent.innerHTML = `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Information -->
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Request ID</label>
                                    <p class="mt-1 font-semibold">${safeText(vacancy.request_id || vacancy.id)}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Job Title</label>
                                    <p class="mt-1 text-lg font-semibold">${safeText(vacancy.title)}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Department</label>
                                    <p class="mt-1">${safeText(vacancy.department || vacancy.department_name)}</p>
                                    ${vacancy.sub_department ? `<p class="text-sm text-gray-600">${safeText(vacancy.sub_department)}</p>` : ''}
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Employment Type</label>
                                    <p class="mt-1">
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium
                                            ${formatType(vacancy.type) === 'contract' ? 'bg-blue-50 text-blue-700' : 
                                              formatType(vacancy.type) === 'full-time' || formatType(vacancy.type) === 'full time' ? 'bg-green-50 text-green-700' : 
                                              'bg-purple-50 text-purple-700'}">
                                            ${safeText(vacancy.type)}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <!-- Status Information -->
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Status</label>
                                    <p class="mt-1">
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium
                                            ${safeText(vacancy.status).toLowerCase() === 'open' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}">
                                            <i data-lucide="${safeText(vacancy.status).toLowerCase() === 'open' ? 'check-circle' : 'x-circle'}" 
                                               class="w-4 h-4"></i>
                                            ${safeText(vacancy.status)}
                                        </span>
                                    </p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Available Vacancies</label>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">${safeText(vacancy.vacancies)}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Days Remaining</label>
                                    <p class="mt-1 text-lg font-semibold ${vacancy.is_expired ? 'text-red-600' : 
                                                                        (vacancy.days_remaining !== undefined && vacancy.days_remaining !== null && Number(vacancy.days_remaining) <= 5) ? 'text-orange-600' : 
                                                                        'text-green-600'}">
                                        ${vacancy.is_expired ? 'Expired' : (vacancy.days_remaining === undefined || vacancy.days_remaining === null ? '' : `${vacancy.days_remaining} days`)}
                                    </p>
                                </div>
                            </div>

                            <!-- Salary Information -->
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Salary Range</label>
                                    <p class="mt-1 text-xl font-bold text-gray-900">${safeText(vacancy.salary_range)}</p>
                                    <p class="text-sm text-gray-500">per hour</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Job Period</label>
                                    <p class="mt-1">${safeText(vacancy.job_period_days)}${vacancy.job_period_days ? ' days' : ''}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Exam Required</label>
                                    <p class="mt-1">
                                        <span class="inline-flex items-center gap-1">
                                            <i data-lucide="${safeText(vacancy.exam_required) === '1' ? 'check-circle' : 'x-circle'}" 
                                               class="w-5 h-5 ${safeText(vacancy.exam_required) === '1' ? 'text-green-500' : 'text-red-500'}"></i>
                                            ${safeText(vacancy.exam_required) === '1' ? 'Yes' : 'No'}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <!-- Timeline Information -->
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Created At</label>
                                    <p class="mt-1">${safeText(vacancy.created_at_formatted || vacancy.created_at)}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Last Updated</label>
                                    <p class="mt-1">${safeText(vacancy.updated_at_formatted || vacancy.updated_at)}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Job End Date</label>
                                    <p class="mt-1">${safeText(vacancy.job_end_date)}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="flex justify-end gap-3">
                                <button class="btn btn-ghost" onclick="document.getElementById('detailModal').close()">Close</button>
                                <button class="btn btn-primary">Apply Now</button>
                            </div>
                        </div>
                    `;

                        // Re-initialize icons in modal
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }

                        detailModal.showModal();

                        fetchJobDetails(detailsRequestId).then(d => {
                            const el = document.getElementById('savedJobDetailsBlock');
                            if (!el) return;
                            const quals = Array.isArray(d.qualifications) ? d.qualifications : [];
                            const reqs = Array.isArray(d.requirements) ? d.requirements : [];
                            const qualList = quals.map(q => {
                                const name = safeText(q && q.name);
                                const desc = safeText(q && q.description);
                                return desc ? `${name} | ${desc}` : name;
                            }).filter(Boolean);
                            const reqLines = reqs.map(r => {
                                const name = safeText(r && r.name);
                                const desc = safeText(r && r.description);
                                return desc ? `${name} | ${desc}` : name;
                            }).filter(Boolean);
                            el.innerHTML = `
                                <h4 class="font-semibold mb-3">Saved Job Details</h4>
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <div class="text-xs font-semibold text-gray-500">Description</div>
                                        <div class="mt-1 whitespace-pre-wrap">${safeText(d.description) || '<span class="text-gray-400">No saved description</span>'}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold text-gray-500">Qualifications</div>
                                        <div class="mt-1 whitespace-pre-wrap">${qualList.length ? safeText(qualList.join('\n')) : '<span class="text-gray-400">No saved qualifications</span>'}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold text-gray-500">Requirements</div>
                                        <div class="mt-1 whitespace-pre-wrap">${reqLines.length ? safeText(reqLines.join('\n')) : '<span class="text-gray-400">No saved requirements</span>'}</div>
                                    </div>
                                </div>
                            `;
                        }).catch(err => {
                            const el = document.getElementById('savedJobDetailsBlock');
                            if (!el) return;
                            el.innerHTML = `
                                <h4 class="font-semibold mb-3">Saved Job Details</h4>
                                <div class="text-sm text-red-600">${safeText(err.message) || 'Failed to load saved details'}</div>
                            `;
                        });
                    };

                    window.openJobDetailsModal = function(vacancyId) {
                        const vacancy = vacanciesData[vacancyId];
                        if (!vacancy) return;

                        const requestId = safeText(vacancy.request_id || vacancy.id);
                        document.getElementById('jobDetailsRequestId').value = requestId;
                        document.getElementById('jobDetailsJobTitle').value = safeText(vacancy.title);
                        document.getElementById('jobDetailsDepartmentId').value = safeText(vacancy.department_id);
                        document.getElementById('jobDetailsSubDepartmentId').value = safeText(vacancy.sub_department_id);
                        document.getElementById('jobDetailsType').value = safeText(vacancy.type);
                        document.getElementById('jobDetailsStatus').value = safeText(vacancy.status);
                        document.getElementById('jobDetailsVacancies').value = safeText(vacancy.vacancies);
                        document.getElementById('jobDetailsExamRequired').value = safeText(vacancy.exam_required);
                        document.getElementById('jobDetailsSalaryMin').value = safeText(vacancy.salary_min);
                        document.getElementById('jobDetailsSalaryMax').value = safeText(vacancy.salary_max);
                        document.getElementById('jobDetailsJobPeriodDays').value = safeText(vacancy.job_period_days);
                        document.getElementById('jobDetailsJobEndDate').value = safeText(vacancy.job_end_date);
                        document.getElementById('jobDetailsRequestIdDisplay').value = requestId;
                        document.getElementById('jobDetailsJobTitleDisplay').value = safeText(vacancy.title);
                        document.getElementById('jobDescriptionInput').value = '';
                        const qualsList = document.getElementById('qualificationsList');
                        const reqsList = document.getElementById('requirementsList');
                        resetArrayList(qualsList);
                        resetArrayList(reqsList);
                        createArrayRow({
                            container: qualsList,
                            nameField: 'qualifications_name[]',
                            descField: 'qualifications_description[]',
                            nameValue: '',
                            descValue: ''
                        });
                        createArrayRow({
                            container: reqsList,
                            nameField: 'requirements_name[]',
                            descField: 'requirements_description[]',
                            nameValue: '',
                            descValue: ''
                        });

                        document.getElementById('jobDetailsModal').showModal();

                        fetchJobDetails(requestId).then(d => {
                            document.getElementById('jobDescriptionInput').value = safeText(d.description);
                            const quals = Array.isArray(d.qualifications) ? d.qualifications : [];
                            const reqs = Array.isArray(d.requirements) ? d.requirements : [];

                            resetArrayList(qualsList);
                            resetArrayList(reqsList);

                            if (quals.length) {
                                quals.forEach(q => {
                                    createArrayRow({
                                        container: qualsList,
                                        nameField: 'qualifications_name[]',
                                        descField: 'qualifications_description[]',
                                        nameValue: q && q.name,
                                        descValue: q && q.description
                                    });
                                });
                            } else {
                                createArrayRow({
                                    container: qualsList,
                                    nameField: 'qualifications_name[]',
                                    descField: 'qualifications_description[]',
                                    nameValue: '',
                                    descValue: ''
                                });
                            }

                            if (reqs.length) {
                                reqs.forEach(r => {
                                    createArrayRow({
                                        container: reqsList,
                                        nameField: 'requirements_name[]',
                                        descField: 'requirements_description[]',
                                        nameValue: r && r.name,
                                        descValue: r && r.description
                                    });
                                });
                            } else {
                                createArrayRow({
                                    container: reqsList,
                                    nameField: 'requirements_name[]',
                                    descField: 'requirements_description[]',
                                    nameValue: '',
                                    descValue: ''
                                });
                            }
                        }).catch(() => {
                        });
                    };

                    document.getElementById('addQualificationRowBtn').addEventListener('click', () => {
                        const container = document.getElementById('qualificationsList');
                        createArrayRow({
                            container,
                            nameField: 'qualifications_name[]',
                            descField: 'qualifications_description[]',
                            nameValue: '',
                            descValue: ''
                        });
                    });

                    document.getElementById('addRequirementRowBtn').addEventListener('click', () => {
                        const container = document.getElementById('requirementsList');
                        createArrayRow({
                            container,
                            nameField: 'requirements_name[]',
                            descField: 'requirements_description[]',
                            nameValue: '',
                            descValue: ''
                        });
                    });

                    async function fetchVacancies() {
                        loadingState.classList.remove('hidden');
                        emptyState.classList.add('hidden');
                        try {
                            const res = await fetch(window.location.pathname + '?action=fetch_vacancies', {
                                headers: { 'Accept': 'application/json' }
                            });
                            const json = await res.json();
                            if (!json || !json.success) {
                                throw new Error(json && json.message ? json.message : 'Failed to load vacancies');
                            }
                            vacanciesData = json.data || {};
                            renderVacancies(vacanciesData);
                        } catch (e) {
                            loadingState.classList.add('hidden');
                            emptyState.classList.remove('hidden');
                            vacanciesTableBody.innerHTML = '';
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: e.message || 'Failed to load vacancies'
                                });
                            }
                        }
                    }

                    document.getElementById('jobDetailsForm').addEventListener('submit', async function(e) {
                        e.preventDefault();
                        const form = e.currentTarget;
                        const btn = document.getElementById('saveJobDetailsBtn');
                        btn.disabled = true;
                        try {
                            const fd = new FormData(form);
                            const requestId = fd.get('request_id');
                            const res = await fetch(window.location.pathname + '?action=save_job_details', {
                                method: 'POST',
                                body: fd
                            });
                            const json = await res.json();
                            if (!json || !json.success) {
                                throw new Error(json && json.message ? json.message : 'Failed to save');
                            }

                            document.getElementById('jobDetailsModal').close();
                            if (typeof Swal !== 'undefined') {
                                const dbg = json.debug || {};
                                const extra = dbg.db ? ` (DB: ${dbg.db}, JV: ${dbg.job_vacancy_inserted || 0}, JD: ${dbg.job_description_inserts || 0}, Q: ${dbg.qualifications_inserts || 0}, R: ${dbg.requirements_inserts || 0})` : '';
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Saved',
                                    text: 'Job details saved successfully' + extra
                                });
                            }

                            if (requestId) {
                                setTimeout(() => {
                                    window.viewVacancyDetails(String(requestId));
                                }, 200);
                            }
                        } catch (err) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: err.message || 'Failed to save'
                                });
                            }
                        } finally {
                            btn.disabled = false;
                        }
                    });

                    fetchVacancies();

                    // Pagination handlers
                    document.getElementById('prevPage').addEventListener('click', () => {
                        console.log('Previous page clicked');
                    });
                    document.getElementById('nextPage').addEventListener('click', () => {
                        console.log('Next page clicked');
                    });
                });
            </script>

            <script src="../JAVASCRIPT/sidebar.js"></script>
            <?php require('../../partials/footer.php') ?>