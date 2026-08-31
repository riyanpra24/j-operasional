<script>
(() => {
    const root = document.querySelector('[data-vehicle-crud-root]');
    if (!root) return;
    const formModal = document.getElementById('vehicleCrudFormModal');
    const deleteModal = document.getElementById('vehicleCrudDeleteModal');
    const form = formModal.querySelector('[data-vehicle-crud-form]');
    const editDeleteButton = form.querySelector('[data-crud-edit-delete]');
    const baseUrl = root.dataset.baseUrl;
    const openModal = modal => {
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => modal.classList.add('open'));
        document.body.classList.add('modal-open');
    };
    const closeModal = modal => {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        setTimeout(() => {
            modal.hidden = true;
            if (!document.querySelector('.vehicle-crud-modal.open')) document.body.classList.remove('modal-open');
        }, 180);
    };
    const fillForm = data => Object.entries(data || {}).forEach(([key, value]) => {
        if (form.elements[key]) form.elements[key].value = value ?? '';
    });
    const announcePrepared = (mode, data) => window.dispatchEvent(new CustomEvent('vehicle-crud:prepared', {detail: {mode, data, form}}));
    const prepareCreate = (data = {}) => {
        form.reset();
        form.action = baseUrl;
        formModal.querySelector('[data-crud-form-title]').textContent = root.dataset.createTitle;
        formModal.querySelector('[data-crud-form-icon]').textContent = '＋';
        formModal.querySelector('[data-crud-submit]').textContent = 'Simpan data';
        if (editDeleteButton) {
            editDeleteButton.hidden = true;
            editDeleteButton.dataset.vehicleCrudDelete = '';
        }
        fillForm(data);
        announcePrepared('create', data);
        openModal(formModal);
    };
    const prepareEdit = data => {
        form.reset();
        form.action = `${baseUrl}/${data.id}`;
        formModal.querySelector('[data-crud-form-title]').textContent = root.dataset.editTitle;
        formModal.querySelector('[data-crud-form-icon]').textContent = '✎';
        formModal.querySelector('[data-crud-submit]').textContent = 'Simpan perubahan';
        if (editDeleteButton) {
            const vehicleName = data.nama_kendaraan === 'Lainnya' && data.nama_kendaraan_lainnya
                ? data.nama_kendaraan_lainnya
                : data.nama_kendaraan;
            editDeleteButton.dataset.vehicleCrudDelete = JSON.stringify({
                id: Number(data.id),
                label: data.delete_label || `${data.nomor_polisi || ''} · ${vehicleName || 'Kendaraan'}`,
            });
            editDeleteButton.hidden = false;
        }
        fillForm(data);
        announcePrepared('edit', data);
        openModal(formModal);
    };
    document.querySelector('[data-vehicle-crud-create]')?.addEventListener('click', () => prepareCreate());
    document.querySelectorAll('[data-vehicle-crud-edit]').forEach(button => button.addEventListener('click', () => prepareEdit(JSON.parse(button.dataset.vehicleCrudEdit))));
    document.querySelectorAll('[data-vehicle-crud-delete]').forEach(button => button.addEventListener('click', () => {
        const data = JSON.parse(button.dataset.vehicleCrudDelete);
        deleteModal.querySelector('[data-crud-delete-name]').textContent = data.label;
        deleteModal.querySelector('[data-crud-delete-form]').action = `${baseUrl}/${data.id}/hapus`;
        openModal(deleteModal);
    }));
    document.querySelectorAll('[data-vehicle-crud-form-close]').forEach(button => button.addEventListener('click', () => closeModal(formModal)));
    document.querySelectorAll('[data-vehicle-crud-delete-close]').forEach(button => button.addEventListener('click', () => closeModal(deleteModal)));
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        [formModal, deleteModal].forEach(modal => { if (!modal.hidden) closeModal(modal); });
    });
    const failedMode = <?= json_encode(session()->getFlashdata('vehicle_crud_modal')) ?>;
    const failedData = <?= json_encode(session()->getFlashdata('vehicle_crud_data') ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const failedId = <?= json_encode(session()->getFlashdata('vehicle_crud_edit_id')) ?>;
    if (failedMode === 'create') prepareCreate(failedData);
    if (failedMode === 'edit' && failedId) prepareEdit({...failedData, id: failedId});
})();
</script>
