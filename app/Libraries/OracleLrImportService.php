<?php
namespace App\Libraries;
use RuntimeException;
use Throwable;

final class OracleLrImportService
{
    /** Hide every upload revision for one exact unit/month/year period. */
    public function delete(string $unit,int $year,int $month,string $role,string $name): int
    {
        if (!in_array($unit,OracleLrSalaryParser::IMPORT_UNITS,true) || $year<2000 || $year>2100 || $month<1 || $month>12) throw new RuntimeException('Pilihan unit, bulan, dan tahun laporan tidak valid.');
        $db=db_connect(); $db->transBegin();
        try {
            $rows=$db->query('SELECT id FROM accounting_lr_imports WHERE unit_name = ? AND report_year = ? AND report_month = ? AND deleted_at IS NULL ORDER BY id DESC FOR UPDATE',[$unit,$year,$month])->getResultArray();
            if (!$rows) throw new RuntimeException('Data laporan pada bulan dan tahun yang dipilih tidak ditemukan.');
            $ok=$db->table('accounting_lr_imports')->whereIn('id',array_column($rows,'id'))->update([
                'deleted_at'=>date('Y-m-d H:i:s'),'deleted_by_role'=>mb_substr($role,0,80),'deleted_by_name'=>mb_substr($name,0,150),
            ]);
            if (!$ok || !$db->transStatus()) throw new RuntimeException('Laporan belum berhasil dihapus.');
            if (!$db->transCommit()) throw new RuntimeException('Penghapusan laporan belum berhasil disimpan.');
            return count($rows);
        } catch (Throwable $e) { $db->transRollback(); throw $e; }
    }
}
