from zipfile import ZipFile
from xml.etree import ElementTree as ET
from decimal import Decimal

path = r'C:\Users\Jamkrindo\Downloads\LR SEKANWIL YTD AGUSTUS 2026.xlsx'
ns = {'s': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
with ZipFile(path) as z:
    strings = [''.join(si.itertext()) for si in ET.fromstring(z.read('xl/sharedStrings.xml')).findall('s:si', ns)] if 'xl/sharedStrings.xml' in z.namelist() else []
    relations = {r.get('Id'): r.get('Target') for r in ET.fromstring(z.read('xl/_rels/workbook.xml.rels'))}
    book = ET.fromstring(z.read('xl/workbook.xml'))
    sheets = book.findall('s:sheets/s:sheet', ns)
    print('SHEETS', [s.get('name') for s in sheets])
    sheet = next(s for s in sheets if s.get('name').lower() == 'kanwil')
    target = relations[sheet.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id')]
    target = target.lstrip('/') if target.startswith('/') else 'xl/' + target
    rows = {}
    for row in ET.fromstring(z.read(target)).findall('s:sheetData/s:row', ns):
        cells = {}
        for c in row.findall('s:c', ns):
            v = c.find('s:v', ns)
            value = v.text if v is not None else ''
            if c.get('t') == 's': value = strings[int(value)]
            elif c.get('t') == 'inlineStr': value = ''.join(c.find('s:is', ns).itertext())
            f = c.find('s:f', ns)
            cells[''.join(filter(str.isalpha, c.get('r')))] = {'value': value, 'type': c.get('t'), 'formula': f.text if f is not None else None, 'fattrs': f.attrib if f is not None else None}
        rows[int(row.get('r'))] = cells
    for r, cells in rows.items():
        if r <= 10: print('HEADER', r, cells)
    selected = [(r, c) for r, c in rows.items() if c.get('B', {}).get('value', '').strip().lower() == 'kur' and c.get('D', {}).get('value', '').strip().lower() == 'beban gaji karyawan']
    total = Decimal(0)
    for r, cells in selected:
        print('MATCH', r, cells)
        total += Decimal(cells['H']['value'])
    print('SALARY MATCHES', len(selected), 'SUM H', total)
