import zipfile, xml.etree.ElementTree as ET, json, re
from decimal import Decimal, localcontext
from pathlib import Path

path = Path(r'C:\Users\Jamkrindo\Documents\JAKSA\RKA_All Uker Kanwil Surabaya.xlsx')
schema = json.loads(Path('app/Config/RkaTemplate2026.json').read_text())
ns = {'s':'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
norm = lambda x: re.sub(r'\s+', ' ', x.strip()).lower()
formnorm = lambda x: re.sub(r'[\s$=]', '', x).upper()
with zipfile.ZipFile(path) as z, localcontext() as context:
    context.prec = 50
    print('BINARY ENTRIES:', [p for p in z.namelist() if p.lower().endswith('.bin')])
    ss = ET.fromstring(z.read('xl/sharedStrings.xml'))
    strings = [''.join(t.text or '' for t in item.findall('.//s:t',ns)) for item in ss.findall('s:si',ns)]
    book = ET.fromstring(z.read('xl/workbook.xml'))
    rels = ET.fromstring(z.read('xl/_rels/workbook.xml.rels'))
    targets = {r.get('Id'):'xl/'+r.get('Target') for r in rels}
    budgets = {}
    for info in book.findall('s:sheets/s:sheet',ns):
        name = info.get('name')
        relation = info.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id')
        xml = ET.fromstring(z.read(targets[relation]))
        cells = {}; shared = {}
        for c in xml.findall('.//s:sheetData/s:row/s:c',ns):
            address = c.get('r'); v=c.find('s:v',ns); f=c.find('s:f',ns)
            value = v.text if v is not None else ''
            if c.get('t') == 's': value = strings[int(value)]
            elif c.get('t') == 'inlineStr': value = ''.join(t.text or '' for t in c.findall('.//s:is//s:t',ns))
            formula = f.text or '' if f is not None else None
            si = f.get('si') if f is not None and f.get('t') == 'shared' else None
            if si is not None and formula: shared[si] = (address, formula)
            cells[address] = {'value':value or '', 'formula':formula,'shared':si,'type':c.get('t','')}
        for address, c in cells.items():
            if c['shared'] is not None and c['formula'] == '':
                base, formula = shared[c['shared']]
                a = re.fullmatch(r'([A-Z]+)(\d+)', base); b = re.fullmatch(r'([A-Z]+)(\d+)', address)
                # Source shared formulas are single-column F:K references.
                dc = ord(b[1])-ord(a[1]); dr=int(b[2])-int(a[2])
                c['formula'] = re.sub(r'(?<![A-Z])([A-Z])(\d+)',lambda m:chr(ord(m[1])+dc)+str(int(m[2])+dr),formula)
        budgets[name]=cells
        label_errors=[]
        for row, definition in schema['rows'].items():
            label = cells.get('E'+row,{}).get('value','') or cells.get('D'+row,{}).get('value','')
            if norm(label) != norm(definition['label']): label_errors.append((row,label,definition['label']))
        formula_errors=[]; result_errors=[]; missing_formulas=[]; inputs_formula=[]; inputs_precision=[]; values={}
        for row, definition in schema['rows'].items():
            for old, new in zip('CDEFG','FGHIJ'):
                address=new+row; cell=cells.get(address,{'value':'','formula':None})
                raw=cell['value']
                if definition['terms'] is None:
                    values[address]=Decimal(raw or '0')
                    if cell['formula'] is not None: inputs_formula.append((address,cell['formula']))
                    if values[address] != values[address].quantize(Decimal('0.01')): inputs_precision.append((address,raw))
                else:
                    values[address]=sum((values[new+str(t['row'])]*t['coefficient'] for t in definition['terms']),Decimal(0))
                    expected=schema['formulas'].get(old+row)
                    expected=re.sub(r'([C-H])(\d+)',lambda m:chr(ord(m[1])+3)+m[2],expected)
                    if cell['formula'] is None: missing_formulas.append(address)
                    elif formnorm(cell['formula']) != formnorm(expected): formula_errors.append((address,cell['formula'],expected))
                    if raw != '' and Decimal(raw) != values[address]: result_errors.append((address,raw,str(values[address])))
            total=sum((values[c+row] for c in 'FGHIJ'),Decimal(0))
            k=cells.get('K'+row,{}).get('value','')
            if k != '' and Decimal(k) != total: result_errors.append(('K'+row,k,str(total)))
        print(json.dumps({'sheet':name,'heading':cells.get('D2'), 'headers':{c:cells.get(c+'6',{}).get('value') for c in 'FGHIJK'},'label_errors':label_errors[:5],'formula_errors':formula_errors[:5],'missing_formula_count':len(missing_formulas),'input_formula_count':len(inputs_formula),'input_formula_examples':inputs_formula[:4],'precision_errors':inputs_precision[:3],'result_mismatch_count':len(result_errors),'result_examples':result_errors[:5],'profit_KUR':str(values['F166']),'profit_TOTAL':str(sum(values[c+'166'] for c in 'FGHIJ'))},ensure_ascii=False))
    corp = budgets['KORPORAT KANWIL']; children=[v for k,v in budgets.items() if k!='KORPORAT KANWIL']
    differences=[]
    for row in schema['input_rows']:
        for col in 'FGHIJ':
            address=col+str(row)
            actual=Decimal(corp.get(address,{}).get('value','') or '0')
            expected=sum((Decimal(c.get(address,{}).get('value','') or '0') for c in children),Decimal(0))
            if actual != expected: differences.append((address,str(actual),str(expected)))
    print('CORPORATE INPUT RECONCILIATION: ',len(differences),'differences',differences[:5])
