<?php
require_once __DIR__ . '/../../COMPETENCY/criticalgaps/config.php';
$employeeId = trim((string)($_GET['employee_id'] ?? ''));
$row = null;
if ($employeeId !== '') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM requested_idps_repository WHERE employee_id = ? LIMIT 1");
        $stmt->execute([$employeeId]);
        $row = $stmt->fetch();
    } catch (Throwable $e) {
        $row = null;
    }
}
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
                        <h1 class="text-2xl font-bold">Create Training Program</h1>
                        <div class="text-sm opacity-70">Prefilled from IDP Request</div>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-6">
                    <div class="card bg-base-100 shadow">
                        <div class="card-body">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="label"><span class="label-text">Employee Name</span></label>
                                    <input type="text" class="input input-bordered w-full" value="<?php echo htmlspecialchars((string)($row['employee_name'] ?? '')); ?>" readonly />
                                </div>
                                <div>
                                    <label class="label"><span class="label-text">Employee ID</span></label>
                                    <input type="text" class="input input-bordered w-full" value="<?php echo htmlspecialchars((string)($row['employee_id'] ?? '')); ?>" readonly />
                                </div>
                                <div>
                                    <label class="label"><span class="label-text">Department</span></label>
                                    <input type="text" class="input input-bordered w-full" value="<?php echo htmlspecialchars((string)($row['department'] ?? '')); ?>" readonly />
                                </div>
                                <div>
                                    <label class="label"><span class="label-text">Position</span></label>
                                    <input type="text" class="input input-bordered w-full" value="<?php echo htmlspecialchars((string)($row['position'] ?? '')); ?>" readonly />
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="label"><span class="label-text">Development Plan</span></label>
                                <textarea class="textarea textarea-bordered w-full min-h-32" readonly><?php echo htmlspecialchars((string)($row['development_plan'] ?? '')); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card bg-base-100 shadow">
                        <div class="card-body">
                            <form method="post" action="#">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="md:col-span-3">
                                        <label class="label"><span class="label-text">Training Title</span></label>
                                        <input type="text" name="training_title" class="input input-bordered w-full" value="<?php echo htmlspecialchars('IDP Training - ' . (string)($row['employee_name'] ?? '')); ?>" />
                                    </div>
                                    <div>
                                        <label class="label"><span class="label-text">Training Type</span></label>
                                        <select name="training_type" class="select select-bordered w-full">
                                            <?php
                                            $types = ['Orientation','Training','Seminar','Workshop','Refresher'];
                                            $pref = (string)($row['requested_training_type'] ?? '');
                                            foreach ($types as $t) {
                                                $sel = $pref === $t ? 'selected' : '';
                                                echo '<option value="'.htmlspecialchars($t).'" '.$sel.'>'.htmlspecialchars($t).'</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="label"><span class="label-text">Training Mode</span></label>
                                        <select name="training_mode" class="select select-bordered w-full">
                                            <?php
                                            $modes = ['Onsite','Online','Hybrid'];
                                            $prefM = (string)($row['requested_training_mode'] ?? '');
                                            foreach ($modes as $m) {
                                                $sel = $prefM === $m ? 'selected' : '';
                                                echo '<option value="'.htmlspecialchars($m).'" '.$sel.'>'.htmlspecialchars($m).'</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="label"><span class="label-text">Description</span></label>
                                        <textarea name="description" class="textarea textarea-bordered w-full min-h-24"><?php echo htmlspecialchars((string)($row['development_plan'] ?? 'IDP Training Request')); ?></textarea>
                                    </div>
                                    <div>
                                        <label class="label"><span class="label-text">Start Date/Time</span></label>
                                        <input type="datetime-local" name="start_datetime" class="input input-bordered w-full" value="<?php echo htmlspecialchars($row && !empty($row['requested_start_datetime']) ? date('Y-m-d\TH:i', strtotime($row['requested_start_datetime'])) : ''); ?>" />
                                    </div>
                                    <div>
                                        <label class="label"><span class="label-text">End Date/Time</span></label>
                                        <input type="datetime-local" name="end_datetime" class="input input-bordered w-full" value="<?php echo htmlspecialchars($row && !empty($row['requested_end_datetime']) ? date('Y-m-d\TH:i', strtotime($row['requested_end_datetime'])) : ''); ?>" />
                                    </div>
                                    <div>
                                        <label class="label"><span class="label-text">Target Audience</span></label>
                                        <input type="text" name="target_audience" class="input input-bordered w-full" value="Specific Employee" />
                                    </div>
                                    <div>
                                        <label class="label"><span class="label-text">Category</span></label>
                                        <input type="text" name="category" class="input input-bordered w-full" value="IDP" />
                                    </div>
                                    <div>
                                        <label class="label"><span class="label-text">Participants Needed</span></label>
                                        <input type="number" name="participants_needed" class="input input-bordered w-full" value="1" />
                                    </div>
                                    <div>
                                        <label class="label"><span class="label-text">Status</span></label>
                                        <select name="status" class="select select-bordered w-full">
                                            <?php
                                            $statuses = ['Under Review','Approved','On Hold','Cancelled'];
                                            foreach ($statuses as $s) {
                                                $sel = $s === 'Under Review' ? 'selected' : '';
                                                echo '<option value="'.htmlspecialchars($s).'" '.$sel.'>'.htmlspecialchars($s).'</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="label"><span class="label-text">Need Budget</span></label>
                                        <select name="need_budget" class="select select-bordered w-full">
                                            <option value="0" selected>No</option>
                                            <option value="1">Yes</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="label"><span class="label-text">Need Items</span></label>
                                        <select name="need_items" class="select select-bordered w-full">
                                            <option value="0" selected>No</option>
                                            <option value="1">Yes</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="label"><span class="label-text">Need Facility</span></label>
                                        <select name="need_facility" class="select select-bordered w-full">
                                            <option value="0" selected>No</option>
                                            <option value="1">Yes</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="md:col-span-1">
                                        <h2 class="text-lg font-semibold">Location</h2>
                                        <div class="mt-3 space-y-3">
                                            <div>
                                                <label class="label"><span class="label-text">Venue</span></label>
                                                <input type="text" name="venue_name" class="input input-bordered w-full" />
                                            </div>
                                            <div>
                                                <label class="label"><span class="label-text">Address</span></label>
                                                <input type="text" name="venue_address" class="input input-bordered w-full" />
                                            </div>
                                            <div>
                                                <label class="label"><span class="label-text">Room</span></label>
                                                <input type="text" name="venue_room" class="input input-bordered w-full" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="md:col-span-1">
                                        <h2 class="text-lg font-semibold">Equipment</h2>
                                        <div class="mt-3">
                                            <div id="equipment-list" class="space-y-2"></div>
                                            <div class="flex gap-2 mt-2">
                                                <input type="text" id="equip-name" class="input input-bordered flex-1" placeholder="Item" />
                                                <input type="number" id="equip-qty" class="input input-bordered w-24" placeholder="Qty" min="1" />
                                                <button type="button" id="equip-add" class="btn btn-outline">Add</button>
                                            </div>
                                            <input type="hidden" name="equipment_json" id="equipment-json" />
                                        </div>
                                    </div>
                                    <div class="md:col-span-1">
                                        <h2 class="text-lg font-semibold">Budget Requests</h2>
                                        <div class="mt-3">
                                            <div id="budget-list" class="space-y-2"></div>
                                            <div class="flex gap-2 mt-2">
                                                <input type="text" id="budget-item" class="input input-bordered flex-1" placeholder="Item" />
                                                <input type="number" id="budget-amount" class="input input-bordered w-32" placeholder="Amount" min="0" step="0.01" />
                                                <button type="button" id="budget-add" class="btn btn-outline">Add</button>
                                            </div>
                                            <div class="mt-3 text-right">
                                                <span class="text-sm opacity-70">Total:</span>
                                                <span id="budget-total" class="font-semibold">0.00</span>
                                            </div>
                                            <input type="hidden" name="budget_json" id="budget-json" />
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-6 flex gap-2">
                                    <button type="submit" class="btn btn-primary">Save</button>
                                    <a href="trainingrequest.php" class="btn btn-outline">Back</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        if (window.lucide) lucide.createIcons();
        (function() {
            var equipList = document.getElementById('equipment-list');
            var equipAdd = document.getElementById('equip-add');
            var equipName = document.getElementById('equip-name');
            var equipQty = document.getElementById('equip-qty');
            var equipJson = document.getElementById('equipment-json');
            var budgetList = document.getElementById('budget-list');
            var budgetAdd = document.getElementById('budget-add');
            var budgetItem = document.getElementById('budget-item');
            var budgetAmount = document.getElementById('budget-amount');
            var budgetJson = document.getElementById('budget-json');
            var budgetTotal = document.getElementById('budget-total');
            var eq = [];
            var bd = [];
            function redrawEquip() {
                equipList.innerHTML = eq.map(function(x, i) {
                    return '<div class="flex items-center gap-2"><span class="flex-1">' + x.name.replace(/[<>&"]/g, '') + '</span><span class="w-16 text-right">' + x.qty + '</span><button type="button" class="btn btn-xs" data-i="'+i+'">Remove</button></div>';
                }).join('');
                equipJson.value = JSON.stringify(eq);
                Array.prototype.forEach.call(equipList.querySelectorAll('button[data-i]'), function(b) {
                    b.addEventListener('click', function() {
                        var i = parseInt(b.getAttribute('data-i'));
                        if (!isNaN(i)) { eq.splice(i,1); redrawEquip(); }
                    });
                });
            }
            function redrawBudget() {
                budgetList.innerHTML = bd.map(function(x, i) {
                    return '<div class="flex items-center gap-2"><span class="flex-1">' + x.item.replace(/[<>&"]/g, '') + '</span><span class="w-24 text-right">' + x.amount.toFixed(2) + '</span><button type="button" class="btn btn-xs" data-i="'+i+'">Remove</button></div>';
                }).join('');
                var total = bd.reduce(function(s, x){ return s + x.amount; }, 0);
                budgetTotal.textContent = total.toFixed(2);
                budgetJson.value = JSON.stringify(bd);
                Array.prototype.forEach.call(budgetList.querySelectorAll('button[data-i]'), function(b) {
                    b.addEventListener('click', function() {
                        var i = parseInt(b.getAttribute('data-i'));
                        if (!isNaN(i)) { bd.splice(i,1); redrawBudget(); }
                    });
                });
            }
            equipAdd.addEventListener('click', function() {
                var n = String(equipName.value || '').trim();
                var q = parseInt(String(equipQty.value || '1'), 10);
                if (n !== '' && q > 0) {
                    eq.push({ name: n, qty: q });
                    equipName.value = '';
                    equipQty.value = '';
                    redrawEquip();
                }
            });
            budgetAdd.addEventListener('click', function() {
                var it = String(budgetItem.value || '').trim();
                var am = parseFloat(String(budgetAmount.value || '0'));
                if (it !== '' && !isNaN(am) && am >= 0) {
                    bd.push({ item: it, amount: am });
                    budgetItem.value = '';
                    budgetAmount.value = '';
                    redrawBudget();
                }
            });
        })();
    </script>
    <script src="../../soliera.js"></script>
    <script src="../../sidebar.js"></script>
</body>
</html>
