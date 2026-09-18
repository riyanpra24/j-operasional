const fs=require('fs'),vm=require('vm'),assert=require('assert');
const source=fs.readFileSync('public/assets/app.js','utf8');
const snippet=source.slice(source.indexOf('// RKA Excel:'),source.indexOf('// RKA deletion:'));
const element=(value='')=>({value,textContent:'',disabled:false,hidden:false,checked:true,files:[],events:{},
 addEventListener(name,handler){this.events[name]=handler;},reportValidity(){return true;},setCustomValidity(){}});
const unit=element('Kanwil'),year=element('2026'),next=element(),reset=element(),file=element(),confirmation=element(),submit=element(),revision=element('0'),flag=element('0'),id=element(),selection=element(),fileStage=element(),alert=element(),back=element();
const units=['Korporat Kanwil','Kanwil','Surabaya','Kediri','Malang','Madiun','Banyuwangi'];
const scope=element('single'),snapshots=element();
const mapping={'#rkaUploadUnit':unit,'#rkaUploadYear':year,'[data-rka-upload-next]':next,'[data-rka-upload-reset]':reset,'[data-rka-upload-file]':file,'[name="confirm_replace"]':confirmation,'[data-rka-upload-submit]':submit,
 '[data-rka-upload-revision]':revision,'[data-rka-upload-reset-flag]':flag,'[data-rka-upload-reset-id]':id,'[data-rka-upload-selection]':selection,'[data-rka-upload-file-stage]':fileStage,'[data-rka-upload-existing-alert]':alert,'[data-rka-upload-step-back]':back};
Object.assign(mapping,{'#rkaUploadScope':scope,'[data-rka-upload-all-snapshots]':snapshots});
const form={dataset:{rkaDataUrl:'/data',rkaUnits:JSON.stringify(units)},events:{},querySelector(key){return mapping[key]||(mapping[key]=element());},addEventListener(name,handler){this.events[name]=handler;}};
let response,requests=0;
vm.runInNewContext(snippet,{URL,window:{location:{href:'http://localhost/rka'},confirm(){return true;}},
 fetch:async()=>{requests++;return response;},document:{querySelector(){return form;},dispatchEvent(){}},CustomEvent:class{}});
(async()=>{
 assert(fileStage.hidden && file.disabled && submit.hidden && submit.disabled);
 let blocked=false;form.events.submit({preventDefault(){blocked=true;}});assert(blocked);
 response={ok:true,json:async()=>({unit:'Kanwil',year:2026,revision:3,budget_id:123,has_record:true})};
 await next.events.click();assert(!alert.hidden && fileStage.hidden && submit.disabled);
 file.value='previous.xlsx';reset.events.click();
 assert.strictEqual(file.value,'');assert.strictEqual(flag.value,'1');assert.strictEqual(id.value,'123');assert.strictEqual(revision.value,'3');
 assert(!fileStage.hidden && selection.hidden && !file.disabled && !submit.disabled && !confirmation.checked);
 back.events.click();assert(fileStage.hidden && submit.disabled);
 unit.value='Malang';unit.events.change();
 assert.strictEqual(flag.value,'0');assert.strictEqual(id.value,'');
 response={ok:true,json:async()=>({unit:'Malang',year:2026,revision:0,budget_id:null,has_record:false})};
 await next.events.click();assert(!fileStage.hidden && !submit.disabled && flag.value==='0');
 back.events.click();unit.value='Kediri';unit.events.change();
 response={ok:true,json:async()=>({unit:'wrong',year:2026,revision:0,budget_id:null,has_record:false})};
 await next.events.click();assert(fileStage.hidden && submit.disabled,'Bad response must keep replacement upload blocked');
 scope.value='all';scope.events.change();assert(unit.hidden && !unit.required);
 const records=Object.fromEntries(units.map(name=>[name,{id:null,revision:0}]));records.Kanwil={id:123,revision:3};
 response={ok:true,json:async()=>({scope:'all',year:2026,records})};
 await next.events.click();assert(!alert.hidden && fileStage.hidden && submit.disabled);
 assert(mapping['[data-rka-upload-delete-existing]'].hidden,'All upload must not expose a single-unit delete');
 reset.events.click();assert(!fileStage.hidden && flag.value==='1' && id.value==='');
 assert.deepStrictEqual(JSON.parse(snapshots.value),records);
 scope.value='single';scope.events.change();assert(!unit.hidden && unit.required && snapshots.value==='');
 blocked=false;form.events.submit({preventDefault(){blocked=true;}});assert(blocked,'Changing scope invalidates checked records');
 scope.value='all';scope.events.change();
 response={ok:true,json:async()=>({scope:'all',year:2026,records:{Kanwil:{id:null,revision:0}}})};
 await next.events.click();assert(fileStage.hidden && submit.disabled,'Missing unit snapshot must block all upload');
 response={ok:true,json:async()=>({scope:'all',year:2026,records:Object.fromEntries(units.map(name=>[name,{id:null,revision:0}]))})};
 await next.events.click();assert(!fileStage.hidden && flag.value==='0','New all-unit upload does not require replacement reset');
 console.log('All-unit upload: mode selection, seven fresh identities, replacement warning, explicit reset and incomplete-response blocking OK.');
 console.log('Excel upload: two-step gate, existing-budget warning, explicit reset, exact id/revision, fresh-record upload and invalid-response blocking OK.');
})().catch(error=>{console.error(error);process.exitCode=1;});
