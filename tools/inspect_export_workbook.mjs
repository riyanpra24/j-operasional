import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const workbook = await SpreadsheetFile.importXlsx(await FileBlob.load(process.argv[2]));
const region = await workbook.inspect({
  kind: "region", sheetId: "SURABAYA", range: "F12:S22", maxChars: 2500,
  tableMaxRows: 11, tableMaxCols: 14, include: "values,formulas",
});
console.log(region.ndjson);
const errors = await workbook.inspect({
  kind: "match", searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!",
  options: { useRegex: true, maxResults: 30 }, maxChars: 2000,
});
console.log(errors.ndjson);
