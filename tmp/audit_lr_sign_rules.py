import runpy, contextlib, io, json, re

with contextlib.redirect_stdout(io.StringIO()):
    source=runpy.run_path('tmp/inspect_lr_salary.py')
target={'pendapatan jasa giro','pendapatan lainnya','laba sebelum pajak'}
norm=lambda value: re.sub(r'\s+',' ',value).strip().lower()
for row,cells in source['rows'].items():
    if norm(cells.get('D',{}).get('value','')) in target:
        print(json.dumps({'row':row,**{column:cells.get(column) for column in ('B','C','D','H')}},ensure_ascii=False))
