<?php
require_once __DIR__ . '/../../COMPETENCY/criticalgaps/config.php';
require('../../partials/header.php');

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
?>

<body class="bg-base-200 min-h-screen">
    <div class="flex h-screen">
        <div class="flex-1 overflow-auto">
            <div class="p-4 sm:p-6 w-full">
                <iframe id="tp-iframe" src="trainingprogram.php" class="w-full h-[900px]"></iframe>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const data = <?php echo json_encode($row ?: []); ?>;
            const iframe = document.getElementById('tp-iframe');

            function prefill() {
                try {
                    const doc = iframe.contentWindow.document;
                    const addBtn = doc.getElementById('add-training-btn');
                    const modal = doc.getElementById('training-modal');
                    if (addBtn) addBtn.click();
                    if (modal && typeof modal.showModal === 'function') modal.showModal();
                    const byId = (id) => doc.getElementById(id);
                    const setVal = (id, val) => {
                        const el = byId(id);
                        if (el) el.value = val;
                    };
                    const setSelect = (id, val) => {
                        const el = byId(id);
                        if (!el) return;
                        for (let i = 0; i < el.options.length; i++) {
                            if (String(el.options[i].value).toLowerCase() === String(val || '').toLowerCase()) {
                                el.selectedIndex = i;
                                break;
                            }
                        }
                    };
                    const title = (data.employee_name ? ('IDP Training Request - ' + data.employee_name) : 'IDP Training Request');
                    setVal('training-title', title);
                    setSelect('training-type', data.requested_training_type || '');
                    setSelect('training-mode', data.requested_training_mode || '');
                    setVal('description', data.development_plan || '');
                    // Set audience/category conservatively if present
                    // Dates
                    const start = data.requested_start_datetime ? new Date(data.requested_start_datetime) : null;
                    const end = data.requested_end_datetime ? new Date(data.requested_end_datetime) : null;
                    const fmtDate = (d) => d ? d.toISOString().slice(0, 10) : '';
                    if (start) setVal('start-date', fmtDate(start));
                    if (end) setVal('end-date', fmtDate(end));
                    // Re-init icons
                    if (iframe.contentWindow.lucide) setTimeout(() => iframe.contentWindow.lucide.createIcons(), 50);
                } catch (e) {
                    console.warn('Prefill failed:', e);
                }
            }
            iframe.addEventListener('load', () => setTimeout(prefill, 150));
        })();
    </script>
    <script>
    (function() {
        const iframe = document.getElementById('tp-iframe');
        try {
            document.querySelectorAll('.flex.flex-col.md\\:flex-row.md\\:items-center.md\\:justify-between.gap-3.mb-6, .card.bg-base-100.shadow.mb-6, #sidebar').forEach(el => el && (el.style.display = 'none'));
            if (iframe) iframe.className = 'w-full h-[900px]';
        } catch (e) {}
        function enhance() {
            try {
                const doc = iframe.contentWindow.document;
                // Hide everything except the training modal
                Array.from(doc.body.children).forEach(el => { if (el.id !== 'training-modal') el.style.display = 'none'; });
                const modalBox = doc.querySelector('#training-modal .modal-box');
                if (modalBox) { modalBox.classList.remove('max-w-4xl'); modalBox.classList.add('max-w-6xl'); }
                // Inject bubbles if description exists
                const formEl = doc.getElementById('training-form');
                const descArea = doc.getElementById('description');
                const dev = (<?php echo json_encode((string)($row['development_plan'] ?? '')); ?>).trim();
                if (formEl && dev !== '') {
                    const bubbleWrap = doc.createElement('div');
                    bubbleWrap.className = 'space-y-2';
                    const label = doc.createElement('div');
                    label.className = 'text-sm font-semibold text-gray-700';
                    label.textContent = 'Development Plan';
                    const bubbles = doc.createElement('div');
                    bubbles.className = 'flex flex-wrap gap-2';
                    dev.split(/\r?\n/).map(s => s.trim()).filter(s => s !== '' && s !== '-').forEach(txt => {
                        const span = doc.createElement('span');
                        span.className = 'badge badge-outline';
                        span.textContent = txt.replace(/^[-•]\s*/, '');
                        bubbles.appendChild(span);
                    });
                    bubbleWrap.appendChild(label);
                    bubbleWrap.appendChild(bubbles);
                    const descContainer = descArea ? descArea.closest('.form-control') : null;
                    if (descContainer && descContainer.parentNode) descContainer.parentNode.insertBefore(bubbleWrap, descContainer);
                    else formEl.insertBefore(bubbleWrap, formEl.firstChild);
                }
                // Fit iframe height
                setTimeout(() => {
                    const box = doc.querySelector('#training-modal .modal-box');
                    const h = box ? (box.scrollHeight + 120) : 900;
                    iframe.style.height = Math.max(600, h) + 'px';
                }, 200);
            } catch (e) {}
        }
        iframe.addEventListener('load', () => setTimeout(enhance, 300));
    })();
    </script>
    <script src="../../soliera.js"></script>
    <script src="../../sidebar.js"></script>
</body>
