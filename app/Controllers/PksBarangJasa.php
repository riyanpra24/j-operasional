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
    private const EXPIRY_WARNING_DAYS = 20;

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
        $status = trim((string) $this->request->getGet('status'));
        $perPage = (int) $this->request->getGet('per_page');

        if (! in_array($status, ['aktif', 'segera', 'berakhir', 'belum'], true)) {
            $status = '';
        }

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
        $this->applyStatusFilter($query, $status);

        return view('pks/index', [
            'title' => 'PKS Barang dan Jasa',
            'records' => $this->decorateRows($query->orderBy('pks_kerjasama.updated_at', 'DESC')->paginate($perPage, 'pks')),
            'pager' => $this->kerjasama->pager,
            'filters' => compact('keyword', 'status', 'perPage'),
            'summary' => $summary,
            'calculationDate' => $this->today()->format('d-m-Y'),
            'expiryWarningDays' => self::EXPIRY_WARNING_DAYS,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa'))
            ->with('pks_modal', 'create')
            ->with('pks_form_data', []);
    }

    public function store(): RedirectResponse
    {
        $data = $this->mainPayload();
        $documentData = $this->initialDocumentPayload(0);
        $formData = array_merge($data, $this->documentFormData($documentData));
        $errors = array_merge($this->validateMain($data), $this->validateDocument($documentData));
        if ($errors !== []) {
            return $this->mainFormError('create', $formData, $errors);
        }

        $db = db_connect();
        $db->transStart();
        $mitraId = $this->mitra->insert($this->mitraData($data), true);
        $kerjasamaId = $this->kerjasama->insert($this->kerjasamaData($data, (int) $mitraId), true);
        $documentData['kerjasama_id'] = (int) $kerjasamaId;
        $documentId = $this->dokumen->insert($documentData, true);
        $db->transComplete();

        if (! $db->transStatus() || ! $kerjasamaId || ! $documentId) {
            return $this->mainFormError('create', $formData, ['pks' => 'Data PKS dan dokumen induk gagal disimpan. Silakan coba kembali.']);
        }

        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa/' . $kerjasamaId . '/ubah'))
            ->with('success', 'PKS beserta dokumen induknya berhasil dibuat. Anda dapat menambahkan addendum dan item pekerjaan pada halaman ini.');
    }

    public function show(int $id): string
    {
        $data = $this->detailPageData($id, false);
        $data['isPopup'] = $this->request->getGet('popup') === '1';

        return view('pks/show', $data);
    }

    public function edit(int $id): string|RedirectResponse
    {
        if ($this->request->getGet('data_utama') !== '1') {
            return view('pks/show', $this->detailPageData($id, true));
        }

        $record = $this->findRecord($id);
        $initialDocument = $this->dokumen->where('kerjasama_id', $id)->where('jenis_dokumen', 'PKS')->first();
        if ($initialDocument !== null) {
            $record = array_merge($record, $this->documentFormData($initialDocument));
        }

        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa'))
            ->with('pks_modal', 'edit')
            ->with('pks_form_data', $record)
            ->with('pks_edit_id', $id);
    }

    public function update(int $id): RedirectResponse
    {
        $record = $this->findRecord($id);
        $initialDocument = $this->dokumen->where('kerjasama_id', $id)->where('jenis_dokumen', 'PKS')->first();
        $data = $this->mainPayload();
        $documentData = $this->initialDocumentPayload($id);
        $formData = array_merge($data, $this->documentFormData($documentData));
        $errors = array_merge(
            $this->validateMain($data, $id),
            $this->validateDocument($documentData, isset($initialDocument['id']) ? (int) $initialDocument['id'] : null)
        );
        if ($errors !== []) {
            return $this->mainFormError('edit', $formData, $errors, $id);
        }

        $db = db_connect();
        $db->transStart();
        $this->mitra->update((int) $record['mitra_id'], $this->mitraData($data));
        $this->kerjasama->update($id, $this->kerjasamaData($data, (int) $record['mitra_id']));
        if ($initialDocument !== null) {
            $this->dokumen->update((int) $initialDocument['id'], $documentData);
        } else {
            $this->dokumen->insert($documentData);
        }
        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->mainFormError('edit', $formData, ['pks' => 'Perubahan data PKS dan dokumen induk gagal disimpan.'], $id);
        }

        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa/' . $id . '/ubah'))->with('success', 'Data utama dan dokumen induk PKS berhasil diperbarui.');
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
        $data['jenis_dokumen'] = 'Addendum';
        $errors = $this->validateDocument($data);
        if ($errors !== []) {
            return $this->detailError($id, $errors, 'dokumen');
        }

        if (! $this->dokumen->insert($data)) {
            return $this->detailError($id, $this->dokumen->errors() ?: ['dokumen' => 'Dokumen gagal disimpan.'], 'dokumen');
        }

        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa/' . $id . '/ubah') . '#riwayat-dokumen')->with('success', 'Riwayat dokumen berhasil ditambahkan.');
    }

    public function updateDocument(int $id, int $documentId): RedirectResponse
    {
        $document = $this->findOwnedDocument($id, $documentId);
        $data = $this->documentPayload($id);
        $data['jenis_dokumen'] = $document['jenis_dokumen'];
        if ($document['jenis_dokumen'] === 'PKS') {
            $data['urutan'] = 0;
        }
        $errors = $this->validateDocument($data, $documentId);
        if ($errors !== []) {
            return $this->detailError($id, $errors, 'dokumen');
        }

        if (! $this->dokumen->update($documentId, $data)) {
            return $this->detailError($id, $this->dokumen->errors() ?: ['dokumen' => 'Dokumen gagal diperbarui.'], 'dokumen');
        }

        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa/' . $id . '/ubah') . '#riwayat-dokumen')->with('success', 'Riwayat dokumen berhasil diperbarui.');
    }

    public function destroyDocument(int $id, int $documentId): RedirectResponse
    {
        $document = $this->findOwnedDocument($id, $documentId);
        if ($document['jenis_dokumen'] === 'PKS') {
            return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa/' . $id . '/ubah') . '#riwayat-dokumen')
                ->with('error', 'Dokumen PKS induk tidak dapat dihapus. Hapus data PKS secara keseluruhan jika memang diperlukan.');
        }
        $this->dokumen->delete($documentId);

        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa/' . $id . '/ubah') . '#riwayat-dokumen')->with('success', $document['jenis_dokumen'] . ' nomor ' . $document['nomor_dokumen'] . ' berhasil dihapus.');
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
        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa/' . $id . '/ubah') . '#item-pekerjaan')->with('success', 'Item pekerjaan berhasil ditambahkan.');
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
        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa/' . $id . '/ubah') . '#item-pekerjaan')->with('success', 'Item pekerjaan berhasil diperbarui.');
    }

    public function destroyItem(int $id, int $itemId): RedirectResponse
    {
        $this->findOwnedItem($id, $itemId);
        $this->item->delete($itemId);
        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa/' . $id . '/ubah') . '#item-pekerjaan')->with('success', 'Item pekerjaan berhasil dihapus.');
    }

    private function baseListQuery(): PksKerjasamaModel
    {
        return $this->kerjasama
            ->select("pks_kerjasama.*, pks_mitra.nama_mitra, pks_mitra.alamat, pks_mitra.nama_kontak, pks_mitra.jabatan_kontak, pks_mitra.telepon, pks_mitra.email,
                (SELECT d.id FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = pks_kerjasama.id AND d.jenis_dokumen = 'PKS' LIMIT 1) AS dokumen_induk_id,
                (SELECT d.nomor_dokumen FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = pks_kerjasama.id AND d.jenis_dokumen = 'PKS' LIMIT 1) AS nomor_dokumen,
                (SELECT d.tanggal_dokumen FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = pks_kerjasama.id AND d.jenis_dokumen = 'PKS' LIMIT 1) AS tanggal_dokumen,
                (SELECT d.jangka_waktu_bulan FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = pks_kerjasama.id AND d.jenis_dokumen = 'PKS' LIMIT 1) AS jangka_waktu_bulan,
                (SELECT d.periode_mulai FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = pks_kerjasama.id AND d.jenis_dokumen = 'PKS' LIMIT 1) AS periode_mulai,
                (SELECT d.periode_selesai FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = pks_kerjasama.id AND d.jenis_dokumen = 'PKS' LIMIT 1) AS periode_selesai,
                (SELECT d.nilai FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = pks_kerjasama.id AND d.jenis_dokumen = 'PKS' LIMIT 1) AS nilai,
                (SELECT d.link_berkas FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = pks_kerjasama.id AND d.jenis_dokumen = 'PKS' LIMIT 1) AS link_berkas,
                (SELECT d.periode_mulai FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = pks_kerjasama.id ORDER BY d.urutan DESC LIMIT 1) AS periode_mulai_terakhir,
                (SELECT d.periode_selesai FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = pks_kerjasama.id ORDER BY d.urutan DESC LIMIT 1) AS periode_selesai_terakhir,
                (SELECT d.nomor_dokumen FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = pks_kerjasama.id ORDER BY d.urutan DESC LIMIT 1) AS nomor_dokumen_terakhir,
                (SELECT d.nilai FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = pks_kerjasama.id ORDER BY d.urutan DESC LIMIT 1) AS nilai_terakhir,
                (SELECT COUNT(*) FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = pks_kerjasama.id) AS jumlah_dokumen", false)
            ->join('pks_mitra', 'pks_mitra.id = pks_kerjasama.mitra_id');
    }

    private function detailPageData(int $id, bool $isEditMode): array
    {
        $record = $this->findRecord($id);
        $documents = $this->dokumen->where('kerjasama_id', $id)->orderBy('urutan', 'ASC')->findAll();
        $items = $this->item->where('kerjasama_id', $id)->orderBy('id', 'ASC')->findAll();
        $latest = $documents === [] ? null : $documents[array_key_last($documents)];
        $status = $this->statusFromDates($latest['periode_mulai'] ?? null, $latest['periode_selesai'] ?? null);
        $addendumSequences = array_map(
            static fn (array $document): int => (int) $document['urutan'],
            array_values(array_filter($documents, static fn (array $document): bool => $document['jenis_dokumen'] === 'Addendum'))
        );

        return [
            'title' => $isEditMode ? 'Kelola PKS Barang dan Jasa' : 'Detail PKS Barang dan Jasa',
            'record' => $record,
            'documents' => $documents,
            'items' => $items,
            'latest' => $latest,
            'status' => $status,
            'nextSequence' => $addendumSequences === [] ? 1 : (max($addendumSequences) + 1),
            'isEditMode' => $isEditMode,
        ];
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

    private function applyStatusFilter(PksKerjasamaModel $query, string $status): void
    {
        if ($status === '') {
            return;
        }

        $latestStart = '(SELECT d.periode_mulai FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = pks_kerjasama.id ORDER BY d.urutan DESC LIMIT 1)';
        $latestEnd = '(SELECT d.periode_selesai FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = pks_kerjasama.id ORDER BY d.urutan DESC LIMIT 1)';
        $warningDays = self::EXPIRY_WARNING_DAYS;
        $today = db_connect()->escape($this->today()->format('Y-m-d'));

        $conditions = [
            'aktif' => "$latestStart <= $today AND $latestEnd >= $today AND DATEDIFF($latestEnd, $today) > $warningDays",
            'segera' => "$latestStart <= $today AND $latestEnd >= $today AND DATEDIFF($latestEnd, $today) <= $warningDays",
            'berakhir' => "$latestEnd < $today",
            'belum' => "($latestStart IS NULL OR $latestEnd IS NULL OR $latestStart > $today)",
        ];

        $query->where($conditions[$status], null, false);
    }

    private function statusFromDates(?string $start, ?string $end): array
    {
        if (! $start || ! $end) {
            return [
                'status_key' => 'belum',
                'status_label' => 'Belum ada dokumen',
                'status_class' => 'neutral',
                'remaining_days' => null,
                'remaining_label' => 'Masa berlaku belum diisi',
                'remaining_class' => 'neutral',
            ];
        }

        $timezone = new \DateTimeZone(config('App')->appTimezone);
        $today = $this->today();
        $startDate = (new \DateTimeImmutable($start, $timezone))->setTime(0, 0);
        $endDate = (new \DateTimeImmutable($end, $timezone))->setTime(0, 0);
        $remainingDays = (int) $today->diff($endDate)->format('%r%a');
        $remaining = $this->remainingDayMeta($remainingDays);

        if ($today < $startDate) {
            return array_merge(['status_key' => 'belum', 'status_label' => 'Belum dimulai', 'status_class' => 'neutral'], $remaining);
        }
        if ($today > $endDate) {
            return array_merge(['status_key' => 'berakhir', 'status_label' => 'Berakhir', 'status_class' => 'expired'], $remaining);
        }
        if ($remainingDays <= self::EXPIRY_WARNING_DAYS) {
            return array_merge(['status_key' => 'segera', 'status_label' => 'Segera berakhir', 'status_class' => 'warning'], $remaining);
        }
        return array_merge(['status_key' => 'aktif', 'status_label' => 'Aktif', 'status_class' => 'active'], $remaining);
    }

    private function remainingDayMeta(int $remainingDays): array
    {
        if ($remainingDays < 0) {
            return [
                'remaining_days' => $remainingDays,
                'remaining_label' => 'Lewat ' . abs($remainingDays) . ' hari',
                'remaining_class' => 'expired',
            ];
        }

        if ($remainingDays === 0) {
            return [
                'remaining_days' => 0,
                'remaining_label' => 'Berakhir hari ini',
                'remaining_class' => 'today',
            ];
        }

        return [
            'remaining_days' => $remainingDays,
            'remaining_label' => 'Sisa ' . $remainingDays . ' hari',
            'remaining_class' => $remainingDays <= self::EXPIRY_WARNING_DAYS ? 'warning' : 'active',
        ];
    }

    private function today(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('today', new \DateTimeZone(config('App')->appTimezone));
    }

    private function mainPayload(): array
    {
        $fields = ['kode_internal', 'nama_kerjasama', 'unit_pengelola', 'pic_internal', 'nama_mitra', 'alamat', 'nama_kontak', 'jabatan_kontak', 'telepon', 'email'];
        $data = [];
        foreach ($fields as $field) {
            $data[$field] = trim((string) $this->request->getPost($field));
        }
        $data['pic_internal'] = match ($data['unit_pengelola']) {
            'Bagian Umum 1' => 'Angger Wicaksono',
            'Bagian Umum 2' => 'Agil Halis Kesawa',
            default => '',
        };
        return $data;
    }

    private function validateMain(array $data, ?int $id = null): array
    {
        $validation = service('validation');
        $validation->setRules([
            'kode_internal' => 'required|max_length[80]',
            'nama_kerjasama' => 'required|max_length[250]',
            'unit_pengelola' => 'permit_empty|in_list[Bagian Umum 1,Bagian Umum 2]',
            'pic_internal' => 'permit_empty|in_list[Angger Wicaksono,Agil Halis Kesawa]',
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
            $errors['kode_internal'] = 'Nomor PKS sudah digunakan pada PKS lain.';
        }
        return $errors;
    }

    private function mitraData(array $data): array
    {
        return array_intersect_key($data, array_flip(['nama_mitra', 'alamat', 'nama_kontak', 'jabatan_kontak', 'telepon', 'email']));
    }

    private function kerjasamaData(array $data, int $mitraId): array
    {
        return ['mitra_id' => $mitraId] + array_intersect_key($data, array_flip(['kode_internal', 'nama_kerjasama', 'unit_pengelola', 'pic_internal']));
    }

    private function documentPayload(int $id): array
    {
        $documentDate = trim((string) $this->request->getPost('tanggal_dokumen'));
        $durationMonths = (int) $this->request->getPost('jangka_waktu_bulan');
        $cooperationValue = trim((string) $this->request->getPost('nilai'));

        return [
            'kerjasama_id' => $id,
            'jenis_dokumen' => trim((string) $this->request->getPost('jenis_dokumen')),
            'urutan' => (int) $this->request->getPost('urutan'),
            'nomor_dokumen' => trim((string) $this->request->getPost('nomor_dokumen')),
            'tanggal_dokumen' => $documentDate,
            'periode_mulai' => $documentDate,
            'jangka_waktu_bulan' => $durationMonths,
            'periode_selesai' => $this->periodEndFromMonths($documentDate, $durationMonths),
            'nilai' => $cooperationValue,
            'link_berkas' => trim((string) $this->request->getPost('link_berkas')) ?: null,
        ];
    }

    private function initialDocumentPayload(int $id): array
    {
        $data = $this->documentPayload($id);
        $data['jenis_dokumen'] = 'PKS';
        $data['urutan'] = 0;

        return $data;
    }

    private function documentFormData(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'nomor_dokumen',
            'tanggal_dokumen',
            'jangka_waktu_bulan',
            'periode_mulai',
            'periode_selesai',
            'nilai',
            'link_berkas',
        ]));
    }

    private function validateDocument(array $data, ?int $documentId = null): array
    {
        $validation = service('validation');
        $validation->setRules([
            'jenis_dokumen' => 'required|in_list[PKS,Addendum]',
            'urutan' => 'required|integer|greater_than_equal_to[0]',
            'nomor_dokumen' => 'required|max_length[200]',
            'tanggal_dokumen' => 'required|valid_date[Y-m-d]',
            'periode_mulai' => 'required|valid_date[Y-m-d]',
            'jangka_waktu_bulan' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[1200]',
            'periode_selesai' => 'required|valid_date[Y-m-d]',
            'nilai' => 'required|greater_than_equal_to[0]',
            'link_berkas' => 'permit_empty|max_length[2048]',
        ]);
        $validation->run($data);
        $errors = $validation->getErrors();
        if ($data['jenis_dokumen'] === 'PKS' && (int) $data['urutan'] !== 0) {
            $errors['urutan'] = 'Dokumen PKS harus menggunakan tahap Induk.';
        }
        if ($data['jenis_dokumen'] === 'Addendum' && (int) $data['urutan'] < 1) {
            $errors['urutan'] = 'Tahap Addendum harus dimulai dari 1.';
        }
        if ($data['jenis_dokumen'] === 'Addendum'
            && $this->dokumen->where('kerjasama_id', $data['kerjasama_id'])->where('jenis_dokumen', 'PKS')->first() === null) {
            $errors['jenis_dokumen'] = 'Dokumen PKS induk harus tersedia sebelum menambahkan Addendum.';
        }
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
        if ($data['jenis_dokumen'] === 'PKS') {
            $existingParent = $this->dokumen->where('kerjasama_id', $data['kerjasama_id'])->where('jenis_dokumen', 'PKS');
            if ($documentId !== null) {
                $existingParent->where('id !=', $documentId);
            }
            if ($existingParent->first() !== null) {
                $errors['jenis_dokumen'] = 'Setiap kerja sama hanya boleh memiliki satu dokumen PKS induk.';
            }
        }
        return $errors;
    }

    private function periodEndFromMonths(string $start, int $months): string
    {
        if ($months < 1 || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
            return '';
        }

        $parts = array_map('intval', explode('-', $start));
        if (count($parts) !== 3 || ! checkdate($parts[1], $parts[2], $parts[0])) {
            return '';
        }

        [$year, $month, $day] = $parts;
        $targetMonthIndex = ($year * 12) + ($month - 1) + $months;
        $targetYear = intdiv($targetMonthIndex, 12);
        $targetMonth = ($targetMonthIndex % 12) + 1;
        $lastDay = (int) (new \DateTimeImmutable(sprintf('%04d-%02d-01', $targetYear, $targetMonth)))->format('t');

        return sprintf('%04d-%02d-%02d', $targetYear, $targetMonth, min($day, $lastDay));
    }

    private function itemPayload(int $id): array
    {
        return [
            'kerjasama_id' => $id,
            'keterangan' => trim((string) $this->request->getPost('keterangan')),
        ];
    }

    private function validateItem(array $data): array
    {
        $validation = service('validation');
        $validation->setRules([
            'keterangan' => 'required|max_length[2000]',
        ]);
        $validation->run($data);
        return $validation->getErrors();
    }

    private function detailError(int $id, array $errors, string $section): RedirectResponse
    {
        return redirect()->to(site_url('bagian-umum-1/pks-barang-jasa/' . $id . '/ubah') . '#' . ($section === 'item' ? 'item-pekerjaan' : 'riwayat-dokumen'))
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
