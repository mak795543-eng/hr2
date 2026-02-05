<?php
session_start()
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HR Portal - Time & Attendance</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <style>
    .centered-hero {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 80vh; /* This will make it take most of the viewport height */
    }
  </style>
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include '../USM/sidebarr.php'; ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
        <!-- Navbar -->
        <?php include '../USM/navbar.php'; ?>
    <div class="container mx-auto px-4 py-8">
    
    <!-- Hero Section -->
    <div class="hero bg-base-200 rounded-lg mb-12">
        <div class="hero-content text-center py-12">
            <div class="max-w-2xl">
                <h1 class="text-5xl font-bold">Applicant Exam</h1>
                <p class="py-6">Start your Test</p>
                <button class="btn btn-primary" onclick="startApplication()">
                    <i class="fas fa-play-circle mr-2"></i>Begin Application Process
                </button>
            </div>
        </div>
    </div>

    <!-- Application Form -->
    <div id="applicationForm" class="hidden">
        <div class="card bg-base-100 shadow-xl mb-12">
            <div class="card-body">
                <h2 class="card-title text-2xl mb-6">
                    <i class="fas fa-user-circle mr-2"></i>Applicant Information
                </h2>
                <form id="applicantForm" method="POST" action="application_handler.php">
                    <input type="hidden" name="action" value="saveApplicant">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">First Name</span>
                            </label>
                            <input type="text" name="first_name" placeholder="Enter your First Name" class="input input-bordered" required />
                        </div>
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Last Name</span>
                            </label>
                            <input type="text" name="last_name" placeholder="Enter your Last Name" class="input input-bordered" required />
                        </div>
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Email</span>
                            </label>
                            <input type="email" name="email" placeholder="Enter Email" class="input input-bordered" required />
                        </div>
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Phone</span>
                            </label>
                            <input type="tel" name="phone" placeholder="Enter your number" class="input input-bordered" required />
                        </div>
                        <div class="form-control md:col-span-2">
                            <label class="label">
                                <span class="label-text">Position Applying For</span>
                            </label>
                            <select name="position" class="select select-bordered" id="roleSelect" required>
                                <option disabled selected>Select a position</option>
                                <option value="chef">Chef</option>
                                <option value="hr_manager">HR Manager</option>
                                <option value="staffs">Staffs</option>
                                <option value="training_coordinator">Training Coordinator</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-control mt-6">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-arrow-right mr-2"></i>Proceed to Exam
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Role Description -->
    <div id="roleDescription" class="hidden">
        <div class="card bg-base-100 shadow-xl mb-8">
            <div class="card-body">
                <h2 class="card-title text-2xl mb-4" id="roleTitle"></h2>
                <div class="flex flex-col md:flex-row gap-6">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold mb-2">Role Overview</h3>
                        <p id="roleOverview" class="mb-4"></p>
                        <h3 class="text-lg font-semibold mb-2">Exam Details</h3>
                        <ul class="list-disc pl-5" id="examDetails"></ul>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold mb-2">Skills Tested</h3>
                        <div class="flex flex-wrap gap-2 mb-4" id="skillsTested"></div>
                        <div class="mt-6">
                            <button class="btn btn-primary w-full" onclick="startExam()">
                                <i class="fas fa-play mr-2"></i>Start Exam Now
                            </button>
                            <button class="btn btn-outline mt-4 w-full" onclick="backToApplication()">
                                <i class="fas fa-arrow-left mr-2"></i>Back to Application
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Exam Section -->
    <div id="examSection" class="hidden">
        <div class="card bg-base-100 shadow-xl mb-12">
            <div class="card-body">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="card-title text-2xl" id="examTitle">Knowledge Assessment</h2>
                    <div class="badge badge-primary badge-lg p-4" id="timer">Time: 15:00</div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                    <p class="font-semibold">Instructions:</p>
                    <ul class="list-disc pl-5 mt-2">
                        <li>This exam consists of <span id="totalQuestions">10</span> multiple-choice questions</li>
                        <li>You have 15 minutes to complete the exam</li>
                        <li>Each question has only one correct answer</li>
                        <li>You cannot go back to previous questions</li>
                        <li>Your results will be displayed immediately after completion</li>
                    </ul>
                </div>

                <div class="flex justify-between items-center mb-6">
                    <div class="text-lg font-semibold">
                        Question <span id="currentQuestionNumber">1</span> of <span id="totalQuestionsCount">10</span>
                    </div>
                    <div class="flex gap-2">
                        <div class="badge badge-info" id="questionCategory">Culinary Knowledge</div>
                        <div class="badge badge-secondary" id="questionDifficulty">Medium</div>
                    </div>
                </div>

                <form id="examForm" method="POST" action="application_handler.php">
                    <input type="hidden" name="action" value="saveExam">
                    <input type="hidden" name="score" id="scoreInput">
                    <input type="hidden" name="correct_answers" id="correctInput">
                    <input type="hidden" name="total_questions" id="totalInput">
                    <input type="hidden" name="time_taken" id="timeInput">

                    <div class="space-y-8" id="questionsContainer">
                        <!-- Questions will be dynamically inserted here -->
                    </div>

                    <div class="flex justify-between mt-8">
                        <button type="button" class="btn btn-outline" id="prevBtn" onclick="prevQuestion()" disabled>
                            <i class="fas fa-arrow-left mr-2"></i>Previous
                        </button>
                        <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextQuestion()">
                            Next<i class="fas fa-arrow-right ml-2"></i>
                        </button>
                        <button type="submit" class="btn btn-success hidden" id="submitBtn">
                            <i class="fas fa-paper-plane mr-2"></i>Submit Exam
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <div id="resultsSection" class="hidden">
        <div class="card bg-base-100 shadow-xl mb-12">
            <div class="card-body">
                <h2 class="card-title text-2xl mb-6">Exam Results</h2>

                <div class="flex flex-col md:flex-row items-center justify-between gap-8 mb-8">
                    <div class="flex justify-center">
                        <div class="radial-progress bg-primary text-primary-content border-4 border-primary" style="--value:0; --size:12rem;" id="scoreCircle">0%</div>
                    </div>

                    <div class="flex-1">
                        <h3 class="text-xl font-semibold mb-2" id="resultMessage"></h3>
                        <p id="scoreText" class="text-lg mb-4"></p>
                        <div class="stats shadow">
                            <div class="stat">
                                <div class="stat-title">Correct Answers</div>
                                <div class="stat-value text-success" id="correctAnswers">0</div>
                                <div class="stat-desc">out of <span id="totalAnswers">10</span> questions</div>
                            </div>
                            <div class="stat">
                                <div class="stat-title">Time Taken</div>
                                <div class="stat-value text-info" id="timeTaken">00:00</div>
                                <div class="stat-desc">minutes:seconds</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-6 rounded-lg mb-6">
                    <h4 class="font-semibold text-lg mb-4">Detailed Results:</h4>
                    <div class="space-y-4" id="detailedResults">
                        <!-- Detailed results will be inserted here -->
                    </div>
                </div>

                <div class="flex justify-center gap-4">
                    <button class="btn btn-outline" onclick="restartExam()">
                        <i class="fas fa-redo mr-2"></i>Retake Exam
                    </button>
                    <button class="btn btn-primary">
                        <i class="fas fa-download mr-2"></i>Download Certificate
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

    <script>
        // Role-specific data
        const roleData = {
            chef: {
                title: "Chef",
                overview: "Chefs are responsible for preparing and creating menus, managing kitchen staff, ensuring food quality and safety standards, and maintaining inventory. They bring creativity and expertise to culinary operations.",
                skills: ["Culinary Techniques", "Menu Planning", "Food Safety", "Kitchen Management", "Creativity"],
                examDetails: "This exam will test your knowledge of culinary techniques, food safety practices, menu planning, and kitchen management.",
                questions: [
                    {
                        id: 1,
                        question: "What is the French term for a chef who is in charge of sauces?",
                        options: ["Saucier", "Patissier", "Poissonnier", "Entremetier"],
                        correctAnswer: 0,
                        category: "Culinary Terms",
                        difficulty: "Easy"
                    },
                    {
                        id: 2,
                        question: "Which temperature range is considered the 'danger zone' for food safety?",
                        options: [
                            "0°C to 5°C (32°F to 41°F)",
                            "5°C to 60°C (41°F to 140°F)",
                            "60°C to 100°C (140°F to 212°F)",
                            "100°C to 120°C (212°F to 248°F)"
                        ],
                        correctAnswer: 1,
                        category: "Food Safety",
                        difficulty: "Easy"
                    },
                    {
                        id: 3,
                        question: "What is the primary purpose of mise en place in a professional kitchen?",
                        options: [
                            "To clean and sanitize workstations",
                            "To prepare and organize ingredients before cooking",
                            "To train new kitchen staff",
                            "To create new menu items"
                        ],
                        correctAnswer: 1,
                        category: "Kitchen Management",
                        difficulty: "Medium"
                    },
                    {
                        id: 4,
                        question: "Which cooking method involves cooking food in a vacuum-sealed bag at precise temperatures?",
                        options: ["Sous-vide", "Braising", "Poaching", "Confit"],
                        correctAnswer: 0,
                        category: "Cooking Techniques",
                        difficulty: "Medium"
                    },
                    {
                        id: 5,
                        question: "What is the purpose of a roux in cooking?",
                        options: [
                            "To thicken sauces and soups",
                            "To add flavor to stocks",
                            "To tenderize meats",
                            "To enhance the color of dishes"
                        ],
                        correctAnswer: 0,
                        category: "Cooking Fundamentals",
                        difficulty: "Easy"
                    },
                    {
                        id: 6,
                        question: "Which knife is primarily used for chopping and dicing vegetables?",
                        options: ["Chef's knife", "Boning knife", "Paring knife", "Bread knife"],
                        correctAnswer: 0,
                        category: "Kitchen Tools",
                        difficulty: "Easy"
                    },
                    {
                        id: 7,
                        question: "What does HACCP stand for in food safety management?",
                        options: [
                            "Hazard Analysis and Critical Control Points",
                            "Health and Culinary Control Procedures",
                            "Hygiene and Cleaning Control Protocol",
                            "Handling and Cooking Control Process"
                        ],
                        correctAnswer: 0,
                        category: "Food Safety",
                        difficulty: "Hard"
                    },
                    {
                        id: 8,
                        question: "Which ingredient is essential for making a traditional hollandaise sauce?",
                        options: ["Egg yolks", "Heavy cream", "Cornstarch", "Buttermilk"],
                        correctAnswer: 0,
                        category: "Sauces",
                        difficulty: "Medium"
                    },
                    {
                        id: 9,
                        question: "What is the ideal internal temperature for cooking poultry?",
                        options: [
                            "65°C (149°F)",
                            "74°C (165°F)",
                            "80°C (176°F)",
                            "85°C (185°F)"
                        ],
                        correctAnswer: 1,
                        category: "Food Safety",
                        difficulty: "Medium"
                    },
                    {
                        id: 10,
                        question: "Which of these is NOT a mother sauce in classical French cuisine?",
                        options: ["Béchamel", "Velouté", "Marinara", "Espagnole"],
                        correctAnswer: 2,
                        category: "Classical Cuisine",
                        difficulty: "Hard"
                    }
                ]
            },
            hr_manager: {
                title: "HR Manager",
                overview: "HR Managers oversee all aspects of human resources practices and processes. They are responsible for recruitment, employee relations, performance management, training & development, and enforcing company policies.",
                skills: ["Recruitment", "Employee Relations", "Labor Laws", "Performance Management", "Conflict Resolution"],
                examDetails: "This exam will test your knowledge of HR practices, employment laws, recruitment strategies, and employee management.",
                questions: [
                    {
                        id: 1,
                        question: "What does FMLA stand for in human resources?",
                        options: [
                            "Family and Medical Leave Act",
                            "Federal Minimum Labor Agreement",
                            "Financial Management and Legal Administration",
                            "Full-time Management Leave Allowance"
                        ],
                        correctAnswer: 0,
                        category: "Employment Law",
                        difficulty: "Easy"
                    },
                    {
                        id: 2,
                        question: "Which interview technique uses a standardized set of questions for all candidates?",
                        options: ["Structured interview", "Unstructured interview", "Behavioral interview", "Stress interview"],
                        correctAnswer: 0,
                        category: "Recruitment",
                        difficulty: "Easy"
                    },
                    {
                        id: 3,
                        question: "What is the primary purpose of a 360-degree feedback system?",
                        options: [
                            "To evaluate an employee's performance from multiple perspectives",
                            "To calculate annual salary increases",
                            "To determine promotion eligibility",
                            "To assess training needs for the entire organization"
                        ],
                        correctAnswer: 0,
                        category: "Performance Management",
                        difficulty: "Medium"
                    },
                    {
                        id: 4,
                        question: "Which law prohibits discrimination based on race, color, religion, sex, or national origin?",
                        options: ["Title VII of the Civil Rights Act", "ADA", "ADEA", "EPA"],
                        correctAnswer: 0,
                        category: "Employment Law",
                        difficulty: "Medium"
                    },
                    {
                        id: 5,
                        question: "What is the typical first step in the disciplinary process?",
                        options: [
                            "Verbal warning",
                            "Written warning",
                            "Suspension",
                            "Termination"
                        ],
                        correctAnswer: 0,
                        category: "Employee Relations",
                        difficulty: "Easy"
                    },
                    {
                        id: 6,
                        question: "Which of these is NOT a protected characteristic under federal employment discrimination laws?",
                        options: ["Age", "Sex", "Education level", "Religion"],
                        correctAnswer: 2,
                        category: "Employment Law",
                        difficulty: "Medium"
                    },
                    {
                        id: 7,
                        question: "What is the primary goal of onboarding?",
                        options: [
                            "To integrate new employees into the organization",
                            "To complete necessary paperwork",
                            "To evaluate new hires during probation",
                            "To assign initial tasks and responsibilities"
                        ],
                        correctAnswer: 0,
                        category: "Recruitment",
                        difficulty: "Easy"
                    },
                    {
                        id: 8,
                        question: "Which metric measures the rate at which employees leave an organization?",
                        options: ["Turnover rate", "Retention rate", "Absenteeism rate", "Engagement score"],
                        correctAnswer: 0,
                        category: "HR Metrics",
                        difficulty: "Easy"
                    },
                    {
                        id: 9,
                        question: "What does COBRA provide for eligible employees?",
                        options: [
                            "Continuation of health insurance coverage",
                            "Severance pay after termination",
                            "Retirement benefits",
                            "Workers' compensation"
                        ],
                        correctAnswer: 0,
                        category: "Benefits Administration",
                        difficulty: "Hard"
                    },
                    {
                        id: 10,
                        question: "Which approach to conflict resolution focuses on finding mutually acceptable solutions?",
                        options: ["Collaboration", "Avoidance", "Competition", "Accommodation"],
                        correctAnswer: 0,
                        category: "Conflict Resolution",
                        difficulty: "Medium"
                    }
                ]
            },
            staffs: {
                title: "Staffs",
                overview: "Staff members are essential team players who perform various operational tasks to support organizational functions. They demonstrate reliability, adaptability, and strong work ethic in their roles.",
                skills: ["Teamwork", "Communication", "Adaptability", "Time Management", "Problem Solving"],
                examDetails: "This exam will test your general workplace skills, including communication, teamwork, problem-solving, and professional conduct.",
                questions: [
                    {
                        id: 1,
                        question: "What is the most important aspect of effective teamwork?",
                        options: ["Communication", "Individual performance", "Following orders", "Working independently"],
                        correctAnswer: 0,
                        category: "Teamwork",
                        difficulty: "Easy"
                    },
                    {
                        id: 2,
                        question: "Which communication style is most effective in the workplace?",
                        options: ["Assertive", "Aggressive", "Passive", "Passive-aggressive"],
                        correctAnswer: 0,
                        category: "Communication",
                        difficulty: "Easy"
                    },
                    {
                        id: 3,
                        question: "What should you do if you don't understand a task assigned to you?",
                        options: [
                            "Ask for clarification",
                            "Try to figure it out on your own",
                            "Ignore the task and move to something else",
                            "Delegate it to someone else"
                        ],
                        correctAnswer: 0,
                        category: "Workplace Ethics",
                        difficulty: "Easy"
                    },
                    {
                        id: 4,
                        question: "Which of these is an example of active listening?",
                        options: [
                            "Paraphrasing what the speaker said to ensure understanding",
                            "Thinking about your response while the other person is talking",
                            "Interrupting to ask questions",
                            "Remaining completely silent throughout the conversation"
                        ],
                        correctAnswer: 0,
                        category: "Communication",
                        difficulty: "Medium"
                    },
                    {
                        id: 5,
                        question: "What is the best approach to handling multiple deadlines?",
                        options: [
                            "Prioritize tasks based on urgency and importance",
                            "Work on the easiest tasks first",
                            "Ask for extensions on all deadlines",
                            "Focus on one task at a time regardless of deadlines"
                        ],
                        correctAnswer: 0,
                        category: "Time Management",
                        difficulty: "Medium"
                    },
                    {
                        id: 6,
                        question: "How should you respond to constructive criticism?",
                        options: [
                            "Listen carefully and use it as an opportunity to improve",
                            "Defend your actions and explain why you were right",
                            "Ignore it and continue with your current approach",
                            "Complain to other colleagues about the feedback"
                        ],
                        correctAnswer: 0,
                        category: "Professional Development",
                        difficulty: "Easy"
                    },
                    {
                        id: 7,
                        question: "What is the primary purpose of a workplace safety protocol?",
                        options: [
                            "To prevent accidents and injuries",
                            "To increase productivity",
                            "To reduce costs",
                            "To improve employee morale"
                        ],
                        correctAnswer: 0,
                        category: "Workplace Safety",
                        difficulty: "Easy"
                    },
                    {
                        id: 8,
                        question: "Which behavior demonstrates professional integrity?",
                        options: [
                            "Taking responsibility for your mistakes",
                            "Always agreeing with your supervisor",
                            "Working longer hours than required",
                            "Socializing with colleagues outside work"
                        ],
                        correctAnswer: 0,
                        category: "Workplace Ethics",
                        difficulty: "Medium"
                    },
                    {
                        id: 9,
                        question: "What is the most effective way to resolve a conflict with a coworker?",
                        options: [
                            "Address the issue directly and respectfully with the person",
                            "Complain to your supervisor immediately",
                            "Avoid the person to prevent further conflict",
                            "Ask other colleagues to intervene"
                        ],
                        correctAnswer: 0,
                        category: "Conflict Resolution",
                        difficulty: "Medium"
                    },
                    {
                        id: 10,
                        question: "Why is punctuality important in the workplace?",
                        options: [
                            "It shows respect for others' time and demonstrates reliability",
                            "It allows you to leave earlier at the end of the day",
                            "It impresses your supervisor more than quality work",
                            "It's required for salary increases"
                        ],
                        correctAnswer: 0,
                        category: "Professionalism",
                        difficulty: "Easy"
                    }
                ]
            },
            training_coordinator: {
                title: "Training Coordinator",
                overview: "Training Coordinators design, implement, and evaluate training programs for employees. They assess training needs, develop educational materials, and facilitate learning opportunities to enhance organizational capabilities.",
                skills: ["Instructional Design", "Needs Assessment", "Facilitation", "Evaluation Methods", "Curriculum Development"],
                examDetails: "This exam will test your knowledge of training methodologies, adult learning principles, program evaluation, and instructional design.",
                questions: [
                    {
                        id: 1,
                        question: "What does ADDIE stand for in instructional design?",
                        options: [
                            "Analysis, Design, Development, Implementation, Evaluation",
                            "Assessment, Direction, Delivery, Instruction, Examination",
                            "Approach, Design, Development, Integration, Execution",
                            "Analysis, Direction, Development, Implementation, Examination"
                        ],
                        correctAnswer: 0,
                        category: "Instructional Design",
                        difficulty: "Easy"
                    },
                    {
                        id: 2,
                        question: "Which learning theory emphasizes learning through experience and reflection?",
                        options: ["Experiential learning", "Behaviorism", "Cognitivism", "Constructivism"],
                        correctAnswer: 0,
                        category: "Learning Theories",
                        difficulty: "Medium"
                    },
                    {
                        id: 3,
                        question: "What is the primary purpose of a training needs assessment?",
                        options: [
                            "To identify gaps between current and desired performance",
                            "To determine the training budget",
                            "To select training participants",
                            "To evaluate trainer effectiveness"
                        ],
                        correctAnswer: 0,
                        category: "Needs Assessment",
                        difficulty: "Medium"
                    },
                    {
                        id: 4,
                        question: "Which evaluation level in Kirkpatrick's model measures changes in learner behavior?",
                        options: ["Level 3: Behavior", "Level 1: Reaction", "Level 2: Learning", "Level 4: Results"],
                        correctAnswer: 0,
                        category: "Evaluation",
                        difficulty: "Hard"
                    },
                    {
                        id: 5,
                        question: "What is the recommended ratio of lecture to activity in adult learning?",
                        options: [
                            "70% activity, 30% lecture",
                            "50% activity, 50% lecture",
                            "30% activity, 70% lecture",
                            "90% activity, 10% lecture"
                        ],
                        correctAnswer: 0,
                        category: "Adult Learning",
                        difficulty: "Medium"
                    },
                    {
                        id: 6,
                        question: "Which learning style prefers visual representations of information?",
                        options: ["Visual learners", "Auditory learners", "Kinesthetic learners", "Reading/Writing learners"],
                        correctAnswer: 0,
                        category: "Learning Styles",
                        difficulty: "Easy"
                    },
                    {
                        id: 7,
                        question: "What is the purpose of a learning management system (LMS)?",
                        options: [
                            "To deliver, track, and manage training programs",
                            "To create instructional materials",
                            "To assess training needs",
                            "To evaluate trainer performance"
                        ],
                        correctAnswer: 0,
                        category: "Training Technology",
                        difficulty: "Easy"
                    },
                    {
                        id: 8,
                        question: "Which technique is most effective for skills-based training?",
                        options: [
                            "Hands-on practice with feedback",
                            "Lecture-style presentation",
                            "Reading assignments",
                            "Group discussions"
                        ],
                        correctAnswer: 0,
                        category: "Training Methods",
                        difficulty: "Medium"
                    },
                    {
                        id: 9,
                        question: "What does SME stand for in training development?",
                        options: [
                            "Subject Matter Expert",
                            "Senior Management Executive",
                            "Skills Measurement Evaluation",
                            "Systematic Methodological Approach"
                        ],
                        correctAnswer: 0,
                        category: "Training Development",
                        difficulty: "Easy"
                    },
                    {
                        id: 10,
                        question: "Which factor is most important for creating an effective learning environment?",
                        options: [
                            "Psychological safety where learners feel comfortable taking risks",
                            "State-of-the-art technology and equipment",
                            "Formal classroom setting with traditional seating",
                            "Strict adherence to the training schedule"
                        ],
                        correctAnswer: 0,
                        category: "Facilitation",
                        difficulty: "Medium"
                    }
                ]
            }
        };

        // Global variables
        let currentQuestion = 0;
        let userAnswers = [];
        let timeLeft = 900; // 15 minutes in seconds
        let timerInterval;
        let selectedRole = '';
        let startTime = null;
        let endTime = null;

        // Start the application process
        function startApplication() {
            document.querySelector('.hero').classList.add('hidden');
            document.getElementById('applicationForm').classList.remove('hidden');
        }

        // Handle application form submission
        document.getElementById('applicantForm').addEventListener('submit', function(e) {
            e.preventDefault();
            selectedRole = document.getElementById('roleSelect').value;
            
            if (!roleData[selectedRole]) {
                alert('Please select a valid role.');
                return;
            }
            
            document.getElementById('applicationForm').classList.add('hidden');
            showRoleDescription();
        });

        // Show role description
        function showRoleDescription() {
            const role = roleData[selectedRole];
            document.getElementById('roleTitle').textContent = role.title;
            document.getElementById('roleOverview').textContent = role.overview;
            document.getElementById('examDetails').innerHTML = `<li>${role.examDetails}</li><li>${role.questions.length} questions with varying difficulty</li><li>15 minute time limit</li>`;
            
            // Add skills
            const skillsContainer = document.getElementById('skillsTested');
            skillsContainer.innerHTML = '';
            role.skills.forEach(skill => {
                skillsContainer.innerHTML += `<span class="badge badge-primary badge-lg">${skill}</span>`;
            });
            
            document.getElementById('roleDescription').classList.remove('hidden');
        }

        // Back to application form
        function backToApplication() {
            document.getElementById('roleDescription').classList.add('hidden');
            document.getElementById('applicationForm').classList.remove('hidden');
        }

        // Start the exam
        function startExam() {
            document.getElementById('roleDescription').classList.add('hidden');
            document.getElementById('examSection').classList.remove('hidden');
            
            const role = roleData[selectedRole];
            document.getElementById('examTitle').textContent = `${role.title} Exam`;
            document.getElementById('totalQuestions').textContent = role.questions.length;
            document.getElementById('totalQuestionsCount').textContent = role.questions.length;
            
            // Start the timer
            startTimer();
            startTime = new Date();
            
            // Display the first question
            displayQuestion(currentQuestion);
        }

        // Display a question
        function displayQuestion(index) {
            const questionsContainer = document.getElementById('questionsContainer');
            questionsContainer.innerHTML = '';
            
            const role = roleData[selectedRole];
            const question = role.questions[index];
            
            document.getElementById('currentQuestionNumber').textContent = index + 1;
            document.getElementById('questionCategory').textContent = question.category;
            document.getElementById('questionDifficulty').textContent = question.difficulty;
            
            // Update button states
            document.getElementById('prevBtn').disabled = index === 0;
            if (index === role.questions.length - 1) {
                document.getElementById('nextBtn').classList.add('hidden');
                document.getElementById('submitBtn').classList.remove('hidden');
            } else {
                document.getElementById('nextBtn').classList.remove('hidden');
                document.getElementById('submitBtn').classList.add('hidden');
            }
            
            const questionHTML = `
                <div class="question">
                    <p class="text-lg mb-6">${question.question}</p>
                    <div class="space-y-3">
                        ${question.options.map((option, i) => `
                            <div class="form-control">
                                <label class="label cursor-pointer justify-start gap-4 p-4 rounded-lg border border-gray-200 hover:bg-gray-50">
                                    <input type="radio" name="answer" value="${i}" class="radio radio-primary" ${userAnswers[index] === i ? 'checked' : ''} />
                                    <span class="label-text text-lg">${option}</span>
                                </label>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            
            questionsContainer.innerHTML = questionHTML;
        }

        // Navigate to next question
        function nextQuestion() {
            const selectedAnswer = document.querySelector('input[name="answer"]:checked');
            
            if (!selectedAnswer) {
                alert('Please select an answer before proceeding.');
                return;
            }
            
            // Save the answer
            userAnswers[currentQuestion] = parseInt(selectedAnswer.value);
            
            // Move to next question
            currentQuestion++;
            displayQuestion(currentQuestion);
        }

        // Navigate to previous question
        function prevQuestion() {
            const selectedAnswer = document.querySelector('input[name="answer"]:checked');
            
            if (selectedAnswer) {
                userAnswers[currentQuestion] = parseInt(selectedAnswer.value);
            }
            
            // Move to previous question
            currentQuestion--;
            displayQuestion(currentQuestion);
        }

        // Start the countdown timer
        function startTimer() {
            timerInterval = setInterval(function() {
                timeLeft--;
                
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                
                document.getElementById('timer').textContent = `Time: ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    submitExam();
                }
            }, 1000);
        }

        // Handle exam form submission
        document.getElementById('examForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const selectedAnswer = document.querySelector('input[name="answer"]:checked');
            
            if (selectedAnswer) {
                userAnswers[currentQuestion] = parseInt(selectedAnswer.value);
            }
            
            clearInterval(timerInterval);
            endTime = new Date();
            submitExam();
        });

        // Submit the exam and calculate results
        function submitExam() {
            const role = roleData[selectedRole];
            
            // Calculate score
            let score = 0;
            role.questions.forEach((question, index) => {
                if (userAnswers[index] === question.correctAnswer) {
                    score++;
                }
            });
            
            const percentage = (score / role.questions.length) * 100;
            
            // Hide exam section, show results section
            document.getElementById('examSection').classList.add('hidden');
            document.getElementById('resultsSection').classList.remove('hidden');
            
            // Calculate time taken
            const timeTaken = Math.floor((endTime - startTime) / 1000);
            const minutes = Math.floor(timeTaken / 60);
            const seconds = timeTaken % 60;
            document.getElementById('timeTaken').textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            // Display score with animation
            const scoreCircle = document.getElementById('scoreCircle');
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress++;
                scoreCircle.style.setProperty('--value', progress);
                scoreCircle.textContent = `${progress}%`;
                
                if (progress >= percentage) {
                    clearInterval(progressInterval);
                }
            }, 20);
            
            // Set result message
            const resultMessage = document.getElementById('resultMessage');
            if (percentage >= 80) {
                resultMessage.textContent = 'Excellent Work!';
                resultMessage.className = 'text-xl font-semibold mb-2 text-success';
            } else if (percentage >= 60) {
                resultMessage.textContent = 'Good Job!';
                resultMessage.className = 'text-xl font-semibold mb-2 text-accent';
            } else {
                resultMessage.textContent = 'Needs Improvement';
                resultMessage.className = 'text-xl font-semibold mb-2 text-error';
            }
            
            // Set score text
            document.getElementById('scoreText').textContent = `You scored ${score} out of ${role.questions.length} questions correctly.`;
            document.getElementById('correctAnswers').textContent = score;
            document.getElementById('totalAnswers').textContent = role.questions.length;
            
            // Display detailed results
            const detailedResults = document.getElementById('detailedResults');
            detailedResults.innerHTML = '';
            
            role.questions.forEach((question, index) => {
                const isCorrect = userAnswers[index] === question.correctAnswer;
                
                detailedResults.innerHTML += `
                    <div class="border-b pb-4 ${isCorrect ? 'bg-success/10 p-4 rounded-lg' : 'bg-error/10 p-4 rounded-lg'}">
                        <p class="font-semibold">Question ${index + 1}: ${question.question}</p>
                        <p class="mt-2">Your answer: ${question.options[userAnswers[index]] || 'Not answered'} 
                            <span class="${isCorrect ? 'text-success' : 'text-error'}">
                                ${isCorrect ? '✓ Correct' : '✗ Incorrect'}
                            </span>
                        </p>
                        ${!isCorrect ? `<p class="mt-1">Correct answer: ${question.options[question.correctAnswer]}</p>` : ''}
                    </div>
                `;
            });
        }

        // Restart the exam
        function restartExam() {
            currentQuestion = 0;
            userAnswers = [];
            timeLeft = 900;
            
            document.getElementById('resultsSection').classList.add('hidden');
            document.getElementById('examSection').classList.remove('hidden');
            
            startExam();
        }
    </script>
     <script src="../soliera.js"></script>
  <script src="../sidebar.js"></script>
</body>
</html>