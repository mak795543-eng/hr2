<?php
session_start();
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
  <script>
    (function () {
      if (!window.Swal || Swal.__hrPatched) return;

      const origFire = Swal.fire.bind(Swal);
      Swal.fire = function () {
        let opts = null;
        if (arguments.length === 1 && arguments[0] && typeof arguments[0] === 'object') {
          opts = Object.assign({}, arguments[0]);
        } else {
          opts = {
            title: arguments[0],
            html: arguments[1],
            icon: arguments[2]
          };
        }

        try {
          const openDialogs = Array.from(document.querySelectorAll('dialog[open]'));
          const topDialog = openDialogs.length ? openDialogs[openDialogs.length - 1] : null;
          if (topDialog && !opts.target) {
            opts.target = topDialog;
          }
        } catch (e) {
        }

        if (typeof opts.heightAuto === 'undefined') {
          opts.heightAuto = false;
        }

        return origFire(opts);
      };

      Swal.__hrPatched = true;
    })();
  </script>
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php 
    // Use relative path or absolute path based on your directory structure
    include '../../../USM/sidebarr.php'; 
    ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../../../USM/navbar.php'; ?>

      <main class="container mx-auto px-4 py-8">
        <style>
          :root {
            --text-color: #2D2D2D;
            --background-color: #FFFFFF;
            --primary-color: #DBEAF6;
            --secondary-color: #ACACAC;
            --accent-color: #2B6CB0;
          }

          .swal2-container {
            position: fixed !important;
            inset: 0 !important;
            z-index: 2147483647 !important;
            pointer-events: auto !important;
          }

          .swal2-popup {
            z-index: 2147483647 !important;
          }

          body {
            color: var(--text-color);
            background-color: var(--background-color);
          }

          .fade-in {
            animation: fadeIn 0.5s ease-in-out;
          }

          @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
          }

          .form-card {
            transition: all 0.3s ease;
            background-color: var(--background-color);
            border: 1px solid var(--primary-color);
          }
          
          .form-card:hover {
            box-shadow: 0 10px 25px -5px rgba(43, 108, 176, 0.1);
          }
          
          .btn-primary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #2a5a9c;
            border-color: #2a5a9c;
            transform: translateY(-2px);
        }
        
        .btn-primary:active {
            background-color: #244d87;
            transform: translateY(0);
        }
        
        .btn-outline {
            border-color: var(--secondary-color);
            color: var(--text-color);
            transition: all 0.3s ease;
        }
        
        .btn-outline:hover {
            background-color: var(--primary-color);
            border-color: var(--accent-color);
            transform: translateY(-2px);
        }
        
        .form-input {
            border: 1px solid var(--secondary-color);
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.1);
        }
        
        .icon {
            width: 24px;
            height: 24px;
            stroke: currentColor;
        }
        
        .section-hidden {
            display: none;
        }
        
        .terms-content {
            max-height: 60vh;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8 max-w-8xl">

        <!-- Personal Information Form -->
        <section id="personal-info-form" class="fade-in">
            <div class="form-card card shadow-sm">
                <div class="card-body p-8">
                    <h2 class="card-title text-2xl text-[#2D2D2D] mb-6">Personal Information</h2>
                    
                    <form id="applicant-form" class="space-y-6">
                        <!-- Name Section -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-[#2D2D2D] font-medium">First Name</span>
                                    <span class="label-text-alt text-[#ACACAC]">Required</span>
                                </label>
                                <input type="text" id="first-name" placeholder="Enter your first name" class="input input-bordered form-input w-full" required>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-[#2D2D2D] font-medium">Middle Name</span>
                                    <span class="label-text-alt text-[#ACACAC]">Optional</span>
                                </label>
                                <input type="text" id="middle-name" placeholder="Enter your middle name" class="input input-bordered form-input w-full">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-[#2D2D2D] font-medium">Last Name</span>
                                    <span class="label-text-alt text-[#ACACAC]">Required</span>
                                </label>
                                <input type="text" id="last-name" placeholder="Enter your last name" class="input input-bordered form-input w-full" required>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-[#2D2D2D] font-medium">Suffix</span>
                                    <span class="label-text-alt text-[#ACACAC]">Optional</span>
                                </label>
                                <select id="suffix" class="select select-bordered form-input w-full">
                                    <option value="" selected>Select suffix</option>
                                    <option value="Jr.">Jr.</option>
                                    <option value="Sr.">Sr.</option>
                                    <option value="II">II</option>
                                    <option value="III">III</option>
                                    <option value="IV">IV</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Contact Information -->
                        <div class="grid grid-cols-1 gap-6">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-[#2D2D2D] font-medium">Email Address</span>
                                    <span class="label-text-alt text-[#ACACAC]">Required</span>
                                </label>
                                <input type="email" id="email" placeholder="Enter your email address" class="input input-bordered form-input w-full" required>
                                <label class="label">
                                    <span class="label-text-alt text-[#ACACAC]">We'll send exam results to this email</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Additional Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-[#2D2D2D] font-medium">Phone Number</span>
                                    <span class="label-text-alt text-[#ACACAC]">Optional</span>
                                </label>
                                <input type="tel" id="phone" placeholder="Enter your phone number" class="input input-bordered form-input w-full">
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-[#2D2D2D] font-medium">Date of Birth</span>
                                    <span class="label-text-alt text-[#ACACAC]">Optional</span>
                                </label>
                                <input type="date" id="dob" class="input input-bordered form-input w-full">
                            </div>
                        </div>
                        
                        <!-- Terms Agreement -->
                        <div class="form-control mt-8">
                            <label class="label cursor-pointer justify-start">
                                <input type="checkbox" id="privacy-agreement" class="checkbox checkbox-primary mr-3" required>
                                <span class="label-text text-[#2D2D2D]">
                                    I agree to the 
                                    <a href="#privacy-terms-modal" class="link link-primary">Privacy Policy</a> 
                                    and 
                                    <a href="#privacy-terms-modal" class="link link-primary">Terms of Service</a>
                                </span>
                            </label>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="flex justify-end mt-8">
                            <button type="submit" class="btn btn-primary">
                                <span>Continue to Role Selection</span>
                                <svg class="icon ml-1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m9 18 6-6-6-6"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <!-- Role Selection Section (Hidden Initially) -->
        <section id="role-selection" class="section-hidden fade-in">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-[#2D2D2D]">Choose Your Role</h2>
                <button id="back-to-form" class="btn btn-outline btn-sm">
                    <svg class="icon mr-1" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                    Back to Form
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
                <!-- Chef Role Card -->
                <div class="form-card card shadow-sm">
                    <div class="card-body items-center text-center p-8">
                        <div class="w-16 h-16 bg-[#DBEAF6] rounded-full flex items-center justify-center mb-4">
                            <svg class="icon text-[#2B6CB0]" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"/>
                                <line x1="6" x2="18" y1="17" y2="17"/>
                            </svg>
                        </div>
                        <h3 class="card-title text-xl text-[#2D2D2D] mb-2">Chef</h3>
                        <p class="text-[#2D2D2D] mb-6">Culinary positions requiring cooking expertise and kitchen management skills</p>
                        <button class="btn btn-primary w-full select-role" data-role="chef">
                            <span>Select Role</span>
                            <svg class="icon ml-1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Crew Role Card -->
                <div class="form-card card shadow-sm">
                    <div class="card-body items-center text-center p-8">
                        <div class="w-16 h-16 bg-[#DBEAF6] rounded-full flex items-center justify-center mb-4">
                            <svg class="icon text-[#2B6CB0]" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </div>
                        <h3 class="card-title text-xl text-[#2D2D2D] mb-2">Crew</h3>
                        <p class="text-[#2D2D2D] mb-6">Front-line positions in customer service, operations, and team collaboration</p>
                        <button class="btn btn-primary w-full select-role" data-role="crew">
                            <span>Select Role</span>
                            <svg class="icon ml-1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Available Exams Section (Hidden Initially) -->
        <section id="available-exams" class="section-hidden fade-in">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-[#2D2D2D]">Available Exams for <span id="selected-role" class="text-[#2B6CB0]"></span></h2>
                <button id="change-role" class="btn btn-outline btn-sm">
                    <svg class="icon mr-1" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                    Change Role
                </button>
            </div>
            
            <!-- Chef Exams -->
            <div id="chef-exams" class="hidden">
                <div class="grid grid-cols-1 gap-6">
                    <div class="form-card card bg-white">
                        <div class="card-body">
                            <div class="flex flex-col md:flex-row md:items-center justify-between">
                                <div class="mb-4 md:mb-0">
                                    <h3 class="card-title text-lg text-[#2D2D2D]">Culinary Knowledge Test</h3>
                                    <p class="text-[#2D2D2D] mt-1">Test your knowledge of cooking techniques, ingredients, and kitchen safety protocols.</p>
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        <span class="badge bg-[#DBEAF6] text-[#2B6CB0] border-0">30 Questions</span>
                                        <span class="badge bg-[#DBEAF6] text-[#2B6CB0] border-0">60 Minutes</span>
                                        <span class="badge bg-[#DBEAF6] text-[#2B6CB0] border-0">Professional Level</span>
                                    </div>
                                </div>
                                <button class="btn btn-primary start-exam mt-4 md:mt-0" data-exam="culinary-test">
                                    <span>Start Exam</span>
                                    <svg class="icon ml-1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="6 3 20 12 6 21 6 3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card card bg-white">
                        <div class="card-body">
                            <div class="flex flex-col md:flex-row md:items-center justify-between">
                                <div class="mb-4 md:mb-0">
                                    <h3 class="card-title text-lg text-[#2D2D2D]">Food Safety Certification</h3>
                                    <p class="text-[#2D2D2D] mt-1">Demonstrate your understanding of food handling, storage, and safety regulations.</p>
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        <span class="badge bg-[#DBEAF6] text-[#2B6CB0] border-0">25 Questions</span>
                                        <span class="badge bg-[#DBEAF6] text-[#2B6CB0] border-0">45 Minutes</span>
                                        <span class="badge bg-[#DBEAF6] text-[#2B6CB0] border-0">Certification</span>
                                    </div>
                                </div>
                                <button class="btn btn-primary start-exam mt-4 md:mt-0" data-exam="food-safety">
                                    <span>Start Exam</span>
                                    <svg class="icon ml-1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="6 3 20 12 6 21 6 3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Crew Exams -->
            <div id="crew-exams" class="hidden">
                <div class="grid grid-cols-1 gap-6">
                    <div class="form-card card bg-white">
                        <div class="card-body">
                            <div class="flex flex-col md:flex-row md:items-center justify-between">
                                <div class="mb-4 md:mb-0">
                                    <h3 class="card-title text-lg text-[#2D2D2D]">Customer Service Skills</h3>
                                    <p class="text-[#2D2D2D] mt-1">Evaluate your ability to handle customer interactions and resolve issues effectively.</p>
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        <span class="badge bg-[#DBEAF6] text-[#2B6CB0] border-0">20 Questions</span>
                                        <span class="badge bg-[#DBEAF6] text-[#2B6CB0] border-0">30 Minutes</span>
                                        <span class="badge bg-[#DBEAF6] text-[#2B6CB0] border-0">Entry Level</span>
                                    </div>
                                </div>
                                <button class="btn btn-primary start-exam mt-4 md:mt-0" data-exam="customer-service">
                                    <span>Start Exam</span>
                                    <svg class="icon ml-1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="6 3 20 12 6 21 6 3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card card bg-white">
                        <div class="card-body">
                            <div class="flex flex-col md:flex-row md:items-center justify-between">
                                <div class="mb-4 md:mb-0">
                                    <h3 class="card-title text-lg text-[#2D2D2D]">Teamwork Assessment</h3>
                                    <p class="text-[#2D2D2D] mt-1">Assess your collaboration skills and ability to work effectively in a team environment.</p>
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        <span class="badge bg-[#DBEAF6] text-[#2B6CB0] border-0">15 Questions</span>
                                        <span class="badge bg-[#DBEAF6] text-[#2B6CB0] border-0">25 Minutes</span>
                                        <span class="badge bg-[#DBEAF6] text-[#2B6CB0] border-0">Behavioral</span>
                                    </div>
                                </div>
                                <button class="btn btn-primary start-exam mt-4 md:mt-0" data-exam="teamwork">
                                    <span>Start Exam</span>
                                    <svg class="icon ml-1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="6 3 20 12 6 21 6 3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Privacy & Terms Modal for Personal Info -->
    <dialog id="privacy-terms-modal" class="modal">
        <div class="modal-box max-w-4xl max-h-screen bg-white">
            <h3 class="font-bold text-2xl mb-4 text-center text-[#2D2D2D]">Privacy Policy & Terms of Service</h3>
            <div class="divider bg-[#DBEAF6]"></div>
            
            <div class="terms-content p-2 mb-6">
                <div class="bg-[#DBEAF6] p-4 mb-6 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="icon text-[#2B6CB0]" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" x2="12" y1="8" y2="12"/>
                                <line x1="12" x2="12.01" y1="16" y2="16"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-[#2D2D2D]">
                                <strong>Important:</strong> Please read these terms carefully before submitting your personal information.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <div>
                        <h4 class="font-bold text-lg mb-2 text-[#2D2D2D]">1. Data Collection & Usage</h4>
                        <ul class="list-disc pl-5 space-y-2 text-[#2D2D2D]">
                            <li>We collect personal information solely for recruitment and examination purposes.</li>
                            <li>Your data will be used to process your application and communicate exam results.</li>
                            <li>We do not share your personal information with third parties without your consent.</li>
                            <li>All data is stored securely and retained only for the duration of the recruitment process.</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-lg mb-2 text-[#2D2D2D]">2. Applicant Rights</h4>
                        <ul class="list-disc pl-5 space-y-2 text-[#2D2D2D]">
                            <li>You have the right to access, correct, or delete your personal information.</li>
                            <li>You may withdraw your application at any time by contacting us.</li>
                            <li>Exam results and personal data will be handled confidentially.</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-lg mb-2 text-[#2D2D2D]">3. Communication</h4>
                        <ul class="list-disc pl-5 space-y-2 text-[#2D2D2D]">
                            <li>We will contact you via the email address provided for exam-related communications.</li>
                            <li>You may receive notifications about your application status and exam results.</li>
                            <li>You can opt-out of non-essential communications at any time.</li>
                        </ul>
                    </div>
                    
                    <div class="bg-[#DBEAF6] p-4 rounded-lg border border-[#2B6CB0]">
                        <h4 class="font-bold text-lg mb-2 text-[#2D2D2D]">Contact Information</h4>
                        <p class="text-[#2D2D2D]">If you have any questions about our privacy practices, please contact our recruitment team at recruitment@company.com</p>
                    </div>
                </div>
            </div>
            
            <div class="modal-action">
                <button class="btn btn-primary" onclick="document.getElementById('privacy-terms-modal').close()">
                    <svg class="icon mr-1" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    I Understand
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Applicant Summary Modal -->
    <dialog id="applicant-summary" class="modal">
        <div class="modal-box max-w-2xl bg-white">
            <h3 class="font-bold text-2xl mb-4 text-center text-[#2D2D2D]">Application Summary</h3>
            <div class="divider bg-[#DBEAF6]"></div>
            
            <div class="space-y-4 mb-6">
                <h4 class="font-bold text-lg text-[#2D2D2D]">Personal Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <span class="font-medium text-[#2D2D2D]">Full Name:</span>
                        <p id="summary-fullname" class="text-[#2D2D2D]">-</p>
                    </div>
                    <div>
                        <span class="font-medium text-[#2D2D2D]">Email:</span>
                        <p id="summary-email" class="text-[#2D2D2D]">-</p>
                    </div>
                    <div>
                        <span class="font-medium text-[#2D2D2D]">Phone:</span>
                        <p id="summary-phone" class="text-[#2D2D2D]">-</p>
                    </div>
                    <div>
                        <span class="font-medium text-[#2D2D2D]">Date of Birth:</span>
                        <p id="summary-dob" class="text-[#2D2D2D]">-</p>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h4 class="font-bold text-lg text-[#2D2D2D]">Selected Role</h4>
                    <p id="summary-role" class="text-[#2D2D2D]">-</p>
                </div>
            </div>
            
            <div class="modal-action">
                <button class="btn btn-outline" id="edit-application">
                    <svg class="icon mr-1" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                    Edit Information
                </button>
                <button class="btn btn-primary" id="confirm-application">
                    <svg class="icon mr-1" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Confirm & Continue
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Exam Terms & Conditions Modal -->
    <dialog id="exam-terms-modal" class="modal">
        <div class="modal-box max-w-4xl max-h-screen bg-white">
            <h3 class="font-bold text-2xl mb-4 text-center text-[#2D2D2D]">Examination Terms & Conditions</h3>
            <div class="divider bg-[#DBEAF6]"></div>
            
            <div class="terms-content p-2 mb-6">
                <div class="bg-[#DBEAF6] p-4 mb-6 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="icon text-[#2B6CB0]" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" x2="12" y1="8" y2="12"/>
                                <line x1="12" x2="12.01" y1="16" y2="16"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-[#2D2D2D]">
                                <strong>Important:</strong> Please read these terms carefully before starting your examination. By clicking "I Agree", you acknowledge that you have read, understood, and agree to be bound by these terms and conditions.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <div>
                        <h4 class="font-bold text-lg mb-2 text-[#2D2D2D]">1. Examination Rules</h4>
                        <ul class="list-disc pl-5 space-y-2 text-[#2D2D2D]">
                            <li>The examination must be completed in one sitting. You cannot pause or save your progress.</li>
                            <li>Once started, the timer will count down continuously until the examination time expires.</li>
                            <li>All questions must be answered before submission. Incomplete examinations will not be graded.</li>
                            <li>You are permitted only one attempt per examination unless otherwise specified.</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-lg mb-2 text-[#2D2D2D]">2. Technical Requirements</h4>
                        <ul class="list-disc pl-5 space-y-2 text-[#2D2D2D]">
                            <li>Ensure you have a stable internet connection throughout the examination.</li>
                            <li>Use a supported browser (Chrome, Firefox, Safari, or Edge) with JavaScript enabled.</li>
                            <li>Do not refresh the page or use the browser's back button during the examination.</li>
                            <li>Technical issues caused by your equipment or internet connection are your responsibility.</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-lg mb-2 text-[#2D2D2D]">3. Academic Integrity</h4>
                        <ul class="list-disc pl-5 space-y-2 text-[#2D2D2D]">
                            <li>This examination must be completed individually without assistance from others.</li>
                            <li>You may not use unauthorized materials, resources, or devices during the examination.</li>
                            <li>Sharing examination content with others is strictly prohibited.</li>
                            <li>Any attempt to circumvent security measures will result in immediate disqualification.</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-lg mb-2 text-[#2D2D2D]">4. Results & Certification</h4>
                        <ul class="list-disc pl-5 space-y-2 text-[#2D2D2D]">
                            <li>Examination results are final and not subject to appeal.</li>
                            <li>Passing scores are determined by the examination committee and may vary by role.</li>
                            <li>Certification, if applicable, will be issued only upon successful completion.</li>
                            <li>Results will be available immediately after submission and may be shared with hiring managers.</li>
                        </ul>
                    </div>
                    
                    <div class="bg-[#DBEAF6] p-4 rounded-lg border border-[#2B6CB0]">
                        <h4 class="font-bold text-lg mb-2 text-[#2D2D2D]">Examination Details</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="font-medium text-[#2D2D2D]">Exam:</span> <span id="terms-exam-title" class="text-[#2D2D2D]">Culinary Knowledge Test</span>
                            </div>
                            <div>
                                <span class="font-medium text-[#2D2D2D]">Duration:</span> <span id="terms-exam-duration" class="text-[#2D2D2D]">60 minutes</span>
                            </div>
                            <div>
                                <span class="font-medium text-[#2D2D2D]">Questions:</span> <span id="terms-exam-questions" class="text-[#2D2D2D]">30</span>
                            </div>
                            <div>
                                <span class="font-medium text-[#2D2D2D]">Passing Score:</span> <span id="terms-passing-score" class="text-[#2D2D2D]">70%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-action flex flex-col sm:flex-row gap-3">
                <button class="btn btn-outline order-2 sm:order-1" id="decline-exam-terms">
                    <svg class="icon mr-1" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" x2="6" y1="6" y2="18"/>
                        <line x1="6" x2="18" y1="6" y2="18"/>
                    </svg>
                    Decline
                </button>
                <div class="flex items-center order-1 sm:order-2 mb-4 sm:mb-0">
                    <input type="checkbox" id="agree-exam-checkbox" class="checkbox bg-white border-[#ACACAC] checked:border-[#2B6CB0] [--chkbg:theme(colors.[#2B6CB0])] [--chkfg:white] mr-2">
                    <label for="agree-exam-checkbox" class="cursor-pointer text-[#2D2D2D]">I have read and agree to the Terms & Conditions</label>
                </div>
                <button class="btn btn-primary order-3" id="agree-exam-terms" disabled>
                    <svg class="icon mr-1" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    I Agree & Start Exam
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Exam Confirmation Modal -->
    <dialog id="exam-confirmation" class="modal">
        <div class="modal-box text-center bg-white max-w-md">
            <div class="flex justify-center mb-4">
                <div class="w-16 h-16 bg-[#DBEAF6] rounded-full flex items-center justify-center">
                    <svg class="icon text-[#2B6CB0]" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
            </div>
            <h3 class="font-bold text-lg mb-2 text-[#2D2D2D]">Exam Ready to Start!</h3>
            <p class="py-4 text-[#2D2D2D]">You have agreed to the terms and conditions. Your exam will now begin.</p>
            <p class="text-sm text-[#ACACAC] mb-4">Good luck with your examination!</p>
            <div class="modal-action justify-center">
                <button class="btn btn-primary" id="start-exam-final">
                    <svg class="icon mr-1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="6 3 20 12 6 21 6 3"/>
                    </svg>
                    Begin Exam
                </button>
            </div>
        </div>
    </dialog>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Application data storage
        let applicationData = {};
        let currentExam = {};
        
        // Form submission
        document.getElementById('applicant-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Collect form data
            applicationData = {
                firstName: document.getElementById('first-name').value,
                middleName: document.getElementById('middle-name').value,
                lastName: document.getElementById('last-name').value,
                suffix: document.getElementById('suffix').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                dob: document.getElementById('dob').value
            };
            
            // Validate required fields
            if (!applicationData.firstName || !applicationData.lastName || !applicationData.email) {
                Swal.fire({
                    title: 'Required Fields',
                    text: 'Please fill in all required fields',
                    icon: 'warning',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#3b82f6'
                });
                return;
            }
            
            if (!document.getElementById('privacy-agreement').checked) {
                Swal.fire({
                    title: 'Agreement Required',
                    text: 'Please agree to the Privacy Policy and Terms of Service',
                    icon: 'warning',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#3b82f6'
                });
                return;
            }
            
            // Show role selection
            document.getElementById('personal-info-form').classList.add('section-hidden');
            document.getElementById('role-selection').classList.remove('section-hidden');
            
            // Update progress steps
            document.querySelectorAll('.step')[0].classList.remove('step-primary');
            document.querySelectorAll('.step')[1].classList.add('step-primary');
        });
        
        // Back to form button
        document.getElementById('back-to-form').addEventListener('click', function() {
            document.getElementById('role-selection').classList.add('section-hidden');
            document.getElementById('personal-info-form').classList.remove('section-hidden');
            
            // Update progress steps
            document.querySelectorAll('.step')[1].classList.remove('step-primary');
            document.querySelectorAll('.step')[0].classList.add('step-primary');
        });
        
        // Role selection
        document.querySelectorAll('.select-role').forEach(button => {
            button.addEventListener('click', function() {
                const role = this.getAttribute('data-role');
                applicationData.selectedRole = role;
                
                // Update summary modal
                updateSummaryModal();
                
                // Show summary modal
                document.getElementById('applicant-summary').showModal();
                
                // Refresh icons in modal
                lucide.createIcons();
            });
        });
        
        // Update summary modal with application data
        function updateSummaryModal() {
            // Format full name
            let fullName = `${applicationData.firstName} `;
            if (applicationData.middleName) {
                fullName += `${applicationData.middleName} `;
            }
            fullName += applicationData.lastName;
            if (applicationData.suffix) {
                fullName += ` ${applicationData.suffix}`;
            }
            
            document.getElementById('summary-fullname').textContent = fullName;
            document.getElementById('summary-email').textContent = applicationData.email;
            document.getElementById('summary-phone').textContent = applicationData.phone || 'Not provided';
            document.getElementById('summary-dob').textContent = applicationData.dob || 'Not provided';
            document.getElementById('summary-role').textContent = applicationData.selectedRole.charAt(0).toUpperCase() + applicationData.selectedRole.slice(1);
        }
        
        // Edit application from summary modal
        document.getElementById('edit-application').addEventListener('click', function() {
            document.getElementById('applicant-summary').close();
        });
        
        // Confirm application and proceed to exams
        document.getElementById('confirm-application').addEventListener('click', function() {
            document.getElementById('applicant-summary').close();
            
            // Show available exams
            document.getElementById('role-selection').classList.add('section-hidden');
            document.getElementById('available-exams').classList.remove('section-hidden');
            
            // Update selected role display
            document.getElementById('selected-role').textContent = applicationData.selectedRole.charAt(0).toUpperCase() + applicationData.selectedRole.slice(1);
            
            // Show appropriate exams
            document.getElementById('chef-exams').classList.add('hidden');
            document.getElementById('crew-exams').classList.add('hidden');
            document.getElementById(`${applicationData.selectedRole}-exams`).classList.remove('hidden');
            
            // Update progress steps
            document.querySelectorAll('.step')[1].classList.remove('step-primary');
            document.querySelectorAll('.step')[2].classList.add('step-primary');
            
            // Refresh icons for newly shown content
            lucide.createIcons();
        });
        
        // Change role button
        document.getElementById('change-role').addEventListener('click', function() {
            document.getElementById('available-exams').classList.add('section-hidden');
            document.getElementById('role-selection').classList.remove('section-hidden');
            
            // Update progress steps
            document.querySelectorAll('.step')[2].classList.remove('step-primary');
            document.querySelectorAll('.step')[1].classList.add('step-primary');
        });
        
        // Start exam functionality with terms & conditions
        document.querySelectorAll('.start-exam').forEach(button => {
            button.addEventListener('click', function() {
                const exam = this.getAttribute('data-exam');
                
                // Set exam details based on the selected exam
                let title, duration, questions, passingScore;
                
                switch(exam) {
                    case 'culinary-test':
                        title = 'Culinary Knowledge Test';
                        duration = '60 minutes';
                        questions = '30';
                        passingScore = '70%';
                        break;
                    case 'food-safety':
                        title = 'Food Safety Certification';
                        duration = '45 minutes';
                        questions = '25';
                        passingScore = '80%';
                        break;
                    case 'customer-service':
                        title = 'Customer Service Skills';
                        duration = '30 minutes';
                        questions = '20';
                        passingScore = '70%';
                        break;
                    case 'teamwork':
                        title = 'Teamwork Assessment';
                        duration = '25 minutes';
                        questions = '15';
                        passingScore = '65%';
                        break;
                }
                
                // Store current exam data
                currentExam = {
                    title: title,
                    duration: duration,
                    questions: questions,
                    passingScore: passingScore
                };
                
                // Update terms modal with exam details
                document.getElementById('terms-exam-title').textContent = title;
                document.getElementById('terms-exam-duration').textContent = duration;
                document.getElementById('terms-exam-questions').textContent = questions;
                document.getElementById('terms-passing-score').textContent = passingScore;
                
                // Reset checkbox
                document.getElementById('agree-exam-checkbox').checked = false;
                document.getElementById('agree-exam-terms').disabled = true;
                
                // Show the terms modal
                document.getElementById('exam-terms-modal').showModal();
                
                // Refresh icons in modal
                lucide.createIcons();
            });
        });
        
        // Exam terms agreement checkbox
        document.getElementById('agree-exam-checkbox').addEventListener('change', function() {
            document.getElementById('agree-exam-terms').disabled = !this.checked;
        });
        
        // Decline exam terms
        document.getElementById('decline-exam-terms').addEventListener('click', function() {
            document.getElementById('exam-terms-modal').close();
        });
        
        // Agree to exam terms
        document.getElementById('agree-exam-terms').addEventListener('click', function() {
            document.getElementById('exam-terms-modal').close();
            document.getElementById('exam-confirmation').showModal();
            
            // Refresh icons in confirmation modal
            lucide.createIcons();
        });
        
        // Start exam final
        document.getElementById('start-exam-final').addEventListener('click', function() {
            document.getElementById('exam-confirmation').close();
            Swal.fire({
                title: 'Starting Exam',
                html: `
                    <div class="text-left">
                        <p><strong>Exam:</strong> ${currentExam.title}</p>
                        <p><strong>Applicant:</strong> ${applicationData.firstName} ${applicationData.lastName}</p>
                        <p><strong>Role:</strong> ${applicationData.selectedRole}</p>
                        <p class="mt-3 text-sm text-gray-600">This would redirect to the actual exam interface in a real application.</p>
                    </div>
                `,
                icon: 'info',
                confirmButtonText: 'OK',
                confirmButtonColor: '#3b82f6'
            });
        });
        
        // Privacy terms modal links
        document.querySelectorAll('a[href="#privacy-terms-modal"]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('privacy-terms-modal').showModal();
                lucide.createIcons();
            });
        });
    </script>
  <script src="../../soliera.js"></script>
  <script src="../../sidebar.js"></script>
</body>

</html>
