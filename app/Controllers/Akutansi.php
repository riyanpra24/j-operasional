<?php

namespace App\Controllers;

use App\Libraries\RkaBudgetService;
use App\Libraries\RkaCalculator;
use App\Libraries\RkaWorkbookParser;
use App\Libraries\OracleLrSalaryParser;
use App\Libraries\OracleLrImportService;
use App\Libraries\OracleLrMappingService;
use App\Libraries\LrReportRows;
use App\Libraries\LrRealizationService;
use App\Libraries\LrFormulaService;
use App\Libraries\LrSourceAdjustmentService;
use App\Libraries\LrDocumentExportService;
use App\Libraries\LrWorkpaperSimulationService;
use App\Libraries\LrWorkpaperImportParser;
use App\Models\AccountingLrImportModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;
use Throwable;

class Akutansi extends BaseController
{
    public function labaRugi(): string
    {
        $unit = $this->request->getGet('unit_kerja');
        $unit = is_string($unit) && in_array($unit, RkaCalculator::UNITS, true) ? $unit : 'Korporat Kanwil';
        $year = $this->request->getGet('tahun');
        $year = is_string($year) && preg_match('/^\d{4}$/D', $year) && (int)$year >= 2000 && (int)$year <= 2100
            ? (int)$year : 2026;
        $basis=$this->request->getGet('jenis_laporan');
        $basis=is_string($basis) && in_array(strtoupper($basis),LrRealizationService::BASES,true) ? strtoupper($basis) : 'YTD';
        $requestedMonth=$this->request->getGet('bulan');
        $requestedMonth=is_string($requestedMonth) && preg_match('/^(?:[1-9]|1[0-2])$/D',$requestedMonth) ? (int)$requestedMonth : null;
        $selectedLobs=self::reportLobs($this->request->getGet('lob'));
        $report=(new LrRealizationService())->view($unit,$year,$basis,$requestedMonth);
        $import=$report['import']; $result=$report['result']; $reportValues=$report['values'];
        $flashedUploadMonth=session()->getFlashdata('lr_upload_month');
        $uploadMonth=is_int($flashedUploadMonth) && $flashedUploadMonth>=1 && $flashedUploadMonth<=12
            ? $flashedUploadMonth : (int)$report['month'];
        return view('akutansi/laba_rugi', [
            'title' => 'Laporan Laba / Rugi ('.$basis.')',
            'reportUnits' => RkaCalculator::UNITS, 'selectedUnit' => $unit,
            'selectedYear' => $year, 'selectedBasis'=>$basis, 'selectedMonth'=>(int)$report['month'], 'selectedLobs'=>$selectedLobs,
            'lrUploadMonth'=>$uploadMonth,
            'lrImport'=>$import,'lrResult'=>$result,'reportValues'=>$reportValues,
            'lrUploadError'=>session()->getFlashdata('lr_upload_error'),
            'isAdmin'=>$this->currentRoleIsAdmin(),
        ]);
    }

    public function importLabaRugi(): RedirectResponse
    {
        $year=2026; $month=(int)date('n'); $basis='YTD'; $storedPath=null; $redirectUnit='Kanwil'; $selectedLobs=self::reportLobs($this->request->getPost('lob'));
        try {
            $postedUnit=$this->request->getPost('unit_kerja');
            if (is_string($postedUnit) && in_array($postedUnit,OracleLrSalaryParser::IMPORT_UNITS,true)) $redirectUnit=$postedUnit;
            $postedYear=$this->request->getPost('tahun');
            if (!is_string($postedYear) || !preg_match('/^\d{4}$/D',$postedYear) || (int)$postedYear<2000 || (int)$postedYear>2100) throw new RuntimeException('Pilih tahun laporan yang valid.');
            $year=(int)$postedYear;
            $postedMonth=$this->request->getPost('bulan');
            if (!is_string($postedMonth) || !preg_match('/^(?:[1-9]|1[0-2])$/D',$postedMonth)) throw new RuntimeException('Pilih bulan laporan yang valid.');
            $month=(int)$postedMonth;
            $postedBasis=$this->request->getPost('jenis_laporan');
            if (!is_string($postedBasis) || !in_array(strtoupper($postedBasis),LrRealizationService::BASES,true)) throw new RuntimeException('Pilih jenis laporan YTD atau PTD yang valid.');
            $basis=strtoupper($postedBasis);
            $file=$this->request->getFile('lr_excel');
            if ($file===null || !$file->isValid() || $file->hasMoved() || strtolower($file->getClientExtension())!=='xlsx' || $file->getSize()>5*1024*1024) throw new RuntimeException('Pilih Excel .xlsx yang valid, maksimal 5 MB.');
            $parsedByUnit=(new LrWorkpaperImportParser())->parse($file->getTempName(),$year,$month,$basis);
            $hash=hash_file('sha256',$file->getTempName());
            $name=mb_substr(basename($file->getClientName()),0,255);
            $relative='uploads/laba_rugi/'.bin2hex(random_bytes(16)).'.xlsx';
            $directory=WRITEPATH.'uploads/laba_rugi';
            if (!is_dir($directory) && !mkdir($directory,0770,true) && !is_dir($directory)) throw new RuntimeException('Penyimpanan berkas belum tersedia.');
            $file->move($directory,basename($relative)); $storedPath=WRITEPATH.$relative;
            $db=db_connect(); $db->transBegin();
            try {
                $createdAt=date('Y-m-d H:i:s');
                foreach ($parsedByUnit as $unit=>$parsed) {
                    $ok=$db->table('accounting_lr_imports')->insert([
                        'unit_name'=>$unit,'report_year'=>$year,'report_month'=>$month,'report_basis'=>$basis,
                        'rule_version'=>LrWorkpaperImportParser::RULE,'result_json'=>json_encode($parsed,JSON_THROW_ON_ERROR),
                        'source_name'=>$name,'source_hash'=>$hash,'source_path'=>$relative,
                        'created_by_name'=>session()->get('auth_display_name') ?: null,'created_at'=>$createdAt,
                    ]);
                    if (!$ok) throw new RuntimeException('Hasil laporan unit kerja belum berhasil disimpan.');
                }
                if (!$db->transStatus() || !$db->transCommit()) throw new RuntimeException('Hasil laporan unit kerja belum berhasil disimpan.');
            } catch (Throwable $exception) {
                $db->transRollback(); throw $exception;
            }
            $storedPath=null; // Successfully persisted source must remain even if redirect fails.
            return redirect()->to(self::labaRugiUrl($redirectUnit,$year,$month,$basis,$selectedLobs))
                ->with('success','Hasil Simulasi Hitung '.$basis.' periode '.self::monthName($month).' '.$year.' berhasil disalin ke Laporan Laba / Rugi untuk Korporat Kanwil dan enam unit kerja. Angka hasil Kertas Kerja dipakai apa adanya.');
        } catch (Throwable $exception) {
            // Only the new randomized private upload is removed on failed insert.
            if ($storedPath!==null && is_file($storedPath)) unlink($storedPath);
            log_message('warning','Upload laporan laba rugi gagal: {message}',['message'=>$exception->getMessage()]);
            $known=$exception instanceof \InvalidArgumentException || get_class($exception)===RuntimeException::class;
            return redirect()->to(self::labaRugiUrl($redirectUnit,$year,$month,$basis,$selectedLobs))
                ->with('lr_upload_error',$known ? $exception->getMessage() : 'Upload belum berhasil. Data sebelumnya tidak diubah.')
                ->with('lr_upload_month',$month);
        }
    }

    private static function monthName(int $month): string
    {
        return [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
            7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'][$month] ?? 'Tidak valid';
    }

    /** @return list<string> */
    private static function reportLobs(mixed $value): array
    {
        if (is_string($value)) $value=[$value];
        if (!is_array($value)) return LrRealizationService::LOB_COLUMNS;
        $selected=[];
        foreach (LrRealizationService::LOB_COLUMNS as $lob) if (in_array($lob,$value,true)) $selected[]=$lob;
        return $selected ?: LrRealizationService::LOB_COLUMNS;
    }

    /** @return list<string> */
    private static function rkaReportLobs(mixed $value): array
    {
        $all=array_values(array_filter(RkaCalculator::schema()['columns'],static fn (string $label): bool=>$label!=='TOTAL'));
        if (is_string($value)) $value=[$value];
        if (!is_array($value)) return $all;
        $selected=[];
        foreach ($all as $lob) if (in_array($lob,$value,true)) $selected[]=$lob;
        return $selected ?: $all;
    }

    /** @param list<string> $lobs */
    private static function labaRugiUrl(string $unit,int $year,int $month,string $basis,array $lobs): string
    {
        return site_url('akutansi/laba-rugi?'.http_build_query(['jenis_laporan'=>$basis,'unit_kerja'=>$unit,'bulan'=>$month,'tahun'=>$year,'lob'=>$lobs]));
    }

    public function deleteLabaRugi(): RedirectResponse
    {
        $unit='Kanwil'; $year=2026; $month=(int)date('n'); $basis='YTD'; $selectedLobs=self::reportLobs($this->request->getPost('lob'));
        try {
            $postedUnit=$this->request->getPost('unit_kerja'); $postedYear=$this->request->getPost('tahun'); $postedMonth=$this->request->getPost('bulan'); $postedBasis=$this->request->getPost('jenis_laporan');
            if (!is_string($postedUnit) || (!in_array($postedUnit,OracleLrSalaryParser::IMPORT_UNITS,true) && $postedUnit!==OracleLrImportService::ALL_UNITS)
                || !is_string($postedYear) || !preg_match('/^\d{4}$/D',$postedYear) || (int)$postedYear<2000 || (int)$postedYear>2100
                || !is_string($postedMonth) || !preg_match('/^(?:[1-9]|1[0-2])$/D',$postedMonth)
                || !is_string($postedBasis) || !in_array(strtoupper($postedBasis),LrRealizationService::BASES,true)) throw new RuntimeException('Pilihan jenis laporan, unit, bulan, dan tahun tidak valid.');
            $unit=$postedUnit; $year=(int)$postedYear; $month=(int)$postedMonth; $basis=strtoupper($postedBasis);
            if ($this->request->getPost('confirm_delete')!=='1') throw new RuntimeException('Konfirmasi penghapusan laporan terlebih dahulu.');
            $service=new OracleLrImportService();
            if ($unit===OracleLrImportService::ALL_UNITS) $service->deleteAll($year,$month,(string)session()->get('auth_role'),(string)session()->get('auth_display_name'),$basis);
            else $service->delete($unit,$year,$month,(string)session()->get('auth_role'),(string)session()->get('auth_display_name'),$basis);
            $redirectUnit=$unit===OracleLrImportService::ALL_UNITS?'Korporat Kanwil':$unit;
            $unitLabel=$unit===OracleLrImportService::ALL_UNITS?'seluruh unit kerja':$unit;
            return redirect()->to(self::labaRugiUrl($redirectUnit,$year,$month,$basis,$selectedLobs))->with('success','Data laporan '.$basis.' '.$unitLabel.' periode '.self::monthName($month).' '.$year.' dipindahkan ke Data Terhapus.');
        } catch (Throwable $e) {
            log_message('warning','Hapus laporan laba rugi gagal: {message}',['message'=>$e->getMessage()]);
            $redirectUnit=$unit===OracleLrImportService::ALL_UNITS?'Korporat Kanwil':$unit;
            return redirect()->to(self::labaRugiUrl($redirectUnit,$year,$month,$basis,$selectedLobs))->with('error',get_class($e)===RuntimeException::class ? $e->getMessage() : 'Laporan belum berhasil dihapus.');
        }
    }

    public function oracleMappings(): string|RedirectResponse
    {
        if (!$this->currentRoleIsAdmin()) return $this->adminMappingDenied();
        $service=new OracleLrMappingService();
        return view('akutansi/oracle_mappings',[
            'title'=>'Pengaturan Mapping Oracle',
            'lobMappings'=>$service->lobMappings(false),
            'accountMappings'=>$service->accountMappings(false),
            'reportLabels'=>LrReportRows::mappableLabels(),
            'mappingError'=>session()->getFlashdata('mapping_error'),
        ]);
    }

    public function saveOracleAccountMapping(): RedirectResponse
    {
        if (!$this->currentRoleIsAdmin()) return $this->adminMappingDenied();
        try {
            (new OracleLrMappingService())->saveAccount($this->request->getPost(),(string)session()->get('auth_display_name'));
            return redirect()->to(site_url('akutansi/pengaturan-mapping-oracle#mapping-coa'))->with('success','Mapping Description COA berhasil disimpan. Upload berikutnya akan memakai aturan terbaru.');
        } catch (Throwable $e) {
            return redirect()->to(site_url('akutansi/pengaturan-mapping-oracle#mapping-coa'))->with('mapping_error',$e instanceof RuntimeException ? $e->getMessage() : 'Mapping Description COA belum berhasil disimpan.');
        }
    }

    public function deleteOracleAccountMapping(): RedirectResponse
    {
        if (!$this->currentRoleIsAdmin()) return $this->adminMappingDenied();
        try {
            if ($this->request->getPost('confirm_delete')!=='1') throw new RuntimeException('Konfirmasi penghapusan mapping COA terlebih dahulu.');
            (new OracleLrMappingService())->deleteAccount($this->request->getPost('id'));
            return redirect()->to(site_url('akutansi/pengaturan-mapping-oracle#mapping-coa'))->with('success','Mapping Description COA berhasil dihapus.');
        } catch (Throwable $e) {
            return redirect()->to(site_url('akutansi/pengaturan-mapping-oracle#mapping-coa'))->with('mapping_error',$e instanceof RuntimeException ? $e->getMessage() : 'Mapping Description COA belum berhasil dihapus.');
        }
    }

    public function formulaSettings(): string|RedirectResponse
    {
        $role = (string) session()->get('auth_role');
        if (!in_array($role, ['admin', 'akutansi'], true)) return $this->formulaSettingsDenied();
        $sourceService = new LrSourceAdjustmentService();
        $sourceRules = $sourceService->rules();
        foreach ($sourceRules as &$sourceRule) $sourceRule['formula_lines'] = $sourceService->formulaLines((array) ($sourceRule['terms'] ?? []));
        unset($sourceRule);
        return view('akutansi/formula_settings', [
            'title' => 'Seting Rumus',
            'formulaScopes' => LrFormulaService::scopeOptions(),
            'formulaColumnScopes' => LrFormulaService::columnScopeOptions(),
            'sourceAdjustmentRules' => $sourceRules,
            'sourceAdjustmentRequests' => $sourceService->requests(),
            'sourceAdjustmentTargets' => LrSourceAdjustmentService::targetOptions(),
            'sourceDescriptions' => $sourceService->knownSourceDescriptions(),
            'sourceAdjustmentError' => session()->getFlashdata('source_adjustment_error'),
            'canManageSourceAdjustments' => true,
            'isSourceAdjustmentAdmin' => $role === 'admin',
        ]);
    }

    public function saveSourceAdjustmentRule(): RedirectResponse
    {
        $role = (string) session()->get('auth_role');
        if (!in_array($role, ['admin', 'akutansi'], true)) return $this->formulaSettingsDenied();
        try {
            $service = new LrSourceAdjustmentService();
            $actor = (string) session()->get('auth_display_name');
            if ($role === 'admin') $service->save($this->request->getPost(), $actor);
            else $service->requestSave($this->request->getPost(), $actor);
            return redirect()->to(site_url('akutansi/seting-rumus#penyesuaian-sumber'))
                ->with('success', $role === 'admin'
                    ? 'Penyesuaian sumber Oracle berhasil disimpan dan langsung berlaku pada periode yang dipilih.'
                    : 'Pengajuan penyesuaian berhasil dikirim. Perhitungan belum berubah sampai disetujui Administrator.');
        } catch (Throwable $exception) {
            return redirect()->to(site_url('akutansi/seting-rumus#penyesuaian-sumber'))
                ->with('source_adjustment_error', $exception instanceof RuntimeException ? $exception->getMessage() : 'Penyesuaian sumber Oracle belum berhasil disimpan.');
        }
    }

    public function deactivateSourceAdjustmentRule(): RedirectResponse
    {
        $role = (string) session()->get('auth_role');
        if (!in_array($role, ['admin', 'akutansi'], true)) return $this->formulaSettingsDenied();
        try {
            if ($this->request->getPost('confirm_deactivate') !== '1') throw new RuntimeException('Konfirmasi penonaktifan penyesuaian terlebih dahulu.');
            $service = new LrSourceAdjustmentService();
            $actor = (string) session()->get('auth_display_name');
            if ($role === 'admin') $service->deactivate($this->request->getPost('id'), $actor);
            else $service->requestDeactivation($this->request->getPost('id'), $actor);
            return redirect()->to(site_url('akutansi/seting-rumus#penyesuaian-sumber'))->with('success', $role === 'admin'
                ? 'Penyesuaian sumber Oracle dinonaktifkan.'
                : 'Pengajuan penonaktifan berhasil dikirim. Aturan tetap aktif sampai disetujui Administrator.');
        } catch (Throwable $exception) {
            return redirect()->to(site_url('akutansi/seting-rumus#penyesuaian-sumber'))
                ->with('source_adjustment_error', $exception instanceof RuntimeException ? $exception->getMessage() : 'Penyesuaian sumber belum berhasil dinonaktifkan.');
        }
    }

    public function deleteSourceAdjustmentRule(): RedirectResponse
    {
        $role = (string) session()->get('auth_role');
        if (!in_array($role, ['admin', 'akutansi'], true)) return $this->formulaSettingsDenied();
        try {
            if ($this->request->getPost('confirm_delete') !== '1') throw new RuntimeException('Konfirmasi penghapusan penyesuaian terlebih dahulu.');
            $service = new LrSourceAdjustmentService();
            $actor = (string) session()->get('auth_display_name');
            if ($role === 'admin') $service->deleteInactive($this->request->getPost('id'), $actor);
            else $service->requestDeletion($this->request->getPost('id'), $actor);
            return redirect()->to(site_url('akutansi/seting-rumus#penyesuaian-sumber'))->with('success', $role === 'admin'
                ? 'Penyesuaian sumber Oracle yang nonaktif berhasil dihapus. Riwayat audit tetap disimpan.'
                : 'Pengajuan penghapusan berhasil dikirim. Aturan belum dihapus sampai disetujui Administrator.');
        } catch (Throwable $exception) {
            return redirect()->to(site_url('akutansi/seting-rumus#penyesuaian-sumber'))
                ->with('source_adjustment_error', $exception instanceof RuntimeException ? $exception->getMessage() : 'Penyesuaian sumber belum berhasil dihapus.');
        }
    }

    public function approveSourceAdjustmentRequest(): RedirectResponse
    {
        if (!$this->currentRoleIsAdmin()) return $this->adminFormulaDenied();
        try {
            if ($this->request->getPost('confirm_approve') !== '1') throw new RuntimeException('Konfirmasi persetujuan pengajuan terlebih dahulu.');
            (new LrSourceAdjustmentService())->approveRequest($this->request->getPost('id'), (string) session()->get('auth_display_name'));
            return redirect()->to(site_url('akutansi/seting-rumus#pengajuan-sumber'))->with('success', 'Pengajuan disetujui dan perubahan telah diterapkan ke perhitungan.');
        } catch (Throwable $exception) {
            return redirect()->to(site_url('akutansi/seting-rumus#pengajuan-sumber'))
                ->with('source_adjustment_error', $exception instanceof RuntimeException ? $exception->getMessage() : 'Pengajuan belum berhasil disetujui.');
        }
    }

    public function rejectSourceAdjustmentRequest(): RedirectResponse
    {
        if (!$this->currentRoleIsAdmin()) return $this->adminFormulaDenied();
        try {
            if ($this->request->getPost('confirm_reject') !== '1') throw new RuntimeException('Konfirmasi penolakan pengajuan terlebih dahulu.');
            (new LrSourceAdjustmentService())->rejectRequest(
                $this->request->getPost('id'), (string) session()->get('auth_display_name'), (string) $this->request->getPost('review_note')
            );
            return redirect()->to(site_url('akutansi/seting-rumus#pengajuan-sumber'))->with('success', 'Pengajuan telah ditolak.');
        } catch (Throwable $exception) {
            return redirect()->to(site_url('akutansi/seting-rumus#pengajuan-sumber'))
                ->with('source_adjustment_error', $exception instanceof RuntimeException ? $exception->getMessage() : 'Pengajuan belum berhasil ditolak.');
        }
    }

    public function rkaKanwilSurabaya(): string
    {
        [$unit, $year] = $this->selection();
        $data = $this->rkaData($unit, $year);
        $data['rkaLobs'] = self::rkaReportLobs(null);
        $data['selectedLobs'] = self::rkaReportLobs($this->request->getGet('lob'));
        $data['uploadError'] = session()->getFlashdata('rka_upload_error');
        $data['uploadMode'] = session()->getFlashdata('rka_upload_mode') === 'all' ? 'all' : 'single';
        $data['uploadAutoOpen'] = $data['uploadError'] !== null || $this->request->getGet('upload') === '1';
        $data['manualAutoOpen'] = $this->request->getGet('manual') === '1';
        return view('akutansi/rka_kanwil_surabaya', $data);
    }

    public function index(): string
    {
        return view('akutansi/index', [
            'title' => 'Akutansi',
        ]);
    }

    public function exportDokumen(): string
    {
        return view('akutansi/export_dokumen', [
            'title' => 'Export Dokumen',
            'exportUnits' => RkaCalculator::UNITS,
            'exportBases' => LrRealizationService::BASES,
            'selectedYear' => 2026,
            'selectedMonth' => (int) date('n'),
        ]);
    }

    public function simulasiHitung(): string
    {
        return view('akutansi/simulasi_hitung', [
            'title' => 'Simulasi Hitung',
            'simulations' => (new LrWorkpaperSimulationService())->history((int) session()->get('auth_user_id'), $this->currentRoleIsAdmin()),
            'selectedMonth' => (int) date('n'),
            'uploadError' => session()->getFlashdata('simulation_upload_error'),
        ]);
    }

    public function uploadSimulasiHitung(): RedirectResponse
    {
        try {
            $rawYear = $this->request->getPost('tahun');
            $rawMonth = $this->request->getPost('bulan');
            $rawBasis = $this->request->getPost('jenis_laporan');
            if (!is_string($rawYear) || !preg_match('/^\d{4}$/D', $rawYear)
                || !is_string($rawMonth) || !preg_match('/^(?:[1-9]|1[0-2])$/D', $rawMonth)
                || !is_string($rawBasis)) throw new RuntimeException('Lengkapi jenis laporan, bulan, dan tahun.');
            $file = $this->request->getFile('oracle_excel');
            if ($file === null || !$file->isValid() || $file->hasMoved()
                || strtolower($file->getClientExtension()) !== 'xlsx' || $file->getSize() > 5 * 1024 * 1024) {
                throw new RuntimeException('Pilih berkas LR Oracle .xlsx yang valid, maksimal 5 MB.');
            }
            $created = (new LrWorkpaperSimulationService())->create(
                $file->getTempName(), $file->getClientName(), (int) $rawYear, (int) $rawMonth,
                strtoupper($rawBasis), (int) session()->get('auth_user_id'), (string) session()->get('auth_display_name')
            );
            $message = 'Kertas kerja ' . $created['report_basis'] . ' ' . self::monthName((int) $created['report_month']) . ' ' . $created['report_year']
                . ' siap diunduh. ' . $created['helper_rows'] . ' baris Oracle sudah diisi ke tabel bantu; rumus asli dihitung ulang saat Excel dibuka.';
            if ($created['missing_rka']) $message .= ' RKA belum tersedia untuk ' . implode(', ', $created['missing_rka']) . ' dan diisi nol.';
            if ($created['unmapped_accounts']) $message .= ' ' . $created['unmapped_accounts'] . ' baris belum cocok dengan mapping akun/segmen; baris tetap ada di tabel bantu, tetapi periksa apakah rumus Excel menjangkaunya.';
            return redirect()->to(site_url('akutansi/simulasi-hitung'))->with('success', $message);
        } catch (Throwable $exception) {
            log_message('warning', 'Simulasi kertas kerja gagal: {message}', ['message' => $exception->getMessage()]);
            $known = $exception instanceof \InvalidArgumentException || get_class($exception) === RuntimeException::class;
            return redirect()->to(site_url('akutansi/simulasi-hitung'))->with('simulation_upload_error',
                $known ? $exception->getMessage() : 'Simulasi belum berhasil. Tidak ada hasil yang disimpan.');
        }
    }

    public function downloadSimulasiHitung(string $id): ResponseInterface|RedirectResponse
    {
        try {
            $file = (new LrWorkpaperSimulationService())->download($id, (int) session()->get('auth_user_id'), $this->currentRoleIsAdmin());
            return $this->response->download($file['path'], null)->setFileName($file['filename'])
                ->setHeader('Cache-Control', 'private, no-store, max-age=0')
                ->setHeader('X-Content-Type-Options', 'nosniff');
        } catch (Throwable $exception) {
            $known = get_class($exception) === RuntimeException::class;
            return redirect()->to(site_url('akutansi/simulasi-hitung'))->with('simulation_upload_error',
                $known ? $exception->getMessage() : 'Berkas simulasi belum dapat diunduh.');
        }
    }

    public function deleteSimulasiHitung(string $id): RedirectResponse
    {
        try {
            if ($this->request->getPost('confirm_delete') !== '1') {
                throw new RuntimeException('Konfirmasi penghapusan hasil simulasi terlebih dahulu.');
            }
            (new LrWorkpaperSimulationService())->delete(
                $id,
                (int) session()->get('auth_user_id'),
                $this->currentRoleIsAdmin()
            );
            return redirect()->to(site_url('akutansi/simulasi-hitung'))
                ->with('success', 'Hasil simulasi telah dihapus. Berkas Excel tidak lagi tersedia untuk diunduh.');
        } catch (Throwable $exception) {
            $known = get_class($exception) === RuntimeException::class;
            return redirect()->to(site_url('akutansi/simulasi-hitung'))->with(
                'simulation_upload_error',
                $known ? $exception->getMessage() : 'Hasil simulasi belum berhasil dihapus.'
            );
        }
    }

    public function downloadExportDokumen(): ResponseInterface|RedirectResponse
    {
        try {
            $rawUnits = $this->request->getPost('unit_kerja');
            $rawYear = $this->request->getPost('tahun');
            $rawMonth = $this->request->getPost('bulan');
            $rawBasis = $this->request->getPost('jenis_laporan');
            if (!is_array($rawUnits) || !is_string($rawYear) || !preg_match('/^\d{4}$/D', $rawYear)
                || !is_string($rawMonth) || !preg_match('/^(?:[1-9]|1[0-2])$/D', $rawMonth)
                || !is_string($rawBasis)) {
                throw new RuntimeException('Lengkapi jenis laporan, unit kerja, bulan, dan tahun export.');
            }
            $units = [];
            foreach ($rawUnits as $unit) {
                if (!is_string($unit) || !in_array($unit, RkaCalculator::UNITS, true)) {
                    throw new RuntimeException('Pilihan unit kerja export tidak valid.');
                }
                if (!in_array($unit, $units, true)) $units[] = $unit;
            }
            $basis = strtoupper($rawBasis);
            if (!in_array($basis, LrRealizationService::BASES, true)) {
                throw new RuntimeException('Pilih jenis laporan YTD atau PTD yang valid.');
            }

            $export = (new LrDocumentExportService())->create($units, (int) $rawYear, (int) $rawMonth, $basis);
            $contents = file_get_contents($export['path']);
            if ($contents === false) throw new RuntimeException('Dokumen hasil export belum dapat dibaca.');
            unlink($export['path']);

            return $this->response
                ->setHeader('Cache-Control', 'private, no-store, max-age=0')
                ->setHeader('X-Content-Type-Options', 'nosniff')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $export['filename'] . '"')
                ->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setBody($contents);
        } catch (Throwable $exception) {
            log_message('warning', 'Export dokumen laba rugi gagal: {message}', ['message' => $exception->getMessage()]);
            $known = $exception instanceof \InvalidArgumentException || get_class($exception) === RuntimeException::class;
            return redirect()->to(site_url('akutansi/export-dokumen'))->with(
                'error',
                $known ? $exception->getMessage() : 'Dokumen belum dapat diexport. Silakan coba kembali.'
            );
        }
    }

    public function rkaUpload(): RedirectResponse
    {
        return redirect()->to(site_url('akutansi/rka-kanwil-surabaya?upload=1'));
    }

    public function rkaManual(): RedirectResponse
    {
        [$unit, $year] = $this->selection();
        return redirect()->to($this->rkaUrl($unit, $year) . '&manual=1');
    }

    public function downloadRkaTemplate(): ResponseInterface
    {
        return $this->response->download(WRITEPATH . 'templates/rka/Template_RKA_JMK_KV_SBY.xlsx', null)->setFileName('Template_RKA_JMK_KV_SBY.xlsx');
    }

    public function rkaManualData(): ResponseInterface
    {
        $this->response->setHeader('Cache-Control', 'private, no-store');
        try {
            if ($this->request->getGet('scope') === 'all') {
                [, $year] = $this->selection(['unit_kerja' => 'Surabaya', 'tahun' => $this->request->getGet('tahun')]);
                $records = [];
                $service = new RkaBudgetService();
                foreach (RkaCalculator::UNITS as $unit) {
                    $record = $service->findStored($unit, $year);
                    $records[$unit] = ['id' => $record !== null ? (int) $record['id'] : null, 'revision' => $record !== null ? (int) $record['revision'] : 0];
                }
                return $this->response->setJSON(['scope' => 'all', 'year' => $year, 'records' => $records]);
            }
            [$unit, $year] = $this->selection($this->request->getGet());
            if ($unit === 'Korporat Kanwil') throw new RuntimeException('Korporat Kanwil dihitung otomatis. Pilih salah satu dari enam unit sumber untuk mengubah RKA.');
            $data = $this->rkaData($unit, $year);
            return $this->response->setJSON([
                'unit' => $unit, 'year' => $year, 'revision' => $data['revision'],
                'inputs' => $data['inputs'], 'has_record' => $data['record'] !== null,
                'budget_id' => $data['record'] !== null ? (int) $data['record']['id'] : null,
            ]);
        } catch (Throwable $exception) {
            log_message('warning', 'Muat isian RKA gagal: {message}', ['message' => $exception->getMessage()]);
            $known = $exception instanceof \InvalidArgumentException || get_class($exception) === RuntimeException::class;
            return $this->response->setStatusCode($known ? 400 : 503)->setJSON([
                'error' => $known ? $exception->getMessage() : 'Isian RKA belum dapat dimuat. Silakan coba kembali.',
            ]);
        }
    }

    public function deleteRka(): RedirectResponse
    {
        $unit = 'Surabaya'; $year = 2026;
        try {
            [$unit, $year] = $this->selection($this->request->getPost());
            if ($this->request->getPost('confirm_delete') !== '1') throw new RuntimeException('Konfirmasi penghapusan RKA terlebih dahulu.');
            $recordId = $this->request->getPost('rka_id');
            if (! is_string($recordId) || ! preg_match('/^[1-9]\d{0,17}$/D', $recordId)) throw new RuntimeException('Identitas RKA tidak valid. Muat ulang halaman sebelum menghapus.');
            (new RkaBudgetService())->delete($unit, $year, $this->revision(), (int) $recordId);
            return redirect()->to($this->rkaUrl($unit, $year))->with('success', 'RKA ' . $unit . ' tahun ' . $year . ' dipindahkan ke Data Terhapus. Administrator dapat memulihkannya.');
        } catch (Throwable $exception) {
            log_message('warning', 'Hapus RKA gagal: {message}', ['message' => $exception->getMessage()]);
            $message = $exception instanceof \InvalidArgumentException || get_class($exception) === RuntimeException::class
                ? $exception->getMessage() : 'RKA belum dapat dihapus. Silakan coba kembali.';
            return redirect()->to($this->rkaUrl($unit, $year))->with('error', $message);
        }
    }

    public function importRka(): RedirectResponse
    {
        $unit = 'Surabaya';
        $year = 2026;
        $scope = $this->request->getPost('upload_scope') ?? 'single';
        try {
            if (!in_array($scope, ['single', 'all'], true)) throw new RuntimeException('Pilih jenis upload RKA yang valid.');
            [$unit, $year] = $this->selection($scope === 'all' ? ['unit_kerja' => 'Surabaya', 'tahun' => $this->request->getPost('tahun')] : $this->request->getPost());
            $this->requireConfirmation();
            $file = $this->request->getFile('rka_excel_file');
            if ($file === null || ! $file->isValid() || $file->getSize() > 5 * 1024 * 1024 || strtolower($file->getClientExtension()) !== 'xlsx') {
                throw new RuntimeException('Pilih berkas Template RKA .xlsx yang valid, maksimal 5 MB.');
            }
            if ($scope === 'all') {
                $parsed = (new RkaWorkbookParser())->parseAll($file->getTempName());
                foreach ($parsed as $sheetUnit => $budget) {
                    if ($budget['year'] !== $year) throw new RuntimeException('Tahun pada A2 sheet ' . $sheetUnit . ' berbeda dengan tahun yang dipilih (' . $year . '). Tidak ada RKA yang disimpan.');
                }
                $rawSnapshots = $this->request->getPost('rka_all_snapshots');
                if (!is_string($rawSnapshots) || strlen($rawSnapshots) > 5000) throw new RuntimeException('Versi RKA tidak valid. Klik Next untuk memeriksa seluruh unit.');
                try { $snapshots = json_decode($rawSnapshots, true, 32, JSON_THROW_ON_ERROR); }
                catch (\JsonException $exception) { throw new RuntimeException('Versi RKA tidak valid. Klik Next untuk memeriksa seluruh unit.'); }
                if (!is_array($snapshots)) throw new RuntimeException('Versi RKA tidak valid. Klik Next untuk memeriksa seluruh unit.');
                (new RkaBudgetService())->saveAll($year, $parsed, $snapshots, $this->request->getPost('rka_reset_existing') === '1', mb_substr(basename($file->getClientName()), 0, 255), hash_file('sha256', $file->getTempName()) ?: null);
                return redirect()->to($this->rkaUrl($unit, $year))->with('success', 'RKA seluruh ' . count(RkaCalculator::UNITS) . ' unit kerja tahun ' . $year . ' berhasil diimpor dan disimpan. Nominal disimpan sesuai nama sheet dan total dihitung ulang sesuai Template RKA.');
            }
            $existing = (new RkaBudgetService())->find($unit, $year);
            if ($existing !== null && ($this->request->getPost('rka_reset_existing') !== '1'
                || $this->request->getPost('rka_reset_id') !== (string) $existing['id'])) {
                throw new RuntimeException('RKA sudah diseting. Klik Next, lalu Seting Ulang sebelum mengupload berkas pengganti.');
            }
            $parsed = (new RkaWorkbookParser())->parse($file->getTempName(), $unit);
            if ($parsed['year'] !== $year) {
                throw new RuntimeException('Tahun pada A2 Excel (' . $parsed['year'] . ') berbeda dengan tahun yang dipilih (' . $year . ').');
            }
            (new RkaBudgetService())->save($unit, $year, $parsed['inputs'], $this->revision(), 'excel', mb_substr(basename($file->getClientName()), 0, 255), hash_file('sha256', $file->getTempName()) ?: null);
            return redirect()->to($this->rkaUrl($unit, $year))->with('success', 'RKA ' . $unit . ' tahun ' . $year . ' berhasil diimpor dan disimpan. Total dihitung ulang sesuai Template RKA.');
        } catch (Throwable $exception) {
            log_message('warning', 'Upload RKA gagal: {message}', ['message' => $exception->getMessage()]);
            $message = $exception instanceof \InvalidArgumentException || get_class($exception) === RuntimeException::class
                ? $exception->getMessage() : 'RKA belum dapat disimpan. Silakan coba kembali atau hubungi Administrator.';
            return redirect()->to($this->rkaUrl($unit, $year))->with('rka_upload_error', $message)->with('rka_upload_mode', $scope === 'all' ? 'all' : 'single');
        }
    }

    public function saveRkaManual(): string|RedirectResponse
    {
        $unit = 'Surabaya';
        $year = 2026;
        $rawInputs = $this->request->getPost('cells');
        try {
            [$unit, $year] = $this->selection($this->request->getPost());
            $this->requireConfirmation();
            if (! is_array($rawInputs)) {
                throw new RuntimeException('Isian nominal RKA tidak tersedia.');
            }
            $inputs = (new RkaCalculator())->normalizeInputs($rawInputs, true);
            $editId = null;
            if ($this->request->getPost('rka_mode') === 'edit') {
                $postedId = $this->request->getPost('rka_edit_id');
                if (! is_string($postedId) || ! preg_match('/^[1-9]\d{0,17}$/D', $postedId)) throw new RuntimeException('Identitas RKA tidak valid. Muat ulang halaman sebelum mengedit.');
                $editId = (int) $postedId;
            }
            (new RkaBudgetService())->save($unit, $year, $inputs, $this->revision(), 'manual', null, null, $editId);
            return redirect()->to($this->rkaUrl($unit, $year))->with('success', 'RKA ' . $unit . ' tahun ' . $year . ' berhasil disimpan.');
        } catch (Throwable $exception) {
            log_message('warning', 'Simpan manual RKA gagal: {message}', ['message' => $exception->getMessage()]);
            $data = $this->rkaData($unit, $year);
            $data['manualAutoOpen'] = true;
            $data['uploadAutoOpen'] = false;
            $data['uploadError'] = null;
            $postedRevision = $this->request->getPost('revision');
            $data['revision'] = is_string($postedRevision) && ctype_digit($postedRevision) ? (int) $postedRevision : 0;
            $data['manualError'] = $exception instanceof \InvalidArgumentException || get_class($exception) === RuntimeException::class
                ? $exception->getMessage() : 'RKA belum dapat disimpan. Silakan coba kembali atau hubungi Administrator.';
            $data['rawInputs'] = is_array($rawInputs) ? $rawInputs : null;
            $data['manualMode'] = $this->request->getPost('rka_mode') === 'edit' ? 'edit' : 'setting';
            $postedId = $this->request->getPost('rka_edit_id');
            $data['manualEditId'] = is_string($postedId) ? $postedId : '';
            return view('akutansi/rka_kanwil_surabaya', $data);
        }
    }

    private function selection(?array $post = null): array
    {
        $source = $post ?? $this->request->getGet();
        $unit = $source['unit_kerja'] ?? ($post === null ? 'Korporat Kanwil' : '');
        $year = $source['tahun'] ?? ($post === null ? '2026' : '');
        if (! is_string($unit) || ! is_string($year) || ! preg_match('/^\d{4}$/D', $year)) {
            if ($post === null) {
                return ['Korporat Kanwil', 2026];
            }
            throw new RuntimeException('Pilih unit kerja dan tahun RKA yang valid.');
        }
        if ($post === null && (! in_array($unit, RkaCalculator::UNITS, true) || (int) $year < 2000 || (int) $year > 2100)) {
            return ['Korporat Kanwil', 2026];
        }
        (new RkaBudgetService())->validateSelection($unit, (int) $year);
        return [$unit, (int) $year];
    }

    private function rkaData(string $unit, int $year): array
    {
        $service = new RkaBudgetService();
        $record = $service->find($unit, $year);
        $schema = RkaCalculator::schema();
        if ($record !== null && $record['template_version'] !== $schema['version']) {
            throw new RuntimeException('Versi RKA tersimpan berbeda dengan template aktif. Hubungi Administrator.');
        }
        $calculator = new RkaCalculator();
        $inputs = $record === null ? $calculator->zeros() : json_decode($record['inputs_json'], true, 512, JSON_THROW_ON_ERROR);
        return [
            'title' => 'RKA Kanwil Surabaya', 'rkaUnits' => RkaCalculator::UNITS,
            'selectedUnit' => $unit, 'selectedYear' => $year, 'schema' => $schema,
            'record' => $record, 'inputs' => $inputs,
            'calculated' => $record === null ? null : $calculator->calculate($inputs),
            'revision' => $record === null ? 0 : (int) $record['revision'],
            'revisions' => $service->revisions(),
        ];
    }

    private function revision(): int
    {
        $value = $this->request->getPost('revision');
        if (! is_string($value) || ! preg_match('/^\d{1,9}$/D', $value)) {
            throw new RuntimeException('Versi RKA tidak valid. Muat ulang halaman sebelum menyimpan.');
        }
        return (int) $value;
    }

    private function requireConfirmation(): void
    {
        if ($this->request->getPost('confirm_replace') !== '1') {
            throw new RuntimeException('Konfirmasi unit, tahun dan penggantian RKA sebelum menyimpan.');
        }
    }

    private function rkaUrl(string $unit, int $year): string
    {
        return site_url('akutansi/rka-kanwil-surabaya?' . http_build_query(['unit_kerja' => $unit, 'tahun' => $year]));
    }

    private function adminMappingDenied(): RedirectResponse
    {
        return redirect()->to(site_url('akutansi/laba-rugi'))->with('error','Pengaturan Mapping Oracle hanya dapat diakses Administrator.');
    }

    private function adminFormulaDenied(): RedirectResponse
    {
        return redirect()->to(site_url('akutansi/laba-rugi'))->with('error', 'Seting Rumus hanya dapat diakses Administrator.');
    }

    private function formulaSettingsDenied(): RedirectResponse
    {
        return redirect()->to(site_url('dashboard'))->with('error', 'Penyesuaian Sumber Oracle hanya dapat dilihat oleh Administrator dan user Akuntansi.');
    }
}
