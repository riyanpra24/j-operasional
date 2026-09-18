<?php

namespace App\Controllers;

use App\Libraries\RkaBudgetService;
use App\Libraries\RkaCalculator;
use App\Libraries\RkaWorkbookParser;
use App\Libraries\OracleLrSalaryParser;
use App\Libraries\OracleLrMappingService;
use App\Libraries\LrReportRows;
use App\Libraries\LrRealizationService;
use App\Libraries\LrFormulaService;
use App\Libraries\LrSourceAdjustmentService;
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
        $report=(new LrRealizationService())->view($unit,$year);
        $import=$report['import']; $result=$report['result']; $reportValues=$report['values'];
        $flashedUploadMonth=session()->getFlashdata('lr_upload_month');
        $uploadMonth=is_int($flashedUploadMonth) && $flashedUploadMonth>=1 && $flashedUploadMonth<=12
            ? $flashedUploadMonth : (int)date('n');
        return view('akutansi/laba_rugi', [
            'title' => 'Laporan Laba & Rugi',
            'reportUnits' => RkaCalculator::UNITS, 'selectedUnit' => $unit,
            'selectedYear' => $year,
            'lrUploadMonth'=>$uploadMonth,
            'lrImport'=>$import,'lrResult'=>$result,'reportValues'=>$reportValues,
            'lrUploadError'=>session()->getFlashdata('lr_upload_error'),
            'isAdmin'=>$this->currentRoleIsAdmin(),
        ]);
    }

    public function importLabaRugi(): RedirectResponse
    {
        $year=2026; $month=(int)date('n'); $storedPath=null; $redirectUnit='Kanwil';
        try {
            $postedUnit=$this->request->getPost('unit_kerja');
            if (is_string($postedUnit) && in_array($postedUnit,OracleLrSalaryParser::IMPORT_UNITS,true)) $redirectUnit=$postedUnit;
            $postedYear=$this->request->getPost('tahun');
            if (!is_string($postedYear) || !preg_match('/^\d{4}$/D',$postedYear) || (int)$postedYear<2000 || (int)$postedYear>2100) throw new RuntimeException('Pilih tahun laporan yang valid.');
            $year=(int)$postedYear;
            $postedMonth=$this->request->getPost('bulan');
            if (!is_string($postedMonth) || !preg_match('/^(?:[1-9]|1[0-2])$/D',$postedMonth)) throw new RuntimeException('Pilih bulan laporan yang valid.');
            $month=(int)$postedMonth;
            $file=$this->request->getFile('lr_excel');
            if ($file===null || !$file->isValid() || $file->hasMoved() || strtolower($file->getClientExtension())!=='xlsx' || $file->getSize()>5*1024*1024) throw new RuntimeException('Pilih Excel .xlsx yang valid, maksimal 5 MB.');
            $parser=new OracleLrSalaryParser(); $parsedByUnit=[];
            foreach (OracleLrSalaryParser::IMPORT_UNITS as $unit) {
                $parsed=$parser->parse($file->getTempName(),$unit);
                if ($parsed['year']!==$year) throw new RuntimeException('Tahun pada periode sheet '.$parsed['sheet'].' ('.$parsed['year'].') berbeda dengan tahun yang dipilih ('.$year.').');
                if ($parsed['month']!==$month) throw new RuntimeException('Bulan pada periode sheet '.$parsed['sheet'].' ('.self::monthName((int)$parsed['month']).') berbeda dengan bulan yang dipilih ('.self::monthName($month).').');
                $parsedByUnit[$unit]=$parsed;
            }
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
                        'unit_name'=>$unit,'report_year'=>$year,'report_month'=>$month,
                        'rule_version'=>OracleLrSalaryParser::RULE,'result_json'=>json_encode($parsed,JSON_THROW_ON_ERROR),
                        'source_name'=>$name,'source_hash'=>$hash,'source_path'=>$relative,
                        'created_by_name'=>session()->get('auth_display_name') ?: null,'created_at'=>$createdAt,
                    ]);
                    if (!$ok) throw new RuntimeException('Hasil laporan unit kerja belum berhasil disimpan.');
                }
                if (!$db->transStatus() || !$db->transCommit()) throw new RuntimeException('Hasil laporan unit kerja belum berhasil disimpan.');
            } catch (Throwable $exception) {
                $db->transRollback(); throw $exception;
            }
            $groupCounts=array_fill_keys(OracleLrMappingService::TARGET_COLUMNS,0); $unmapped=0;
            foreach ($parsedByUnit as $parsed) {
                foreach ($groupCounts as $column=>$count) $groupCounts[$column]+=(int)($parsed['segment_counts'][$column]??0);
                $unmapped+=count($parsed['unmapped']??[]);
            }
            $summary=[]; foreach ($groupCounts as $column=>$count) if ($count>0) $summary[]=$column.' '.$count.' baris';
            $storedPath=null; // Successfully persisted source must remain even if redirect fails.
            return redirect()->to(site_url('akutansi/laba-rugi?unit_kerja='.rawurlencode($redirectUnit).'&tahun='.$year))
                ->with('success','Excel periode '.self::monthName($month).' '.$year.' berhasil dikelompokkan untuk Kanwil dan 5 cabang: '.implode(', ',$summary).'. '.($unmapped>0 ? $unmapped.' baris belum terpetakan dan tersedia pada Detail Pengelompokan.' : 'Semua baris LOB yang terbaca telah terpetakan.'));
        } catch (Throwable $exception) {
            // Only the new randomized private upload is removed on failed insert.
            if ($storedPath!==null && is_file($storedPath)) unlink($storedPath);
            log_message('warning','Upload laporan laba rugi gagal: {message}',['message'=>$exception->getMessage()]);
            $known=$exception instanceof \InvalidArgumentException || get_class($exception)===RuntimeException::class;
            return redirect()->to(site_url('akutansi/laba-rugi?unit_kerja='.rawurlencode($redirectUnit).'&tahun='.$year))
                ->with('lr_upload_error',$known ? $exception->getMessage() : 'Upload belum berhasil. Data sebelumnya tidak diubah.')
                ->with('lr_upload_month',$month);
        }
    }

    private static function monthName(int $month): string
    {
        return [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
            7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'][$month] ?? 'Tidak valid';
    }

    public function deleteLabaRugi(): RedirectResponse
    {
        $unit='Kanwil'; $year=2026; $month=(int)date('n');
        try {
            $postedUnit=$this->request->getPost('unit_kerja'); $postedYear=$this->request->getPost('tahun'); $postedMonth=$this->request->getPost('bulan');
            if (!is_string($postedUnit) || !in_array($postedUnit,OracleLrSalaryParser::IMPORT_UNITS,true)
                || !is_string($postedYear) || !preg_match('/^\d{4}$/D',$postedYear) || (int)$postedYear<2000 || (int)$postedYear>2100
                || !is_string($postedMonth) || !preg_match('/^(?:[1-9]|1[0-2])$/D',$postedMonth)) throw new RuntimeException('Pilihan unit, bulan, dan tahun tidak valid.');
            $unit=$postedUnit; $year=(int)$postedYear; $month=(int)$postedMonth;
            if ($this->request->getPost('confirm_delete')!=='1') throw new RuntimeException('Konfirmasi penghapusan laporan terlebih dahulu.');
            (new \App\Libraries\OracleLrImportService())->delete($unit,$year,$month,(string)session()->get('auth_role'),(string)session()->get('auth_display_name'));
            return redirect()->to(site_url('akutansi/laba-rugi?unit_kerja='.rawurlencode($unit).'&tahun='.$year))->with('success','Data laporan '. $unit.' periode '.self::monthName($month).' '.$year.' dipindahkan ke Data Terhapus.');
        } catch (Throwable $e) {
            log_message('warning','Hapus laporan laba rugi gagal: {message}',['message'=>$e->getMessage()]);
            return redirect()->to(site_url('akutansi/laba-rugi?unit_kerja='.rawurlencode($unit).'&tahun='.$year))->with('error',get_class($e)===RuntimeException::class ? $e->getMessage() : 'Laporan belum berhasil dihapus.');
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
