import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const inputPath = process.argv[2];
if (!inputPath) throw new Error("Input workbook path is required.");
const source = await FileBlob.load(inputPath);
const workbook = await SpreadsheetFile.importXlsx(source);

const result = { sheets: [], formulaErrors: null };
for (let sheetIndex = 0; sheetIndex < workbook.worksheets.items.length; sheetIndex += 1) {
  const sheet = workbook.worksheets.getItemAt(sheetIndex);
  const used = sheet.getUsedRange();
  const values = used?.values ?? [];
  const formulas = used?.formulas ?? [];
  const rows = Math.max(values.length, formulas.length);
  const cols = Math.max(0, ...values.map((r) => r?.length ?? 0), ...formulas.map((r) => r?.length ?? 0));
  const summaryRows = [];
  const headings = [];
  const lobTotals = new Map();
  const accountTotals = new Map();
  let dataRowCount = 0;
  let formulaCount = 0;
  let endingBalanceFormulaCount = 0;
  let textLiteralFormulaCount = 0;
  let midFormulaCount = 0;

  for (let row = 0; row < rows; row += 1) {
    const entity = values[row]?.[0] ?? null;
    const lob = values[row]?.[1] ?? null;
    const account = values[row]?.[2] ?? null;
    const label = values[row]?.[3] ?? null;
    const amount = values[row]?.[7] ?? null;
    if (!entity && typeof label === "string") {
      if (typeof amount === "number") summaryRows.push({ row: row + 1, label: label.replace(/\s+/g, " ").trim(), amount });
      else headings.push({ row: row + 1, label: label.replace(/\s+/g, " ").trim() });
    }
    if (entity && typeof amount === "number") {
      dataRowCount += 1;
      const lobKey = String(lob ?? "Tanpa LOB").trim();
      lobTotals.set(lobKey, (lobTotals.get(lobKey) ?? 0) + amount);
      const accountKey = `${String(account ?? "").trim()} | ${String(label ?? "").replace(/\s+/g, " ").trim()}`;
      accountTotals.set(accountKey, (accountTotals.get(accountKey) ?? 0) + amount);
    }
    for (let col = 0; col < cols; col += 1) {
      const formula = formulas[row]?.[col] ?? null;
      if (!formula) continue;
      formulaCount += 1;
      if (col === 7) endingBalanceFormulaCount += 1;
      if (/^=".*"$/.test(formula)) textLiteralFormulaCount += 1;
      if (/^=MID\(/i.test(formula)) midFormulaCount += 1;
    }
  }

  const sortedAccountTotals = [...accountTotals.entries()]
    .map(([account, amount]) => ({ account, amount }))
    .sort((a, b) => Math.abs(b.amount) - Math.abs(a.amount));

  result.sheets.push({
    name: sheet.name,
    period: values[4]?.[0] ?? null,
    entity: values[5]?.[0] ?? null,
    rows,
    cols,
    dataRowCount,
    formulaCount,
    textLiteralFormulaCount,
    midFormulaCount,
    endingBalanceFormulaCount,
    headings,
    summaryRows,
    lobTotals: [...lobTotals.entries()].map(([lob, amount]) => ({ lob, amount })),
    topAccounts: sortedAccountTotals.slice(0, 12),
  });
}

const errors = await workbook.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!",
  options: { useRegex: true, maxResults: 300 },
  summary: "formula error scan",
});
result.formulaErrors = errors.ndjson;
console.log(JSON.stringify(result, null, 2));
