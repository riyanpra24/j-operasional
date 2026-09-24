const fs=require('fs');
const vm=require('vm');
const assert=require('assert');

const source=fs.readFileSync('public/assets/app.js','utf8');
const snippet=source.slice(source.indexOf('// Laba / Rugi: multi-pilih'),source.indexOf('// Laba & Rugi:'));
const fields=['KUR','PEN','NON KUR','KBG/SURETYSHIP','KONSUMTIF','PRODUKTIF'].map(value=>({value,checked:true,events:{},addEventListener(event,handler){this.events[event]=handler;}}));
const summary={textContent:''};
const inside={};
const picker={open:true,querySelector(selector){return selector==='summary'?summary:null;},querySelectorAll(){return fields;},contains(target){return target===inside;},removeAttribute(name){if(name==='open')this.open=false;}};
const documentEvents={};
const document={
    querySelectorAll(selector){return selector==='.lr-lob-picker'?[picker]:(selector==='.lr-lob-picker[open]'&&picker.open?[picker]:[]);},
    addEventListener(event,handler){documentEvents[event]=handler;},
};
vm.runInNewContext(snippet,{document});
assert.strictEqual(summary.textContent,'Semua LOB');
fields.slice(1).forEach(field=>{field.checked=false;field.events.change();});
assert.strictEqual(summary.textContent,'KUR');
fields[0].checked=false; fields[0].events.change();
assert.strictEqual(fields[0].checked,true,'Last LOB must remain selected.');
documentEvents.click({target:inside}); assert.strictEqual(picker.open,true,'Click inside must keep picker open.');
documentEvents.click({target:{}}); assert.strictEqual(picker.open,false,'Click outside must close picker.');
console.log('LR LOB picker: multi-select summary, minimum one option, and click-outside closing OK.');
