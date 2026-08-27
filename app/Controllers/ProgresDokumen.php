<?php

namespace App\Controllers;

use App\Models\AgendarisModel;
use App\Models\DokumenKeluarModel;
use App\Models\DokumenMasukModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

class ProgresDokumen extends BaseController
{
    private DokumenKeluarModel $model;
    private DokumenMasukModel $dokumenMasukModel;
    private AgendarisModel $agendarisModel;

    public function __construct()
    {
        $this->model = new DokumenKeluarModel();
        $this->dokumenMasukModel = new DokumenMasukModel();
        $this->agendarisModel = new AgendarisModel();
    }

    public function masuk(): string
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $jenis   = trim((string) $this->request->getGet('jenis'));
        $from    = trim((string) $this->request->getGet('dari'));
        $to      = trim((string) $this->request->getGet('sampai'));
        $perPage = (int) $this->request->getGet('per_page');

        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 10;
        }

        $this->agendarisModel->select('agendaris.*')
            ->where('agendaris.progres', 'Menunggu Penyelesaian');

        if ($keyword !== '') {
            $this->agendarisModel->groupStart()
                ->like('agendaris.nomor_surat', $keyword)
                ->orLike('agendaris.nomor_agendaris', $keyword)
                ->orLike('agendaris.perihal_surat', $keyword)
                ->orLike('agendaris.pengirim', $keyword)
                ->orLike('agendaris.penerima', $keyword)
                ->orLike('agendaris.pengambilan', $keyword)
                ->orLike('agendaris.jenis', $keyword)
                ->groupEnd();
        }

        if ($jenis !== '') {
            $this->agendarisModel->where('agendaris.jenis', $jenis);
        }

        if ($this->isDate($from)) {
            $this->agendarisModel->where('agendaris.tanggal_diterima >=', $from);
        }

        if ($this->isDate($to)) {
            $this->agendarisModel->where('agendaris.tanggal_diterima <=', $to);
        }

        $jenisOptions = db_connect()->table('agendaris')
            ->select('jenis')
            ->where('progres', 'Menunggu Penyelesaian')
            ->where('jenis IS NOT NULL', null, false)
            ->where('jenis !=', '')
            ->groupBy('jenis')
            ->orderBy('jenis', 'ASC')
            ->get()
            ->getResultArray();

        return view('agendaris/progres_dokumen_masuk', [
            'title'  => 'Progres Dokumen',
            'agenda' => $this->agendarisModel
                ->orderBy('agendaris.created_at', 'ASC')
                ->orderBy('agendaris.id', 'ASC')
                ->paginate($perPage, 'progres_dokumen_masuk'),
            'pager'        => $this->agendarisModel->pager,
            'filters'      => compact('keyword', 'jenis', 'from', 'to', 'perPage'),
            'jenisOptions' => array_column($jenisOptions, 'jenis'),
        ]);
    }

    public function showMasuk(int $id): ResponseInterface
    {
        $dokumen = $this->dokumenMasukModel->find($id);
        if ($dokumen === null) {
            throw PageNotFoundException::forPageNotFound('Progres Dokumen Masuk tidak ditemukan.');
        }

        $sudahDiserahkan = trim((string) ($dokumen['pengambilan'] ?? '')) !== '';

        return $this->response->setJSON([
            'success' => true,
            'dokumen' => [
                'pengirim'         => $dokumen['pengirim'],
                'perihal'          => $dokumen['perihal'] ?: '-',
                'penerima'         => $dokumen['penerima'] ?: '-',
                'hari'             => $dokumen['hari'],
                'tanggal'          => $this->displayDate($dokumen['tanggal']),
                'jenis'            => $dokumen['jenis'],
                'jumlah'           => number_format((int) $dokumen['jumlah'], 0, ',', '.'),
                'ekspedisi'        => $dokumen['ekspedisi'] ?: '-',
                'penyerahan'       => $sudahDiserahkan ? $dokumen['pengambilan'] : 'Menunggu Penyerahan',
                'waktu_penyerahan' => $dokumen['penyerahan_at']
                    ? date('d-m-Y H:i', strtotime($dokumen['penyerahan_at'])) . ' WIB'
                    : '-',
                'progres'          => $sudahDiserahkan ? 'Sudah Diserahkan' : 'Menunggu Penyerahan',
            ],
        ]);
    }

    public function index(): string
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $progres = trim((string) $this->request->getGet('progres'));
        $perPage = (int) $this->request->getGet('per_page');

        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 10;
        }

        // Progres Security dan penyelesaian Agendaris merupakan dua tahap terpisah.
        // Dokumen tetap berada di halaman ini setelah diambil ekspedisi sampai
        // Agendaris sendiri menandainya selesai.
        $this->model->where('status_agendaris !=', 'Selesai');

        if ($keyword !== '') {
            $this->model->groupStart()
                ->like('nomor_surat', $keyword)
                ->orLike('jenis_surat', $keyword)
                ->orLike('pemohon', $keyword)
                ->orLike('pelaksana', $keyword)
                ->orLike('up', $keyword)
                ->orLike('nomor_resi', $keyword)
                ->orLike('penerima', $keyword)
                ->orLike('security', $keyword)
                ->orLike('alamat_penerima', $keyword)
                ->groupEnd();
        }

        if (in_array($progres, ['Menunggu Ekspedisi', 'Diambil Ekspedisi'], true)) {
            $this->model->where('progres', $progres);
        }

        return view('agendaris/progres_dokumen', [
            'title'   => 'Progres Dokumen',
            'dokumen' => $this->model->orderBy('id', 'ASC')->paginate($perPage, 'progres_dokumen'),
            'pager'   => $this->model->pager,
            'filters' => compact('keyword', 'progres', 'perPage'),
        ]);
    }

    public function store(): ResponseInterface
    {
        $data = $this->payload();
        // Field tahap Security tidak boleh ditentukan dari formulir Agendaris.
        // Nilai awal progres dibuat sistem dan selanjutnya hanya diubah melalui
        // endpoint Distribusi Dokumen yang dibatasi untuk role Security.
        $data['security']         = null;
        $data['tanggal_security'] = null;
        $data['progres']          = 'Menunggu Ekspedisi';
        $data['status_agendaris'] = 'Menunggu Penyelesaian';
        $data['selesai_agendaris_at'] = null;
        $errors = $this->validatePayload($data, false);
        if ($errors !== []) {
            return $this->validationError($errors);
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $id = $this->model->insert($data, true);
            if ($id === false) {
                throw new \RuntimeException(implode(' ', $this->model->errors() ?: ['Dokumen gagal disimpan.']));
            }
            if (! $db->table('distribusi_dokumen')->insert(['dokumen_keluar_id' => $id])) {
                throw new \RuntimeException('Antrean Distribusi Dokumen gagal dibuat.');
            }
            $db->transCommit();
        } catch (\Throwable $error) {
            $db->transRollback();

            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Progres Dokumen Keluar belum dapat disimpan.',
                'errors'  => [$error->getMessage()],
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        $message = 'Progres Dokumen Keluar berhasil ditambahkan.';
        session()->setFlashdata('success', $message);

        return $this->successResponse($message, ['id' => $id], 201);
    }

    public function show(int $id): ResponseInterface
    {
        $dokumen = $this->findOrFail($id);

        return $this->response->setJSON([
            'success' => true,
            'dokumen' => [
                'id'                         => (int) $dokumen['id'],
                'nomor_surat'                => $dokumen['nomor_surat'],
                'jenis_surat'                => $dokumen['jenis_surat'],
                'pemohon'                    => $dokumen['pemohon'] ?: '-',
                'pemohon_value'              => $dokumen['pemohon'] ?: '',
                'pelaksana'                  => $dokumen['pelaksana'] ?: '-',
                'pelaksana_value'            => $dokumen['pelaksana'] ?: '',
                'up'                         => $dokumen['up'] ?: '-',
                'up_value'                   => $dokumen['up'] ?: '',
                'tanggal_pengiriman'         => $this->displayDate($dokumen['tanggal_pengiriman']),
                'tanggal_pengiriman_value'   => $dokumen['tanggal_pengiriman'],
                'nomor_resi'                 => $dokumen['nomor_resi'] ?: '-',
                'nomor_resi_value'           => $dokumen['nomor_resi'] ?: '',
                'tanggal_diterima'           => $this->displayDate($dokumen['tanggal_diterima']),
                'tanggal_diterima_value'     => $dokumen['tanggal_diterima'] ?: '',
                'penerima'                   => $dokumen['penerima'] ?: '-',
                'penerima_value'             => $dokumen['penerima'] ?: '',
                'alamat_penerima'            => $dokumen['alamat_penerima'],
                'security'                   => $dokumen['security'] ?: '-',
                'security_value'             => $dokumen['security'] ?: '',
                'tanggal_security'           => $this->displayDateTime($dokumen['diterima_security_at'] ?? null, $dokumen['tanggal_security'] ?? null),
                'tanggal_security_value'     => $dokumen['tanggal_security'] ?: '',
                'progres'                    => $dokumen['progres'] ?: 'Menunggu Ekspedisi',
                'status_agendaris'           => $dokumen['status_agendaris'] ?: 'Menunggu Penyelesaian',
                'status_agendaris_value'     => $dokumen['status_agendaris'] ?: 'Menunggu Penyelesaian',
                'waktu_selesai_agendaris'    => $this->displayDateTime($dokumen['selesai_agendaris_at'] ?? null),
                'waktu_pengambilan_ekspedisi'=> $this->displayDateTime($dokumen['diambil_ekspedisi_at'] ?? null),
                'locked'                     => $dokumen['progres'] === 'Diambil Ekspedisi',
                'update_url'                 => site_url("agendaris/progres-dokumen-keluar/{$id}"),
                'delete_url'                 => site_url("agendaris/progres-dokumen-keluar/{$id}/hapus"),
            ],
        ]);
    }

    public function update(int $id): ResponseInterface
    {
        $dokumen = $this->findOrFail($id);

        $data   = $this->payload();
        foreach (['security', 'tanggal_security', 'progres'] as $securityField) {
            $data[$securityField] = $dokumen[$securityField];
        }
        $data['selesai_agendaris_at'] = $data['status_agendaris'] === 'Selesai'
            ? ($dokumen['selesai_agendaris_at'] ?: date('Y-m-d H:i:s'))
            : null;
        $errors = $this->validatePayload($data, false);
        if ($errors !== []) {
            return $this->validationError($errors);
        }
        if ($data['status_agendaris'] === 'Selesai' && $data['progres'] !== 'Diambil Ekspedisi') {
            return $this->validationError(['Dokumen baru dapat diselesaikan oleh Agendaris setelah progres Security menjadi Diambil Ekspedisi.']);
        }

        if (! $this->model->update($id, $data)) {
            return $this->validationError($this->model->errors() ?: ['Progres Dokumen Keluar gagal diperbarui.']);
        }

        $message = 'Progres Dokumen Keluar berhasil diperbarui.';
        session()->setFlashdata('success', $message);

        return $this->successResponse($message);
    }

    public function destroy(int $id): ResponseInterface
    {
        $dokumen = $this->findOrFail($id);
        if ($dokumen['progres'] === 'Diambil Ekspedisi' && (string) session()->get('auth_role') !== 'admin') {
            return $this->lockedResponse();
        }

        if (! $this->model->delete($id, true)) {
            return $this->validationError($this->model->errors() ?: ['Progres Dokumen Keluar gagal dihapus.']);
        }

        $message = "Dokumen nomor {$dokumen['nomor_surat']} berhasil dihapus permanen dari database.";
        session()->setFlashdata('success', $message);

        return $this->successResponse($message);
    }

    private function payload(): array
    {
        return [
            'nomor_surat'        => trim((string) $this->request->getPost('nomor_surat')),
            'jenis_surat'        => trim((string) $this->request->getPost('jenis_surat')),
            'pemohon'            => $this->nullIfEmpty($this->request->getPost('pemohon')),
            'pelaksana'          => $this->nullIfEmpty($this->request->getPost('pelaksana')),
            'up'                 => $this->nullIfEmpty($this->request->getPost('up')),
            'tanggal_pengiriman' => trim((string) $this->request->getPost('tanggal_pengiriman')),
            'nomor_resi'         => $this->nullIfEmpty($this->request->getPost('nomor_resi')),
            'tanggal_diterima'   => $this->nullIfEmpty($this->request->getPost('tanggal_diterima')),
            'penerima'           => $this->nullIfEmpty($this->request->getPost('penerima')),
            'alamat_penerima'    => trim((string) $this->request->getPost('alamat_penerima')),
            'security'           => trim((string) $this->request->getPost('security')),
            'tanggal_security'   => trim((string) $this->request->getPost('tanggal_security')),
            'progres'            => trim((string) $this->request->getPost('progres')),
            'status_agendaris'   => trim((string) $this->request->getPost('status_agendaris')),
        ];
    }

    private function validatePayload(array $data, bool $securityRequired = true): array
    {
        $securityRule = $securityRequired
            ? 'required|in_list[Yanto Pujoyuwono,M. Aziz Dwi Pratomo,Ach. Fathur Rozi,Yayak Andriyani]'
            : 'permit_empty|in_list[Yanto Pujoyuwono,M. Aziz Dwi Pratomo,Ach. Fathur Rozi,Yayak Andriyani]';
        $securityDateRule = $securityRequired ? 'required|valid_date[Y-m-d]' : 'permit_empty|valid_date[Y-m-d]';

        $validation = service('validation')->setRules([
            'nomor_surat'        => 'required|max_length[150]',
            'jenis_surat'        => 'required|max_length[100]',
            'pemohon'            => 'permit_empty|max_length[255]',
            'pelaksana'          => 'permit_empty|max_length[255]',
            'up'                 => 'permit_empty|max_length[255]',
            'tanggal_pengiriman' => 'required|valid_date[Y-m-d]',
            'nomor_resi'         => 'permit_empty|max_length[100]',
            'tanggal_diterima'   => 'permit_empty|valid_date[Y-m-d]',
            'penerima'           => 'permit_empty|max_length[255]',
            'alamat_penerima'    => 'required|max_length[2000]',
            'security'           => $securityRule,
            'tanggal_security'   => $securityDateRule,
            'progres'            => 'required|in_list[Menunggu Ekspedisi,Diambil Ekspedisi]',
            'status_agendaris'   => 'required|in_list[Menunggu Penyelesaian,Selesai]',
        ], [
            'security' => ['required' => 'Security wajib dipilih.', 'in_list' => 'Pilih Security dari daftar yang tersedia.'],
            'tanggal_security' => ['required' => 'Tanggal Diterima Security wajib diisi.', 'valid_date' => 'Tanggal Diterima Security tidak valid.'],
            'progres' => ['required' => 'Progres wajib dipilih.', 'in_list' => 'Pilih Progres dari daftar yang tersedia.'],
            'status_agendaris' => ['required' => 'Status Penyelesaian Agendaris wajib dipilih.', 'in_list' => 'Pilih Status Penyelesaian Agendaris dari daftar yang tersedia.'],
        ]);

        if (! $validation->run($data)) {
            return array_values($validation->getErrors());
        }

        if ($data['tanggal_security'] !== null && $data['tanggal_security'] !== '' && $data['tanggal_security'] < $data['tanggal_pengiriman']) {
            return ['Tanggal Diterima Security tidak boleh lebih awal dari Tanggal Pengiriman.'];
        }

        if ($data['tanggal_diterima'] !== null && $data['tanggal_diterima'] < $data['tanggal_pengiriman']) {
            return ['Tanggal Diterima tidak boleh lebih awal dari Tanggal Pengiriman.'];
        }

        return [];
    }

    private function findOrFail(int $id): array
    {
        $dokumen = $this->model->find($id);
        if ($dokumen === null) {
            throw PageNotFoundException::forPageNotFound('Progres Dokumen Keluar tidak ditemukan.');
        }

        return $dokumen;
    }

    private function validationError(array $errors): ResponseInterface
    {
        return $this->response->setStatusCode(422)->setJSON([
            'success' => false,
            'message' => 'Periksa kembali data Progres Dokumen Keluar.',
            'errors'  => array_values($errors),
            'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
        ]);
    }

    private function lockedResponse(): ResponseInterface
    {
        $message = 'Dokumen berstatus Diambil Ekspedisi sudah masuk ke Security dan hanya dapat dilihat.';

        return $this->response->setStatusCode(409)->setJSON([
            'success' => false,
            'message' => $message,
            'errors'  => [$message],
            'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
        ]);
    }

    private function successResponse(string $message, array $extra = [], int $status = 200): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON(array_merge([
            'success' => true,
            'message' => $message,
            'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
        ], $extra));
    }

    private function displayDate(?string $date): string
    {
        return $date ? date('d-m-Y', strtotime($date)) : '-';
    }

    private function displayDateTime(?string $timestamp, ?string $dateFallback = null): string
    {
        if ($timestamp) {
            return date('d-m-Y H:i', strtotime($timestamp)) . ' WIB';
        }

        return $dateFallback ? $this->displayDate($dateFallback) : '-';
    }

    private function isDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function nullIfEmpty(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
