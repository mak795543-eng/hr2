 <?php
    require('../../partials/header.php');
    ?>

 <body class="bg-base-200 min-h-screen">
     <div class="flex h-screen">
         <?php include '../../USM/sidebarr.php'; ?>

         <div class="flex flex-col flex-1 overflow-auto">
             <?php include '../../USM/navbar.php'; ?>

             <div class="max-w-7xl mx-auto p-6">
                 <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
                     <div>
                         <h1 class="text-2xl font-bold">Skill Gap Analysis</h1>
                         <div class="text-sm opacity-70">All employees with evaluated KPIs and competency status.</div>
                     </div>
                     <div class="flex gap-2">
                         <a href="criticalgaps.php" class="btn btn-outline btn-sm">Critical Roles</a>
                     </div>
                 </div>

                 <div class="card bg-base-100 shadow">
                     <div class="card-body">
                         <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
                             <div class="flex gap-2">
                                 <button type="button" id="refreshBtn" class="btn btn-primary">Refresh</button>
                             </div>
                         </div>

                         <div class="overflow-x-auto mt-4">
                             <table class="table table-zebra table-sm">
                                 <thead>
                                     <tr>
                                         <th>Employee</th>
                                         <th>Dept</th>
                                         <th>Position</th>
                                         <th class="text-right">Competency</th>
                                         <th>Status</th>
                                         <th class="text-center">Actions</th>
                                     </tr>
                                 </thead>
                                 <tbody id="employeeRows"></tbody>
                             </table>
                         </div>
                     </div>
                 </div>
             </div>
         </div>
     </div>

     <!-- View Details Modal -->
     <dialog id="view-modal" class="modal modal-lg">
         <div class="modal-box bg-white border border-gray-300 p-0 max-w-5xl">
             <div class="p-6 border-b border-gray-300 bg-gray-50">
                 <div class="flex justify-between items-center">
                     <div>
                         <h3 class="font-bold text-lg text-gray-900 flex items-center gap-2">
                             <i data-lucide="bar-chart" class="w-5 h-5 text-gray-600"></i>
                             Competency Assessment
                         </h3>
                         <p class="text-sm text-gray-600 mt-1" id="employee-subtitle"></p>
                     </div>
                     <button onclick="document.getElementById('view-modal').close()"
                         class="btn btn-sm btn-circle bg-transparent border-0 hover:bg-gray-200 text-gray-600">
                         <i data-lucide="x" class="w-4 h-4"></i>
                     </button>
                 </div>
             </div>

             <div class="p-6">
                 <div id="employee-details-content">
                     <div class="text-center py-12">
                         <div class="loading loading-spinner loading-lg text-gray-600"></div>
                         <p class="mt-4 text-gray-600">Loading competency assessment...</p>
                     </div>
                 </div>
             </div>
         </div>
     </dialog>

     <script>
         lucide.createIcons();
     </script>
     <!-- Removed broken inline script; using external gap_analysis_fix.js -->
     <script src="gap_analysis_fix.js"></script>
     <script src="../../soliera.js"></script>
     <script src="../../sidebar.js"></script>
 </body>

 </html>