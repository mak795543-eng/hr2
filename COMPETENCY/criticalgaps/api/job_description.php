<?php

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../job_desc/db_job_desc.php';

    $requestId = isset($_GET['request_id']) ? trim((string)$_GET['request_id']) : '';
    $requestIdInt = ($requestId !== '' && ctype_digit($requestId)) ? (int)$requestId : null;

    $conn = job_desc_mysqli();

    $tableExists = function (mysqli $c, string $table): bool {
        $t = $c->real_escape_string($table);
        $res = $c->query("SHOW TABLES LIKE '{$t}'");
        if (!$res) return false;
        $ok = $res->num_rows > 0;
        $res->free();
        return $ok;
    };

    $getColumns = function (mysqli $c, string $table): array {
        $cols = [];
        $resCols = $c->query("SHOW COLUMNS FROM `{$table}`");
        if ($resCols) {
            while ($row = $resCols->fetch_assoc()) {
                $field = (string)($row['Field'] ?? '');
                if ($field !== '') $cols[$field] = true;
            }
            $resCols->free();
        }
        return $cols;
    };

    $firstExisting = function (array $cols, array $candidates): ?string {
        foreach ($candidates as $c) {
            if (isset($cols[$c])) return $c;
        }
        return null;
    };

    $buildOrderBy = function (array $cols, ?string $idField): string {
        $orderParts = [];
        if (isset($cols['created_at'])) {
            $orderParts[] = '`created_at` DESC';
        }
        if ($idField !== null) {
            $orderParts[] = "`{$idField}` DESC";
        }
        if (!$orderParts) return '';
        return ' ORDER BY ' . implode(', ', $orderParts);
    };

    $fetchRows = function (
        mysqli $c,
        string $table,
        array $selectMap,
        ?string $requestIdField,
        string $requestId,
        ?int $requestIdInt,
        string $orderBy
    ): array {
        if (!$selectMap) {
            return [];
        }

        $selectParts = [];
        foreach ($selectMap as $outKey => $dbField) {
            $selectParts[] = "`{$dbField}` AS `{$outKey}`";
        }
        $selectSql = implode(', ', $selectParts);

        if ($requestId !== '' && $requestIdField !== null) {
            $stmt = $c->prepare("SELECT {$selectSql} FROM `{$table}` WHERE `{$requestIdField}` = ?{$orderBy}");
            if (!$stmt) throw new RuntimeException($c->error);
            if ($requestIdInt !== null) {
                $stmt->bind_param('i', $requestIdInt);
            } else {
                $stmt->bind_param('s', $requestId);
            }
        } else {
            $stmt = $c->prepare("SELECT {$selectSql} FROM `{$table}`{$orderBy}");
            if (!$stmt) throw new RuntimeException($c->error);
        }

        $ok = $stmt->execute();
        if (!$ok) {
            throw new RuntimeException($stmt->error ?: 'Query failed');
        }

        $rows = [];
        $result = method_exists($stmt, 'get_result') ? $stmt->get_result() : false;
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $clean = [];
                foreach ($selectMap as $outKey => $_) {
                    $clean[$outKey] = $row[$outKey] ?? null;
                }
                $rows[] = $clean;
            }
        } else {
            $bindVars = [];
            foreach ($selectMap as $outKey => $_) {
                $bindVars[$outKey] = null;
            }
            $refs = [];
            foreach (array_keys($bindVars) as $k) {
                $refs[] = &$bindVars[$k];
            }
            $stmt->bind_result(...$refs);
            while ($stmt->fetch()) {
                $clean = [];
                foreach ($selectMap as $outKey => $_) {
                    $clean[$outKey] = $bindVars[$outKey];
                }
                $rows[] = $clean;
            }
        }

        $stmt->close();
        return $rows;
    };

    $tables = [
        'job_description' => [
            'tables' => ['job_description', 'job_roles'],
            'fields' => [
                'id' => ['id', 'ID'],
                'request_id' => ['request_id', 'requestId', 'requestID'],
                'description' => ['description', 'job_description', 'job_desc', 'details'],
            ],
            'required' => ['request_id', 'description'],
        ],
        'requirements' => [
            'tables' => ['requirements', 'job_requirements'],
            'fields' => [
                'id' => ['id', 'ID'],
                'request_id' => ['request_id', 'requestId', 'requestID'],
                'name' => ['name', 'requirement', 'title'],
                'description' => ['description', 'details', 'requirement_description'],
            ],
            'required' => ['request_id', 'name'],
        ],
        'qualifications' => [
            'tables' => ['qualifications'],
            'fields' => [
                'id' => ['id', 'ID'],
                'request_id' => ['request_id', 'requestId', 'requestID'],
                'name' => ['name', 'qualification', 'title'],
                'description' => ['description', 'details', 'qualification_description'],
            ],
            'required' => ['request_id', 'name'],
        ],
    ];

    $grouped = [];
    if ($requestId !== '') {
        $grouped[$requestId] = [
            'job_description' => null,
            'qualifications' => [],
            'requirements' => [],
        ];
    }

    foreach ($tables as $logicalName => $cfg) {
        $tableCandidates = $cfg['tables'] ?? [$logicalName];
        $tableName = null;
        foreach ($tableCandidates as $t) {
            if ($tableExists($conn, $t)) {
                $tableName = $t;
                break;
            }
        }
        if ($tableName === null) {
            continue;
        }

        $cols = $getColumns($conn, $tableName);
        $resolved = [];
        foreach (($cfg['fields'] ?? []) as $outKey => $candidates) {
            $resolved[$outKey] = $firstExisting($cols, $candidates);
        }

        foreach (($cfg['required'] ?? []) as $reqKey) {
            if (!isset($resolved[$reqKey]) || $resolved[$reqKey] === null) {
                throw new RuntimeException("Table {$tableName} missing required column for {$reqKey}");
            }
        }

        $selectMap = [];
        foreach ($resolved as $outKey => $dbField) {
            if ($dbField === null) continue;
            $selectMap[$outKey] = $dbField;
        }

        $needsDescriptionPlaceholder =
            ($logicalName === 'requirements' || $logicalName === 'qualifications')
            && (!isset($resolved['description']) || $resolved['description'] === null);

        $orderBy = $buildOrderBy($cols, $resolved['id'] ?? null);
        $rows = $fetchRows(
            $conn,
            $tableName,
            $selectMap,
            $resolved['request_id'] ?? null,
            $requestId,
            $requestIdInt,
            $orderBy
        );

        if ($needsDescriptionPlaceholder) {
            foreach ($rows as &$r) {
                $r['description'] = null;
            }
            unset($r);
        }

        if ($logicalName === 'job_description') {
            foreach ($rows as $row) {
                $rid = isset($row['request_id']) ? (string)$row['request_id'] : '';
                if ($rid === '') continue;
                if (!isset($grouped[$rid])) {
                    $grouped[$rid] = [
                        'job_description' => null,
                        'qualifications' => [],
                        'requirements' => [],
                    ];
                }
                if ($grouped[$rid]['job_description'] === null) {
                    $grouped[$rid]['job_description'] = $row['description'] ?? null;
                }
            }
        } elseif ($logicalName === 'requirements') {
            foreach ($rows as $row) {
                $rid = isset($row['request_id']) ? (string)$row['request_id'] : '';
                if ($rid === '') continue;
                if (!isset($grouped[$rid])) {
                    $grouped[$rid] = [
                        'job_description' => null,
                        'qualifications' => [],
                        'requirements' => [],
                    ];
                }
                $grouped[$rid]['requirements'][] = [
                    'name' => $row['name'] ?? null,
                    'description' => $row['description'] ?? null,
                ];
            }
        } elseif ($logicalName === 'qualifications') {
            foreach ($rows as $row) {
                $rid = isset($row['request_id']) ? (string)$row['request_id'] : '';
                if ($rid === '') continue;
                if (!isset($grouped[$rid])) {
                    $grouped[$rid] = [
                        'job_description' => null,
                        'qualifications' => [],
                        'requirements' => [],
                    ];
                }
                $grouped[$rid]['qualifications'][] = [
                    'name' => $row['name'] ?? null,
                    'description' => $row['description'] ?? null,
                ];
            }
        }
    }

    $conn->close();

    echo json_encode([
        'success' => true,
        'data' => $grouped,
    ]);
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        try { $conn->close(); } catch (Throwable $t) {}
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
