import runpy, subprocess, json, re

source = runpy.run_path('tmp/inspect_lr_salary.py')
labels = json.loads(subprocess.check_output(['C:/xampp/php/php.exe', '-r', 'require "app/Libraries/LrReportRows.php"; echo json_encode(App\\Libraries\\LrReportRows::expenseLabels());'], text=True))
norm = lambda value: re.sub(r'\s+', ' ', value).strip().lower()
rows = source['rows']
kur = [(r, c) for r, c in rows.items() if norm(c.get('B', {}).get('value', '')) == 'kur']
names = {norm(c.get('D', {}).get('value', '')) for _, c in kur}
print('EXPENSE LABELS', len(labels))
print('EXACT MATCH LABELS', sum(norm(label) in names for label in labels))
print('NO EXACT MATCH:', json.dumps([label for label in labels if norm(label) not in names], ensure_ascii=False))
print('SOURCE EXTRA NAMES:', json.dumps(sorted(names - {norm(label) for label in labels}), ensure_ascii=False))
