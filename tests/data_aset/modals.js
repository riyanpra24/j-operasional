const fs = require('fs');
const vm = require('vm');
const path = require('path');
let script = fs.readFileSync(path.join(__dirname, '../../app/Views/data_aset/modals.php'), 'utf8').match(/<script>\s*([\s\S]*?)\s*<\/script>/)[1];
script = script.replace(/<\?= json_encode\(\$popupRecords, \$jsonFlags\) \?>/, '{"7":{"id":7,"kategori":"Gedung","nama_aset":"Kantor","masa_manfaat":360,"sisa_masa_manfaat":0}}')
    .replace(/<\?= json_encode\(\$popupState, \$jsonFlags\) \?>/, 'null')
    .replace(/<\?= json_encode\(\$baseUrl, \$jsonFlags\) \?>/, '"/data-aset"')
    .replace(/<\?= esc\(csrf_token\(\), 'js'\) \?>/, 'csrf_test');
const makeModal = () => ({ hidden: true, classList: { add() {}, remove() {} }, setAttribute() {}, querySelector: () => ({ focus() {} }), querySelectorAll: () => [] });
const formModal = makeModal(), detailModal = makeModal(), importModal = makeModal();
const fields = Object.fromEntries(['kategori', 'nama_aset', 'masa_manfaat', 'sisa_masa_manfaat'].map((name) => [name, { value: '' }]));
const form = { action: '', reset() { Object.values(fields).forEach((field) => { field.value = ''; }); }, elements: { namedItem: (name) => fields[name] } };
const makeTrigger = (dataset) => ({ dataset, addEventListener(_, fn) { this.click = () => fn({ currentTarget: this, preventDefault() {} }); }, closest: () => ({ removeAttribute() {}, querySelector: () => null }), focus() {} });
const create = makeTrigger({}), importTrigger = makeTrigger({}), edit = makeTrigger({ assetEdit: '7' }), view = makeTrigger({ assetView: '7' });
const detail = { dataset: { assetDetail: 'nama_aset' }, textContent: '', removeAttribute() {} };
detailModal.querySelectorAll = () => [detail];
const title = { textContent: '' }, errors = { hidden: true, replaceChildren() {}, append() {} }, deleteForm = { action: '' };
const detailEdit = makeTrigger({});
const map = { assetFormModal: formModal, assetDetailModal: detailModal, assetImportModal: importModal, assetModalForm: form, assetFormErrors: errors, assetFormTitle: title, assetDeleteForm: deleteForm, assetDetailEdit: detailEdit };
const document = { getElementById: (id) => map[id], querySelector: (selector) => selector === '[data-asset-create]' ? create : selector === '[data-asset-import]' ? importTrigger : null, querySelectorAll: (selector) => selector === '[data-asset-edit]' ? [edit] : selector === '[data-asset-view]' ? [view] : [] };
vm.runInNewContext(script, { document, window: {}, requestAnimationFrame: (callback) => callback(), encodeURIComponent });
create.click();
if (formModal.hidden || form.action !== '/data-aset' || title.textContent !== 'Tambah Aset') throw new Error('Create popup failed.');
importTrigger.click();
if (importModal.hidden) throw new Error('Import popup failed.');
edit.click();
if (form.action !== '/data-aset/7' || fields.nama_aset.value !== 'Kantor' || fields.sisa_masa_manfaat.value !== 0) throw new Error('Edit popup failed to retain values.');
view.click();
if (detailModal.hidden || detail.textContent !== 'Kantor' || deleteForm.action !== '/data-aset/7/hapus') throw new Error('Detail popup failed.');
detailEdit.click();
if (!detailModal.hidden || formModal.hidden || form.action !== '/data-aset/7') throw new Error('Detail-to-edit transition failed.');
const restoredScript = script.replace('const popupState = null;', 'const popupState = {id:7,values:{nama_aset:"Diperbaiki",sisa_masa_manfaat:0},errors:["Nomor aset sudah digunakan."]};');
document.createElement = () => ({ textContent: '', append() {} });
vm.runInNewContext(restoredScript, { document, window: {}, requestAnimationFrame: (callback) => callback(), encodeURIComponent });
if (formModal.hidden || form.action !== '/data-aset/7' || fields.nama_aset.value !== 'Diperbaiki' || errors.hidden) throw new Error('Validation state was not restored in the popup.');
console.log('Data Aset popup interaction: passed');
