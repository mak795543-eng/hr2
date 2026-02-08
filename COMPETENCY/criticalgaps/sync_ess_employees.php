<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $ess = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=hr2_employee_self_service;charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'ESS connection failed']);
    exit;
}

try {
    $res = $ess->query("SELECT employee_no, first_name, last_name, department, position, status FROM employees");
    $rows = $res ? $res->fetchAll() : [];

    ensureKpiSchema();

    $ins = $pdo->prepare(
        "INSERT INTO employees (employee_id, full_name, position, department)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            full_name = VALUES(full_name),
            position = VALUES(position),
            department = VALUES(department),
            updated_at = CURRENT_TIMESTAMP"
    );

    $inserted = 0;
    $updated = 0;

    if (count($rows) > 0) {
        foreach ($rows as $r) {
            $empId = trim((string)($r['employee_no'] ?? ''));
            if ($empId === '') continue;
            $first = trim((string)($r['first_name'] ?? ''));
            $last = trim((string)($r['last_name'] ?? ''));
            $full = trim($first . ' ' . $last);
            if ($full === '') $full = $empId;
            $dept = trim((string)($r['department'] ?? ''));
            $pos = trim((string)($r['position'] ?? ''));

            $stmt = $pdo->prepare("SELECT 1 FROM employees WHERE employee_id = ? LIMIT 1");
            $stmt->execute([$empId]);
            $exists = (bool)$stmt->fetchColumn();

            $ins->execute([$empId, $full, $pos, $dept]);
            if ($exists) $updated++;
            else $inserted++;
        }
    } else {
        $seed = [
            ['E0001', 'Ava Santos', 'Front Desk Associate', 'Front Office / Reception'],
            ['E0002', 'Miguel Reyes', 'Front Desk Supervisor', 'Front Office / Reception'],
            ['E0003', 'Lara Cruz', 'Room Attendant', 'Housekeeping'],
            ['E0004', 'Jude Garcia', 'Housekeeping Supervisor', 'Housekeeping'],
            ['E0005', 'Noah Ramos', 'Waiter', 'Food & Beverage (F&B)'],
            ['E0006', 'Emma Flores', 'Bartender', 'Food & Beverage (F&B)'],
            ['E0007', 'Rafael Torres', 'Line Cook', 'Kitchen / Culinary'],
            ['E0008', 'Sofia Mendoza', 'Sous Chef', 'Kitchen / Culinary'],
            ['E0009', 'Caleb Diaz', 'Sales Executive', 'Sales & Marketing'],
            ['E0010', 'Iris Navarro', 'Marketing Associate', 'Sales & Marketing'],
            ['E0011', 'Ethan Castillo', 'HR Assistant', 'Human Resources (HR)'],
            ['E0012', 'Chloe Santiago', 'HR Officer', 'Human Resources (HR)'],
            ['E0013', 'Lucas Aquino', 'Accountant', 'Finance / Accounting'],
            ['E0014', 'Mia Alvarez', 'Finance Analyst', 'Finance / Accounting'],
            ['E0015', 'Daniel Cruz', 'Maintenance Technician', 'Engineering / Maintenance'],
            ['E0016', 'Sara Bautista', 'Maintenance Supervisor', 'Engineering / Maintenance'],
            ['E0017', 'Leo Ramos', 'Security Guard', 'Security'],
            ['E0018', 'Nina Delgado', 'Security Supervisor', 'Security'],
            ['E0019', 'Jonas Perez', 'Concierge', 'Front Office / Reception'],
            ['E0020', 'Paula Ramos', 'Hostess', 'Food & Beverage (F&B)'],
            ['E0021', 'Kenji Morales', 'Dishwasher', 'Kitchen / Culinary'],
            ['E0022', 'Valerie Gomez', 'Digital Marketer', 'Sales & Marketing'],
            ['E0023', 'Owen Cruz', 'Payroll Specialist', 'Finance / Accounting'],
            ['E0024', 'Bianca Soriano', 'Training Coordinator', 'Human Resources (HR)'],
            ['E0025', 'Marco Robles', 'HVAC Technician', 'Engineering / Maintenance'],
        ];
        foreach ($seed as $r) {
            $stmt = $pdo->prepare("SELECT 1 FROM employees WHERE employee_id = ? LIMIT 1");
            $stmt->execute([$r[0]]);
            $exists = (bool)$stmt->fetchColumn();
            $ins->execute([$r[0], $r[1], $r[2], $r[3]]);
            if ($exists) $updated++;
            else $inserted++;
        }
    }

    echo json_encode([
        'success' => true,
        'inserted' => $inserted,
        'updated' => $updated,
        'total_ess' => count($rows)
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sync failed']);
}
