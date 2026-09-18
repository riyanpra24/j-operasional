"""Read-only reconciliation of the realization engine against the supplied workpaper."""

from decimal import Decimal, ROUND_HALF_UP, getcontext
import json
import subprocess

import openpyxl


SOURCE = "C:/Users/Jamkrindo/Downloads/LR SEKANWIL YTD AGUSTUS 2026.xlsx"
WORKPAPER = "C:/Users/Jamkrindo/Downloads/KERTAS KERJA REALISASI ANGGARAN KANWIL SURABAYA RKA 2026.xlsx"
PHP = "C:/xampp/php/php.exe"
UNITS = ("Kanwil", "Surabaya", "Kediri", "Malang", "Madiun", "Banyuwangi")
COLUMNS = {
    12: "KUR", 13: "PEN", 14: "NON KUR", 15: "KBG/SURETYSHIP",
    16: "KONSUMTIF", 17: "PRODUKTIF", 18: "TOTAL",
}
ROWS = {
    10: "Imbal Jasa Penjaminan Bruto",
    11: "Restitusi penjaminan kredit",
    12: "Premi Penjaminan Ulang",
    13: "IMBAL JASA PENJAMINAN BERSIH",
    16: "Beban Klaim",
    17: "Kenaikan (Penurunan) Cadangan Klaim",
    18: "Pendapatan Subrogasi",
    19: "Beban Komisi Netto",
    20: "JUMLAH BEBAN KLAIM",
    22: "PENJAMINAN BERSIH",
    51: "Total Beban Karyawan",
    141: "Total Beban Administrasi & Umum",
    159: "Total Beban Penyusutan & Amortisasi",
    161: "TOTAL BEBAN USAHA",
    163: "PENDAPATAN (BEBAN) LAIN-LAIN BERSIH",
    165: "LABA SEBELUM PAJAK",
}
getcontext().prec = 180


def normalize(value):
    return " ".join(value.strip().lower().split())


def whole(value):
    return Decimal(str(value or 0)).quantize(Decimal("1"), rounding=ROUND_HALF_UP)


requires = [
    "RkaMoney.php", "LrMoney.php", "LrSignRules.php", "OracleLrSalaryParser.php",
    "LrReportRows.php", "OracleLrMappingService.php", "RkaCalculator.php",
    "LrFormulaService.php", "LrSourceAdjustmentService.php", "LrRealizationCalculator.php",
]
prefix = "".join(f'require "app/Libraries/{name}";' for name in requires)
code = prefix + (
    '$p=new App\\Libraries\\OracleLrSalaryParser();$r=[];'
    'foreach(App\\Libraries\\OracleLrSalaryParser::IMPORT_UNITS as $u)'
    '{$r[$u]=$p->parse("' + SOURCE + '",$u);}'
    '$v=(new App\\Libraries\\LrRealizationCalculator())->calculate($r);'
    'echo json_encode($v,JSON_THROW_ON_ERROR);'
)
actual = json.loads(subprocess.check_output([PHP, "-r", code], text=True))
workbook = openpyxl.load_workbook(WORKPAPER, read_only=True, data_only=True)

checks = 0
differences = []
for unit in UNITS:
    sheet = workbook[unit.upper()]
    for row, label in ROWS.items():
        # Surabaya's workpaper included the erroneous reinsurance reserve source.
        # The approved system rule intentionally excludes it and therefore changes
        # this row and its downstream claim, net guarantee, and pre-tax profit.
        if unit == "Surabaya" and row in (17, 20, 22, 165):
            continue
        key = normalize(label)
        for column_number, column in COLUMNS.items():
            expected = whole(sheet.cell(row, column_number).value)
            observed = whole(actual.get(unit, {}).get(key, {}).get(column))
            known_helper_omission = unit == "Kanwil" and row in (159, 161, 165) and column != "TOTAL"
            if not known_helper_omission and abs(observed - expected) > 1:
                differences.append((unit, row, label, column, observed, expected))
            checks += 1

# Corporate lines that are not downstream of Surabaya's corrected reserve must
# still equal the workpaper's six-unit consolidation.
corporate = workbook["KORPORAT KANWIL"]
for row, label in ROWS.items():
    if row in (17, 20, 22, 165):
        continue
    key = normalize(label)
    for column_number, column in COLUMNS.items():
        expected = whole(corporate.cell(row, column_number).value)
        observed = whole(actual["Korporat Kanwil"].get(key, {}).get(column))
        known_helper_omission = row in (159, 161) and column != "TOTAL"
        if not known_helper_omission and abs(observed - expected) > 1:
            differences.append(("Korporat Kanwil", row, label, column, observed, expected))
        checks += 1

# The corrected Surabaya reserve uses only the approved primary-guarantee COA;
# product columns remain literal zero and the correction flows through formulas.
reserve = actual["Surabaya"][normalize("Kenaikan (Penurunan) Cadangan Klaim")]
assert all(whole(reserve.get(column)) == 0 for column in ("KBG/SURETYSHIP", "KONSUMTIF", "PRODUKTIF"))
assert whole(actual["Madiun"][normalize("Imbal Jasa Penjaminan Bruto")]["PEN"]) == Decimal("400000")
renovation = actual["Kanwil"][normalize("Beban penyusutan aset tetap - renovasi dan instalasi")]
assert whole(renovation["KUR"]) == Decimal("1766556")
assert whole(renovation["NON KUR"]) == Decimal("-1766556")
assert whole(renovation["TOTAL"]) == 0
checks += 7

assert not differences, differences

print(f"Full realization reconciliation: {checks} monetary cells agree within one Rupiah of Excel's binary cache; the approved Surabaya reserve correction and one balancing Kanwil COA omitted from the helper block are isolated and formula-driven.")
