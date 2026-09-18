import runpy, contextlib, io, collections, json

with contextlib.redirect_stdout(io.StringIO()):
    source=runpy.run_path('tmp/inspect_lr_salary.py')
rows=source['rows']
print('LOB labels:', json.dumps(dict(collections.Counter(c.get('B',{}).get('value','') for r,c in rows.items() if r>7)),ensure_ascii=False))
for r,c in rows.items():
    if c.get('B',{}).get('value','').strip().lower() in ('non kur','non-kur','nonkur') and c.get('D',{}).get('value','').strip().lower()=='beban gaji karyawan':
        print('NON KUR salary source:', r, c['B']['value'], c['D']['value'].strip(), c.get('H'))
