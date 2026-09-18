import fs from "node:fs/promises";
import path from "node:path";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const [inputPath, outputDir] = process.argv.slice(2);
if (!inputPath || !outputDir) throw new Error("Usage: node trace_rka.mjs <input.xlsx> <outputDir>");
const workbook = await SpreadsheetFile.importXlsx(await FileBlob.load(inputPath));
await fs.mkdir(outputDir, { recursive: true });

const targets = [
  "KORPORAT KANWIL!R166",
  "KORPORAT KANWIL!X166",
  "SURABAYA!L11",
  "SURABAYA!R11",
  "KANWIL!R164",
];
const summary = [];
for (const target of targets) {
  try {
    const trace = await workbook.trace(target);
    const body = trace?.ndjson ?? JSON.stringify(trace);
    const safe = target.replace(/[^A-Za-z0-9_-]+/g, "_");
    await fs.writeFile(path.join(outputDir, `${safe}.ndjson`), body, "utf8");
    const lines = body.split(/\r?\n/).filter(Boolean);
    summary.push({ target, lineCount: lines.length, firstLines: lines.slice(0, 8) });
  } catch (error) {
    summary.push({ target, error: String(error?.message ?? error) });
  }
}
console.log(JSON.stringify(summary, null, 2));
