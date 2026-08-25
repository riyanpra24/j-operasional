<?php

namespace App\Controllers;

use App\Models\DokumenMasukModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

class DokumenMasuk extends BaseController
{
    private DokumenMasukModel $model;

    public function __construct()
    {
        $this->model = new DokumenMasukModel();
    }

    public function index(): string
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $jenis   = trim((string) $this->request->getGet('jenis'));
        $from    = trim((string) $this->request->getGet('dari'));
        $to      = trim((string) $this->request->getGet('sampai'));
        $perPage = (int) $this->request->getGet('per_page');

        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 10;
        }

        if ($keyword !== '') {
            $this->model->groupStart()
                ->like('pengirim', $keyword)
                ->orLike('perihal', $keyword)
                ->orLike('penerima', $keyword)
                ->orLike('pengambilan', $keyword)
                ->orLike('jenis', $keyword)
                ->orLike('ekspedisi', $keyword)
                ->groupEnd();
        }

        if ($jenis !== '') {
            $this->model->where('jenis', $jenis);
        }

        if ($this->isDate($from)) {
            $this->model->where('tanggal >=', $from);
        }

        if ($this->isDate($to)) {
            $this->model->where('tanggal <=', $to);
        }

        $jenisOptions = db_connect()->table('dokumen_masuk')
            ->select('jenis')
            ->where('deleted_at', null)
            ->groupBy('jenis')
            ->orderBy('jenis', 'ASC')
            ->get()
            ->getResultArray();

        return view('dokumen_masuk/index', [
            'title'   => 'Dokumen Masuk',
            'dokumen' => $this->model->orderBy('created_at', 'ASC')->orderBy('id', 'ASC')->paginate($perPage, 'dokumen_masuk'),
            'pager'   => $this->model->pager,
            'filters' => compact('keyword', 'jenis', 'from', 'to', 'perPage'),
            'jenisOptions' => array_column($jenisOptions, 'jenis'),
        ]);
    }

    public function create(): string
    {
        return view('dokumen_masuk/form', [
            'title'       => 'Tambah Dokumen Masuk',
            'dokumen'     => [],
            'action'      => site_url('dokumen-masuk'),
            'submitLabel' => 'Simpan dokumen',
        ]);
    }

    public function store(): ResponseInterface
    {
        $data = $this->payload();

        if (! $this->validatePayload($data)) {
            if ($this->request->isAJAX()) {
                return $this->ajaxError(service('validation')->getErrors());
            }

            return redirect()->back()->withInput()->with('errors', service('validation')->getErrors());
        }

        $id = $this->model->insert($data, true);

        if ($id === false) {
            if ($this->request->isAJAX()) {
                return $this->ajaxError($this->model->errors());
            }

            return redirect()->back()->withInput()->with('errors', $this->model->errors());
        }

        $message = 'Dokumen masuk berhasil disimpan.';
        session()->setFlashdata('success', $message);

        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(201)->setJSON([
                'success' => true,
                'message' => $message,
                'id'      => $id,
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        return redirect()->to(site_url("dokumen-masuk/{$id}"));
    }

    public function show(int $id): string|ResponseInterface
    {
        $dokumen = $this->findOrFail($id);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'dokumen' => [
                    'id'              => (int) $dokumen['id'],
                    'pengirim'        => $dokumen['pengirim'],
                    'perihal'         => $dokumen['perihal'] ?: '-',
                    'penerima'        => $dokumen['penerima'] ?: '-',
                    'hari'            => $dokumen['hari'],
                    'tanggal'         => date('d-m-Y', strtotime($dokumen['tanggal'])),
                    'tanggal_value'   => $dokumen['tanggal'],
                    'jenis'           => $dokumen['jenis'],
                    'jumlah'          => number_format((int) $dokumen['jumlah'], 0, ',', '.'),
                    'ekspedisi'       => $dokumen['ekspedisi'] ?: '-',
                    'pengambilan'     => $dokumen['pengambilan'] ?: 'Belum diambil',
                    'penyerahan_at'   => $dokumen['penyerahan_at']
                        ? date('d-m-Y H:i', strtotime($dokumen['penyerahan_at'])) . ' WIB'
                        : '',
                    'created_at'      => date('d-m-Y H:i', strtotime($dokumen['created_at'])) . ' WIB',
                    'updated_at'      => date('d-m-Y H:i', strtotime($dokumen['updated_at'])) . ' WIB',
                    'edit_url'        => site_url("dokumen-masuk/{$id}/ubah"),
                    'update_url'      => site_url("dokumen-masuk/{$id}"),
                ],
            ]);
        }

        return view('dokumen_masuk/show', [
            'title'   => 'Detail Dokumen Masuk',
            'dokumen' => $dokumen,
        ]);
    }

    public function edit(int $id): string
    {
        return view('dokumen_masuk/form', [
            'title'       => 'Ubah Dokumen Masuk',
            'dokumen'     => $this->findOrFail($id),
            'action'      => site_url("dokumen-masuk/{$id}"),
            'submitLabel' => 'Simpan perubahan',
        ]);
    }

    public function update(int $id): ResponseInterface
    {
        $this->findOrFail($id);
        $data = $this->payload();

        if (! $this->validatePayload($data)) {
            if ($this->request->isAJAX()) {
                return $this->ajaxError(service('validation')->getErrors());
            }

            return redirect()->back()->withInput()->with('errors', service('validation')->getErrors());
        }

        if (! $this->model->update($id, $data)) {
            if ($this->request->isAJAX()) {
                return $this->ajaxError($this->model->errors());
            }

            return redirect()->back()->withInput()->with('errors', $this->model->errors());
        }

        $message = 'Dokumen masuk berhasil diperbarui.';
        session()->setFlashdata('success', $message);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'message' => $message,
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        return redirect()->to(site_url("dokumen-masuk/{$id}"));
    }

    public function destroy(int $id): ResponseInterface
    {
        $dokumen = $this->findOrFail($id);

        if (trim((string) ($dokumen['pengambilan'] ?? '')) !== '') {
            $message = 'Dokumen sudah diserahkan dan tidak dapat dihapus dari sistem.';

            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(409)->setJSON([
                    'success' => false,
                    'message' => $message,
                    'errors'  => [$message],
                    'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
                ]);
            }

            return redirect()->back()->with('errors', ['delete' => $message]);
        }

        if (! $this->model->delete($id, true)) {
            $errors = $this->model->errors() ?: ['delete' => 'Dokumen gagal dihapus dari database.'];

            if ($this->request->isAJAX()) {
                return $this->ajaxError($errors);
            }

            return redirect()->back()->with('errors', $errors);
        }

        $message = "Dokumen dari {$dokumen['pengirim']} telah dihapus permanen dari database.";
        session()->setFlashdata('success', $message);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'message' => $message,
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        return redirect()->to(site_url('dokumen-masuk'));
    }

    private function payload(): array
    {
        $tanggal = trim((string) $this->request->getPost('tanggal'));
        $perihalPilihan = trim((string) $this->request->getPost('perihal_pilihan'));
        $perihal = match ($perihalPilihan) {
            'Confidential Documents' => 'Confidential Documents',
            'Lainnya'                => trim((string) $this->request->getPost('perihal_lainnya')),
            default                  => trim((string) $this->request->getPost('perihal')),
        };
        $ekspedisiPilihan = trim((string) $this->request->getPost('ekspedisi_pilihan'));
        $ekspedisi = match ($ekspedisiPilihan) {
            'Lainnya' => trim((string) $this->request->getPost('ekspedisi_lainnya')),
            ''        => trim((string) $this->request->getPost('ekspedisi')),
            default   => $ekspedisiPilihan,
        };
        $jenisPilihan = trim((string) $this->request->getPost('jenis'));
        $jenis = $jenisPilihan === 'Lainnya'
            ? trim((string) $this->request->getPost('jenis_lainnya'))
            : $jenisPilihan;

        return [
            'pengirim'  => trim((string) $this->request->getPost('pengirim')),
            'perihal'   => $perihal,
            'penerima'  => trim((string) $this->request->getPost('penerima')),
            'hari'      => $this->dayName($tanggal),
            'tanggal'   => $tanggal,
            'jenis'     => $jenis,
            'jumlah'    => (int) $this->request->getPost('jumlah'),
            'ekspedisi' => $this->nullIfEmpty($ekspedisi),
        ];
    }

    private function validatePayload(array $data): bool
    {
        return service('validation')->setRules([
            'pengirim'  => 'required|max_length[255]',
            'perihal'   => 'required|max_length[255]',
            'penerima'  => 'required|max_length[255]',
            'hari'      => 'required|in_list[Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu]',
            'tanggal'   => 'required|valid_date[Y-m-d]',
            'jenis'     => 'required|max_length[100]',
            'jumlah'    => 'required|integer|greater_than_equal_to[1]',
            'ekspedisi' => 'permit_empty|max_length[150]',
        ])->run($data);
    }

    private function findOrFail(int $id): array
    {
        $row = $this->model->find($id);
        if ($row === null) {
            throw PageNotFoundException::forPageNotFound('Dokumen masuk tidak ditemukan.');
        }

        return $row;
    }

    private function dayName(string $date): string
    {
        if (! $this->isDate($date)) {
            return '';
        }

        return [1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'][(int) date('N', strtotime($date))];
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

    private function ajaxError(array $errors): ResponseInterface
    {
        return $this->response->setStatusCode(422)->setJSON([
            'success' => false,
            'message' => 'Periksa kembali data yang diinput.',
            'errors'  => array_values($errors),
            'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
        ]);
    }
}
