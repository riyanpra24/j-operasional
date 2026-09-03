<?php

namespace App\Controllers;

use App\Models\AgendarisModel;
use App\Models\DokumenKeluarModel;
use App\Models\DokumenMasukModel;
use App\Models\DokumenSpkModel;
use App\Models\PksDokumenModel;
use App\Models\PksItemModel;
use App\Models\PksKerjasamaModel;
use App\Models\UserModel;
use App\Models\VehicleDocumentModel;
use App\Models\VehicleMaintenanceModel;
use App\Models\VehicleModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Model;

class DeletedData extends BaseController
{
    /** @var array<string, array{label:string, table:string, model:class-string<Model>, fields:list<string>}> */
    private const RESOURCES = [
        'dokumen-masuk' => ['label' => 'Dokumen Masuk', 'table' => 'dokumen_masuk', 'model' => DokumenMasukModel::class, 'fields' => ['nomor_surat', 'pengirim', 'perihal']],
        'agendaris' => ['label' => 'Agendaris', 'table' => 'agendaris', 'model' => AgendarisModel::class, 'fields' => ['nomor_surat', 'pengirim', 'perihal_surat']],
        'dokumen-keluar' => ['label' => 'Dokumen Keluar', 'table' => 'dokumen_keluar', 'model' => DokumenKeluarModel::class, 'fields' => ['nomor_surat', 'jenis_surat', 'penerima']],
        'dokumen-spk' => ['label' => 'Dokumen SPK', 'table' => 'dokumen_spk', 'model' => DokumenSpkModel::class, 'fields' => ['nomor_dokumen', 'perihal']],
        'pks' => ['label' => 'PKS Barang dan Jasa', 'table' => 'pks_kerjasama', 'model' => PksKerjasamaModel::class, 'fields' => ['kode_internal', 'nama_kerjasama']],
        'dokumen-pks' => ['label' => 'Riwayat Dokumen PKS', 'table' => 'pks_dokumen_kerjasama', 'model' => PksDokumenModel::class, 'fields' => ['jenis_dokumen', 'nomor_dokumen']],
        'item-pks' => ['label' => 'Item Pekerjaan PKS', 'table' => 'pks_item_kerjasama', 'model' => PksItemModel::class, 'fields' => ['keterangan']],
        'akun' => ['label' => 'Akun Pengguna', 'table' => 'users', 'model' => UserModel::class, 'fields' => ['display_name', 'username']],
        'kendaraan' => ['label' => 'Data Kendaraan', 'table' => 'vehicles', 'model' => VehicleModel::class, 'fields' => ['nomor_polisi', 'nama_kendaraan']],
        'servis-kendaraan' => ['label' => 'Servis dan Perawatan', 'table' => 'vehicle_maintenance', 'model' => VehicleMaintenanceModel::class, 'fields' => ['jenis_perawatan', 'tanggal_servis']],
        'dokumen-kendaraan' => ['label' => 'Dokumen Kendaraan', 'table' => 'vehicle_documents', 'model' => VehicleDocumentModel::class, 'fields' => ['jenis_dokumen', 'nomor_dokumen']],
    ];

    public function index(): string|RedirectResponse
    {
        if (! $this->isAdministrator()) {
            return redirect()->to(site_url('dashboard'))->with('error', 'Halaman ini hanya dapat diakses oleh Administrator.');
        }

        $selectedType = trim((string) $this->request->getGet('jenis'));
        $order = $this->requestedListOrder();
        if ($selectedType !== '' && ! isset(self::RESOURCES[$selectedType])) {
            $selectedType = '';
        }

        $records = [];
        $counts = [];

        foreach (self::RESOURCES as $type => $resource) {
            $model = new $resource['model']();
            $deletedRows = $model->onlyDeleted()->orderBy('deleted_at', 'DESC')->findAll();
            $counts[$type] = count($deletedRows);

            if ($selectedType !== '' && $selectedType !== $type) {
                continue;
            }

            foreach ($deletedRows as $row) {
                $records[] = [
                    'id' => (int) $row['id'],
                    'type' => $type,
                    'module' => $resource['label'],
                    'label' => $this->recordLabel($row, $resource['fields']),
                    'deleted_at' => (string) ($row['deleted_at'] ?? ''),
                    'deleted_by_role' => (string) ($row['deleted_by_role'] ?? ''),
                    'deleted_by_name' => (string) ($row['deleted_by_name'] ?? ''),
                ];
            }
        }

        usort($records, static function (array $left, array $right) use ($order): int {
            return $order === 'terlama'
                ? strcmp($left['deleted_at'], $right['deleted_at'])
                : strcmp($right['deleted_at'], $left['deleted_at']);
        });

        return view('deleted_data/index', [
            'title' => 'Data Terhapus',
            'records' => $records,
            'resources' => self::RESOURCES,
            'counts' => $counts,
            'total' => array_sum($counts),
            'selectedType' => $selectedType,
            'order' => $order,
        ]);
    }

    public function restore(string $type, int $id): RedirectResponse
    {
        if (! $this->isAdministrator()) {
            return redirect()->to(site_url('dashboard'))->with('error', 'Pemulihan data hanya dapat dilakukan oleh Administrator.');
        }

        $resource = self::RESOURCES[$type] ?? null;
        if ($resource === null) {
            throw PageNotFoundException::forPageNotFound('Jenis data terhapus tidak dikenali.');
        }

        /** @var Model $model */
        $model = new $resource['model']();
        $record = $model->withDeleted()->find($id);
        if ($record === null || empty($record['deleted_at'])) {
            return redirect()->to(site_url('data-terhapus'))->with('error', 'Data terhapus tidak ditemukan atau sudah dipulihkan.');
        }

        $restored = db_connect()->table($resource['table'])
            ->where('id', $id)
            ->where('deleted_at IS NOT NULL', null, false)
            ->update([
                'deleted_at' => null,
                'deleted_by_role' => null,
                'deleted_by_name' => null,
            ]);

        if (! $restored) {
            return redirect()->to(site_url('data-terhapus'))->with('error', 'Data belum dapat dipulihkan.');
        }

        return redirect()->to(site_url('data-terhapus'))->with('success', $resource['label'] . ' berhasil dipulihkan.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        if (! $this->isAdministrator()) {
            return redirect()->to(site_url('dashboard'))->with('error', 'Penghapusan permanen hanya dapat dilakukan oleh Administrator.');
        }

        $resource = self::RESOURCES[$type] ?? null;
        if ($resource === null) {
            throw PageNotFoundException::forPageNotFound('Jenis data terhapus tidak dikenali.');
        }

        /** @var Model $model */
        $model = new $resource['model']();
        $record = $model->withDeleted()->find($id);
        if ($record === null || empty($record['deleted_at'])) {
            return redirect()->to(site_url('data-terhapus'))->with('error', 'Data terhapus tidak ditemukan atau sudah diproses.');
        }

        if (! $model->delete($id, true)) {
            return redirect()->to(site_url('data-terhapus'))->with('error', 'Data belum dapat dihapus permanen.');
        }

        return redirect()->to(site_url('data-terhapus'))->with('success', $resource['label'] . ' berhasil dihapus permanen.');
    }

    /** @param list<string> $fields */
    private function recordLabel(array $record, array $fields): string
    {
        $parts = [];
        foreach ($fields as $field) {
            $value = trim((string) ($record[$field] ?? ''));
            if ($value !== '' && ! in_array($value, $parts, true)) {
                $parts[] = $value;
            }
        }

        return $parts !== [] ? implode(' · ', $parts) : 'Data #' . (int) ($record['id'] ?? 0);
    }

    private function isAdministrator(): bool
    {
        return (string) session()->get('auth_role') === 'admin';
    }
}
