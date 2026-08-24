<?php

namespace App\Controllers;

use App\Models\AgendarisModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class Agendaris extends BaseController
{
    private AgendarisModel $model;

    public function __construct()
    {
        $this->model = new AgendarisModel();
    }

    public function index(): RedirectResponse
    {
        return redirect()->to(site_url('agendaris/surat-masuk'));
    }

    public function suratMasuk(): string
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $jenis   = trim((string) $this->request->getGet('jenis'));
        $from    = trim((string) $this->request->getGet('dari'));
        $to      = trim((string) $this->request->getGet('sampai'));
        $perPage = (int) $this->request->getGet('per_page');

        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 10;
        }

        $this->model->select('agendaris.*');

        if ($keyword !== '') {
            $this->model->groupStart()
                ->like('agendaris.nomor_surat', $keyword)
                ->orLike('agendaris.perihal_surat', $keyword)
                ->orLike('agendaris.pengirim', $keyword)
                ->orLike('agendaris.penerima', $keyword)
                ->orLike('agendaris.pengambilan', $keyword)
                ->orLike('agendaris.jenis', $keyword)
                ->groupEnd();
        }

        if ($jenis !== '') {
            $this->model->where('agendaris.jenis', $jenis);
        }

        if ($this->isDate($from)) {
            $this->model->where('agendaris.tanggal_diterima >=', $from);
        }

        if ($this->isDate($to)) {
            $this->model->where('agendaris.tanggal_diterima <=', $to);
        }

        $jenisOptions = db_connect()->table('agendaris')
            ->select('jenis')
            ->where('jenis IS NOT NULL', null, false)
            ->where('jenis !=', '')
            ->groupBy('jenis')
            ->orderBy('jenis', 'ASC')
            ->get()
            ->getResultArray();

        return view('agendaris/index', [
            'title'   => 'Surat Masuk',
            'agenda'  => $this->model->orderBy('agendaris.created_at', 'ASC')->orderBy('agendaris.id', 'ASC')->paginate($perPage, 'agendaris'),
            'pager'   => $this->model->pager,
            'filters' => compact('keyword', 'jenis', 'from', 'to', 'perPage'),
            'jenisOptions' => array_column($jenisOptions, 'jenis'),
        ]);
    }

    public function store(): ResponseInterface
    {
        $data   = $this->payload();
        $errors = $this->validateAgenda($data);

        if ($errors !== []) {
            return $this->validationError($errors);
        }

        $id = $this->model->insert($data, true);
        if ($id === false) {
            return $this->validationError($this->model->errors() ?: ['Data agendaris gagal disimpan.']);
        }

        return $this->successResponse('Surat masuk berhasil ditambahkan ke Agendaris.', ['id' => $id], 201);
    }

    public function synchronize(): RedirectResponse
    {
        $db = db_connect();

        try {
            $db->query(
                "INSERT INTO agendaris (dokumen_masuk_id, pengirim, penerima, pengambilan, jenis, tanggal_diterima, tanggal_surat, nomor_surat, perihal_surat, created_at, updated_at) "
                . "SELECT d.id, d.pengirim, d.penerima, d.pengambilan, d.jenis, d.tanggal, NULL, NULL, COALESCE(NULLIF(TRIM(d.perihal), ''), 'Belum diisi'), NOW(), NOW() "
                . 'FROM dokumen_masuk d '
                . 'LEFT JOIN agendaris a ON a.dokumen_masuk_id = d.id '
                . "WHERE d.deleted_at IS NULL AND TRIM(COALESCE(d.pengambilan, '')) <> '' AND a.id IS NULL"
            );
        } catch (\Throwable $error) {
            return redirect()->to(site_url('agendaris/surat-masuk'))
                ->with('error', 'Sinkronisasi belum berhasil. Silakan coba kembali.');
        }

        $total = $db->affectedRows();
        $message = $total > 0
            ? "Sinkronisasi selesai. {$total} Surat Masuk yang hilang berhasil dibuat ulang dari Dokumen Masuk."
            : 'Semua Surat Masuk sudah tersinkronisasi dengan Dokumen Masuk.';

        return redirect()->to(site_url('agendaris/surat-masuk'))->with('sync_success', $message);
    }

    public function show(int $id): ResponseInterface
    {
        $agenda = $this->findJoined($id);

        return $this->response->setJSON([
            'success' => true,
            'agenda'  => [
                'id'                  => (int) $agenda['id'],
                'source_locked'       => $agenda['dokumen_masuk_id'] !== null,
                'sumber_data'         => $agenda['dokumen_masuk_id'] !== null ? 'Security · Dokumen Masuk' : 'Input Manual · Surat Masuk',
                'pengirim'            => $agenda['pengirim'],
                'penerima'            => $agenda['penerima'] ?: '-',
                'penerima_value'      => $agenda['penerima'] ?: '',
                'pengambilan'         => $agenda['pengambilan'] ?: '-',
                'pengambilan_value'   => $agenda['pengambilan'] ?: '',
                'jenis'               => $agenda['jenis'] ?: '-',
                'jenis_value'         => $agenda['jenis'] ?: '',
                'tanggal_diterima'    => date('d-m-Y', strtotime($agenda['tanggal_diterima'])),
                'tanggal_value'       => $agenda['tanggal_diterima'],
                'tanggal_surat'       => $agenda['tanggal_surat'] ? date('d-m-Y', strtotime($agenda['tanggal_surat'])) : 'Belum diisi',
                'tanggal_surat_value' => $agenda['tanggal_surat'] ?: '',
                'nomor_surat'         => $agenda['nomor_surat'] ?: 'Belum diisi',
                'nomor_surat_value'   => $agenda['nomor_surat'] ?: '',
                'perihal_surat'       => $agenda['perihal_surat'],
                'berkas_link'         => $agenda['berkas_link'] ?: '',
                'created_at'          => date('d-m-Y H:i', strtotime($agenda['created_at'])) . ' WIB',
                'updated_at'          => date('d-m-Y H:i', strtotime($agenda['updated_at'])) . ' WIB',
                'update_url'          => site_url("agendaris/surat-masuk/{$id}"),
                'delete_url'          => site_url("agendaris/surat-masuk/{$id}/hapus"),
            ],
        ]);
    }

    public function update(int $id): ResponseInterface
    {
        $agenda = $this->findJoined($id);
        $data   = $this->payload();

        if ($agenda['dokumen_masuk_id'] !== null) {
            foreach (['pengirim', 'penerima', 'pengambilan', 'jenis', 'tanggal_diterima'] as $lockedField) {
                $data[$lockedField] = $agenda[$lockedField];
            }
        }

        $errors = $this->validateAgenda($data);

        if ($errors !== []) {
            return $this->validationError($errors);
        }

        if (! $this->model->update($id, $data)) {
            return $this->validationError($this->model->errors() ?: ['Data agendaris gagal diperbarui.']);
        }

        return $this->successResponse('Surat masuk Agendaris berhasil diperbarui.');
    }

    public function destroy(int $id): ResponseInterface
    {
        $agenda = $this->findJoined($id);

        if (! $this->model->delete($id, true)) {
            return $this->validationError($this->model->errors() ?: ['Data agendaris gagal dihapus.']);
        }

        $nomor = $agenda['nomor_surat'] ?: 'belum diisi';
        $message = "Surat nomor {$nomor} berhasil dihapus permanen dari Agendaris.";
        session()->setFlashdata('success', $message);

        return $this->successResponse($message);
    }

    private function payload(): array
    {
        return [
            'pengirim'         => trim((string) $this->request->getPost('pengirim')),
            'penerima'         => trim((string) $this->request->getPost('penerima')),
            'pengambilan'      => trim((string) $this->request->getPost('pengambilan')),
            'jenis'            => trim((string) $this->request->getPost('jenis')),
            'tanggal_diterima' => trim((string) $this->request->getPost('tanggal_diterima')),
            'tanggal_surat'    => trim((string) $this->request->getPost('tanggal_surat')),
            'nomor_surat'      => trim((string) $this->request->getPost('nomor_surat')),
            'perihal_surat'    => trim((string) $this->request->getPost('perihal_surat')),
            'berkas_link'      => trim((string) $this->request->getPost('berkas_link')),
        ];
    }

    private function validateAgenda(array $data): array
    {
        $validation = service('validation')->setRules([
            'pengirim'         => 'required|max_length[255]',
            'penerima'         => 'permit_empty|max_length[255]',
            'pengambilan'      => 'permit_empty|max_length[255]',
            'jenis'            => 'required|max_length[100]',
            'tanggal_diterima' => 'required|valid_date[Y-m-d]',
            'tanggal_surat'    => 'required|valid_date[Y-m-d]',
            'nomor_surat'      => 'required|max_length[150]',
            'perihal_surat'    => 'required|max_length[255]',
            'berkas_link'      => 'permit_empty|max_length[2048]',
        ]);

        if (! $validation->run($data)) {
            return array_values($validation->getErrors());
        }

        if ($data['berkas_link'] !== '') {
            $scheme = strtolower((string) parse_url($data['berkas_link'], PHP_URL_SCHEME));
            if ($scheme !== 'https' || filter_var($data['berkas_link'], FILTER_VALIDATE_URL) === false) {
                return ['Link berkas harus berupa URL HTTPS yang valid.'];
            }
        }

        return [];
    }

    private function findJoined(int $id): array
    {
        $row = db_connect()->table('agendaris')
            ->where('agendaris.id', $id)
            ->get()
            ->getRowArray();

        if ($row === null) {
            throw PageNotFoundException::forPageNotFound('Data Surat Masuk Agendaris tidak ditemukan.');
        }

        return $row;
    }

    private function validationError(array $errors): ResponseInterface
    {
        return $this->response->setStatusCode(422)->setJSON([
            'success' => false,
            'message' => 'Periksa kembali data Surat Masuk.',
            'errors'  => array_values($errors),
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
        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

}
