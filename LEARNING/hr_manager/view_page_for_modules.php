<!-- Learning Modules Review Section -->
<div class="mb-12" id="modules-section">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold mb-2 text-gray-800">Learning Module Review</h1>
            <p class="text-gray-600">Review and approve pending learning modules</p>
        </div>
        <div class="flex gap-4 items-center">
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="text-sm text-gray-500">Pending Modules</div>
                <div class="text-2xl font-bold text-gray-800"><?php echo count($pending_modules); ?></div>
                <div class="text-xs text-gray-400">Awaiting review</div>
            </div>
            <button class="btn-plain px-4 py-2 rounded-lg" onclick="window.location.href='learning_module_repository.php'">
                <i class="fas fa-arrow-left mr-2"></i>Back to Repository
            </button>
        </div>
    </div>

    <!-- Department Filter for Modules -->
    <div class="filter-section mb-6">
        <div class="flex flex-col sm:flex-row sm:items-end gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Filter by Department
                </label>
                <div class="flex flex-col sm:flex-row gap-3">
                    <select class="select select-bordered w-full sm:w-64" id="departmentFilter">
                        <option value="all">All Departments</option>
                        <?php foreach ($departments as $dept): 
                            $display_name = ucwords(str_replace('-', ' ', $dept));
                        ?>
                            <option value="<?php echo $dept; ?>" <?php echo $selected_department === $dept ? 'selected' : ''; ?>>
                                <?php echo $display_name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <div class="flex gap-2">
                        <button class="btn-plain px-4 py-2 rounded-lg" onclick="applyModuleFilter()">
                            Apply Filter
                        </button>
                        <button class="btn-plain px-4 py-2 rounded-lg" onclick="clearModuleFilter()">
                            Clear
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Active Filter Badge -->
            <?php if ($selected_department !== 'all'): ?>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600">Active filter:</span>
                    <span class="bg-gray-100 px-3 py-1 rounded-full text-sm font-medium text-gray-700">
                        <?php echo ucwords(str_replace('-', ' ', $selected_department)); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Module Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <?php if (empty($pending_modules)): ?>
            <div class="col-span-full empty-state">
                <i class="fas fa-file-alt"></i>
                <h3 class="text-xl font-semibold mb-2 text-gray-700">
                    <?php if ($selected_department !== 'all'): ?>
                        No Pending Modules in <?php echo ucwords(str_replace('-', ' ', $selected_department)); ?>
                    <?php else: ?>
                        No Pending Modules
                    <?php endif; ?>
                </h3>
                <p class="text-gray-500 mb-4">
                    <?php if ($selected_department !== 'all'): ?>
                        There are no learning modules awaiting review in this department.
                    <?php else: ?>
                        There are no learning modules awaiting review at this time.
                    <?php endif; ?>
                </p>
                <div class="flex gap-2 justify-center">
                    <?php if ($selected_department !== 'all'): ?>
                        <button class="btn-plain px-4 py-2 rounded-lg" onclick="clearModuleFilter()">
                            View All Departments
                        </button>
                    <?php endif; ?>
                    <button class="btn-plain px-4 py-2 rounded-lg" onclick="window.location.href='learning_module_repository.php'">
                        <i class="fas fa-plus mr-2"></i>Create New Module
                    </button>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($pending_modules as $module): ?>
                <div class="module-card rounded-lg p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($module['title']); ?></h3>
                        <span class="status-pending text-xs px-2 py-1 rounded-full">Pending</span>
                    </div>
                    
                    <div class="flex flex-wrap gap-2 my-3">
                        <span class="badge-outline text-xs px-2 py-1 rounded"><?php echo ucfirst(str_replace('-', ' ', $module['department'])); ?></span>
                        <span class="badge-outline text-xs px-2 py-1 rounded"><?php echo htmlspecialchars($module['roles']); ?></span>
                    </div>
                    
                    <div class="space-y-2 mb-4">
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">Date Added:</span> 
                            <?php echo date('Y-m-d', strtotime($module['created_at'])); ?>
                        </p>
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">Topic:</span> 
                            <?php echo htmlspecialchars($module['topic']); ?>
                        </p>
                    </div>
                    
                    <div class="mt-4">
                      <button class="btn-plain w-full py-2 rounded-lg text-sm" 
        onclick="viewPendingModule(<?php echo $module['id']; ?>)">
    <i class="fas fa-eye mr-2"></i>Review Module
</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>