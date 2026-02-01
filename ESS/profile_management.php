<?php
session_start();

require_once __DIR__ . '/db.php';

$emp = $conn ? ess_ensure_employee($conn) : null;

$success_message = '';
$error_message = '';

$employeeId = $conn ? ess_employee_id($conn) : null;

$hasPendingProfileEditRequest = false;
$decisionRequest = null;

if ($conn && is_int($employeeId)) {
    if (isset($_GET['ack']) && is_numeric($_GET['ack'])) {
        $ackId = (int)$_GET['ack'];
        $stmt = mysqli_prepare($conn, 'SELECT id, status, reason_choice, requested_data FROM profile_update_requests WHERE id = ? AND employee_id = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ii', $ackId, $employeeId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if (is_array($row)) {
                $status = (string)($row['status'] ?? '');
                $stmt2 = mysqli_prepare($conn, 'UPDATE profile_update_requests SET seen_by_employee = 1 WHERE id = ? AND employee_id = ?');
                if ($stmt2) {
                    mysqli_stmt_bind_param($stmt2, 'ii', $ackId, $employeeId);
                    mysqli_stmt_execute($stmt2);
                    mysqli_stmt_close($stmt2);
                }
                if (strtolower(trim($status)) === 'approved') {
                    $_SESSION['profile_edit_granted'] = true;

                    $allowed = ['gender', 'civil_status', 'location', 'work_email', 'nationality', 'phone', 'emergency_contact', 'first_name', 'middle_name', 'birthdate'];
                    $editFields = [];
                    $raw = (string)($row['requested_data'] ?? '');
                    if ($raw !== '') {
                        $decoded = json_decode($raw, true);
                        if (is_array($decoded)) {
                            $rf = $decoded['requested_fields'] ?? [];
                            if (is_array($rf)) {
                                foreach ($rf as $v) {
                                    $v = trim((string)$v);
                                    if ($v !== '' && in_array($v, $allowed, true)) {
                                        $editFields[] = $v;
                                    }
                                }
                            }
                        }
                    }
                    $_SESSION['profile_edit_fields'] = array_values(array_unique($editFields));

                    $surnameGranted = false;
                    $reasonChoice = strtolower(trim((string)($row['reason_choice'] ?? '')));
                    if ($reasonChoice === 'change of surname') {
                        $g = '';
                        $stmtG = mysqli_prepare($conn, 'SELECT gender FROM employee_profiles WHERE employee_id = ? LIMIT 1');
                        if ($stmtG) {
                            mysqli_stmt_bind_param($stmtG, 'i', $employeeId);
                            mysqli_stmt_execute($stmtG);
                            $resG = mysqli_stmt_get_result($stmtG);
                            $rowG = $resG ? mysqli_fetch_assoc($resG) : null;
                            mysqli_stmt_close($stmtG);
                            if (is_array($rowG)) {
                                $g = strtolower(trim((string)($rowG['gender'] ?? '')));
                            }
                        }
                        if ($g === 'female' || $g === 'f') {
                            $surnameGranted = true;
                        }
                    }
                    $_SESSION['profile_surname_granted'] = $surnameGranted;
                }
            }
        }

        header('Location: ' . basename((string)$_SERVER['PHP_SELF']));
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_edit_access'])) {
        $allowedReasons = [
            'Updated Personal Details',
            'Legal Change of Information',
            'change of surname',
        ];

        $reasonChoice = trim((string)($_POST['reason_choice'] ?? ''));
        $reasonText = trim((string)($_POST['reason_text'] ?? ''));

        $requestedFields = $_POST['requested_fields'] ?? [];
        $requestedFields = is_array($requestedFields) ? $requestedFields : [];
        $requestedFields = array_values(array_filter(array_map('strval', $requestedFields), static fn($v) => trim($v) !== ''));

        $allowedFieldKeys = ['gender', 'civil_status', 'location', 'work_email', 'nationality', 'phone', 'emergency_contact', 'first_name', 'middle_name', 'birthdate'];
        $requestedFields = array_values(array_unique(array_values(array_filter($requestedFields, static fn($v) => in_array($v, $allowedFieldKeys, true)))));

        $proofDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profile_request_proofs';
        if (!is_dir($proofDir)) {
            @mkdir($proofDir, 0775, true);
        }

        $stmt = mysqli_prepare($conn, 'SELECT id FROM profile_update_requests WHERE employee_id = ? AND status = \'Pending\' ORDER BY created_at DESC LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $employeeId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);
            if (is_array($row)) {
                header('Location: ' . basename((string)$_SERVER['PHP_SELF']) . '?pending=1');
                exit;
            }
        }

        if (!in_array($reasonChoice, $allowedReasons, true)) {
            $error_message = 'Please select a valid reason.';
        } elseif ($reasonChoice === 'Updated Personal Details' && count($requestedFields) === 0) {
            $error_message = 'Please select what you want to change.';
        } elseif ($reasonText === '') {
            $error_message = 'Please provide your reason details.';
        } elseif (!isset($_FILES['proof_file']) || !is_array($_FILES['proof_file'])) {
            $error_message = 'Please upload proof/basis file.';
        } else {
            $upErr = (int)($_FILES['proof_file']['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($upErr !== UPLOAD_ERR_OK) {
                $uploadErrorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the server limit (upload_max_filesize).',
                    UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the form limit (MAX_FILE_SIZE).',
                    UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
                    UPLOAD_ERR_NO_FILE => 'No file uploaded.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk on the server.',
                    UPLOAD_ERR_EXTENSION => 'File upload stopped by a PHP extension on the server.',
                ];
                $error_message = $uploadErrorMessages[$upErr] ?? ('File upload error (code ' . $upErr . ').');
            } else {
                $tmp = (string)($_FILES['proof_file']['tmp_name'] ?? '');
                $orig = (string)($_FILES['proof_file']['name'] ?? 'proof');
                $size = (int)($_FILES['proof_file']['size'] ?? 0);

                if ($tmp === '' || !is_uploaded_file($tmp)) {
                    $error_message = 'Upload validation failed. Please try again.';
                } elseif ($size > 5 * 1024 * 1024) {
                    $error_message = 'File too large. Maximum is 5MB.';
                } else {
                    $ext = strtolower((string)pathinfo($orig, PATHINFO_EXTENSION));
                    $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'];
                    if (!in_array($ext, $allowedExt, true)) {
                        $error_message = 'Unsupported file type. Please upload PDF, image, or document file.';
                    } else {
                        $fileName = 'profile_request_' . $employeeId . '_' . time() . '.' . $ext;
                        $dest = $proofDir . DIRECTORY_SEPARATOR . $fileName;
                        if (!@move_uploaded_file($tmp, $dest)) {
                            $error_message = 'Failed to save uploaded proof file. Please try again.';
                        } else {
                            $proofRelPath = 'uploads/profile_request_proofs/' . $fileName;

                            $payload = json_encode([
                                'type' => 'edit_access',
                                'requested_at' => date('c'),
                                'reason_choice' => $reasonChoice,
                                'requested_fields' => $reasonChoice === 'Updated Personal Details' ? $requestedFields : [],
                            ], JSON_UNESCAPED_SLASHES);
                            if (!is_string($payload) || $payload === '') {
                                $payload = '{"type":"edit_access"}';
                            }

                            $stmt = mysqli_prepare(
                                $conn,
                                'INSERT INTO profile_update_requests (employee_id, requested_data, reason_choice, reason_text, proof_file_path, status) VALUES (?, ?, ?, ?, ?, \'Pending\')'
                            );
                            if (!$stmt) {
                                $error_message = 'Failed to submit request. Please try again.';
                            } else {
                                mysqli_stmt_bind_param($stmt, 'issss', $employeeId, $payload, $reasonChoice, $reasonText, $proofRelPath);
                                $ok = mysqli_stmt_execute($stmt);
                                mysqli_stmt_close($stmt);

                                if (!$ok) {
                                    $error_message = 'Failed to submit request. Please try again.';
                                } else {
                                    header('Location: ' . basename((string)$_SERVER['PHP_SELF']) . '?requested=1');
                                    exit;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile_changes'])) {
        $granted = (bool)($_SESSION['profile_edit_granted'] ?? false);
        if (!$granted) {
            $error_message = 'Editing is restricted. Please request approval first.';
        } else {
            $allowed = (array)($_SESSION['profile_edit_fields'] ?? []);
            $allowed = array_values(array_unique(array_map('strval', $allowed)));
            $canEdit = static fn(string $k) => in_array($k, $allowed, true);

            $surnameGranted = (bool)($_SESSION['profile_surname_granted'] ?? false);

            $phone = trim((string)($_POST['phone'] ?? ''));
            $workLocation = trim((string)($_POST['work_location'] ?? ''));
            $birthdate = trim((string)($_POST['birthdate'] ?? ''));
            $nationality = trim((string)($_POST['nationality'] ?? ''));
            $gender = trim((string)($_POST['gender'] ?? ''));
            $civilStatus = trim((string)($_POST['civil_status'] ?? ''));
            $workEmail = trim((string)($_POST['work_email'] ?? ''));
            $firstName = trim((string)($_POST['first_name'] ?? ''));
            $middleName = trim((string)($_POST['middle_name'] ?? ''));
            $suffix = trim((string)($_POST['suffix'] ?? ''));
            $lastName = trim((string)($_POST['last_name'] ?? ''));
            $emergencyName = trim((string)($_POST['emergency_name'] ?? ''));
            $emergencyRelationship = trim((string)($_POST['emergency_relationship'] ?? ''));

            $birthdateParam = '';
            $ageParam = '';

            if ($canEdit('birthdate') && $birthdate !== '') {
                $dt = null;
                $formats = ['Y-m-d', 'Y-n-j', 'm/d/Y', 'd/m/Y'];
                foreach ($formats as $fmt) {
                    $try = DateTime::createFromFormat('!' . $fmt, $birthdate);
                    $errors = $try ? DateTime::getLastErrors() : ['warning_count' => 1, 'error_count' => 1];
                    if ($try && is_array($errors) && ($errors['warning_count'] ?? 0) === 0 && ($errors['error_count'] ?? 0) === 0) {
                        $dt = $try;
                        break;
                    }
                }

                if (!$dt) {
                    $ts = strtotime($birthdate);
                    if ($ts !== false) {
                        $dt = new DateTime(date('Y-m-d', $ts));
                    }
                }

                if (!$dt) {
                    $error_message = 'Invalid birthdate format.';
                } else {
                    $today = new DateTime('today');
                    if ($dt > $today) {
                        $error_message = 'Birthdate cannot be in the future.';
                    } else {
                        $age = (int)$dt->diff($today)->y;
                        if ($age < 0 || $age > 120) {
                            $error_message = 'Birthdate results in an invalid age.';
                        } else {
                            $birthdateParam = $dt->format('Y-m-d');
                            $ageParam = (string)$age;
                        }
                    }
                }
            }

            if (!$canEdit('birthdate')) {
                $birthdateParam = '';
                $ageParam = '';
            }

            if ($error_message === '') {
                $empSet = [];
                $empTypes = '';
                $empVals = [];

                if ($canEdit('first_name')) {
                    if ($firstName === '') {
                        $error_message = 'First name is required.';
                    } else {
                        $empSet[] = 'first_name = ?';
                        $empTypes .= 's';
                        $empVals[] = $firstName;
                    }
                }

                if ($canEdit('middle_name')) {
                    $empSet[] = 'middle_name = NULLIF(?, \'\')';
                    $empTypes .= 's';
                    $empVals[] = $middleName;
                }

                if ($canEdit('work_email')) {
                    if ($workEmail === '') {
                        $error_message = 'Work email is required.';
                    } else {
                        $empSet[] = 'email = ?';
                        $empTypes .= 's';
                        $empVals[] = $workEmail;
                    }
                }

                if ($suffix !== '' && $canEdit('middle_name')) {
                    $empSet[] = 'suffix = NULLIF(?, \'\')';
                    $empTypes .= 's';
                    $empVals[] = $suffix;
                }

                if ($surnameGranted) {
                    if ($lastName === '') {
                        $error_message = 'Last name is required.';
                    } else {
                        $empSet[] = 'last_name = ?';
                        $empTypes .= 's';
                        $empVals[] = $lastName;
                    }
                    if (!$canEdit('middle_name')) {
                        $empSet[] = 'middle_name = NULLIF(?, \'\')';
                        $empTypes .= 's';
                        $empVals[] = $middleName;
                    }
                }

                $okEmp = true;
                if ($error_message === '' && count($empSet) > 0) {
                    $empVals[] = $employeeId;
                    $empTypes .= 'i';
                    $sqlEmp = 'UPDATE employees SET ' . implode(', ', $empSet) . ' WHERE id = ?';
                    $stmtEmp = mysqli_prepare($conn, $sqlEmp);
                    if (!$stmtEmp) {
                        $okEmp = false;
                    } else {
                        $refs = [];
                        foreach ($empVals as $k => $v) {
                            $refs[$k] = &$empVals[$k];
                        }
                        mysqli_stmt_bind_param($stmtEmp, $empTypes, ...$refs);
                        $okEmp = mysqli_stmt_execute($stmtEmp);
                        mysqli_stmt_close($stmtEmp);
                    }
                    if (!$okEmp) {
                        $error_message = 'Failed to save profile.';
                    }
                }

                if ($error_message === '') {
                    @mysqli_query($conn, 'INSERT IGNORE INTO employee_profiles (employee_id) VALUES (' . (int)$employeeId . ')');

                    $profSet = [];
                    $profTypes = '';
                    $profVals = [];

                    if ($canEdit('phone')) {
                        $profSet[] = 'phone = NULLIF(?, \'\')';
                        $profTypes .= 's';
                        $profVals[] = $phone;
                    }
                    if ($canEdit('location')) {
                        $profSet[] = 'work_location = NULLIF(?, \'\')';
                        $profTypes .= 's';
                        $profVals[] = $workLocation;
                    }
                    if ($canEdit('nationality')) {
                        $profSet[] = 'nationality = NULLIF(?, \'\')';
                        $profTypes .= 's';
                        $profVals[] = $nationality;
                    }
                    if ($canEdit('gender')) {
                        $profSet[] = 'gender = NULLIF(?, \'\')';
                        $profTypes .= 's';
                        $profVals[] = $gender;
                    }
                    if ($canEdit('civil_status')) {
                        $profSet[] = 'civil_status = NULLIF(?, \'\')';
                        $profTypes .= 's';
                        $profVals[] = $civilStatus;
                    }
                    if ($canEdit('emergency_contact')) {
                        $profSet[] = 'emergency_name = NULLIF(?, \'\')';
                        $profTypes .= 's';
                        $profVals[] = $emergencyName;
                        $profSet[] = 'emergency_relationship = NULLIF(?, \'\')';
                        $profTypes .= 's';
                        $profVals[] = $emergencyRelationship;
                    }
                    if ($canEdit('birthdate')) {
                        $profSet[] = 'birthdate = NULLIF(?, \'\')';
                        $profTypes .= 's';
                        $profVals[] = $birthdateParam;
                        $profSet[] = 'age = NULLIF(?, \'\')';
                        $profTypes .= 's';
                        $profVals[] = $ageParam;
                    }

                    if (count($profSet) > 0) {
                        $profVals[] = $employeeId;
                        $profTypes .= 'i';
                        $sqlProf = 'UPDATE employee_profiles SET ' . implode(', ', $profSet) . ' WHERE employee_id = ?';
                        $stmtProf = mysqli_prepare($conn, $sqlProf);
                        if (!$stmtProf) {
                            $error_message = 'Failed to save profile.';
                        } else {
                            $refs2 = [];
                            foreach ($profVals as $k => $v) {
                                $refs2[$k] = &$profVals[$k];
                            }
                            mysqli_stmt_bind_param($stmtProf, $profTypes, ...$refs2);
                            $okProf = mysqli_stmt_execute($stmtProf);
                            mysqli_stmt_close($stmtProf);
                            if (!$okProf) {
                                $error_message = 'Failed to save profile.';
                            }
                        }
                    }
                }

                if ($error_message === '') {
                    $_SESSION['profile_edit_granted'] = false;
                    $_SESSION['profile_edit_fields'] = [];
                    $_SESSION['profile_surname_granted'] = false;
                    $success_message = 'Profile updated successfully.';

                    $emp = ess_ensure_employee($conn);
                }
            }
        }
    }

    $stmt = mysqli_prepare($conn, 'SELECT id FROM profile_update_requests WHERE employee_id = ? AND status = \'Pending\' ORDER BY created_at DESC LIMIT 1');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        $hasPendingProfileEditRequest = is_array($row);
    }

    $stmt = mysqli_prepare($conn, "SELECT id, status, remarks FROM profile_update_requests WHERE employee_id = ? AND status <> 'Pending' AND seen_by_employee = 0 ORDER BY reviewed_at DESC, created_at DESC LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        if (is_array($row)) {
            $decisionRequest = $row;
        }
    }
}

$photoDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profile_photos';
if (!is_dir($photoDir)) {
    @mkdir($photoDir, 0775, true);
}

$avatarUrl = 'https://api.dicebear.com/7.x/adventurer/svg?seed=Alex';
if (is_int($employeeId)) {
    $existing = glob($photoDir . DIRECTORY_SEPARATOR . 'employee_' . $employeeId . '.*');
    if (is_array($existing) && count($existing) > 0) {
        $base = basename((string)$existing[0]);
        $avatarUrl = 'uploads/profile_photos/' . $base;
    }
}

if (isset($_GET['photo']) && (string)$_GET['photo'] === '1') {
    $success_message = 'Profile photo updated.';
}

if (isset($_GET['requested']) && (string)$_GET['requested'] === '1') {
    $success_message = 'Your request has been submitted for approval.';
}

if (isset($_GET['pending']) && (string)$_GET['pending'] === '1') {
    $error_message = 'You already have a pending request. Please wait for approval.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    if (!is_int($employeeId)) {
        $error_message = 'Unable to identify employee. Please login again.';
    } elseif (!isset($_FILES['profile_photo']) || !is_array($_FILES['profile_photo'])) {
        $error_message = 'No file uploaded.';
    } else {
        $err = (int)($_FILES['profile_photo']['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            $uploadErrorMessages = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the server limit (upload_max_filesize).',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the form limit (MAX_FILE_SIZE).',
                UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk on the server.',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by a PHP extension on the server.',
            ];
            $error_message = $uploadErrorMessages[$err] ?? ('File upload error (code ' . $err . ').');
        } else {
            $tmp = (string)($_FILES['profile_photo']['tmp_name'] ?? '');
            $orig = (string)($_FILES['profile_photo']['name'] ?? 'photo');
            $size = (int)($_FILES['profile_photo']['size'] ?? 0);

            if ($tmp === '' || !is_uploaded_file($tmp)) {
                $error_message = 'Upload validation failed. Please try again.';
            } elseif ($size > 2 * 1024 * 1024) {
                $error_message = 'File too large. Maximum is 2MB.';
            } else {
                $ext = strtolower((string)pathinfo($orig, PATHINFO_EXTENSION));
                $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($ext, $allowedExt, true)) {
                    $error_message = 'Unsupported file type. Please upload JPG, PNG, GIF, or WEBP.';
                } else {
                    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
                    $mime = $finfo ? (string)finfo_file($finfo, $tmp) : '';
                    if ($finfo) {
                        finfo_close($finfo);
                    }
                    if ($mime !== '' && !str_starts_with($mime, 'image/')) {
                        $error_message = 'Invalid image file.';
                    } else {
                        $existing = glob($photoDir . DIRECTORY_SEPARATOR . 'employee_' . $employeeId . '.*');
                        if (is_array($existing)) {
                            foreach ($existing as $p) {
                                if (is_string($p) && is_file($p)) {
                                    @unlink($p);
                                }
                            }
                        }

                        $fileName = 'employee_' . $employeeId . '.' . $ext;
                        $dest = $photoDir . DIRECTORY_SEPARATOR . $fileName;
                        if (!@move_uploaded_file($tmp, $dest)) {
                            $error_message = 'Failed to save uploaded file. Please try again.';
                        } else {
                            header('Location: ' . basename((string)$_SERVER['PHP_SELF']) . '?photo=1');
                            exit;
                        }
                    }
                }
            }
        }
    }
}

$user = [
    'name' => 'Alex Johnson',
    'first_name' => 'Alex',
    'middle_name' => '',
    'last_name' => 'Johnson',
    'suffix' => '',
    'position' => 'Design & Product Team',
    'role' => 'Senior UX Designer',
    'access_role' => '',
    'location' => 'San Francisco, CA',
    'joined' => 'Joined Jan 12, 2023',
    'email' => 'alex.j@company.com',
    'phone' => '+1 (555) 123-4567',
    'work_email' => 'alex.j@company.com',
    'work_location' => 'San Francisco, USA',
    'gender' => '',
    'age' => '',
    'birthdate' => '',
    'civil_status' => '',
    'nationality' => '',
    'emp_id' => '#99214-B',
    'department' => 'Product Eng.',
    'department_no' => '',
    'status' => 'Full-Time',
    'emergency_name' => 'Jamie Johnson',
    'emergency_relationship' => 'Spouse'
];

if (is_array($emp)) {
    $user['first_name'] = (string)($emp['first_name'] ?? '');
    $user['middle_name'] = (string)($emp['middle_name'] ?? '');
    $user['last_name'] = (string)($emp['last_name'] ?? '');
    $user['suffix'] = (string)($emp['suffix'] ?? '');

    $nameParts = [];
    $ln = trim($user['last_name']);
    $fn = trim($user['first_name']);
    $mn = trim($user['middle_name']);
    $sx = trim($user['suffix']);
    if ($ln !== '') {
        $nameParts[] = $ln;
    }
    if ($fn !== '') {
        $nameParts[] = $fn;
    }
    if ($mn !== '') {
        $nameParts[] = $mn;
    }
    if ($sx !== '') {
        $nameParts[] = $sx;
    }
    $user['name'] = count($nameParts) > 0 ? implode(', ', $nameParts) : (string)($emp['employee_no'] ?? 'Employee');

    $user['position'] = (string)($emp['position'] ?? '');
    $user['role'] = (string)($emp['department'] ?? '');
    $user['access_role'] = (string)($_SESSION['role'] ?? '');
    $user['work_email'] = (string)($emp['email'] ?? '');
    $user['email'] = (string)($emp['email'] ?? '');
    $user['department'] = (string)($emp['department'] ?? '');
    $user['department_no'] = (string)($_SESSION['Dept_id'] ?? '');
    $user['emp_id'] = (string)($emp['employee_no'] ?? '');
    $user['status'] = (string)($emp['status'] ?? 'Active');
}

if ($conn && is_int($employeeId)) {
    $stmt = mysqli_prepare($conn, 'SELECT phone, work_location, gender, age, birthdate, civil_status, nationality, emergency_name, emergency_relationship FROM employee_profiles WHERE employee_id = ? LIMIT 1');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        if (is_array($row)) {
            $user['phone'] = (string)($row['phone'] ?? $user['phone']);
            $user['work_location'] = (string)($row['work_location'] ?? $user['work_location']);
            $user['gender'] = (string)($row['gender'] ?? $user['gender']);
            $user['age'] = (string)($row['age'] ?? $user['age']);
            $user['birthdate'] = (string)($row['birthdate'] ?? $user['birthdate']);
            $user['civil_status'] = (string)($row['civil_status'] ?? $user['civil_status']);
            $user['nationality'] = (string)($row['nationality'] ?? $user['nationality']);
            $user['emergency_name'] = (string)($row['emergency_name'] ?? $user['emergency_name']);
            $user['emergency_relationship'] = (string)($row['emergency_relationship'] ?? $user['emergency_relationship']);
        }
    }
}

$user['location'] = $user['work_location'] !== '' ? $user['work_location'] : $user['location'];

$editGranted = (bool)($_SESSION['profile_edit_granted'] ?? false);
$allowedEditFields = (array)($_SESSION['profile_edit_fields'] ?? []);
$allowedEditFields = array_values(array_unique(array_map('strval', $allowedEditFields)));
$surnameGranted = (bool)($_SESSION['profile_surname_granted'] ?? false);

$canEdit = static fn(string $k) => in_array($k, $allowedEditFields, true);

$canEditFirstName = $editGranted && $canEdit('first_name');
$canEditWorkEmail = $editGranted && $canEdit('work_email');
$canEditPhone = $editGranted && $canEdit('phone');
$canEditLocation = $editGranted && $canEdit('location');
$canEditBirthdate = $editGranted && $canEdit('birthdate');
$canEditNationality = $editGranted && $canEdit('nationality');
$canEditGender = $editGranted && $canEdit('gender');
$canEditCivilStatus = $editGranted && $canEdit('civil_status');
$canEditEmergency = $editGranted && $canEdit('emergency_contact');

$canEditMiddleName = $editGranted && ($canEdit('middle_name') || $surnameGranted);
$canEditLastName = $editGranted && $surnameGranted;

$birthdateValue = '';
if ((string)$user['birthdate'] !== '') {
    $ts = strtotime((string)$user['birthdate']);
    $birthdateValue = $ts ? date('Y-m-d', $ts) : '';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profile Management</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="flex h-screen">
    <?php include '../USM/sidebarr.php'; ?>

    <div class="flex flex-col flex-1 overflow-auto">
      <?php include '../USM/navbar.php'; ?>

      <main class="flex-1 p-4 md:p-6">
        <div class="max-w-6xl mx-auto">
          <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Profile Management</h1>
            <p class="text-gray-600">Manage your account information.</p>
          </div>

          <?php if ($success_message !== ''): ?>
            <div class="alert alert-success mb-6">
              <i data-lucide="check-circle" class="w-5 h-5"></i>
              <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
          <?php endif; ?>

          <?php if ($error_message !== ''): ?>
            <div class="alert alert-error mb-6">
              <i data-lucide="alert-triangle" class="w-5 h-5"></i>
              <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
          <?php endif; ?>

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="space-y-6">
              <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                  <div class="flex items-start gap-4">
                    <div class="relative">
                      <div class="avatar">
                        <div class="w-20 rounded-full bg-base-200">
                          <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar" />
                        </div>
                      </div>
                      <button id="changePhotoBtn" class="btn btn-sm btn-circle btn-primary absolute -bottom-2 -right-2" type="button" aria-label="Change photo">
                        <i data-lucide="camera" class="w-4 h-4"></i>
                      </button>

                      <form id="photoForm" method="POST" enctype="multipart/form-data" class="hidden">
                        <input type="hidden" name="upload_photo" value="1" />
                        <input id="photoInput" name="profile_photo" type="file" accept="image/*" />
                      </form>
                    </div>

                    <div class="flex-1">
                      <div class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
                      <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['position']); ?></div>
                    </div>
                  </div>

                  <div class="divider my-2"></div>

                  <div class="space-y-3 text-sm">
                    <div class="flex items-center gap-2 text-gray-700">
                      <i data-lucide="briefcase" class="w-4 h-4 text-blue-600"></i>
                      <span><?php echo htmlspecialchars($user['role']); ?></span>
                    </div>
                    <?php if (trim((string)$user['department_no']) !== ''): ?>
                    <div class="flex items-center gap-2 text-gray-700">
                      <i data-lucide="hash" class="w-4 h-4 text-slate-600"></i>
                      <span><?php echo htmlspecialchars((string)$user['department_no']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (trim((string)$user['access_role']) !== ''): ?>
                    <div class="flex items-center gap-2 text-gray-700">
                      <i data-lucide="user" class="w-4 h-4 text-indigo-600"></i>
                      <span><?php echo htmlspecialchars((string)$user['access_role']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex items-center gap-2 text-gray-700">
                      <i data-lucide="map-pin" class="w-4 h-4 text-green-600"></i>
                      <span><?php echo htmlspecialchars($user['location']); ?></span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-700">
                      <i data-lucide="calendar" class="w-4 h-4 text-purple-600"></i>
                      <span><?php echo htmlspecialchars($user['joined']); ?></span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                  <h2 class="card-title">Employment Data</h2>
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div>
                      <div class="text-xs font-semibold text-gray-500">EMP ID</div>
                      <div class="mt-1 font-semibold text-gray-900"><?php echo htmlspecialchars($user['emp_id']); ?></div>
                    </div>
                    <div>
                      <div class="text-xs font-semibold text-gray-500">DEPARTMENT</div>
                      <div class="mt-1 font-semibold text-gray-900"><?php echo htmlspecialchars($user['department']); ?></div>
                    </div>
                    <div>
                      <div class="text-xs font-semibold text-gray-500">STATUS</div>
                      <div class="mt-1 font-semibold text-gray-900"><?php echo htmlspecialchars($user['status']); ?></div>
                    </div>
                  </div>

                  <div class="mt-4 flex items-start gap-2 text-sm text-amber-700">
                    <i data-lucide="info" class="w-4 h-4 mt-0.5"></i>
                    <div>To change employment details, please contact HR administrator.</div>
                  </div>
                </div>
              </div>

            </div>

            <div class="lg:col-span-2 space-y-6">
              <dialog id="requestEditModal" class="modal">
                <div class="modal-box w-11/12 max-w-xl">
                  <div class="flex items-start justify-between gap-3">
                    <div>
                      <h3 class="font-bold text-lg">Request to Edit Personal Info</h3>
                      <p class="text-sm text-gray-500">Please select a reason and upload proof/basis.</p>
                    </div>
                    <form method="dialog">
                      <button class="btn btn-sm btn-ghost" aria-label="Close">
                        <i data-lucide="x" class="w-4 h-4"></i>
                      </button>
                    </form>
                  </div>

                  <form method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                    <input type="hidden" name="request_edit_access" value="1" />

                    <div class="form-control">
                      <label class="label"><span class="label-text">Reason</span></label>
                      <select id="reasonChoice" name="reason_choice" class="select select-bordered" required>
                        <option value="" disabled selected>Select reason</option>
                        <option value="Updated Personal Details">Updated Personal Details</option>
                        <option value="Legal Change of Information">Legal Change of Information</option>
                        <option value="change of surname">change of surname</option>
                      </select>
                    </div>

                    <div id="requestedFieldsWrap" class="form-control hidden">
                      <label class="label"><span class="label-text">What do you want to change?</span></label>
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <label class="flex items-center gap-2">
                          <input type="checkbox" name="requested_fields[]" value="gender" class="checkbox checkbox-sm" />
                          <span>Gender</span>
                        </label>
                        <label class="flex items-center gap-2">
                          <input type="checkbox" name="requested_fields[]" value="civil_status" class="checkbox checkbox-sm" />
                          <span>Civil Status</span>
                        </label>
                        <label class="flex items-center gap-2">
                          <input type="checkbox" name="requested_fields[]" value="location" class="checkbox checkbox-sm" />
                          <span>Location</span>
                        </label>
                        <label class="flex items-center gap-2">
                          <input type="checkbox" name="requested_fields[]" value="work_email" class="checkbox checkbox-sm" />
                          <span>Work Email</span>
                        </label>
                        <label class="flex items-center gap-2">
                          <input type="checkbox" name="requested_fields[]" value="nationality" class="checkbox checkbox-sm" />
                          <span>Nationality</span>
                        </label>
                        <label class="flex items-center gap-2">
                          <input type="checkbox" name="requested_fields[]" value="phone" class="checkbox checkbox-sm" />
                          <span>Phone Number</span>
                        </label>
                        <label class="flex items-center gap-2">
                          <input type="checkbox" name="requested_fields[]" value="emergency_contact" class="checkbox checkbox-sm" />
                          <span>Emergency Contact</span>
                        </label>
                        <label class="flex items-center gap-2">
                          <input type="checkbox" name="requested_fields[]" value="first_name" class="checkbox checkbox-sm" />
                          <span>First Name</span>
                        </label>
                        <label class="flex items-center gap-2">
                          <input type="checkbox" name="requested_fields[]" value="middle_name" class="checkbox checkbox-sm" />
                          <span>Middle Name</span>
                        </label>
                        <label class="flex items-center gap-2">
                          <input type="checkbox" name="requested_fields[]" value="birthdate" class="checkbox checkbox-sm" />
                          <span>Birthdate</span>
                        </label>
                      </div>
                      <div class="mt-2 text-xs text-gray-500">Age will be calculated automatically from birthdate.</div>
                    </div>

                    <div class="form-control">
                      <label class="label"><span class="label-text">Reason details</span></label>
                      <textarea name="reason_text" class="textarea textarea-bordered" placeholder="Write your reason..." required></textarea>
                    </div>

                    <div class="form-control">
                      <label class="label"><span class="label-text">Proof/Basis file</span></label>
                      <input name="proof_file" type="file" class="file-input file-input-bordered w-full" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,image/*" required />
                    </div>

                    <div class="modal-action">
                      <button type="submit" class="btn btn-primary">Submit Request</button>
                      <form method="dialog"><button class="btn" type="submit">Cancel</button></form>
                    </div>
                  </form>
                </div>
                <form method="dialog" class="modal-backdrop"><button>close</button></form>
              </dialog>

              <form id="profileDataForm" method="POST">
                <input type="hidden" name="save_profile_changes" value="1" />

              <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                  <div class="flex items-center justify-between">
                    <div>
                      <h2 class="card-title">Personal Information</h2>
                      <p class="text-sm text-gray-500">Update your personal details.</p>
                    </div>
                    <div class="flex items-center gap-2">
                      <?php if ($editGranted): ?>
                        <span class="badge badge-success">Approved to Edit</span>
                      <?php elseif ($hasPendingProfileEditRequest): ?>
                        <span class="badge badge-warning">Pending Approval</span>
                      <?php else: ?>
                        <span class="badge badge-ghost">Restricted</span>
                      <?php endif; ?>

                      <?php if (!$editGranted && !$hasPendingProfileEditRequest): ?>
                        <button id="requestEditBtn" class="btn btn-outline btn-sm" type="button">Request</button>
                      <?php endif; ?>

                      <button id="editProfileBtn" class="btn btn-ghost btn-sm" type="button">
                        <i data-lucide="pencil" class="w-4 h-4"></i>
                        <span class="ml-2">Edit</span>
                      </button>
                    </div>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">LAST NAME</span></label>
                      <input
                        name="last_name"
                        class="profile-edit-field input input-bordered"
                        value="<?php echo htmlspecialchars((string)$user['last_name']); ?>"
                        data-editable="<?php echo $canEditLastName ? '1' : '0'; ?>"
                        <?php echo $canEditLastName ? '' : 'disabled'; ?>
                      />
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">FIRST NAME</span></label>
                      <input
                        name="first_name"
                        class="profile-edit-field input input-bordered"
                        value="<?php echo htmlspecialchars((string)$user['first_name']); ?>"
                        data-editable="<?php echo $canEditFirstName ? '1' : '0'; ?>"
                        <?php echo $canEditFirstName ? '' : 'disabled'; ?>
                      />
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">MIDDLE NAME</span></label>
                      <input
                        name="middle_name"
                        class="profile-edit-field input input-bordered"
                        value="<?php echo htmlspecialchars((string)$user['middle_name']); ?>"
                        data-editable="<?php echo $canEditMiddleName ? '1' : '0'; ?>"
                        <?php echo $canEditMiddleName ? '' : 'disabled'; ?>
                      />
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">SUFFIX</span></label>
                      <input class="input input-bordered" value="<?php echo htmlspecialchars((string)$user['suffix']); ?>" disabled />
                    </div>

                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">WORK EMAIL</span></label>
                      <input
                        name="work_email"
                        class="profile-edit-field input input-bordered"
                        value="<?php echo htmlspecialchars($user['work_email']); ?>"
                        data-editable="<?php echo $canEditWorkEmail ? '1' : '0'; ?>"
                        <?php echo $canEditWorkEmail ? '' : 'disabled'; ?>
                      />
                    </div>

                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">PHONE NUMBER</span></label>
                      <input
                        name="phone"
                        class="profile-edit-field input input-bordered"
                        value="<?php echo htmlspecialchars($user['phone']); ?>"
                        data-editable="<?php echo $canEditPhone ? '1' : '0'; ?>"
                        <?php echo $canEditPhone ? '' : 'disabled'; ?>
                      />
                    </div>

                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">LOCATION</span></label>
                      <input
                        name="work_location"
                        class="profile-edit-field input input-bordered"
                        value="<?php echo htmlspecialchars($user['work_location']); ?>"
                        data-editable="<?php echo $canEditLocation ? '1' : '0'; ?>"
                        <?php echo $canEditLocation ? '' : 'disabled'; ?>
                      />
                    </div>

                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">GENDER</span></label>
                      <input
                        name="gender"
                        class="profile-edit-field input input-bordered"
                        value="<?php echo htmlspecialchars($user['gender']); ?>"
                        data-editable="<?php echo $canEditGender ? '1' : '0'; ?>"
                        <?php echo $canEditGender ? '' : 'disabled'; ?>
                      />
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">AGE</span></label>
                      <input class="input input-bordered" value="<?php echo htmlspecialchars($user['age']); ?>" disabled />
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">BIRTHDATE</span></label>
                      <input
                        name="birthdate"
                        type="date"
                        class="profile-edit-field input input-bordered"
                        value="<?php echo htmlspecialchars($birthdateValue); ?>"
                        data-editable="<?php echo $canEditBirthdate ? '1' : '0'; ?>"
                        <?php echo $canEditBirthdate ? '' : 'disabled'; ?>
                      />
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">CIVIL STATUS</span></label>
                      <input
                        name="civil_status"
                        class="profile-edit-field input input-bordered"
                        value="<?php echo htmlspecialchars($user['civil_status']); ?>"
                        data-editable="<?php echo $canEditCivilStatus ? '1' : '0'; ?>"
                        <?php echo $canEditCivilStatus ? '' : 'disabled'; ?>
                      />
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">NATIONALITY</span></label>
                      <input
                        name="nationality"
                        class="profile-edit-field input input-bordered"
                        value="<?php echo htmlspecialchars($user['nationality']); ?>"
                        data-editable="<?php echo $canEditNationality ? '1' : '0'; ?>"
                        <?php echo $canEditNationality ? '' : 'disabled'; ?>
                      />
                    </div>
                  </div>
                </div>
              </div>

              <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                  <h2 class="card-title">Emergency Contact</h2>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">NAME</span></label>
                      <input
                        name="emergency_name"
                        class="profile-edit-field input input-bordered"
                        value="<?php echo htmlspecialchars($user['emergency_name']); ?>"
                        data-editable="<?php echo $canEditEmergency ? '1' : '0'; ?>"
                        <?php echo $canEditEmergency ? '' : 'disabled'; ?>
                      />
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">RELATIONSHIP</span></label>
                      <input
                        name="emergency_relationship"
                        class="profile-edit-field input input-bordered"
                        value="<?php echo htmlspecialchars($user['emergency_relationship']); ?>"
                        data-editable="<?php echo $canEditEmergency ? '1' : '0'; ?>"
                        <?php echo $canEditEmergency ? '' : 'disabled'; ?>
                      />
                    </div>
                  </div>

                  <div class="mt-6 flex justify-end gap-3">
                    <button id="discardChangesBtn" class="btn btn-ghost" type="button" <?php echo $editGranted ? '' : 'disabled'; ?>>Discard</button>
                    <button id="saveChangesBtn" class="btn btn-primary" type="submit" <?php echo $editGranted ? '' : 'disabled'; ?>>Save Changes</button>
                  </div>
                </div>
              </div>
              </form>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script>
    lucide.createIcons();

    const profileDecision = <?php echo json_encode($decisionRequest, JSON_UNESCAPED_SLASHES); ?>;
    const profileEditGranted = <?php echo $editGranted ? 'true' : 'false'; ?>;
    const profileHasPending = <?php echo $hasPendingProfileEditRequest ? 'true' : 'false'; ?>;

    const changePhotoBtn = document.getElementById('changePhotoBtn');
    const photoInput = document.getElementById('photoInput');
    const photoForm = document.getElementById('photoForm');

    if (changePhotoBtn && photoInput && photoForm) {
      changePhotoBtn.addEventListener('click', () => photoInput.click());
      photoInput.addEventListener('change', () => {
        if (photoInput.files && photoInput.files.length > 0) {
          photoForm.submit();
        }
      });
    }

    const editBtn = document.getElementById('editProfileBtn');
    const requestBtn = document.getElementById('requestEditBtn');
    const editableFields = document.querySelectorAll('.profile-edit-field');
    const saveBtn = document.getElementById('saveChangesBtn');
    const discardBtn = document.getElementById('discardChangesBtn');
    const requestModal = document.getElementById('requestEditModal');
    const reasonChoice = document.getElementById('reasonChoice');
    const requestedFieldsWrap = document.getElementById('requestedFieldsWrap');

    function setEditing(enabled) {
      editableFields.forEach((el) => {
        const editable = (el.getAttribute('data-editable') || '0') === '1';
        el.disabled = !(enabled && editable);
      });
      if (saveBtn) saveBtn.disabled = !enabled;
      if (discardBtn) discardBtn.disabled = !enabled;
    }

    setEditing(profileEditGranted);

    async function showDecisionIfAny() {
      if (!profileDecision || !profileDecision.id) return;

      const status = (profileDecision.status || '').toLowerCase();
      const remarks = profileDecision.remarks || '';

      if (status === 'approved') {
        await Swal.fire({
          icon: 'success',
          title: 'Approved',
          text: remarks ? ('Your request was approved: ' + remarks) : 'Your request was approved. You can now edit your information.',
        });
      } else if (status === 'rejected') {
        await Swal.fire({
          icon: 'error',
          title: 'Rejected',
          text: remarks ? ('Your request was rejected: ' + remarks) : 'Your request was rejected.',
        });
      } else {
        return;
      }

      const url = new URL(window.location.href);
      url.searchParams.set('ack', profileDecision.id);
      window.location.href = url.toString();
    }

    document.addEventListener('DOMContentLoaded', showDecisionIfAny);

    async function submitRequestFlow() {
      if (profileEditGranted) {
        setEditing(true);
        return;
      }

      if (profileHasPending) {
        await Swal.fire({
          icon: 'info',
          title: 'Request already submitted',
          text: 'Please wait for approval before requesting again.',
        });
        return;
      }

      if (requestModal && typeof requestModal.showModal === 'function') {
        requestModal.showModal();
      }
    }

    if (requestBtn) {
      requestBtn.addEventListener('click', submitRequestFlow);
    }

    function updateRequestedFieldsVisibility() {
      if (!reasonChoice || !requestedFieldsWrap) return;
      const isUpdated = (reasonChoice.value || '') === 'Updated Personal Details';
      if (isUpdated) {
        requestedFieldsWrap.classList.remove('hidden');
      } else {
        requestedFieldsWrap.classList.add('hidden');
        requestedFieldsWrap.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
          cb.checked = false;
        });
      }
    }

    if (reasonChoice) {
      reasonChoice.addEventListener('change', updateRequestedFieldsVisibility);
      document.addEventListener('DOMContentLoaded', updateRequestedFieldsVisibility);
    }

    if (editBtn) {
      editBtn.addEventListener('click', async () => {
        await submitRequestFlow();
      });
    }

    if (discardBtn) {
      discardBtn.addEventListener('click', () => {
        window.location.reload();
      });
    }
  </script>
</body>
</html>
