import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const source = await FileBlob.load("C:/Users/Jamkrindo/Downloads/LR SEKANWIL YTD AGUSTUS 2026.xlsx");
const workbook = await SpreadsheetFile.importXlsx(source);
const reversals = [];
const mixed = [];

function expectedSign(section, label, account) {
  const s = section.toLowerCase();
  const l = label.toLowerCase();
  if (account.startsWith("610")) return "mixed";
  if (s.includes("imbal jasa penjaminan bruto")) return "negative";
  if (s.includes("premi penjaminan ulang")) return "positive";
  if (s.includes("restitusi")) return "positive";
  if (s.includes("beban klaim")) return l.includes("penerimaan") ? "negative" : "positive";
  if (s.includes("pendapatan subrogasi")) return l.startsWith("beban") ? "positive" : "negative";
  if (s.includes("beban komisi netto")) return l.startsWith("pendapatan") ? "negative" : "positive";
  if (s.includes("pendapatan (beban) lain")) return l.startsWith("pendapatan") ? "negative" : "positive";
  if (s.includes("beban sumber daya") || s.includes("administrasi") || s.includes("penyusutan") || s.includes("pajak")) return "positive";
  return "unknown";
}

for (let i = 0; i < workbook.worksheets.items.length; i += 1) {
  const sheet = workbook.worksheets.getItemAt(i);
  const values = sheet.getUsedRange()?.values ?? [];
  let section = "";
  for (let r = 7; r < values.length; r += 1) {
    const entity = values[r]?.[0] ?? null;
    const lob = String(values[r]?.[1] ?? "").trim();
    const account = String(values[r]?.[2] ?? "").trim();
    const label = String(values[r]?.[3] ?? "").replace(/\s+/g, " ").trim();
    const amount = values[r]?.[7] ?? null;
    if (!entity && label) section = label;
    if (!entity || !account || typeof amount !== "number" || amount === 0) continue;
    const expected = expectedSign(section, label, account);
    const item = { sheet: sheet.name, row: r + 1, section, lob, account, label, amount, expected };
    if (expected === "mixed") mixed.push(item);
    else if ((expected === "positive" && amount < 0) || (expected === "negative" && amount > 0)) reversals.push(item);
  }
}

console.log(JSON.stringify({ reversalCount: reversals.length, reversals, mixedCount: mixed.length, mixed }, null, 2));
