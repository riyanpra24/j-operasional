import zipfile, xml.etree.ElementTree as ET, json
path = r'C:\Users\Jamkrindo\Downloads\Template RKA.xlsx'
ns = {'s':'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
with zipfile.ZipFile(path) as z:
    strings=[]
    if 'xl/sharedStrings.xml' in z.namelist():
        root=ET.fromstring(z.read('xl/sharedStrings.xml'))
        strings=[''.join(si.itertext()) for si in root.findall('s:si',ns)]
    root=ET.fromstring(z.read('xl/worksheets/sheet1.xml'))
    cells={}
    for c in root.findall('.//s:sheetData/s:row/s:c',ns):
        v=c.find('s:v',ns); f=c.find('s:f',ns); value=v.text if v is not None else None
        if c.get('t')=='s': value=strings[int(value)]
        elif c.get('t')=='inlineStr': value=''.join(c.find('s:is',ns).itertext())
        cells[c.get('r')]={'value':value,'type':c.get('t'),'formula':f.text if f is not None else None,'formula_attributes':f.attrib if f is not None else None}
    with open('tmp/rka_template/raw.json','w',encoding='utf8') as out: json.dump(cells,out,ensure_ascii=False,indent=2)
    for r in range(1,167):
        label=cells.get(f'B{r}',{}).get('value') or cells.get(f'A{r}',{}).get('value')
        data={col:cells.get(f'{col}{r}') for col in 'CDEFGH' if cells.get(f'{col}{r}',{}).get('value') is not None or cells.get(f'{col}{r}',{}).get('formula')}
        if label or data: print(r,label,json.dumps(data,ensure_ascii=False))
    print('MERGES:',[m.get('ref') for m in root.findall('s:mergeCells/s:mergeCell',ns)])
