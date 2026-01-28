<?php
session_start();

require_once __DIR__ . '/db.php';

$employeeId = ess_employee_id($conn);

$success_message = '';
$error_message = '';

$subject = '';
$category = 'Workplace Grievance';
$details = '';
$anonymous = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim((string)($_POST['subject'] ?? ''));
    $category = trim((string)($_POST['category'] ?? 'Workplace Grievance'));
    $details = trim((string)($_POST['details'] ?? ''));
    $anonymous = isset($_POST['anonymous']);

    if ($subject === '' || $details === '') {
        $error_message = 'Please fill in the required fields.';
    } else {
        if (!$employeeId) {
            $error_message = 'Unable to identify employee. Please login again.';
        } elseif (!$conn) {
            $error_message = 'Database connection unavailable.';
        } else {
            $desc = '[' . $category . '] ' . $details;
            if ($anonymous) {
                $desc = '[ANONYMOUS] ' . $desc;
            }

            $stmt = mysqli_prepare($conn, 'INSERT INTO complaints (employee_id, subject, description, status) VALUES (?, ?, ?, ?)');
            if (!$stmt) {
                $error_message = 'Failed to submit complaint. Please try again.';
            } else {
                $status = 'Open';
                mysqli_stmt_bind_param($stmt, 'isss', $employeeId, $subject, $desc, $status);
                $ok = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                if (!$ok) {
                    $error_message = 'Failed to submit complaint. Please try again.';
                } else {
                    $success_message = 'Your incident report has been submitted.';
                    $subject = '';
                    $details = '';
                    $anonymous = false;
                    $category = 'Workplace Grievance';
                }
            }
        }
    }
}

$categories = [
    'Workplace Grievance',
    'Harassment',
    'Safety Concern',
    'Policy Violation',
    'Facilities / Maintenance',
    'Payroll / Benefits',
    'Other',
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Complaint / Feedback</title>
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
        <div class="max-w-3xl mx-auto">
          <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
              <div class="flex items-center gap-2">
                <div class="p-2 rounded-xl bg-base-200">
                  <i data-lucide="message-square-warning" class="w-5 h-5"></i>
                </div>
                <div>
                  <h1 class="text-xl md:text-2xl font-bold text-gray-800">Lodge a Complaint / Feedback</h1>
                  <p class="text-sm text-gray-500">Submit an incident report and we’ll route it to the appropriate team.</p>
                </div>
              </div>

              <?php if ($success_message !== ''): ?>
                <div class="alert alert-success mt-6">
                  <i data-lucide="check-circle" class="w-5 h-5"></i>
                  <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
              <?php endif; ?>

              <?php if ($error_message !== ''): ?>
                <div class="alert alert-error mt-6">
                  <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                  <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
              <?php endif; ?>

              <form method="POST" class="mt-6 space-y-4">
                <div class="form-control">
                  <label class="label"><span class="label-text">Subject</span></label>
                  <input name="subject" class="input input-bordered" placeholder="Brief summary of the issue..." value="<?php echo htmlspecialchars($subject); ?>" required />
                </div>

                <div class="form-control">
                  <label class="label"><span class="label-text">Incident Category</span></label>
                  <select name="category" class="select select-bordered">
                    <?php foreach ($categories as $c): ?>
                      <option value="<?php echo htmlspecialchars($c); ?>" <?php echo ($category === $c) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-control">
                  <label class="label"><span class="label-text">Details</span></label>
                  <textarea name="details" class="textarea textarea-bordered min-h-[140px]" placeholder="Provide as much detail as possible..." required><?php echo htmlspecialchars($details); ?></textarea>
                </div>

                <div class="form-control">
                  <label class="label cursor-pointer justify-start gap-3">
                    <input type="checkbox" name="anonymous" class="checkbox" <?php echo $anonymous ? 'checked' : ''; ?> />
                    <span class="label-text">Submit Anonymously</span>
                  </label>
                </div>

                <div class="pt-2">
                  <button class="btn btn-error w-full" type="submit">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span class="ml-2">Submit Incident Report</span>
                  </button>
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
  </script>
</body>
</html>
