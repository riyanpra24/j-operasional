<?php

namespace App\Controllers;

use App\Models\DataAsetModel;
use App\Libraries\AssetPeriod;
use App\Libraries\AssetWorkbookParser;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class DataAset extends BaseController
{
    private const GROUPS = ['Gedung', 'Kendaraan', 'Mesin', 'Perabot', 'Peralatan', 'Peralatan Teknologi Informasi', 'Renovasi dan Instalasi', 'Rumah Dinas', 'Tanah'];
    private const STATUSES = ['Sedang Digunakan', 'Berfungsi', 'Sedang Dibangun/Dikembangkan'];
    private const FIELDS = ['kategori', 'kelompok_aset', 'kodefikasi_aset', 'nomor_aset', 'nama_aset', 'status_aset', 'tanggal_perolehan', 'tanggal_habis_manfaat', 'lokasi', 'foto_aset'];
    private const URL = 'bagian-umum-1/data-aset';

    public function index(): string
    {
        $filters = [];
        foreach (['q', 'kelompok', 'status', 'dari', 'sampai'] as $key) {
            $value = $this->request->getGet($key);
            $filters[$key] = is_string($value) ? trim($value) : '';
        }
        $perPage = (int) $this->request->getGet('per_page');
        $filters['perPage'] = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 10;
        $model = new DataAsetModel();
        if ($filters['q'] !== '') {
            $model->groupStart()->like('kelompok_aset', $filters['q'])->orLike('nama_aset', $filters['q'])->orLike('kodefikasi_aset', $filters['q'])->orLike('nomor_aset', $filters['q'])->groupEnd();
        }
        if (in_array($filters['kelompok'], self::GROUPS, true)) { $model->where('kategori', $filters['kelompok']); }
        if (in_array($filters['status'], self::STATUSES, true)) { $model->where('status_aset', $filters['status']); }
        foreach (['dari' => '>=', 'sampai' => '<='] as $key => $operator) {
            if ($this->validDate($filters[$key])) { $model->where('tanggal_perolehan ' . $operator, $filters[$key]); }
        }
        $statusRows = (new DataAsetModel())->select('status_aset')->distinct()->findAll();
        $statuses = array_values(array_unique(array_merge(self::STATUSES, array_column($statusRows, 'status_aset'))));
        if ($filters['status'] !== '' && in_array($filters['status'], $statuses, true) && ! in_array($filters['status'], self::STATUSES, true)) {
            $model->where('status_aset', $filters['status']);
        }
        // Keep each category together and preserve the source order of its assets.
        $grouped = [];
        foreach ($model->orderBy('id', 'ASC')->findAll() as $record) {
            $grouped[$record['kategori']][] = $record;
        }
        $orderedGroups = [];
        foreach (self::GROUPS as $group) {
            if (isset($grouped[$group])) {
                $orderedGroups[$group] = $grouped[$group];
                unset($grouped[$group]);
            }
        }
        $orderedGroups += $grouped;
        $orderedRecords = [];
        foreach ($orderedGroups as $records) {
            foreach ($records as $record) {
                $orderedRecords[] = $record;
            }
        }
        $totalRecords = count($orderedRecords);
        $page = max(1, (int) $this->request->getGet('page_aset'));
        $page = min($page, max(1, (int) ceil($totalRecords / $filters['perPage'])));
        $groups = [];
        foreach (array_slice($orderedRecords, ($page - 1) * $filters['perPage'], $filters['perPage']) as $record) {
            $groups[$record['kategori']][] = AssetPeriod::forRecord($record);
        }
        $pagination = $totalRecords > 0 ? service('pager')->makeLinks($page, $filters['perPage'], $totalRecords, 'table_full', 0, 'aset') : '';
        return view('data_aset/index', ['title' => 'Data Aset', 'filters' => $filters, 'assetGroups' => self::GROUPS, 'assetStatuses' => $statuses, 'groups' => $groups, 'pagination' => $pagination, 'popupState' => session()->getFlashdata('asset_modal'), 'isAdmin' => $this->currentRoleIsAdmin()]);
    }

    public function create(): string { return $this->formView([]); }
    public function show(int $id): string { return view('data_aset/show', ['title' => 'Detail Aset', 'record' => $this->findAsset($id)]); }
    public function edit(int $id): string { return $this->formView($this->findAsset($id)); }
    public function store(): RedirectResponse { return $this->saveAsset(); }
    public function update(int $id): RedirectResponse { $this->findAsset($id); return $this->saveAsset($id); }

    public function import(): RedirectResponse
    {
        if (! $this->currentRoleIsAdmin()) {
            return redirect()->to(site_url(self::URL))->with('error', 'Impor Excel hanya tersedia untuk administrator.');
        }
        $file = $this->request->getFile('asset_excel');
        if ($file === null || ! $file->isValid() || $file->hasMoved() || strtolower($file->getClientExtension()) !== 'xlsx' || $file->getSize() > 5 * 1024 * 1024) {
            return redirect()->to(site_url(self::URL))->with('error', 'Pilih file Excel .xlsx dengan ukuran maksimal 5 MB.');
        }
        try {
            $rows = (new AssetWorkbookParser())->parse($file->getTempName(), self::GROUPS);
        } catch (\RuntimeException $exception) {
            return redirect()->to(site_url(self::URL))->with('error', $exception->getMessage());
        }
        $db = db_connect();
        $model = new DataAsetModel();
        $added = 0; $skipped = 0;
        $db->transBegin();
        try {
            foreach ($rows as $row) {
                if ($db->table('data_aset')->where('nomor_aset', $row['nomor_aset'])->countAllResults() > 0) { $skipped++; continue; }
                if (! $model->insert($row)) { throw new \RuntimeException('Data aset gagal disimpan.'); }
                $added++;
            }
            if (! $db->transStatus() || ! $db->transCommit()) { throw new \RuntimeException('Data aset gagal disimpan.'); }
        } catch (\Throwable $exception) {
            $db->transRollback();
            log_message('error', 'Impor Data Aset gagal: {message}', ['message' => $exception->getMessage()]);
            return redirect()->to(site_url(self::URL))->with('error', 'Impor gagal dan tidak ada data yang ditambahkan. Periksa nomor aset dan coba lagi.');
        }
        return redirect()->to(site_url(self::URL))->with('success', "Impor selesai: $added aset ditambahkan, $skipped nomor aset yang sudah ada dilewati.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->findAsset($id);
        if (! $this->deleteRecord(new DataAsetModel(), 'data_aset', $id)) {
            return redirect()->to(site_url(self::URL))->with('error', 'Data aset gagal dihapus.');
        }
        return redirect()->to(site_url(self::URL))->with('success', $this->currentRoleIsAdmin() ? 'Data aset berhasil dihapus permanen.' : 'Data aset berhasil dihapus.');
    }

    private function saveAsset(?int $id = null): RedirectResponse
    {
        $data = [];
        foreach (self::FIELDS as $field) {
            $value = $this->request->getPost($field);
            $data[$field] = is_string($value) ? trim($value) : '';
        }
        $rules = [
            'kategori' => 'required|in_list[' . implode(',', self::GROUPS) . ']',
            'kelompok_aset' => 'required|max_length[255]', 'kodefikasi_aset' => 'required|max_length[100]',
            'nomor_aset' => 'required|max_length[100]', 'nama_aset' => 'required|max_length[150]',
            'status_aset' => 'required|max_length[100]', 'tanggal_perolehan' => 'permit_empty|valid_date[Y-m-d]',
            'tanggal_habis_manfaat' => 'permit_empty|valid_date[Y-m-d]',
            'lokasi' => 'permit_empty|max_length[255]',
            'foto_aset' => 'permit_empty|max_length[2048]',
        ];
        $validator = service('validation');
        $validator->setRules($rules);
        $errors = $validator->run($data) ? [] : $validator->getErrors();
        if ($this->validDate($data['tanggal_perolehan']) && $this->validDate($data['tanggal_habis_manfaat']) && $data['tanggal_habis_manfaat'] < $data['tanggal_perolehan']) {
            $errors['tanggal_habis_manfaat'] = 'Tanggal habis masa manfaat tidak boleh sebelum tanggal perolehan.';
        }
        if ($data['foto_aset'] !== '' && (! filter_var($data['foto_aset'], FILTER_VALIDATE_URL) || ! in_array(strtolower((string) parse_url($data['foto_aset'], PHP_URL_SCHEME)), ['http', 'https'], true))) {
            $errors['foto_aset'] = 'Tautan foto aset harus berupa alamat http atau https yang valid.';
        }
        $duplicate = (new DataAsetModel())->withDeleted()->where('nomor_aset', $data['nomor_aset'])->first();
        if ($duplicate && (int) $duplicate['id'] !== $id) { $errors['nomor_aset'] = 'Nomor aset sudah digunakan, termasuk data yang telah dihapus.'; }
        if ($errors !== []) {
            if ($this->request->getPost('asset_modal') === '1') {
                return $this->modalError($id, $data, array_values($errors));
            }
            $url = $id === null ? self::URL . '/tambah' : self::URL . '/' . $id . '/ubah';
            return redirect()->to(site_url($url))->withInput()->with('errors', $errors);
        }
        foreach (['tanggal_perolehan', 'tanggal_habis_manfaat', 'lokasi', 'foto_aset'] as $field) {
            if ($data[$field] === '') { $data[$field] = null; }
        }
        $period = AssetPeriod::months($data['tanggal_perolehan'], $data['tanggal_habis_manfaat']);
        $remaining = AssetPeriod::remaining($data['tanggal_habis_manfaat']);
        if ($period !== null) { $data['masa_manfaat'] = $period; }
        if ($remaining !== null) { $data['sisa_masa_manfaat'] = $remaining; }
        $model = new DataAsetModel();
        try {
            $saved = $id === null ? $model->insert($data) : $model->update($id, $data);
        } catch (\CodeIgniter\Database\Exceptions\DatabaseException $exception) {
            log_message('error', 'Gagal menyimpan Data Aset: {message}', ['message' => $exception->getMessage()]);
            if ($this->request->getPost('asset_modal') === '1') { return $this->modalError($id, $data, ['Data aset gagal disimpan. Periksa kembali nomor aset.']); }
            return redirect()->back()->withInput()->with('error', 'Data aset gagal disimpan. Periksa kembali nomor aset.');
        }
        if (! $saved) {
            if ($this->request->getPost('asset_modal') === '1') { return $this->modalError($id, $data, ['Data aset gagal disimpan.']); }
            return redirect()->back()->withInput()->with('error', 'Data aset gagal disimpan.');
        }
        return redirect()->to(site_url(self::URL))->with('success', $id === null ? 'Data aset berhasil ditambahkan.' : 'Data aset berhasil diperbarui.');
    }

    private function modalError(?int $id, array $data, array $errors): RedirectResponse
    {
        return redirect()->to(site_url(self::URL))->with('asset_modal', ['id' => $id, 'values' => $data, 'errors' => $errors]);
    }

    private function formView(array $record): string
    {
        return view('data_aset/form', ['title' => $record === [] ? 'Tambah Aset' : 'Ubah Aset', 'record' => $record, 'assetGroups' => self::GROUPS]);
    }
    private function findAsset(int $id): array
    {
        $record = (new DataAsetModel())->find($id);
        if ($record === null) { throw PageNotFoundException::forPageNotFound('Data aset tidak ditemukan.'); }
        return AssetPeriod::forRecord($record);
    }
    private function validDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
