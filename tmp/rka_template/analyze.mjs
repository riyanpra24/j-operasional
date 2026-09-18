import fs from 'node:fs/promises';
import {FileBlob, SpreadsheetFile} from '@oai/artifact-tool';
const workbook = await SpreadsheetFile.importXlsx(await FileBlob.load('C:/Users/Jamkrindo/Downloads/Template RKA.xlsx'));
console.log((await workbook.inspect({kind:'sheet',include:'id,name',maxChars:3000})).ndjson);
const sheets=[];
for(let i=0;i<workbook.worksheets.items.length;i++){
  const sheet=workbook.worksheets.getItemAt(i);
  const range=sheet.getUsedRange();
  const data={name:sheet.name,address:range.address,values:range.values,formulas:range.formulas};
  sheets.push(data);
  console.log(JSON.stringify({name:sheet.name,address:range.address}));
}
await fs.writeFile('tmp/rka_template/analysis.json',JSON.stringify(sheets,null,2));
console.log((await workbook.inspect({kind:'formula',sheetId:sheets[0].name,range:'A1:Z180',maxChars:4500,options:{maxResults:30}})).ndjson);
console.log((await workbook.inspect({kind:'match',searchTerm:'#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!',options:{useRegex:true,maxResults:25},maxChars:2000})).ndjson);
