<?php
session_start();

require_once __DIR__ . '/db.php';

$emp = $conn ? ess_ensure_employee($conn) : null;

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

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="space-y-6">
              <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                  <div class="flex items-start gap-4">
                    <div class="relative">
                      <div class="avatar">
                        <div class="w-20 rounded-full bg-base-200">
                          <img src="https://api.dicebear.com/7.x/adventurer/svg?seed=Alex" alt="Avatar" />
                        </div>
                      </div>
                      <button class="btn btn-sm btn-circle btn-primary absolute -bottom-2 -right-2" type="button" aria-label="Change photo">
                        <i data-lucide="camera" class="w-4 h-4"></i>
                      </button>
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
              <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                  <div class="flex items-center justify-between">
                    <div>
                      <h2 class="card-title">Personal Information</h2>
                      <p class="text-sm text-gray-500">Update your personal details.</p>
                    </div>
                    <button class="btn btn-ghost btn-sm" type="button">
                      <i data-lucide="pencil" class="w-4 h-4"></i>
                      <span class="ml-2">Edit</span>
                    </button>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">FULL NAME</span></label>
                      <input class="input input-bordered" value="<?php echo htmlspecialchars($user['name']); ?>" />
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">WORK EMAIL</span></label>
                      <input class="input input-bordered" value="<?php echo htmlspecialchars($user['work_email']); ?>" />
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">PHONE NUMBER</span></label>
                      <input class="input input-bordered" value="<?php echo htmlspecialchars($user['phone']); ?>" />
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">LOCATION</span></label>
                      <input class="input input-bordered" value="<?php echo htmlspecialchars($user['work_location']); ?>" />
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
                      <input class="input input-bordered" value="<?php echo htmlspecialchars($user['emergency_name']); ?>" />
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">RELATIONSHIP</span></label>
                      <input class="input input-bordered" value="<?php echo htmlspecialchars($user['emergency_relationship']); ?>" />
                    </div>
                  </div>

                  <div class="mt-6 flex justify-end gap-3">
                    <button class="btn btn-ghost" type="button">Discard</button>
                    <button class="btn btn-primary" type="button">Save Changes</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script>
    lucide.createIcons();
  </script>
</body>
</html>
