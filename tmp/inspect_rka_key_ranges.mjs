import fs from "node:fs/promises";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const [inputPath, outputPath] = process.argv.slice(2);
if (!inputPath || !outputPath) throw new Error("Usage: node inspect_rka_key_ranges.mjs <input.xlsx> <output.json>");
const workbook = await SpreadsheetFile.importXlsx(await FileBlob.load(inputPath));
const requests = [
  { sheetId: "KORPORAT KANWIL", range: "D1:X25" },
  { sheetId: "KORPORAT KANWIL", range: "D140:X166" },
  { sheetId: "SURABAYA", range: "D5:S25" },
  { sheetId: "SURABAYA", range: "W8:AG25" },
  { sheetId: "KANWIL", range: "D160:S166" },
];
const results = [];
for (const request of requests) {
  const inspected = await workbook.inspect({
    kind: "table",
    sheetId: request.sheetId,
    range: request.range,
    include: "values,formulas",
    tableMaxRows: 30,
    tableMaxCols: 24,
    tableMaxCellChars: 180,
    maxChars: 16000,
  });
  results.push({ ...request, ndjson: inspected.ndjson });
}
await fs.writeFile(outputPath, JSON.stringify(results, null, 2), "utf8");
console.log(JSON.stringify(results.map((item) => ({ sheet: item.sheetId, range: item.range, chars: item.ndjson.length })), null, 2));
