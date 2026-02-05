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
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <style>
          .question-card {
            transition: all 0.3s ease;
          }
          .question-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
          }
          .option-image-preview {
            max-width: 150px;
            max-height: 100px;
            object-fit: contain;
          }
          .other-option-input {
            border: none !important;
            background: transparent !important;
            box-shadow: none !important;
            padding-left: 0 !important;
            margin-left: -8px;
            color: #6b7280 !important;
          }
          .other-option-input:focus {
            outline: none !important;
            border: none !important;
            box-shadow: none !important;
          }
          .other-option-input::placeholder {
            color: #6b7280 !important;
            font-style: italic;
          }
          .other-label {
            color: #6b7280;
            font-weight: 500;
          }
          .answer-input {
            background-color: #f9fafb;
            border-left: 4px solid #3b82f6;
          }
          .correct-answer {
            background-color: #d1fae5 !important;
            border-color: #10b981 !important;
          }
          .answer-key-indicator {
            background-color: #10b981;
            color: white;
            border-radius: 4px;
            padding: 2px 8px;
            font-size: 0.75rem;
            margin-left: 8px;
          }
        </style>

        <script>
          tailwind.config = {
            theme: {
              extend: {
                colors: {
                  primary: '#3b82f6',
                  secondary: '#10b981',
                  accent: '#8b5cf6',
                }
              }
            }
          }
        </script>

        <!-- Main Form Container -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
          <form id="examForm">
            <!-- Section 1: Exam Details -->
            <div class="p-6 md:p-8 border-b border-gray-200">
              <div class="flex items-center mb-6">
                <div class="bg-primary text-white rounded-full w-8 h-8 flex items-center justify-center mr-3">
                  <span>1</span>
                </div>
                <h2 class="text-xl font-bold text-gray-800">Examination Details</h2>
              </div>

              <div class="space-y-6">
                <!-- Examination Type Dropdown -->
                <div class="form-control">
                  <label class="label">
                    <span class="label-text font-semibold text-gray-700">Type</span>
                  </label>
                  <select id="examType" name="examType" class="select select-bordered w-full focus:select-primary" required>
                    <option value="" disabled selected>Select examination type</option>
                    <option value="applicant">Applicant Examination</option>
                    <option value="employee">Employee Examination</option>
                  </select>
                </div>

                <!-- Exam Title -->
                <div class="form-control">
                  <label class="label">
                    <span class="label-text font-semibold text-gray-700">Examination Title</span>
                  </label>
                  <input type="text" id="examTitle" name="examTitle" placeholder="Enter examination title" 
                    class="input input-bordered w-full focus:input-primary" required>
                </div>

                <!-- Description -->
                <div class="form-control">
                  <label class="label">
                    <span class="label-text font-semibold text-gray-700">Description</span>
                  </label>
                  <textarea id="examDescription" name="examDescription" 
                    placeholder="Enter examination description" 
                    class="textarea textarea-bordered h-24 focus:textarea-primary"></textarea>
                </div>

                <!-- Module Selection -->
                <div class="form-control">
                  <label class="label">
                    <span class="label-text font-semibold text-gray-700">Based on Module</span>
                  </label>
                  <select id="moduleSelect" name="moduleSelect" 
                    class="select select-bordered w-full focus:select-primary" required>
                    <option value="" disabled selected>Select a module</option>
                    <option value="module1">Introduction to Programming</option>
                    <option value="module2">Data Structures and Algorithms</option>
                    <option value="module3">Database Management Systems</option>
                    <option value="module4">Web Development</option>
                    <option value="module5">Software Engineering</option>
                  </select>
                </div>

                <!-- Module Information -->
                <div id="moduleInfo" class="bg-gray-50 rounded-xl p-5 border border-gray-200 hidden transition-all duration-300">
                  <h3 class="font-bold text-lg text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-info-circle text-primary mr-2"></i>
                    Module Information
                  </h3>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <span class="font-semibold text-gray-700">Department:</span>
                      <span id="departmentInfo" class="ml-2 text-gray-600">-</span>
                    </div>
                    <div>
                      <span class="font-semibold text-gray-700">Roles:</span>
                      <span id="rolesInfo" class="ml-2 text-gray-600">-</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Section 2: Questions -->
            <div class="p-6 md:p-8">
              <div class="flex items-center mb-6">
                <div class="bg-primary text-white rounded-full w-8 h-8 flex items-center justify-center mr-3">
                  <span>2</span>
                </div>
                <h2 class="text-xl font-bold text-gray-800">Examination Questions</h2>
              </div>

              <p class="text-gray-600 mb-6">Add questions to your examination. You can add multiple choice, true/false, short answer, or identification questions.</p>

              <!-- Questions Container -->
              <div id="questionsContainer" class="space-y-6 mb-6">
                <!-- Questions will be added here dynamically -->
              </div>

              <!-- Add Question Button -->
              <div class="flex justify-center">
                <button type="button" id="addQuestion" class="btn btn-outline">
                  <i class="fas fa-plus mr-2"></i>
                  Add Question
                </button>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="bg-gray-50 p-6 md:p-8 border-t border-gray-200">
              <div class="flex flex-col md:flex-row justify-between gap-4">
                <button type="button" id="saveDraft" class="btn btn-ghost order-2 md:order-1">
                  <i class="fas fa-save mr-2"></i>
                  Save as Draft
                </button>
                <div class="flex gap-3 order-1 md:order-2">
                  <button type="button" id="previewExam" class="btn btn-outline">
                    <i class="fas fa-eye mr-2"></i>
                    Preview
                  </button>
                  <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check-circle mr-2"></i>
                    Create Examination
                  </button>
                </div>
              </div>
            </div>
          </form>
        </div>
      </main>
    </div>
  </div>

  <!-- Answer Key Modal -->
  <div id="answerKeyModal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box max-w-2xl">
      <h3 class="font-bold text-lg mb-4">Set Answer Key</h3>
      
      <div id="modalQuestionContent" class="mb-6">
        <!-- Question content will be inserted here -->
      </div>
      
      <div class="form-control mb-4">
        <label class="label">
          <span class="label-text font-semibold">Points</span>
        </label>
        <input type="number" id="questionPoints" min="1" max="100" value="1" 
          class="input input-bordered w-24">
      </div>
      
      <div id="answerKeyOptions" class="mb-6">
        <!-- Answer options will be inserted here based on question type -->
      </div>
      
      <div class="modal-action">
        <button class="btn btn-ghost" id="closeAnswerKeyModal">Cancel</button>
        <button class="btn btn-primary" id="saveAnswerKey">Save Answer Key</button>
      </div>
    </div>
  </div>

  <!-- Question Template (Hidden) -->
  <template id="questionTemplate">
    <div class="question-item bg-base-100 border border-gray-200 rounded-xl p-5 shadow-sm transition-all duration-300 hover:shadow-md" data-question-id="">
      <div class="flex justify-between items-start mb-4">
        <div class="flex items-center">
          <span class="question-number font-bold text-lg text-primary mr-3">Q1</span>
          <select class="question-type select select-bordered select-sm focus:select-primary">
            <option value="multiple">Multiple Choice</option>
            <option value="truefalse">True/False</option>
            <option value="shortanswer">Short Answer</option>
            <option value="identification">Identification</option>
          </select>
        </div>
        <button type="button" class="remove-question btn btn-circle btn-ghost btn-sm text-error">
          <i class="fas fa-times"></i>
        </button>
      </div>
      
      <input type="text" class="question-text input input-bordered w-full mb-4 focus:input-primary" placeholder="Enter your question" required>
      
      <!-- Options Container (for Multiple Choice and True/False) -->
      <div class="options-container mt-4 space-y-3">
        <!-- Options will be dynamically added based on question type -->
      </div>
      
      <!-- Answer Input (for Short Answer and Identification) -->
      <div class="answer-container mt-4 hidden">
        <div class="form-control">
          <label class="label">
            <span class="label-text font-semibold text-gray-700">Expected Answer</span>
          </label>
          <input type="text" class="answer-input input input-bordered w-full focus:input-primary" 
            placeholder="Enter the expected answer for this question">
        </div>
      </div>
      
      <!-- Add Options (for Multiple Choice only) -->
      <div class="add-options-container mt-4 hidden">
        <div class="flex items-center text-sm">
          <span class="text-gray-600 mr-2">Add</span>
          <button type="button" class="add-regular-option text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
            option
          </button>
          <span class="text-gray-600 mx-2">or</span>
          <button type="button" class="add-other-option text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
            "other"
          </button>
        </div>
      </div>
      
      <!-- Answer Key Section -->
      <div class="answer-key-section mt-6 pt-4 border-t border-gray-200">
        <div class="flex justify-between items-center">
          <div>
            <span class="text-sm text-gray-600">Answer Key:</span>
            <span id="answerKeyStatus" class="text-sm font-medium text-gray-800 ml-2">Not set</span>
            <span id="pointsDisplay" class="text-sm text-gray-600 ml-2"></span>
          </div>
          <button type="button" class="set-answer-key-btn btn btn-sm btn-outline">
            <i class="fas fa-key mr-1"></i>
            Set Answer Key
          </button>
        </div>
        <div id="correctAnswersPreview" class="mt-2 text-sm text-gray-700 hidden">
          <!-- Correct answers will be displayed here -->
        </div>
      </div>
    </div>
  </template>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Module information data
      const moduleData = {
        module1: {
          department: "Computer Science",
          roles: "Students, Junior Developers"
        },
        module2: {
          department: "Computer Science",
          roles: "Students, Software Engineers"
        },
        module3: {
          department: "Information Technology",
          roles: "Students, Database Administrators"
        },
        module4: {
          department: "Web Development",
          roles: "Students, Frontend Developers, Full Stack Developers"
        },
        module5: {
          department: "Software Engineering",
          roles: "Students, Software Engineers, Project Managers"
        }
      };
      
      // Answer key data storage
      const answerKeys = {};
      let currentQuestionId = null;
      
      // Module selection change handler
      const moduleSelect = document.getElementById('moduleSelect');
      const moduleInfo = document.getElementById('moduleInfo');
      const departmentInfo = document.getElementById('departmentInfo');
      const rolesInfo = document.getElementById('rolesInfo');
      
      moduleSelect.addEventListener('change', function() {
        const selectedModule = this.value;
        
        if (selectedModule && moduleData[selectedModule]) {
          departmentInfo.textContent = moduleData[selectedModule].department;
          rolesInfo.textContent = moduleData[selectedModule].roles;
          moduleInfo.classList.remove('hidden');
          // Add animation effect
          moduleInfo.classList.add('animate-pulse');
          setTimeout(() => {
            moduleInfo.classList.remove('animate-pulse');
          }, 1000);
        } else {
          moduleInfo.classList.add('hidden');
        }
      });
      
      // Question management
      const questionsContainer = document.getElementById('questionsContainer');
      const addQuestionBtn = document.getElementById('addQuestion');
      const questionTemplate = document.getElementById('questionTemplate');
      let questionCount = 0;
      
      // Answer Key Modal Elements
      const answerKeyModal = document.getElementById('answerKeyModal');
      const modalQuestionContent = document.getElementById('modalQuestionContent');
      const answerKeyOptions = document.getElementById('answerKeyOptions');
      const questionPoints = document.getElementById('questionPoints');
      const closeAnswerKeyModal = document.getElementById('closeAnswerKeyModal');
      const saveAnswerKey = document.getElementById('saveAnswerKey');
      
      // Add question button handler
      addQuestionBtn.addEventListener('click', function() {
        addQuestion();
      });
      
      // Function to add a new question
      function addQuestion() {
        questionCount++;
        const questionClone = document.importNode(questionTemplate.content, true);
        const questionDiv = questionClone.querySelector('.question-item');
        const questionId = `question_${Date.now()}_${questionCount}`;
        questionDiv.setAttribute('data-question-id', questionId);
        
        // Initialize answer key data for this question
        answerKeys[questionId] = {
          points: 1,
          correctAnswers: [],
          questionType: 'multiple'
        };
        
        // Update question number
        questionDiv.querySelector('.question-number').textContent = `Q${questionCount}`;
        
        // Set unique IDs for inputs
        const questionInput = questionDiv.querySelector('.question-text');
        questionInput.name = `questionText${questionCount}`;
        
        const questionType = questionDiv.querySelector('.question-type');
        questionType.name = `questionType${questionCount}`;
        
        // Add to container with animation
        questionDiv.classList.add('opacity-0', 'transform', '-translate-y-4');
        questionsContainer.appendChild(questionDiv);
        
        // Animate in
        setTimeout(() => {
          questionDiv.classList.remove('opacity-0', '-translate-y-4');
          questionDiv.classList.add('opacity-100', 'translate-y-0');
        }, 10);
        
        // Add event listeners for the new question
        const optionsContainer = questionDiv.querySelector('.options-container');
        const answerContainer = questionDiv.querySelector('.answer-container');
        const addOptionsContainer = questionDiv.querySelector('.add-options-container');
        const addRegularOptionBtn = questionDiv.querySelector('.add-regular-option');
        const addOtherOptionBtn = questionDiv.querySelector('.add-other-option');
        const removeQuestionBtn = questionDiv.querySelector('.remove-question');
        const setAnswerKeyBtn = questionDiv.querySelector('.set-answer-key-btn');
        
        // Initialize question with Multiple Choice type (default)
        initializeMultipleChoice(optionsContainer, addOptionsContainer);
        
        // Question type change handler
        questionType.addEventListener('change', function() {
          updateQuestionType(this, optionsContainer, answerContainer, addOptionsContainer, addRegularOptionBtn, addOtherOptionBtn);
          // Update answer key data
          answerKeys[questionId].questionType = this.value;
          answerKeys[questionId].correctAnswers = [];
          updateAnswerKeyDisplay(questionDiv);
        });
        
        // Add option button handlers
        addRegularOptionBtn.addEventListener('click', function() {
          addOption(optionsContainer, false);
        });
        
        addOtherOptionBtn.addEventListener('click', function() {
          addOption(optionsContainer, true);
        });
        
        // Remove question button handler
        removeQuestionBtn.addEventListener('click', function() {
          // Animate out
          questionDiv.classList.add('opacity-0', 'translate-y-4');
          setTimeout(() => {
            // Remove from answer keys
            delete answerKeys[questionId];
            questionDiv.remove();
            updateQuestionNumbers();
          }, 300);
        });
        
        // Set Answer Key button handler
        setAnswerKeyBtn.addEventListener('click', function() {
          openAnswerKeyModal(questionDiv, questionId);
        });
        
        // Image upload handlers will be added when options are created
      }
      
      // Function to initialize Multiple Choice question with blank options
      function initializeMultipleChoice(optionsContainer, addOptionsContainer) {
        // Clear any existing options
        optionsContainer.innerHTML = '';
        
        // Add two blank options for Multiple Choice
        for (let i = 0; i < 2; i++) {
          const optionDiv = createOptionElement('', false, false);
          optionsContainer.appendChild(optionDiv);
        }
        
        // Show add options container
        addOptionsContainer.classList.remove('hidden');
      }
      
      // Function to open Answer Key Modal
      function openAnswerKeyModal(questionDiv, questionId) {
        currentQuestionId = questionId;
        const questionText = questionDiv.querySelector('.question-text').value;
        const questionType = questionDiv.querySelector('.question-type').value;
        const answerKeyData = answerKeys[questionId];
        
        // Set modal title and points
        modalQuestionContent.innerHTML = `
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="font-semibold text-gray-800 mb-2">Question:</h4>
            <p class="text-gray-700">${questionText || 'No question text entered'}</p>
          </div>
        `;
        
        questionPoints.value = answerKeyData.points;
        
        // Generate answer options based on question type
        let optionsHtml = '';
        
        if (questionType === 'multiple' || questionType === 'truefalse') {
          const options = questionDiv.querySelectorAll('.option-input');
          optionsHtml = `
            <div class="form-control">
              <label class="label">
                <span class="label-text font-semibold">Select Correct Answer(s)</span>
                ${questionType === 'multiple' ? '<span class="label-text-alt text-gray-500">(Multiple answers allowed)</span>' : ''}
              </label>
              <div class="space-y-2">
          `;
          
          options.forEach((option, index) => {
            const optionText = option.value || (questionType === 'truefalse' ? (index === 0 ? 'True' : 'False') : `Option ${index + 1}`);
            const isChecked = answerKeyData.correctAnswers.includes(optionText);
            optionsHtml += `
              <label class="flex items-center cursor-pointer p-2 rounded hover:bg-gray-100">
                <input type="${questionType === 'multiple' ? 'checkbox' : 'radio'}" 
                       name="correctAnswer" 
                       value="${optionText}" 
                       class="mr-3 ${questionType === 'multiple' ? 'checkbox' : 'radio'} checkbox-primary" 
                       ${isChecked ? 'checked' : ''}>
                <span>${optionText}</span>
              </label>
            `;
          });
          
          optionsHtml += `</div></div>`;
        } else if (questionType === 'shortanswer' || questionType === 'identification') {
          const answerInput = questionDiv.querySelector('.answer-input');
          const currentAnswer = answerKeyData.correctAnswers[0] || '';
          
          optionsHtml = `
            <div class="form-control">
              <label class="label">
                <span class="label-text font-semibold">Correct Answer</span>
              </label>
              <input type="text" id="textAnswerInput" class="input input-bordered w-full" 
                     value="${currentAnswer}" placeholder="Enter the correct answer">
            </div>
          `;
        }
        
        answerKeyOptions.innerHTML = optionsHtml;
        
        // Show modal
        answerKeyModal.classList.add('modal-open');
      }
      
      // Function to save answer key
      saveAnswerKey.addEventListener('click', function() {
        if (!currentQuestionId) return;
        
        const questionDiv = document.querySelector(`[data-question-id="${currentQuestionId}"]`);
        const questionType = questionDiv.querySelector('.question-type').value;
        const points = parseInt(questionPoints.value) || 1;
        
        // Update answer key data
        answerKeys[currentQuestionId].points = points;
        answerKeys[currentQuestionId].correctAnswers = [];
        
        if (questionType === 'multiple' || questionType === 'truefalse') {
          const selectedOptions = answerKeyOptions.querySelectorAll('input:checked');
          selectedOptions.forEach(option => {
            answerKeys[currentQuestionId].correctAnswers.push(option.value);
          });
        } else if (questionType === 'shortanswer' || questionType === 'identification') {
          const textAnswer = document.getElementById('textAnswerInput').value;
          if (textAnswer) {
            answerKeys[currentQuestionId].correctAnswers.push(textAnswer);
          }
        }
        
        // Update display
        updateAnswerKeyDisplay(questionDiv);
        
        // Close modal
        answerKeyModal.classList.remove('modal-open');
        showToast('Answer key saved successfully!', 'success');
      });
      
      // Close modal handler
      closeAnswerKeyModal.addEventListener('click', function() {
        answerKeyModal.classList.remove('modal-open');
      });
      
      // Function to update answer key display on question card
      function updateAnswerKeyDisplay(questionDiv) {
        const questionId = questionDiv.getAttribute('data-question-id');
        const answerKeyData = answerKeys[questionId];
        const answerKeyStatus = questionDiv.querySelector('#answerKeyStatus');
        const pointsDisplay = questionDiv.querySelector('#pointsDisplay');
        const correctAnswersPreview = questionDiv.querySelector('#correctAnswersPreview');
        
        if (answerKeyData.correctAnswers.length > 0) {
          answerKeyStatus.textContent = 'Set';
          answerKeyStatus.classList.add('answer-key-indicator');
          pointsDisplay.textContent = `(${answerKeyData.points} point${answerKeyData.points > 1 ? 's' : ''})`;
          
          // Show correct answers preview
          correctAnswersPreview.classList.remove('hidden');
          correctAnswersPreview.innerHTML = `
            <strong>Correct Answer(s):</strong> ${answerKeyData.correctAnswers.join(', ')}
          `;
        } else {
          answerKeyStatus.textContent = 'Not set';
          answerKeyStatus.classList.remove('answer-key-indicator');
          pointsDisplay.textContent = '';
          correctAnswersPreview.classList.add('hidden');
        }
      }
      
      // Function to update question type
      function updateQuestionType(selectElement, optionsContainer, answerContainer, addOptionsContainer, addRegularOptionBtn, addOtherOptionBtn) {
        const questionType = selectElement.value;
        
        if (questionType === 'multiple') {
          optionsContainer.style.display = 'block';
          answerContainer.style.display = 'none';
          addOptionsContainer.classList.remove('hidden');
          
          // Clear existing options and add blank options for Multiple Choice
          optionsContainer.innerHTML = '';
          for (let i = 0; i < 2; i++) {
            const optionDiv = createOptionElement('', false, false);
            optionsContainer.appendChild(optionDiv);
          }
          
        } else if (questionType === 'truefalse') {
          optionsContainer.style.display = 'block';
          answerContainer.style.display = 'none';
          addOptionsContainer.classList.add('hidden');
          
          // Clear existing options and add True/False
          optionsContainer.innerHTML = '';
          
          const trueOption = createOptionElement('True', true);
          const falseOption = createOptionElement('False', true);
          
          optionsContainer.appendChild(trueOption);
          optionsContainer.appendChild(falseOption);
          
          // Hide image upload buttons for True/False options
          const optionItems = optionsContainer.querySelectorAll('.option-item');
          optionItems.forEach(item => {
            const imageBtn = item.querySelector('.btn.btn-circle.btn-ghost.btn-sm.cursor-pointer');
            if (imageBtn) {
              imageBtn.style.display = 'none';
            }
          });
        } else if (questionType === 'shortanswer' || questionType === 'identification') {
          optionsContainer.style.display = 'none';
          answerContainer.style.display = 'block';
          addOptionsContainer.classList.add('hidden');
          
          // Update placeholder based on question type
          const answerInput = answerContainer.querySelector('.answer-input');
          if (questionType === 'identification') {
            answerInput.placeholder = "Enter the correct identification term or phrase";
          } else {
            answerInput.placeholder = "Enter the expected answer for this question";
          }
        }
      }
      
      // Function to create an option element
      function createOptionElement(value, readOnly = false, isOther = false) {
        const optionDiv = document.createElement('div');
        optionDiv.className = 'option-item flex items-center gap-3';
        
        if (isOther) {
          // Special styling for "Other" option - empty field for respondents
          optionDiv.innerHTML = `
            <div class="drag-handle text-gray-400 cursor-move">
              <i class="fas fa-grip-vertical"></i>
            </div>
            <div class="flex items-center flex-grow">
              <span class="other-label">Other:</span>
              <input type="text" 
                     class="other-option-input flex-grow ml-2" 
                     placeholder="______" 
                     readonly 
                     disabled
                     data-other="true">
            </div>
            <div class="flex items-center gap-2">
              <!-- No image upload for Other option since it's for respondents -->
              <button type="button" class="remove-option btn btn-circle btn-ghost btn-sm text-error">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          `;
          
          // Add tooltip to explain this is for respondents
          optionDiv.title = "This field will be empty for respondents to fill in their own answers";
        } else {
          // Regular option
          optionDiv.innerHTML = `
            <div class="drag-handle text-gray-400 cursor-move">
              <i class="fas fa-grip-vertical"></i>
            </div>
            <input type="text" class="option-input input input-bordered flex-grow focus:input-primary" 
              value="${value}" ${readOnly ? 'readonly' : ''} placeholder="Enter option">
            <div class="flex items-center gap-2">
              <label class="btn btn-circle btn-ghost btn-sm cursor-pointer">
                <i class="fas fa-image"></i>
                <input type="file" class="option-image-input hidden" accept="image/*">
              </label>
              <button type="button" class="remove-option btn btn-circle btn-ghost btn-sm text-error">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          `;
        }
        
        // Add event listener to remove button
        const removeBtn = optionDiv.querySelector('.remove-option');
        removeBtn.addEventListener('click', function() {
          const optionsContainer = optionDiv.closest('.options-container');
          if (optionsContainer.children.length > 1) {
            // Animate out
            optionDiv.classList.add('opacity-0', 'translate-x-4');
            setTimeout(() => {
              optionDiv.remove();
            }, 300);
          } else {
            showToast('A question must have at least one option', 'warning');
          }
        });
        
        // Add image upload handler only for regular options
        if (!isOther) {
          const imageInput = optionDiv.querySelector('.option-image-input');
          if (imageInput) {
            imageInput.addEventListener('change', handleImageUpload);
          }
        }
        
        return optionDiv;
      }
      
      // Function to handle image upload for options
      function handleImageUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        // Check if file is an image
        if (!file.type.match('image.*')) {
          showToast('Please select an image file', 'error');
          return;
        }
        
        // Create a preview
        const reader = new FileReader();
        reader.onload = function(e) {
          const optionItem = event.target.closest('.option-item');
          
          // Remove existing preview if any
          const existingPreview = optionItem.querySelector('.option-image-preview');
          if (existingPreview) {
            existingPreview.remove();
          }
          
          // Create and add preview
          const preview = document.createElement('img');
          preview.src = e.target.result;
          preview.className = 'option-image-preview rounded border border-gray-300';
          preview.alt = 'Option image preview';
          
          // Insert after the input field
          const inputContainer = optionItem.querySelector('.flex-grow');
          inputContainer.parentNode.insertBefore(preview, inputContainer.nextSibling);
          
          // Add remove image button
          const removeImageBtn = document.createElement('button');
          removeImageBtn.type = 'button';
          removeImageBtn.className = 'btn btn-circle btn-ghost btn-sm text-error remove-image';
          removeImageBtn.innerHTML = '<i class="fas fa-times"></i>';
          removeImageBtn.addEventListener('click', function() {
            preview.remove();
            removeImageBtn.remove();
            event.target.value = ''; // Clear the file input
          });
          
          // Replace the image upload button with remove button
          const imageBtnContainer = optionItem.querySelector('.btn.btn-circle.btn-ghost.btn-sm.cursor-pointer');
          imageBtnContainer.replaceWith(removeImageBtn);
        };
        
        reader.readAsDataURL(file);
      }
      
      // Function to add an option to a question
      function addOption(optionsContainer, isOther = false) {
        const optionCount = optionsContainer.children.length + 1;
        const value = isOther ? 'Other' : '';
        const optionDiv = createOptionElement(value, false, isOther);
        
        // Animate in
        optionDiv.classList.add('opacity-0', 'translate-x-4');
        optionsContainer.appendChild(optionDiv);
        
        setTimeout(() => {
          optionDiv.classList.remove('opacity-0', 'translate-x-4');
          optionDiv.classList.add('opacity-100', 'translate-x-0');
        }, 10);
      }
      
      // Function to update question numbers after removal
      function updateQuestionNumbers() {
        const questions = questionsContainer.querySelectorAll('.question-item');
        questionCount = questions.length;
        
        questions.forEach((question, index) => {
          const questionNumber = question.querySelector('.question-number');
          questionNumber.textContent = `Q${index + 1}`;
        });
      }
      
      // Form submission handler
      document.getElementById('examForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Basic validation
        const examTitle = document.getElementById('examTitle').value;
        const moduleSelect = document.getElementById('moduleSelect').value;
        const questions = questionsContainer.querySelectorAll('.question-item');
        
        if (!examTitle) {
          showToast('Please enter an examination title', 'error');
          document.getElementById('examTitle').focus();
          return;
        }
        
        if (!moduleSelect) {
          showToast('Please select a module', 'error');
          document.getElementById('moduleSelect').focus();
          return;
        }
        
        if (questions.length === 0) {
          showToast('Please add at least one question', 'error');
          return;
        }
        
        // Check each question has text
        let valid = true;
        questions.forEach(question => {
          const questionText = question.querySelector('.question-text').value;
          if (!questionText) {
            valid = false;
            question.querySelector('.question-text').classList.add('input-error');
          } else {
            question.querySelector('.question-text').classList.remove('input-error');
          }
        });
        
        if (!valid) {
          showToast('Please ensure all questions have text', 'error');
          return;
        }
        
        // Prepare data for submission (including answer keys)
        const formData = {
          examTitle: examTitle,
          examDescription: document.getElementById('examDescription').value,
          module: moduleSelect,
          questions: [],
          answerKeys: answerKeys
        };
        
        // Collect question data
        questions.forEach((question, index) => {
          const questionId = question.getAttribute('data-question-id');
          const questionData = {
            id: questionId,
            number: index + 1,
            type: question.querySelector('.question-type').value,
            text: question.querySelector('.question-text').value,
            options: [],
            answerKey: answerKeys[questionId] || { points: 1, correctAnswers: [] }
          };
          
          // For multiple choice and true/false, collect options
          if (questionData.type === 'multiple' || questionData.type === 'truefalse') {
            const options = question.querySelectorAll('.option-input');
            options.forEach(option => {
              questionData.options.push(option.value);
            });
          }
          
          // For short answer and identification, collect expected answer
          if (questionData.type === 'shortanswer' || questionData.type === 'identification') {
            const answerInput = question.querySelector('.answer-input');
            questionData.expectedAnswer = answerInput.value;
          }
          
          formData.questions.push(questionData);
        });
        
        console.log('Form data to submit:', formData);
        
        // Show success message
        showToast('Examination created successfully with answer keys!', 'success');
        
        // In a real application, you would submit the form data to a server here
        // this.submit();
      });
      
   // Save draft button handler - UPDATED
document.getElementById('saveDraft').addEventListener('click', function() {
    saveAsDraft();
});

// Function to save as draft
function saveAsDraft() {
    // Basic validation
    const examTitle = document.getElementById('examTitle').value;
    const moduleSelect = document.getElementById('moduleSelect').value;
    
    if (!examTitle) {
        showToast('Please enter an examination title', 'error');
        document.getElementById('examTitle').focus();
        return;
    }
    
    if (!moduleSelect) {
        showToast('Please select a module', 'error');
        document.getElementById('moduleSelect').focus();
        return;
    }

    // Prepare draft data
    const draftData = {
        id: 'draft_' + Date.now(),
        examTitle: examTitle,
        examDescription: document.getElementById('examDescription').value,
        module: moduleSelect,
        moduleName: moduleSelect.options[moduleSelect.selectedIndex].text,
        questions: [],
        answerKeys: {...answerKeys},
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
        status: 'draft'
    };

    // Collect question data
    const questions = questionsContainer.querySelectorAll('.question-item');
    questions.forEach((question, index) => {
        const questionId = question.getAttribute('data-question-id');
        const questionType = question.querySelector('.question-type').value;
        const questionData = {
            id: questionId,
            number: index + 1,
            type: questionType,
            text: question.querySelector('.question-text').value,
            options: [],
            answerKey: answerKeys[questionId] || { points: 1, correctAnswers: [] }
        };
        
        // For multiple choice and true/false, collect options
        if (questionData.type === 'multiple' || questionData.type === 'truefalse') {
            const options = question.querySelectorAll('.option-input');
            options.forEach(option => {
                questionData.options.push(option.value);
            });
        }
        
        // For short answer and identification, collect expected answer
        if (questionData.type === 'shortanswer' || questionData.type === 'identification') {
            const answerInput = question.querySelector('.answer-input');
            questionData.expectedAnswer = answerInput ? answerInput.value : '';
        }
        
        draftData.questions.push(questionData);
    });

    // Save to localStorage
    saveDraftToStorage(draftData);
    
    // Show success message and redirect
    Swal.fire({
        icon: 'success',
        title: 'Draft Saved!',
        text: 'Your examination has been saved as draft.',
        showConfirmButton: false,
        timer: 1500
    }).then(() => {
        // Redirect to examination drafts page
        window.location.href = 'examinationdraft.php';
    });
}

// Function to save draft to localStorage
function saveDraftToStorage(draftData) {
    let drafts = JSON.parse(localStorage.getItem('examDrafts')) || [];
    
    // Check if this is an update to existing draft
    const existingIndex = drafts.findIndex(draft => draft.id === draftData.id);
    if (existingIndex !== -1) {
        drafts[existingIndex] = draftData;
    } else {
        drafts.push(draftData);
    }
    
    localStorage.setItem('examDrafts', JSON.stringify(drafts));
    console.log('Draft saved:', draftData);
    return draftData;
}
   // Function to load draft data when page loads
function loadDraftData() {
  const draftId = sessionStorage.getItem('loadDraftId');
  if (!draftId) return false;

  const drafts = JSON.parse(localStorage.getItem('examDrafts')) || [];
  const draft = drafts.find(d => d.id === draftId);
  
  if (draft) {
    console.log('Loading draft:', draft);
    
    // Populate form fields
    document.getElementById('examTitle').value = draft.examTitle || '';
    document.getElementById('examDescription').value = draft.examDescription || '';
    
    // Set module selection
    const moduleSelect = document.getElementById('moduleSelect');
    moduleSelect.value = draft.module || '';
    
    // Trigger module change to show module info
    if (moduleSelect.value) {
      moduleSelect.dispatchEvent(new Event('change'));
    }
    
    // Clear existing questions
    questionsContainer.innerHTML = '';
    questionCount = 0;
    
    // Reset answerKeys
    Object.keys(answerKeys).forEach(key => delete answerKeys[key]);
    
    // Load questions from draft
    draft.questions.forEach(questionData => {
      addQuestionFromDraft(questionData);
    });
    
    // Load answer keys
    if (draft.answerKeys) {
      Object.assign(answerKeys, draft.answerKeys);
    }
    
    // Update answer key displays
    setTimeout(() => {
      questionsContainer.querySelectorAll('.question-item').forEach(questionDiv => {
        updateAnswerKeyDisplay(questionDiv);
      });
    }, 100);
    
    sessionStorage.removeItem('loadDraftId');
    showToast('Draft loaded successfully!', 'success');
    return true;
  }
  return false;
}
      // Function to add question from draft data
      function addQuestionFromDraft(questionData) {
        questionCount++;
        const questionClone = document.importNode(questionTemplate.content, true);
        const questionDiv = questionClone.querySelector('.question-item');
        const questionId = questionData.id || `question_${Date.now()}_${questionCount}`;
        questionDiv.setAttribute('data-question-id', questionId);
        
        // Update question number and type
        questionDiv.querySelector('.question-number').textContent = `Q${questionCount}`;
        const questionTypeSelect = questionDiv.querySelector('.question-type');
        questionTypeSelect.value = questionData.type;
        
        // Set question text
        questionDiv.querySelector('.question-text').value = questionData.text || '';
        
        // Add to container
        questionsContainer.appendChild(questionDiv);
        
        // Get references to containers
        const optionsContainer = questionDiv.querySelector('.options-container');
        const answerContainer = questionDiv.querySelector('.answer-container');
        const addOptionsContainer = questionDiv.querySelector('.add-options-container');
        const addRegularOptionBtn = questionDiv.querySelector('.add-regular-option');
        const addOtherOptionBtn = questionDiv.querySelector('.add-other-option');
        const removeQuestionBtn = questionDiv.querySelector('.remove-question');
        const setAnswerKeyBtn = questionDiv.querySelector('.set-answer-key-btn');
        
        // Initialize based on question type
        if (questionData.type === 'multiple') {
          initializeMultipleChoice(optionsContainer, addOptionsContainer);
          // Populate options
          questionData.options.forEach((optionText, index) => {
            if (index < optionsContainer.children.length) {
              const optionInput = optionsContainer.children[index].querySelector('.option-input');
              if (optionInput) optionInput.value = optionText;
            } else {
              addOption(optionsContainer, false);
              const newOptionInput = optionsContainer.lastChild.querySelector('.option-input');
              if (newOptionInput) newOptionInput.value = optionText;
            }
          });
        } else if (questionData.type === 'truefalse') {
          updateQuestionType(questionTypeSelect, optionsContainer, answerContainer, addOptionsContainer, addRegularOptionBtn, addOtherOptionBtn);
        } else if (questionData.type === 'shortanswer' || questionData.type === 'identification') {
          updateQuestionType(questionTypeSelect, optionsContainer, answerContainer, addOptionsContainer, addRegularOptionBtn, addOtherOptionBtn);
          const answerInput = questionDiv.querySelector('.answer-input');
          if (answerInput) answerInput.value = questionData.expectedAnswer || '';
        }
        
        // Add event listeners
        questionTypeSelect.addEventListener('change', function() {
          updateQuestionType(this, optionsContainer, answerContainer, addOptionsContainer, addRegularOptionBtn, addOtherOptionBtn);
          answerKeys[questionId].questionType = this.value;
          answerKeys[questionId].correctAnswers = [];
          updateAnswerKeyDisplay(questionDiv);
        });
        
        addRegularOptionBtn.addEventListener('click', function() {
          addOption(optionsContainer, false);
        });
        
        addOtherOptionBtn.addEventListener('click', function() {
          addOption(optionsContainer, true);
        });
        
        removeQuestionBtn.addEventListener('click', function() {
          questionDiv.classList.add('opacity-0', 'translate-y-4');
          setTimeout(() => {
            delete answerKeys[questionId];
            questionDiv.remove();
            updateQuestionNumbers();
          }, 300);
        });
        
        setAnswerKeyBtn.addEventListener('click', function() {
          openAnswerKeyModal(questionDiv, questionId);
        });
        
        // Add remove option listeners
        const removeOptionBtns = questionDiv.querySelectorAll('.remove-option');
        removeOptionBtns.forEach(btn => {
          btn.addEventListener('click', function() {
            const optionItem = this.closest('.option-item');
            if (optionsContainer.children.length > 1) {
              optionItem.classList.add('opacity-0', 'translate-x-4');
              setTimeout(() => {
                optionItem.remove();
              }, 300);
            } else {
              showToast('A question must have at least one option', 'warning');
            }
          });
        });
        
        // Initialize answer key data for this question
        if (!answerKeys[questionId]) {
          answerKeys[questionId] = {
            points: 1,
            correctAnswers: [],
            questionType: questionData.type
          };
        }
        
        // Update answer key display
        setTimeout(() => {
          updateAnswerKeyDisplay(questionDiv);
        }, 50);
      }
      
      // Preview button handler
      document.getElementById('previewExam').addEventListener('click', function() {
        showToast('Preview feature coming soon!', 'info');
      });
      
      // Toast notification function
      function showToast(message, type = 'info') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast toast-top toast-end z-50`;
        
        let alertClass = 'alert-info';
        if (type === 'success') alertClass = 'alert-success';
        if (type === 'error') alertClass = 'alert-error';
        if (type === 'warning') alertClass = 'alert-warning';
        
        toast.innerHTML = `
          <div class="alert ${alertClass} shadow-lg text-white">
            <div>
              <span>${message}</span>
            </div>
          </div>
        `;
        
        document.body.appendChild(toast);
        
        // Remove toast after 3 seconds
        setTimeout(() => {
          toast.remove();
        }, 3000);
      }
      
      // Load draft if available when page loads
      setTimeout(() => {
        const draftLoaded = loadDraftData();
        if (draftLoaded) {
          console.log('Draft examination loaded successfully');
        }
      }, 500);
      
      // Add initial question
      addQuestion();
    });
  </script>
</body>
<script src="../sidebar.js"></script>
</html>