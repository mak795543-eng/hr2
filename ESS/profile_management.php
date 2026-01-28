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
        $stmt = mysqli_prepare($conn, 'SELECT id, status FROM profile_update_requests WHERE id = ? AND employee_id = ? LIMIT 1');
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
                }
            }
        }

        header('Location: ' . basename((string)$_SERVER['PHP_SELF']));
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_edit_access'])) {
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

        $payload = json_encode([
            'type' => 'edit_access',
            'requested_at' => date('c'),
        ], JSON_UNESCAPED_SLASHES);
        if (!is_string($payload) || $payload === '') {
            $payload = '{"type":"edit_access"}';
        }

        $stmt = mysqli_prepare($conn, 'INSERT INTO profile_update_requests (employee_id, requested_data, status) VALUES (?, ?, \'Pending\')');
        if (!$stmt) {
            $error_message = 'Failed to submit request. Please try again.';
        } else {
            mysqli_stmt_bind_param($stmt, 'is', $employeeId, $payload);
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

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile_changes'])) {
        $granted = (bool)($_SESSION['profile_edit_granted'] ?? false);
        if (!$granted) {
            $error_message = 'Editing is restricted. Please request approval first.';
        } else {
            $fullName = trim((string)($_POST['full_name'] ?? ''));
            $workEmail = trim((string)($_POST['work_email'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $workLocation = trim((string)($_POST['work_location'] ?? ''));
            $emergencyName = trim((string)($_POST['emergency_name'] ?? ''));
            $emergencyRelationship = trim((string)($_POST['emergency_relationship'] ?? ''));

            if ($fullName === '' || $workEmail === '') {
                $error_message = 'Full name and work email are required.';
            } else {
                $parts = ess_split_name($fullName);
                $stmt = mysqli_prepare($conn, 'UPDATE employees SET first_name = ?, last_name = ?, email = ? WHERE id = ?');
                if (!$stmt) {
                    $error_message = 'Failed to save profile.';
                } else {
                    mysqli_stmt_bind_param($stmt, 'sssi', $parts['first'], $parts['last'], $workEmail, $employeeId);
                    $ok = mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    if (!$ok) {
                        $error_message = 'Failed to save profile. Email may already be used.';
                    } else {
                        $stmt2 = mysqli_prepare(
                            $conn,
                            'INSERT INTO employee_profiles (employee_id, phone, work_location, emergency_name, emergency_relationship) VALUES (?, ?, ?, ?, ?) '
                            . 'ON DUPLICATE KEY UPDATE phone = VALUES(phone), work_location = VALUES(work_location), emergency_name = VALUES(emergency_name), emergency_relationship = VALUES(emergency_relationship)'
                        );
                        if ($stmt2) {
                            mysqli_stmt_bind_param($stmt2, 'issss', $employeeId, $phone, $workLocation, $emergencyName, $emergencyRelationship);
                            mysqli_stmt_execute($stmt2);
                            mysqli_stmt_close($stmt2);
                        }

                        $_SESSION['profile_edit_granted'] = false;
                        $success_message = 'Profile updated successfully.';

                        $emp = ess_ensure_employee($conn);
                    }
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
    'position' => 'Design & Product Team',
    'role' => 'Senior UX Designer',
    'location' => 'San Francisco, CA',
    'joined' => 'Joined Jan 12, 2023',
    'email' => 'alex.j@company.com',
    'phone' => '+1 (555) 123-4567',
    'work_email' => 'alex.j@company.com',
    'work_location' => 'San Francisco, USA',
    'emp_id' => '#99214-B',
    'department' => 'Product Eng.',
    'status' => 'Full-Time',
    'emergency_name' => 'Jamie Johnson',
    'emergency_relationship' => 'Spouse'
];

if (is_array($emp)) {
    $fullName = trim(((string)($emp['first_name'] ?? '')) . ' ' . ((string)($emp['last_name'] ?? '')));
    if ($fullName === '') {
        $fullName = (string)($emp['employee_no'] ?? 'Employee');
    }

    $user['name'] = $fullName;
    $user['position'] = (string)($emp['department'] ?? '');
    $user['role'] = (string)($emp['position'] ?? '');
    $user['work_email'] = (string)($emp['email'] ?? '');
    $user['email'] = (string)($emp['email'] ?? '');
    $user['department'] = (string)($emp['department'] ?? '');
    $user['emp_id'] = (string)($emp['employee_no'] ?? '');
    $user['status'] = (string)($emp['status'] ?? 'Active');
}

if ($conn && is_int($employeeId)) {
    $stmt = mysqli_prepare($conn, 'SELECT phone, work_location, emergency_name, emergency_relationship FROM employee_profiles WHERE employee_id = ? LIMIT 1');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        if (is_array($row)) {
            $user['phone'] = (string)($row['phone'] ?? $user['phone']);
            $user['work_location'] = (string)($row['work_location'] ?? $user['work_location']);
            $user['emergency_name'] = (string)($row['emergency_name'] ?? $user['emergency_name']);
            $user['emergency_relationship'] = (string)($row['emergency_relationship'] ?? $user['emergency_relationship']);
        }
    }
}

$editGranted = (bool)($_SESSION['profile_edit_granted'] ?? false);
$fieldsDisabledAttr = $editGranted ? '' : 'disabled';
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
            <p class="text-gray-600">Manage your account information and security settings.</p>
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
                  <div class="flex items-center gap-2">
                    <i data-lucide="shield" class="w-5 h-5 text-blue-600"></i>
                    <h2 class="card-title text-base">Security</h2>
                  </div>
                  <div class="mt-4 space-y-2">
                    <button class="btn btn-outline btn-sm w-full justify-start" type="button">
                      <i data-lucide="key" class="w-4 h-4"></i>
                      <span class="ml-2">Change Password</span>
                    </button>
                    <button class="btn btn-outline btn-sm w-full justify-start" type="button">
                      <i data-lucide="smartphone" class="w-4 h-4"></i>
                      <span class="ml-2">Two-Factor Auth</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
              <form id="requestEditForm" method="POST" class="hidden">
                <input type="hidden" name="request_edit_access" value="1" />
              </form>

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
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">FULL NAME</span></label>
                      <input name="full_name" class="profile-edit-field input input-bordered" value="<?php echo htmlspecialchars($user['name']); ?>" <?php echo $fieldsDisabledAttr; ?> />
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">WORK EMAIL</span></label>
                      <input name="work_email" class="profile-edit-field input input-bordered" value="<?php echo htmlspecialchars($user['work_email']); ?>" <?php echo $fieldsDisabledAttr; ?> />
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">PHONE NUMBER</span></label>
                      <input name="phone" class="profile-edit-field input input-bordered" value="<?php echo htmlspecialchars($user['phone']); ?>" <?php echo $fieldsDisabledAttr; ?> />
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">LOCATION</span></label>
                      <input name="work_location" class="profile-edit-field input input-bordered" value="<?php echo htmlspecialchars($user['work_location']); ?>" <?php echo $fieldsDisabledAttr; ?> />
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

              <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                  <h2 class="card-title">Emergency Contact</h2>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">NAME</span></label>
                      <input name="emergency_name" class="profile-edit-field input input-bordered" value="<?php echo htmlspecialchars($user['emergency_name']); ?>" <?php echo $fieldsDisabledAttr; ?> />
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">RELATIONSHIP</span></label>
                      <input name="emergency_relationship" class="profile-edit-field input input-bordered" value="<?php echo htmlspecialchars($user['emergency_relationship']); ?>" <?php echo $fieldsDisabledAttr; ?> />
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
    const requestEditForm = document.getElementById('requestEditForm');

    function setEditing(enabled) {
      editableFields.forEach((el) => {
        el.disabled = !enabled;
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

      const res = await Swal.fire({
        icon: 'info',
        title: 'Request required',
        text: 'You need to request first before editing your profile information (except profile photo).',
        showCancelButton: true,
        confirmButtonText: 'Submit Request',
        cancelButtonText: 'Cancel'
      });

      if (res.isConfirmed) {
        if (requestEditForm) {
          requestEditForm.submit();
        }
      }
    }

    if (requestBtn) {
      requestBtn.addEventListener('click', submitRequestFlow);
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
