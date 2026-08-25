<?php

namespace App\Controllers;

use App\Models\DokumenKeluarModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

class DokumenKeluar extends BaseController
{
    private DokumenKeluarModel $model;

    public function __construct()
    {
        $this->model = new DokumenKeluarModel();
    }

    public function index(): string
    {
        $securityView = service('uri')->getSegment(1) === 'dokumen-keluar';
        $indexUrl     = $securityView ? site_url('dokumen-keluar') : site_url('agendaris/surat-keluar');
        $keyword = trim((string) $this->request->getGet('q'));
        $jenis   = trim((string) $this->request->getGet('jenis'));
        $from    = trim((string) $this->request->getGet('dari'));
        $to      = trim((string) $this->request->getGet('sampai'));
        $perPage = (int) $this->request->getGet('per_page');

        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 10;
        }

        if ($securityView) {
            $this->model->where('progres', 'Diambil Ekspedisi');
        }

        if ($keyword !== '') {
            $this->model->groupStart()
                ->like('nomor_surat', $keyword)
                ->orLike('jenis_surat', $keyword)
                ->orLike('pemohon', $keyword)
                ->orLike('pelaksana', $keyword)
                ->orLike('up', $keyword)
                ->orLike('alamat_penerima', $keyword)
                ->groupEnd();
        }

        if ($jenis !== '') {
            $this->model->where('jenis_surat', $jenis);
        }

        if ($this->isDate($from)) {
            $this->model->where('tanggal_pengiriman >=', $from);
        }

        if ($this->isDate($to)) {
            $this->model->where('tanggal_pengiriman <=', $to);
        }

        $jenisBuilder = db_connect()->table('dokumen_keluar')->select('jenis_surat');
        if ($securityView) {
            $jenisBuilder->where('progres', 'Diambil Ekspedisi');
        }
        $jenisOptions = $jenisBuilder->groupBy('jenis_surat')->orderBy('jenis_surat', 'ASC')->get()->getResultArray();

        return view('dokumen_keluar/index', [
            'title'        => $securityView ? 'Dokumen Keluar' : 'Surat Keluar',
            'indexUrl'     => $indexUrl,
            'readOnly'     => $securityView,
            'dokumen'      => $this->model->orderBy('id', 'ASC')->paginate($perPage, 'dokumen_keluar'),
            'pager'        => $this->model->pager,
            'filters'      => compact('keyword', 'jenis', 'from', 'to', 'perPage'),
            'jenisOptions' => array_column($jenisOptions, 'jenis_surat'),
        ]);
    }

    public function store(): ResponseInterface
    {
        $data   = $this->payload();
        $errors = $this->validateDokumen($data);

        if ($errors !== []) {
            return $this->validationError($errors);
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $id = $this->model->insert($data, true);
            if ($id === false) {
                throw new \RuntimeException(implode(' ', $this->model->errors() ?: ['Surat Keluar gagal disimpan.']));
            }

            if (! $db->table('distribusi_dokumen')->insert(['dokumen_keluar_id' => $id])) {
                throw new \RuntimeException('Antrean Distribusi Dokumen gagal dibuat.');
            }

            $db->transCommit();
        } catch (\Throwable $error) {
            $db->transRollback();

            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Surat Keluar dan Distribusi Dokumen belum dapat disimpan.',
                'errors'  => [$error->getMessage()],
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        $message = 'Surat Keluar berhasil ditambahkan.';
        session()->setFlashdata('success', $message);

        return $this->successResponse($message, ['id' => $id], 201);
    }

    public function show(int $id): ResponseInterface
    {
        $dokumen = $this->findDokumen($id);

        return $this->response->setJSON([
            'success' => true,
            'dokumen' => [
                'id'                       => (int) $dokumen['id'],
                'nomor_surat'              => $dokumen['nomor_surat'],
                'jenis_surat'              => $dokumen['jenis_surat'],
                'pemohon'                  => $dokumen['pemohon'] ?: '-',
                'pemohon_value'            => $dokumen['pemohon'] ?: '',
                'pelaksana'                => $dokumen['pelaksana'] ?: '-',
                'pelaksana_value'          => $dokumen['pelaksana'] ?: '',
                'up'                       => $dokumen['up'] ?: '-',
                'up_value'                 => $dokumen['up'] ?: '',
                'tanggal_pengiriman'       => date('d-m-Y', strtotime($dokumen['tanggal_pengiriman'])),
                'tanggal_pengiriman_value' => $dokumen['tanggal_pengiriman'],
                'alamat_penerima'          => $dokumen['alamat_penerima'],
                'dokumen_link'             => $dokumen['dokumen_link'] ?: '',
                'dokumen_link_value'       => $dokumen['dokumen_link'] ?: '',
                'update_url'               => site_url("agendaris/surat-keluar/{$id}"),
                'delete_url'               => site_url("agendaris/surat-keluar/{$id}/hapus"),
            ],
        ]);
    }

    public function update(int $id): ResponseInterface
    {
        $dokumen = $this->findDokumen($id);
        if ($dokumen['progres'] === 'Diambil Ekspedisi') {
            return $this->lockedResponse();
        }

        $data   = $this->payload();
        $errors = $this->validateDokumen($data);

        if ($errors !== []) {
            return $this->validationError($errors);
        }

        if (! $this->model->update($id, $data)) {
            return $this->validationError($this->model->errors() ?: ['Surat Keluar gagal diperbarui.']);
        }

        $message = 'Surat Keluar berhasil diperbarui.';
        session()->setFlashdata('success', $message);

        return $this->successResponse($message);
    }

    public function destroy(int $id): ResponseInterface
    {
        $dokumen = $this->findDokumen($id);

        if ($dokumen['progres'] === 'Diambil Ekspedisi') {
            return $this->lockedResponse();
        }

        if (! $this->model->delete($id, true)) {
            return $this->validationError($this->model->errors() ?: ['Surat Keluar gagal dihapus.']);
        }

        $message = "Surat Keluar nomor {$dokumen['nomor_surat']} berhasil dihapus permanen dari database.";
        session()->setFlashdata('success', $message);

        return $this->successResponse($message);
    }

    private function payload(): array
    {
        return [
            'nomor_surat'         => trim((string) $this->request->getPost('nomor_surat')),
            'jenis_surat'         => trim((string) $this->request->getPost('jenis_surat')),
            'pemohon'             => trim((string) $this->request->getPost('pemohon')),
            'pelaksana'           => trim((string) $this->request->getPost('pelaksana')),
            'up'                  => trim((string) $this->request->getPost('up')),
            'tanggal_pengiriman'  => trim((string) $this->request->getPost('tanggal_pengiriman')),
            'alamat_penerima'     => trim((string) $this->request->getPost('alamat_penerima')),
            'dokumen_link'        => trim((string) $this->request->getPost('dokumen_link')),
        ];
    }

    private function validateDokumen(array &$data): array
    {
        $validation = service('validation')->setRules([
            'nomor_surat'        => 'required|max_length[150]',
            'jenis_surat'        => 'required|max_length[100]',
            'pemohon'            => 'permit_empty|max_length[255]',
            'pelaksana'          => 'permit_empty|max_length[255]',
            'up'                 => 'permit_empty|max_length[255]',
            'tanggal_pengiriman' => 'required|valid_date[Y-m-d]',
            'alamat_penerima'    => 'required|max_length[2000]',
            'dokumen_link'       => 'permit_empty|max_length[2048]',
        ]);

        if (! $validation->run($data)) {
            return array_values($validation->getErrors());
        }

        if ($data['dokumen_link'] !== '') {
            $scheme = strtolower((string) parse_url($data['dokumen_link'], PHP_URL_SCHEME));
            if ($scheme !== 'https' || filter_var($data['dokumen_link'], FILTER_VALIDATE_URL) === false) {
                return ['Link dokumen harus berupa URL HTTPS yang valid.'];
            }
        }

        foreach (['pemohon', 'pelaksana', 'up', 'dokumen_link'] as $optionalField) {
            if ($data[$optionalField] === '') {
                $data[$optionalField] = null;
            }
        }

        return [];
    }

    private function findDokumen(int $id): array
    {
        $dokumen = $this->model->find($id);
        if ($dokumen === null) {
            throw PageNotFoundException::forPageNotFound('Data Surat Keluar tidak ditemukan.');
        }

        return $dokumen;
    }

    private function validationError(array $errors): ResponseInterface
    {
        return $this->response->setStatusCode(422)->setJSON([
            'success' => false,
            'message' => 'Periksa kembali data Surat Keluar.',
            'errors'  => array_values($errors),
            'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
        ]);
    }

    private function lockedResponse(): ResponseInterface
    {
        $message = 'Dokumen yang sudah masuk ke Security hanya dapat dilihat dan tidak dapat diubah atau dihapus.';

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

    private function isDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
