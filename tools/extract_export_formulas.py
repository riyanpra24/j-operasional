"""Extract Excel formulas from the approved deviation workbook for exports.

This reads the reference file; it does not modify the workbook. The exported
report uses these formulas only where the system also supplies a numeric value.
"""

import hashlib
import json
import sys
from pathlib import Path

import openpyxl


if len(sys.argv) != 3:
    raise SystemExit("Usage: extract_export_formulas.py <reference.xlsx> <formulas.json>")

source = Path(sys.argv[1])
destination = Path(sys.argv[2])
workbook = openpyxl.load_workbook(source, data_only=False, read_only=True)
formulas = {}
for sheet in workbook:
    formulas[sheet.title] = {
        cell.coordinate: cell.value
        for row in sheet.iter_rows(min_row=9, max_row=169, min_col=6, max_col=19)
        for cell in row
        if cell.data_type == "f"
    }

payload = {
    "reference_sha256": hashlib.sha256(source.read_bytes()).hexdigest(),
    "formulas_by_sheet": formulas,
}
destination.parent.mkdir(parents=True, exist_ok=True)
destination.write_text(json.dumps(payload, ensure_ascii=False, separators=(",", ":")), encoding="utf-8")
print("Extracted", sum(map(len, formulas.values())), "formulas across", len(formulas), "sheets")
