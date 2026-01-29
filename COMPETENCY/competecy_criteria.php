<?php
session_start();
function dd($vars)
{
    echo '<pre>'; // Format output nicely
    var_dump($vars); // Dump the variable
    echo '</pre>';
    die(); // Stop execution
}
if (!isset($_SESSION['employee_id'])) {
    header('Location: /hr2/index.php');
    exit;
}
$base_url = rtrim((getenv('APP_BASE_PATH') ?: '/hr2/'), '/');
$apiUrl = $base_url . '/COMPETENCY/api/competency_criteria.php';
// dd('test');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competency Criteria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="../soliera.css">
    <link rel="stylesheet" href="../sidebar.css">
    <style>
        .truncate-text {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .tooltip-content {
            max-width: 300px;
            word-wrap: break-word;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="flex h-screen">
        <?php include '../USM/sidebarr.php'; ?>

        <div class="flex flex-col flex-1 overflow-auto">
            <?php include '../USM/navbar.php'; ?>

            <main class="flex-1 overflow-auto">
                <div class="container mx-auto px-4 py-8">

                    <div class="flex justify-between items-start mb-8">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">Competency Criteria</h1>
                            <p class="text-gray-600 mb-4">Define core competencies used across KPI evaluation and development planning</p>
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <i data-lucide="info" class="w-4 h-4"></i>
                                <span>Competencies defined here are used for KPI mapping, skill gap analysis, development plans, and succession readiness.</span>
                            </div>
                        </div>
                        <button id="addCompetencyBtn" class="btn btn-primary gap-2">
                            <i data-lucide="plus" class="w-5 h-5"></i>
                            Add Competency
                        </button>
                    </div>

                    <div class="card bg-base-100 shadow-lg">
                        <div class="card-body p-0">
                            <div class="overflow-x-auto">
                                <table class="table table-zebra">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="font-semibold">Name</th>
                                            <th class="font-semibold">Description</th>
                                            <th class="font-semibold text-right">Required Level (%)</th>
                                            <th class="font-semibold text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="competencyTableBody"></tbody>
                                </table>
                            </div>
                            <div id="emptyState" class="hidden p-8 text-center">
                                <i data-lucide="folder-open" class="w-16 h-16 mx-auto text-gray-300 mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No competency criteria yet</h3>
                                <p class="text-gray-600 mb-4">Get started by adding your first competency</p>
                                <button id="addFirstCompetencyBtn" class="btn btn-primary gap-2">
                                    <i data-lucide="plus" class="w-5 h-5"></i>
                                    Add Competency
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <dialog id="competencyModal" class="modal">
        <div class="modal-box w-11/12 max-w-2xl">
            <form id="competencyForm" method="dialog">
                <div class="flex justify-between items-center mb-6">
                    <h3 id="modalTitle" class="text-xl font-bold">Add Competency</h3>
                    <button type="button" class="btn btn-sm btn-circle btn-ghost" onclick="competencyModal.close()">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <input type="hidden" id="competencyId" name="id">

                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text font-medium">Name <span class="text-red-500">*</span></span>
                        <span class="label-text-alt"><span id="nameCounter">0</span>/100</span>
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="input input-bordered w-full"
                        placeholder="e.g., Leadership, Technical Expertise, Communication"
                        maxlength="100"
                        required>
                    <div class="label">
                        <span class="label-text-alt text-red-500 hidden" id="nameError">This competency name already exists</span>
                    </div>
                </div>

                <div class="form-control mb-6">
                    <label class="label">
                        <span class="label-text font-medium">Description <span class="text-red-500">*</span></span>
                        <span class="label-text-alt"><span id="descCounter">0</span>/500</span>
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        class="textarea textarea-bordered h-32"
                        placeholder="Describe the competency, its importance, and how it's measured..."
                        maxlength="500"
                        required></textarea>
                </div>

                <div class="form-control mb-6">
                    <label class="label">
                        <span class="label-text font-medium">Required Level (%) <span class="text-red-500">*</span></span>
                    </label>
                    <input
                        type="number"
                        id="requiredLevel"
                        name="required_level"
                        class="input input-bordered w-full"
                        placeholder="e.g., 80"
                        min="0"
                        max="100"
                        step="0.1"
                        required>
                </div>

                <div class="modal-action">
                    <button type="button" class="btn btn-ghost" onclick="competencyModal.close()">Cancel</button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">Save Competency</button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog id="deleteModal" class="modal">
        <div class="modal-box">
            <div class="flex items-center gap-3 mb-6">
                <div class="bg-red-100 p-2 rounded-full">
                    <i data-lucide="trash-2" class="w-6 h-6 text-red-600"></i>
                </div>
                <div>
                    <h3 class="font-bold text-lg">Delete Competency</h3>
                    <p class="text-gray-600">Are you sure you want to delete this competency?</p>
                </div>
            </div>

            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 hidden" id="deleteWarning">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <p id="deleteWarningText" class="text-sm text-yellow-700"></p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg mb-6">
                <h4 class="font-medium mb-2" id="deleteCompetencyName"></h4>
                <p class="text-sm text-gray-600" id="deleteCompetencyDesc"></p>
            </div>

            <div class="modal-action">
                <button type="button" class="btn btn-ghost" onclick="deleteModal.close()">Cancel</button>
                <button type="button" id="confirmDeleteBtn" class="btn btn-error">Delete</button>
            </div>
        </div>
    </dialog>

    <script>
        lucide.createIcons();

        const API_URL = <?php echo json_encode($apiUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

        const competencyModal = document.getElementById('competencyModal');
        const deleteModal = document.getElementById('deleteModal');
        const addCompetencyBtn = document.getElementById('addCompetencyBtn');
        const addFirstCompetencyBtn = document.getElementById('addFirstCompetencyBtn');
        const competencyForm = document.getElementById('competencyForm');
        const competencyTableBody = document.getElementById('competencyTableBody');
        const emptyState = document.getElementById('emptyState');
        const modalTitle = document.getElementById('modalTitle');
        const nameField = document.getElementById('name');
        const descField = document.getElementById('description');
        const requiredLevelField = document.getElementById('requiredLevel');
        const nameCounter = document.getElementById('nameCounter');
        const descCounter = document.getElementById('descCounter');
        const nameError = document.getElementById('nameError');
        const deleteWarning = document.getElementById('deleteWarning');
        const deleteWarningText = document.getElementById('deleteWarningText');
        const deleteCompetencyName = document.getElementById('deleteCompetencyName');
        const deleteCompetencyDesc = document.getElementById('deleteCompetencyDesc');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

        let competencies = [];
        let currentDeleteId = null;

        nameField.addEventListener('input', (e) => {
            nameCounter.textContent = e.target.value.length;
            nameError.classList.add('hidden');
        });

        descField.addEventListener('input', (e) => {
            descCounter.textContent = e.target.value.length;
        });

        addCompetencyBtn.addEventListener('click', () => {
            openModal();
        });

        addFirstCompetencyBtn.addEventListener('click', () => {
            openModal();
        });

        function openModal(competency = null) {
            competencyForm.reset();
            nameCounter.textContent = '0';
            descCounter.textContent = '0';
            nameError.classList.add('hidden');

            if (competency) {
                modalTitle.textContent = 'Edit Competency';
                document.getElementById('competencyId').value = competency.id;
                nameField.value = competency.name;
                descField.value = competency.description;
                requiredLevelField.value = (competency.required_level ?? '');
                nameCounter.textContent = competency.name.length;
                descCounter.textContent = competency.description.length;
            } else {
                modalTitle.textContent = 'Add Competency';
                document.getElementById('competencyId').value = '';
            }

            competencyModal.showModal();
        }

        async function apiRequest(action, payload = null) {
            const url = API_URL + '?action=' + encodeURIComponent(action);
            const res = await fetch(url, {
                method: payload ? 'POST' : 'GET',
                headers: payload ? {
                    'Content-Type': 'application/json'
                } : undefined,
                body: payload ? JSON.stringify(payload) : undefined,
                credentials: 'same-origin'
            });

            let data = null;
            try {
                data = await res.json();
            } catch (e) {
                data = {
                    success: false,
                    message: 'Invalid server response'
                };
            }

            if (!res.ok || !data || data.success === false) {
                const msg = (data && data.message) ? data.message : 'Request failed';
                const err = new Error(msg);
                err.status = res.status;
                throw err;
            }

            return data;
        }

        competencyForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const idRaw = document.getElementById('competencyId').value;
            const id = idRaw ? parseInt(idRaw, 10) : null;
            const name = nameField.value.trim();
            const description = descField.value.trim();
            const requiredLevel = requiredLevelField.value === '' ? null : parseFloat(requiredLevelField.value);

            if (!name || !description || requiredLevel === null || Number.isNaN(requiredLevel)) return;

            try {
                if (id) {
                    await apiRequest('update', {
                        id,
                        name,
                        description,
                        required_level: requiredLevel
                    });
                } else {
                    await apiRequest('create', {
                        name,
                        description,
                        required_level: requiredLevel
                    });
                }

                competencyModal.close();
                await loadCompetencies();
            } catch (err) {
                if (err.status === 409) {
                    nameError.classList.remove('hidden');
                    return;
                }
                alert(err.message || 'Failed to save competency');
            }
        });

        function openDeleteModal(competency) {
            deleteCompetencyName.textContent = competency.name;
            deleteCompetencyDesc.textContent = competency.description;
            currentDeleteId = competency.id;

            deleteWarning.classList.add('hidden');
            confirmDeleteBtn.disabled = false;
            confirmDeleteBtn.classList.remove('btn-disabled');

            deleteModal.showModal();
        }

        confirmDeleteBtn.addEventListener('click', async () => {
            if (!currentDeleteId) return;
            try {
                await apiRequest('delete', {
                    id: currentDeleteId
                });
                deleteModal.close();
                currentDeleteId = null;
                await loadCompetencies();
            } catch (err) {
                alert(err.message || 'Failed to delete competency');
            }
        });

        function truncateText(text, maxLength) {
            if (!text) return '';
            if (text.length <= maxLength) return text;
            return text.substring(0, maxLength) + '...';
        }

        function renderTable() {
            if (!competencies || competencies.length === 0) {
                competencyTableBody.innerHTML = '';
                emptyState.classList.remove('hidden');
                lucide.createIcons();
                return;
            }

            emptyState.classList.add('hidden');

            competencyTableBody.innerHTML = competencies.map(comp => {
                const safeTip = String(comp.description || '').replace(/\"/g, '&quot;');
                return `
                    <tr>
                        <td>
                            <div class="font-medium">${comp.name}</div>
                        </td>
                        <td>
                            <div class="tooltip tooltip-top cursor-help" data-tip="${safeTip}">
                                <div class="truncate-text max-w-md">${truncateText(comp.description, 100)}</div>
                            </div>
                        </td>
                        <td class="text-right font-medium">${(parseFloat(comp.required_level ?? 0) || 0).toFixed(1)}%</td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <button data-action="edit" data-id="${comp.id}" class="btn btn-sm btn-ghost btn-square">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                </button>
                                <button data-action="delete" data-id="${comp.id}" class="btn btn-sm btn-ghost btn-square text-red-600">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            competencyTableBody.querySelectorAll('button[data-action="edit"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = parseInt(btn.getAttribute('data-id'), 10);
                    const comp = competencies.find(c => c.id === id);
                    if (comp) openModal(comp);
                });
            });

            competencyTableBody.querySelectorAll('button[data-action="delete"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = parseInt(btn.getAttribute('data-id'), 10);
                    const comp = competencies.find(c => c.id === id);
                    if (comp) openDeleteModal(comp);
                });
            });

            lucide.createIcons();
        }

        async function loadCompetencies() {
            try {
                const res = await apiRequest('list');
                competencies = Array.isArray(res.data) ? res.data : [];
            } catch (err) {
                competencies = [];
                alert(err.message || 'Failed to load competencies');
            }
            renderTable();
        }

        loadCompetencies();
    </script>

    <?php require('../partials/footer.php') ?>