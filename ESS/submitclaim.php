<?php
session_start();

$claims = [
    ['title' => 'Flight to NYC Conference', 'date' => 'Jan 20', 'category' => 'Travel', 'status' => 'For Approval', 'amount' => 5450.00],
    ['title' => 'Client Lunch - Midtown', 'date' => 'Jan 18', 'category' => 'Meals', 'status' => 'Approved', 'amount' => 825.50],
    ['title' => 'Annual Internet Stipend', 'date' => 'Jan 01', 'category' => 'Home Office', 'status' => 'Paid', 'amount' => 6000.00],
];

function peso($amount) {
    return '₱' . number_format((float)$amount, 2);
}

function claimBadge($status) {
    $s = strtolower(trim($status));
    return match ($s) {
        'pending' => 'badge-warning',
        'for approval' => 'badge-info',
        'approved' => 'badge-success',
        'processing' => 'badge-primary',
        'paid' => 'badge-success',
        'on hold' => 'badge-ghost',
        'failed' => 'badge-error',
        'cancelled' => 'badge-neutral',
        default => 'badge-ghost',
    };
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Submit Claim</title>
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
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
              <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                  <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-800">New Reimbursement Claim</h1>
                    <span class="badge badge-ghost">Draft Saved</span>
                  </div>

                  <form class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">CLAIM TITLE</span></label>
                      <input class="input input-bordered" placeholder="e.g. Flight to NYC" />
                    </div>

                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">AMOUNT</span></label>
                      <label class="input input-bordered flex items-center gap-2">
                        <span class="text-gray-500">₱</span>
                        <input type="number" class="grow" min="0" step="0.01" placeholder="0.00" />
                      </label>
                    </div>

                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">EXPENSE CATEGORY</span></label>
                      <select class="select select-bordered">
                        <option>Travel & Transport</option>
                        <option>Meals</option>
                        <option>Home Office</option>
                        <option>Supplies</option>
                        <option>Other</option>
                      </select>
                    </div>

                    <div class="form-control">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">DATE OF EXPENSE</span></label>
                      <input class="input input-bordered" type="date" />
                    </div>

                    <div class="md:col-span-2">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">SUPPORTING RECEIPT</span></label>
                      <div class="border-2 border-dashed border-base-300 rounded-xl p-6 bg-base-100 hover:bg-base-200/40 transition cursor-pointer">
                        <div class="flex flex-col items-center justify-center text-center gap-2">
                          <div class="btn btn-circle btn-primary btn-sm" type="button">
                            <i data-lucide="upload" class="w-4 h-4"></i>
                          </div>
                          <div class="font-semibold text-gray-800">Click to upload or drag & drop</div>
                          <div class="text-xs text-gray-500">PDF, JPG, or PNG up to 10MB</div>
                        </div>
                      </div>
                    </div>

                    <div class="md:col-span-2">
                      <label class="label"><span class="label-text text-xs font-semibold text-gray-500">ADDITIONAL NOTES</span></label>
                      <textarea class="textarea textarea-bordered" rows="4"></textarea>
                    </div>

                    <div class="md:col-span-2 mt-2">
                      <button class="btn btn-primary w-full" type="button">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        <span class="ml-2">Submit Claim for Approval</span>
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <div class="space-y-6">
              <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                  <div class="flex items-center justify-between">
                    <h2 class="card-title">Recent Claims</h2>
                    <a href="#" class="link link-primary text-sm">History</a>
                  </div>

                  <div class="mt-4 space-y-4">
                    <?php foreach ($claims as $c): ?>
                      <div class="flex items-start justify-between">
                        <div>
                          <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($c['title']); ?></div>
                          <div class="text-xs text-gray-500"><?php echo htmlspecialchars($c['date']); ?> • <?php echo htmlspecialchars(strtoupper($c['category'])); ?></div>
                        </div>
                        <div class="text-right">
                          <span class="badge badge-sm <?php echo claimBadge($c['status']); ?>"><?php echo htmlspecialchars(strtoupper($c['status'])); ?></span>
                          <div class="mt-1 font-semibold text-gray-900"><?php echo htmlspecialchars(peso($c['amount'])); ?></div>
                        </div>
                      </div>
                      <div class="divider my-0"></div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>

              <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                  <div class="flex items-center gap-2">
                    <div class="p-2 rounded-xl bg-base-200">
                      <i data-lucide="shield-check" class="w-5 h-5"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900">Policy Note</h3>
                  </div>
                  <p class="mt-3 text-sm text-gray-600">
                    Reimbursements are processed every Friday. Claims submitted after 5PM Thursday will be reviewed the following week.
                  </p>
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
