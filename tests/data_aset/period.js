const fs = require('fs');
const vm = require('vm');
const path = require('path');
const code = fs.readFileSync(path.join(__dirname, '../../public/assets/data-aset-period.js'), 'utf8');
const makeField = (value = '') => ({ value, addEventListener(_, callback) { this.change = callback; }, setCustomValidity(message) { this.error = message; } });
const fields = {
    tanggal_perolehan: makeField('2026-01-20'), tanggal_habis_manfaat: makeField('2027-01-20'),
    masa_manfaat: makeField(), sisa_masa_manfaat: makeField(),
};
const form = { elements: { namedItem: (name) => fields[name] } };
const badge = { dataset: { assetRemaining: '2027-01-20' }, textContent: '' };
const document = { currentScript: { dataset: { systemTime: String(Date.parse('2026-10-19T05:00:00Z')) } }, querySelectorAll: (selector) => selector === '[data-asset-period-form]' ? [form] : [badge] };
const window = { setInterval() {} };
vm.runInNewContext(code, { document, window, Intl, Date });
if (fields.masa_manfaat.value !== 12 || fields.sisa_masa_manfaat.value !== 3 || badge.textContent !== 'Sisa 3 bulan') throw new Error('Period calculation failed');
fields.tanggal_habis_manfaat.value = '2026-01-19'; fields.tanggal_habis_manfaat.change();
if (fields.masa_manfaat.value !== '' || !fields.tanggal_habis_manfaat.error || fields.sisa_masa_manfaat.value !== 0) throw new Error('Invalid dates not handled');
if (window.AssetPeriod.months('2026-01-31', '2026-02-28') !== 0 || window.AssetPeriod.remaining('2026-10-18') !== 0) throw new Error('Date boundary calculation failed');
console.log('Data Aset period interaction: passed');
