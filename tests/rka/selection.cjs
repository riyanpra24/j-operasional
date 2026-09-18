// Exercise the exact manual-popup loader without touching user data.
const fs = require('fs'), vm = require('vm'), assert = require('assert');
const schema = JSON.parse(fs.readFileSync('app/Config/RkaTemplate2026.json','utf8'));
const source = fs.readFileSync('public/assets/app.js','utf8');
const snippet = source.slice(source.indexOf('// Manual RKA:'),source.indexOf('// Tutup panel urutan'));
const element = (value='') => ({value,disabled:false,checked:false,textContent:'',events:{},
  addEventListener(name,handler){this.events[name]=handler;},reportValidity(){return true;},
  setCustomValidity(message){this.validity=message;},setAttribute(){},select(){}});
const fields = schema.input_rows.flatMap(row=>['C','D','E','F','G'].map(column=>Object.assign(element(),{dataset:{rkaInput:`${column}${row}`}})));
const outputs = ['H14','H166'].map(cell=>({dataset:{rkaOutput:cell},textContent:''}));
const unit=element('Surabaya'),year=element('2026'),load=element(),unitField=element('Surabaya'),yearField=element('2026'),revision=element('0'),confirmation=element(),save=element(),status=element(),selectionStatus=element();
status.classList={toggle(){}};
const metadata = Object.fromEntries(['#rka-manual-report-title','.lr-report-header p','.lr-report-year','.lr-report-caption','.lr-report-scroll','[data-rka-manual-confirm-text]'].map(key=>[key,element()]));
const selectionStage=element(),inputStage=element(),existingAlert=element(),reset=element(),back=element(),saveConfirm=element(),menuBack=element(),existingMessage=element();
inputStage.hidden=true;existingAlert.hidden=true;
const popup={dataset:{rkaStage:'selection'},querySelector(){return element();}};
popup.open=false;popup.showModal=function(){this.open=true;};
const editTrigger=element();editTrigger.dataset={unit:'Kanwil',year:'2027',id:'123'};
const editExisting=element();
const mapping = {'[data-rka-manual-unit]':unit,'[data-rka-manual-year]':year,'[data-rka-manual-load]':load,
  '[name="unit_kerja"]':unitField,'[name="tahun"]':yearField,'[name="revision"]':revision,'[name="confirm_replace"]':confirmation,
  'button[type="submit"]':save,'[data-rka-manual-status]':status,'[data-rka-selection-status]':selectionStatus,
  '[data-rka-selection-stage]':selectionStage,'[data-rka-input-stage]':inputStage,'[data-rka-existing-alert]':existingAlert,
  '[data-rka-manual-reset]':reset,'[data-rka-step-back]':back,'[data-rka-save-confirm]':saveConfirm,'[data-rka-manual-back]':menuBack,
  '[data-rka-existing-message]':existingMessage,'[data-rka-manual-mode]':element(),'[data-rka-manual-edit-id]':element(),'[data-rka-edit-existing]':editExisting,...metadata};
const form={events:{},dataset:{rkaDataUrl:'/manual-data',hasUnsaved:'false',rkaStep:'selection'},
  querySelector(key){return mapping[key]||null;},querySelectorAll(key){return key==='[data-rka-input]'?fields:key==='[data-rka-output]'?outputs:[];},
  addEventListener(name,handler){this.events[name]=handler;},reportValidity(){},closest(){return popup;}};
let confirmationAllowed=true, requests=0, nextResponse;
vm.runInNewContext(snippet,{URL,window:{location:{href:'http://localhost/rka'},confirm(){return confirmationAllowed;}},
  fetch:async()=>{requests++;return nextResponse;},document:{querySelectorAll(){return [editTrigger];},querySelector(key){return key==='[data-rka-manual-form]'?form:{textContent:JSON.stringify(schema)};}}});
(async()=>{
  assert(save.disabled && inputStage.hidden,'Input stage must not be accessible before Next');
  unit.value='Kanwil';year.value='2027';unit.events.change();
  assert(save.disabled && fields.every(field=>field.disabled),'Changing selection must block editing/saving old data under a new unit');
  let blocked=false;form.events.submit({preventDefault(){blocked=true;}});assert(blocked);
  const inputs=Object.fromEntries(fields.map(field=>[field.dataset.rkaInput,'0.00']));inputs.C11='100.10';
  nextResponse={ok:true,json:async()=>({unit:'Kanwil',year:2027,revision:2,budget_id:123,has_record:true,inputs})};
  await load.events.click();
  assert(!existingAlert.hidden && inputStage.hidden && save.disabled,'Existing RKA must show warning before resetting');
  assert.strictEqual(unitField.value,'Surabaya');assert.strictEqual(revision.value,'0','Checking existing RKA is read-only');
  reset.events.click();
  assert.strictEqual(unitField.value,'Kanwil');assert.strictEqual(yearField.value,'2027');assert.strictEqual(revision.value,'2');
  assert(fields.every(field=>field.value===''),'Setting again must start with all nominal inputs blank');
  assert.strictEqual(outputs[0].textContent,'-');assert(selectionStage.hidden && !inputStage.hidden && existingAlert.hidden);
  assert(!save.disabled && fields.every(field=>!field.disabled));assert.strictEqual(confirmation.checked,false);
  const edited=fields.find(field=>field.dataset.rkaInput==='C11');edited.value='100,10';edited.events.input();
  back.events.click();assert(!selectionStage.hidden && inputStage.hidden && save.disabled);
  unit.value='Kediri';unit.events.change();
  nextResponse={ok:true,json:async()=>({unit:'Kediri',year:2027,revision:0,has_record:false,inputs:{C11:'999.00'}})};
  await load.events.click();
  assert.strictEqual(unitField.value,'Kanwil');assert.strictEqual(fields.find(field=>field.dataset.rkaInput==='C11').value,'100,10');
  assert(save.disabled,'Malformed/new-unit response must not permit saving the previous inputs');
  assert(selectionStatus.textContent.includes('Gagal memuat'));
  unit.value='Kanwil';unit.events.change();assert(save.disabled,'Returning to the same choice still requires Next');
  fields[0].events.input();confirmationAllowed=false;const before=requests;
  await load.events.click();assert.strictEqual(requests,before,'Unsaved edits must not be discarded without confirmation');
  confirmationAllowed=true;
  unit.value='Malang';unit.events.change();
  nextResponse={ok:true,json:async()=>({unit:'Malang',year:2027,revision:0,has_record:false,inputs})};
  await load.events.click();
  assert(!inputStage.hidden && selectionStage.hidden && fields.every(field=>field.value===''));
  assert.strictEqual(unitField.value,'Malang');assert.strictEqual(revision.value,'0');assert(!save.disabled);
  const editInputs={...inputs,G11:'3210.05',D18:'-1.25'};
  for (const sourceType of ['manual','excel']) {
    nextResponse={ok:true,json:async()=>({unit:'Kanwil',year:2027,revision:2,budget_id:123,has_record:true,source_type:sourceType,inputs:editInputs})};
    await editTrigger.events.click();
    assert(!inputStage.hidden && popup.open && !save.disabled);
    assert.strictEqual(mapping['[data-rka-manual-mode]'].value,'edit');assert.strictEqual(mapping['[data-rka-manual-edit-id]'].value,'123');
    assert.strictEqual(fields.find(field=>field.dataset.rkaInput==='C11').value,'100,10');
    assert.strictEqual(fields.find(field=>field.dataset.rkaInput==='G11').value,'3.210,05');
    assert.strictEqual(fields.find(field=>field.dataset.rkaInput==='D18').value,'-1,25');
    assert.strictEqual(outputs[0].textContent,'3.310,15');assert.strictEqual(outputs[1].textContent,'3.311,40');
  }
  nextResponse={ok:true,json:async()=>({unit:'Kanwil',year:2027,revision:2,budget_id:124,has_record:true,inputs})};
  await editTrigger.events.click();assert(save.disabled && inputStage.hidden,'Edit must reject a replaced/deleted record');
  assert.strictEqual(fields.find(field=>field.dataset.rkaInput==='G11').value,'3.210,05','Failed edit load must preserve draft');
  console.log('Edit RKA: all nominal fields from manual and Excel preserved exactly, automatic totals match, replacement identity rejected.');
  console.log('Manual RKA two steps: Next gate, existing-budget warning, explicit blank reset, new blank draft, revision protection and unsaved-edit confirmation OK.');
})().catch(error=>{console.error(error);process.exitCode=1;});
