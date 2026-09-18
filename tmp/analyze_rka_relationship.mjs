import fs from "node:fs/promises";
import path from "node:path";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const [rkaPath, lrPath, outputDir] = process.argv.slice(2);
if (!rkaPath || !lrPath || !outputDir) {
  throw new Error("Usage: node analyze_rka_relationship.mjs <rka.xlsx> <lr.xlsx> <outputDir>");
}

const normalize = (value) => String(value ?? "").replace(/\s+/g, " ").trim();
const hasText = (value) => value !== null && value !== undefined && normalize(value) !== "";
const isNumber = (value) => typeof value === "number" && Number.isFinite(value);
const columnName = (index) => {
  let n = index + 1;
  let name = "";
  while (n > 0) {
    const r = (n - 1) % 26;
    name = String.fromCharCode(65 + r) + name;
    n = Math.floor((n - 1) / 26);
  }
  return name;
};
const cellAddress = (row, col) => `${columnName(col)}${row + 1}`;

async function loadWorkbook(filePath) {
  return SpreadsheetFile.importXlsx(await FileBlob.load(filePath));
}

function collectSheet(sheet) {
  const used = sheet.getUsedRange();
  const values = used?.values ?? [];
  const formulas = used?.formulas ?? [];
  const displayFormulas = used?.displayFormulas ?? [];
  const rowCount = Math.max(values.length, formulas.length);
  const colCount = Math.max(0, ...values.map((row) => row?.length ?? 0), ...formulas.map((row) => row?.length ?? 0));
  let firstNonEmpty = null;
  let lastNonEmpty = null;
  const populatedRows = [];
  const formulaCells = [];
  const externalFormulaCells = [];
  const crossSheetFormulaCells = [];
  const labelRows = [];
  const numericRows = [];
  const keywordRegex = /(anggaran|realisasi|rka|rkap|persen|prosentase|persentase|sisa|varian|variance|ytd|laba|rugi|pendapatan|beban|biaya|surplus|defisit|saldo|akun|rekening)/i;

  for (let r = 0; r < rowCount; r += 1) {
    const rowValues = values[r] ?? [];
    const rowFormulas = formulas[r] ?? [];
    const nonEmptyCols = [];
    const textParts = [];
    const numericCells = [];
    for (let c = 0; c < colCount; c += 1) {
      const value = rowValues[c];
      const formula = rowFormulas[c];
      if (hasText(value) || hasText(formula)) {
        nonEmptyCols.push(c);
        if (!firstNonEmpty) firstNonEmpty = { row: r + 1, col: c + 1, address: cellAddress(r, c) };
        lastNonEmpty = { row: r + 1, col: c + 1, address: cellAddress(r, c) };
      }
      if (typeof value === "string" && normalize(value)) textParts.push(normalize(value));
      if (isNumber(value)) numericCells.push({ address: cellAddress(r, c), value });
      if (hasText(formula)) {
        const record = {
          address: cellAddress(r, c),
          formula: normalize(formula),
          displayFormula: normalize(displayFormulas[r]?.[c]),
          value,
        };
        formulaCells.push(record);
        if (/\[[^\]]+\]|\.xlsx|\.xls|\\|https?:/i.test(record.formula)) externalFormulaCells.push(record);
        if (/!/.test(record.formula)) crossSheetFormulaCells.push(record);
      }
    }
    if (nonEmptyCols.length) {
      populatedRows.push({
        row: r + 1,
        firstCol: columnName(Math.min(...nonEmptyCols)),
        lastCol: columnName(Math.max(...nonEmptyCols)),
        preview: rowValues.slice(0, Math.min(colCount, 18)).map((v) => hasText(v) ? v : null),
      });
    }
    const rowText = textParts.join(" | ");
    if (keywordRegex.test(rowText)) {
      labelRows.push({ row: r + 1, text: rowText.slice(0, 800), numericCells: numericCells.slice(0, 16) });
    }
    if (numericCells.length >= 2) {
      numericRows.push({ row: r + 1, text: rowText.slice(0, 300), numericCells: numericCells.slice(0, 20) });
    }
  }

  const formulaFamilies = {};
  for (const item of formulaCells) {
    const matches = item.formula.toUpperCase().match(/[A-Z][A-Z0-9._]*(?=\()/g) ?? [];
    if (!matches.length) formulaFamilies.ARITHMETIC = (formulaFamilies.ARITHMETIC ?? 0) + 1;
    for (const fn of matches) formulaFamilies[fn] = (formulaFamilies[fn] ?? 0) + 1;
  }

  return {
    name: sheet.name,
    usedRange: firstNonEmpty && lastNonEmpty ? `${firstNonEmpty.address}:${lastNonEmpty.address}` : null,
    rowCount,
    colCount,
    formulaCount: formulaCells.length,
    externalFormulaCount: externalFormulaCells.length,
    crossSheetFormulaCount: crossSheetFormulaCells.length,
    formulaFamilies,
    formulaSamples: formulaCells.slice(0, 80),
    externalFormulaCells: externalFormulaCells.slice(0, 100),
    crossSheetFormulaCells: crossSheetFormulaCells.slice(0, 100),
    labelRows: labelRows.slice(0, 160),
    firstPopulatedRows: populatedRows.slice(0, 30),
    lastPopulatedRows: populatedRows.slice(-20),
    numericRows: numericRows.slice(0, 100),
  };
}

function collectLrAccounts(workbook) {
  const accounts = new Map();
  for (const sheet of workbook.worksheets.items) {
    const used = sheet.getUsedRange();
    const values = used?.values ?? [];
    for (let r = 0; r < values.length; r += 1) {
      const entity = values[r]?.[0];
      const rawAccount = values[r]?.[2];
      const description = normalize(values[r]?.[3]);
      const amount = values[r]?.[7];
      if (!hasText(entity) || !hasText(rawAccount) || !isNumber(amount)) continue;
      const account = normalize(rawAccount).replace(/^'+/, "");
      if (!/\d{4,}/.test(account)) continue;
      const key = account.match(/\d{4,}/)?.[0] ?? account;
      const current = accounts.get(key) ?? { account: key, descriptions: new Set(), total: 0, bySheet: {} };
      if (description) current.descriptions.add(description);
      current.total += amount;
      current.bySheet[sheet.name] = (current.bySheet[sheet.name] ?? 0) + amount;
      accounts.set(key, current);
    }
  }
  return accounts;
}

function findRkaAccountCandidates(workbook, lrAccounts) {
  const matches = [];
  const seen = new Set();
  for (const sheet of workbook.worksheets.items) {
    const used = sheet.getUsedRange();
    const values = used?.values ?? [];
    const formulas = used?.formulas ?? [];
    const rowCount = Math.max(values.length, formulas.length);
    const colCount = Math.max(0, ...values.map((row) => row?.length ?? 0), ...formulas.map((row) => row?.length ?? 0));
    for (let r = 0; r < rowCount; r += 1) {
      for (let c = 0; c < colCount; c += 1) {
        const candidates = [values[r]?.[c], formulas[r]?.[c]];
        for (const raw of candidates) {
          const text = normalize(raw);
          if (!text) continue;
          const accountCodes = text.match(/\b\d{5,12}\b/g) ?? [];
          for (const account of accountCodes) {
            if (!lrAccounts.has(account)) continue;
            const key = `${sheet.name}|${cellAddress(r, c)}|${account}`;
            if (seen.has(key)) continue;
            seen.add(key);
            matches.push({
              sheet: sheet.name,
              address: cellAddress(r, c),
              account,
              value: values[r]?.[c],
              formula: formulas[r]?.[c] ?? null,
              rowPreview: (values[r] ?? []).slice(0, Math.min(colCount, 24)),
              lr: {
                descriptions: [...lrAccounts.get(account).descriptions].slice(0, 5),
                total: lrAccounts.get(account).total,
                bySheet: lrAccounts.get(account).bySheet,
              },
            });
          }
        }
      }
    }
  }
  return matches;
}

const rka = await loadWorkbook(rkaPath);
const lr = await loadWorkbook(lrPath);
const result = {
  rkaPath,
  lrPath,
  rkaSheetNames: rka.worksheets.items.map((sheet) => sheet.name),
  lrSheetNames: lr.worksheets.items.map((sheet) => sheet.name),
  rkaSheets: rka.worksheets.items.map(collectSheet),
};
const lrAccounts = collectLrAccounts(lr);
result.lrDistinctAccounts = lrAccounts.size;
result.rkaLrAccountMatches = findRkaAccountCandidates(rka, lrAccounts);

const errors = await rka.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!",
  options: { useRegex: true, maxResults: 500 },
  summary: "formula error scan",
});
result.formulaErrors = errors.ndjson;

await fs.mkdir(outputDir, { recursive: true });
for (let index = 0; index < rka.worksheets.items.length; index += 1) {
  const sheet = rka.worksheets.getItemAt(index);
  const safeName = `${String(index + 1).padStart(2, "0")}_${sheet.name.replace(/[<>:"/\\|?*]/g, "_")}.png`;
  try {
    const preview = await rka.render({ sheetName: sheet.name, autoCrop: "all", scale: 0.9, format: "png" });
    await fs.writeFile(path.join(outputDir, safeName), new Uint8Array(await preview.arrayBuffer()));
  } catch (error) {
    result.rkaSheets[index].renderError = String(error?.message ?? error);
  }
}

const reportPath = path.join(outputDir, "analysis.json");
await fs.writeFile(reportPath, JSON.stringify(result, null, 2), "utf8");
console.log(JSON.stringify({ reportPath, sheets: result.rkaSheetNames.length, formulaErrors: result.formulaErrors }, null, 2));
