"""Read-only independent Decimal reconciliation of KUR/NON KUR expense rows."""
from zipfile import ZipFile
from xml.etree import ElementTree as ET
from decimal import Decimal, ROUND_HALF_UP, getcontext
import subprocess, json, re

source = 'C:/Users/Jamkrindo/Downloads/LR SEKANWIL YTD AGUSTUS 2026.xlsx'
prefix = 'require "app/Libraries/LrMoney.php"; require "app/Libraries/OracleLrSalaryParser.php"; require "app/Libraries/LrReportRows.php"; require "app/Libraries/LrSignRules.php"; require "app/Libraries/OracleLrMappingService.php"; '
getcontext().prec = 180
run = lambda code: json.loads(subprocess.check_output(['C:/xampp/php/php.exe', '-r', prefix + code], text=True))
mapping = run('echo json_encode(App\\Libraries\\LrReportRows::sourceMap());')
result = run('$p=(new App\\Libraries\\OracleLrSalaryParser())->parse("' + source + '"); $v=App\\Libraries\\OracleLrSalaryParser::reportValues($p); $d=[]; foreach($v as $a=>$s) foreach($s as $k=>$x) $d[$a][$k]=App\\Libraries\\LrMoney::reportDisplay($x); echo json_encode(["values"=>$v,"display"=>$d,"rules"=>$p["sign_rule_inputs"]]);')
actual = result['values']
norm = lambda text: re.sub(r'\s+', ' ', text).strip().lower()
ns = {'s': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
expected = {}
matched = 0
with ZipFile(source) as z:
    strings = [''.join(t.text or '' for t in si.findall('.//s:t', ns)) for si in ET.fromstring(z.read('xl/sharedStrings.xml')).findall('s:si', ns)]
    relationships = {r.get('Id'): r.get('Target') for r in ET.fromstring(z.read('xl/_rels/workbook.xml.rels'))}
    sheet = next(s for s in ET.fromstring(z.read('xl/workbook.xml')).findall('s:sheets/s:sheet', ns) if s.get('name') == 'KANWIL')
    target = relationships[sheet.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id')]
    target = target.lstrip('/') if target.startswith('/') else 'xl/' + target
    for row in ET.fromstring(z.read(target)).findall('s:sheetData/s:row', ns):
        cells = {}
        for cell in row.findall('s:c', ns):
            value = cell.find('s:v', ns)
            value = value.text if value is not None else ''
            if cell.get('t') == 's': value = strings[int(value)]
            elif cell.get('t') == 'inlineStr': value = ''.join(t.text or '' for t in cell.findall('.//s:t', ns))
            cells[re.sub(r'\d', '', cell.get('r'))] = (value, cell)
        lob = norm(cells.get('B', ('',))[0])
        if lob not in ('kur', 'non kur'): continue
        segment = {'kur': 'KUR', 'non kur': 'NON KUR'}[lob]
        description = norm(cells.get('D', ('',))[0])
        if description not in mapping: continue
        raw, cell = cells['H']
        assert cell.find('s:f', ns) is None and cell.get('t') in (None, 'n')
        amount = Decimal(raw)
        key = norm(mapping[description])
        expected.setdefault(key, {})
        calculation = -amount if description in ('pendapatan jasa giro','pendapatan lainnya') else amount
        expected[key][segment] = expected[key].get(segment, Decimal(0)) + calculation
        matched += 1
assert set(expected) == set(actual), 'Mapped expense keys differ'
for key, segments in expected.items():
    assert set(segments) == set(actual[key]), f'Segment keys differ: {key}'
    for segment, amount in segments.items():
        assert Decimal(actual[key][segment]) == amount, f'Nominal differs: {key}, {segment}'
        whole = amount.copy_abs().quantize(Decimal(1), rounding=ROUND_HALF_UP)
        display = ('*' if amount < 0 else '') + f'{int(whole):,}'.replace(',', '.')
        assert result['display'][key][segment] == display, f'Rounded presentation differs: {key}, {segment}'
counts = {segment: sum(segment in amounts for amounts in expected.values()) for segment in ('KUR', 'NON KUR')}
assert Decimal(result['rules'][0]['source_amount']) == Decimal('7126234676.1700001')
assert Decimal(result['rules'][0]['calculation_amount']) == Decimal('-7126234676.1700001')
print(f'Independent read-only Decimal reconciliation: {matched} source rows; account counts {counts}; every source decimal and final display rounding agree separately.')
