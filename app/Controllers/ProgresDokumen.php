<?php

namespace App\Controllers;

use App\Models\DokumenKeluarModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

class ProgresDokumen extends BaseController
{
    private DokumenKeluarModel $model;

    public function __construct()
    {
        $this->model = new DokumenKeluarModel();
    }

    public function index(): string
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $progres = trim((string) $this->request->getGet('progres'));
        $perPage = (int) $this->request->getGet('per_page');

        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 10;
        }

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
        $data   = $this->payload();
        $errors = $this->validatePayload($data);
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
                'message' => 'Progres Dokumen belum dapat disimpan.',
                'errors'  => [$error->getMessage()],
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        $message = 'Progres Dokumen berhasil ditambahkan.';
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
                'tanggal_security'           => $this->displayDate($dokumen['tanggal_security']),
                'tanggal_security_value'     => $dokumen['tanggal_security'] ?: '',
                'progres'                    => $dokumen['progres'] ?: 'Menunggu Ekspedisi',
                'locked'                     => $dokumen['progres'] === 'Diambil Ekspedisi',
                'update_url'                 => site_url("agendaris/progres-dokumen/{$id}"),
                'delete_url'                 => site_url("agendaris/progres-dokumen/{$id}/hapus"),
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
        $errors = $this->validatePayload($data, false);
        if ($errors !== []) {
            return $this->validationError($errors);
        }

        if (! $this->model->update($id, $data)) {
            return $this->validationError($this->model->errors() ?: ['Progres Dokumen gagal diperbarui.']);
        }

        $message = 'Progres Dokumen berhasil diperbarui.';
        session()->setFlashdata('success', $message);

        return $this->successResponse($message);
    }

    public function destroy(int $id): ResponseInterface
    {
        $dokumen = $this->findOrFail($id);
        if ($dokumen['progres'] === 'Diambil Ekspedisi') {
            return $this->lockedResponse();
        }

        if (! $this->model->delete($id, true)) {
            return $this->validationError($this->model->errors() ?: ['Progres Dokumen gagal dihapus.']);
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
        ], [
            'security' => ['required' => 'Security wajib dipilih.', 'in_list' => 'Pilih Security dari daftar yang tersedia.'],
            'tanggal_security' => ['required' => 'Tanggal Diterima Security wajib diisi.', 'valid_date' => 'Tanggal Diterima Security tidak valid.'],
            'progres' => ['required' => 'Progres wajib dipilih.', 'in_list' => 'Pilih Progres dari daftar yang tersedia.'],
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
            throw PageNotFoundException::forPageNotFound('Progres Dokumen tidak ditemukan.');
        }

        return $dokumen;
    }

    private function validationError(array $errors): ResponseInterface
    {
        return $this->response->setStatusCode(422)->setJSON([
            'success' => false,
            'message' => 'Periksa kembali data Progres Dokumen.',
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

    private function nullIfEmpty(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
