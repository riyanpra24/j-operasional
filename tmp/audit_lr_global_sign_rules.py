from zipfile import ZipFile
from xml.etree import ElementTree as ET
import json, re

path=r'C:\Users\Jamkrindo\Downloads\LR SEKANWIL YTD AGUSTUS 2026.xlsx'
ns={'s':'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
target={'laba tahun berjalan','jumlah laba komprehensif tahun berjalan'}
norm=lambda value: re.sub(r'\s+',' ',value).strip().lower()
with ZipFile(path) as z:
    strings=[''.join(t.text or '' for t in item.findall('.//s:t',ns)) for item in ET.fromstring(z.read('xl/sharedStrings.xml')).findall('s:si',ns)]
    rels={rel.get('Id'):rel.get('Target') for rel in ET.fromstring(z.read('xl/_rels/workbook.xml.rels'))}
    for sheet in ET.fromstring(z.read('xl/workbook.xml')).findall('s:sheets/s:sheet',ns):
        rel=sheet.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id'); targetPath=rels[rel]
        targetPath=targetPath.lstrip('/') if targetPath.startswith('/') else 'xl/'+targetPath
        found=[]
        for row in ET.fromstring(z.read(targetPath)).findall('s:sheetData/s:row',ns):
            cells={}
            for cell in row.findall('s:c',ns):
                value=cell.find('s:v',ns); value=value.text if value is not None else ''
                if cell.get('t')=='s': value=strings[int(value)]
                elif cell.get('t')=='inlineStr': value=''.join(t.text or '' for t in cell.findall('.//s:t',ns))
                cells[re.sub(r'\d','',cell.get('r'))]={'value':value,'formula':cell.find('s:f',ns) is not None,'type':cell.get('t')}
            if norm(cells.get('D',{}).get('value','')) in target: found.append({'row':int(row.get('r')),'D':cells['D'],'H':cells.get('H')})
        print(json.dumps({'sheet':sheet.get('name'),'matches':found},ensure_ascii=False))
