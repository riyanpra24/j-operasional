(() => {
    const start = () => {
        const unitDialog = document.getElementById('lrVolumeUnitDialog');
        const editDialog = document.getElementById('lrVolumeEditDialog');
        if (!unitDialog || !editDialog) return;

        const show = (dialog) => {
            if (!dialog || dialog.open) return;
            if (typeof dialog.showModal === 'function') {
                dialog.showModal();
                return;
            }
            dialog.setAttribute('open', '');
        };
        const close = (dialog) => {
            if (!dialog) return;
            if (typeof dialog.close === 'function') dialog.close();
            else dialog.removeAttribute('open');
        };

        document.querySelectorAll('[data-lr-volume-edit-open]').forEach((button) => {
            button.addEventListener('click', () => show(unitDialog));
        });
        unitDialog.querySelectorAll('[data-lr-volume-unit-close]').forEach((button) => {
            button.addEventListener('click', () => close(unitDialog));
        });
        editDialog.querySelectorAll('[data-lr-volume-edit-close]').forEach((button) => {
            button.addEventListener('click', () => close(editDialog));
        });
        unitDialog.querySelector('[data-lr-volume-unit-continue]')?.addEventListener('click', () => {
            const month = unitDialog.querySelector('[data-lr-edit-period-month]');
            const year = unitDialog.querySelector('[data-lr-edit-period-year]');
            if (!(month instanceof HTMLSelectElement) || !(year instanceof HTMLInputElement) || !year.reportValidity()) return;
            const url = new URL(window.location.href);
            url.searchParams.set('bulan', month.value);
            url.searchParams.set('tahun', year.value);
            url.searchParams.set('edit', '1');
            window.location.assign(url.toString());
        });
        if (editDialog.dataset.autoOpen === 'true') show(editDialog);
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
