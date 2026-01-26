<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Development Portal</title>
  <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .stat-card {
            border-left: 4px solid;
            transition: all 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .competency-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .progress-ring {
            width: 60px;
            height: 60px;
        }
        .sidebar-item {
            border-radius: 0.5rem;
            margin: 0.25rem 0;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
<div class="flex h-screen">
    <!-- Sidebar -->
    <?php 
    // Use relative path or absolute path based on your directory structure
    include '../../../../USM/sidebarr.php'; 
    ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
        <!-- Top Navigation -->
        <header class="bg-white border-b border-gray-200">
            <div class="px-4 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Left Section -->
                    <div class="flex items-center">
                        <div class="flex items-center">
                            <i data-lucide="graduation-cap" class="w-6 h-6 text-blue-600 mr-2"></i>
                            <span class="text-xl font-semibold text-gray-800">Talent Portal</span>
                        </div>
                        <div class="hidden lg:flex ml-8 space-x-1">
                            <a href="#" class="px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg">Dashboard</a>
                            <a href="#" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-blue-600 rounded-lg">Learning</a>
                            <a href="#" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-blue-600 rounded-lg">Assessments</a>
                            <a href="#" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-blue-600 rounded-lg">Career</a>
                        </div>
                    </div>

                    <!-- Right Section -->
                    <div class="flex items-center space-x-4">
                        <button class="p-2 text-gray-600 hover:text-blue-600 hover:bg-gray-100 rounded-lg">
                            <i data-lucide="bell" class="w-5 h-5"></i>
                            <span class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>
                        <div class="hidden md:flex items-center space-x-3">
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-800">Sarah Johnson</p>
                                <p class="text-xs text-gray-500">Senior Developer</p>
                            </div>
                            <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-medium">
                                SJ
                            </div>
                        </div>
                        <button class="lg:hidden p-2 text-gray-600 hover:text-blue-600">
                            <i data-lucide="menu" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex-1 flex">
            <!-- Sidebar (Desktop) -->
            <aside class="hidden lg:block w-64 bg-white border-r border-gray-200">
                <div class="p-6">
                    <div class="mb-8">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg flex items-center justify-center">
                                <i data-lucide="user" class="w-5 h-5"></i>
                            </div>
                            <div class="ml-3">
                                <p class="font-medium text-gray-800">Sarah Johnson</p>
                                <p class="text-xs text-gray-500">Level: L3 â€¢ Dept: Engineering</p>
                            </div>
                        </div>
                        
                        <div class="space-y-1">
                            <a href="#" class="flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg sidebar-item">
                                <i data-lucide="layout-dashboard" class="w-4 h-4 mr-3"></i>
                                Dashboard
                            </a>
                            <a href="#" class="flex items-center px-3 py-2 text-sm text-gray-600 hover:text-blue-600 rounded-lg sidebar-item">
                                <i data-lucide="book-open" class="w-4 h-4 mr-3"></i>
                                Learning Modules
                            </a>
                            <a href="#" class="flex items-center px-3 py-2 text-sm text-gray-600 hover:text-blue-600 rounded-lg sidebar-item">
                                <i data-lucide="clipboard-check" class="w-4 h-4 mr-3"></i>
                                Assessments
                            </a>
                            <a href="#" class="flex items-center px-3 py-2 text-sm text-gray-600 hover:text-blue-600 rounded-lg sidebar-item">
                                <i data-lucide="calendar" class="w-4 h-4 mr-3"></i>
                                Leave & Schedule
                            </a>
                            <a href="#" class="flex items-center px-3 py-2 text-sm text-gray-600 hover:text-blue-600 rounded-lg sidebar-item">
                                <i data-lucide="trending-up" class="w-4 h-4 mr-3"></i>
                                Competency Status
                            </a>
                            <a href="#" class="flex items-center px-3 py-2 text-sm text-gray-600 hover:text-blue-600 rounded-lg sidebar-item">
                                <i data-lucide="clock" class="w-4 h-4 mr-3"></i>
                                Training Schedule
                            </a>
                        </div>
                    </div>
                    
                    <div class="pt-6 border-t border-gray-200">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Quick Stats</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Learning Progress</span>
                                <span class="font-medium text-blue-600">68%</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Assessments Due</span>
                                <span class="font-medium text-amber-600">3</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Leave Balance</span>
                                <span class="font-medium text-green-600">15 days</span>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 p-4 lg:p-8">
                <!-- Welcome Header -->
                <div class="mb-8">
                    <h1 class="text-2xl font-semibold text-gray-800 mb-2">Development Dashboard</h1>
                    <p class="text-gray-600">Track your learning, assessments, and career growth</p>
                </div>

                <!-- Summary Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                    <!-- Learning Modules -->
                    <div class="stat-card bg-white p-5 rounded-lg border border-gray-200 border-l-blue-500">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <div class="flex items-center mb-1">
                                    <i data-lucide="book-open" class="w-4 h-4 text-blue-600 mr-2"></i>
                                    <p class="text-sm font-medium text-gray-600">Learning Modules</p>
                                </div>
                                <p class="text-2xl font-semibold text-gray-800">8 Active</p>
                            </div>
                            <div class="relative progress-ring">
                                <div class="w-16 h-16 rounded-full border-4 border-blue-100 flex items-center justify-center">
                                    <span class="text-lg font-semibold text-blue-600">68%</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500">3 modules due this week</div>
                        <div class="mt-3 flex items-center text-sm text-blue-600">
                            <i data-lucide="arrow-up-right" class="w-4 h-4 mr-1"></i>
                            <span>Continue "Advanced React"</span>
                        </div>
                    </div>

                    <!-- Assessments to Take -->
                    <div class="stat-card bg-white p-5 rounded-lg border border-gray-200 border-l-amber-500">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <div class="flex items-center mb-1">
                                    <i data-lucide="clipboard-check" class="w-4 h-4 text-amber-600 mr-2"></i>
                                    <p class="text-sm font-medium text-gray-600">Assessments Due</p>
                                </div>
                                <p class="text-2xl font-semibold text-gray-800">3 Pending</p>
                            </div>
                            <div class="text-xs font-medium text-white bg-amber-500 px-3 py-1 rounded-full">
                                High Priority
                            </div>
                        </div>
                        <div class="text-xs text-gray-500">Due within next 7 days</div>
                        <div class="mt-3 space-y-1">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-700">Technical Skills</span>
                                <span class="text-amber-600 font-medium">Due tomorrow</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-700">Leadership</span>
                                <span class="text-gray-500">Due in 3 days</span>
                            </div>
                        </div>
                    </div>

                    <!-- Leave Balance -->
                    <div class="stat-card bg-white p-5 rounded-lg border border-gray-200 border-l-green-500">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <div class="flex items-center mb-1">
                                    <i data-lucide="calendar" class="w-4 h-4 text-green-600 mr-2"></i>
                                    <p class="text-sm font-medium text-gray-600">Leave Balance</p>
                                </div>
                                <p class="text-2xl font-semibold text-gray-800">15 days</p>
                            </div>
                            <div class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded">
                                Available
                            </div>
                        </div>
                        <div class="text-xs text-gray-500">+3 days this quarter</div>
                        <div class="mt-3 flex justify-between items-center">
                            <div class="text-sm">
                                <span class="text-gray-700">Used: 5 days</span>
                                <span class="text-gray-400 mx-2">â€¢</span>
                                <span class="text-gray-700">Pending: 2 days</span>
                            </div>
                            <button class="text-sm text-green-600 hover:text-green-800 font-medium">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Second Row of Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                    <!-- Recent Activities -->
                    <div class="stat-card bg-white p-5 rounded-lg border border-gray-200 border-l-purple-500">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <div class="flex items-center mb-1">
                                    <i data-lucide="activity" class="w-4 h-4 text-purple-600 mr-2"></i>
                                    <p class="text-sm font-medium text-gray-600">Recent Activities</p>
                                </div>
                                <p class="text-2xl font-semibold text-gray-800">12 Today</p>
                            </div>
                            <div class="text-xs font-medium text-purple-600 bg-purple-50 px-2 py-1 rounded">
                                Updated
                            </div>
                        </div>
                        <div class="text-xs text-gray-500">Latest updates in your development</div>
                        <div class="mt-3 space-y-2">
                            <div class="flex items-center text-sm">
                                <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                                <span class="text-gray-700">Completed "Cloud Fundamentals"</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <div class="w-2 h-2 bg-blue-500 rounded-full mr-2"></div>
                                <span class="text-gray-700">Assessment submitted</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <div class="w-2 h-2 bg-amber-500 rounded-full mr-2"></div>
                                <span class="text-gray-700">New module assigned</span>
                            </div>
                        </div>
                    </div>

                    <!-- Training Schedule -->
                    <div class="stat-card bg-white p-5 rounded-lg border border-gray-200 border-l-indigo-500">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <div class="flex items-center mb-1">
                                    <i data-lucide="clock" class="w-4 h-4 text-indigo-600 mr-2"></i>
                                    <p class="text-sm font-medium text-gray-600">Training Schedule</p>
                                </div>
                                <p class="text-2xl font-semibold text-gray-800">4 Sessions</p>
                            </div>
                            <div class="text-xs text-indigo-600 bg-indigo-50 px-2 py-1 rounded">
                                This Week
                            </div>
                        </div>
                        <div class="text-xs text-gray-500">Next: Leadership Workshop (Tomorrow)</div>
                        <div class="mt-3 space-y-1">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-700">Advanced React</span>
                                <span class="text-indigo-600 font-medium">10:00 AM</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-700">Leadership Workshop</span>
                                <span class="text-gray-500">2:00 PM</span>
                            </div>
                        </div>
                    </div>

                    <!-- Competency Status -->
                    <div class="stat-card bg-white p-5 rounded-lg border border-gray-200 border-l-teal-500">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <div class="flex items-center mb-1">
                                    <i data-lucide="trending-up" class="w-4 h-4 text-teal-600 mr-2"></i>
                                    <p class="text-sm font-medium text-gray-600">Competency Status</p>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-2xl font-semibold text-gray-800">Succession Ready</span>
                                    <div class="ml-2 competency-badge bg-teal-100 text-teal-800">
                                        High
                                    </div>
                                </div>
                            </div>
                            <i data-lucide="award" class="w-6 h-6 text-teal-500"></i>
                        </div>
                        <div class="text-xs text-gray-500">Based on latest assessments</div>
                        <div class="mt-3 flex flex-wrap gap-1">
                            <span class="competency-badge bg-blue-100 text-blue-800">Upskilling</span>
                            <span class="competency-badge bg-green-100 text-green-800">Retain</span>
                            <span class="competency-badge bg-purple-100 text-purple-800">Reskilling</span>
                            <span class="competency-badge bg-teal-100 text-teal-800">Succession Ready</span>
                        </div>
                    </div>
                </div>

                <!-- Detailed Sections -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Upcoming Schedules -->
                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="p-5 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                    <i data-lucide="calendar" class="w-5 h-5 text-blue-600 mr-2"></i>
                                    Upcoming Schedules
                                </h2>
                                <button class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    View Calendar
                                </button>
                            </div>
                        </div>
                        <div class="p-5">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="p-2 bg-white rounded-lg mr-3">
                                            <i data-lucide="users" class="w-4 h-4 text-blue-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-sm text-gray-800">Team Sync Meeting</p>
                                            <p class="text-xs text-gray-500">Daily standup â€¢ Tomorrow, 9:30 AM</p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-medium text-blue-600 bg-white px-2 py-1 rounded">Required</span>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="p-2 bg-white rounded-lg mr-3">
                                            <i data-lucide="graduation-cap" class="w-4 h-4 text-green-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-sm text-gray-800">Leadership Workshop</p>
                                            <p class="text-xs text-gray-500">Soft skills â€¢ Tomorrow, 2:00 PM</p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-medium text-green-600 bg-white px-2 py-1 rounded">Training</span>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="p-2 bg-white rounded-lg mr-3">
                                            <i data-lucide="clipboard-check" class="w-4 h-4 text-purple-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-sm text-gray-800">Technical Assessment</p>
                                            <p class="text-xs text-gray-500">Advanced React â€¢ June 25, 11:00 AM</p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-medium text-purple-600 bg-white px-2 py-1 rounded">Assessment</span>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-amber-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="p-2 bg-white rounded-lg mr-3">
                                            <i data-lucide="book-open" class="w-4 h-4 text-amber-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-sm text-gray-800">Learning Module Due</p>
                                            <p class="text-xs text-gray-500">Cloud Architecture â€¢ June 26, EOD</p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-medium text-amber-600 bg-white px-2 py-1 rounded">Learning</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Recent Activities -->
                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="p-5 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                    <i data-lucide="list-checks" class="w-5 h-5 text-purple-600 mr-2"></i>
                                    Activity Details
                                </h2>
                                <button class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    See All
                                </button>
                            </div>
                        </div>
                        <div class="p-5">
                            <div class="space-y-4">
                                <div class="flex items-start border-l-4 border-green-500 pl-4">
                                    <div class="flex-1">
                                        <p class="font-medium text-sm text-gray-800">Completed learning module</p>
                                        <p class="text-xs text-gray-500">"Cloud Fundamentals" â€¢ Today, 10:30 AM</p>
                                        <div class="mt-1 text-xs text-green-600">
                                            <i data-lucide="check-circle" class="w-3 h-3 inline mr-1"></i>
                                            95% score achieved
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-start border-l-4 border-blue-500 pl-4">
                                    <div class="flex-1">
                                        <p class="font-medium text-sm text-gray-800">Assessment submitted</p>
                                        <p class="text-xs text-gray-500">"Technical Skills Evaluation" â€¢ Today, 9:15 AM</p>
                                        <div class="mt-1 text-xs text-blue-600">
                                            <i data-lucide="clock" class="w-3 h-3 inline mr-1"></i>
                                            Results pending
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-start border-l-4 border-amber-500 pl-4">
                                    <div class="flex-1">
                                        <p class="font-medium text-sm text-gray-800">New module assigned</p>
                                        <p class="text-xs text-gray-500">"Advanced React Patterns" â€¢ Yesterday, 4:20 PM</p>
                                        <div class="mt-1 text-xs text-amber-600">
                                            <i data-lucide="calendar" class="w-3 h-3 inline mr-1"></i>
                                            Due in 7 days
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-start border-l-4 border-purple-500 pl-4">
                                    <div class="flex-1">
                                        <p class="font-medium text-sm text-gray-800">Competency updated</p>
                                        <p class="text-xs text-gray-500">Moved to "Succession Ready" â€¢ June 10</p>
                                        <div class="mt-1 text-xs text-purple-600">
                                            <i data-lucide="award" class="w-3 h-3 inline mr-1"></i>
                                            New career path available
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-start border-l-4 border-indigo-500 pl-4">
                                    <div class="flex-1">
                                        <p class="font-medium text-sm text-gray-800">Training scheduled</p>
                                        <p class="text-xs text-gray-500">"Leadership Workshop" â€¢ June 9</p>
                                        <div class="mt-1 text-xs text-indigo-600">
                                            <i data-lucide="users" class="w-3 h-3 inline mr-1"></i>
                                            15 participants enrolled
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-8 pt-6 border-t border-gray-200 text-center text-sm text-gray-500">
                    <p>Â© 2023 Talent Development Portal â€¢ Employee ID: EMP-2023-045 â€¢ Last updated: Today, 11:45 AM</p>
                </div>
            </main>
        </div>
    </div>

    <!-- Mobile Sidebar -->
    <div id="mobile-sidebar" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="toggleMobileSidebar()"></div>
        <div class="fixed inset-y-0 left-0 w-64 bg-white">
            <div class="p-6">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center">
                        <i data-lucide="graduation-cap" class="w-6 h-6 text-blue-600 mr-2"></i>
                        <span class="text-xl font-semibold text-gray-800">Talent</span>
                    </div>
                    <button onclick="toggleMobileSidebar()" class="p-2 text-gray-600 hover:text-blue-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                
                <div class="space-y-1">
                    <a href="#" class="flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg sidebar-item">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 mr-3"></i>
                        Dashboard
                    </a>
                    <a href="#" class="flex items-center px-3 py-2 text-sm text-gray-600 hover:text-blue-600 rounded-lg sidebar-item">
                        <i data-lucide="book-open" class="w-4 h-4 mr-3"></i>
                        Learning Modules
                    </a>
                    <a href="#" class="flex items-center px-3 py-2 text-sm text-gray-600 hover:text-blue-600 rounded-lg sidebar-item">
                        <i data-lucide="clipboard-check" class="w-4 h-4 mr-3"></i>
                        Assessments
                    </a>
                    <a href="#" class="flex items-center px-3 py-2 text-sm text-gray-600 hover:text-blue-600 rounded-lg sidebar-item">
                        <i data-lucide="calendar" class="w-4 h-4 mr-3"></i>
                        Leave & Schedule
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Mobile sidebar toggle
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('mobile-sidebar');
            sidebar.classList.toggle('hidden');
        }
        
        // Add event listener to menu button
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu button
            const menuButton = document.querySelector('button.lg\\:hidden');
            if (menuButton) {
                menuButton.addEventListener('click', toggleMobileSidebar);
            }
            
            // Add click interactions to summary cards
            const summaryCards = document.querySelectorAll('.stat-card');
            summaryCards.forEach(card => {
                card.addEventListener('click', function() {
                    const title = this.querySelector('.text-sm').textContent;
                    console.log(`Viewing details for: ${title}`);
                });
            });
            
            // Add progress animation for learning modules
            const progressRing = document.querySelector('.progress-ring');
            if (progressRing) {
                setTimeout(() => {
                    progressRing.style.transform = 'scale(1.05)';
                    setTimeout(() => {
                        progressRing.style.transform = 'scale(1)';
                    }, 300);
                }, 500);
            }
        });
    </script>
    </div>
  </div>
  <script src="../../soliera.js"></script>
  <script src="../../sidebar.js"></script>
</body>
</html>
