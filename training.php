<?php
session_start();
include("../db.php"); // make sure this sets $host, $username, $password

$db_name = "hr2_training";
$conn = $connections[$db_name] ?? die("❌ Connection not found for $db_name");

// Fetch all training programs
$trainings = [];
$result = $conn->query("SELECT * FROM trainings ORDER BY start_date DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $trainings[] = $row;
    }
}

// Fetch upcoming trainings (within next 30 days)
$upcomingTrainings = [];
$result = $conn->query("SELECT * FROM trainings 
                        WHERE start_date >= CURDATE() 
                          AND start_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
                        ORDER BY start_date ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $upcomingTrainings[] = $row;
    }
}

// Calculate stats
$scheduledCount = 0;
$activeCount = 0;
$upcomingThisWeek = 0;
$externalCount = 0;
$totalParticipants = 0;

foreach ($trainings as $training) {
    $startDate = new DateTime($training['start_date']);
    $endDate   = new DateTime($training['end_date']);
    $today     = new DateTime();

    if ($today < $startDate) {
        $scheduledCount++;
        // Check if it's within the next week
        $interval = $today->diff($startDate);
        if ($interval->days <= 7) {
            $upcomingThisWeek++;
        }
    } elseif ($today >= $startDate && $today <= $endDate) {
        $activeCount++;
    }

    if ($training['type'] === 'External') {
        $externalCount++;
    }

    $totalParticipants += $training['max_participants'];
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HR Portal - Training Management</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://unpkg.com/lucide@latest"></script>
  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .training-card {
      transition: all 0.3s ease;
      cursor: pointer;
    }
    .training-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    .stats-card {
      transition: all 0.3s ease;
    }
    .stats-card:hover {
      transform: translateY(-3px);
    }
    .status-badge {
      display: inline-block;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    .status-upcoming {
      background-color: #3B82F6;
      color: white;
    }
    .status-active {
      background-color: #10B981;
      color: white;
    }
    .status-completed {
      background-color: #6B7280;
      color: white;
    }
    .status-external {
      background-color: #8B5CF6;
      color: white;
    }
    /* SweetAlert2 always in front */
    .swal2-topmost {
      z-index: 100000 !important;
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include '../USM/sidebarr.php'; ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../USM/navbar.php'; ?>
      
      <main class="container mx-auto px-4 py-8">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Training Management</h1>
            <p class="text-gray-600">Create and manage training programs</p>
          </div>
          <button class="btn btn-primary" onclick="createTrainingModal.showModal()">
            <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
            Create Training
          </button>
        </div>

        <!-- Create Training Modal -->
<dialog id="createTrainingModal" class="modal">
    <div class="modal-box w-11/12 max-w-5xl">
        <div class="flex justify-between items-center mb-2">
            <h3 class="font-bold text-2xl text-primary">Create Training Design</h3>
            <button type="button" class="btn btn-sm btn-circle" onclick="createTrainingModal.close()">✕</button>
        </div>
        
        <div class="divider my-2"></div>
        
        <!-- Progress Steps -->
        <ul class="steps steps-vertical md:steps-horizontal w-full mb-6">
            <li class="step step-primary">Training Details</li>
            <li class="step">Schedule</li>
            <li class="step">Participants</li>
            <li class="step">Review</li>
        </ul>
        
        <form id="training-form" onsubmit="return handleTrainingSubmit(event)">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Column -->
                <div>
                    <h4 class="font-semibold text-lg mb-4 text-primary">Training Details</h4>
                    
                    <div class="form-control w-full mb-4">
                        <label class="label">
                            <span class="label-text font-medium">Training Program Name</span>
                        </label>
                        <input type="text" id="training-name" name="name" placeholder="Enter training program name" class="input input-bordered w-full focus:input-primary" required />
                    </div>
                    
                    <div class="form-control w-full mb-4">
                        <label class="label">
                            <span class="label-text font-medium">Training Category</span>
                        </label>
                        <select class="select select-bordered w-full focus:select-primary" id="training-type" name="type" required>
                            <option disabled selected value="">Select training Category</option>
                            <option value="Internal Program">Internal Program</option>
                            <option value="External" selected>External</option>
                            <option value="Workshop">Workshop</option>
                            <option value="Seminar">Seminar</option>
                        </select>
                    </div>
                    
                    <div class="form-control w-full mb-4">
                        <label class="label">
                            <span class="label-text font-medium">Description</span>
                        </label>
                        <textarea class="textarea textarea-bordered h-24 focus:textarea-primary" id="training-description" name="description" placeholder="Training description" required></textarea>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div>
                    <h4 class="font-semibold text-lg mb-4 text-primary">Schedule & Participants</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-medium">Start Date</span>
                            </label>
                            <input type="date" id="start-date" name="start_date" class="input input-bordered focus:input-primary" required />
                        </div>
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-medium">End Date</span>
                            </label>
                            <input type="date" id="end-date" name="end_date" class="input input-bordered focus:input-primary" required />
                        </div>
                    </div>
                    
                    <div class="form-control w-full mb-4">
                        <label class="label">
                            <span class="label-text font-medium">Training Location</span>
                        </label>
                        <input type="text" id="training-location" name="location" placeholder="Enter location" class="input input-bordered w-full focus:input-primary" required />
                    </div>
                    
                    <div class="form-control w-full mb-4">
                        <label class="label">
                            <span class="label-text font-medium">Max Participants</span>
                        </label>
                        <input type="number" id="max-participants" name="max_participants" placeholder="Enter number" class="input input-bordered w-full focus:input-primary" min="1" required />
                    </div>
                </div>
            </div>
            
            <div class="modal-action mt-6">
                <button type="button" class="btn btn-outline mr-2" onclick="createTrainingModal.close()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <span>Create Training Design</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </form>
    </div>
    
    <!-- Click outside to close -->
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
</dialog>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div class="stats stats-card shadow bg-white">
            <div class="stat">
              <div class="stat-figure text-primary">
                <i data-lucide="calendar" class="w-8 h-8"></i>
              </div>
              <div class="stat-title">Scheduled Trainings</div>
              <div class="stat-value text-primary" id="scheduled-count"><?php echo $scheduledCount; ?></div>
              <div class="stat-desc" id="upcoming-count"><?php echo $upcomingThisWeek; ?> upcoming this week</div>
            </div>
          </div>
          
          <div class="stats stats-card shadow bg-white">
            <div class="stat">
              <div class="stat-figure text-secondary">
                <i data-lucide="users" class="w-8 h-8"></i>
              </div>
              <div class="stat-title">Active Programs</div>
              <div class="stat-value text-secondary" id="active-count"><?php echo $activeCount; ?></div>
              <div class="stat-desc" id="external-count"><?php echo $externalCount; ?> external programs</div>
            </div>
          </div>
          
          <div class="stats stats-card shadow bg-white">
            <div class="stat">
              <div class="stat-figure text-accent">
                <i data-lucide="book-open" class="w-8 h-8"></i>
              </div>
              <div class="stat-title">Training Modules</div>
              <div class="stat-value text-accent" id="modules-count"><?php echo count($trainings); ?></div>
              <div class="stat-desc" id="new-modules">+<?php echo ceil(count($trainings)/4); ?> this month</div>
            </div>
          </div>
          
          <div class="stats stats-card shadow bg-white">
            <div class="stat">
              <div class="stat-figure text-info">
                <i data-lucide="user-check" class="w-8 h-8"></i>
              </div>
              <div class="stat-title">Participants</div>
              <div class="stat-value text-info" id="participants-count"><?php echo $totalParticipants; ?></div>
              <div class="stat-desc" id="completion-rate">87% completion rate</div>
            </div>
          </div>
        </div>

        <!-- Training Programs Cards View -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
          <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <h2 class="text-xl font-semibold text-gray-800">Training Programs</h2>
            <div class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-2 w-full md:w-auto">
              <div class="form-control">
                <input type="text" id="search-training" placeholder="Search trainings..." class="input input-bordered w-full" oninput="filterTrainings()" />
              </div>
              <select class="select select-bordered w-full md:w-auto" onchange="filterTrainings()" id="status-filter">
                <option value="all">All Statuses</option>
                <option value="active">Active</option>
                <option value="upcoming">Upcoming</option>
                <option value="completed">Completed</option>
                <option value="external">External</option>
              </select>
              <button class="btn btn-outline" onclick="toggleView()">
                <i data-lucide="list" class="w-4 h-4 mr-2" id="view-icon"></i>
                <span id="view-text">List View</span>
              </button>
            </div>
          </div>

          <!-- Cards View -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="cards-view">
            <?php foreach ($trainings as $training): ?>
              <?php
                $startDate = new DateTime($training['start_date']);
                $endDate = new DateTime($training['end_date']);
                $today = new DateTime();
                
                if ($today < $startDate) {
                  $status = 'Upcoming';
                  $statusClass = 'status-upcoming';
                } elseif ($today >= $startDate && $today <= $endDate) {
                  $status = 'Active';
                  $statusClass = 'status-active';
                } else {
                  $status = 'Completed';
                  $statusClass = 'status-completed';
                }
                
                if ($training['type'] === 'External') {
                  $statusClass = 'status-external';
                }
              ?>
              <div class="training-card card bg-base-100 shadow-md" onclick="viewTraining(<?php echo $training['id']; ?>)">
                <div class="card-body">
                  <div class="flex justify-between items-start mb-2">
                    <h3 class="card-title text-lg"><?php echo htmlspecialchars($training['name']); ?></h3>
                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                  </div>
                  <p class="text-gray-600 mb-4"><?php echo strlen($training['description']) > 80 ? substr(htmlspecialchars($training['description']), 0, 80) . '...' : htmlspecialchars($training['description']); ?></p>
                  <div class="flex items-center text-sm text-gray-500 mb-2">
                    <i data-lucide="calendar" class="w-4 h-4 mr-2"></i>
                    <?php echo $startDate->format('M j, Y'); ?> - <?php echo $endDate->format('M j, Y'); ?>
                  </div>
                  <div class="flex items-center text-sm text-gray-500 mb-2">
                    <i data-lucide="map-pin" class="w-4 h-4 mr-2"></i>
                    <?php echo htmlspecialchars($training['location']); ?>
                  </div>
                  <div class="flex items-center text-sm text-gray-500 mb-4">
                    <i data-lucide="users" class="w-4 h-4 mr-2"></i>
                    <?php echo $training['max_participants']; ?> participants max
                  </div>
                  <div class="card-actions justify-end">
                    <button class="btn btn-sm btn-outline" onclick="event.stopPropagation(); editTraining(<?php echo $training['id']; ?>)">Edit</button>
                    <button class="btn btn-sm btn-primary">View</button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Table View (Hidden by default) -->
          <div class="overflow-x-auto hidden" id="table-view">
            <table class="table table-zebra w-full" id="trainings-table">
              <thead>
                <tr>
                  <th onclick="sortTable(0)">Training Name <i data-lucide="arrow-up-down" class="w-4 h-4 inline"></i></th>
                  <th onclick="sortTable(1)">Type <i data-lucide="arrow-up-down" class="w-4 h-4 inline"></i></th>
                  <th onclick="sortTable(2)">Schedule <i data-lucide="arrow-up-down" class="w-4 h-4 inline"></i></th>
                  <th onclick="sortTable(3)">Participants <i data-lucide="arrow-up-down" class="w-4 h-4 inline"></i></th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="trainings-body">
                <?php foreach ($trainings as $training): ?>
                  <?php
                    $startDate = new DateTime($training['start_date']);
                    $endDate = new DateTime($training['end_date']);
                    $today = new DateTime();
                    
                    if ($today < $startDate) {
                      $status = 'Upcoming';
                      $statusClass = 'status-upcoming';
                    } elseif ($today >= $startDate && $today <= $endDate) {
                      $status = 'Active';
                      $statusClass = 'status-active';
                    } else {
                      $status = 'Completed';
                      $statusClass = 'status-completed';
                    }
                    
                    if ($training['type'] === 'External') {
                      $statusClass = 'status-external';
                    }
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars($training['name']); ?></td>
                    <td><?php echo htmlspecialchars($training['type']); ?></td>
                    <td><?php echo $startDate->format('M j, Y'); ?> - <?php echo $endDate->format('M j, Y'); ?></td>
                    <td><?php echo $training['max_participants']; ?></td>
                    <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                    <td>
                      <div class="flex space-x-2">
                        <button class="btn btn-xs btn-outline" onclick="viewTraining(<?php echo $training['id']; ?>)">View</button>
                        <button class="btn btn-xs btn-primary" onclick="editTraining(<?php echo $training['id']; ?>)">Edit</button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="flex flex-col md:flex-row justify-between items-center mt-6 gap-4">
            <div class="text-sm text-gray-600" id="showing-text">Showing <?php echo count($trainings); ?> out of <?php echo count($trainings); ?> trainings</div>
            <div class="btn-group">
              <button class="btn btn-sm" onclick="changePage('prev')">Previous</button>
              <button class="btn btn-sm btn-active" id="page-number">1</button>
              <button class="btn btn-sm" onclick="changePage('next')">Next</button>
            </div>
          </div>
        </div>

        <!-- Upcoming Schedule Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-xl font-semibold text-gray-800 mb-6">Upcoming Schedule</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="upcoming-cards">
            <?php if (count($upcomingTrainings) > 0): ?>
              <?php foreach ($upcomingTrainings as $training): ?>
                <?php
                  $startDate = new DateTime($training['start_date']);
                  $endDate = new DateTime($training['end_date']);
                  $today = new DateTime();
                  $daysUntil = $today->diff($startDate)->days;
                ?>
                <div class="training-card card bg-base-100 shadow-md border-l-4 border-primary" onclick="viewTraining(<?php echo $training['id']; ?>)">
                  <div class="card-body">
                    <h3 class="card-title text-lg"><?php echo htmlspecialchars($training['name']); ?></h3>
                    <p class="text-gray-600 mb-4"><?php echo strlen($training['description']) > 80 ? substr(htmlspecialchars($training['description']), 0, 80) . '...' : htmlspecialchars($training['description']); ?></p>
                    <div class="flex items-center text-sm text-gray-500 mb-2">
                      <i data-lucide="calendar" class="w-4 h-4 mr-2"></i>
                      <?php echo $startDate->format('M j, Y'); ?> - <?php echo $endDate->format('M j, Y'); ?>
                    </div>
                    <div class="flex items-center text-sm text-gray-500 mb-2">
                      <i data-lucide="map-pin" class="w-4 h-4 mr-2"></i>
                      <?php echo htmlspecialchars($training['location']); ?>
                    </div>
                    <div class="flex items-center text-sm text-primary font-semibold">
                      <i data-lucide="clock" class="w-4 h-4 mr-2"></i>
                      Starts in <?php echo $daysUntil; ?> day<?php echo $daysUntil !== 1 ? 's' : ''; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="col-span-full text-center py-8 text-gray-500">
                <i data-lucide="calendar" class="w-12 h-12 mx-auto mb-4 text-gray-400"></i>
                <p>No upcoming trainings scheduled</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </main>

      <!-- View Training Modal -->
      <dialog id="viewTrainingModal" class="modal">
        <div class="modal-box w-11/12 max-w-3xl">
          <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-2xl text-primary" id="view-title">Training Details</h3>
            <button type="button" class="btn btn-sm btn-circle" onclick="viewTrainingModal.close()">✕</button>
          </div>
          
          <div class="divider my-2"></div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h4 class="font-semibold text-lg mb-4 text-primary">Training Details</h4>
              
              <div class="mb-4">
                <p class="text-sm text-gray-500">Program Name</p>
                <p class="font-medium" id="view-name"></p>
              </div>
              
              <div class="mb-4">
                <p class="text-sm text-gray-500">Type</p>
                <p class="font-medium" id="view-type"></p>
              </div>
              
              <div class="mb-4">
                <p class="text-sm text-gray-500">Description</p>
                <p class="font-medium" id="view-description"></p>
              </div>
            </div>
            
            <div>
              <h4 class="font-semibold text-lg mb-4 text-primary">Schedule & Participants</h4>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                  <p class="text-sm text-gray-500">Start Date</p>
                  <p class="font-medium" id="view-start-date"></p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">End Date</p>
                  <p class="font-medium" id="view-end-date"></p>
                </div>
              </div>
              
              <div class="mb-4">
                <p class="text-sm text-gray-500">Location</p>
                <p class="font-medium" id="view-location"></p>
              </div>
              
              <div class="mb-4">
                <p class="text-sm text-gray-500">Max Participants</p>
                <p class="font-medium" id="view-max-participants"></p>
              </div>
              
              <div class="mb-4">
                <p class="text-sm text-gray-500">Status</p>
                <p class="font-medium">
                  <span class="status-badge" id="view-status-badge"></span>
                </p>
              </div>
            </div>
          </div>
          
          <div class="modal-action mt-6">
            <button type="button" class="btn btn-outline mr-2" onclick="viewTrainingModal.close()">Close</button>
            <button type="button" class="btn btn-primary" onclick="editTraining()">Edit Training</button>
          </div>
        </div>
        
        <!-- Click outside to close -->
        <form method="dialog" class="modal-backdrop">
          <button>close</button>
        </form>
      </dialog>

      <!-- Create Training Modal (your existing code) -->
      <!-- Edit Training Modal (your existing code) -->
    </div>
  </div>

  <script src="../soliera.js"></script>
  <script src="../sidebar.js"></script>

  <script>
    // Training data from PHP
    const trainings = <?php echo json_encode($trainings); ?>;
    let filteredTrainings = [...trainings];
    let isCardView = true;
    let currentPage = 1;
    const itemsPerPage = 6;

    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof lucide !== 'undefined') {
        lucide.createIcons();
      }
    });

    // View training details
    function viewTraining(id) {
      const training = trainings.find(t => t.id == id);
      if (!training) return;
      
      const status = getTrainingStatus(training);
      const statusClass = getStatusClass(status, training.type);
      
      document.getElementById('view-title').textContent = training.name;
      document.getElementById('view-name').textContent = training.name;
      document.getElementById('view-type').textContent = training.type;
      document.getElementById('view-description').textContent = training.description;
      document.getElementById('view-start-date').textContent = formatDate(training.start_date);
      document.getElementById('view-end-date').textContent = formatDate(training.end_date);
      document.getElementById('view-location').textContent = training.location;
      document.getElementById('view-max-participants').textContent = training.max_participants;
      
      const statusBadge = document.getElementById('view-status-badge');
      statusBadge.textContent = status;
      statusBadge.className = `status-badge ${statusClass}`;
      
      document.getElementById('viewTrainingModal').showModal();
    }

    // Edit training
    function editTraining(id) {
      // Close view modal if open
      if(document.getElementById('viewTrainingModal').open) {
        document.getElementById('viewTrainingModal').close();
      }
      // Your existing edit functionality here
      console.log('Edit training:', id);
      // You would populate the edit form with training data
    }

    // Toggle between card and table view
    function toggleView() {
      isCardView = !isCardView;
      
      const cardsView = document.getElementById('cards-view');
      const tableView = document.getElementById('table-view');
      const viewIcon = document.getElementById('view-icon');
      const viewText = document.getElementById('view-text');
      
      if (isCardView) {
        cardsView.classList.remove('hidden');
        tableView.classList.add('hidden');
        viewText.textContent = 'List View';
        viewIcon.setAttribute('data-lucide', 'list');
      } else {
        cardsView.classList.add('hidden');
        tableView.classList.remove('hidden');
        viewText.textContent = 'Card View';
        viewIcon.setAttribute('data-lucide', 'layout-grid');
      }
      if (typeof lucide !== 'undefined') {
        lucide.createIcons();
      }
    }

    // Get training status
    function getTrainingStatus(training) {
      const today = new Date();
      const startDate = new Date(training.start_date);
      const endDate = new Date(training.end_date);
      if (today < startDate) {
        return 'Upcoming';
      } else if (today >= startDate && today <= endDate) {
        return 'Active';
      } else {
        return 'Completed';
      }
    }

    // Get status class, including 'External' override
    function getStatusClass(status, type) {
      if (type === 'External') return 'status-external';
      switch(status.toLowerCase()) {
        case 'upcoming': return 'status-upcoming';
        case 'active': return 'status-active';
        case 'completed': return 'status-completed';
        default: return '';
      }
    }

    // Format date
    function formatDate(dateString) {
      const options = { year: 'numeric', month: 'short', day: 'numeric' };
      return new Date(dateString).toLocaleDateString(undefined, options);
    }

    // Handle Training Submit
    function handleTrainingSubmit(event) {
      event.preventDefault();

      // Get form data
      const formData = {
        name: document.getElementById('training-name').value,
        type: document.getElementById('training-type').value,
        description: document.getElementById('training-description').value,
        start_date: document.getElementById('start-date').value,
        end_date: document.getElementById('end-date').value,
        location: document.getElementById('training-location').value,
        max_participants: document.getElementById('max-participants').value
      };

      // Send data to server
      fetch('handle_training.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          // 1. Minimize/close the modal first
          document.getElementById('createTrainingModal').close();
          document.getElementById('training-form').reset();

          // 2. Show SweetAlert2 modal after a short delay
          setTimeout(() => {
            Swal.fire({
              icon: 'success',
              title: 'Training Created',
              text: 'Training design created successfully with ID: ' + data.training_id,
              confirmButtonText: 'OK',
              customClass: { popup: 'swal2-topmost' }
            }).then(() => {
              window.location.reload();
            });
          }, 300); // delay for smooth transition (can be 0 if you want instant)
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            html: 'Error: ' + data.message + '<br>' + (data.errors ? data.errors.join('<br>') : ''),
            confirmButtonText: 'OK',
            customClass: { popup: 'swal2-topmost' }
          });
        }
      })
      .catch(error => {
        console.error('Error:', error);
        Swal.fire({
          icon: 'error',
          title: 'Network Error',
          text: 'An error occurred while creating the training design.',
          confirmButtonText: 'OK',
          customClass: { popup: 'swal2-topmost' }
        });
      });

      return false;
    }
  </script>
</body>
</html>