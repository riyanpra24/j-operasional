from collections import Counter
from decimal import Decimal
from zipfile import ZipFile
from xml.etree import ElementTree as ET
import re


SOURCE = r"C:\Users\Jamkrindo\Downloads\LR SEKANWIL YTD AGUSTUS 2026.xlsx"
BRANCHES = ("SURABAYA", "KEDIRI", "MALANG", "MADIUN", "BANYUWANGI")
NS = {"s": "http://schemas.openxmlformats.org/spreadsheetml/2006/main"}
RID = "{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id"


def normalized(value):
    return re.sub(r"\s+", " ", value or "").strip().lower()


with ZipFile(SOURCE) as archive:
    strings = []
    if "xl/sharedStrings.xml" in archive.namelist():
        root = ET.fromstring(archive.read("xl/sharedStrings.xml"))
        strings = ["".join(node.text or "" for node in item.findall(".//s:t", NS)) for item in root.findall("s:si", NS)]
    relationships = {
        item.get("Id"): item.get("Target")
        for item in ET.fromstring(archive.read("xl/_rels/workbook.xml.rels"))
    }
    workbook = ET.fromstring(archive.read("xl/workbook.xml"))
    sheets = {item.get("name"): item for item in workbook.findall("s:sheets/s:sheet", NS)}

    for sheet_name in BRANCHES:
        target = relationships[sheets[sheet_name].get(RID)]
        target = target.lstrip("/") if target.startswith("/") else "xl/" + target
        rows = []
        for row in ET.fromstring(archive.read(target)).findall("s:sheetData/s:row", NS):
            cells = {}
            for cell in row.findall("s:c", NS):
                raw = cell.find("s:v", NS)
                value = raw.text if raw is not None else ""
                if cell.get("t") == "s":
                    value = strings[int(value)]
                elif cell.get("t") == "inlineStr":
                    value = "".join(node.text or "" for node in cell.findall(".//s:t", NS))
                column = re.sub(r"\d", "", cell.get("r"))
                cells[column] = value
            rows.append((int(row.get("r")), cells))

        headers = {column: next((cells.get(column, "") for row, cells in rows if row == 7), "") for column in ("B", "D", "H")}
        segment_counts = Counter()
        account_counts = Counter()
        examples = []
        total = {"KUR": Decimal(0), "NON KUR": Decimal(0)}
        for row, cells in rows:
            segment = {"kur": "KUR", "non kur": "NON KUR"}.get(normalized(cells.get("B")))
            description = normalized(cells.get("D"))
            if segment is None or not description or not cells.get("H"):
                continue
            segment_counts[segment] += 1
            account_counts[(segment, description)] += 1
            total[segment] += Decimal(cells["H"])
            if len(examples) < 3:
                examples.append((row, segment, cells.get("D"), cells.get("H")))
        print(sheet_name, headers, dict(segment_counts), "distinct", len(account_counts), "totals", total, "examples", examples)
