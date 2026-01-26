<?php
session_start();
// Database connection - UPDATED TO NEW DATABASE
$dbPrefix = getenv('DB_PREFIX') ?: '';
$servername = getenv('LEAVE_DB_HOST') ?: (getenv('DB_HOST') ?: "localhost");
$username = getenv('LEAVE_DB_USER') ?: (getenv('DB_USER') ?: "root");
$passwordEnv = getenv('LEAVE_DB_PASS');
$passwordGlobal = getenv('DB_PASS');
$password = $passwordEnv !== false
    ? $passwordEnv
    : ($passwordGlobal !== false
        ? $passwordGlobal
        : (($username === 'root' && ($servername === 'localhost' || $servername === '127.0.0.1')) ? '' : 'makmak01'));
$dbname = getenv('LEAVE_DB_NAME') ?: ($dbPrefix !== '' ? ($dbPrefix . 'ESS_leave_db') : 'ESS_leave_db'); // Updated database name
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch leave requests from database
$leave_requests = [];
$result = $conn->query("SELECT * FROM leave_requests ORDER BY created_at DESC");
if ($result) {
    $leave_requests = $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch leave balance
$leave_balance = [
    'vacation' => ['used' => 0, 'total' => 15],
    'sick' => ['used' => 0, 'total' => 10],
    'emergency' => ['used' => 0, 'total' => 5]
];

// Calculate used leave days
foreach ($leave_requests as $request) {
    if ($request['status'] == 'approved') {
        $leave_balance[strtolower($request['leave_type'])]['used'] += $request['days'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Leave Management</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- SweetAlert2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="../CSS/sidebar.css">
  <style>
    body {
      font-size: 14px;
    }
    .card, .modal, .table {
      font-size: inherit;
    }
    /* Fix SweetAlert2 z-index */
    .swal2-container {
      z-index: 99999 !important;
    }
    .swal2-backdrop-show {
      background-color: rgba(0, 0, 0, 0.5) !important;
    }
    /* Ensure modal stays below SweetAlert */
    dialog[open] {
      z-index: 9999;
    }
    dialog[open] + .backdrop {
      z-index: 9998;
    }
  </style>
</head>
<body>
<div class="flex h-screen">
    <!-- Sidebar -->
    <?php 
    // Use relative path or absolute path based on your directory structure
    include '../../../../../USM/sidebarr.php'; 
    ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../../../../../USM/navbar.php'; ?>
        
        <div class="drawer lg:drawer-open">
          <input id="my-drawer-2" type="checkbox" class="drawer-toggle" />
          <div class="drawer-content flex flex-col">
            <main class="flex-1 p-4 md:p-6">
              <div class="mb-6">
                <div class="flex justify-between items-center">
                  <div>
                    <h1 class="text-xl font-semibold text-gray-800">Leave & Requests</h1>
                    <p class="text-gray-600 text-sm">Manage your leave requests and balances</p>
                  </div>
                  <button class="btn btn-primary btn-sm" onclick="showModal('leaveRequestModal')">
                    <i class="fas fa-plus mr-1"></i>New Request
                  </button>
                </div>
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2">
                  <div class="card bg-white border border-gray-200">
                    <div class="card-body p-4">
                      <h2 class="card-title text-lg font-medium mb-3">ðŸ“ My Leave Requests</h2>
                      <div class="overflow-x-auto">
                        <table class="table table-sm w-full">
                          <thead class="bg-gray-100">
                            <tr class="text-xs">
                              <th>Leave Type</th>
                              <th>Dates</th>
                              <th>Days</th>
                              <th>Status</th>
                              <th>Applied On</th>
                              <th>Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php if (empty($leave_requests)): ?>
                              <tr>
                                <td colspan="6" class="text-center py-4 text-gray-500">
                                  No leave requests found
                                </td>
                              </tr>
                            <?php else: ?>
                              <?php foreach ($leave_requests as $request): ?>
                                <tr class="text-xs">
                                  <td><?php echo htmlspecialchars($request['leave_type']); ?></td>
                                  <td>
                                    <?php 
                                    echo date('M d', strtotime($request['start_date'])) . ' - ' . 
                                         date('M d, Y', strtotime($request['end_date'])); 
                                    ?>
                                  </td>
                                  <td><?php echo $request['days']; ?></td>
                                  <td>
                                    <?php 
                                    $status_class = '';
                                    switch($request['status']) {
                                      case 'approved': $status_class = 'badge-success'; break;
                                      case 'pending': $status_class = 'badge-warning'; break;
                                      case 'rejected': $status_class = 'badge-error'; break;
                                      default: $status_class = 'badge-neutral';
                                    }
                                    ?>
                                    <span class="badge badge-sm <?php echo $status_class; ?>">
                                      <?php echo ucfirst($request['status']); ?>
                                    </span>
                                  </td>
                                  <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                  <td>
                                    <button class="btn btn-ghost btn-xs" onclick="viewLeaveDetails(<?php echo $request['id']; ?>)">
                                      <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($request['status'] == 'pending'): ?>
                                      <button class="btn btn-ghost btn-xs" onclick="cancelLeaveRequest(<?php echo $request['id']; ?>)">
                                        <i class="fas fa-times text-error"></i>
                                      </button>
                                    <?php endif; ?>
                                  </td>
                                </tr>
                              <?php endforeach; ?>
                            <?php endif; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>

                <div>
                  <div class="card bg-white border border-gray-200">
                    <div class="card-body p-4">
                      <h2 class="card-title text-lg font-medium mb-3">ðŸ“Š Leave Balance</h2>
                      <div class="space-y-4">
                        <?php foreach ($leave_balance as $type => $balance): ?>
                          <div>
                            <div class="flex justify-between items-center mb-1">
                              <span class="capitalize"><?php echo $type; ?> Leave:</span>
                              <span class="text-sm font-medium">
                                <?php echo ($balance['total'] - $balance['used']); ?>/<?php echo $balance['total']; ?> days
                              </span>
                            </div>
                            <?php 
                            $percentage = ($balance['used'] / $balance['total']) * 100;
                            $color_class = '';
                            if ($percentage < 50) $color_class = 'bg-green-500';
                            elseif ($percentage < 80) $color_class = 'bg-yellow-500';
                            else $color_class = 'bg-red-500';
                            ?>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                              <div class="h-2 rounded-full <?php echo $color_class; ?>" 
                                   style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                          </div>
                        <?php endforeach; ?>

                        <div class="divider my-2"></div>

                        <div>
                          <h3 class="font-medium mb-2">Leave Policy</h3>
                          <ul class="text-xs space-y-1">
                            <li>â€¢ 15 vacation days per year</li>
                            <li>â€¢ 10 sick days per year</li>
                            <li>â€¢ 5 emergency days per year</li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="card bg-white border border-gray-200 mt-4">
                    <div class="card-body p-4">
                      <h2 class="card-title text-lg font-medium mb-3">ðŸ“Œ Request Status</h2>
                      <div class="space-y-2">
                        <?php
                        $pending = 0;
                        $approved = 0;
                        $rejected = 0;
                        foreach ($leave_requests as $request) {
                          switch($request['status']) {
                            case 'pending': $pending++; break;
                            case 'approved': $approved++; break;
                            case 'rejected': $rejected++; break;
                          }
                        }
                        ?>
                        <div class="flex items-center justify-between">
                          <span>Pending Requests:</span>
                          <span class="badge badge-warning badge-sm"><?php echo $pending; ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                          <span>Approved Requests:</span>
                          <span class="badge badge-success badge-sm"><?php echo $approved; ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                          <span>Rejected Requests:</span>
                          <span class="badge badge-error badge-sm"><?php echo $rejected; ?></span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </main>
          </div>
        </div>
      </div>

  <!-- Modals -->
  <dialog id="leaveRequestModal" class="modal">
    <div class="modal-box w-11/12 max-w-md">
      <h3 class="font-semibold text-lg mb-4">New Leave Request</h3>
      <form id="leaveForm">
        <div class="space-y-3">
          <div class="form-control">
            <label class="label">
              <span class="label-text font-medium">Leave Type *</span>
            </label>
            <select id="leaveType" name="leave_type" class="select select-bordered w-full" required>
              <option value="">Select type</option>
              <option value="vacation">Vacation Leave</option>
              <option value="sick">Sick Leave</option>
              <option value="emergency">Emergency Leave</option>
            </select>
          </div>
          
          <div class="form-control">
            <label class="label">
              <span class="label-text font-medium">Start Date *</span>
            </label>
            <input type="date" id="leaveStartDate" name="start_date" class="input input-bordered w-full" required>
          </div>
          
          <div class="form-control">
            <label class="label">
              <span class="label-text font-medium">End Date *</span>
            </label>
            <input type="date" id="leaveEndDate" name="end_date" class="input input-bordered w-full" required>
          </div>
          
          <div class="form-control">
            <label class="label">
              <span class="label-text font-medium">Reason</span>
            </label>
            <textarea name="reason" class="textarea textarea-bordered" rows="3" placeholder="Optional"></textarea>
          </div>
        </div>
        
        <div class="modal-action">
          <button type="button" class="btn btn-ghost" onclick="closeModal('leaveRequestModal')">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="submitLeaveRequest()">Submit Request</button>
        </div>
      </form>
    </div>
    <div class="modal-backdrop" onclick="closeModal('leaveRequestModal')"></div>
  </dialog>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // SweetAlert2 Configuration
    const SwalConfig = {
      customClass: {
        popup: '!z-[99999]',
        actions: '!flex !gap-2',
        confirmButton: '!inline-flex !items-center !justify-center !px-4 !py-2 !bg-blue-600 !text-white !rounded-lg !hover:bg-blue-700 !transition',
        cancelButton: '!inline-flex !items-center !justify-center !px-4 !py-2 !bg-gray-200 !text-gray-800 !rounded-lg !hover:bg-gray-300 !transition'
      },
      buttonsStyling: false,
      showClass: {
        popup: 'animate__animated animate__fadeIn animate__faster'
      },
      hideClass: {
        popup: 'animate__animated animate__fadeOut animate__faster'
      }
    };

    function showModal(modalId) {
      const modal = document.getElementById(modalId);
      if (modal) {
        modal.showModal();
      }
    }
    
    function closeModal(modalId) {
      document.getElementById(modalId).close();
    }
    
    function submitLeaveRequest() {
      const leaveType = document.getElementById('leaveType').value;
      const startDate = document.getElementById('leaveStartDate').value;
      const endDate = document.getElementById('leaveEndDate').value;
      
      if (!leaveType || !startDate || !endDate) {
        Swal.fire({
          title: 'Missing Information',
          text: 'Please fill in all required fields',
          icon: 'error',
          confirmButtonText: 'OK',
          confirmButtonColor: '#3b82f6',
          ...SwalConfig
        });
        return;
      }
      
      // Validate dates
      if (new Date(startDate) > new Date(endDate)) {
        Swal.fire({
          title: 'Invalid Dates',
          text: 'Start date cannot be after end date',
          icon: 'error',
          confirmButtonText: 'OK',
          confirmButtonColor: '#3b82f6',
          ...SwalConfig
        });
        return;
      }
      
      Swal.fire({
        title: 'Submit Leave Request?',
        text: 'Are you sure you want to submit this leave request?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Submit',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        ...SwalConfig
      }).then((result) => {
        if (result.isConfirmed) {
          // Prepare form data
          const formData = new FormData(document.getElementById('leaveForm'));
          
          // Calculate days
          const start = new Date(startDate);
          const end = new Date(endDate);
          const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
          formData.append('days', days);
          
          // Show loading
          Swal.fire({
            title: 'Submitting...',
            text: 'Please wait while we submit your request',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            },
            ...SwalConfig
          });
          
          // Submit via AJAX
          fetch('api/submit_leave.php', {
            method: 'POST',
            body: formData
          })
          .then(response => {
            if (!response.ok) {
              throw new Error('Network response was not ok');
            }
            return response.json();
          })
          .then(data => {
            Swal.close();
            if (data.success) {
              Swal.fire({
                title: 'Success!',
                text: data.message || 'Leave request submitted successfully!',
                icon: 'success',
                confirmButtonText: 'OK',
                confirmButtonColor: '#10b981',
                ...SwalConfig
              }).then(() => {
                closeModal('leaveRequestModal');
                // Clear form
                document.getElementById('leaveForm').reset();
                // Reload page to show new request
                setTimeout(() => location.reload(), 500);
              });
            } else {
              Swal.fire({
                title: 'Error!',
                text: data.message || 'Failed to submit leave request',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#ef4444',
                ...SwalConfig
              });
            }
          })
          .catch(error => {
            console.error('Error:', error);
            Swal.fire({
              title: 'Error!',
              text: 'An error occurred while submitting the request. Please check if the API endpoint exists.',
              icon: 'error',
              confirmButtonText: 'OK',
              confirmButtonColor: '#ef4444',
              ...SwalConfig
            });
          });
        }
      });
    }
    
    function viewLeaveDetails(id) {
      // Show loading
      Swal.fire({
        title: 'Loading...',
        text: 'Please wait while we fetch leave details',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        },
        ...SwalConfig
      });
      
      // Fetch leave details from API
      fetch(`api/get_leave.php?id=${id}`)
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json();
        })
        .then(data => {
          Swal.close();
          if (data.success && data.data) {
            const request = data.data;
            
            // Format dates
            const formatDate = (dateString) => {
              const date = new Date(dateString);
              return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric' 
              });
            };
            
            const startDateFormatted = formatDate(request.start_date);
            const endDateFormatted = formatDate(request.end_date);
            const createdAtFormatted = formatDate(request.created_at);
            
            // Get status badge class
            let statusClass = '';
            switch(request.status) {
              case 'approved': statusClass = 'badge-success'; break;
              case 'pending': statusClass = 'badge-warning'; break;
              case 'rejected': statusClass = 'badge-error'; break;
              default: statusClass = 'badge-neutral';
            }
            
            Swal.fire({
              title: 'Leave Request Details',
              html: `<div class="text-left text-sm space-y-3">
                <div class="grid grid-cols-2 gap-2">
                  <div><strong>Request ID:</strong></div>
                  <div>#LV${request.id}</div>
                  
                  <div><strong>Leave Type:</strong></div>
                  <div>${request.leave_type}</div>
                  
                  <div><strong>Start Date:</strong></div>
                  <div>${startDateFormatted}</div>
                  
                  <div><strong>End Date:</strong></div>
                  <div>${endDateFormatted}</div>
                  
                  <div><strong>Duration:</strong></div>
                  <div>${request.days} day${request.days !== 1 ? 's' : ''}</div>
                  
                  <div><strong>Status:</strong></div>
                  <div><span class="badge badge-sm ${statusClass}">${request.status}</span></div>
                </div>
                
                <div class="pt-2 border-t">
                  <div><strong>Reason:</strong></div>
                  <div class="mt-1 p-2 bg-gray-50 rounded">${request.reason || 'No reason provided'}</div>
                </div>
                
                <div class="text-xs text-gray-500">
                  <strong>Submitted:</strong> ${createdAtFormatted}
                </div>
              </div>`,
              icon: 'info',
              confirmButtonText: 'Close',
              confirmButtonColor: '#3b82f6',
              width: '500px',
              ...SwalConfig
            });
          } else {
            Swal.fire({
              title: 'Error!',
              text: data.message || 'Failed to load leave details',
              icon: 'error',
              confirmButtonText: 'OK',
              confirmButtonColor: '#ef4444',
              ...SwalConfig
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({
            title: 'Error!',
            text: 'Failed to load leave details. Please check if the API endpoint exists.',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#ef4444',
            ...SwalConfig
          });
        });
    }
    
    function cancelLeaveRequest(id) {
      Swal.fire({
        title: 'Cancel Leave Request?',
        text: 'Are you sure you want to cancel this pending leave request?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Cancel',
        cancelButtonText: 'Keep Request',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        ...SwalConfig
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading
          Swal.fire({
            title: 'Cancelling...',
            text: 'Please wait while we cancel your request',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            },
            ...SwalConfig
          });
          
          // Call cancel API
          fetch('api/cancel_leave.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
          })
          .then(response => {
            if (!response.ok) {
              throw new Error('Network response was not ok');
            }
            return response.json();
          })
          .then(data => {
            Swal.close();
            if (data.success) {
              Swal.fire({
                title: 'Cancelled!',
                text: data.message || 'Leave request has been cancelled.',
                icon: 'success',
                confirmButtonText: 'OK',
                confirmButtonColor: '#10b981',
                ...SwalConfig
              }).then(() => {
                location.reload();
              });
            } else {
              Swal.fire({
                title: 'Error!',
                text: data.message || 'Failed to cancel request',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#ef4444',
                ...SwalConfig
              });
            }
          })
          .catch(error => {
            console.error('Error:', error);
            Swal.fire({
              title: 'Error!',
              text: 'An error occurred while cancelling the request. Please check if the API endpoint exists.',
              icon: 'error',
              confirmButtonText: 'OK',
              confirmButtonColor: '#ef4444',
              ...SwalConfig
            });
          });
        }
      });
    }

    // Set minimum date to today
    document.addEventListener('DOMContentLoaded', function() {
      const today = new Date().toISOString().split('T')[0];
      const startDateInput = document.getElementById('leaveStartDate');
      const endDateInput = document.getElementById('leaveEndDate');
      
      if (startDateInput) startDateInput.min = today;
      if (endDateInput) endDateInput.min = today;
      
      // Update end date min when start date changes
      if (startDateInput) {
        startDateInput.addEventListener('change', function() {
          if (endDateInput) {
            endDateInput.min = this.value;
          }
        });
      }
    });
  </script>
  <script src="../../../../../soliera.js"></script>
  <script src="../../../../../sidebar.js"></script>
</body>
</html>
