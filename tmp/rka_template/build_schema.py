import json,re
from decimal import Decimal
raw=json.load(open('tmp/rka_template/raw.json',encoding='utf8'))
shared={}
def pos(cell):
    m=re.fullmatch(r'([A-Z]+)(\d+)',cell); return ord(m[1])-64,int(m[2])
for cell,data in raw.items():
    attrs=data.get('formula_attributes') or {}
    if attrs.get('t')=='shared' and data['formula']: shared[attrs['si']]=(cell,data['formula'])
def formula(cell):
    data=raw.get(cell,{})
    if data.get('formula'): return data['formula']
    attrs=data.get('formula_attributes') or {}
    if attrs.get('t')=='shared':
        base,text=shared[attrs['si']]; col,row=pos(cell); bc,br=pos(base)
        return re.sub(r'([A-Z]+)(\d+)',lambda m:chr(ord(m[1])+col-bc)+str(int(m[2])+row-br),text)
    return None
def label(row): return raw.get(f'B{row}',{}).get('value') or raw.get(f'A{row}',{}).get('value')
input_rows=[8,11,12,13,17,18,19,20,25]+list(range(29,52))+list(range(55,142))+list(range(145,160))+[164]
derived={14:[(11,1),(12,-1),(13,-1)],21:[(17,1),(18,1),(19,-1),(20,-1)],23:[(14,1),(21,-1)],52:[(r,1) for r in range(29,52)],142:[(r,1) for r in range(55,142)],160:[(r,1) for r in range(145,160)],162:[(52,1),(142,1),(160,1)],166:[(23,1),(25,1),(162,-1),(164,1)]}
rows={str(r):{'label':label(r),'terms':[{'row':a,'coefficient':b} for a,b in derived[r]] if r in derived else None} for r in sorted(input_rows+list(derived))}
formulas={cell:formula(cell) for cell in raw if formula(cell)}
cells={}
errors=[]
for r in sorted(input_rows+list(derived)):
    for col in 'CDEFG':
        cell=f'{col}{r}'
        value=sum((cells[f'{col}{row}']*co for row,co in derived[r]),Decimal(0)) if r in derived else Decimal(raw[cell]['value'])
        cells[cell]=value
    cells[f'H{r}']=sum((cells[f'{col}{r}'] for col in 'CDEFG'),Decimal(0))
for cell,text in formulas.items():
    if cells[cell]!=Decimal(raw[cell]['value']): errors.append((cell,str(cells[cell]),raw[cell]['value']))
schema={'version':'rka-2026-v1','source':'Template RKA.xlsx','sheet':'Sheet1','input_rows':input_rows,'rows':rows,'formulas':formulas,'columns':{'C':'KUR','D':'PEN','E':'KBG/SURETYSHIP','F':'KONSUMTIF','G':'PRODUKTIF','H':'TOTAL'},'groups':[{'row':8,'type':'major'},{'row':10,'label':label(10),'value_row':14,'type':'group','key':'pendapatan-penjaminan','details':list(range(11,15))},{'row':16,'label':label(16),'value_row':21,'type':'group','key':'beban-klaim','details':list(range(17,22))},{'row':23,'type':'major'},{'row':25,'type':'major'},{'row':27,'label':label(27),'value_row':162,'type':'major'},{'row':28,'label':label(28),'value_row':52,'type':'subgroup','key':'beban-karyawan','details':list(range(29,53))},{'row':54,'label':label(54),'value_row':142,'type':'subgroup','key':'beban-administrasi-umum','details':list(range(55,143))},{'row':144,'label':label(144),'value_row':160,'type':'subgroup','key':'beban-penyusutan-amortisasi','details':list(range(145,161))},{'row':162,'type':'subtotal'},{'row':164,'type':'major'},{'row':166,'type':'profit'}]}
with open('tmp/rka_template/schema.json','w',encoding='utf8') as f: json.dump(schema,f,ensure_ascii=False,indent=2)
with open('tmp/rka_template/expected.json','w',encoding='utf8') as f: json.dump({cell:f'{value:.2f}' for cell,value in cells.items()},f,indent=2)
print('Inputs:',len(input_rows)*5,'Calculated Excel cells verified:',len(formulas),'Errors:',errors)
print('Key totals:',{f'H{r}':str(cells[f'H{r}']) for r in [14,21,23,52,142,160,162,166]})
print('Formula-free totals:',{cell:raw[cell]['value'] for cell in ['H25','H164']})
assert not errors
