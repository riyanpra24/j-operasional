<?php

namespace App\Controllers;

use App\Models\VehicleActivityLogModel;
use App\Models\VehicleDocumentModel;
use App\Models\VehicleMaintenanceModel;
use App\Models\VehicleModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class MonitoringKendaraan extends BaseController
{
    private const VEHICLE_NAMES = [
        'Kendaraan Pinwil',
        'Kendaraan Wakil Pinwil',
        'Kendaraan Operasional Kantor',
        'Lainnya',
    ];
    private const VEHICLE_TYPES = ['Mobil', 'Motor'];
    private const VEHICLE_OWNERSHIP_STATUSES = ['Kendaraan Aset', 'Kendaraan Sewa'];
    private const VEHICLE_STATUSES = ['Digunakan', 'Perawatan', 'Tidak Aktif', 'Lainnya'];
    private const VEHICLE_DRIVERS = ['Eryuninto', 'Riyanto', 'Fransiskus Medhison', 'Lainnya'];
    private const VEHICLE_MANAGEMENT_UNITS = ['Bagian Umum 1', 'Bagian Umum 2'];
    private const SERVICE_BUDGETS = ['Asuransi', 'Kantor'];
    private const OFFICE_SERVICE_COMPANY = 'PT. Jaminan Kredit Indonesia (Persero)';
    private const DOCUMENT_TYPES = ['STNK', 'Pajak', 'KIR', 'Asuransi', 'Lainnya'];

    public function index(): RedirectResponse
    {
        return redirect()->to(site_url('bagian-umum-2/monitoring-kendaraan/data-kendaraan'));
    }

    public function vehicles(): string
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $status = trim((string) $this->request->getGet('status'));
        $perPage = $this->perPage();
        $isAdmin = $this->isAdmin();
        $model = new VehicleModel();

        if ($isAdmin) {
            $model->withDeleted();
        }

        if ($keyword !== '') {
            $model->groupStart()->like('nomor_polisi', $keyword)->orLike('nama_kendaraan', $keyword)
                ->orLike('nama_kendaraan_lainnya', $keyword)
                ->orLike('merek', $keyword)->orLike('tipe', $keyword)->orLike('unit_pengguna', $keyword)
                ->orLike('unit_pengguna_lainnya', $keyword)
                ->orLike('pic', $keyword)->groupEnd();
        }
        if (in_array($status, self::VEHICLE_STATUSES, true)) {
            $model->where('status', $status);
        } else {
            $status = '';
        }

        return view('monitoring_kendaraan/vehicles', [
            'title' => 'Data Kendaraan',
            'activePage' => 'vehicles',
            'records' => $model->orderBy('nomor_polisi', 'ASC')->paginate($perPage, 'vehicles'),
            'pager' => $model->pager,
            'filters' => compact('keyword', 'status', 'perPage'),
            'vehicleNames' => self::VEHICLE_NAMES,
            'vehicleTypes' => self::VEHICLE_TYPES,
            'vehicleOwnershipStatuses' => self::VEHICLE_OWNERSHIP_STATUSES,
            'statuses' => self::VEHICLE_STATUSES,
            'drivers' => self::VEHICLE_DRIVERS,
            'managementUnits' => self::VEHICLE_MANAGEMENT_UNITS,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function storeVehicle(): RedirectResponse
    {
        $data = $this->vehiclePayload();
        $errors = $this->validateVehicle($data);
        if ($errors !== []) {
            return $this->formError('vehicles', 'create', $data, $errors);
        }

        $data['created_by'] = $this->actorId();
        $model = new VehicleModel();
        $id = $model->insert($data, true);
        if (! $id) {
            return $this->formError('vehicles', 'create', $data, $model->errors() ?: ['vehicle' => 'Data kendaraan gagal disimpan.']);
        }

        $vehicle = $model->find($id);
        $this->recordActivity($vehicle, 'Kendaraan', (int) $id, 'Ditambahkan', 'Data kendaraan baru ditambahkan.');

        return redirect()->to($this->pageUrl('vehicles'))->with('success', "Kendaraan {$data['nomor_polisi']} berhasil ditambahkan.");
    }

    public function updateVehicle(int $id): RedirectResponse
    {
        $model = new VehicleModel();
        if ($this->isAdmin()) {
            $model->withDeleted();
        }
        $vehicle = $this->findOrFail($model, $id, 'Kendaraan tidak ditemukan.');
        $data = $this->vehiclePayload($vehicle);
        $errors = $this->validateVehicle($data, $id);
        if ($errors !== []) {
            return $this->formError('vehicles', 'edit', $data, $errors, $id);
        }
        if (! $model->update($id, $data)) {
            return $this->formError('vehicles', 'edit', $data, $model->errors() ?: ['vehicle' => 'Perubahan kendaraan gagal disimpan.'], $id);
        }

        $this->recordActivity($model->find($id) ?: $vehicle, 'Kendaraan', $id, 'Diperbarui', 'Data kendaraan diperbarui.');

        return redirect()->to($this->pageUrl('vehicles'))->with('success', "Kendaraan {$data['nomor_polisi']} berhasil diperbarui.");
    }

    public function destroyVehicle(int $id): RedirectResponse
    {
        $isAdmin = $this->isAdmin();
        $model = new VehicleModel();
        if ($isAdmin) {
            $model->withDeleted();
        }
        $vehicle = $this->findOrFail($model, $id, 'Kendaraan tidak ditemukan.');
        $description = $isAdmin
            ? 'Data kendaraan dihapus permanen dari database beserta seluruh data turunannya.'
            : 'Data kendaraan dihapus dari tampilan Bagian Umum 2 dan masih dapat dilihat oleh Administrator.';
        $this->recordActivity($vehicle, 'Kendaraan', $id, 'Dihapus', $description);
        if (! $model->delete($id, $isAdmin)) {
            return redirect()->to($this->pageUrl('vehicles'))->with('error', 'Data kendaraan gagal dihapus.');
        }

        $message = $isAdmin
            ? "Kendaraan {$vehicle['nomor_polisi']} berhasil dihapus permanen dari database."
            : "Kendaraan {$vehicle['nomor_polisi']} berhasil dihapus dari tampilan Bagian Umum 2.";

        return redirect()->to($this->pageUrl('vehicles'))->with('success', $message);
    }

    public function maintenance(): string
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $perPage = $this->perPage();
        $isAdmin = $this->isAdmin();
        $model = new VehicleMaintenanceModel();
        if ($isAdmin) {
            $model->withDeleted();
        }
        $model->select('vehicle_maintenance.*, vehicles.nomor_polisi, vehicles.nama_kendaraan, vehicles.nama_kendaraan_lainnya, vehicles.deleted_at AS vehicle_deleted_at')
            ->join('vehicles', 'vehicles.id = vehicle_maintenance.vehicle_id');
        if (! $isAdmin) {
            $model->where('vehicles.deleted_at', null);
        }
        if ($keyword !== '') {
            $model->groupStart()->like('vehicles.nomor_polisi', $keyword)->orLike('vehicles.nama_kendaraan', $keyword)
                ->orLike('vehicles.nama_kendaraan_lainnya', $keyword)
                ->orLike('vehicle_maintenance.jenis_perawatan', $keyword)->orLike('vehicle_maintenance.bengkel', $keyword)->groupEnd();
        }

        return view('monitoring_kendaraan/maintenance', [
            'title' => 'Servis dan Perawatan',
            'activePage' => 'maintenance',
            'records' => $model->orderBy('tanggal_servis', 'DESC')->orderBy('vehicle_maintenance.id', 'DESC')->paginate($perPage, 'maintenance'),
            'pager' => $model->pager,
            'filters' => compact('keyword', 'perPage'),
            'vehicles' => $this->vehicleOptions(),
            'serviceBudgets' => self::SERVICE_BUDGETS,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function storeMaintenance(): RedirectResponse
    {
        $data = $this->maintenancePayload();
        $errors = $this->validateMaintenance($data);
        if ($errors !== []) {
            return $this->formError('maintenance', 'create', $data, $errors);
        }

        $data['created_by'] = $this->actorId();
        $model = new VehicleMaintenanceModel();
        $id = $model->insert($data, true);
        if (! $id) {
            return $this->formError('maintenance', 'create', $data, $model->errors() ?: ['maintenance' => 'Data servis gagal disimpan.']);
        }

        $vehicle = $this->vehicle((int) $data['vehicle_id']);
        $this->synchronizeKilometer((int) $data['vehicle_id']);
        $this->recordActivity($vehicle, 'Servis', (int) $id, 'Ditambahkan', "Servis {$data['jenis_perawatan']} tanggal {$data['tanggal_servis']} ditambahkan.");

        return redirect()->to($this->pageUrl('maintenance'))->with('success', 'Data servis dan perawatan berhasil ditambahkan.');
    }

    public function updateMaintenance(int $id): RedirectResponse
    {
        $model = new VehicleMaintenanceModel();
        if ($this->isAdmin()) {
            $model->withDeleted();
        }
        $record = $this->findOrFail($model, $id, 'Data servis tidak ditemukan.');
        $data = $this->maintenancePayload($record);
        $errors = $this->validateMaintenance($data);
        if ($errors !== []) {
            return $this->formError('maintenance', 'edit', $data, $errors, $id);
        }
        if (! $model->update($id, $data)) {
            return $this->formError('maintenance', 'edit', $data, $model->errors() ?: ['maintenance' => 'Perubahan data servis gagal disimpan.'], $id);
        }

        $previousVehicleId = (int) $record['vehicle_id'];
        $vehicle = $this->vehicle((int) $data['vehicle_id']);
        $this->synchronizeKilometer($previousVehicleId);
        if ((int) $data['vehicle_id'] !== $previousVehicleId) {
            $this->synchronizeKilometer((int) $data['vehicle_id']);
        }
        $this->recordActivity($vehicle, 'Servis', $id, 'Diperbarui', "Servis {$data['jenis_perawatan']} tanggal {$data['tanggal_servis']} diperbarui.");

        return redirect()->to($this->pageUrl('maintenance'))->with('success', 'Data servis dan perawatan berhasil diperbarui.');
    }

    public function destroyMaintenance(int $id): RedirectResponse
    {
        $isAdmin = $this->isAdmin();
        $model = new VehicleMaintenanceModel();
        if ($isAdmin) {
            $model->withDeleted();
        }
        $record = $this->findOrFail($model, $id, 'Data servis tidak ditemukan.');
        $vehicle = $this->vehicle((int) $record['vehicle_id'], $isAdmin);
        $description = $isAdmin
            ? "Servis {$record['jenis_perawatan']} tanggal {$record['tanggal_servis']} dihapus permanen dari database."
            : "Servis {$record['jenis_perawatan']} tanggal {$record['tanggal_servis']} dihapus dari tampilan Bagian Umum 2 dan masih dapat dilihat oleh Administrator.";
        $this->recordActivity($vehicle, 'Servis', $id, 'Dihapus', $description);
        if (! $model->delete($id, $isAdmin)) {
            return redirect()->to($this->pageUrl('maintenance'))->with('error', 'Data servis gagal dihapus.');
        }
        $this->synchronizeKilometer((int) $record['vehicle_id']);

        return redirect()->to($this->pageUrl('maintenance'))->with(
            'success',
            $isAdmin
                ? 'Data servis dan perawatan berhasil dihapus permanen dari database.'
                : 'Data servis dan perawatan berhasil dihapus dari tampilan Bagian Umum 2.',
        );
    }

    public function documents(): string
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $type = trim((string) $this->request->getGet('jenis'));
        $perPage = $this->perPage();
        $isAdmin = $this->isAdmin();
        $model = new VehicleDocumentModel();
        if ($isAdmin) {
            $model->withDeleted();
        }
        $model->select('vehicle_documents.*, vehicles.nomor_polisi, vehicles.nama_kendaraan, vehicles.nama_kendaraan_lainnya, vehicles.deleted_at AS vehicle_deleted_at')
            ->join('vehicles', 'vehicles.id = vehicle_documents.vehicle_id');
        if (! $isAdmin) {
            $model->where('vehicles.deleted_at', null);
        }
        if ($keyword !== '') {
            $model->groupStart()->like('vehicles.nomor_polisi', $keyword)->orLike('vehicles.nama_kendaraan', $keyword)
                ->orLike('vehicles.nama_kendaraan_lainnya', $keyword)
                ->orLike('vehicle_documents.nomor_dokumen', $keyword)->groupEnd();
        }
        if (in_array($type, self::DOCUMENT_TYPES, true)) {
            $model->where('vehicle_documents.jenis_dokumen', $type);
        } else {
            $type = '';
        }

        return view('monitoring_kendaraan/documents', [
            'title' => 'Dokumen Kendaraan',
            'activePage' => 'documents',
            'records' => $this->decorateDocuments($model->orderBy('masa_berlaku', 'ASC')->orderBy('vehicle_documents.id', 'DESC')->paginate($perPage, 'documents')),
            'pager' => $model->pager,
            'filters' => compact('keyword', 'type', 'perPage'),
            'vehicles' => $this->vehicleOptions(),
            'documentTypes' => self::DOCUMENT_TYPES,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function storeDocument(): RedirectResponse
    {
        $data = $this->documentPayload();
        $errors = $this->validateDocument($data);
        if ($errors !== []) {
            return $this->formError('documents', 'create', $data, $errors);
        }

        $data['created_by'] = $this->actorId();
        $model = new VehicleDocumentModel();
        $id = $model->insert($data, true);
        if (! $id) {
            return $this->formError('documents', 'create', $data, $model->errors() ?: ['document' => 'Dokumen kendaraan gagal disimpan.']);
        }

        $vehicle = $this->vehicle((int) $data['vehicle_id']);
        $this->recordActivity($vehicle, 'Dokumen', (int) $id, 'Ditambahkan', "Dokumen {$data['jenis_dokumen']} dengan masa berlaku {$data['masa_berlaku']} ditambahkan.");

        return redirect()->to($this->pageUrl('documents'))->with('success', 'Dokumen kendaraan berhasil ditambahkan.');
    }

    public function updateDocument(int $id): RedirectResponse
    {
        $model = new VehicleDocumentModel();
        $this->findOrFail($model, $id, 'Dokumen kendaraan tidak ditemukan.');
        $data = $this->documentPayload();
        $errors = $this->validateDocument($data);
        if ($errors !== []) {
            return $this->formError('documents', 'edit', $data, $errors, $id);
        }
        if (! $model->update($id, $data)) {
            return $this->formError('documents', 'edit', $data, $model->errors() ?: ['document' => 'Perubahan dokumen kendaraan gagal disimpan.'], $id);
        }

        $vehicle = $this->vehicle((int) $data['vehicle_id']);
        $this->recordActivity($vehicle, 'Dokumen', $id, 'Diperbarui', "Dokumen {$data['jenis_dokumen']} dengan masa berlaku {$data['masa_berlaku']} diperbarui.");

        return redirect()->to($this->pageUrl('documents'))->with('success', 'Dokumen kendaraan berhasil diperbarui.');
    }

    public function destroyDocument(int $id): RedirectResponse
    {
        $isAdmin = $this->isAdmin();
        $model = new VehicleDocumentModel();
        if ($isAdmin) {
            $model->withDeleted();
        }
        $record = $this->findOrFail($model, $id, 'Dokumen kendaraan tidak ditemukan.');
        $vehicle = $this->vehicle((int) $record['vehicle_id'], $isAdmin);
        $description = $isAdmin
            ? "Dokumen {$record['jenis_dokumen']} dihapus permanen dari database."
            : "Dokumen {$record['jenis_dokumen']} dihapus dari tampilan Bagian Umum 2 dan masih dapat dilihat oleh Administrator.";
        $this->recordActivity($vehicle, 'Dokumen', $id, 'Dihapus', $description);
        if (! $model->delete($id, $isAdmin)) {
            return redirect()->to($this->pageUrl('documents'))->with('error', 'Dokumen kendaraan gagal dihapus.');
        }

        return redirect()->to($this->pageUrl('documents'))->with(
            'success',
            $isAdmin
                ? 'Dokumen kendaraan berhasil dihapus permanen dari database.'
                : 'Dokumen kendaraan berhasil dihapus dari tampilan Bagian Umum 2.',
        );
    }

    public function reports(): string|RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to($this->pageUrl('vehicles'))
                ->with('error', 'Riwayat & Laporan hanya dapat diakses oleh Administrator.');
        }

        $keyword = trim((string) $this->request->getGet('q'));
        $type = trim((string) $this->request->getGet('jenis'));
        $perPage = $this->perPage();
        $types = ['Kendaraan', 'Servis', 'Dokumen'];
        $model = new VehicleActivityLogModel();
        if ($keyword !== '') {
            $model->groupStart()->like('vehicle_label', $keyword)->orLike('description', $keyword)->orLike('actor_name', $keyword)->groupEnd();
        }
        if (in_array($type, $types, true)) {
            $model->where('entity_type', $type);
        } else {
            $type = '';
        }

        return view('monitoring_kendaraan/reports', [
            'title' => 'Riwayat & Laporan',
            'activePage' => 'reports',
            'records' => $model->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->paginate($perPage, 'reports'),
            'pager' => $model->pager,
            'filters' => compact('keyword', 'type', 'perPage'),
            'types' => $types,
        ]);
    }

    private function vehiclePayload(?array $existing = null): array
    {
        $vehicleName = $this->cleanText($this->request->getPost('nama_kendaraan'));
        $status = trim((string) $this->request->getPost('status'));
        $managementUnit = $this->nullIfEmpty($this->request->getPost('pic'));
        $initialKilometer = trim((string) $this->request->getPost('kilometer'));

        return [
            'nomor_polisi' => strtoupper($this->cleanText($this->request->getPost('nomor_polisi'))),
            'nama_kendaraan' => $vehicleName,
            'nama_kendaraan_lainnya' => $vehicleName === 'Lainnya'
                ? $this->nullIfEmpty($this->request->getPost('nama_kendaraan_lainnya'))
                : null,
            'jenis' => $this->cleanText($this->request->getPost('jenis')),
            'status_kendaraan' => $this->cleanText($this->request->getPost('status_kendaraan')),
            'merek' => $this->nullIfEmpty($this->request->getPost('merek')),
            'tipe' => $this->nullIfEmpty($this->request->getPost('tipe')),
            'tahun' => $this->nullIfEmpty($this->request->getPost('tahun')),
            'warna' => $this->nullIfEmpty($this->request->getPost('warna')),
            'nomor_rangka' => $this->nullIfEmpty($this->request->getPost('nomor_rangka')),
            'nomor_mesin' => $this->nullIfEmpty($this->request->getPost('nomor_mesin')),
            'unit_pengguna' => $this->nullIfEmpty($this->request->getPost('unit_pengguna')),
            'unit_pengguna_lainnya' => $this->request->getPost('unit_pengguna') === 'Lainnya'
                ? $this->nullIfEmpty($this->request->getPost('unit_pengguna_lainnya'))
                : null,
            'pic' => $managementUnit,
            'pic_internal' => $this->internalPicForUnit($managementUnit),
            // Kilometer awal dapat dicatat saat kendaraan dibuat. Setelah itu,
            // perubahannya hanya mengikuti KM Saat Servis tertinggi.
            'kilometer' => $existing === null
                ? ($initialKilometer === '' ? '0' : $initialKilometer)
                : (string) ($existing['kilometer'] ?? 0),
            'status' => $status,
            'status_lainnya' => $status === 'Lainnya'
                ? $this->nullIfEmpty($this->request->getPost('status_lainnya'))
                : null,
        ];
    }

    private function maintenancePayload(?array $existing = null): array
    {
        $submittedDate = trim((string) $this->request->getPost('tanggal_servis'));
        $serviceDate = $existing !== null && (string) session()->get('auth_role') !== 'admin'
            ? (string) $existing['tanggal_servis']
            : $submittedDate;
        $serviceBudget = trim((string) $this->request->getPost('anggaran_servis'));

        return [
            'vehicle_id' => trim((string) $this->request->getPost('vehicle_id')),
            'tanggal_servis' => $serviceDate,
            'jenis_perawatan' => $this->cleanText($this->request->getPost('jenis_perawatan')),
            'bengkel' => $this->nullIfEmpty($this->request->getPost('bengkel')),
            'kilometer' => $this->nullIfEmpty($this->request->getPost('kilometer')),
            'biaya' => trim((string) $this->request->getPost('biaya')) ?: '0',
            'servis_berikutnya_tanggal' => $this->nextServiceDate($serviceDate),
            'anggaran_servis' => $serviceBudget,
            'nama_perusahaan' => match ($serviceBudget) {
                'Kantor' => self::OFFICE_SERVICE_COMPANY,
                'Asuransi' => $this->nullIfEmpty($this->request->getPost('nama_perusahaan')),
                default => null,
            },
            'keterangan' => $this->nullIfEmpty($this->request->getPost('keterangan')),
            'link_berkas' => $this->nullIfEmpty($this->request->getPost('link_berkas')),
        ];
    }

    private function documentPayload(): array
    {
        return [
            'vehicle_id' => trim((string) $this->request->getPost('vehicle_id')),
            'jenis_dokumen' => trim((string) $this->request->getPost('jenis_dokumen')),
            'nomor_dokumen' => $this->nullIfEmpty($this->request->getPost('nomor_dokumen')),
            'tanggal_terbit' => $this->nullIfEmpty($this->request->getPost('tanggal_terbit')),
            'masa_berlaku' => trim((string) $this->request->getPost('masa_berlaku')),
            'link_berkas' => $this->nullIfEmpty($this->request->getPost('link_berkas')),
            'keterangan' => $this->nullIfEmpty($this->request->getPost('keterangan')),
        ];
    }

    private function validateVehicle(array $data, ?int $id = null): array
    {
        $uniquePlate = 'is_unique[vehicles.nomor_polisi' . ($id === null ? ']' : ',id,' . $id . ']');
        $validation = service('validation');
        $validation->setRules([
            'nomor_polisi' => ['label' => 'Nomor polisi', 'rules' => 'required|max_length[20]|' . $uniquePlate],
            'nama_kendaraan' => ['label' => 'Nama kendaraan', 'rules' => 'required|in_list[' . implode(',', self::VEHICLE_NAMES) . ']'],
            'nama_kendaraan_lainnya' => [
                'label' => 'Nama kendaraan lainnya',
                'rules' => ($data['nama_kendaraan'] ?? '') === 'Lainnya' ? 'required|max_length[150]' : 'permit_empty|max_length[150]',
            ],
            'jenis' => ['label' => 'Jenis kendaraan', 'rules' => 'required|in_list[' . implode(',', self::VEHICLE_TYPES) . ']'],
            'status_kendaraan' => ['label' => 'Status kendaraan', 'rules' => 'required|in_list[' . implode(',', self::VEHICLE_OWNERSHIP_STATUSES) . ']'],
            'merek' => ['label' => 'Merek', 'rules' => 'permit_empty|max_length[100]'],
            'tipe' => ['label' => 'Tipe', 'rules' => 'permit_empty|max_length[100]'],
            'tahun' => ['label' => 'Tahun', 'rules' => 'permit_empty|integer|greater_than_equal_to[1900]|less_than_equal_to[' . ((int) date('Y') + 1) . ']'],
            'warna' => ['label' => 'Warna', 'rules' => 'permit_empty|max_length[60]'],
            'nomor_rangka' => ['label' => 'Nomor rangka', 'rules' => 'permit_empty|max_length[100]'],
            'nomor_mesin' => ['label' => 'Nomor mesin', 'rules' => 'permit_empty|max_length[100]'],
            'unit_pengguna' => ['label' => 'Driver pengguna', 'rules' => 'permit_empty|in_list[' . implode(',', self::VEHICLE_DRIVERS) . ']'],
            'unit_pengguna_lainnya' => [
                'label' => 'Nama driver lainnya',
                'rules' => ($data['unit_pengguna'] ?? '') === 'Lainnya' ? 'required|max_length[150]' : 'permit_empty|max_length[150]',
            ],
            'pic' => ['label' => 'Unit pengelola', 'rules' => 'permit_empty|in_list[' . implode(',', self::VEHICLE_MANAGEMENT_UNITS) . ']'],
            'pic_internal' => ['label' => 'PIC internal', 'rules' => 'permit_empty|in_list[Angger Wicaksono,Agil Halis Kesawa]'],
            'kilometer' => ['label' => 'Kilometer', 'rules' => 'required|integer|greater_than_equal_to[0]'],
            'status' => ['label' => 'Status', 'rules' => 'required|in_list[' . implode(',', self::VEHICLE_STATUSES) . ']'],
            'status_lainnya' => [
                'label' => 'Status lainnya',
                'rules' => ($data['status'] ?? '') === 'Lainnya' ? 'required|max_length[100]' : 'permit_empty|max_length[100]',
            ],
        ]);

        return $validation->run($data) ? [] : $validation->getErrors();
    }

    private function validateMaintenance(array $data): array
    {
        $validation = service('validation');
        $validation->setRules([
            'vehicle_id' => ['label' => 'Kendaraan', 'rules' => 'required|integer|greater_than[0]'],
            'tanggal_servis' => ['label' => 'Tanggal servis', 'rules' => 'required|valid_date[Y-m-d]'],
            'jenis_perawatan' => ['label' => 'Jenis perawatan', 'rules' => 'required|max_length[150]'],
            'bengkel' => ['label' => 'Bengkel', 'rules' => 'permit_empty|max_length[150]'],
            'kilometer' => ['label' => 'Kilometer saat servis', 'rules' => 'required|integer|greater_than_equal_to[0]'],
            'biaya' => ['label' => 'Biaya', 'rules' => 'required|decimal|greater_than_equal_to[0]'],
            'servis_berikutnya_tanggal' => ['label' => 'Tanggal servis berikutnya', 'rules' => 'permit_empty|valid_date[Y-m-d]'],
            'anggaran_servis' => ['label' => 'Anggaran Service', 'rules' => 'required|in_list[' . implode(',', self::SERVICE_BUDGETS) . ']'],
            'nama_perusahaan' => [
                'label' => 'Nama perusahaan',
                'rules' => ($data['anggaran_servis'] ?? '') === 'Kantor'
                    ? 'required|in_list[' . self::OFFICE_SERVICE_COMPANY . ']'
                    : (($data['anggaran_servis'] ?? '') === 'Asuransi' ? 'required|max_length[150]' : 'permit_empty|max_length[150]'),
            ],
            'keterangan' => ['label' => 'Keterangan', 'rules' => 'permit_empty|max_length[5000]'],
            'link_berkas' => ['label' => 'Link berkas', 'rules' => 'permit_empty|max_length[2048]'],
        ]);
        $validation->run($data);
        $errors = $validation->getErrors();
        if ($errors === [] && (new VehicleModel())->find((int) $data['vehicle_id']) === null) {
            $errors['vehicle_id'] = 'Kendaraan yang dipilih tidak ditemukan.';
        }
        if ($data['link_berkas'] !== null && ! $this->isValidUrl($data['link_berkas'])) {
            $errors['link_berkas'] = 'Link berkas harus berupa URL http atau https yang valid.';
        }

        return $errors;
    }

    private function validateDocument(array $data): array
    {
        $validation = service('validation');
        $validation->setRules([
            'vehicle_id' => ['label' => 'Kendaraan', 'rules' => 'required|integer|greater_than[0]'],
            'jenis_dokumen' => ['label' => 'Jenis dokumen', 'rules' => 'required|in_list[' . implode(',', self::DOCUMENT_TYPES) . ']'],
            'nomor_dokumen' => ['label' => 'Nomor dokumen', 'rules' => 'permit_empty|max_length[150]'],
            'tanggal_terbit' => ['label' => 'Tanggal terbit', 'rules' => 'permit_empty|valid_date[Y-m-d]'],
            'masa_berlaku' => ['label' => 'Masa berlaku', 'rules' => 'required|valid_date[Y-m-d]'],
            'link_berkas' => ['label' => 'Link berkas', 'rules' => 'permit_empty|max_length[2048]'],
            'keterangan' => ['label' => 'Keterangan', 'rules' => 'permit_empty|max_length[5000]'],
        ]);
        $validation->run($data);
        $errors = $validation->getErrors();
        if ($errors === [] && (new VehicleModel())->find((int) $data['vehicle_id']) === null) {
            $errors['vehicle_id'] = 'Kendaraan yang dipilih tidak ditemukan.';
        }
        if ($data['link_berkas'] !== null && ! $this->isValidUrl($data['link_berkas'])) {
            $errors['link_berkas'] = 'Link berkas harus berupa URL http atau https yang valid.';
        }

        return $errors;
    }

    private function formError(string $page, string $mode, array $data, array $errors, ?int $id = null): RedirectResponse
    {
        return redirect()->to($this->pageUrl($page))->with([
            'errors' => $errors,
            'vehicle_crud_modal' => $mode,
            'vehicle_crud_data' => $data,
            'vehicle_crud_edit_id' => $id,
        ]);
    }

    private function recordActivity(array $vehicle, string $entityType, int $entityId, string $action, string $description): void
    {
        (new VehicleActivityLogModel())->insert([
            'vehicle_id' => (int) $vehicle['id'],
            'vehicle_label' => $vehicle['nomor_polisi'] . ' · ' . $this->vehicleDisplayName($vehicle),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'description' => $description,
            'actor_name' => $this->actorName(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function decorateDocuments(array $records): array
    {
        $today = new \DateTimeImmutable('today');

        return array_map(static function (array $record) use ($today): array {
            $expiry = new \DateTimeImmutable($record['masa_berlaku']);
            $days = (int) $today->diff($expiry)->format('%r%a');
            $record['remaining_days'] = $days;
            if ($days < 0) {
                $record['status_label'] = 'Kedaluwarsa';
                $record['status_class'] = 'expired';
                $record['remaining_label'] = abs($days) . ' hari lewat';
            } elseif ($days <= 30) {
                $record['status_label'] = 'Segera Berakhir';
                $record['status_class'] = 'warning';
                $record['remaining_label'] = $days . ' hari lagi';
            } else {
                $record['status_label'] = 'Aktif';
                $record['status_class'] = 'active';
                $record['remaining_label'] = $days . ' hari lagi';
            }

            return $record;
        }, $records);
    }

    private function synchronizeKilometer(int $vehicleId): void
    {
        $service = (new VehicleMaintenanceModel())
            ->selectMax('kilometer', 'kilometer_terakhir')
            ->where('vehicle_id', $vehicleId)
            ->first();

        (new VehicleModel())->update($vehicleId, [
            'kilometer' => (int) ($service['kilometer_terakhir'] ?? 0),
        ]);
    }

    private function nextServiceDate(string $serviceDate): ?string
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $serviceDate);
        if ($date === false || $date->format('Y-m-d') !== $serviceDate) {
            return null;
        }

        $targetMonth = $date->modify('first day of +3 months');
        $targetDay = min((int) $date->format('d'), (int) $targetMonth->format('t'));

        return $targetMonth->setDate(
            (int) $targetMonth->format('Y'),
            (int) $targetMonth->format('m'),
            $targetDay,
        )->format('Y-m-d');
    }

    private function vehicleOptions(): array
    {
        return array_map(function (array $vehicle): array {
            $vehicle['nama_tampilan'] = $this->vehicleDisplayName($vehicle);

            return $vehicle;
        }, (new VehicleModel())->orderBy('nomor_polisi', 'ASC')->findAll());
    }

    private function vehicleDisplayName(array $vehicle): string
    {
        if (($vehicle['nama_kendaraan'] ?? '') === 'Lainnya' && ! empty($vehicle['nama_kendaraan_lainnya'])) {
            return (string) $vehicle['nama_kendaraan_lainnya'];
        }

        return (string) ($vehicle['nama_kendaraan'] ?? '-');
    }

    private function internalPicForUnit(?string $unit): ?string
    {
        return match ($unit) {
            'Bagian Umum 1' => 'Angger Wicaksono',
            'Bagian Umum 2' => 'Agil Halis Kesawa',
            default => null,
        };
    }

    private function vehicle(int $id, bool $withDeleted = false): array
    {
        $model = new VehicleModel();
        if ($withDeleted) {
            $model->withDeleted();
        }

        return $this->findOrFail($model, $id, 'Kendaraan tidak ditemukan.');
    }

    private function isAdmin(): bool
    {
        return (string) session()->get('auth_role') === 'admin';
    }

    private function findOrFail($model, int $id, string $message): array
    {
        $record = $model->find($id);
        if ($record === null) {
            throw PageNotFoundException::forPageNotFound($message);
        }

        return $record;
    }

    private function pageUrl(string $page): string
    {
        $paths = ['vehicles' => 'data-kendaraan', 'maintenance' => 'servis-perawatan', 'documents' => 'dokumen-kendaraan', 'reports' => 'riwayat-laporan'];

        return site_url('bagian-umum-2/monitoring-kendaraan/' . $paths[$page]);
    }

    private function perPage(): int
    {
        $value = (int) $this->request->getGet('per_page');

        return in_array($value, [10, 20, 50, 100], true) ? $value : 10;
    }

    private function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    private function cleanText($value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', (string) $value)));
    }

    private function nullIfEmpty($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function actorId(): ?int
    {
        $id = session()->get('auth_user_id');

        return $id === null ? null : (int) $id;
    }

    private function actorName(): string
    {
        return (string) (session()->get('auth_display_name') ?: session()->get('auth_username') ?: 'Pengguna');
    }
}
