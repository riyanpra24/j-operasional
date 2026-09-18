"""Independent read-only Decimal reconciliation for five branch sheets."""
from decimal import Decimal, getcontext
from zipfile import ZipFile
from xml.etree import ElementTree as ET
import json
import re
import subprocess


SOURCE = "C:/Users/Jamkrindo/Downloads/LR SEKANWIL YTD AGUSTUS 2026.xlsx"
UNITS = ("Surabaya", "Kediri", "Malang", "Madiun", "Banyuwangi")
NS = {"s": "http://schemas.openxmlformats.org/spreadsheetml/2006/main"}
RID = "{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id"
PHP_PREFIX = (
    'require "app/Libraries/LrMoney.php"; '
    'require "app/Libraries/OracleLrSalaryParser.php"; '
    'require "app/Libraries/LrReportRows.php"; '
    'require "app/Libraries/LrSignRules.php"; '
    'require "app/Libraries/OracleLrMappingService.php"; '
)
getcontext().prec = 180


def run_php(code):
    output = subprocess.check_output(["C:/xampp/php/php.exe", "-r", PHP_PREFIX + code], text=True)
    return json.loads(output)


def normalize(text):
    return re.sub(r"\s+", " ", text or "").strip().lower()


mapping = run_php("echo json_encode(App\\Libraries\\LrReportRows::branchSourceMap());")
formula_mappings = run_php("echo json_encode(App\\Libraries\\LrReportRows::branchFormulaSourceMappings());")
formula_mapping = {normalize(item["source_description"]): item for item in formula_mappings}
actual = run_php(
    '$p=new App\\Libraries\\OracleLrSalaryParser();$o=[];'
    'foreach(App\\Libraries\\OracleLrSalaryParser::BRANCH_UNITS as $u)'
    '{$r=$p->parse("' + SOURCE + '",$u);$o[$u]=["counts"=>$r["segment_counts"],"values"=>App\\Libraries\\OracleLrSalaryParser::reportValues($r)];}'
    'echo json_encode($o);'
)
with ZipFile(SOURCE) as archive:
    strings = []
    if "xl/sharedStrings.xml" in archive.namelist():
        root = ET.fromstring(archive.read("xl/sharedStrings.xml"))
        strings = ["".join(node.text or "" for node in item.findall(".//s:t", NS)) for item in root.findall("s:si", NS)]
    relationships = {item.get("Id"): item.get("Target") for item in ET.fromstring(archive.read("xl/_rels/workbook.xml.rels"))}
    workbook = ET.fromstring(archive.read("xl/workbook.xml"))
    sheets = {item.get("name"): item for item in workbook.findall("s:sheets/s:sheet", NS)}

    grand_rows = {"KUR": 0, "NON KUR": 0, "PEN": 0}
    for unit in UNITS:
        sheet_name = unit.upper()
        target = relationships[sheets[sheet_name].get(RID)]
        target = target.lstrip("/") if target.startswith("/") else "xl/" + target
        expected = {}
        counts = {"KUR": 0, "NON KUR": 0, "PEN": 0}
        for row in ET.fromstring(archive.read(target)).findall("s:sheetData/s:row", NS):
            cells = {}
            for cell in row.findall("s:c", NS):
                raw = cell.find("s:v", NS)
                value = raw.text if raw is not None else ""
                if cell.get("t") == "s":
                    value = strings[int(value)]
                elif cell.get("t") == "inlineStr":
                    value = "".join(node.text or "" for node in cell.findall(".//s:t", NS))
                cells[re.sub(r"\d", "", cell.get("r"))] = (value, cell)
            segment = {"kur": "KUR", "non kur": "NON KUR", "pen": "PEN"}.get(normalize(cells.get("B", ("",))[0]))
            source_label = normalize(cells.get("D", ("",))[0])
            formula = formula_mapping.get(source_label)
            if segment is None or (source_label not in mapping and formula is None):
                continue
            if formula is not None and segment not in formula.get("segments", []):
                continue
            raw_amount, amount_cell = cells["H"]
            assert amount_cell.find("s:f", NS) is None and amount_cell.get("t") in (None, "n")
            report_label = normalize(formula["report_label"] if formula is not None else mapping[source_label])
            amount = Decimal(raw_amount)
            sign_mode = formula.get("sign_mode") if formula is not None else ("invert" if report_label in ("pendapatan jasa giro", "pendapatan lainnya") else "keep")
            if sign_mode == "invert":
                amount = -amount
            expected.setdefault(report_label, {})
            expected[report_label][segment] = expected[report_label].get(segment, Decimal(0)) + amount
            counts[segment] += 1
            grand_rows[segment] += 1

        assert actual[unit]["counts"] == counts, f"row count differs: {unit}: {actual[unit]['counts']} != {counts}"
        assert set(actual[unit]["values"]) == set(expected), f"account set differs: {unit}"
        for label, segments in expected.items():
            assert set(actual[unit]["values"][label]) == set(segments), f"segment set differs: {unit}, {label}"
            for segment, amount in segments.items():
                assert Decimal(actual[unit]["values"][label][segment]) == amount, f"amount differs: {unit}, {label}, {segment}"

print(f"Independent branch reconciliation: {grand_rows['KUR']} KUR, {grand_rows['NON KUR']} NON KUR and {grand_rows['PEN']} PEN rows; every approved expense, guarantee and claim source decimal agrees across five sheets.")
