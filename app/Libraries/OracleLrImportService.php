<?php
namespace App\Libraries;
use RuntimeException;
use Throwable;

final class OracleLrImportService
{
    public const ALL_UNITS = 'all';

    /** Hide every upload revision for one exact unit/month/year period. */
    public function delete(string $unit,int $year,int $month,string $role,string $name,string $basis='YTD'): int
    {
        if (!in_array($unit,OracleLrSalaryParser::IMPORT_UNITS,true) || $year<2000 || $year>2100 || $month<1 || $month>12 || !in_array($basis,LrRealizationService::BASES,true)) throw new RuntimeException('Pilihan jenis, unit, bulan, dan tahun laporan tidak valid.');
        return $this->deleteUnits([$unit],$year,$month,$role,$name,$basis);
    }

    /** Hide every upload revision for all source units and the simulated corporate sheet in one exact month/year period. */
    public function deleteAll(int $year,int $month,string $role,string $name,string $basis='YTD'): int
    {
        if ($year<2000 || $year>2100 || $month<1 || $month>12 || !in_array($basis,LrRealizationService::BASES,true)) throw new RuntimeException('Pilihan jenis, bulan, dan tahun laporan tidak valid.');
        return $this->deleteUnits(array_merge(OracleLrSalaryParser::IMPORT_UNITS,['Korporat Kanwil']),$year,$month,$role,$name,$basis);
    }

    /** @param list<string> $units */
    private function deleteUnits(array $units,int $year,int $month,string $role,string $name,string $basis): int
    {
        $db=db_connect(); $db->transBegin();
        try {
            $placeholders=implode(',',array_fill(0,count($units),'?'));
            $rows=$db->query('SELECT id FROM accounting_lr_imports WHERE unit_name IN ('.$placeholders.') AND report_year = ? AND report_month = ? AND report_basis = ? AND deleted_at IS NULL ORDER BY id DESC FOR UPDATE',[...$units,$year,$month,$basis])->getResultArray();
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
