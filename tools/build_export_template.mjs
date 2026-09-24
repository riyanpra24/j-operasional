import fs from "node:fs/promises";
import path from "node:path";
import { execFileSync } from "node:child_process";
import { fileURLToPath } from "node:url";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const source = process.argv[2];
const output = process.argv[3];
if (!source || !output) throw new Error("Usage: build_export_template.mjs <source> <output>");

const workbook = await SpreadsheetFile.importXlsx(await FileBlob.load(source));
for (const sheet of workbook.worksheets.items) {
  const target = sheet.getRange("F9:S176");
  target.clear({ applyTo: "contents" });
  target.values = Array.from({ length: 168 }, () => Array(14).fill(null));
  sheet.getRange("R4").values = [[null]];
}

await fs.mkdir(path.dirname(output), { recursive: true });
const file = await SpreadsheetFile.exportXlsx(workbook);
await file.save(output);
// The imported XLSX exporter can serialize cleared formulas again. Strip any
// surviving source values from OOXML while preserving the artifact-tool styles.
execFileSync("php", [path.join(path.dirname(fileURLToPath(import.meta.url)), "sanitize_export_template.php"), output], { stdio: "inherit" });
