(() => {
  const b = document.body;
  if (!b || b.getAttribute('data-page') !== 'add-training') return;

  const qs = (sel) => document.querySelector(sel);

  const bootAddTraining = () => {
    const api = window.__TRAINING__ || {};

    const backBtn = qs('#add-training-back');
    if (backBtn && typeof api.handleAddTrainingBack === 'function') {
      backBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        const href = backBtn.getAttribute('href') || 'trainingprogram.php';
        await api.handleAddTrainingBack(href);
      });
    }

    const cancelBtn = qs('#add-training-cancel');
    if (cancelBtn && typeof api.handleAddTrainingCancel === 'function') {
      cancelBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        const href = cancelBtn.getAttribute('href') || 'trainingprogram.php';
        await api.handleAddTrainingCancel(href);
      });
    }

    (async () => {
      if (typeof api.migrateLocalDraftsOnce === 'function') {
        await api.migrateLocalDraftsOnce();
      }

      if (typeof api.getUrlParam !== 'function') return;
      const draftId = api.getUrlParam('draft_id');
      if (!draftId) return;

      const swalFire = typeof api.swalFire === 'function' ? api.swalFire : null;

      try {
        if (typeof api.getDraftFromDb === 'function') {
          const found = await api.getDraftFromDb(draftId);
          if (found && found.data && typeof api.applyDraftToUI === 'function') {
            if (typeof api.setActiveDraftId === 'function') api.setActiveDraftId(String(found.id));
            api.applyDraftToUI(found.data);
            if (swalFire) swalFire({ icon: 'info', title: 'Draft loaded', text: 'You can continue your saved draft now.', timer: 1200, showConfirmButton: false });
            return;
          }
        }
      } catch (_) {
      }

      try {
        if (typeof api.loadDrafts !== 'function') return;
        const drafts = api.loadDrafts();
        const fallback = (Array.isArray(drafts) ? drafts : []).find((d) => String(d && d.id) === String(draftId));
        if (fallback && fallback.data && typeof api.applyDraftToUI === 'function') {
          if (typeof api.setActiveDraftId === 'function') api.setActiveDraftId(String(fallback.id));
          api.applyDraftToUI(fallback.data);
          if (swalFire) swalFire({ icon: 'info', title: 'Draft loaded', text: 'You can continue your saved draft now.', timer: 1200, showConfirmButton: false });
        } else {
          if (swalFire) swalFire({ icon: 'error', title: 'Draft not found', text: 'This draft may have been deleted.' });
        }
      } catch (_) {
      }
    })();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootAddTraining);
  } else {
    bootAddTraining();
  }
})();
