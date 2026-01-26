<?php
session_start();
require_once __DIR__ . '/db.php';

$programs = [];
try {
    $sql = "SELECT * FROM training_programs WHERE status IN ('Under Review','Pending') ORDER BY FIELD(status,'Under Review','Pending'), created_at DESC";
    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) {
        $programs[] = $row;
    }
} catch (Throwable $e) {
    $programs = [];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Training Programs</title>
     <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function () {
            if (!window.Swal || window.__SWAL_DAISY_PATCHED__) return;
            window.__SWAL_DAISY_PATCHED__ = true;
            const orig = window.Swal.fire.bind(window.Swal);
            window.Swal.fire = function (opts) {
                const inOpts = opts || {};
                const inCustom = (inOpts && inOpts.customClass) ? inOpts.customClass : {};
                const customClass = {
                    popup: 'bg-base-100 text-base-content rounded-box',
                    title: 'text-base-content',
                    htmlContainer: 'text-base-content',
                    actions: 'flex gap-2',
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-ghost',
                    denyButton: 'btn btn-ghost',
                    ...(inCustom || {})
                };
                return orig({
                    returnFocus: false,
                    buttonsStyling: false,
                    ...inOpts,
                    customClass
                });
            };
        })();
    </script>
    <style>
        .swal2-container { z-index: 2147483647 !important; }
        .card-table thead { display: none; }
        .card-table tbody {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 0.75rem;
        }
        @media (min-width: 768px) {
            .card-table tbody { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1280px) {
            .card-table tbody { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        .card-table tbody tr {
            display: block;
            background: #fff;
            border: 1px solid #eef2f7;
            border-radius: 0.75rem;
            padding: 1rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .card-table tbody tr.card-empty {
            grid-column: 1 / -1;
            text-align: center;
            color: #6b7280;
            padding: 2.25rem 1rem;
        }
        .card-table tbody tr.card-empty td { display: block; }
        .card-table td {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.35rem 0;
            border: 0;
            background: transparent;
            white-space: normal;
        }
        .card-table td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #374151;
            flex: 0 0 auto;
        }
        .card-table td[data-label="Actions"] {
            display: block;
            padding-top: 0.75rem;
            margin-top: 0.5rem;
            border-top: 1px solid #eef2f7;
        }
        .card-table td[data-label="Actions"]::before { display: none; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
     <div class="flex h-screen">
    <!-- Sidebar -->
    <?php 
    // Use relative path or absolute path based on your directory structure
    include '../../USM/sidebarr.php'; 
    ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../../USM/navbar.php'; ?>
    
    <main class="max-w-6xl mx-auto px-4 py-6">
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Review</h1>
                    <p class="text-gray-600">Training programs waiting for approval.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="review_log.php" class="btn btn-outline">Action Logs</a>
                    <a href="trainingprogram.php" class="btn btn-ghost">Back</a>
                </div>
            </div>

            <div>
                <table class="table card-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Schedule</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($programs)) : ?>
                            <tr class="card-empty"><td colspan="6">No programs to review.</td></tr>
                        <?php else : ?>
                            <?php foreach ($programs as $p) : ?>
                                <tr data-program-row="<?= (int)$p['id'] ?>">
                                    <td data-label="ID">#<?= (int)$p['id'] ?></td>
                                    <td data-label="Title"><?= htmlspecialchars((string)($p['training_title'] ?? '')) ?></td>
                                    <td data-label="Type"><?= htmlspecialchars((string)($p['training_type'] ?? '')) ?></td>
                                    <td data-label="Schedule"><?= htmlspecialchars((string)($p['start_datetime'] ?? '')) ?> to <?= htmlspecialchars((string)($p['end_datetime'] ?? '')) ?></td>
                                    <td data-label="Status"><span class="badge badge-outline"><?= htmlspecialchars((string)($p['status'] ?? '')) ?></span></td>
                                    <td data-label="Actions">
                                        <div class="flex flex-wrap gap-2">
                                            <a class="btn btn-sm btn-outline" href="trainingprogram.php#" data-action="view" data-program-id="<?= (int)$p['id'] ?>">View</a>
                                            <?php if (in_array((string)($p['status'] ?? ''), ['Under Review', 'Pending'], true)) : ?>
                                                <button type="button" class="btn btn-sm btn-ghost" data-action="cancel" data-program-id="<?= (int)$p['id'] ?>">Cancel</button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-success" data-action="approve" data-program-id="<?= (int)$p['id'] ?>">Approve</button>
                                            <button type="button" class="btn btn-sm btn-error" data-action="reject" data-program-id="<?= (int)$p['id'] ?>">Reject</button>
                                            <button type="button" class="btn btn-sm btn-warning" data-action="compliance" data-program-id="<?= (int)$p['id'] ?>">For Compliance</button>
                                            <a class="btn btn-sm btn-ghost" href="review_log.php?program_id=<?= (int)$p['id'] ?>">History</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        (function () {
            const postStatusUpdate = async (programId, newStatus, reason) => {
                try {
                    console.log('Updating status:', { programId, newStatus, reason });
                    
                    const fd = new FormData();
                    fd.append('action', 'update_program_status');
                    fd.append('program_id', String(programId));
                    fd.append('status', String(newStatus));
                    if (reason !== null && reason !== undefined) fd.append('reason', String(reason));

                    const res = await fetch('trainingprogram.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                    
                    if (!res.ok) {
                        console.error('HTTP error:', res.status, res.statusText);
                        throw new Error(`HTTP ${res.status}: ${res.statusText}`);
                    }
                    
                    const data = await res.json();
                    console.log('Response data:', data);
                    return data;
                } catch (error) {
                    console.error('Error in postStatusUpdate:', error);
                    throw error;
                }
            };

            const removeRow = (programId) => {
                const row = document.querySelector(`[data-program-row="${programId}"]`);
                if (row) row.remove();
            };

            document.addEventListener('click', async (e) => {
                const btn = e.target.closest('[data-action]');
                if (!btn) return;

                const action = btn.getAttribute('data-action');
                const programId = btn.getAttribute('data-program-id');
                if (!programId) return;

                if (action === 'view') {
                    window.location.href = 'trainingprogram.php?open_program_id=' + encodeURIComponent(programId);
                    return;
                }

                if (action === 'approve') {
                    const confirmRes = window.Swal ? await Swal.fire({
                        icon: 'question',
                        title: 'Approve?',
                        text: 'Approve this training program?',
                        showCancelButton: true,
                        confirmButtonText: 'Approve',
                        cancelButtonText: 'Cancel'
                    }) : { isConfirmed: window.confirm('Approve this training program?') };
                    if (!confirmRes.isConfirmed) return;

                    try {
                        const data = await postStatusUpdate(programId, 'Approved', '');
                        if (!data || !data.success) {
                            if (window.Swal) Swal.fire({ icon: 'error', title: 'Failed', text: (data && data.message) ? data.message : 'Unable to update status.' });
                            return;
                        }
                        removeRow(programId);
                        if (window.Swal) Swal.fire({ icon: 'success', title: 'Approved', timer: 1200, showConfirmButton: false });
                    } catch (error) {
                        console.error('Approve action error:', error);
                        if (window.Swal) Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred while approving. Check console for details.' });
                    }
                    return;
                }

                if (action === 'cancel') {
                    const confirmRes = window.Swal ? await Swal.fire({
                        icon: 'warning',
                        title: 'Cancel training?',
                        text: 'This will delete the training program.',
                        showCancelButton: true,
                        confirmButtonText: 'Cancel Training',
                        cancelButtonText: 'Close'
                    }) : { isConfirmed: window.confirm('Cancel this training?') };
                    if (!confirmRes.isConfirmed) return;

                    const fd = new FormData();
                    fd.append('action', 'delete_program');
                    fd.append('program_id', String(programId));
                    const res = await fetch('trainingprogram.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                    const data = await res.json();
                    if (!data || !data.success) {
                        if (window.Swal) Swal.fire({ icon: 'error', title: 'Failed', text: (data && data.message) ? data.message : 'Unable to cancel training.' });
                        return;
                    }
                    removeRow(programId);
                    if (window.Swal) Swal.fire({ icon: 'success', title: 'Cancelled', timer: 1200, showConfirmButton: false });
                    return;
                }

                if (action === 'reject' || action === 'compliance') {
                    const title = action === 'reject' ? 'Reject' : 'For Compliance';
                    const newStatus = action === 'reject' ? 'Rejected' : 'For Compliance';

                    let reason = '';
                    if (window.Swal) {
                        const r = await Swal.fire({
                            icon: 'warning',
                            title: title,
                            input: 'textarea',
                            inputLabel: 'Reason',
                            inputPlaceholder: 'Type the reason...',
                            inputAttributes: { 'aria-label': 'Reason' },
                            showCancelButton: true,
                            confirmButtonText: 'Submit',
                            cancelButtonText: 'Cancel',
                            inputValidator: (value) => {
                                if (!value || !String(value).trim()) return 'Reason is required.';
                            }
                        });
                        if (!r.isConfirmed) return;
                        reason = String(r.value || '').trim();
                    } else {
                        reason = window.prompt('Reason:') || '';
                        reason = reason.trim();
                        if (!reason) return;
                    }

                    try {
                        const data = await postStatusUpdate(programId, newStatus, reason);
                        if (!data || !data.success) {
                            if (window.Swal) Swal.fire({ icon: 'error', title: 'Failed', text: (data && data.message) ? data.message : 'Unable to update status.' });
                            return;
                        }
                        removeRow(programId);
                        if (window.Swal) Swal.fire({ icon: 'success', title: 'Updated', timer: 1200, showConfirmButton: false });
                    } catch (error) {
                        console.error('Reject/Compliance action error:', error);
                        if (window.Swal) Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred while updating status. Check console for details.' });
                    }
                    return;
                }
            });
        })();
    </script>
        <script src="main.js"></script>
    <script src="maintwo.js"></script>
    <script src="../../soliera.js"></script>
  <script src="../../sidebar.js"></script>
</body>
</html>
