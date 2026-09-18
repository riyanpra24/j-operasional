"""Independent read-only audit for two all-sheet sign rules."""
from zipfile import ZipFile
from xml.etree import ElementTree as ET
from decimal import Decimal, getcontext
import subprocess, json, re

getcontext().prec=180
source='C:/Users/Jamkrindo/Downloads/LR SEKANWIL YTD AGUSTUS 2026.xlsx'
ns={'s':'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
norm=lambda value: re.sub(r'\s+',' ',value).strip().lower()
targets={'laba tahun berjalan','jumlah laba komprehensif tahun berjalan'}
expected={}
with ZipFile(source) as z:
    strings=[''.join(t.text or '' for t in item.findall('.//s:t',ns)) for item in ET.fromstring(z.read('xl/sharedStrings.xml')).findall('s:si',ns)]
    rels={rel.get('Id'):rel.get('Target') for rel in ET.fromstring(z.read('xl/_rels/workbook.xml.rels'))}
    for sheet in ET.fromstring(z.read('xl/workbook.xml')).findall('s:sheets/s:sheet',ns):
        path=rels[sheet.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id')]
        path=path.lstrip('/') if path.startswith('/') else 'xl/'+path
        for row in ET.fromstring(z.read(path)).findall('s:sheetData/s:row',ns):
            cells={}
            for cell in row.findall('s:c',ns):
                value=cell.find('s:v',ns); value=value.text if value is not None else ''
                if cell.get('t')=='s': value=strings[int(value)]
                elif cell.get('t')=='inlineStr': value=''.join(t.text or '' for t in cell.findall('.//s:t',ns))
                cells[re.sub(r'\d','',cell.get('r'))]=(value,cell)
            label=norm(cells.get('D',('',))[0])
            if label not in targets: continue
            raw,amount=cells['H']; assert amount.find('s:f',ns) is None and amount.get('t') in (None,'n')
            key=(sheet.get('name'),label); assert key not in expected
            expected[key]=(int(row.get('r')),Decimal(raw),-Decimal(raw))
prefix='require "app/Libraries/LrMoney.php"; require "app/Libraries/OracleLrSalaryParser.php"; require "app/Libraries/LrReportRows.php"; require "app/Libraries/LrSignRules.php"; require "app/Libraries/OracleLrMappingService.php"; '
code='$p=(new App\\Libraries\\OracleLrSalaryParser())->parse("'+source+'"); echo json_encode($p["all_sheet_sign_rule_inputs"]);'
actual=json.loads(subprocess.check_output(['C:/xampp/php/php.exe','-r',prefix+code],text=True))
assert len(actual)==len(expected)==12
for item in actual:
    key=(item['sheet'],norm(item['description'])); assert key in expected
    row,source_value,calculation=expected.pop(key)
    assert item['row']==row and Decimal(item['source_amount'])==source_value and Decimal(item['calculation_amount'])==calculation
assert not expected
print('All-sheet sign reconciliation: 12 values across 6 sheets; exact source decimals retained and both sign directions inverted.')
