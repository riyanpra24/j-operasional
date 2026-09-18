import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const input = await FileBlob.load("C:/Users/Jamkrindo/Documents/JAKSA/Laporan Laba Rugi.xlsx");
const workbook = await SpreadsheetFile.importXlsx(input);
const sheet = workbook.worksheets.getItem("Kanwil");
const used = sheet.getUsedRange();
const values = used.values;
const formulas = used.formulas;

const valueAt = (row, col) => values[row - 1]?.[col - 1] ?? null;
const formulaAt = (row, col) => formulas[row - 1]?.[col - 1] ?? null;
const sumRows = (start, end) => {
  let total = 0;
  for (let row = start; row <= end; row += 1) {
    const value = Number(valueAt(row, 8));
    if (Number.isFinite(value)) total += value;
  }
  return total;
};

const detailRanges = [
  { name: "Beban Sumber Daya Manusia", start: 23, end: 40 },
  { name: "Beban Administrasi dan Umum", start: 42, end: 100 },
  { name: "Beban Penyusutan dan Amortisasi", start: 102, end: 113 },
  { name: "Pendapatan (Beban) Lain-lain", start: 116, end: 118 },
  { name: "Beban Pajak Penghasilan", start: 122, end: 124 },
];

const sections = detailRanges.map((section) => ({
  ...section,
  total: sumRows(section.start, section.end),
}));

const details = [];
for (const section of detailRanges) {
  for (let row = section.start; row <= section.end; row += 1) {
    const amount = Number(valueAt(row, 8));
    if (!Number.isFinite(amount)) continue;
    details.push({
      row,
      section: section.name,
      entity: valueAt(row, 1),
      lob: valueAt(row, 2),
      account: String(valueAt(row, 3) ?? ""),
      description: String(valueAt(row, 5) || valueAt(row, 4) || "").trim(),
      partner: valueAt(row, 6),
      amount,
    });
  }
}

const groupBy = (keyFn) => {
  const totals = new Map();
  for (const item of details) {
    const key = keyFn(item);
    totals.set(key, (totals.get(key) ?? 0) + item.amount);
  }
  return [...totals.entries()]
    .map(([key, amount]) => ({ key, amount }))
    .sort((a, b) => Math.abs(b.amount) - Math.abs(a.amount));
};

const formulaKinds = { textLiteral: 0, mid: 0, arithmetic: 0, other: 0 };
const formulaCells = [];
for (let row = 1; row <= formulas.length; row += 1) {
  for (let col = 1; col <= (formulas[row - 1]?.length ?? 0); col += 1) {
    const formula = formulaAt(row, col);
    if (!formula) continue;
    formulaCells.push({ row, col, formula });
    if (/^=".*"$/.test(formula)) formulaKinds.textLiteral += 1;
    else if (/^=MID\(/i.test(formula)) formulaKinds.mid += 1;
    else if (/[+\-*/]|SUM|SUBTOTAL/i.test(formula.slice(1))) formulaKinds.arithmetic += 1;
    else formulaKinds.other += 1;
  }
}

const reported = {
  totalOperatingExpenses: valueAt(114, 8),
  profitBeforeTax: valueAt(119, 8),
  incomeTaxExpense: valueAt(126, 8),
  profitCurrentYear: valueAt(127, 8),
  comprehensiveIncome: valueAt(129, 8),
};

const computed = {
  totalOperatingExpenses: sumRows(23, 113),
  otherIncomeExpense: sumRows(116, 118),
  profitBeforeTax: sumRows(23, 113) + sumRows(116, 118),
  incomeTaxExpense: sumRows(122, 124),
  profitCurrentYearUsingWorkbookSign: sumRows(23, 113) + sumRows(116, 118) + sumRows(122, 124),
};

const checks = {
  totalOperatingExpensesDelta: reported.totalOperatingExpenses - computed.totalOperatingExpenses,
  profitBeforeTaxDelta: reported.profitBeforeTax - computed.profitBeforeTax,
  incomeTaxExpenseDelta: reported.incomeTaxExpense - computed.incomeTaxExpense,
  profitCurrentYearDelta: reported.profitCurrentYear - computed.profitCurrentYearUsingWorkbookSign,
  comprehensiveIncomeVsProfitDelta: reported.comprehensiveIncome - reported.profitCurrentYear,
};

console.log(JSON.stringify({
  period: valueAt(5, 1),
  entity: valueAt(6, 1),
  rowCount: values.length,
  formulaCount: formulaCells.length,
  formulaKinds,
  formulasInEndingBalance: formulaCells.filter((cell) => cell.col === 8),
  sections,
  reported,
  computed,
  checks,
  byLob: groupBy((item) => item.lob),
  byAccount: groupBy((item) => `${item.account} | ${item.description}`),
  topAbsoluteDetails: [...details]
    .sort((a, b) => Math.abs(b.amount) - Math.abs(a.amount))
    .slice(0, 15),
}, null, 2));
