<?php

namespace App\Controllers;

use App\Models\PksDokumenModel;
use App\Models\PksItemModel;
use App\Models\PksKerjasamaModel;
use App\Models\PksMitraModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class PksBarangJasa extends BaseController
{
    private PksKerjasamaModel $kerjasama;
    private PksMitraModel $mitra;
    private PksDokumenModel $dokumen;
    private PksItemModel $item;

    public function __construct()
    {
        $this->kerjasama = new PksKerjasamaModel();
        $this->mitra = new PksMitraModel();
        $this->dokumen = new PksDokumenModel();
        $this->item = new PksItemModel();
    }

    public function index(): string
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $perPage = (int) $this->request->getGet('per_page');

        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 10;
        }

        $summaryRows = $this->decorateRows($this->baseListQuery()->findAll());
        $summary = ['total' => count($summaryRows), 'aktif' => 0, 'segera' => 0, 'berakhir' => 0, 'belum' => 0];
        foreach ($summaryRows as $row) {
            $summary[$row['status_key']]++;
        }

        $query = $this->baseListQuery();
        if ($keyword !== '') {
            $query->groupStart()
                ->like('pks_kerjasama.kode_internal', $keyword)
                ->orLike('pks_kerjasama.nama_kerjasama', $keyword)
                ->orLike('pks_mitra.nama_mitra', $keyword)
                ->orLike('pks_kerjasama.unit_pengelola', $keyword)
                ->groupEnd();
        }

        return view('pks/index', [
            'title' => 'PKS Barang dan Jasa',
            'records' => $this->decorateRows($query->orderBy('pks_kerjasama.updated_at', 'DESC')->paginate($perPage, 'pks')),
            'pager' => $this->kerjasama->pager,
            'filters' => compact('keyword', 'perPage'),
            'summary' => $summary,
        ]);
    }

    public function create(): string
    {
        return view('pks/form', [
            'title' => 'Tambah PKS Barang dan Jasa',
            'record' => null,
            'action' => site_url('bagian-umum-1/pks-barang-jasa'),
        ]);
    }

    public function store(): RedirectResponse
    {
        $data = $this->mainPayload();
        $errors = $this->validateMain($data);
        if ($errors !== []) {
            return $this->mainFormError('create', $data, $errors);
        }

        $db = db_connect();
        $db->transStart();
        $mitraId = $this->mitra->insert($this->mitraData($data), true);
        $kerjasamaId = $this->kerjasama->insert($this->kerjasamaData($data, (int) $mitraId), true);
        $db->transComplete();

        if (! $db->transStatus() || ! $kerjasamaId) {
            return $this->mainFormError('create', $data, ['pks' => 'Data PKS gagal disimpan. Silakan coba kembali.']);
        }

        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa/' . $kerjasamaId))
            ->with('success', 'Data utama PKS berhasil dibuat. Silakan tambahkan dokumen PKS/addendum dan item pekerjaan.');
    }

    public function show(int $id): string
    {
        $record = $this->findRecord($id);
        $documents = $this->dokumen->where('kerjasama_id', $id)->orderBy('urutan', 'ASC')->findAll();
        $items = $this->item->where('kerjasama_id', $id)->orderBy('id', 'ASC')->findAll();
        $latest = $documents === [] ? null : $documents[array_key_last($documents)];
        $status = $this->statusFromDates($latest['periode_mulai'] ?? null, $latest['periode_selesai'] ?? null);

        return view('pks/show', [
            'title' => 'Detail PKS Barang dan Jasa',
            'record' => $record,
            'documents' => $documents,
            'items' => $items,
            'latest' => $latest,
            'status' => $status,
            'nextSequence' => $documents === [] ? 1 : ((int) max(array_column($documents, 'urutan')) + 1),
        ]);
    }

    public function edit(int $id): string
    {
        return view('pks/form', [
            'title' => 'Ubah PKS Barang dan Jasa',
            'record' => $this->findRecord($id),
            'action' => site_url('bagian-umum-1/pks-barang-jasa/' . $id),
        ]);
    }

    public function update(int $id): RedirectResponse
    {
        $record = $this->findRecord($id);
        $data = $this->mainPayload();
        $errors = $this->validateMain($data, $id);
        if ($errors !== []) {
            return $this->mainFormError('edit', $data, $errors, $id);
        }

        $db = db_connect();
        $db->transStart();
        $this->mitra->update((int) $record['mitra_id'], $this->mitraData($data));
        $this->kerjasama->update($id, $this->kerjasamaData($data, (int) $record['mitra_id']));
        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->mainFormError('edit', $data, ['pks' => 'Perubahan data PKS gagal disimpan.'], $id);
        }

        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa/' . $id))->with('success', 'Data utama PKS berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $record = $this->findRecord($id);
        $mitraId = (int) $record['mitra_id'];
        $db = db_connect();
        $db->transStart();
        $this->kerjasama->delete($id);
        if ($this->kerjasama->where('mitra_id', $mitraId)->countAllResults() === 0) {
            $this->mitra->delete($mitraId);
        }
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa'))->with('error', 'Data PKS gagal dihapus.');
        }

        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa'))->with('success', 'PKS ' . $record['kode_internal'] . ' beserta riwayatnya berhasil dihapus.');
    }

    public function storeDocument(int $id): RedirectResponse
    {
        $this->findRecord($id);
        $data = $this->documentPayload($id);
        $errors = $this->validateDocument($data);
        if ($errors !== []) {
            return $this->detailError($id, $errors, 'dokumen');
        }

        if (! $this->dokumen->insert($data)) {
            return $this->detailError($id, $this->dokumen->errors() ?: ['dokumen' => 'Dokumen gagal disimpan.'], 'dokumen');
        }

        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa/' . $id) . '#riwayat-dokumen')->with('success', 'Riwayat dokumen berhasil ditambahkan.');
    }

    public function updateDocument(int $id, int $documentId): RedirectResponse
    {
        $this->findOwnedDocument($id, $documentId);
        $data = $this->documentPayload($id);
        $errors = $this->validateDocument($data, $documentId);
        if ($errors !== []) {
            return $this->detailError($id, $errors, 'dokumen');
        }

        if (! $this->dokumen->update($documentId, $data)) {
            return $this->detailError($id, $this->dokumen->errors() ?: ['dokumen' => 'Dokumen gagal diperbarui.'], 'dokumen');
        }

        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa/' . $id) . '#riwayat-dokumen')->with('success', 'Riwayat dokumen berhasil diperbarui.');
    }

    public function destroyDocument(int $id, int $documentId): RedirectResponse
    {
        $document = $this->findOwnedDocument($id, $documentId);
        $this->dokumen->delete($documentId);

        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa/' . $id) . '#riwayat-dokumen')->with('success', $document['jenis_dokumen'] . ' nomor ' . $document['nomor_dokumen'] . ' berhasil dihapus.');
    }

    public function storeItem(int $id): RedirectResponse
    {
        $this->findRecord($id);
        $data = $this->itemPayload($id);
        $errors = $this->validateItem($data);
        if ($errors !== []) {
            return $this->detailError($id, $errors, 'item');
        }

        $this->item->insert($data);
        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa/' . $id) . '#item-pekerjaan')->with('success', 'Item pekerjaan berhasil ditambahkan.');
    }

    public function updateItem(int $id, int $itemId): RedirectResponse
    {
        $this->findOwnedItem($id, $itemId);
        $data = $this->itemPayload($id);
        $errors = $this->validateItem($data);
        if ($errors !== []) {
            return $this->detailError($id, $errors, 'item');
        }

        $this->item->update($itemId, $data);
        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa/' . $id) . '#item-pekerjaan')->with('success', 'Item pekerjaan berhasil diperbarui.');
    }

    public function destroyItem(int $id, int $itemId): RedirectResponse
    {
        $item = $this->findOwnedItem($id, $itemId);
        $this->item->delete($itemId);
        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa/' . $id) . '#item-pekerjaan')->with('success', 'Item ' . $item['nama_item'] . ' berhasil dihapus.');
    }

    private function baseListQuery(): PksKerjasamaModel
    {
        return $this->kerjasama
            ->select("pks_kerjasama.*, pks_mitra.nama_mitra, pks_mitra.alamat, pks_mitra.nama_kontak, pks_mitra.jabatan_kontak, pks_mitra.telepon, pks_mitra.email,
                (SELECT d.periode_mulai FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = pks_kerjasama.id ORDER BY d.urutan DESC LIMIT 1) AS periode_mulai_terakhir,
                (SELECT d.periode_selesai FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = pks_kerjasama.id ORDER BY d.urutan DESC LIMIT 1) AS periode_selesai_terakhir,
                (SELECT d.nomor_dokumen FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = pks_kerjasama.id ORDER BY d.urutan DESC LIMIT 1) AS nomor_dokumen_terakhir,
                (SELECT d.nilai FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = pks_kerjasama.id ORDER BY d.urutan DESC LIMIT 1) AS nilai_terakhir,
                (SELECT COUNT(*) FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = pks_kerjasama.id) AS jumlah_dokumen", false)
            ->join('pks_mitra', 'pks_mitra.id = pks_kerjasama.mitra_id');
    }

    private function decorateRows(array $rows): array
    {
        foreach ($rows as &$row) {
            $status = $this->statusFromDates($row['periode_mulai_terakhir'] ?? null, $row['periode_selesai_terakhir'] ?? null);
            $row += $status;
        }
        unset($row);
        return $rows;
    }

    private function statusFromDates(?string $start, ?string $end): array
    {
        if (! $start || ! $end) {
            return ['status_key' => 'belum', 'status_label' => 'Belum ada dokumen', 'status_class' => 'neutral'];
        }

        $today = new \DateTimeImmutable('today');
        $startDate = new \DateTimeImmutable($start);
        $endDate = new \DateTimeImmutable($end);
        if ($today < $startDate) {
            return ['status_key' => 'belum', 'status_label' => 'Belum dimulai', 'status_class' => 'neutral'];
        }
        if ($today > $endDate) {
            return ['status_key' => 'berakhir', 'status_label' => 'Berakhir', 'status_class' => 'expired'];
        }
        if ((int) $today->diff($endDate)->format('%a') <= 30) {
            return ['status_key' => 'segera', 'status_label' => 'Segera berakhir', 'status_class' => 'warning'];
        }
        return ['status_key' => 'aktif', 'status_label' => 'Aktif', 'status_class' => 'active'];
    }

    private function mainPayload(): array
    {
        $fields = ['kode_internal', 'nama_kerjasama', 'unit_pengelola', 'pic_internal', 'ruang_lingkup', 'keterangan', 'nama_mitra', 'alamat', 'nama_kontak', 'jabatan_kontak', 'telepon', 'email'];
        $data = [];
        foreach ($fields as $field) {
            $data[$field] = trim((string) $this->request->getPost($field));
        }
        return $data;
    }

    private function validateMain(array $data, ?int $id = null): array
    {
        $validation = service('validation');
        $validation->setRules([
            'kode_internal' => 'required|max_length[80]',
            'nama_kerjasama' => 'required|max_length[250]',
            'unit_pengelola' => 'permit_empty|max_length[150]',
            'pic_internal' => 'permit_empty|max_length[150]',
            'nama_mitra' => 'required|max_length[200]',
            'nama_kontak' => 'permit_empty|max_length[150]',
            'jabatan_kontak' => 'permit_empty|max_length[150]',
            'telepon' => 'permit_empty|max_length[50]',
            'email' => 'permit_empty|valid_email|max_length[150]',
        ]);
        $validation->run($data);
        $errors = $validation->getErrors();
        $duplicate = $this->kerjasama->where('kode_internal', $data['kode_internal']);
        if ($id !== null) {
            $duplicate->where('id !=', $id);
        }
        if ($data['kode_internal'] !== '' && $duplicate->first() !== null) {
            $errors['kode_internal'] = 'Kode internal sudah digunakan pada PKS lain.';
        }
        return $errors;
    }

    private function mitraData(array $data): array
    {
        return array_intersect_key($data, array_flip(['nama_mitra', 'alamat', 'nama_kontak', 'jabatan_kontak', 'telepon', 'email']));
    }

    private function kerjasamaData(array $data, int $mitraId): array
    {
        return ['mitra_id' => $mitraId] + array_intersect_key($data, array_flip(['kode_internal', 'nama_kerjasama', 'unit_pengelola', 'pic_internal', 'ruang_lingkup', 'keterangan']));
    }

    private function documentPayload(int $id): array
    {
        return [
            'kerjasama_id' => $id,
            'jenis_dokumen' => trim((string) $this->request->getPost('jenis_dokumen')),
            'urutan' => (int) $this->request->getPost('urutan'),
            'nomor_dokumen' => trim((string) $this->request->getPost('nomor_dokumen')),
            'tanggal_dokumen' => trim((string) $this->request->getPost('tanggal_dokumen')),
            'periode_mulai' => trim((string) $this->request->getPost('periode_mulai')),
            'periode_selesai' => trim((string) $this->request->getPost('periode_selesai')),
            'nilai' => (float) $this->request->getPost('nilai'),
            'link_berkas' => trim((string) $this->request->getPost('link_berkas')) ?: null,
            'keterangan' => trim((string) $this->request->getPost('keterangan')) ?: null,
        ];
    }

    private function validateDocument(array $data, ?int $documentId = null): array
    {
        $validation = service('validation');
        $validation->setRules([
            'jenis_dokumen' => 'required|in_list[PKS,Addendum]',
            'urutan' => 'required|greater_than_equal_to[1]',
            'nomor_dokumen' => 'required|max_length[200]',
            'tanggal_dokumen' => 'required|valid_date[Y-m-d]',
            'periode_mulai' => 'required|valid_date[Y-m-d]',
            'periode_selesai' => 'required|valid_date[Y-m-d]',
            'nilai' => 'required|greater_than_equal_to[0]',
            'link_berkas' => 'permit_empty|max_length[2048]',
        ]);
        $validation->run($data);
        $errors = $validation->getErrors();
        if ($data['periode_mulai'] !== '' && $data['periode_selesai'] !== '' && $data['periode_selesai'] < $data['periode_mulai']) {
            $errors['periode_selesai'] = 'Periode selesai tidak boleh lebih awal dari periode mulai.';
        }
        if ($data['link_berkas'] && (! filter_var($data['link_berkas'], FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $data['link_berkas']))) {
            $errors['link_berkas'] = 'Link berkas harus berupa alamat http atau https yang valid.';
        }
        $duplicate = $this->dokumen->where('kerjasama_id', $data['kerjasama_id'])->where('urutan', $data['urutan']);
        if ($documentId !== null) {
            $duplicate->where('id !=', $documentId);
        }
        if ($duplicate->first() !== null) {
            $errors['urutan'] = 'Urutan dokumen sudah digunakan. Gunakan nomor tahap yang berbeda.';
        }
        return $errors;
    }

    private function itemPayload(int $id): array
    {
        $jumlah = trim((string) $this->request->getPost('jumlah'));
        return [
            'kerjasama_id' => $id,
            'nama_item' => trim((string) $this->request->getPost('nama_item')),
            'jumlah' => $jumlah === '' ? null : (float) $jumlah,
            'satuan' => trim((string) $this->request->getPost('satuan')) ?: null,
            'keterangan' => trim((string) $this->request->getPost('keterangan')) ?: null,
        ];
    }

    private function validateItem(array $data): array
    {
        $validation = service('validation');
        $validation->setRules([
            'nama_item' => 'required|max_length[250]',
            'jumlah' => 'permit_empty|greater_than_equal_to[0]',
            'satuan' => 'permit_empty|max_length[80]',
        ]);
        $validation->run($data);
        return $validation->getErrors();
    }

    private function detailError(int $id, array $errors, string $section): RedirectResponse
    {
        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa/' . $id) . '#' . ($section === 'item' ? 'item-pekerjaan' : 'riwayat-dokumen'))
            ->withInput()->with('errors', $errors)->with('pks_error_section', $section);
    }

    private function mainFormError(string $mode, array $data, array $errors, ?int $id = null): RedirectResponse
    {
        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa'))
            ->with('errors', $errors)
            ->with('pks_modal', $mode)
            ->with('pks_form_data', $data)
            ->with('pks_edit_id', $id);
    }

    private function findRecord(int $id): array
    {
        $record = $this->kerjasama->select('pks_kerjasama.*, pks_mitra.nama_mitra, pks_mitra.alamat, pks_mitra.nama_kontak, pks_mitra.jabatan_kontak, pks_mitra.telepon, pks_mitra.email')
            ->join('pks_mitra', 'pks_mitra.id = pks_kerjasama.mitra_id')->find($id);
        if ($record === null) {
            throw PageNotFoundException::forPageNotFound('Data PKS tidak ditemukan.');
        }
        return $record;
    }

    private function findOwnedDocument(int $id, int $documentId): array
    {
        $document = $this->dokumen->where('kerjasama_id', $id)->where('id', $documentId)->first();
        if ($document === null) {
            throw PageNotFoundException::forPageNotFound('Riwayat dokumen tidak ditemukan.');
        }
        return $document;
    }

    private function findOwnedItem(int $id, int $itemId): array
    {
        $item = $this->item->where('kerjasama_id', $id)->where('id', $itemId)->first();
        if ($item === null) {
            throw PageNotFoundException::forPageNotFound('Item pekerjaan tidak ditemukan.');
        }
        return $item;
    }
}
