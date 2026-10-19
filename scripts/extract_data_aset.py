"""Extract detail rows, excluding headings and totals, from the supplied workbook."""
import json
import sys
from datetime import datetime
from pathlib import Path

import openpyxl

source, target = map(Path, sys.argv[1:3])
book = openpyxl.load_workbook(source, data_only=True, read_only=True)
sheet = book['JKI_FA_Daftar_Aset_Tetap_Beser_']
groups = {'Gedung', 'Kendaraan', 'Mesin', 'Perabot', 'Peralatan', 'Peralatan Teknologi Informasi', 'Renovasi dan Instalasi', 'Rumah Dinas', 'Tanah'}
group = None
records = []
for row in sheet.iter_rows(min_row=10, values_only=True):
    if row[0] in groups and row[1] is None and row[2] is None:
        group = row[0]
    if row[1] is None or row[2] is None:
        continue
    if group is None or not isinstance(row[7], datetime) or not isinstance(row[8], datetime):
        raise ValueError('Detail aset memiliki kelompok atau tanggal yang tidak valid')
    records.append({'kategori': group, 'kelompok_aset': str(row[0]).strip(), 'kodefikasi_aset': str(row[1]).strip(), 'nomor_aset': str(row[2]).strip(), 'nama_aset': str(row[4]).strip(), 'status_aset': str(row[6]).strip(), 'tanggal_perolehan': row[7].date().isoformat(), 'tanggal_habis_manfaat': row[8].date().isoformat(), 'masa_manfaat': row[12], 'sisa_masa_manfaat': row[13], 'lokasi': str(row[24]).strip() if row[24] is not None else None})
if len(records) != 296 or len({r['nomor_aset'] for r in records}) != len(records):
    raise ValueError('Jumlah detail atau keunikan nomor aset tidak sesuai')
target.write_text(json.dumps(records, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
print(f'{len(records)} detail aset siap diimpor; baris jumlah tidak disertakan.')
