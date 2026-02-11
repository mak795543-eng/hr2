(() => {
  const qs = (sel) => document.querySelector(sel);

  const enhanceTextarea = (ta) => {
    if (!ta || ta.nodeType !== 1) return;
    if (String(ta.tagName || '').toUpperCase() !== 'TEXTAREA') return;
    if (ta.dataset && ta.dataset.autogrowApplied === '1') return;

    try {
      if (ta.dataset) ta.dataset.autogrowApplied = '1';
      ta.style.resize = 'vertical';
      ta.style.overflowY = 'hidden';
    } catch (_) {
    }

    const getMinHeight = () => {
      let minH = 0;
      try {
        minH = ta.dataset ? parseFloat(ta.dataset.autogrowMinHeight || '0') : 0;
      } catch (_) {
        minH = 0;
      }
      if (minH && !isNaN(minH) && minH > 0) return minH;

      try {
        const cs = window.getComputedStyle ? window.getComputedStyle(ta) : null;
        if (cs) {
          const mh = cs.minHeight ? parseFloat(cs.minHeight) : 0;
          if (mh && !isNaN(mh) && mh > 0) minH = mh;
          if (!minH) {
            const h = cs.height ? parseFloat(cs.height) : 0;
            if (h && !isNaN(h) && h > 0) minH = h;
          }
        }
      } catch (_) {
      }

      try {
        if (ta.dataset) ta.dataset.autogrowMinHeight = String(minH || 0);
        if (minH && !isNaN(minH) && minH > 0) ta.style.minHeight = String(minH) + 'px';
      } catch (_) {
      }

      return minH && !isNaN(minH) ? minH : 0;
    };

    const autoGrow = () => {
      try {
        const minH = getMinHeight();
        ta.style.height = 'auto';
        const next = Math.max(ta.scrollHeight, minH || 0);
        ta.style.height = String(next) + 'px';
      } catch (_) {
      }
    };

    ta.addEventListener('input', autoGrow);
    ta.addEventListener('change', autoGrow);
    autoGrow();
  };

  const enhanceAllTextareas = (root) => {
    const base = root && root.querySelectorAll ? root : document;
    try {
      const list = Array.from(base.querySelectorAll('textarea'));
      list.forEach((ta) => enhanceTextarea(ta));
    } catch (_) {
    }
  };

  const observeTextareas = () => {
    if (!document.body || !window.MutationObserver) return;

    const obs = new MutationObserver((mutations) => {
      for (const m of mutations) {
        const nodes = Array.from(m.addedNodes || []);
        nodes.forEach((n) => {
          if (!n || n.nodeType !== 1) return;
          if (String(n.tagName || '').toUpperCase() === 'TEXTAREA') {
            enhanceTextarea(n);
          } else {
            enhanceAllTextareas(n);
          }
        });
      }
    });

    try {
      obs.observe(document.body, { childList: true, subtree: true });
    } catch (_) {
    }
  };

  const getOpenDialogTarget = () => {
    const openDialogs = Array.from(document.querySelectorAll('dialog[open]'));
    return openDialogs.length ? openDialogs[openDialogs.length - 1] : undefined;
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      enhanceAllTextareas(document);
      observeTextareas();
    });
  } else {
    enhanceAllTextareas(document);
    observeTextareas();
  }

  const swalFire = async (opts, targetDialog) => {
    if (!window.Swal) return null;
    const target = targetDialog || getOpenDialogTarget();
    const inOpts = opts || {};
    const inCustomClass = (inOpts && inOpts.customClass) ? inOpts.customClass : {};
    const customClass = {
      popup: 'bg-base-100 text-base-content rounded-box',
      title: 'text-base-content',
      htmlContainer: 'text-base-content',
      actions: 'flex gap-2',
      confirmButton: 'btn btn-primary',
      cancelButton: 'btn btn-ghost',
      denyButton: 'btn btn-ghost',
      ...(inCustomClass || {})
    };
    return window.Swal.fire({
      returnFocus: false,
      buttonsStyling: false,
      ...(target ? { target } : {}),
      ...inOpts,
      customClass
    });
  };

  const isAddTrainingPage = (() => {
    const b = document.body;
    return !!(b && b.getAttribute('data-page') === 'add-training');
  })();

  const editProgramId = (() => {
    if (!isAddTrainingPage) return null;
    try {
      const u = new URL(window.location.href);
      const v = u.searchParams.get('edit_program_id');
      return v && String(v).trim() !== '' ? String(v).trim() : null;
    } catch (_) {
      return null;
    }
  })();

  const idpRequestId = (() => {
    if (!isAddTrainingPage) return null;
    try {
      const u = new URL(window.location.href);
      const v = u.searchParams.get('idp_id');
      return v && String(v).trim() !== '' ? String(v).trim() : null;
    } catch (_) {
      return null;
    }
  })();

  const DRAFTS_STORAGE_KEY = 'training_program_drafts_v1';
  const OWNER_KEY = (() => {
    const b = document.body;
    return b ? String(b.getAttribute('data-owner-key') || '') : '';
  })();
  let activeDraftId = null;

  const draftApiPost = async (action, body) => {
    const fd = new FormData();
    fd.append('action', action);
    Object.keys(body || {}).forEach((k) => fd.append(k, body[k]));
    const res = await fetch('drafts.php', { method: 'POST', body: fd, credentials: 'same-origin' });
    return await res.json();
  };

  const draftApiGet = async (params) => {
    const url = new URL('drafts.php', window.location.href);
    Object.keys(params || {}).forEach((k) => url.searchParams.set(k, params[k]));
    const res = await fetch(url.toString(), { credentials: 'same-origin' });
    return await res.json();
  };

  const loadDrafts = () => {
    try {
      const raw = window.localStorage ? window.localStorage.getItem(DRAFTS_STORAGE_KEY) : null;
      const parsed = raw ? JSON.parse(raw) : [];
      return Array.isArray(parsed) ? parsed : [];
    } catch (_) {
      return [];
    }
  };

  const persistDrafts = (drafts) => {
    try {
      if (!window.localStorage) return;
      window.localStorage.setItem(DRAFTS_STORAGE_KEY, JSON.stringify(Array.isArray(drafts) ? drafts : []));
    } catch (_) {
    }
  };

  const migrateLocalDraftsOnce = async () => {
    if (!isAddTrainingPage) return;
    if (!window.localStorage) return;
    const localDrafts = loadDrafts();
    if (!localDrafts.length) return;

    const guardKey = `drafts_migrated_to_db_${encodeURIComponent(String(OWNER_KEY || ''))}`;
    try {
      const already = window.localStorage.getItem(guardKey);
      if (already === '1') return;
    } catch (_) {
      return;
    }

    try {
      const payload = localDrafts.map((d) => ({
        id: String(d && d.id || ''),
        title: String(d && d.title || 'Untitled Training'),
        data: d && d.data ? d.data : null
      })).filter((d) => d.id && d.data);

      if (!payload.length) {
        window.localStorage.setItem(guardKey, '1');
        return;
      }

      const r = await draftApiPost('migrate', { drafts_json: JSON.stringify(payload) });
      if (r && r.success) {
        persistDrafts([]);
        window.localStorage.setItem(guardKey, '1');
      }
    } catch (_) {
    }
  };

  const saveDraftToDb = async (draft) => {
    const id = String(draft && draft.id || '');
    const title = String(draft && draft.title || '');
    const data = draft && draft.data ? draft.data : null;
    if (!id || !title || !data) return { success: false };
    return await draftApiPost('upsert', { id, title, data_json: JSON.stringify(data) });
  };

  const deleteDraftFromDb = async (id) => {
    const did = String(id || '');
    if (!did) return { success: false };
    return await draftApiPost('delete', { id: did });
  };

  const getDraftFromDb = async (id) => {
    const did = String(id || '');
    if (!did) return null;
    const r = await draftApiGet({ action: 'get', id: did });
    if (r && r.success && r.draft) return r.draft;
    return null;
  };

  const deleteDraftById = (draftId) => {
    const drafts = loadDrafts().filter((d) => String(d && d.id) !== String(draftId));
    persistDrafts(drafts);
  };

  const upsertDraft = (draft) => {
    const drafts = loadDrafts();
    const idx = drafts.findIndex((d) => String(d && d.id) === String(draft && draft.id));
    if (idx >= 0) drafts[idx] = draft;
    else drafts.unshift(draft);
    persistDrafts(drafts);
  };

  let trainings = [];
  let viewingTrainingId = null;
  let currentFilter = { type: 'all', status: 'all' };
  let pendingOpenProgramId = null;

  const addTrainingBtn = qs('#add-training-btn');
  const trainingModal = qs('#training-modal');
  const viewTrainingModal = qs('#view-training-modal');
  const cancelBtn = qs('#cancel-btn');
  const saveTrainingBtn = qs('#save-training-btn');
  const trainingForm = qs('#training-form');
  const closeViewModal = qs('#close-view-modal');
  const resubmitTrainingBtn = qs('#resubmit-training-btn');
  const postTrainingBtn = qs('#post-training-btn');

  const targetAudienceSelect = qs('#target-audience');
  const requestedBySelect = qs('#requested-by');
  const departmentContainer = qs('#department-container');
  const subDepartmentContainer = qs('#sub-department-container');
  const roleContainer = qs('#role-container');
  const employeeContainer = qs('#employee-container');
  const trainingEmployeeInput = qs('#training-employee');
  const trainingMentorSelect = qs('#training-mentor');
  const subDepartmentSelect = qs('#training-sub-department');
  const trainingCategoryByDept = qs('#training-category-by-department');
  const trainingCategoryContainer = qs('#training-category-container');

  const modalTitle = qs('#modal-title');
  const modalSubtitle = qs('#modal-subtitle');
  const idpListContainer = qs('#idp-list-container');
  const idpListTable = qs('#idp-list-table');
  const idpListLoading = qs('#idp-list-loading');

  const needBudgetSelect = qs('#need-budget');
  const needItemsSelect = qs('#need-items');
  const needFacilitySelect = qs('#need-facility');

  const budgetRequestModal = qs('#budget-request-modal');
  const logisticsRequestModal = qs('#logistics-request-modal');
  const facilityRequestModal = qs('#facility-request-modal');

  const budgetCancelBtn = qs('#budget-cancel-btn');
  const budgetCancelActionBtn = qs('#budget-cancel-action-btn');
  const budgetSaveBtn = qs('#budget-save-btn');
  const logisticsCancelBtn = qs('#logistics-cancel-btn');
  const logisticsCancelActionBtn = qs('#logistics-cancel-action-btn');
  const logisticsSaveBtn = qs('#logistics-save-btn');
  const facilityCancelBtn = qs('#facility-cancel-btn');
  const facilityCancelActionBtn = qs('#facility-cancel-action-btn');
  const facilitySaveBtn = qs('#facility-save-btn');

  const objectivesOpenBtn = qs('#objectives-open-btn');
  const objectivesSummary = qs('#objectives-summary');
  const objectivesModal = qs('#objectives-modal');
  const objectivesCloseBtn = qs('#objectives-close-btn');
  const objectivesCancelBtn = qs('#objectives-cancel-btn');
  const objectivesApplyBtn = qs('#objectives-apply-btn');

  const trainingCards = qs('#training-cards');

  const budgetItemsContainer = qs('#budget-items-container');
  const budgetAddItemBtn = qs('#budget-add-item-btn');
  const budgetTotalCostEl = qs('#budget-total-cost');

  const logisticsItemsContainer = qs('#logistics-items-container');
  const logisticsAddItemBtn = qs('#logistics-add-item-btn');

  const requestDraft = {
    budget: {
      completed: false,
      basic: {
        title: '',
        purpose: '',
        department: '',
        event_date: '',
        justification: '',
        remarks: ''
      },
      items: [],
      total_cost: 0
    },
    logistics: {
      completed: false,
      basic: {
        title: '',
        purpose: '',
        department: '',
        event_date: '',
        needed_by_date: '',
      },
      items: [],
      delivery: { location: '', contact_person: '' },
      remarks: ''
    },
    facility: {
      completed: false,
      basic: {
        title: '',
        purpose: '',
        department: '',
        event_date: ''
      },
      location: {
        preferred_location: '',
        start_time: '',
        end_time: ''
      },
      special_requirements: '',
      remarks: ''
    }
  };

  const budgetSummary = qs('#budget-summary');
  const logisticsSummary = qs('#logistics-summary');
  const facilitySummary = qs('#facility-summary');

  const esc = (v) => String(v ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

  const deptName = (id) => {
    const n = String(id || '');
    if (n === '1') return 'Front Office / Reception';
    if (n === '2') return 'Housekeeping';
    if (n === '3') return 'Food & Beverage (F&B)';
    if (n === '4') return 'Kitchen / Culinary';
    if (n === '5') return 'Sales & Marketing';
    if (n === '6') return 'Human Resources (HR)';
    if (n === '7') return 'Finance / Accounting';
    if (n === '8') return 'Engineering / Maintenance';
    if (n === '9') return 'Security';
    return n;
  };

  const roleOptionsByDepartment = {
    '1': [
      'Receptionist / Front Desk Officer',
      'Guest Service Agent / Concierge',
      'Reservation Agent',
      'Bellhop / Porter'
    ],
    '2': [
      'Executive Housekeeper / Housekeeping Manager',
      'Floor Supervisor',
      'Room Attendant / Housekeeper',
      'Laundry Attendant',
      'Public Area Attendant'
    ],
    '3': [
      'F&B Manager / Director',
      'Restaurant Manager / Captain',
      'Waiter / Waitress / Server'
    ],
    '4': [
      'Executive Chef / Head Chef',
      'Sous Chef (assistant to head chef)',
      'Line Cook / Station Chef',
      'Pastry Chef / Baker',
      'Kitchen Steward / Dishwasher'
    ],
    '5': [
      'Sales & Marketing Manager',
      'Revenue Manager',
      'Event / Banquet Sales Coordinator',
      'Social Media / Marketing Executive'
    ],
    '6': [
      'HR Manager / Director',
      'Recruitment Officer',
      'Training & Development Specialist',
      'Payroll / HR Assistant'
    ],
    '7': [
      'Finance Manager / Controller',
      'Accountant',
      'Payroll Officer',
      'Cost Controller'
    ],
    '8': [
      'Chief Engineer / Engineering Manager',
      'Maintenance Technician',
      'Electrician / Plumber',
      'HVAC Technician'
    ],
    '9': [
      'Security Manager / Supervisor',
      'Security Guard',
      'CCTV / Surveillance Officer'
    ]
  };

  const getRoleOptionsForDepartment = (deptId) => {
    const key = String(deptId || '');
    const list = roleOptionsByDepartment[key];
    return Array.isArray(list) ? list.slice() : [];
  };

  const fillRolesByDepartment = (preserveValue) => {
    const roleSelect = qs('#training-role');
    if (!roleSelect) return;

    const dept = (qs('#training-department') || {}).value || '';
    const prev = preserveValue ? (roleSelect.value || '') : '';

    roleSelect.innerHTML = '<option value="" selected>Select a role</option>';
    const list = getRoleOptionsForDepartment(dept);
    list.forEach((v) => {
      const opt = document.createElement('option');
      opt.value = v;
      opt.textContent = v;
      if (prev && prev === v) opt.selected = true;
      roleSelect.appendChild(opt);
    });
  };

  const fillSubDepartments = (preserveValue) => {
    if (!subDepartmentContainer || !subDepartmentSelect) return;
    subDepartmentContainer.classList.add('hidden');
    subDepartmentSelect.required = false;
    subDepartmentSelect.value = '';
  };

  const syncTrainingCategoryByDepartmentOptions = () => {
    if (!trainingCategoryByDept) return;
    const catEl = qs('#training-category');

    Array.from(trainingCategoryByDept.querySelectorAll('option')).forEach((opt) => {
      if (!opt) return;
      opt.hidden = false;
      opt.disabled = false;
    });

    if (catEl) {
      const selected = String(catEl.value || '');
      const selOpt = Array.from(trainingCategoryByDept.querySelectorAll('option')).find((o) => String(o.value || '') === selected);
      if (selOpt && (selOpt.hidden || selOpt.disabled)) {
        catEl.value = '';
      }
    }
  };

  const syncMentorOptionsForTargetAudience = () => {
    if (!trainingMentorSelect) return;
    const dept = (qs('#training-department') || {}).value || '';

    let deptHeadId = null;
    let deptMgrId = null;
    try {
      const mapH = (window && window.DEPARTMENT_HEADS) ? window.DEPARTMENT_HEADS : null;
      if (mapH && dept && Object.prototype.hasOwnProperty.call(mapH, String(dept))) {
        deptHeadId = String(mapH[String(dept)] || '');
      }
    } catch (_) {
      deptHeadId = null;
    }
    try {
      const mapM = (window && window.DEPARTMENT_MANAGERS) ? window.DEPARTMENT_MANAGERS : null;
      if (mapM && dept && Object.prototype.hasOwnProperty.call(mapM, String(dept))) {
        deptMgrId = String(mapM[String(dept)] || '');
      }
    } catch (_) {
      deptMgrId = null;
    }

    const allowed = new Set(
      [deptHeadId, deptMgrId]
        .filter((v) => typeof v === 'string' && v.trim() !== '')
        .map(String)
    );

    const options = Array.from(trainingMentorSelect.querySelectorAll('option'));
    options.forEach((opt, idx) => {
      if (!opt) return;
      if (idx === 0) {
        opt.hidden = false;
        opt.disabled = false;
        return;
      }
      if (!dept) {
        opt.hidden = true;
        opt.disabled = true;
        return;
      }
      const ok = allowed.has(String(opt.value || ''));
      opt.hidden = !ok;
      opt.disabled = !ok;
    });

    if (!dept) {
      trainingMentorSelect.value = '';
      return;
    }

    const current = String(trainingMentorSelect.value || '');
    if (current && allowed.has(current)) return;
    const next = deptHeadId || deptMgrId || '';
    trainingMentorSelect.value = next ? String(next) : '';
  };

  const renderKeyValueGrid = (pairs) => {
    const safe = pairs.filter((p) => p && p.label);
    if (!safe.length) return '';
    return `
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        ${safe.map((p) => `
          <div class="bg-base-200 rounded-lg p-3">
            <div class="text-xs text-gray-500">${esc(p.label)}</div>
            <div class="text-sm font-medium text-gray-900 break-words">${esc(p.value || '-')}</div>
          </div>
        `).join('')}
      </div>
    `;
  };

  const renderBudgetSummary = () => {
    if (!budgetSummary) return;
    if (!requestDraft.budget.completed) {
      budgetSummary.classList.add('hidden');
      budgetSummary.innerHTML = '';
      return;
    }

    const b = requestDraft.budget;
    const items = Array.isArray(b.items) ? b.items : [];
    const itemsHtml = items.length ? `
      <div class="space-y-2">
        ${items.map((it, idx) => `
          <div class="bg-white border border-gray-200 rounded-lg p-3">
            <div class="flex items-start justify-between gap-2">
              <div class="font-semibold text-gray-900">Item ${idx + 1}</div>
              <div class="text-sm font-semibold text-blue-600">₱${Number((it.quantity || 0) * (it.unit_cost || 0)).toFixed(2)}</div>
            </div>
            <div class="mt-2 text-sm text-gray-700">${esc(it.category || '')} - ${esc(it.description || '')}</div>
            <div class="mt-1 text-xs text-gray-500">Qty: ${esc(it.quantity ?? '')} | Unit Cost: ₱${esc(it.unit_cost ?? '')}</div>
            ${it.remarks ? `<div class="mt-2 text-xs text-gray-500">Remarks: ${esc(it.remarks)}</div>` : ''}
          </div>
        `).join('')}
      </div>
    ` : '<div class="text-sm text-gray-500">No budget items.</div>';

    budgetSummary.classList.remove('hidden');
    budgetSummary.innerHTML = `
      <div class="card bg-white border border-gray-200 shadow-sm">
        <div class="card-body p-5">
          <div class="flex items-center justify-between gap-2">
            <h3 class="card-title text-base">Budget Request</h3>
            <span class="badge badge-success badge-outline">Saved</span>
          </div>
          ${renderKeyValueGrid([
            { label: 'Title', value: b.basic.title },
            { label: 'Purpose', value: b.basic.purpose },
            { label: 'Department', value: deptName(b.basic.department) },
            { label: 'Event Date', value: b.basic.event_date },
          ])}
          <div class="mt-4">
            <div class="text-sm font-semibold text-gray-700 mb-2">Budget Items</div>
            ${itemsHtml}
          </div>
          <div class="mt-4 bg-blue-50 rounded-lg p-4 flex items-center justify-between">
            <div>
              <div class="text-sm font-semibold text-gray-700">Total Estimated Cost</div>
              <div class="text-xs text-gray-500">Sum of all budget items</div>
            </div>
            <div class="text-lg font-bold text-blue-600">₱${Number(b.total_cost || 0).toFixed(2)}</div>
          </div>
          <div class="mt-4">
            <div class="text-sm font-semibold text-gray-700">Justification</div>
            <div class="mt-1 text-sm text-gray-700 whitespace-pre-line">${esc(b.basic.justification || '-')}</div>
          </div>
          <div class="mt-3">
            <div class="text-sm font-semibold text-gray-700">Remarks</div>
            <div class="mt-1 text-sm text-gray-700 whitespace-pre-line">${esc(b.basic.remarks || '-')}</div>
          </div>
        </div>
      </div>
    `;
  };

  const renderLogisticsSummary = () => {
    if (!logisticsSummary) return;
    if (!requestDraft.logistics.completed) {
      logisticsSummary.classList.add('hidden');
      logisticsSummary.innerHTML = '';
      return;
    }

    const l = requestDraft.logistics;
    const items = Array.isArray(l.items) ? l.items : [];
    const itemsHtml = items.length ? `
      <div class="space-y-2">
        ${items.map((it, idx) => `
          <div class="bg-white border border-gray-200 rounded-lg p-3">
            <div class="font-semibold text-gray-900">Item ${idx + 1}</div>
            <div class="mt-2 text-sm text-gray-700">${esc(it.category || '')} - ${esc(it.name || '')}</div>
            <div class="mt-1 text-xs text-gray-500">Qty: ${esc(it.quantity ?? '')} | Unit: ${esc(it.unit ?? '')}</div>
            ${it.remarks ? `<div class="mt-2 text-xs text-gray-500">Remarks: ${esc(it.remarks)}</div>` : ''}
          </div>
        `).join('')}
      </div>
    ` : '<div class="text-sm text-gray-500">No requested items.</div>';

    logisticsSummary.classList.remove('hidden');
    logisticsSummary.innerHTML = `
      <div class="card bg-white border border-gray-200 shadow-sm">
        <div class="card-body p-5">
          <div class="flex items-center justify-between gap-2">
            <h3 class="card-title text-base">Equipment / Logistics Request</h3>
            <span class="badge badge-success badge-outline">Saved</span>
          </div>
          ${renderKeyValueGrid([
            { label: 'Title', value: l.basic.title },
            { label: 'Purpose', value: l.basic.purpose },
            { label: 'Department', value: deptName(l.basic.department) },
            { label: 'Event Date', value: l.basic.event_date },
            { label: 'Needed By', value: l.basic.needed_by_date },
          ])}
          <div class="mt-4">
            <div class="text-sm font-semibold text-gray-700 mb-2">Requested Items</div>
            ${itemsHtml}
          </div>
          <div class="mt-4">
            <div class="text-sm font-semibold text-gray-700 mb-2">Delivery Information</div>
            ${renderKeyValueGrid([
              { label: 'Delivery Location', value: l.delivery.location },
              { label: 'Contact Person', value: l.delivery.contact_person },
            ])}
          </div>
          <div class="mt-3">
            <div class="text-sm font-semibold text-gray-700">Remarks</div>
            <div class="mt-1 text-sm text-gray-700 whitespace-pre-line">${esc(l.remarks || '-')}</div>
          </div>
        </div>
      </div>
    `;
  };

  const renderFacilitySummary = () => {
    if (!facilitySummary) return;
    if (!requestDraft.facility.completed) {
      facilitySummary.classList.add('hidden');
      facilitySummary.innerHTML = '';
      return;
    }

    const f = requestDraft.facility;
    facilitySummary.classList.remove('hidden');
    facilitySummary.innerHTML = `
      <div class="card bg-white border border-gray-200 shadow-sm">
        <div class="card-body p-5">
          <div class="flex items-center justify-between gap-2">
            <h3 class="card-title text-base">Location / Facility Request</h3>
            <span class="badge badge-success badge-outline">Saved</span>
          </div>
          ${renderKeyValueGrid([
            { label: 'Title', value: f.basic.title },
            { label: 'Purpose', value: f.basic.purpose },
            { label: 'Department', value: deptName(f.basic.department) },
            { label: 'Event Date', value: f.basic.event_date },
            { label: 'Preferred Location', value: f.location.preferred_location },
            { label: 'Start Time', value: f.location.start_time },
            { label: 'End Time', value: f.location.end_time },
          ])}
          <div class="mt-3">
            <div class="text-sm font-semibold text-gray-700">Special Requirements</div>
            <div class="mt-1 text-sm text-gray-700 whitespace-pre-line">${esc(f.special_requirements || '-')}</div>
          </div>
          <div class="mt-3">
            <div class="text-sm font-semibold text-gray-700">Remarks</div>
            <div class="mt-1 text-sm text-gray-700 whitespace-pre-line">${esc(f.remarks || '-')}</div>
          </div>
        </div>
      </div>
    `;
  };

  const updateRequestSummaries = () => {
    renderBudgetSummary();
    renderLogisticsSummary();
    renderFacilitySummary();
  };

  const formatDateTime = (dt) => {
    if (!dt) return '';
    const d = new Date(String(dt).replace(' ', 'T'));
    if (isNaN(d.getTime())) return String(dt);
    return d.toLocaleString();
  };

  const getDurationText = (startDt, endDt) => {
    const s = new Date(String(startDt || '').replace(' ', 'T'));
    const e = new Date(String(endDt || '').replace(' ', 'T'));
    const ms = e - s;
    if (isNaN(ms) || ms <= 0) return '';
    const mins = Math.floor(ms / 60000);
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    return `${h}h ${m}m`;
  };

  const statusClass = (status) => {
    const s = String(status || '').toLowerCase();
    if (s === 'under review') return 'status-review';
    if (s === 'pending') return 'status-pending';
    if (s === 'approved') return 'status-approved';
    if (s === 'rejected') return 'status-rejected';
    if (s === 'for compliance') return 'status-compliance';
    if (s === 'on hold') return 'status-onhold';
    if (s === 'posted') return 'status-posted';
    if (s === 'planned') return 'status-planned';
    if (s === 'scheduled') return 'status-scheduled';
    if (s === 'ongoing') return 'status-ongoing';
    if (s === 'completed') return 'status-completed';
    if (s === 'cancelled') return 'status-cancelled';
    return 'status-planned';
  };

  const setDefaultDates = () => {
    const now = new Date();
    const tomorrow = new Date(now);
    tomorrow.setDate(tomorrow.getDate() + 1);
    const formatDate = (date) => date.toISOString().split('T')[0];

    const startDateEl = qs('#start-date');
    const endDateEl = qs('#end-date');
    const startTimeEl = qs('#start-time');
    const endTimeEl = qs('#end-time');

    if (startDateEl) startDateEl.value = formatDate(tomorrow);
    if (endDateEl) endDateEl.value = formatDate(tomorrow);
    if (startTimeEl) startTimeEl.value = '09:00';
    if (endTimeEl) endTimeEl.value = '17:00';
  };

  const getUrlParam = (key) => {
    try {
      const u = new URL(window.location.href);
      return u.searchParams.get(key);
    } catch (_) {
      return null;
    }
  };

  const getCurrentDraftPayload = () => {
    const startDate = (qs('#start-date') || {}).value || '';
    const startTime = (qs('#start-time') || {}).value || '';
    const endDate = (qs('#end-date') || {}).value || '';
    const endTime = (qs('#end-time') || {}).value || '';

    const objectives = Array.from(document.querySelectorAll('.js-training-objective'))
      .filter((el) => el && el.checked)
      .map((el) => String(el.value || ''))
      .filter((v) => v.trim() !== '');

    const form = {
      training_title: (qs('#training-title') || {}).value || '',
      training_type: (qs('#training-type') || {}).value || '',
      training_mode: (qs('#training-mode') || {}).value || '',
      requested_by: (qs('#requested-by') || {}).value || '',
      category: (qs('#training-category') || {}).value || '',
      description: (qs('#description') || {}).value || '',
      target_audience: (qs('#target-audience') || {}).value || '',
      department_id: (qs('#training-department') || {}).value || '',
      sub_department: (qs('#training-sub-department') || {}).value || '',
      target_role: (qs('#training-role') || {}).value || '',
      employee_id: (qs('#training-employee') || {}).value || '',
      mentor_id: (qs('#training-mentor') || {}).value || '',
      participants_needed: (qs('#participants-needed') || {}).value || '1',
      max_participants: (qs('#max-participants') || {}).value || '',
      training_level: (qs('#competency-level') || {}).value || '',
      objectives,
      objectives_other: (qs('#training-objectives-other') || {}).value || '',
      start_date: startDate,
      start_time: startTime,
      end_date: endDate,
      end_time: endTime,
      need_budget: (qs('#need-budget') || {}).value || '0',
      need_items: (qs('#need-items') || {}).value || '0',
      need_facility: (qs('#need-facility') || {}).value || '0'
    };

    const req = JSON.parse(JSON.stringify(requestDraft));
    return { form, requestDraft: req };
  };

  const applyDraftToUI = (draftData) => {
    if (!draftData || !draftData.form) return;

    const set = (id, value) => {
      const el = document.getElementById(id);
      if (el) el.value = value;
    };

    set('training-title', draftData.form.training_title || '');
    set('training-type', draftData.form.training_type || '');
    set('training-mode', draftData.form.training_mode || 'Onsite');
    set('requested-by', draftData.form.requested_by || '');
    set('training-category', draftData.form.category || '');
    set('description', draftData.form.description || '');
    set('target-audience', draftData.form.target_audience || '');

    try {
      const bubbleWrap = document.getElementById('idp-development-plans');
      const bubbleEmpty = document.getElementById('idp-development-plans-empty');
      if (bubbleWrap) bubbleWrap.innerHTML = '';
      const items = Array.isArray(draftData.form.development_plan_items) ? draftData.form.development_plan_items : [];
      if (bubbleWrap && items.length) {
        items.forEach((t) => {
          const s = String(t || '').trim();
          if (!s) return;
          const el = document.createElement('span');
          el.className = 'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold border border-gray-200 bg-gray-100 text-gray-800';
          el.textContent = s;
          bubbleWrap.appendChild(el);
        });
        if (bubbleEmpty) bubbleEmpty.classList.add('hidden');
      } else {
        if (bubbleEmpty) bubbleEmpty.classList.remove('hidden');
      }
    } catch (_) {
    }

    set('training-department', draftData.form.department_id || '');
    fillSubDepartments(false);
    set('training-sub-department', draftData.form.sub_department || '');
    fillRolesByDepartment(false);
    set('training-role', draftData.form.target_role || '');
    set('training-employee', draftData.form.employee_id || '');
    set('training-mentor', draftData.form.mentor_id || '');
    set('participants-needed', draftData.form.participants_needed || '1');
    set('max-participants', draftData.form.max_participants || '');
    set('competency-level', draftData.form.training_level || '');

    try {
      const vals = Array.isArray(draftData.form.objectives) ? draftData.form.objectives.map(String) : [];
      Array.from(document.querySelectorAll('.js-training-objective')).forEach((el) => {
        if (!el) return;
        el.checked = vals.includes(String(el.value || ''));
      });
    } catch (_) {
    }
    set('training-objectives-other', draftData.form.objectives_other || '');

    set('start-date', draftData.form.start_date || '');
    set('start-time', draftData.form.start_time || '');
    set('end-date', draftData.form.end_date || '');
    set('end-time', draftData.form.end_time || '');

    set('need-budget', String(draftData.form.need_budget ?? '0'));
    set('need-items', String(draftData.form.need_items ?? '0'));
    set('need-facility', String(draftData.form.need_facility ?? '0'));

    if (needBudgetSelect) lastNeedBudgetValue = String(needBudgetSelect.value || '0');
    if (needItemsSelect) lastNeedItemsValue = String(needItemsSelect.value || '0');
    if (needFacilitySelect) lastNeedFacilityValue = String(needFacilitySelect.value || '0');

    const rd = draftData.requestDraft || {};

    if (rd.budget) {
      requestDraft.budget.completed = !!rd.budget.completed;
      requestDraft.budget.basic = { ...requestDraft.budget.basic, ...(rd.budget.basic || {}) };
      requestDraft.budget.items = Array.isArray(rd.budget.items) ? rd.budget.items : [];
      requestDraft.budget.total_cost = Number(rd.budget.total_cost || 0);
    }

    if (rd.logistics) {
      requestDraft.logistics.completed = !!rd.logistics.completed;
      requestDraft.logistics.basic = { ...requestDraft.logistics.basic, ...(rd.logistics.basic || {}) };
      requestDraft.logistics.items = Array.isArray(rd.logistics.items) ? rd.logistics.items : [];
      requestDraft.logistics.delivery = { ...requestDraft.logistics.delivery, ...(rd.logistics.delivery || {}) };
      requestDraft.logistics.remarks = String(rd.logistics.remarks || '');
    }

    if (rd.facility) {
      requestDraft.facility.completed = !!rd.facility.completed;
      requestDraft.facility.basic = { ...requestDraft.facility.basic, ...(rd.facility.basic || {}) };
      requestDraft.facility.location = { ...requestDraft.facility.location, ...(rd.facility.location || {}) };
      requestDraft.facility.special_requirements = String(rd.facility.special_requirements || '');
      requestDraft.facility.remarks = String(rd.facility.remarks || '');
    }

    if (typeof window.handleTargetAudienceChange === 'function') {
      window.handleTargetAudienceChange();
    }
    fillSubDepartments(true);
    fillRolesByDepartment(true);
    syncTrainingCategoryByDepartmentOptions();
    syncMentorOptionsForTargetAudience();
    syncObjectiveChoicesByDepartment();
    updateObjectivesSummary();
    updateRequestSummaries();
  };

  const handleAddTrainingBack = async (backHref) => {
    if (!window.Swal) {
      const ok = window.confirm('Save draft before leaving?');
      if (!ok) {
        window.location.href = backHref;
        return;
      }
    }

    const res = await swalFire({
      icon: 'question',
      title: 'Save draft?',
      text: 'Do you want to save this as a draft so you can continue later?',
      showCancelButton: true,
      confirmButtonText: 'Save Draft',
      cancelButtonText: 'Back'
    });

    if (!res || !res.isConfirmed) {
      window.location.href = backHref;
      return;
    }

    const payload = getCurrentDraftPayload();
    const title = payload.form.training_title || 'Untitled Training';
    const nowIso = new Date().toISOString();
    const id = activeDraftId || `d_${Date.now()}_${Math.random().toString(16).slice(2)}`;

    const draft = { id, title, saved_at: nowIso, data: payload };
    try {
      const r = await saveDraftToDb(draft);
      if (!(r && r.success)) {
        upsertDraft(draft);
      }
    } catch (_) {
      upsertDraft(draft);
    }

    window.location.href = 'drafts.php';
  };

  const handleAddTrainingCancel = async (cancelHref) => {
    if (!window.Swal) {
      const ok = window.confirm('Do you want to save this as a draft so you can continue later?');
      if (!ok) {
        window.location.href = cancelHref;
        return;
      }
    }

    const res = await swalFire({
      icon: 'question',
      title: 'Save as draft?',
      text: 'Do you want to save this as a draft so you can continue later?',
      showDenyButton: true,
      showCancelButton: true,
      confirmButtonText: 'Save Draft',
      denyButtonText: 'Discard',
      cancelButtonText: 'Cancel'
    });

    if (!res) return;

    if (res.isDenied) {
      window.location.href = cancelHref;
      return;
    }

    if (!res.isConfirmed) {
      return;
    }

    const payload = getCurrentDraftPayload();
    const title = payload.form.training_title || 'Untitled Training';
    const nowIso = new Date().toISOString();
    const id = activeDraftId || `d_${Date.now()}_${Math.random().toString(16).slice(2)}`;

    const draft = { id, title, saved_at: nowIso, data: payload };
    try {
      const r = await saveDraftToDb(draft);
      if (!(r && r.success)) {
        upsertDraft(draft);
      }
    } catch (_) {
      upsertDraft(draft);
    }

    window.location.href = 'drafts.php';
  };

  const renderTrainingCards = () => {
    if (!trainingCards) return;

    let filtered = trainings.slice();
    if (currentFilter.type !== 'all') {
      filtered = filtered.filter((t) => (t.training_type || '') === currentFilter.type);
    }
    if (currentFilter.status !== 'all') {
      filtered = filtered.filter((t) => (t.status || '') === currentFilter.status);
    }

    trainingCards.innerHTML = '';
    if (!filtered.length) {
      trainingCards.innerHTML = '<div class="col-span-full text-center text-gray-500 py-10">No training programs found.</div>';
      return;
    }

    const actionButtonsHtml = (t) => {
      const st = String(t && t.status ? t.status : '');
      const s = st.toLowerCase();

      let html = '';

      const viewBtn = `
        <button data-action="view" data-id="${t.id}" class="btn btn-xs btn-ghost" title="View Details">
          <i data-lucide="eye" class="h-4 w-4"></i>
        </button>
      `;

      if (s === 'approved') {
        html += viewBtn;
        html += `
          <button data-action="edit" data-id="${t.id}" class="btn btn-xs btn-outline" title="Edit">
            <i data-lucide="pencil" class="h-4 w-4"></i>
          </button>
          <button data-action="hold" data-id="${t.id}" class="btn btn-xs btn-warning" title="Hold">
            <i data-lucide="pause-circle" class="h-4 w-4"></i>
          </button>
        `;
      } else if (s === 'on hold') {
        html += `
          <button data-action="edit" data-id="${t.id}" class="btn btn-xs btn-outline" title="Edit">
            <i data-lucide="pencil" class="h-4 w-4"></i>
          </button>
        `;
      } else if (s === 'for compliance') {
        html += `
          <button data-action="edit" data-id="${t.id}" class="btn btn-xs btn-outline" title="Edit">
            <i data-lucide="pencil" class="h-4 w-4"></i>
          </button>
          <button data-action="delete" data-id="${t.id}" class="btn btn-xs btn-error" title="Delete">
            <i data-lucide="trash-2" class="h-4 w-4"></i>
          </button>
        `;
      } else if (s === 'rejected') {
        html += viewBtn;
        html += `
          <button data-action="delete" data-id="${t.id}" class="btn btn-xs btn-error" title="Delete">
            <i data-lucide="trash-2" class="h-4 w-4"></i>
          </button>
        `;
      } else if (s === 'under review' || s === 'pending') {
        html += viewBtn;
        html += `
          <button data-action="cancel" data-id="${t.id}" class="btn btn-xs btn-ghost" title="Cancel">
            <i data-lucide="x" class="h-4 w-4"></i>
          </button>
        `;
      } else {
        html += viewBtn;
      }

      return html;
    };

    filtered.forEach((t) => {
      const startText = formatDateTime(t.start_datetime);
      const endText = formatDateTime(t.end_datetime);
      const durationText = getDurationText(t.start_datetime, t.end_datetime);

      const card = document.createElement('div');
      card.className = 'training-card bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden';
      card.innerHTML = `
        <div class="p-5">
          <div class="flex justify-between items-start mb-4">
            <div>
              <div class="flex items-center gap-2 mb-2">
                <span class="badge badge-outline">${t.training_type || ''}</span>
                <span class="status-badge ${statusClass(t.status)}">${t.status || ''}</span>
              </div>
              <h3 class="font-bold text-lg text-gray-900 line-clamp-1">${t.training_title || ''}</h3>
            </div>
          </div>
          <div class="space-y-3 mb-4">
            <div class="flex items-center gap-2 text-sm text-gray-600">
              <i data-lucide="calendar" class="h-4 w-4"></i>
              <span>${startText}</span>
            </div>
            <div class="flex items-center gap-2 text-sm text-gray-600">
              <i data-lucide="clock" class="h-4 w-4"></i>
              <span>${durationText}</span>
            </div>
            <div class="flex items-center gap-2 text-sm text-gray-600">
              <i data-lucide="tag" class="h-4 w-4"></i>
              <span>${t.category || ''}</span>
            </div>
            <div class="flex items-center gap-2 text-sm text-gray-600">
              <i data-lucide="target" class="h-4 w-4"></i>
              <span>${t.target_audience || ''}</span>
            </div>
          </div>
          <p class="text-sm text-gray-500 mb-5 line-clamp-2">${String(t.description || '').replace(/</g, '&lt;')}</p>
          <div class="flex justify-between items-center border-t border-gray-100 pt-4">
            <span class="text-xs text-gray-400">${endText ? `Ends ${endText}` : ''}</span>
            <div class="flex items-center gap-2">
              ${actionButtonsHtml(t)}
            </div>
          </div>
        </div>
      `;
      trainingCards.appendChild(card);
    });

    if (window.lucide) window.lucide.createIcons();
  };

  const updateStats = () => {
    const total = trainings.length;
    const totalEl = qs('#total-trainings');
    if (totalEl) totalEl.textContent = String(total);

    const active = trainings.filter((t) => {
      const s = String(t.status || '').toLowerCase();
      return s !== 'completed' && s !== 'cancelled';
    }).length;

    const activeEl = qs('#active-trainings');
    if (activeEl) activeEl.textContent = String(active);

    const activeProgress = qs('#active-progress');
    if (activeProgress) activeProgress.style.width = total ? `${Math.round((active / total) * 100)}%` : '0%';

    const now = new Date();
    const upcoming = trainings.filter((t) => {
      const s = new Date(String(t.start_datetime || '').replace(' ', 'T'));
      return !isNaN(s.getTime()) && s > now;
    }).length;

    const upcomingEl = qs('#upcoming-trainings');
    if (upcomingEl) upcomingEl.textContent = String(upcoming);
  };

  const loadTrainings = async () => {
    try {
      const res = await fetch('trainingprogram.php?action=list_programs', { credentials: 'same-origin' });
      const data = await res.json();
      if (data && data.success) {
        trainings = (data.programs || []).map((p) => ({
          id: parseInt(p.id, 10),
          training_title: p.training_title,
          training_type: p.training_type,
          description: p.description,
          target_audience: p.target_audience,
          department_id: p.department_id,
          target_role: p.target_role,
          category: p.category,
          participants_needed: parseInt(p.participants_needed, 10),
          start_datetime: p.start_datetime,
          end_datetime: p.end_datetime,
          status: p.status,
          status_reason: p.status_reason,
          need_budget: parseInt(p.need_budget, 10),
          need_items: parseInt(p.need_items, 10),
          need_facility: parseInt(p.need_facility, 10),
          created_at: p.created_at,
          updated_at: p.updated_at
        }));
        renderTrainingCards();
        updateStats();

        if (pendingOpenProgramId) {
          const toOpen = pendingOpenProgramId;
          pendingOpenProgramId = null;
          viewTraining(toOpen);
        }
      }
    } catch (_) {
    }
  };

  const resetForm = () => {
    viewingTrainingId = null;
    if (trainingForm) trainingForm.reset();
    if (departmentContainer) departmentContainer.classList.add('hidden');
    if (subDepartmentContainer) subDepartmentContainer.classList.add('hidden');
    if (subDepartmentSelect) {
      subDepartmentSelect.required = false;
      subDepartmentSelect.value = '';
    }
    if (roleContainer) roleContainer.classList.add('hidden');
    if (employeeContainer) employeeContainer.classList.add('hidden');
    if (trainingEmployeeInput) trainingEmployeeInput.required = false;
    setDefaultDates();

    setSelectedObjectives([]);
    syncObjectiveChoicesByDepartment();
    updateObjectivesSummary();

    requestDraft.budget.completed = false;
    requestDraft.budget.basic = {
      title: '', purpose: '', department: '', event_date: '', justification: '', remarks: ''
    };
    requestDraft.budget.items = [];
    requestDraft.budget.total_cost = 0;

    requestDraft.logistics.completed = false;
    requestDraft.logistics.basic = {
      title: '', purpose: '', department: '', event_date: '', needed_by_date: ''
    };
    requestDraft.logistics.items = [];
    requestDraft.logistics.delivery = { location: '', contact_person: '' };
    requestDraft.logistics.remarks = '';

    requestDraft.facility.completed = false;
    requestDraft.facility.basic = {
      title: '', purpose: '', department: '', event_date: ''
    };
    requestDraft.facility.location = { preferred_location: '', start_time: '', end_time: '' };
    requestDraft.facility.special_requirements = '';
    requestDraft.facility.remarks = '';

    if (budgetItemsContainer) budgetItemsContainer.innerHTML = '';
    if (logisticsItemsContainer) logisticsItemsContainer.innerHTML = '';
    if (budgetTotalCostEl) budgetTotalCostEl.textContent = '0.00';
    updateRequestSummaries();
  };

  const getDefaultEventDate = () => (qs('#start-date') ? qs('#start-date').value : '');

  let lastNeedBudgetValue = needBudgetSelect ? String(needBudgetSelect.value || '0') : '0';
  let lastNeedItemsValue = needItemsSelect ? String(needItemsSelect.value || '0') : '0';
  let lastNeedFacilityValue = needFacilitySelect ? String(needFacilitySelect.value || '0') : '0';

  let budgetModalSession = null;
  let logisticsModalSession = null;
  let facilityModalSession = null;

  let objectivesModalSession = null;

  const deepCopy = (v) => {
    try {
      return JSON.parse(JSON.stringify(v));
    } catch (_) {
      return null;
    }
  };

  const getSelectedObjectives = () => {
    try {
      return Array.from(document.querySelectorAll('.js-training-objective'))
        .filter((el) => el && el.checked)
        .map((el) => String(el.value || ''))
        .filter((v) => v.trim() !== '');
    } catch (_) {
      return [];
    }
  };

  const setSelectedObjectives = (values) => {
    const vals = Array.isArray(values) ? values.map(String) : [];
    try {
      Array.from(document.querySelectorAll('.js-training-objective')).forEach((el) => {
        if (!el) return;
        el.checked = vals.includes(String(el.value || ''));
      });
    } catch (_) {
    }
  };

  const updateObjectivesSummary = () => {
    if (!objectivesSummary) return;
    const vals = getSelectedObjectives();
    if (!vals.length) {
      objectivesSummary.textContent = 'No objectives selected.';
      return;
    }
    objectivesSummary.textContent = vals.join(', ');
  };

  const syncObjectiveChoicesByDepartment = () => {
    try {
      Array.from(document.querySelectorAll('.js-training-objective')).forEach((el) => {
        if (!el) return;
        const wrapper = el.closest('label');
        if (wrapper) wrapper.classList.remove('hidden');
      });
    } catch (_) {
    }
  };

  const getTrainingContext = () => {
    const title = (qs('#training-title') || {}).value || '';
    const department = (qs('#training-department') || {}).value || '';
    const event_date = (qs('#start-date') || {}).value || '';
    const purpose = '';
    const start_time = (qs('#start-time') || {}).value || '';
    const end_time = (qs('#end-time') || {}).value || '';

    return { title, department, event_date, purpose, start_time, end_time };
  };

  const syncParticipantsLimits = () => {
    const pnEl = qs('#participants-needed');
    const mpEl = qs('#max-participants');
    if (!pnEl || !mpEl) return;
    const pn = parseInt(String(pnEl.value || '0'), 10);
    if (!isNaN(pn) && pn > 0) {
      mpEl.min = String(pn);
      const mp = parseInt(String(mpEl.value || '0'), 10);
      if (!mpEl.value || isNaN(mp) || mp < pn) {
        mpEl.value = String(pn);
      }
    }
  };

  const syncDepartmentToRequests = () => {
    const dept = (qs('#training-department') || {}).value || '';
    if (!dept) return;

    // Keep request department in sync unless the request is already completed.
    if (!requestDraft.budget.completed) requestDraft.budget.basic.department = dept;
    if (!requestDraft.logistics.completed) requestDraft.logistics.basic.department = dept;
    if (!requestDraft.facility.completed) requestDraft.facility.basic.department = dept;

    const setVal = (id, value) => {
      const el = document.getElementById(id);
      if (el) el.value = value;
    };
    setVal('budget-department', dept);
    setVal('logistics-department', dept);
    setVal('facility-department', dept);
  };

  const calculateBudgetTotal = (items) => items.reduce((sum, it) => sum + ((it.quantity || 0) * (it.unit_cost || 0)), 0);

  const getBudgetItemsFromUI = () => {
    if (!budgetItemsContainer) return [];
    const rows = Array.from(budgetItemsContainer.querySelectorAll('.bg-white'));
    return rows.map((r) => {
      const category = (r.querySelector('.budget-item-category') || {}).value || '';
      const description = (r.querySelector('.budget-item-desc') || {}).value || '';
      const quantityRaw = (r.querySelector('.budget-item-qty') || {}).value || '0';
      const unitCostRaw = (r.querySelector('.budget-item-unit') || {}).value || '0';
      const remarks = (r.querySelector('.budget-item-remarks') || {}).value || '';
      const quantity = parseFloat(quantityRaw);
      const unit_cost = parseFloat(unitCostRaw);
      return {
        category,
        description,
        quantity: isNaN(quantity) ? 0 : quantity,
        unit_cost: isNaN(unit_cost) ? 0 : unit_cost,
        remarks
      };
    }).filter((i) => i.category !== '' || i.description !== '' || i.quantity > 0 || i.unit_cost > 0 || i.remarks !== '');
  };

  const updateBudgetTotalFromUI = () => {
    const items = getBudgetItemsFromUI();
    const total = calculateBudgetTotal(items);
    if (budgetTotalCostEl) budgetTotalCostEl.textContent = total.toFixed(2);
  };

  const addBudgetItemRow = (item) => {
    if (!budgetItemsContainer) return;

    const row = document.createElement('div');
    row.className = 'bg-white rounded-lg p-4 border border-base-300';
    row.innerHTML = `
      <div class="flex items-center justify-between mb-3">
        <div class="text-sm font-semibold text-gray-700">Budget Item</div>
        <button type="button" class="btn btn-ghost btn-xs" data-action="remove-budget-item">Remove</button>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="form-control">
          <label class="label"><span class="label-text">Expense Category</span></label>
          <select class="select select-bordered budget-item-category" required>
            <option value="" selected>Select Category</option>
            <option value="Venue">Venue</option>
            <option value="Meals">Meals</option>
            <option value="Materials">Materials</option>
            <option value="Transport">Transport</option>
            <option value="Accommodation">Accommodation</option>
            <option value="Others">Others</option>
          </select>
        </div>
        <div class="form-control md:col-span-2">
          <label class="label"><span class="label-text">Description</span></label>
          <textarea class="textarea textarea-bordered w-full budget-item-desc" rows="2" required placeholder="E.g., Conference hall rental"></textarea>
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text">Quantity</span></label>
          <input type="number" min="1" class="input input-bordered budget-item-qty" required value="1">
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
        <div class="form-control">
          <label class="label"><span class="label-text">Unit Cost (₱)</span></label>
          <input type="number" min="0" step="0.01" class="input input-bordered budget-item-unit" required value="0.00">
        </div>
        <div class="form-control md:col-span-2">
          <label class="label"><span class="label-text">Remarks</span></label>
          <textarea class="textarea textarea-bordered w-full budget-item-remarks" rows="2" placeholder="Additional details..."></textarea>
        </div>
      </div>
    `;

    budgetItemsContainer.appendChild(row);

    const setVal = (selector, value) => {
      const el = row.querySelector(selector);
      if (el && value !== null && value !== undefined) el.value = value;
    };

    if (item) {
      setVal('.budget-item-category', item.category || '');
      setVal('.budget-item-desc', item.description || '');
      setVal('.budget-item-qty', item.quantity || 1);
      setVal('.budget-item-unit', item.unit_cost || 0);
      setVal('.budget-item-remarks', item.remarks || '');
    }

    row.addEventListener('input', updateBudgetTotalFromUI);

    const removeBtn = row.querySelector('[data-action="remove-budget-item"]');
    if (removeBtn) {
      removeBtn.addEventListener('click', () => {
        row.remove();
        updateBudgetTotalFromUI();
      });
    }

    updateBudgetTotalFromUI();
  };

  const getLogisticsItemsFromUI = () => {
    if (!logisticsItemsContainer) return [];
    const rows = Array.from(logisticsItemsContainer.querySelectorAll('.bg-white'));
    return rows.map((r) => {
      const category = (r.querySelector('.logistics-item-category') || {}).value || '';
      const name = (r.querySelector('.logistics-item-name') || {}).value || '';
      const quantityRaw = (r.querySelector('.logistics-item-qty') || {}).value || '0';
      const unit = (r.querySelector('.logistics-item-unit') || {}).value || '';
      const remarks = (r.querySelector('.logistics-item-remarks') || {}).value || '';
      const quantity = parseFloat(quantityRaw);
      return {
        category,
        name,
        quantity: isNaN(quantity) ? 0 : quantity,
        unit,
        remarks
      };
    }).filter((i) => i.category !== '' || i.name !== '' || i.quantity > 0 || i.unit !== '' || i.remarks !== '');
  };

  const addLogisticsItemRow = (item) => {
    if (!logisticsItemsContainer) return;

    const row = document.createElement('div');
    row.className = 'bg-white rounded-lg p-4 border border-base-300';
    row.innerHTML = `
      <div class="flex items-center justify-between mb-3">
        <div class="text-sm font-semibold text-gray-700">Item</div>
        <button type="button" class="btn btn-ghost btn-xs" data-action="remove-logistics-item">Remove</button>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="form-control">
          <label class="label"><span class="label-text">Item Category</span></label>
          <select class="select select-bordered logistics-item-category" required>
            <option value="" selected>Select Category</option>
            <option value="Electronics">Electronics</option>
            <option value="Furniture">Furniture</option>
            <option value="Supplies">Supplies</option>
            <option value="AV Equipment">AV Equipment</option>
            <option value="Others">Others</option>
          </select>
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text">Item Name</span></label>
          <input type="text" class="input input-bordered logistics-item-name" required placeholder="E.g., Laptop, Projector">
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text">Quantity</span></label>
          <input type="number" min="1" class="input input-bordered logistics-item-qty" required value="1">
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text">Unit</span></label>
          <select class="select select-bordered logistics-item-unit" required>
            <option value="" selected>Select Unit</option>
            <option value="Pieces">Pieces</option>
            <option value="Sets">Sets</option>
            <option value="Boxes">Boxes</option>
            <option value="Units">Units</option>
          </select>
        </div>
      </div>
      <div class="form-control mt-3">
        <label class="label"><span class="label-text">Remarks</span></label>
        <textarea class="textarea textarea-bordered w-full logistics-item-remarks" rows="2" placeholder="Specifications, brand preferences, or special requirements..."></textarea>
      </div>
    `;

    logisticsItemsContainer.appendChild(row);

    const setVal = (selector, value) => {
      const el = row.querySelector(selector);
      if (el && value !== null && value !== undefined) el.value = value;
    };

    if (item) {
      setVal('.logistics-item-category', item.category || '');
      setVal('.logistics-item-name', item.name || '');
      setVal('.logistics-item-qty', item.quantity || 1);
      setVal('.logistics-item-unit', item.unit || '');
      setVal('.logistics-item-remarks', item.remarks || '');
    }

    const removeBtn = row.querySelector('[data-action="remove-logistics-item"]');
    if (removeBtn) {
      removeBtn.addEventListener('click', () => {
        row.remove();
      });
    }
  };

  const openBudgetRequestModal = () => {
    if (!budgetRequestModal) return;

    budgetModalSession = {
      prevNeedValue: String(lastNeedBudgetValue || '0'),
      prevDraft: deepCopy(requestDraft.budget),
      saved: false
    };

    const ctx = getTrainingContext();
    if (!requestDraft.budget.completed) {
      requestDraft.budget.basic.title = ctx.title;
      requestDraft.budget.basic.department = ctx.department;
      requestDraft.budget.basic.event_date = ctx.event_date;
    }

    const set = (id, value) => {
      const el = document.getElementById(id);
      if (el) el.value = value;
    };

    set('budget-title', requestDraft.budget.basic.title || ctx.title || '');
    set('budget-purpose', requestDraft.budget.basic.purpose || '');
    set('budget-department', ctx.department || requestDraft.budget.basic.department || '');
    set('budget-event-date', requestDraft.budget.basic.event_date || ctx.event_date || getDefaultEventDate());
    set('budget-justification', requestDraft.budget.basic.justification || '');
    set('budget-remarks', requestDraft.budget.basic.remarks || '');

    if (budgetItemsContainer) {
      budgetItemsContainer.innerHTML = '';
      if (requestDraft.budget.items && requestDraft.budget.items.length) {
        requestDraft.budget.items.forEach((it) => addBudgetItemRow(it));
      } else {
        addBudgetItemRow();
      }
    }
    updateBudgetTotalFromUI();

    budgetRequestModal.showModal();
  };

  const openLogisticsRequestModal = () => {
    if (!logisticsRequestModal) return;

    logisticsModalSession = {
      prevNeedValue: String(lastNeedItemsValue || '0'),
      prevDraft: deepCopy(requestDraft.logistics),
      saved: false
    };

    const ctx = getTrainingContext();
    if (!requestDraft.logistics.completed) {
      requestDraft.logistics.basic.title = ctx.title;
      requestDraft.logistics.basic.department = ctx.department;
      requestDraft.logistics.basic.event_date = ctx.event_date;
      requestDraft.logistics.basic.needed_by_date = ctx.event_date;
    }

    const set = (id, value) => {
      const el = document.getElementById(id);
      if (el) el.value = value;
    };

    set('logistics-title', requestDraft.logistics.basic.title || ctx.title || '');
    set('logistics-purpose', requestDraft.logistics.basic.purpose || '');
    set('logistics-department', ctx.department || requestDraft.logistics.basic.department || '');
    set('logistics-event-date', requestDraft.logistics.basic.event_date || ctx.event_date || getDefaultEventDate());
    set('logistics-needed-by-date', requestDraft.logistics.basic.needed_by_date || ctx.event_date || getDefaultEventDate());

    if (logisticsItemsContainer) {
      logisticsItemsContainer.innerHTML = '';
      if (requestDraft.logistics.items && requestDraft.logistics.items.length) {
        requestDraft.logistics.items.forEach((it) => addLogisticsItemRow(it));
      } else {
        addLogisticsItemRow();
      }
    }

    set('logistics-delivery-location', requestDraft.logistics.delivery.location || '');
    set('logistics-contact-person', requestDraft.logistics.delivery.contact_person || '');
    set('logistics-remarks', requestDraft.logistics.remarks || '');

    logisticsRequestModal.showModal();
  };

  const openFacilityRequestModal = () => {
    if (!facilityRequestModal) return;

    facilityModalSession = {
      prevNeedValue: String(lastNeedFacilityValue || '0'),
      prevDraft: deepCopy(requestDraft.facility),
      saved: false
    };

    const ctx = getTrainingContext();
    if (!requestDraft.facility.completed) {
      requestDraft.facility.basic.title = ctx.title;
      requestDraft.facility.basic.department = ctx.department;
      requestDraft.facility.basic.event_date = ctx.event_date;
      requestDraft.facility.location.start_time = ctx.start_time;
      requestDraft.facility.location.end_time = ctx.end_time;
    }

    const set = (id, value) => {
      const el = document.getElementById(id);
      if (el) el.value = value;
    };

    set('facility-title', requestDraft.facility.basic.title || ctx.title || '');
    set('facility-purpose', requestDraft.facility.basic.purpose || '');
    set('facility-department', ctx.department || requestDraft.facility.basic.department || '');
    set('facility-event-date', requestDraft.facility.basic.event_date || ctx.event_date || getDefaultEventDate());

    set('facility-preferred-location', requestDraft.facility.location.preferred_location || '');
    set('facility-start-time', requestDraft.facility.location.start_time || ctx.start_time || '');
    set('facility-end-time', requestDraft.facility.location.end_time || ctx.end_time || '');

    set('facility-special-requirements', requestDraft.facility.special_requirements || '');
    set('facility-remarks', requestDraft.facility.remarks || '');

    facilityRequestModal.showModal();
  };

  const openObjectivesModal = () => {
    if (!objectivesModal) return;
    objectivesModalSession = { prevValues: getSelectedObjectives(), saved: false };
    syncObjectiveChoicesByDepartment();
    objectivesModal.showModal();
  };

  const handleTargetAudienceChange = () => {
    const targetAudience = targetAudienceSelect ? targetAudienceSelect.value : '';

    if (departmentContainer) departmentContainer.classList.remove('hidden');
    if (roleContainer) roleContainer.classList.remove('hidden');
    if (employeeContainer) employeeContainer.classList.add('hidden');
    if (trainingEmployeeInput) trainingEmployeeInput.required = false;

    const deptSelect = qs('#training-department');
    if (deptSelect) {
      deptSelect.required = true;
    }

    const roleSelect = qs('#training-role');
    if (roleSelect) roleSelect.required = false;

    const catEl = qs('#training-category');
    if (trainingCategoryContainer) trainingCategoryContainer.classList.remove('hidden');
    if (catEl) {
      catEl.required = false;
    }

    if (targetAudience === 'By Department') {
      if (roleSelect) roleSelect.required = true;
      fillRolesByDepartment(true);
    }

    if (targetAudience === 'Specific Employee') {
      if (employeeContainer) employeeContainer.classList.remove('hidden');
      if (trainingEmployeeInput) trainingEmployeeInput.required = true;
      fillRolesByDepartment(true);
    }

    if (targetAudience === 'Managers' || targetAudience === 'New Hires' || targetAudience === 'Trainee') {
      fillRolesByDepartment(true);
    }

    if (targetAudience === 'Trainee') {
      if (trainingCategoryContainer) trainingCategoryContainer.classList.add('hidden');
      if (catEl) {
        catEl.value = '';
        catEl.required = false;
      }
    }

    syncMentorOptionsForTargetAudience();
  };

  const filterByType = (type) => {
    currentFilter.type = type;
    renderTrainingCards();
  };

  const filterByStatus = (status) => {
    currentFilter.status = status;
    renderTrainingCards();
  };

  const viewTraining = (id) => {
    const training = trainings.find((t) => t.id == id);
    if (!training) return;

    viewingTrainingId = id;

    const setText = (id2, value) => {
      const el = document.getElementById(id2);
      if (el) el.textContent = value;
    };

    setText('view-training-title', training.training_title || '');
    setText('view-training-type', training.training_type || '');
    setText('view-category', training.category || '');
    setText('view-target-audience', training.target_audience || '');
    setText('view-status', training.status || '');
    setText('view-start-date', formatDateTime(training.start_datetime));
    setText('view-end-date', formatDateTime(training.end_datetime));
    setText('view-duration', getDurationText(training.start_datetime, training.end_datetime));
    setText('view-competency-level', String(training.training_level || ''));
    setText('view-description', training.description || '');
    setText('view-created-date', formatDateTime(training.created_at));
    setText('view-updated-date', formatDateTime(training.updated_at));

    const reasonContainer = document.getElementById('view-status-reason-container');
    const reasonEl = document.getElementById('view-status-reason');
    const statusText = String(training.status || '');
    const reasonText = String(training.status_reason || '');
    const shouldShowReason = (statusText === 'Rejected' || statusText === 'For Compliance') && reasonText.trim() !== '';
    if (reasonContainer) {
      if (shouldShowReason) reasonContainer.classList.remove('hidden');
      else reasonContainer.classList.add('hidden');
    }
    if (reasonEl) reasonEl.textContent = shouldShowReason ? reasonText : '';

    const reqContainer = document.getElementById('view-request-statuses-container');
    const finStatus = document.getElementById('view-financial-status');
    const finReason = document.getElementById('view-financial-reason');
    const logStatus = document.getElementById('view-logistics-status');
    const logReason = document.getElementById('view-logistics-reason');
    const admStatus = document.getElementById('view-admin-status');
    const admReason = document.getElementById('view-admin-reason');

    const showReqStatuses = statusText === 'Approved' || statusText === 'ON HOLD';
    if (reqContainer) {
      if (showReqStatuses) reqContainer.classList.remove('hidden');
      else reqContainer.classList.add('hidden');
    }

    if (postTrainingBtn) {
      postTrainingBtn.classList.add('hidden');
      postTrainingBtn.removeAttribute('data-program-id');
    }

    const setReq = (statusEl, reasonEl2, req) => {
      const raw = req && req.status ? String(req.status) : '';
      let st = raw;
      if (!st) st = 'Not Requested';
      if (String(st).toLowerCase() === 'pending') st = 'PENDING';
      if (statusEl) statusEl.textContent = st;

      const rr = req && req.rejection_reason ? String(req.rejection_reason) : '';
      if (reasonEl2) reasonEl2.textContent = String(st).toLowerCase() === 'rejected' && rr.trim() !== '' ? rr : '';
    };

    if (showReqStatuses) {
      fetch(`trainingprogram.php?action=get_program_requests&program_id=${encodeURIComponent(String(training.id))}`, { credentials: 'same-origin' })
        .then((r) => r.json())
        .then((d) => {
          if (!d || !d.success) return;
          const reqs = d.requests || {};
          setReq(finStatus, finReason, reqs.financial);
          setReq(logStatus, logReason, reqs.logistics);
          setReq(admStatus, admReason, reqs.admin);

          if (postTrainingBtn) {
            const needBudget = String(training.need_budget || '') === '1' || training.need_budget === 1;
            const needItems = String(training.need_items || '') === '1' || training.need_items === 1;
            const needFacility = String(training.need_facility || '') === '1' || training.need_facility === 1;

            const finOk = !needBudget || (reqs.financial && String(reqs.financial.status || '') === 'Approved');
            const logOk = !needItems || (reqs.logistics && String(reqs.logistics.status || '') === 'Approved');
            const admOk = !needFacility || (reqs.admin && String(reqs.admin.status || '') === 'Approved');

            if (statusText === 'Approved' && finOk && logOk && admOk) {
              postTrainingBtn.classList.remove('hidden');
              postTrainingBtn.setAttribute('data-program-id', String(training.id));
            } else {
              postTrainingBtn.classList.add('hidden');
              postTrainingBtn.removeAttribute('data-program-id');
            }
          }

          if (d.program_status && String(d.program_status) !== String(training.status)) {
            training.status = String(d.program_status);
            setText('view-status', training.status);
            renderTrainingCards();
            updateStats();
          }
        })
        .catch(() => {
        });
    }

    if (resubmitTrainingBtn) {
      if (statusText === 'ON HOLD') {
        resubmitTrainingBtn.classList.remove('hidden');
        resubmitTrainingBtn.setAttribute('data-program-id', String(training.id));
      } else {
        resubmitTrainingBtn.classList.add('hidden');
        resubmitTrainingBtn.removeAttribute('data-program-id');
      }
    }

    if (viewTrainingModal) viewTrainingModal.showModal();
  };

  const deptIdByName = (name) => {
    const n = String(name || '').toLowerCase();
    if (n.includes('front office')) return '1';
    if (n.includes('housekeeping')) return '2';
    if (n.includes('food') || n.includes('beverage') || n.includes('f&b')) return '3';
    if (n.includes('kitchen') || n.includes('culinary')) return '4';
    if (n.includes('sales') || n.includes('marketing')) return '5';
    if (n.includes('human resources') || n.includes('hr')) return '6';
    if (n.includes('finance') || n.includes('accounting')) return '7';
    if (n.includes('engineering') || n.includes('maintenance')) return '8';
    if (n.includes('security')) return '9';
    return '';
  };

  const openIdpPickThenModal = async () => {
    try {
      const fd = new FormData();
      fd.append('action', 'list_idps');
      const res = await fetch('trainingrequest.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      const data = await res.json();
      const items = (data && data.success && Array.isArray(data.items)) ? data.items : [];
      if (!items.length) {
        if (window.Swal) await swalFire({ icon: 'info', title: 'No IDP Requests', text: 'No eligible IDP requests found.', timer: 1400, showConfirmButton: false });
        resetForm();
        if (modalTitle) modalTitle.textContent = 'Create New Training Program';
        if (modalSubtitle) modalSubtitle.textContent = 'Fill in all required information to create a new training program';
        trainingModal.showModal();
        return;
      }
      const inputOptions = {};
      items.forEach((it) => {
        const id = String(it.id);
        const label = `${String(it.employee_name || '')} — ${String(it.department || '')}`;
        inputOptions[id] = label;
      });
      const pick = await swalFire({
        title: 'Select IDP Request',
        input: 'select',
        inputOptions,
        inputPlaceholder: 'Choose an employee request',
        showCancelButton: true,
        confirmButtonText: 'Continue'
      });
      if (!pick || !pick.isConfirmed) return;
      const chosenId = String(pick.value || '');
      if (!chosenId) return;
      const fd2 = new FormData();
      fd2.append('action', 'get_idp_details');
      fd2.append('idp_id', chosenId);
      const res2 = await fetch('trainingrequest.php', { method: 'POST', body: fd2, credentials: 'same-origin' });
      const det = await res2.json();
      const idp = (det && det.success && det.idp) ? det.idp : null;
      resetForm();
      if (idp) {
        const tt = String(idp.requested_training_type || '');
        const tm = String(idp.requested_training_mode || idp.delivery_mode || '');
        const deptN = String(idp.department || '');
        const empName = String(idp.employee_name || '');
        const empId = String(idp.employee_id || '');
        const sd = String(idp.requested_start_datetime || '');
        const ed = String(idp.requested_end_datetime || '');

        const setSelect = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };
        const setInput = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };
        setSelect('training-type', tt);
        setSelect('training-mode', tm);
        setInput('training-title', `${tt || 'Training'} - ${empName || ''}`.trim());
        setSelect('requested-by', 'IDP');
        setSelect('target-audience', 'Specific Employee');
        if (typeof window.handleTargetAudienceChange === 'function') window.handleTargetAudienceChange();
        const deptId = deptIdByName(deptN);
        const deptEl = document.getElementById('training-department');
        if (deptEl) {
          const hasOpt = Array.from(deptEl.options || []).some((o) => String(o.value || '') === String(deptId || ''));
          if (deptId && hasOpt) deptEl.value = deptId;
        }
        if (typeof fillRolesByDepartment === 'function') fillRolesByDepartment(false);
        if (typeof syncMentorOptionsForTargetAudience === 'function') syncMentorOptionsForTargetAudience();

        const empSelect = document.getElementById('training-employee');
        if (empSelect) {
          let matched = false;
          const opts = Array.from(empSelect.options || []);
          for (const o of opts) {
            if (String(o.value || '') === empId) { o.selected = true; matched = true; break; }
          }
          if (!matched) {
            for (const o of opts) {
              const txt = String(o.textContent || '').toLowerCase();
              if (txt.includes(empName.toLowerCase())) { o.selected = true; break; }
            }
          }
        }

        const parseDt = (s) => {
          if (!s) return null;
          const d = new Date(String(s).replace(' ', 'T'));
          if (isNaN(d.getTime())) return null;
          return d;
        };
        const fmtDt = (s) => {
          const d = parseDt(s);
          if (!d) return '';
          return d.toLocaleString();
        };
        const banner = document.getElementById('idp-request-banner');
        const bEmp = document.getElementById('idp-employee');
        const bDept = document.getElementById('idp-department');
        const bType = document.getElementById('idp-type');
        const bMode = document.getElementById('idp-mode');
        const bSched = document.getElementById('idp-schedule');
        if (banner) {
          banner.classList.remove('hidden');
          if (bEmp) bEmp.textContent = empName || '-';
          if (bDept) bDept.textContent = deptN || '-';
          if (bType) bType.textContent = tt || '-';
          if (bMode) bMode.textContent = tm || '-';
          const schedText = `${fmtDt(sd)}${ed ? ' — ' + fmtDt(ed) : ''}`;
          if (bSched) bSched.textContent = schedText || '-';
        }
        const pad2 = (n) => String(n).padStart(2, '0');
        const toYmd = (d) => `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
        const toHm = (d) => `${pad2(d.getHours())}:${pad2(d.getMinutes())}`;
        const sdD = parseDt(sd);
        const edD = parseDt(ed);
        if (sdD) {
          setSelect('start-date', toYmd(sdD));
          setSelect('start-time', toHm(sdD));
        }
        if (edD) {
          setSelect('end-date', toYmd(edD));
          setSelect('end-time', toHm(edD));
        }

        const desc = document.getElementById('description');
        if (desc) {
          desc.value = `Training created from IDP request for ${empName} (${deptN}).`;
          const ev = new Event('input'); desc.dispatchEvent(ev);
        }
      }
      if (modalTitle) modalTitle.textContent = 'Create New Training Program';
      if (modalSubtitle) modalSubtitle.textContent = 'Prefilled from IDP request';
      if (trainingModal) trainingModal.showModal();
    } catch (_) {
      resetForm();
      const banner = document.getElementById('idp-request-banner');
      if (banner) banner.classList.add('hidden');
      if (modalTitle) modalTitle.textContent = 'Create New Training Program';
      if (trainingModal) trainingModal.showModal();
    }
  };

  const showFormView = () => {
    if (idpListContainer) idpListContainer.classList.add('hidden');
    const banner = document.getElementById('idp-request-banner');
    if (banner && banner.classList.contains('hidden')) banner.classList.add('hidden');
    if (trainingForm) trainingForm.classList.remove('hidden');
    if (modalTitle) modalTitle.textContent = 'Create New Training Program';
  };

  const showIdpListInModal = async () => {
    if (trainingForm) trainingForm.classList.add('hidden');
    const banner = document.getElementById('idp-request-banner');
    if (banner) banner.classList.add('hidden');
    if (idpListContainer) idpListContainer.classList.remove('hidden');
    if (modalTitle) modalTitle.textContent = 'Select Training Request';
    if (modalSubtitle) modalSubtitle.textContent = 'Pick a request to prefill the training program';
    if (idpListLoading) idpListLoading.classList.remove('hidden');
    try {
      const fd = new FormData();
      fd.append('action', 'list_idps');
      const res = await fetch('trainingrequest.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      const data = await res.json();
      const items = (data && data.success && Array.isArray(data.items)) ? data.items : [];
      if (idpListLoading) idpListLoading.classList.add('hidden');
      if (idpListTable) {
        idpListTable.innerHTML = items.length ? items.map((r) => {
          const pos = String(r.position || '');
          let comp = '0%';
          const rawComp = r.competency;
          const numComp = typeof rawComp === 'number' ? rawComp : parseFloat(String(rawComp || ''));
          if (!isNaN(numComp)) comp = `${numComp.toFixed(1)}%`;
          const succ = String(r.succession_status || '');
          const sched = (() => {
            const s = String(r.requested_start_datetime || '');
            const e = String(r.requested_end_datetime || '');
            if (s && e) return `${formatDateTime(s)} - ${formatDateTime(e)}`;
            if (s) return formatDateTime(s);
            return 'N/A';
          })();
          const status = String(r.idp_status || '');
          const mode = String(r.requested_training_mode || r.delivery_mode || '');
          return `
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 flex flex-col justify-between">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <div class="font-semibold text-sm text-gray-900">${esc(r.employee_name || '')}</div>
                  <div class="text-xs opacity-70">${esc(r.employee_id || '')}</div>
                  <div class="mt-1 text-xs text-gray-600">${esc(r.department || '')}</div>
                </div>
                <span class="badge badge-sm">${esc(status)}</span>
              </div>
              <div class="mt-3 grid grid-cols-1 gap-1 text-xs text-gray-700">
                <div><span class="font-semibold">Position:</span> ${esc(pos)}</div>
                <div><span class="font-semibold">Competency:</span> ${esc(comp)}</div>
                <div><span class="font-semibold">Succession:</span> ${esc(succ)}</div>
                <div><span class="font-semibold">Type:</span> ${esc(r.requested_training_type || '')}</div>
                <div><span class="font-semibold">Mode:</span> ${esc(mode)}</div>
                <div><span class="font-semibold">Schedule:</span> ${esc(sched)}</div>
              </div>
              <div class="mt-4 flex justify-end gap-2">
                <button type="button" class="btn btn-sm btn-ghost" data-action="view-idp" data-id="${String(r.id)}">
                  View
                </button>
                <button type="button" class="btn btn-sm bg-gray-900 text-white hover:bg-gray-800 border-0" data-action="pick-idp" data-id="${String(r.id)}">
                  Create
                </button>
              </div>
            </div>
          `;
        }).join('') : '<div class="col-span-full text-center py-6 opacity-70 text-sm">No requested IDPs found.</div>';
      }
    } catch (_) {
      if (idpListLoading) idpListLoading.classList.add('hidden');
      if (idpListTable) {
        const tbody = idpListTable.querySelector('tbody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="text-center py-6 opacity-70">Failed to load requests.</td></tr>';
      }
    }
  };

  const setupEventListeners = () => {
    if (addTrainingBtn && trainingModal && (String(addTrainingBtn.tagName || '').toUpperCase() === 'BUTTON' || String(addTrainingBtn.tagName || '').toUpperCase() === 'A')) {
      addTrainingBtn.addEventListener('click', async (e) => {
        if (e && e.preventDefault) e.preventDefault();
        resetForm();
        if (trainingModal) trainingModal.showModal();
        await showIdpListInModal();
      });
    }

    if (cancelBtn && trainingModal) {
      cancelBtn.addEventListener('click', () => {
        trainingModal.close();
        resetForm();
        if (idpListContainer) idpListContainer.classList.add('hidden');
        const banner = document.getElementById('idp-request-banner');
        if (banner) banner.classList.add('hidden');
        if (trainingForm) trainingForm.classList.add('hidden');
      });
    }

    if (closeViewModal && viewTrainingModal) {
      closeViewModal.addEventListener('click', () => {
        viewTrainingModal.close();
      });
    }

    if (resubmitTrainingBtn) {
      resubmitTrainingBtn.addEventListener('click', async () => {
        const programId = resubmitTrainingBtn.getAttribute('data-program-id');
        if (!programId) return;

        const confirmRes = window.Swal ? await swalFire({
          icon: 'question',
          title: 'Resubmit Training Program?',
          text: 'This will send the training program back to Under Review.',
          showCancelButton: true,
          confirmButtonText: 'Resubmit',
          cancelButtonText: 'Cancel'
        }, getOpenDialogTarget()) : { isConfirmed: window.confirm('Resubmit this training program?') };
        if (!confirmRes || !confirmRes.isConfirmed) return;

        const fd = new FormData();
        fd.append('action', 'update_program_status');
        fd.append('program_id', String(programId));
        fd.append('status', 'Under Review');
        fd.append('reason', '');

        try {
          const res = await fetch('trainingprogram.php', { method: 'POST', body: fd, credentials: 'same-origin' });
          const data = await res.json();
          if (!data || !data.success) {
            if (window.Swal) await swalFire({ icon: 'error', title: 'Failed', text: (data && data.message) ? data.message : 'Unable to resubmit.' }, getOpenDialogTarget());
            return;
          }
          if (window.Swal) await swalFire({ icon: 'success', title: 'Resubmitted', timer: 1200, showConfirmButton: false }, getOpenDialogTarget());
          if (viewTrainingModal) viewTrainingModal.close();
          loadTrainings();
        } catch (_) {
          if (window.Swal) await swalFire({ icon: 'error', title: 'Failed', text: 'Unexpected error while resubmitting.' }, getOpenDialogTarget());
        }
      });
    }

    if (postTrainingBtn) {
      postTrainingBtn.addEventListener('click', () => {
        const programId = postTrainingBtn.getAttribute('data-program-id');
        if (!programId) return;
        (async () => {
          const fd = new FormData();
          fd.append('action', 'post_training');
          fd.append('program_id', String(programId));
          try {
            const res = await fetch('trainingprogram.php', { method: 'POST', body: fd, credentials: 'same-origin' });
            const data = await res.json();
            if (!data || !data.success) {
              const msg = (data && data.message) ? data.message : 'Unable to post training.';
              if (window.Swal) await swalFire({ icon: 'error', title: 'Failed', text: msg }, getOpenDialogTarget());
              else window.alert(msg);
              return;
            }
            if (window.Swal) await swalFire({ icon: 'success', title: 'Posted', text: 'Training has been posted.', timer: 1200, showConfirmButton: false }, getOpenDialogTarget());
            window.location.href = 'posted_trainings.php';
          } catch (_) {
            if (window.Swal) await swalFire({ icon: 'error', title: 'Failed', text: 'Unexpected error while posting.' }, getOpenDialogTarget());
            else window.alert('Unexpected error while posting.');
          }
        })();
      });
    }

    if (targetAudienceSelect) {
      targetAudienceSelect.addEventListener('change', handleTargetAudienceChange);
      handleTargetAudienceChange();
    }

    const pnEl = qs('#participants-needed');
    const mpEl = qs('#max-participants');
    if (pnEl) pnEl.addEventListener('input', syncParticipantsLimits);
    if (mpEl) mpEl.addEventListener('input', syncParticipantsLimits);
    syncParticipantsLimits();

    const deptEl = qs('#training-department');
    if (deptEl) {
      deptEl.addEventListener('change', () => {
        fillSubDepartments(false);
        fillRolesByDepartment(false);
        syncTrainingCategoryByDepartmentOptions();
        syncMentorOptionsForTargetAudience();
        syncObjectiveChoicesByDepartment();
        updateObjectivesSummary();
        syncDepartmentToRequests();
      });
    }

    if (requestedBySelect) {
      requestedBySelect.addEventListener('change', () => {
        const v = String(requestedBySelect.value || '');
        if (v === 'New Hire Onboarding') {
          if (targetAudienceSelect) targetAudienceSelect.value = 'Trainee';
          if (typeof window.handleTargetAudienceChange === 'function') {
            window.handleTargetAudienceChange();
          }
        }
      });
    }

    fillSubDepartments(false);
    fillRolesByDepartment(false);
    syncTrainingCategoryByDepartmentOptions();
    syncMentorOptionsForTargetAudience();
    syncObjectiveChoicesByDepartment();
    updateObjectivesSummary();

    if (needBudgetSelect) {
      needBudgetSelect.addEventListener('change', () => {
        const next = String(needBudgetSelect.value || '0');
        if (next === '1') {
          openBudgetRequestModal();
        } else {
          requestDraft.budget.completed = false;
          requestDraft.budget.items = [];
          requestDraft.budget.total_cost = 0;
          if (budgetItemsContainer) budgetItemsContainer.innerHTML = '';
          if (budgetTotalCostEl) budgetTotalCostEl.textContent = '0.00';
          lastNeedBudgetValue = '0';
          updateRequestSummaries();
        }
      });
    }

    if (needItemsSelect) {
      needItemsSelect.addEventListener('change', () => {
        const next = String(needItemsSelect.value || '0');
        if (next === '1') {
          openLogisticsRequestModal();
        } else {
          requestDraft.logistics.completed = false;
          requestDraft.logistics.items = [];
          if (logisticsItemsContainer) logisticsItemsContainer.innerHTML = '';
          lastNeedItemsValue = '0';
          updateRequestSummaries();
        }
      });
    }

    if (needFacilitySelect) {
      needFacilitySelect.addEventListener('change', () => {
        const next = String(needFacilitySelect.value || '0');
        if (next === '1') {
          openFacilityRequestModal();
        } else {
          requestDraft.facility.completed = false;
          lastNeedFacilityValue = '0';
          updateRequestSummaries();
        }
      });
    }

    const closeBudgetModal = () => { if (budgetRequestModal) budgetRequestModal.close(); };
    const closeLogisticsModal = () => { if (logisticsRequestModal) logisticsRequestModal.close(); };
    const closeFacilityModal = () => { if (facilityRequestModal) facilityRequestModal.close(); };

    if (budgetCancelBtn) budgetCancelBtn.addEventListener('click', closeBudgetModal);
    if (budgetCancelActionBtn) budgetCancelActionBtn.addEventListener('click', closeBudgetModal);
    if (logisticsCancelBtn) logisticsCancelBtn.addEventListener('click', closeLogisticsModal);
    if (logisticsCancelActionBtn) logisticsCancelActionBtn.addEventListener('click', closeLogisticsModal);
    if (facilityCancelBtn) facilityCancelBtn.addEventListener('click', closeFacilityModal);
    if (facilityCancelActionBtn) facilityCancelActionBtn.addEventListener('click', closeFacilityModal);

    if (budgetRequestModal) {
      budgetRequestModal.addEventListener('close', () => {
        if (!budgetModalSession) return;
        if (!budgetModalSession.saved) {
          if (needBudgetSelect) needBudgetSelect.value = String(budgetModalSession.prevNeedValue || '0');
          lastNeedBudgetValue = needBudgetSelect ? String(needBudgetSelect.value || '0') : lastNeedBudgetValue;
          if (budgetModalSession.prevDraft) requestDraft.budget = budgetModalSession.prevDraft;
          if (budgetItemsContainer) budgetItemsContainer.innerHTML = '';
          if (budgetTotalCostEl) budgetTotalCostEl.textContent = '0.00';
          updateRequestSummaries();
        }
        budgetModalSession = null;
      });
    }

    if (logisticsRequestModal) {
      logisticsRequestModal.addEventListener('close', () => {
        if (!logisticsModalSession) return;
        if (!logisticsModalSession.saved) {
          if (needItemsSelect) needItemsSelect.value = String(logisticsModalSession.prevNeedValue || '0');
          lastNeedItemsValue = needItemsSelect ? String(needItemsSelect.value || '0') : lastNeedItemsValue;
          if (logisticsModalSession.prevDraft) requestDraft.logistics = logisticsModalSession.prevDraft;
          if (logisticsItemsContainer) logisticsItemsContainer.innerHTML = '';
          updateRequestSummaries();
        }
        logisticsModalSession = null;
      });
    }

    if (facilityRequestModal) {
      facilityRequestModal.addEventListener('close', () => {
        if (!facilityModalSession) return;
        if (!facilityModalSession.saved) {
          if (needFacilitySelect) needFacilitySelect.value = String(facilityModalSession.prevNeedValue || '0');
          lastNeedFacilityValue = needFacilitySelect ? String(needFacilitySelect.value || '0') : lastNeedFacilityValue;
          if (facilityModalSession.prevDraft) requestDraft.facility = facilityModalSession.prevDraft;
          updateRequestSummaries();
        }
        facilityModalSession = null;
      });
    }

    if (objectivesOpenBtn) objectivesOpenBtn.addEventListener('click', openObjectivesModal);

    const closeObjectivesModal = () => { if (objectivesModal) objectivesModal.close(); };
    if (objectivesCloseBtn) objectivesCloseBtn.addEventListener('click', closeObjectivesModal);
    if (objectivesCancelBtn) objectivesCancelBtn.addEventListener('click', closeObjectivesModal);
    if (objectivesApplyBtn) {
      objectivesApplyBtn.addEventListener('click', () => {
        if (objectivesModalSession) objectivesModalSession.saved = true;
        closeObjectivesModal();
        updateObjectivesSummary();
      });
    }
    if (objectivesModal) {
      objectivesModal.addEventListener('close', () => {
        if (!objectivesModalSession) return;
        if (!objectivesModalSession.saved) {
          setSelectedObjectives(objectivesModalSession.prevValues);
        }
        objectivesModalSession = null;
        syncObjectiveChoicesByDepartment();
        updateObjectivesSummary();
      });
    }

    if (budgetAddItemBtn) budgetAddItemBtn.addEventListener('click', () => addBudgetItemRow());
    if (logisticsAddItemBtn) logisticsAddItemBtn.addEventListener('click', () => addLogisticsItemRow());

    if (budgetSaveBtn && budgetRequestModal) {
      budgetSaveBtn.addEventListener('click', async () => {
        const form = document.getElementById('budget-request-form');
        if (form && !form.checkValidity()) {
          form.reportValidity();
          return;
        }

        if (window.Swal) {
          const res = await swalFire({
            icon: 'question',
            title: 'Save Budget Request?',
            text: 'Are you sure you want to save this budget request as complete?',
            showCancelButton: true,
            confirmButtonText: 'Yes, save',
            cancelButtonText: 'Cancel'
          }, budgetRequestModal);
          if (!res.isConfirmed) return;
        } else {
          if (!window.confirm('Save this budget request as complete?')) return;
        }

        const items = getBudgetItemsFromUI();
        const totalCost = calculateBudgetTotal(items);

        requestDraft.budget.basic = {
          title: (document.getElementById('budget-title') || {}).value || '',
          purpose: (document.getElementById('budget-purpose') || {}).value || '',
          department: (document.getElementById('budget-department') || {}).value || '',
          event_date: (document.getElementById('budget-event-date') || {}).value || '',
          justification: (document.getElementById('budget-justification') || {}).value || '',
          remarks: (document.getElementById('budget-remarks') || {}).value || ''
        };

        requestDraft.budget.items = items;
        requestDraft.budget.total_cost = totalCost;
        requestDraft.budget.completed = true;
        if (budgetModalSession) budgetModalSession.saved = true;
        if (needBudgetSelect) {
          needBudgetSelect.value = '1';
          lastNeedBudgetValue = '1';
        }
        budgetRequestModal.close();

        updateRequestSummaries();

        if (window.Swal) await swalFire({ icon: 'success', title: 'Saved', text: 'Budget request saved.', timer: 1200, showConfirmButton: false }, getOpenDialogTarget());
      });
    }

    if (logisticsSaveBtn && logisticsRequestModal) {
      logisticsSaveBtn.addEventListener('click', async () => {
        const form = document.getElementById('logistics-request-form');
        if (form && !form.checkValidity()) {
          form.reportValidity();
          return;
        }

        if (window.Swal) {
          const res = await swalFire({
            icon: 'question',
            title: 'Save Logistics Request?',
            text: 'Are you sure you want to save this logistics request as complete?',
            showCancelButton: true,
            confirmButtonText: 'Yes, save',
            cancelButtonText: 'Cancel'
          }, logisticsRequestModal);
          if (!res.isConfirmed) return;
        } else {
          if (!window.confirm('Save this logistics request as complete?')) return;
        }

        const items = getLogisticsItemsFromUI();

        requestDraft.logistics.basic = {
          title: (document.getElementById('logistics-title') || {}).value || '',
          purpose: (document.getElementById('logistics-purpose') || {}).value || '',
          department: (document.getElementById('logistics-department') || {}).value || '',
          event_date: (document.getElementById('logistics-event-date') || {}).value || '',
          needed_by_date: (document.getElementById('logistics-needed-by-date') || {}).value || '',
        };

        requestDraft.logistics.items = items;
        requestDraft.logistics.delivery = {
          location: (document.getElementById('logistics-delivery-location') || {}).value || '',
          contact_person: (document.getElementById('logistics-contact-person') || {}).value || ''
        };
        requestDraft.logistics.remarks = (document.getElementById('logistics-remarks') || {}).value || '';
        requestDraft.logistics.completed = true;
        if (logisticsModalSession) logisticsModalSession.saved = true;
        if (needItemsSelect) {
          needItemsSelect.value = '1';
          lastNeedItemsValue = '1';
        }
        logisticsRequestModal.close();

        updateRequestSummaries();

        if (window.Swal) await swalFire({ icon: 'success', title: 'Saved', text: 'Logistics request saved.', timer: 1200, showConfirmButton: false }, getOpenDialogTarget());
      });
    }

    if (facilitySaveBtn && facilityRequestModal) {
      facilitySaveBtn.addEventListener('click', async () => {
        const form = document.getElementById('facility-request-form');
        if (form && !form.checkValidity()) {
          form.reportValidity();
          return;
        }

        if (window.Swal) {
          const res = await swalFire({
            icon: 'question',
            title: 'Save Location Request?',
            text: 'Are you sure you want to save this location request as complete?',
            showCancelButton: true,
            confirmButtonText: 'Yes, save',
            cancelButtonText: 'Cancel'
          }, facilityRequestModal);
          if (!res.isConfirmed) return;
        } else {
          if (!window.confirm('Save this location request as complete?')) return;
        }

        requestDraft.facility.basic = {
          title: (document.getElementById('facility-title') || {}).value || '',
          purpose: (document.getElementById('facility-purpose') || {}).value || '',
          department: (document.getElementById('facility-department') || {}).value || '',
          event_date: (document.getElementById('facility-event-date') || {}).value || ''
        };

        requestDraft.facility.location = {
          preferred_location: (document.getElementById('facility-preferred-location') || {}).value || '',
          start_time: (document.getElementById('facility-start-time') || {}).value || '',
          end_time: (document.getElementById('facility-end-time') || {}).value || ''
        };

        requestDraft.facility.special_requirements = (document.getElementById('facility-special-requirements') || {}).value || '';
        requestDraft.facility.remarks = (document.getElementById('facility-remarks') || {}).value || '';
        requestDraft.facility.completed = true;
        if (facilityModalSession) facilityModalSession.saved = true;
        if (needFacilitySelect) {
          needFacilitySelect.value = '1';
          lastNeedFacilityValue = '1';
        }
        facilityRequestModal.close();

        updateRequestSummaries();

        if (window.Swal) await swalFire({ icon: 'success', title: 'Saved', text: 'Location request saved.', timer: 1200, showConfirmButton: false }, getOpenDialogTarget());
      });
    }

    if (saveTrainingBtn) {
      saveTrainingBtn.addEventListener('click', async () => {
        if (!trainingForm) return;
        if (!trainingForm.checkValidity()) {
          trainingForm.reportValidity();
          return;
        }

        if (needBudgetSelect && needBudgetSelect.value === '1' && !requestDraft.budget.completed) {
          if (window.Swal) await swalFire({ icon: 'info', title: 'Budget Request Needed', text: 'Please fill up the Budget Request modal first.' });
          openBudgetRequestModal();
          return;
        }
        if (needItemsSelect && needItemsSelect.value === '1' && !requestDraft.logistics.completed) {
          if (window.Swal) await swalFire({ icon: 'info', title: 'Logistics Request Needed', text: 'Please fill up the Logistics Request modal first.' });
          openLogisticsRequestModal();
          return;
        }
        if (needFacilitySelect && needFacilitySelect.value === '1' && !requestDraft.facility.completed) {
          if (window.Swal) await swalFire({ icon: 'info', title: 'Facility Request Needed', text: 'Please fill up the Facility Request modal first.' });
          openFacilityRequestModal();
          return;
        }

        const startDate = (qs('#start-date') || {}).value || '';
        const startTime = (qs('#start-time') || {}).value || '';
        const endDate = (qs('#end-date') || {}).value || '';
        const endTime = (qs('#end-time') || {}).value || '';

        const fd = new FormData();
        if (isAddTrainingPage && editProgramId) {
          fd.append('action', 'update_program');
          fd.append('program_id', String(editProgramId));
        } else {
          fd.append('action', 'create_program');
        }
        fd.append('training_title', (qs('#training-title') || {}).value || '');
        fd.append('training_type', (qs('#training-type') || {}).value || '');
        fd.append('training_mode', (qs('#training-mode') || {}).value || 'Onsite');
        fd.append('requested_by', (qs('#requested-by') || {}).value || '');
        fd.append('description', (qs('#description') || {}).value || '');
        fd.append('target_audience', (qs('#target-audience') || {}).value || '');
        fd.append('department_id', (qs('#training-department') || {}).value || '');
        fd.append('sub_department', (qs('#training-sub-department') || {}).value || '');
        fd.append('mentor_id', (qs('#training-mentor') || {}).value || '');
        fd.append('max_participants', (qs('#max-participants') || {}).value || '');
        fd.append('training_level', (qs('#competency-level') || {}).value || '');

        fd.append('training_objectives_json', '[]');
        fd.append('training_objectives_other', '');

        const ta = (qs('#target-audience') || {}).value || '';
        let tr = '';
        tr = (qs('#training-role') || {}).value || '';
        if (ta === 'Specific Employee') tr = (qs('#training-employee') || {}).value || '';
        fd.append('target_role', tr);

        fd.append('category', (qs('#training-category') || {}).value || '');
        fd.append('participants_needed', (qs('#participants-needed') || {}).value || '');

        fd.append('start_datetime', `${startDate} ${startTime}:00`);
        fd.append('end_datetime', `${endDate} ${endTime}:00`);
        fd.append('status', 'Under Review');

        fd.append('need_budget', needBudgetSelect ? needBudgetSelect.value : '0');
        fd.append('need_items', needItemsSelect ? needItemsSelect.value : '0');
        fd.append('need_facility', needFacilitySelect ? needFacilitySelect.value : '0');

        if (needBudgetSelect && needBudgetSelect.value === '1') {
          fd.append('budget_amount', String(requestDraft.budget.total_cost || 0));
          fd.append('financial_details_json', JSON.stringify({
            basic: requestDraft.budget.basic,
            items: requestDraft.budget.items,
            total_cost: requestDraft.budget.total_cost
          }));
        }

        if (needItemsSelect && needItemsSelect.value === '1') {
          fd.append('items_requested', JSON.stringify(requestDraft.logistics.items || []));
          fd.append('logistics_details_json', JSON.stringify({
            basic: requestDraft.logistics.basic,
            delivery: requestDraft.logistics.delivery,
            items: requestDraft.logistics.items,
            remarks: requestDraft.logistics.remarks
          }));
        }

        if (needFacilitySelect && needFacilitySelect.value === '1') {
          fd.append('facility_details', requestDraft.facility.special_requirements || '');
          fd.append('admin_details_json', JSON.stringify({
            basic: requestDraft.facility.basic,
            location: requestDraft.facility.location,
            special_requirements: requestDraft.facility.special_requirements,
            remarks: requestDraft.facility.remarks
          }));
        }

        try {
          const res = await fetch('trainingprogram.php', { method: 'POST', body: fd, credentials: 'same-origin' });
          const data = await res.json();

          if (!data || !data.success) {
            if (window.Swal) await swalFire({ icon: 'error', title: 'Save failed', text: (data && data.message) ? data.message : 'Unable to save training program.' });
            return;
          }

          if (window.Swal) {
            await swalFire({
              icon: 'success',
              title: 'Training Saved!',
              text: 'Training program has been submitted for review. Department requests will be created once it is approved.',
              showConfirmButton: true,
              confirmButtonText: 'OK'
            }, isAddTrainingPage ? undefined : getOpenDialogTarget());
          }

          if (isAddTrainingPage) {
            if (activeDraftId) {
              try {
                await deleteDraftFromDb(activeDraftId);
              } catch (_) {
              }
              deleteDraftById(activeDraftId);
            }
            window.location.href = 'trainingprogram.php';
            return;
          }

          if (trainingModal) trainingModal.close();
          resetForm();
          loadTrainings();
        } catch (_) {
          if (window.Swal) await swalFire({ 
            icon: 'error', 
            title: 'Save failed', 
            text: 'Unexpected error while saving.',
            customClass: {
              confirmButton: 'btn btn-danger'
            },
            buttonsStyling: false
          });
        }
      });
    }

    document.addEventListener('click', (event) => {
      const t = event && event.target;
      const button = (t && typeof t.closest === 'function') ? t.closest('button') : null;
      if (!button) return;
      const action = button.getAttribute('data-action');
      const id = button.getAttribute('data-id');
      if (action && id) {
        event.preventDefault();
        event.stopPropagation();
        if (action === 'view-idp') {
          (async () => {
            try {
              const fd = new FormData();
              fd.append('action', 'get_idp_details');
              fd.append('idp_id', String(id));
              const res = await fetch('trainingrequest.php', { method: 'POST', body: fd, credentials: 'same-origin' });
              const det = await res.json();
              const idp = (det && det.success && det.idp) ? det.idp : null;
              if (!idp) return;
              const sched = (() => {
                const s = String(idp.requested_start_datetime || '');
                const e = String(idp.requested_end_datetime || '');
                if (s && e) return `${formatDateTime(s)} - ${formatDateTime(e)}`;
                if (s) return formatDateTime(s);
                return 'N/A';
              })();
              const html = `
                <div class="text-left space-y-3">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div>
                      <div class="text-xs text-gray-500">Employee</div>
                      <div class="font-semibold text-gray-900">${esc(idp.employee_name || '')}</div>
                      <div class="text-xs opacity-70">${esc(idp.employee_id || '')}</div>
                    </div>
                    <div>
                      <div class="text-xs text-gray-500">Department</div>
                      <div class="text-sm text-gray-900">${esc(idp.department || '')}</div>
                    </div>
                    <div>
                      <div class="text-xs text-gray-500">Position</div>
                      <div class="text-sm text-gray-900">${esc(idp.position || '')}</div>
                    </div>
                    <div>
                      <div class="text-xs text-gray-500">Succession Status</div>
                      <div class="text-sm text-gray-900">${esc(idp.succession_status || '')}</div>
                    </div>
                  </div>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div>
                      <div class="text-xs text-gray-500">Requested Type</div>
                      <div class="text-sm text-gray-900">${esc(idp.requested_training_type || '')}</div>
                    </div>
                    <div>
                      <div class="text-xs text-gray-500">Mode</div>
                      <div class="text-sm text-gray-900">${esc(idp.requested_training_mode || idp.delivery_mode || '')}</div>
                    </div>
                  </div>
                  <div>
                    <div class="text-xs text-gray-500">Requested Schedule</div>
                    <div class="text-sm text-gray-900">${esc(sched)}</div>
                  </div>
                  <div>
                    <div class="text-xs text-gray-500">Development Plan</div>
                    <div class="text-sm text-gray-900 whitespace-pre-line">${esc(idp.development_plan || '')}</div>
                  </div>
                </div>
              `;
              if (window.Swal) {
                await swalFire({
                  title: 'Training Request Details',
                  html
                }, getOpenDialogTarget());
              }
            } catch (_) {
            }
          })();
          return;
        }
        if (action === 'pick-idp') {
          window.location.href = `add_training.php?idp_id=${encodeURIComponent(String(id))}`;
          return;
        }
        if (action === 'view') {
          viewTraining(id);
          return;
        }

        if (action === 'edit') {
          window.location.href = `add_training.php?edit_program_id=${encodeURIComponent(String(id))}`;
          return;
        }

        if (action === 'hold') {
          (async () => {
            const confirmRes = window.Swal ? await swalFire({
              icon: 'warning',
              title: 'Hold this training?',
              text: 'This will set the training status to ON HOLD.',
              showCancelButton: true,
              confirmButtonText: 'Hold',
              cancelButtonText: 'Cancel'
            }, getOpenDialogTarget()) : { isConfirmed: window.confirm('Hold this training?') };
            if (!confirmRes || !confirmRes.isConfirmed) return;

            const fd = new FormData();
            fd.append('action', 'update_program_status');
            fd.append('program_id', String(id));
            fd.append('status', 'ON HOLD');
            fd.append('reason', '');

            try {
              const res = await fetch('trainingprogram.php', { method: 'POST', body: fd, credentials: 'same-origin' });
              const data = await res.json();
              if (!data || !data.success) {
                if (window.Swal) await swalFire({ icon: 'error', title: 'Failed', text: (data && data.message) ? data.message : 'Unable to hold training.' }, getOpenDialogTarget());
                return;
              }
              const idx = trainings.findIndex((p) => String(p.id) === String(id));
              if (idx >= 0) trainings[idx].status = 'ON HOLD';
              renderTrainingCards();
              updateStats();
              if (window.Swal) await swalFire({ icon: 'success', title: 'Held', timer: 1200, showConfirmButton: false }, getOpenDialogTarget());
            } catch (_) {
              if (window.Swal) await swalFire({ icon: 'error', title: 'Failed', text: 'Unexpected error while holding.' }, getOpenDialogTarget());
            }
          })();
          return;
        }

        if (action === 'delete' || action === 'cancel') {
          (async () => {
            const title = action === 'cancel' ? 'Cancel this training?' : 'Delete this training?';
            const text = action === 'cancel' ? 'This will remove the training program.' : 'This will permanently delete the training program.';
            const confirmText = action === 'cancel' ? 'Cancel Training' : 'Delete';

            const confirmRes = window.Swal ? await swalFire({
              icon: 'warning',
              title,
              text,
              showCancelButton: true,
              confirmButtonText: confirmText,
              cancelButtonText: 'Close'
            }, getOpenDialogTarget()) : { isConfirmed: window.confirm(title) };
            if (!confirmRes || !confirmRes.isConfirmed) return;

            const fd = new FormData();
            fd.append('action', 'delete_program');
            fd.append('program_id', String(id));

            try {
              const res = await fetch('trainingprogram.php', { method: 'POST', body: fd, credentials: 'same-origin' });
              const data = await res.json();
              if (!data || !data.success) {
                if (window.Swal) await swalFire({ icon: 'error', title: 'Failed', text: (data && data.message) ? data.message : 'Unable to delete.' }, getOpenDialogTarget());
                return;
              }

              trainings = trainings.filter((p) => String(p.id) !== String(id));
              renderTrainingCards();
              updateStats();
              if (window.Swal) await swalFire({ icon: 'success', title: 'Removed', timer: 1200, showConfirmButton: false }, getOpenDialogTarget());
            } catch (_) {
              if (window.Swal) await swalFire({ icon: 'error', title: 'Failed', text: 'Unexpected error while deleting.' }, getOpenDialogTarget());
            }
          })();
          return;
        }
      }
    });
  };

  const boot = () => {
    if (window.lucide) window.lucide.createIcons();
    setDefaultDates();
    setupEventListeners();

    if (isAddTrainingPage && editProgramId) {
      (async () => {
        try {
          const h1 = document.querySelector('h1');
          if (h1) h1.textContent = 'Edit Training Program';
          const p = document.querySelector('h1 + p');
          if (p) p.textContent = 'Update the training details then submit again for review.';
        } catch (_) {
        }

        try {
          const res = await fetch(`trainingprogram.php?action=get_program&program_id=${encodeURIComponent(String(editProgramId))}`, { credentials: 'same-origin' });
          const data = await res.json();
          if (!data || !data.success || !data.program) return;
          const pr = data.program;

          const parseJson = (v) => {
            try {
              const s = String(v || '');
              if (!s) return null;
              return JSON.parse(s);
            } catch (_) {
              return null;
            }
          };

          const budgetJson = parseJson(pr.financial_details_json);
          const logisticsJson = parseJson(pr.logistics_details_json);
          const adminJson = parseJson(pr.admin_details_json);

          const objectives = (() => {
            const arr = parseJson(pr.training_objectives_json);
            return Array.isArray(arr) ? arr.map(String) : [];
          })();

          const sd = String(pr.start_datetime || '');
          const ed = String(pr.end_datetime || '');
          const startDate = sd ? sd.split(' ')[0] : '';
          const startTime = sd && sd.split(' ').length > 1 ? sd.split(' ')[1].slice(0, 5) : '';
          const endDate = ed ? ed.split(' ')[0] : '';
          const endTime = ed && ed.split(' ').length > 1 ? ed.split(' ')[1].slice(0, 5) : '';

          const payload = {
            form: {
              training_title: String(pr.training_title || ''),
              training_type: String(pr.training_type || ''),
              training_mode: String(pr.training_mode || 'Onsite'),
              requested_by: String(pr.requested_by || ''),
              category: String(pr.category || ''),
              description: String(pr.description || ''),
              target_audience: String(pr.target_audience || ''),
              department_id: String(pr.department_id ?? ''),
              sub_department: String(pr.sub_department || ''),
              target_role: String(pr.target_role || ''),
              employee_id: '',
              mentor_id: String(pr.mentor_id ?? ''),
              participants_needed: String(pr.participants_needed || '1'),
              max_participants: String(pr.max_participants ?? ''),
              training_level: String(pr.training_level || ''),
              objectives,
              objectives_other: String(pr.training_objectives_other || ''),
              start_date: startDate,
              start_time: startTime,
              end_date: endDate,
              end_time: endTime,
              need_budget: String(pr.need_budget ?? '0'),
              need_items: String(pr.need_items ?? '0'),
              need_facility: String(pr.need_facility ?? '0')
            },
            requestDraft: {
              budget: {
                completed: !!(budgetJson && budgetJson.basic),
                basic: (budgetJson && budgetJson.basic) ? budgetJson.basic : { title: '', purpose: '', department: '', event_date: '', justification: '', remarks: '' },
                items: (budgetJson && Array.isArray(budgetJson.items)) ? budgetJson.items : [],
                total_cost: (budgetJson && typeof budgetJson.total_cost !== 'undefined') ? Number(budgetJson.total_cost || 0) : Number(pr.financial_budget_amount || 0)
              },
              logistics: {
                completed: !!(logisticsJson && logisticsJson.basic),
                basic: (logisticsJson && logisticsJson.basic) ? logisticsJson.basic : { title: '', purpose: '', department: '', event_date: '', needed_by_date: '' },
                items: (logisticsJson && Array.isArray(logisticsJson.items)) ? logisticsJson.items : [],
                delivery: (logisticsJson && logisticsJson.delivery) ? logisticsJson.delivery : { location: '', contact_person: '' },
                remarks: (logisticsJson && typeof logisticsJson.remarks !== 'undefined') ? String(logisticsJson.remarks || '') : ''
              },
              facility: {
                completed: !!(adminJson && adminJson.basic),
                basic: (adminJson && adminJson.basic) ? adminJson.basic : { title: '', purpose: '', department: '', event_date: '' },
                location: (adminJson && adminJson.location) ? adminJson.location : { preferred_location: '', start_time: '', end_time: '' },
                special_requirements: (adminJson && typeof adminJson.special_requirements !== 'undefined') ? String(adminJson.special_requirements || '') : String(pr.admin_facility_details || ''),
                remarks: (adminJson && typeof adminJson.remarks !== 'undefined') ? String(adminJson.remarks || '') : ''
              }
            }
          };

          applyDraftToUI(payload);
          if (window.Swal) await swalFire({ icon: 'info', title: 'Edit mode', text: 'Training details loaded.', timer: 1200, showConfirmButton: false });
        } catch (_) {
        }
      })();
    }

    if (isAddTrainingPage && !editProgramId && idpRequestId) {
      (async () => {
        try {
          const fd = new FormData();
          fd.append('action', 'get_idp_details');
          fd.append('idp_id', String(idpRequestId));
          const res = await fetch('trainingrequest.php', { method: 'POST', body: fd, credentials: 'same-origin' });
          const data = await res.json();
          if (!data || !data.success || !data.idp) return;
          const idp = data.idp;

          const deptName = String(idp.department || '').trim();
          const deptMap = {
            'Front Office / Reception': '1',
            'Housekeeping': '2',
            'Food & Beverage (F&B)': '3',
            'Food & Beverage (F&amp;B)': '3',
            'Kitchen / Culinary': '4',
            'Sales & Marketing': '5',
            'Human Resources (HR)': '6',
            'Finance / Accounting': '7',
            'Engineering / Maintenance': '8',
            'Security': '9'
          };
          const deptSelectEl = qs('#training-department');
          let deptId = deptMap[deptName] || '';
          if (!deptId) {
            const direct = String(idp.department || '').trim();
            if (direct && /^[0-9]+$/.test(direct)) deptId = direct;
          }
          if (!deptId && deptSelectEl) {
            const opt = Array.from(deptSelectEl.options || []).find((o) => String((o && o.textContent) || '').trim() === deptName);
            if (opt) deptId = String(opt.value || '');
          }

          const splitDT = (dt) => {
            const s0 = String(dt || '').trim();
            const s = s0.replace('T', ' ').replace(/\.[0-9]+$/, '');
            if (!s) return { d: '', t: '' };
            const parts = s.split(' ');
            return {
              d: parts[0] || '',
              t: (parts[1] || '').slice(0, 5)
            };
          };
          const sd = splitDT(idp.requested_start_datetime);
          const ed = splitDT(idp.requested_end_datetime);

          const planItems = (() => {
            const raw = String(idp.development_plan || '');
            return raw
              .split(/\r?\n/)
              .map((l) => String(l || '').trim())
              .filter((l) => l.indexOf('- ') === 0)
              .map((l) => l.slice(2).trim())
              .filter((l) => l !== '');
          })();

          const trainingType = String(idp.requested_training_type || '').trim() || 'Training';
          const mode = String(idp.requested_training_mode || '').trim() || String(idp.delivery_mode || '').trim() || 'Onsite';
          const employeeName = String(idp.employee_name || '').trim();
          const title = employeeName ? `IDP Training - ${employeeName}` : 'IDP Training';
          const targetRole = String(idp.position || '').trim();

          const competencyMap = {
            'Retrain': 'Retraining',
            'Retraining': 'Retraining',
            'Refresher Training': 'Retraining',
            'Reskilling': 'Reskilling',
            'Upskilling': 'Upskilling',
            'Succession Ready': 'Succession Ready'
          };
          const rawStatus = String(idp.succession_status || '').trim();
          const competencyLevel = competencyMap[rawStatus] || rawStatus;

          const payload = {
            form: {
              training_title: title,
              training_type: trainingType,
              training_mode: mode,
              requested_by: 'IDP',
              category: 'IDP',
              description: String(idp.development_plan || '').trim() || 'IDP Training Request',
              target_audience: 'By Department',
              department_id: deptId,
              sub_department: '',
              target_role: targetRole,
              employee_id: '',
              mentor_id: '',
              participants_needed: '1',
              max_participants: '',
              training_level: competencyLevel,
              objectives: [],
              objectives_other: '',
              start_date: sd.d,
              start_time: sd.t,
              end_date: ed.d,
              end_time: ed.t,
              need_budget: '0',
              need_items: '0',
              need_facility: '0',
              development_plan_items: planItems
            },
            requestDraft: requestDraft
          };

          applyDraftToUI(payload);

          if (typeof window.handleTargetAudienceChange === 'function') {
            try { window.handleTargetAudienceChange(); } catch (_) {}
          }

          if (deptSelectEl && deptId) {
            deptSelectEl.value = String(deptId);
            try { fillRolesByDepartment(false); } catch (_) {}
          }

          if (sd.d) {
            const el = qs('#start-date');
            if (el) el.value = sd.d;
          }
          if (sd.t) {
            const el = qs('#start-time');
            if (el) el.value = sd.t;
          }
          if (ed.d) {
            const el = qs('#end-date');
            if (el) el.value = ed.d;
          }
          if (ed.t) {
            const el = qs('#end-time');
            if (el) el.value = ed.t;
          }

          const roleSelect = qs('#training-role');
          if (roleSelect && targetRole) {
            const exists = Array.from(roleSelect.options || []).some((o) => String(o.value || '') === targetRole);
            if (!exists) {
              const opt = document.createElement('option');
              opt.value = targetRole;
              opt.textContent = targetRole;
              roleSelect.appendChild(opt);
            }
            roleSelect.value = targetRole;
          }

          const compSel = qs('#competency-level');
          if (compSel && competencyLevel) {
            const has = Array.from(compSel.options || []).some((o) => String(o.value || '') === String(competencyLevel));
            if (has) compSel.value = String(competencyLevel);
          }

          const cat = qs('#training-category');
          if (cat) cat.value = 'IDP';

          const formEl = document.getElementById('training-form');
          if (formEl) {
            const controls = formEl.querySelectorAll('input, select, textarea');
            const editableIds = {
              'start-date': true,
              'start-time': true,
              'end-date': true,
              'end-time': true,
              'need-budget': true,
              'need-items': true,
              'need-facility': true
            };
            controls.forEach((el) => {
              const id = el.id || '';
              if (!editableIds[id]) {
                el.disabled = true;
              }
            });
          }

          if (window.Swal) await swalFire({ icon: 'info', title: 'Loaded from IDP', text: 'Training request details have been prefilled.', timer: 1400, showConfirmButton: false });
        } catch (_) {
        }
      })();
    }

    if (qs('#training-cards')) {
      const openId = getUrlParam('open_program_id');
      if (openId) pendingOpenProgramId = openId;
      loadTrainings();
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  window.__TRAINING__ = window.__TRAINING__ || {};
  window.__TRAINING__.swalFire = swalFire;
  window.__TRAINING__.getUrlParam = getUrlParam;
  window.__TRAINING__.migrateLocalDraftsOnce = migrateLocalDraftsOnce;
  window.__TRAINING__.getDraftFromDb = getDraftFromDb;
  window.__TRAINING__.loadDrafts = loadDrafts;
  window.__TRAINING__.applyDraftToUI = applyDraftToUI;
  window.__TRAINING__.setActiveDraftId = (id) => { activeDraftId = id; };
  window.__TRAINING__.handleAddTrainingBack = handleAddTrainingBack;
  window.__TRAINING__.handleAddTrainingCancel = handleAddTrainingCancel;

  window.handleTargetAudienceChange = handleTargetAudienceChange;
  window.filterByType = filterByType;
  window.filterByStatus = filterByStatus;
  window.viewTraining = viewTraining;

})();
