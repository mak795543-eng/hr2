<?php
session_start();

if (!defined('SUPPRESS_DB_ERRORS')) {
    define('SUPPRESS_DB_ERRORS', true);
}

$appBasePath = getenv('APP_BASE_PATH') ?: '/hr2/';

require_once __DIR__ . '/../../db.php';

$preferredDbNames = ['hr2usm', 'rest_core_2_usm', 'hr2_usmhr2', 'hr2_soliera_usm'];
$conn = null;
foreach ($preferredDbNames as $dbName) {
    if (isset($connections[$dbName]) && $connections[$dbName] instanceof mysqli) {
        $conn = $connections[$dbName];
        break;
    }
}

$employeeId = $_SESSION['employee_id'] ?? null;
if (!$employeeId) {
    header('Location: ' . $appBasePath . 'USM/index.php');
    exit();
}

$uploadMessage = '';
$uploadMessageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile_photo') {
    if (!$conn) {
        $uploadMessage = 'Database connection not available.';
        $uploadMessageType = 'error';
    } elseif (!isset($_FILES['profile_photo'])) {
        $uploadMessage = 'No file uploaded.';
        $uploadMessageType = 'error';
    } else {
        $file = $_FILES['profile_photo'];
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $uploadMessage = 'Upload failed.';
            $uploadMessageType = 'error';
        } elseif (!isset($file['size']) || (int)$file['size'] > 2 * 1024 * 1024) {
            $uploadMessage = 'File is too large. Max file size is 2MB.';
            $uploadMessageType = 'error';
        } else {
            $tmpName = (string)($file['tmp_name'] ?? '');
            $mime = '';
            if ($tmpName !== '' && is_file($tmpName)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mime = (string)finfo_file($finfo, $tmpName);
                    finfo_close($finfo);
                }
                if ($mime === '') {
                    $imgInfo = @getimagesize($tmpName);
                    if (is_array($imgInfo) && isset($imgInfo['mime'])) {
                        $mime = (string)$imgInfo['mime'];
                    }
                }
            }

            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
            ];

            if ($mime === '' || !isset($allowed[$mime])) {
                $uploadMessage = 'Invalid image type. Allowed: JPG, PNG, GIF, WEBP.';
                $uploadMessageType = 'error';
            } else {
                $ext = $allowed[$mime];
                $safeEmployeeId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$employeeId);
                $newFilename = time() . '_emp_' . $safeEmployeeId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $uploadDir = __DIR__ . '/../../USM/Profile_images/';
                $targetPath = $uploadDir . $newFilename;

                if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                    $uploadMessage = 'Upload folder is not writable: USM/Profile_images.';
                    $uploadMessageType = 'error';
                } elseif (!move_uploaded_file($tmpName, $targetPath)) {
                    $uploadMessage = 'Failed to save uploaded file.';
                    $uploadMessageType = 'error';
                } else {
                    $stmt = mysqli_prepare($conn, "UPDATE department_accounts SET image_url = ? WHERE employee_id = ? LIMIT 1");
                    if (!$stmt) {
                        $uploadMessage = 'Failed to update profile photo in database.';
                        $uploadMessageType = 'error';
                    } else {
                        mysqli_stmt_bind_param($stmt, 'ss', $newFilename, $employeeId);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);

                        header('Location: ' . $_SERVER['PHP_SELF'] . '?photo_updated=1');
                        exit();
                    }
                }
            }
        }
    }
}

$profile = null;
if ($conn) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM department_accounts WHERE employee_id = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $employeeId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $profile = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
    }
}

if (!is_array($profile)) {
    $profile = [
        'employee_id' => $employeeId,
        'employee_name' => $_SESSION['employee_name'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? '',
        'status' => '',
        'Dept_id' => $_SESSION['Dept_id'] ?? '',
        'dept_name' => '',
        'image_url' => '',
    ];
}

if (!isset($_SESSION['employee_name']) || trim((string)$_SESSION['employee_name']) === '') {
    if (isset($profile['employee_name']) && trim((string)$profile['employee_name']) !== '') {
        $_SESSION['employee_name'] = $profile['employee_name'];
    }
}

$displayName = trim((string)($profile['employee_name'] ?? ''));
$displayRole = trim((string)($profile['role'] ?? ($_SESSION['role'] ?? '')));
$displayDepartment = trim((string)($profile['dept_name'] ?? ''));
if ($displayDepartment === '') {
    $displayDepartment = trim((string)($profile['Dept_id'] ?? ($_SESSION['Dept_id'] ?? '')));
}
$displayStatus = trim((string)($profile['status'] ?? ''));

$imageFilename = '';
if (!empty($profile['image_url'])) {
    $imageFilename = (string)$profile['image_url'];
}

$avatarUrl = 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . rawurlencode($displayName !== '' ? $displayName : $employeeId);
if ($imageFilename !== '') {
    $avatarUrl = $appBasePath . 'USM/Profile_images/' . rawurlencode($imageFilename);
    $diskPath = __DIR__ . '/../../USM/Profile_images/' . $imageFilename;
    if (is_file($diskPath)) {
        $avatarUrl .= '?v=' . rawurlencode((string)filemtime($diskPath));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Profile | Hotel & Restaurant Organization</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #0864A6;
            --accent-color: #D4AF37;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 3px solid var(--primary-color);
        }
        
        .section-card {
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .section-card:hover {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .gold-accent {
            color: var(--accent-color);
        }
        
        .primary-bg {
            background-color: var(--primary-color);
        }
        
        .primary-text {
            color: var(--primary-color);
        }
        
        .skill-progress {
            height: 10px;
            border-radius: 5px;
        }
        
        .avatar-upload {
            position: relative;
            display: inline-block;
        }
        
        .avatar-upload .edit-icon {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            padding: 6px;
            font-size: 12px;
        }
        
        .document-icon {
            font-size: 2rem;
            color: var(--primary-color);
        }
        
        .badge-icon {
            font-size: 1.8rem;
            color: var(--accent-color);
        }
        
        /* Modal styling */
        .modal-box {
            max-width: 600px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php 
    // Use relative path or absolute path based on your directory structure
    include '../../USM/sidebarr.php'; 
    ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../../USM/navbar.php'; ?>
        
        <!-- Header Section -->
        <header class="profile-header rounded-xl p-6 mb-8">
            <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                <!-- Avatar -->
                <div class="avatar-upload">
                    <div class="avatar">
                        <div class="w-28 h-28 rounded-full ring ring-primary ring-offset-2 ring-offset-base-100">
                            <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Employee Avatar" />
                        </div>
                    </div>
                    <div class="edit-icon cursor-pointer" onclick="openEditModal()">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>
                
                <!-- Employee Info -->
                <div class="flex-1">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($displayName !== '' ? $displayName : $employeeId); ?></h1>
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <span class="badge badge-lg primary-bg text-white"><?php echo htmlspecialchars($displayRole !== '' ? $displayRole : ''); ?></span>
                                <span class="badge badge-lg bg-gray-200 text-gray-800"><?php echo htmlspecialchars($displayDepartment !== '' ? $displayDepartment : ''); ?></span>
                                <?php
                                $statusText = $displayStatus !== '' ? $displayStatus : 'active';
                                $statusNorm = strtolower(trim((string)$statusText));
                                $statusClass = 'bg-green-100 text-green-800';
                                if ($statusNorm === 'inactive' || $statusNorm === 'disabled') {
                                    $statusClass = 'bg-red-100 text-red-800';
                                } elseif ($statusNorm === 'pending') {
                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                }
                                ?>
                                <span class="badge badge-lg <?php echo $statusClass; ?>">
                                    <i class="fas fa-circle text-xs mr-1"></i> <?php echo htmlspecialchars(ucfirst($statusText)); ?>
                                </span>
                            </div>
                            <p class="text-gray-600 mt-3">
                                <i class="fas fa-id-badge gold-accent mr-2"></i>
                                Employee ID: <span class="font-semibold"><?php echo htmlspecialchars($employeeId); ?></span>
                            </p>
                        </div>
                        
                        <div class="flex flex-col gap-2">
                            <button class="btn primary-bg text-white hover:bg-blue-800" onclick="openEditModal()">
                                <i class="fas fa-user-edit mr-2"></i> Edit Personal Info
                            </button>
                            <button class="btn btn-outline border-gray-300 text-gray-700">
                                <i class="fas fa-print mr-2"></i> Print Profile
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
          
        </header>
        
        <!-- Main Content -->
        <?php if ($uploadMessageType === 'error' && $uploadMessage !== ''): ?>
            <div class="alert alert-error mb-6">
                <span><?php echo htmlspecialchars($uploadMessage); ?></span>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['photo_updated']) && $_GET['photo_updated'] === '1'): ?>
            <div class="alert alert-success mb-6">
                <span>Profile photo updated successfully.</span>
            </div>
        <?php endif; ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Personal Information Section -->
                <section class="section-card bg-white rounded-xl p-6 shadow-md">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-user primary-text mr-3"></i>Personal Information
                        </h2>
                        <button class="btn btn-sm btn-outline border-primary text-primary hover:bg-blue-50" onclick="openEditModal()">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-gray-500">Full Name</span>
                                </label>
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <?php echo htmlspecialchars($displayName !== '' ? $displayName : $employeeId); ?>
                                </div>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-gray-500">Date of Birth</span>
                                </label>
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    October 15, 2003
                                </div>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-gray-500">Gender</span>
                                </label>
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    Male
                                </div>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-gray-500">Civil Status</span>
                                </label>
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    Married
                                </div>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-gray-500">Nationality</span>
                                </label>
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    American
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="space-y-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-gray-500">Contact Number</span>
                                    <span class="label-text-alt text-green-600 font-medium">Editable</span>
                                </label>
                                <div class="p-3 bg-white rounded-lg border border-gray-300">
                                    +63 9306652949
                                </div>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-gray-500">Personal Email</span>
                                    <span class="label-text-alt text-green-600 font-medium">Editable</span>
                                </label>
                                <div class="p-3 bg-white rounded-lg border border-gray-300">
                                    <?php echo htmlspecialchars((string)($profile['email'] ?? '')); ?>
                                </div>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-gray-500">Home Address</span>
                                    <span class="label-text-alt text-green-600 font-medium">Editable</span>
                                </label>
                                <div class="p-3 bg-white rounded-lg border border-gray-300">
                                    123 Hospitality Ave, Suite 45<br>New York, NY 10001
                                </div>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-gray-500">Emergency Contact</span>
                                    <span class="label-text-alt text-green-600 font-medium">Editable</span>
                                </label>
                                <div class="p-3 bg-white rounded-lg border border-gray-300">
                                    <div class="font-medium">Sarah Smith (Spouse)</div>
                                    <div class="text-gray-600">+1 (555) 987-6543</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Employment Information Section (Read-Only) -->
                <section class="section-card bg-white rounded-xl p-6 shadow-md">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-briefcase primary-text mr-3"></i>Employment Information
                        </h2>
                        <div class="badge badge-lg bg-gray-100 text-gray-700 border-0">
                            <i class="fas fa-lock mr-1 text-sm"></i> HR Controlled
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-gray-500">Employee ID</span>
                                </label>
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <?php echo htmlspecialchars($employeeId); ?>
                                </div>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-gray-500">Job Title / Position</span>
                                </label>
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <?php echo htmlspecialchars($displayRole !== '' ? $displayRole : ''); ?>
                                </div>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-gray-500">Department</span>
                                </label>
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <?php echo htmlspecialchars($displayDepartment !== '' ? $displayDepartment : ''); ?>
                                </div>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-gray-500">Employment Type</span>
                                </label>
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <span class="badge badge-success">Regular</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-gray-500">Date Hired</span>
                                </label>
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    June 15, 2019
                                </div>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-gray-500">Work Location / Branch</span>
                                </label>
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    Grand Plaza Hotel - Downtown
                                </div>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-gray-500">Supervisor / Manager</span>
                                </label>
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    Maria Rodriguez, F&B Director
                                </div>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-gray-500">Shift Type</span>
                                </label>
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    Flexible (Kitchen Operations)
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Training & Certifications Section -->
                <section class="section-card bg-white rounded-xl p-6 shadow-md">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-graduation-cap primary-text mr-3"></i>Training & Certifications
                    </h2>
                    
                    <div class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead>
                                <tr>
                                    <th>Training/Certification</th>
                                    <th>Status</th>
                                    <th>Completion Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="font-medium">Food Safety Manager Certification</div>
                                        <div class="text-sm text-gray-500">National Restaurant Association</div>
                                    </td>
                                    <td>
                                        <span class="badge badge-success">Completed</span>
                                    </td>
                                    <td>Nov 15, 2022</td>
                                    <td>
                                        <button class="btn btn-xs btn-outline border-primary text-primary">
                                            <i class="fas fa-download mr-1"></i> Certificate
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="font-medium">Advanced Culinary Techniques</div>
                                        <div class="text-sm text-gray-500">Culinary Institute of America</div>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">In Progress</span>
                                    </td>
                                    <td>Expected: Feb 2024</td>
                                    <td>
                                        <button class="btn btn-xs btn-outline border-gray-400 text-gray-700" disabled>
                                            <i class="fas fa-eye mr-1"></i> View
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="font-medium">Hospitality Leadership Program</div>
                                        <div class="text-sm text-gray-500">Cornell University Online</div>
                                    </td>
                                    <td>
                                        <span class="badge badge-warning">Expires Soon</span>
                                    </td>
                                    <td>Jan 10, 2021</td>
                                    <td>
                                        <button class="btn btn-xs btn-outline border-primary text-primary">
                                            <i class="fas fa-redo mr-1"></i> Renew
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-6 text-center">
                        <button class="btn btn-outline border-primary text-primary">
                            <i class="fas fa-external-link-alt mr-2"></i> View All Trainings
                        </button>
                    </div>
                </section>
                
                <!-- Documents & Attachments Section -->
                <section class="section-card bg-white rounded-xl p-6 shadow-md">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-folder-open primary-text mr-3"></i>Documents & Attachments
                        </h2>
                        <button class="btn btn-sm primary-bg text-white" onclick="openUploadModal()">
                            <i class="fas fa-upload mr-1"></i> Upload Document
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Document Cards -->
                        <div class="card bg-gray-50 border border-gray-200">
                            <div class="card-body p-4">
                                <div class="flex items-start gap-4">
                                    <div class="document-icon">
                                        <i class="fas fa-file-contract"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-bold text-gray-800">Employment Contract</h3>
                                        <p class="text-sm text-gray-500">Updated: June 15, 2019</p>
                                        <div class="mt-3">
                                            <span class="badge badge-sm bg-gray-200 text-gray-700 border-0">HR Uploaded</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-actions justify-end mt-4">
                                    <button class="btn btn-xs btn-outline border-gray-400 text-gray-700">
                                        <i class="fas fa-eye mr-1"></i> View
                                    </button>
                                    <button class="btn btn-xs btn-outline border-primary text-primary">
                                        <i class="fas fa-download mr-1"></i> Download
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card bg-gray-50 border border-gray-200">
                            <div class="card-body p-4">
                                <div class="flex items-start gap-4">
                                    <div class="document-icon">
                                        <i class="fas fa-id-card"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-bold text-gray-800">Driver's License</h3>
                                        <p class="text-sm text-gray-500">Updated: March 10, 2023</p>
                                        <div class="mt-3">
                                            <span class="badge badge-sm bg-blue-100 text-primary border-0">Personal Upload</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-actions justify-end mt-4">
                                    <button class="btn btn-xs btn-outline border-gray-400 text-gray-700">
                                        <i class="fas fa-eye mr-1"></i> View
                                    </button>
                                    <button class="btn btn-xs btn-outline border-primary text-primary">
                                        <i class="fas fa-download mr-1"></i> Download
                                    </button>
                                    <button class="btn btn-xs btn-outline border-red-400 text-red-500">
                                        <i class="fas fa-trash mr-1"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card bg-gray-50 border border-gray-200">
                            <div class="card-body p-4">
                                <div class="flex items-start gap-4">
                                    <div class="document-icon">
                                        <i class="fas fa-file-medical"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-bold text-gray-800">Medical Certificate</h3>
                                        <p class="text-sm text-gray-500">Updated: August 22, 2023</p>
                                        <div class="mt-3">
                                            <span class="badge badge-sm bg-blue-100 text-primary border-0">Personal Upload</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-actions justify-end mt-4">
                                    <button class="btn btn-xs btn-outline border-gray-400 text-gray-700">
                                        <i class="fas fa-eye mr-1"></i> View
                                    </button>
                                    <button class="btn btn-xs btn-outline border-primary text-primary">
                                        <i class="fas fa-download mr-1"></i> Download
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <p class="text-sm text-gray-500">
                            <i class="fas fa-info-circle gold-accent mr-1"></i>
                            You can upload personal documents (resume, updated IDs, certificates). HR-uploaded documents cannot be deleted.
                        </p>
                    </div>
                </section>
            </div>
            
            <!-- Right Column -->
            <div class="space-y-8">
                
                <!-- Skills & Competency Overview -->
                <section class="section-card bg-white rounded-xl p-6 shadow-md">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-chart-line primary-text mr-3"></i>Skills & Competency
                    </h2>
                    
                    <div class="space-y-5">
                        <!-- Skill Progress Bars -->
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="font-medium text-gray-700">Culinary Expertise</span>
                                <span class="font-bold primary-text">92%</span>
                            </div>
                            <progress class="progress progress-primary skill-progress" value="92" max="100"></progress>
                        </div>
                        
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="font-medium text-gray-700">Kitchen Management</span>
                                <span class="font-bold primary-text">85%</span>
                            </div>
                            <progress class="progress progress-primary skill-progress" value="85" max="100"></progress>
                        </div>
                        
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="font-medium text-gray-700">Team Leadership</span>
                                <span class="font-bold primary-text">78%</span>
                            </div>
                            <progress class="progress progress-primary skill-progress" value="78" max="100"></progress>
                        </div>
                        
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="font-medium text-gray-700">Food Safety Standards</span>
                                <span class="font-bold primary-text">95%</span>
                            </div>
                            <progress class="progress progress-primary skill-progress" value="95" max="100"></progress>
                        </div>
                        
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="font-medium text-gray-700">Menu Planning</span>
                                <span class="font-bold primary-text">88%</span>
                            </div>
                            <progress class="progress progress-primary skill-progress" value="88" max="100"></progress>
                        </div>
                    </div>
                    
                    <div class="mt-8 p-4 bg-blue-50 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-gray-500">Overall Competency Score</div>
                                <div class="text-3xl font-bold primary-text">87.6%</div>
                            </div>
                            <div class="text-4xl gold-accent">
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 text-center">
                        <button class="btn btn-outline border-primary text-primary btn-sm">
                            <i class="fas fa-external-link-alt mr-2"></i> View Detailed Report
                        </button>
                    </div>
                </section>
                
                <!-- Recognition & Achievements -->
                <section class="section-card bg-white rounded-xl p-6 shadow-md">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-trophy primary-text mr-3"></i>Recognition & Achievements
                    </h2>
                    
                    <div class="space-y-5">
                        <div class="flex items-start gap-4">
                            <div class="badge-icon">
                                <i class="fas fa-award"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800">Employee of the Quarter</h3>
                                <p class="text-sm text-gray-500">Q3 2023 • Food & Beverage Department</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="badge-icon">
                                <i class="fas fa-medal"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800">Perfect Safety Record</h3>
                                <p class="text-sm text-gray-500">3 Years • Zero Incidents</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="badge-icon">
                                <i class="fas fa-gem"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800">5-Year Service Milestone</h3>
                                <p class="text-sm text-gray-500">June 2024 (Upcoming)</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 text-center">
                        <button class="btn btn-outline border-primary text-primary btn-sm">
                            <i class="fas fa-external-link-alt mr-2"></i> View All Awards
                        </button>
                    </div>
                </section>
                
                <!-- Payroll & Compensation Snapshot -->
                <section class="section-card bg-white rounded-xl p-6 shadow-md">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-money-check-alt primary-text mr-3"></i>Payroll Snapshot
                    </h2>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span class="font-medium text-gray-700">Salary Grade</span>
                            <span class="font-bold primary-text">SG-12</span>
                        </div>
                        
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span class="font-medium text-gray-700">Last Pay Date</span>
                            <span class="font-bold primary-text">Oct 31, 2023</span>
                        </div>
                        
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span class="font-medium text-gray-700">YTD Earnings</span>
                            <span class="font-bold primary-text">$68,450.00</span>
                        </div>
                        
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span class="font-medium text-gray-700">Next Pay Date</span>
                            <span class="font-bold primary-text">Nov 15, 2023</span>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <button class="btn primary-bg text-white w-full">
                            <i class="fas fa-external-link-alt mr-2"></i> View Payroll Details
                        </button>
                    </div>
                </section>
                
                <!-- Account & Security Settings -->
                <section class="section-card bg-white rounded-xl p-6 shadow-md">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-user-shield primary-text mr-3"></i>Account & Security
                    </h2>
                    
                    <div class="space-y-4">
                        <button class="btn btn-outline border-primary text-primary w-full justify-start">
                            <i class="fas fa-key mr-3"></i> Change Password
                        </button>
                        
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <div>
                                <div class="font-medium text-gray-700">Two-Factor Authentication</div>
                                <div class="text-sm text-gray-500">Add an extra layer of security</div>
                            </div>
                            <input type="checkbox" class="toggle toggle-primary" checked />
                        </div>
                        
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <div class="font-medium text-gray-700 mb-1">Last Login Activity</div>
                            <div class="text-sm text-gray-500">
                                <i class="fas fa-laptop mr-1"></i> Chrome on Windows • Today, 08:45 AM
                            </div>
                            <div class="text-sm text-gray-500 mt-1">
                                <i class="fas fa-map-marker-alt mr-1"></i> New York, NY (Approximate)
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="mt-12 pt-6 border-t border-gray-200">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-gray-500 text-sm">
                    <p> 2023 Hospitality Management System. Employee Self-Service Portal v2.4</p>
                    <p class="mt-1">Data last updated: November 5, 2023 10:30 AM</p>
                </div>
                
                <div class="flex gap-4">
                    <button class="btn btn-ghost btn-sm text-gray-600">
                        <i class="fas fa-question-circle mr-1"></i> Help
                    </button>
                    <button class="btn btn-ghost btn-sm text-gray-600">
                        <i class="fas fa-download mr-1"></i> Export Profile
                    </button>
                    <button class="btn btn-ghost btn-sm text-gray-600">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </button>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- Edit Profile Modal -->
    <dialog id="editModal" class="modal">
        <div class="modal-box">
            <form method="dialog">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
            </form>
            <h3 class="font-bold text-2xl primary-text mb-6">
                <i class="fas fa-user-edit mr-2"></i> Edit Personal Information
            </h3>

            <form method="post" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="update_profile_photo" />
                <p class="text-gray-600 mb-4">
                    <i class="fas fa-info-circle gold-accent mr-1"></i>
                    You can only edit personal contact information. All other fields are controlled by HR.
                </p>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium">Contact Number</span>
                    </label>
                    <input type="text" class="input input-bordered w-full" value="+1 (555) 123-4567" />
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium">Personal Email</span>
                    </label>
                    <input type="email" class="input input-bordered w-full" value="<?php echo htmlspecialchars((string)($profile['email'] ?? '')); ?>" />
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium">Home Address</span>
                    </label>
                    <textarea class="textarea textarea-bordered h-24">123 Hospitality Ave, Suite 45
New York, NY 10001</textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-medium">Emergency Contact Name</span>
                        </label>
                        <input type="text" class="input input-bordered w-full" value="Sarah Smith" />
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-medium">Emergency Contact Number</span>
                        </label>
                        <input type="text" class="input input-bordered w-full" value="+1 (555) 987-6543" />
                    </div>
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium">Relationship</span>
                    </label>
                    <input type="text" class="input input-bordered w-full" value="Spouse" />
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium">Profile Photo</span>
                    </label>
                    <input type="file" name="profile_photo" class="file-input file-input-bordered w-full" accept="image/*" required />
                    <label class="label">
                        <span class="label-text-alt text-gray-500">Max file size: 2MB. Formats: JPG, PNG</span>
                    </label>
                </div>

                <div class="modal-action">
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('editModal').close()">Cancel</button>
                    <button type="submit" class="btn primary-bg text-white">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </dialog>
    
    <!-- Upload Document Modal -->
    <dialog id="uploadModal" class="modal">
        <div class="modal-box">
            <form method="dialog">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
            </form>
            <h3 class="font-bold text-2xl primary-text mb-6">
                <i class="fas fa-upload mr-2"></i> Upload Document
            </h3>
            
            <div class="space-y-4">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium">Document Type</span>
                    </label>
                    <select class="select select-bordered w-full">
                        <option disabled selected>Select document type</option>
                        <option>Government ID</option>
                        <option>Medical Certificate</option>
                        <option>Training Certificate</option>
                        <option>Resume/CV</option>
                        <option>Other Personal Document</option>
                    </select>
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium">Document Title</span>
                    </label>
                    <input type="text" class="input input-bordered w-full" placeholder="e.g., Updated Driver's License" />
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium">Upload File</span>
                    </label>
                    <input type="file" class="file-input file-input-bordered w-full" />
                    <label class="label">
                        <span class="label-text-alt text-gray-500">Max file size: 5MB. Allowed: PDF, JPG, PNG, DOC</span>
                    </label>
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium">Expiration Date (if applicable)</span>
                    </label>
                    <input type="date" class="input input-bordered w-full" />
                </div>
                
                <div class="alert bg-blue-50 border-blue-200 mt-4">
                    <div>
                        <i class="fas fa-info-circle text-blue-500"></i>
                        <div>
                            <h3 class="font-bold">Upload Guidelines</h3>
                            <div class="text-xs">Personal documents can be uploaded and deleted. HR-uploaded documents (contracts, official IDs) are view-only.</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-action">
                <button class="btn btn-ghost" onclick="document.getElementById('uploadModal').close()">Cancel</button>
                <button class="btn primary-bg text-white">
                    <i class="fas fa-upload mr-2"></i> Upload Document
                </button>
            </div>
        </div>
    </dialog>

    <script>
        // Modal functions
        function openEditModal() {
            document.getElementById('editModal').showModal();
        }
        
        function openUploadModal() {
            document.getElementById('uploadModal').showModal();
        }
        
        function saveChanges() {
            // In a real app, this would submit the form to the server
            alert('Personal information updated successfully! Changes will be reflected after HR review.');
            document.getElementById('editModal').close();
        }
        
        // Sample function to simulate data loading
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Employee profile dashboard loaded');
            
            // Add event listeners for edit indicators
            const editableFields = document.querySelectorAll('.label-text-alt.text-green-600');
            editableFields.forEach(field => {
                field.addEventListener('click', function(e) {
                    openEditModal();
                });
            });
        });
    </script>
      <script src="../../soliera.js"></script>
  <script src="../../sidebar.js"></script>
</body>
</html>