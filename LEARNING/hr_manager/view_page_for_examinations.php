<!-- Examinations Review Section -->
<div class="mb-12 hidden" id="examinations-section">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold mb-2 text-gray-800">Examination Review</h1>
            <p class="text-gray-600">Review and approve pending examinations</p>
        </div>
        <div class="flex gap-4 items-center">
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="text-sm text-gray-500">Pending Exams</div>
                <div class="text-2xl font-bold text-gray-800"><?php echo count($pending_examinations); ?></div>
                <div class="text-xs text-gray-400">Awaiting review</div>
            </div>
            <button class="btn-plain px-4 py-2 rounded-lg" onclick="window.location.href='examination_repository.php'">
                <i class="fas fa-arrow-left mr-2"></i>Back to Exams
            </button>
        </div>
    </div>

    <!-- Department Filter for Examinations -->
    <div class="filter-section mb-6">
        <div class="flex flex-col sm:flex-row sm:items-end gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Filter by Department
                </label>
                <div class="flex flex-col sm:flex-row gap-3">
                    <select class="select select-bordered w-full sm:w-64" id="examDepartmentFilter">
                        <option value="all">All Departments</option>
                        <?php foreach ($exam_departments as $dept): 
                            $display_name = ucwords(str_replace('-', ' ', $dept));
                        ?>
                            <option value="<?php echo $dept; ?>" <?php echo $selected_exam_department === $dept ? 'selected' : ''; ?>>
                                <?php echo $display_name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <div class="flex gap-2">
                        <button class="btn-plain px-4 py-2 rounded-lg" onclick="applyExamFilter()">
                            Apply Filter
                        </button>
                        <button class="btn-plain px-4 py-2 rounded-lg" onclick="clearExamFilter()">
                            Clear
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Active Filter Badge -->
            <?php if ($selected_exam_department !== 'all'): ?>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600">Active filter:</span>
                    <span class="bg-gray-100 px-3 py-1 rounded-full text-sm font-medium text-gray-700">
                        <?php echo ucwords(str_replace('-', ' ', $selected_exam_department)); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Examination Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <?php if (empty($pending_examinations)): ?>
            <div class="col-span-full empty-state">
                <i class="fas fa-file-alt"></i>
                <h3 class="text-xl font-semibold mb-2 text-gray-700">
                    <?php if ($selected_exam_department !== 'all'): ?>
                        No Pending Examinations in <?php echo ucwords(str_replace('-', ' ', $selected_exam_department)); ?>
                    <?php else: ?>
                        No Pending Examinations
                    <?php endif; ?>
                </h3>
                <p class="text-gray-500 mb-4">
                    <?php if ($selected_exam_department !== 'all'): ?>
                        There are no examinations awaiting review in this department.
                    <?php else: ?>
                        There are no examinations awaiting review at this time.
                    <?php endif; ?>
                </p>
                <div class="flex gap-2 justify-center">
                    <?php if ($selected_exam_department !== 'all'): ?>
                        <button class="btn-plain px-4 py-2 rounded-lg" onclick="clearExamFilter()">
                            View All Departments
                        </button>
                    <?php endif; ?>
                    <button class="btn-plain px-4 py-2 rounded-lg" onclick="window.location.href='examinations.php'">
                        <i class="fas fa-plus mr-2"></i>Create New Examination
                    </button>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($pending_examinations as $exam): ?>
                <div class="exam-card rounded-lg p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($exam['title']); ?></h3>
                        <span class="status-pending text-xs px-2 py-1 rounded-full">Pending</span>
                    </div>
                    
                    <div class="flex flex-wrap gap-2 my-3">
                        <span class="badge-outline text-xs px-2 py-1 rounded"><?php echo ucfirst(str_replace('-', ' ', $exam['department'])); ?></span>
                        <span class="badge-outline text-xs px-2 py-1 rounded"><?php echo htmlspecialchars($exam['roles']); ?></span>
                    </div>
                    
                    <div class="space-y-2 mb-4">
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">Date Added:</span> 
                            <?php echo date('Y-m-d', strtotime($exam['created_at'])); ?>
                        </p>
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">Questions:</span> 
                            <?php echo $exam['question_count'] ?? 'N/A'; ?>
                        </p>
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">Duration:</span> 
                            <?php echo $exam['duration'] ?? 'N/A'; ?> minutes
                        </p>
                    </div>
                    
                    <div class="mt-4">
                        <!-- ADD THIS BUTTON FOR VIEWING EXAMINATION -->
                        <button class="btn-plain w-full py-2 rounded-lg text-sm" 
                                onclick="viewExam(<?php echo $exam['id']; ?>, '<?php echo htmlspecialchars($exam['title']); ?>', '<?php echo htmlspecialchars($exam['department']); ?>', '<?php echo htmlspecialchars($exam['roles']); ?>', '<?php echo $exam['duration'] ?? 'N/A'; ?>', '<?php echo $exam['question_count'] ?? 'N/A'; ?>')">
                            <i class="fas fa-eye mr-2"></i>View Examination
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>