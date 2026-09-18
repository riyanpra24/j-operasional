<?php
namespace App\Libraries;

/** Shared report structure: display labels and approved expense matching. */
final class LrReportRows
{
    public static function rows(): array
    {
        return [
    ['label' => 'VOLUME', 'type' => 'major'],
    ['label' => 'PENDAPATAN PENJAMINAN', 'value_label' => 'IMBAL JASA PENJAMINAN BERSIH', 'type' => 'group', 'key' => 'pendapatan-penjaminan', 'details' => [
        ['label' => 'Imbal Jasa Penjaminan Bruto', 'type' => 'detail'],
        ['label' => 'Restitusi penjaminan kredit', 'type' => 'detail'],
        ['label' => 'Premi Penjaminan Ulang', 'type' => 'detail'],
        ['label' => 'IMBAL JASA PENJAMINAN BERSIH', 'type' => 'detail-total'],
    ]],
    ['label' => 'BEBAN KLAIM', 'value_label' => 'JUMLAH BEBAN KLAIM', 'type' => 'group', 'key' => 'beban-klaim', 'details' => [
        ['label' => 'Beban Klaim', 'type' => 'detail'],
        ['label' => 'Kenaikan (Penurunan) Cadangan Klaim', 'type' => 'detail'],
        ['label' => 'Pendapatan Subrogasi', 'type' => 'detail'],
        ['label' => 'Beban Komisi Netto', 'type' => 'detail'],
        ['label' => 'JUMLAH BEBAN KLAIM', 'type' => 'detail-total'],
    ]],
    ['label' => 'PENJAMINAN BERSIH', 'type' => 'major'],
    ['label' => 'PENDAPATAN INVESTASI BERSIH', 'type' => 'major'],
    ['label' => 'BEBAN USAHA', 'type' => 'major'],
    ['label' => 'BEBAN KARYAWAN', 'value_label' => 'Total Beban Karyawan', 'type' => 'subgroup', 'key' => 'beban-karyawan', 'details' => [
        ['label' => 'Beban gaji karyawan', 'type' => 'detail'],
        ['label' => 'Beban lembur karyawan', 'type' => 'detail'],
        ['label' => 'Beban asuransi kesehatan karyawan', 'type' => 'detail'],
        ['label' => 'Beban asuransi jiwa karyawan', 'type' => 'detail'],
        ['label' => 'Beban dana pensiun', 'type' => 'detail'],
        ['label' => 'Beban kompensasi kinerja karyawan', 'type' => 'detail'],
        ['label' => 'Biaya jasa kini karyawan', 'type' => 'detail'],
        ['label' => 'Biaya jasa lalu karyawan', 'type' => 'detail'],
        ['label' => 'Bunga atas imbalan pasca kerja karyawan', 'type' => 'detail'],
        ['label' => 'Beban BPJS kesehatan karyawan', 'type' => 'detail'],
        ['label' => 'Beban BPJS jaminan hari tua karyawan', 'type' => 'detail'],
        ['label' => 'Beban BPJS jaminan kecelakaan kerja dan jaminan kematian karyawan', 'type' => 'detail'],
        ['label' => 'Beban BPJS jaminan pensiun karyawan', 'type' => 'detail'],
        ['label' => 'Beban tunjangan hari raya karyawan', 'type' => 'detail'],
        ['label' => 'Beban tunjangan PPh 21 karyawan', 'type' => 'detail'],
        ['label' => 'Beban tunjangan transportasi karyawan', 'type' => 'detail'],
        ['label' => 'Beban tunjangan jabatan', 'type' => 'detail'],
        ['label' => 'Beban tunjangan cuti besar', 'type' => 'detail'],
        ['label' => 'Beban tunjangan cuti tahunan', 'type' => 'detail'],
        ['label' => 'Beban tunjangan makan', 'type' => 'detail'],
        ['label' => 'Beban Tunjangan Pakaian Kerja', 'type' => 'detail'],
        ['label' => 'Beban kompensasi PKWT/PKWTT', 'type' => 'detail'],
        ['label' => 'Beban tunjangan lainnya', 'type' => 'detail'],
        ['label' => 'Total Beban Karyawan', 'type' => 'detail-total'],
    ]],
    ['label' => 'BEBAN ADMINISTRASI & UMUM', 'value_label' => 'Total Beban Administrasi & Umum', 'type' => 'subgroup', 'key' => 'beban-administrasi-umum', 'details' => array_map(
        static fn (string $label): array => [
            'label' => $label,
            'type' => $label === 'Total Beban Administrasi & Umum' ? 'detail-total' : 'detail',
        ],
        [
            'Imbal jasa profesional - akuntan publik',
            'Imbal jasa profesional - konsultan hukum',
            'Imbal jasa profesional - konsultan manajemen',
            'Beban imbal jasa profesional IT',
            'Imbal jasa profesional - lainnya',
            'Air, listrik dan utilitas - gedung',
            'Air, listrik dan utilitas - rumah dinas',
            'Pemasaran',
            'Beban pelatihan manajemen',
            'Beban pelatihan karyawan - insidentil',
            'Beban pelatihan karyawan - leadership',
            'Beban pelatihan karyawan - TNA',
            'Beban sertifikasi',
            'Beban perekrutan karyawan',
            'Beban uang saku magang',
            'Akomodasi dinas dalam negeri',
            'Transportasi dinas dalam negeri',
            'Tunjangan perjalanan dinas dalam negeri',
            'Akomodasi dinas luar negeri',
            'Transportasi dinas luar negeri',
            'Tunjangan perjalanan dinas luar negeri',
            'Percetakan dan inventaris kantor',
            'Telepon, fax dan komunikasi - gedung',
            'Telepon, fax dan komunikasi - rumah dinas',
            'Bensin dan parkir',
            'Beban pemeliharaan kendaraan',
            'Beban penggunaan Data Center',
            'Beban jaringan dan konektivitas',
            'Beban penyewaan peralatan IT',
            'Beban pemeliharaan peralatan IT',
            'Beban pemeliharaan perangkat lunak IT',
            'Beban pemeliharaan gedung',
            'Beban pemeliharaan rumah dinas',
            'Beban pemeliharaan mesin dan peralatan',
            'Beban pemeliharaan renovasi dan instalasi',
            'Beban aset bernilai rendah',
            'Beban pemeliharaan lainnya',
            'Beban pemeliharaan gedung sewa',
            'Beban pemeliharaan gudang sewa',
            'Beban pemeliharaan rumah dinas sewa',
            'Beban pemeliharaan peralatan sewa',
            'Beban pemeliharaan aset sewa lainnya',
            'Sewa kantor',
            'Sewa gudang',
            'Sewa kendaraan',
            'Beban pemeliharaan kendaraan sewa',
            'Sewa peralatan',
            'Sewa rumah dinas',
            'Biaya service charge',
            'Sewa lainnya',
            'Beban CKPN',
            'Beban pengumpulan informasi dan pengelolaan data',
            'Beban pengelolaan dana dan investasi',
            'Beban penelitian dan pengembangan',
            'Beban sosialisasi dan rekonsiliasi',
            'Beban eksploitasi',
            'Beban asuransi',
            'Beban iuran keanggotaan',
            'Beban pajak lainnya',
            'Denda pajak',
            'PPN Masukan yang dibebankan',
            'Pungutan OJK',
            'Pajak bumi dan bangunan',
            'Beban TJSL - Pilar Sosial',
            'Beban TJSL - Pilar Lingkungan',
            'Beban TJSL - Pilar Ekonomi',
            'Beban TJSL - Pilar Hukum dan Tata Kelola',
            'Beban sponsorship event',
            'Beban sumbangan',
            'Beban akomodasi rapat',
            'Beban konsumsi rapat',
            'Beban honorarium peserta rapat',
            'Beban jasa pelaksanaan rapat',
            'Beban representasi manajemen',
            'Beban representasi perusahaan lainnya',
            'Biaya admin bank',
            'Biaya transfer',
            'Retribusi keamanan',
            'Retribusi kebersihan',
            'Beban pajak dan retribusi daerah',
            'Beban umum dan administrasi lainnya',
            'Beban lainnya',
            'Biaya kegiatan internal',
            'Biaya komunitas',
            'Biaya komunikasi dan kampanye',
            'Selisih biaya invoice',
            'Beban retribusi dan kontribusi lainnya',
            'Total Beban Administrasi & Umum',
        ]
    )],
    ['label' => 'BEBAN PENYUSUTAN & AMORTISASI', 'value_label' => 'Total Beban Penyusutan & Amortisasi', 'type' => 'subgroup', 'key' => 'beban-penyusutan-amortisasi', 'details' => [
        ['label' => 'Beban penyusutan aset tetap - gedung', 'type' => 'detail'],
        ['label' => 'Beban penyusutan aset tetap - rumah dinas', 'type' => 'detail'],
        ['label' => 'Beban penyusutan aset tetap - kendaraan', 'type' => 'detail'],
        ['label' => 'Beban penyusutan aset tetap - mesin dan peralatan', 'type' => 'detail'],
        ['label' => 'Beban penyusutan aset tetap - renovasi dan instalasi', 'type' => 'detail'],
        ['label' => 'Beban bunga liabilitas sewa - gedung', 'type' => 'detail'],
        ['label' => 'Beban bunga liabilitas sewa - rumah dinas', 'type' => 'detail'],
        ['label' => 'Beban bunga liabilitas sewa - kendaraan', 'type' => 'detail'],
        ['label' => 'Beban bunga liabilitas sewa - peralatan', 'type' => 'detail'],
        ['label' => 'Beban penyusutan hak guna sewa - gedung', 'type' => 'detail'],
        ['label' => 'Beban penyusutan hak guna sewa - rumah dinas', 'type' => 'detail'],
        ['label' => 'Beban penyusutan hak guna sewa - kendaraan', 'type' => 'detail'],
        ['label' => 'Beban penyusutan hak guna sewa - peralatan', 'type' => 'detail'],
        ['label' => 'Beban penyusutan hak guna sewa - gudang', 'type' => 'detail'],
        ['label' => 'Beban amortisasi aset tidak berwujud - perangkat lunak', 'type' => 'detail'],
        ['label' => 'Total Beban Penyusutan & Amortisasi', 'type' => 'detail-total'],
    ]],
    ['label' => 'TOTAL BEBAN USAHA', 'type' => 'subtotal'],
    ['label' => 'PENDAPATAN (BEBAN) LAIN-LAIN BERSIH', 'value_label' => 'PENDAPATAN (BEBAN) LAIN-LAIN BERSIH', 'type' => 'group', 'key' => 'pendapatan-beban-lain', 'details' => [
        ['label' => 'Pendapatan jasa giro', 'type' => 'detail'],
        ['label' => 'Pendapatan lainnya', 'type' => 'detail'],
    ]],
    ['label' => 'LABA SEBELUM PAJAK', 'type' => 'profit'],
];
    }

    public static function expenseLabels(): array
    {
        $labels=[];
        foreach (self::rows() as $row) {
            if (!in_array($row['key']??'', ['beban-klaim','beban-karyawan','beban-administrasi-umum','beban-penyusutan-amortisasi'],true)) continue;
            foreach ($row['details'] as $detail) if ($detail['type']==='detail') $labels[]=$detail['label'];
        }
        return $labels;
    }

    public static function expenseMap(): array
    {
        $map=[];
        foreach (self::expenseLabels() as $label) $map[OracleLrSalaryParser::normalizeLabel($label)]=$label;
        // Verified spelling in the supplied Oracle KANWIL export, not fuzzy matching.
        $map['tranportasi dinas dalam negeri']='Transportasi dinas dalam negeri';
        $map['tranportasi dinas luar negeri']='Transportasi dinas luar negeri';
        return $map;
    }

    /** Cabang belum memakai aturan umum untuk volume, pendapatan penjaminan, atau beban klaim. */
    public static function branchExpenseLabels(): array
    {
        $labels=[];
        foreach (self::rows() as $row) {
            if (!in_array($row['key']??'', ['beban-karyawan','beban-administrasi-umum','beban-penyusutan-amortisasi'],true)) continue;
            foreach ($row['details'] as $detail) if ($detail['type']==='detail') $labels[]=$detail['label'];
        }
        return $labels;
    }

    public static function branchExpenseMap(): array
    {
        $map=[];
        foreach (self::branchExpenseLabels() as $label) $map[OracleLrSalaryParser::normalizeLabel($label)]=$label;
        $map['tranportasi dinas dalam negeri']='Transportasi dinas dalam negeri';
        $map['tranportasi dinas luar negeri']='Transportasi dinas luar negeri';
        return $map;
    }

    public static function branchSourceLabels(): array
    {
        return array_merge(self::branchExpenseLabels(),['Pendapatan jasa giro','Pendapatan lainnya']);
    }

    public static function branchSourceMap(): array
    {
        $map=[];
        foreach (self::branchSourceLabels() as $label) $map[OracleLrSalaryParser::normalizeLabel($label)]=$label;
        $map['tranportasi dinas dalam negeri']='Transportasi dinas dalam negeri';
        $map['tranportasi dinas luar negeri']='Transportasi dinas luar negeri';
        return $map;
    }

    /**
     * Source COA whose wording differs from the report row.
     * The source amount is a credit balance, so every branch segment uses the
     * inverted value. A missing source remains missing; it is never fabricated
     * as a zero-valued PEN row.
     */
    public static function branchFormulaSourceMappings(): array
    {
        return [
            [
                'source_description' => 'Pendapatan premi penjaminan kredit',
                'report_label' => 'Imbal Jasa Penjaminan Bruto',
                'sign_mode' => 'invert',
                'segments' => ['KUR', 'NON KUR', 'PEN'],
                'product_sign_mode' => 'invert',
            ],
            [
                // Preserve the exact Oracle spelling; do not fuzzy-match other tax accounts.
                'source_description' => 'Beban Pajak PPh 21 Non Karywan',
                'report_label' => 'Pendapatan Subrogasi',
                'sign_mode' => 'invert',
                'segments' => ['NON KUR'],
            ],
            [
                'source_description' => 'Restitusi penjaminan kredit',
                'report_label' => 'Restitusi penjaminan kredit',
                'sign_mode' => 'invert',
                'segments' => ['KUR', 'NON KUR', 'PEN'],
                'product_sign_mode' => 'invert',
            ],
            [
                'source_description' => 'Pembayaran premi penjaminan ulang keluar',
                'report_label' => 'Premi Penjaminan Ulang',
                'sign_mode' => 'keep',
                'segments' => ['KUR', 'NON KUR', 'PEN'],
                'product_sign_mode' => 'keep',
            ],
            [
                'source_description' => 'Beban klaim penjaminan kredit bruto',
                'report_label' => 'Beban Klaim',
                'sign_mode' => 'keep',
                'segments' => ['KUR', 'NON KUR', 'PEN'],
                'product_sign_mode' => 'keep',
            ],
            [
                'source_description' => 'Penerimaan klaim penjaminan ulang',
                'report_label' => 'Beban Klaim',
                'sign_mode' => 'keep',
                'segments' => ['KUR', 'NON KUR', 'PEN'],
                'product_sign_mode' => 'keep',
            ],
            [
                'source_description' => 'Kenaikan/penurunan estimasi liabilitas klaim - penjaminan',
                'report_label' => 'Kenaikan (Penurunan) Cadangan Klaim',
                'sign_mode' => 'keep',
                'segments' => ['KUR', 'NON KUR', 'PEN'],
                'product_zero' => true,
            ],
            [
                'source_description' => 'Hak subrogasi penjaminan kredit - bruto',
                'report_label' => 'Pendapatan Subrogasi',
                'sign_mode' => 'invert',
                'segments' => ['KUR', 'NON KUR', 'PEN'],
                'product_sign_mode' => 'invert',
            ],
            [
                'source_description' => 'Beban penagihan subrogasi',
                'report_label' => 'Pendapatan Subrogasi',
                'sign_mode' => 'invert',
                'segments' => ['KUR', 'NON KUR', 'PEN'],
                'product_sign_mode' => 'keep',
            ],
            [
                'source_description' => 'Pendapatan komisi penjaminan ulang diterima',
                'report_label' => 'Beban Komisi Netto',
                'sign_mode' => 'invert',
                'segments' => ['KUR', 'NON KUR', 'PEN'],
                'product_sign_mode' => 'invert',
            ],
            [
                'source_description' => 'Komisi penjaminan kredit dibayar',
                'report_label' => 'Beban Komisi Netto',
                'sign_mode' => 'invert',
                'segments' => ['KUR', 'NON KUR', 'PEN'],
                'product_sign_mode' => 'invert',
            ],
        ];
    }

    public static function branchFormulaSourceMap(): array
    {
        $map = [];
        foreach (self::branchFormulaSourceMappings() as $mapping) {
            $map[OracleLrSalaryParser::normalizeLabel((string) $mapping['source_description'])] = $mapping;
        }
        return $map;
    }

    /** Null means an ordinary mapping; a list means this formula source is segment-restricted. */
    public static function branchFormulaSourceSegments(string $sourceDescription): ?array
    {
        $source = OracleLrSalaryParser::normalizeLabel($sourceDescription);
        foreach (self::branchFormulaSourceMappings() as $mapping) {
            if ($source === OracleLrSalaryParser::normalizeLabel((string) $mapping['source_description'])) {
                return $mapping['segments'] ?? null;
            }
        }
        return null;
    }

    /** NON KUR amounts on these rows are allocated using the premium product mix. */
    public static function allocatedProductLabels(): array
    {
        return array_values(array_unique(array_merge(
            self::branchExpenseLabels(),
            ['Pendapatan jasa giro', 'Pendapatan lainnya']
        )));
    }

    public static function sourceLabels(): array
    {
        return array_merge(self::expenseLabels(),['Pendapatan jasa giro','Pendapatan lainnya']);
    }

    public static function sourceMap(): array
    {
        $map=[];
        foreach (self::sourceLabels() as $label) $map[OracleLrSalaryParser::normalizeLabel($label)]=$label;
        $map['tranportasi dinas dalam negeri']='Transportasi dinas dalam negeri';
        $map['tranportasi dinas luar negeri']='Transportasi dinas luar negeri';
        return $map;
    }

    /** Detail rows that may receive a direct Oracle COA mapping. */
    public static function mappableLabels(): array
    {
        $labels = [];
        foreach (self::rows() as $row) {
            foreach ($row['details'] ?? [] as $detail) {
                if (($detail['type'] ?? '') === 'detail') $labels[] = $detail['label'];
            }
        }
        return array_values(array_unique($labels));
    }

    /** Every reader-facing row that may be used as a formula result or component. */
    public static function valueLabels(): array
    {
        $labels = [];
        foreach (self::rows() as $row) {
            if (isset($row['details'])) {
                foreach ($row['details'] as $detail) $labels[] = (string) $detail['label'];
                if (isset($row['value_label'])) $labels[] = (string) $row['value_label'];
                continue;
            }
            if (in_array($row['type'] ?? '', ['major', 'subtotal', 'profit'], true)) {
                if (($row['label'] ?? '') !== 'BEBAN USAHA') $labels[] = (string) $row['label'];
            }
        }
        return array_values(array_unique($labels));
    }
}
