// Verify the exact manual-calculation handler against server-generated cases.
const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const schema = JSON.parse(fs.readFileSync('app/Config/RkaTemplate2026.json', 'utf8'));
const cases = JSON.parse(fs.readFileSync('tmp/rka_template/verified_cases.json', 'utf8'));
const source = fs.readFileSync('public/assets/app.js', 'utf8');
const snippet = source.slice(source.indexOf('// Manual RKA:'), source.indexOf('// Tutup panel urutan'));
const localized = (amount) => {
  if (amount === '0.00') return '';
  const [whole, fraction] = amount.split('.');
  return whole.replace(/\B(?=(\d{3})+(?!\d))/g, '.') + (fraction === '00' ? '' : ',' + fraction);
};
const reported = amount => amount.startsWith('-') ? `*${localized(amount).slice(1)}` : (localized(amount) || '-');
for (const test of cases) {
  const events = {};
  const fields = schema.input_rows.flatMap(row => ['C','D','E','F','G'].map(column => ({
    dataset: {rkaInput:`${column}${row}`}, value:localized(test.inputs[`${column}${row}`] || '0.00'),
    validity:'', setCustomValidity(value){this.validity=value;},
    addEventListener(event,handler){events[`${this.dataset.rkaInput}:${event}`]=handler;},
    select(){},
  })));
  const outputs = Object.keys(test.expected).map(cell => ({dataset:{rkaOutput:cell},textContent:'',
    set innerHTML(value){this.html=value;this.textContent=value.replace(/<[^>]*>/g,'');},
  }));
  const status = {textContent:'',classList:{toggle(){}}};
  const form = {
    querySelectorAll(selector){return selector.includes('rka-input')?fields:outputs;},
    querySelector(selector){return selector.includes('manual-status')?status:null;},
    addEventListener(event,handler){events[event]=handler;}, reportValidity(){},
  };
  vm.runInNewContext(snippet,{document:{querySelector(selector){return selector.includes('manual-form')?form:{textContent:JSON.stringify(schema)};}}});
  outputs.forEach(output=>{
    assert.strictEqual(output.textContent,reported(test.expected[output.dataset.rkaOutput]),output.dataset.rkaOutput);
    assert.strictEqual(output.dataset.rkaEmpty,test.expected[output.dataset.rkaOutput] === '0.00' ? 'true' : 'false');
    if (test.expected[output.dataset.rkaOutput].startsWith('-')) assert(output.html.startsWith('<span class="lr-rka-negative-marker">*</span>'));
  });
  fields[0].value='0,001'; events[`${fields[0].dataset.rkaInput}:input`]();
  assert(fields[0].validity); assert(outputs.every(output=>output.textContent==='—'));
  let prevented=false; events.submit({preventDefault(){prevented=true;}}); assert(prevented);
}
console.log(`Manual RKA: ${cases.length} exact server/browser cases reconciled; invalid precision blocked.`);

// Exercise the actual shared group handler: only marked RKA summary amounts hide.
const groupSnippet = source.slice(source.indexOf('// Laba & Rugi:'), source.indexOf('// Oracle LR:'));
const makeGroup = (key, marker) => {
  const attributes = {'aria-expanded':'false'};
  const toggle = {dataset:{lrToggle:key},getAttribute(name){return attributes[name];},setAttribute(name,value){attributes[name]=value;}};
  const amounts = Array.from({length:marker?6:0},()=>({dataset:{},attributes:{},hasAttribute(name){return name===`data-${marker}-group-output`;},setAttribute(name,value){this.attributes[name]=value;},textContent:'1.000'}));
  const details = [{dataset:{lrDetail:key},hidden:true},{dataset:{lrDetail:'unrelated'},hidden:true}];
  const row = {querySelector(){return toggle;},querySelectorAll(){return amounts;},closest(){return {querySelectorAll(){return details;}};},addEventListener(event,handler){this.click=handler;}};
  return {row,toggle,amounts,details};
};
const rkaGroup = makeGroup('claims','rka'), lrGroup = makeGroup('employee','lr'), plainGroup = makeGroup('other',null);
vm.runInNewContext(groupSnippet,{document:{querySelectorAll(){return [rkaGroup.row,lrGroup.row,plainGroup.row];}}});
rkaGroup.row.click();
assert.strictEqual(rkaGroup.toggle.getAttribute('aria-expanded'),'true');
assert(rkaGroup.amounts.every(amount=>amount.dataset.rkaExpanded==='true' && amount.attributes['aria-hidden']==='true' && amount.textContent==='1.000'));
assert.strictEqual(rkaGroup.details[0].hidden,false);
assert.strictEqual(rkaGroup.details[1].hidden,true);
rkaGroup.row.click();
assert(rkaGroup.amounts.every(amount=>amount.dataset.rkaExpanded==='false' && amount.attributes['aria-hidden']==='false'));
assert.strictEqual(rkaGroup.details[0].hidden,true);
lrGroup.row.click();
assert(lrGroup.amounts.every(amount=>amount.dataset.lrExpanded==='true' && amount.attributes['aria-hidden']==='true'));
assert.strictEqual(lrGroup.details[0].hidden,false);
plainGroup.row.click(); assert.strictEqual(plainGroup.details[0].hidden,false);
rkaGroup.row.click(); rkaGroup.row.click(); rkaGroup.row.click();
assert(rkaGroup.amounts.every(amount=>amount.dataset.rkaExpanded==='true'));
console.log('RKA/LR groups: expanded summary amounts hide and restore; unrelated groups stay unchanged.');
