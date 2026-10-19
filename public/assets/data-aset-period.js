(() => {
    const script = document.currentScript;
    const systemTime = Number(script?.dataset.systemTime);
    const startedAt = Date.now();
    const today = () => {
        const now = Number.isFinite(systemTime) && systemTime > 0 ? systemTime + (Date.now() - startedAt) : Date.now();
        const parts = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(new Date(now));
        const get = (type) => parts.find((part) => part.type === type)?.value;
        return `${get('year')}-${get('month')}-${get('day')}`;
    };
    const months = (start, end) => {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(start || '') || !/^\d{4}-\d{2}-\d{2}$/.test(end || '') || end < start) return null;
        const [sy, sm, sd] = start.split('-').map(Number);
        const [ey, em, ed] = end.split('-').map(Number);
        return Math.max(0, (ey - sy) * 12 + em - sm - (ed < sd ? 1 : 0));
    };
    const remaining = (end) => !end ? null : end <= today() ? 0 : months(today(), end);
    const update = (form) => {
        if (!form) return;
        const start = form.elements.namedItem('tanggal_perolehan');
        const end = form.elements.namedItem('tanggal_habis_manfaat');
        const total = form.elements.namedItem('masa_manfaat');
        const left = form.elements.namedItem('sisa_masa_manfaat');
        if (!start || !end || !total || !left) return;
        const invalid = start.value && end.value && end.value < start.value;
        end.setCustomValidity(invalid ? 'Tanggal habis masa manfaat tidak boleh sebelum tanggal perolehan.' : '');
        total.value = months(start.value, end.value) ?? '';
        left.value = remaining(end.value) ?? '';
    };
    const refresh = () => {
        document.querySelectorAll('[data-asset-period-form]').forEach(update);
        document.querySelectorAll('[data-asset-remaining]').forEach((element) => {
            const value = remaining(element.dataset.assetRemaining);
            element.textContent = value === null ? '—' : element.dataset.assetRemainingFormat === 'number' ? String(value) : `Sisa ${value} bulan`;
        });
    };
    document.querySelectorAll('[data-asset-period-form]').forEach((form) => {
        ['tanggal_perolehan', 'tanggal_habis_manfaat'].forEach((name) => form.elements.namedItem(name)?.addEventListener('input', () => update(form)));
    });
    window.AssetPeriod = { update, remaining, months, refresh };
    refresh();
    window.setInterval(refresh, 60000);
})();
