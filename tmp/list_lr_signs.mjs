import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const source = await FileBlob.load("C:/Users/Jamkrindo/Downloads/LR SEKANWIL YTD AGUSTUS 2026.xlsx");
const workbook = await SpreadsheetFile.importXlsx(source);
const result = [];

for (let i = 0; i < workbook.worksheets.items.length; i += 1) {
  const sheet = workbook.worksheets.getItemAt(i);
  const values = sheet.getUsedRange()?.values ?? [];
  let section = "";
  for (let row = 7; row < values.length; row += 1) {
    const entity = values[row]?.[0] ?? null;
    const account = String(values[row]?.[2] ?? "").trim();
    const label = String(values[row]?.[3] ?? "").replace(/\s+/g, " ").trim();
    const amount = values[row]?.[7] ?? null;
    if (!entity && label) section = label;
    if (!entity || !account || typeof amount !== "number" || amount === 0) continue;
    result.push({ sheet: sheet.name, row: row + 1, section, account, label, amount });
  }
}

const grouped = new Map();
for (const item of result) {
  const key = `${item.section} | ${item.account} | ${item.label}`;
  if (!grouped.has(key)) grouped.set(key, { section: item.section, account: item.account, label: item.label, positive: 0, negative: 0, total: 0, examples: [] });
  const group = grouped.get(key);
  if (item.amount > 0) group.positive += 1;
  else group.negative += 1;
  group.total += item.amount;
  if (group.examples.length < 5) group.examples.push({ sheet: item.sheet, row: item.row, amount: item.amount });
}

console.log(JSON.stringify([...grouped.values()].sort((a, b) => a.account.localeCompare(b.account) || a.section.localeCompare(b.section)), null, 2));
