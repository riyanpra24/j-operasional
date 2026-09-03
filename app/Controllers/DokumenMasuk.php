<?php

namespace App\Controllers;

use App\Models\DokumenMasukModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use Config\SecurityPersonnel;

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
        $order = $this->requestedListOrder();

        $this->model->where('pengambilan IS NOT NULL', null, false)
            ->where('pengambilan !=', '');

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
            ->where('pengambilan IS NOT NULL', null, false)
            ->where('pengambilan !=', '')
            ->groupBy('jenis')
            ->orderBy('jenis', 'ASC')
            ->get()
            ->getResultArray();

        if ($order !== '') {
            $direction = $order === 'terbaru' ? 'DESC' : 'ASC';
            $this->model->orderBy('tanggal', $direction)->orderBy('id', $direction);
        } else {
            $this->model->orderBy('created_at', 'ASC')->orderBy('id', 'ASC');
        }

        return view('dokumen_masuk/index', [
            'title'   => 'Dokumen Masuk',
            'dokumen' => $this->model->paginate($perPage, 'dokumen_masuk'),
            'pager'   => $this->model->pager,
            'filters' => compact('keyword', 'jenis', 'from', 'to', 'perPage', 'order'),
            'jenisOptions' => array_column($jenisOptions, 'jenis'),
        ]);
    }

    public function create(): string
    {
        return view('dokumen_masuk/form', [
            'title'       => 'Tambah Dokumen Masuk',
            'dokumen'     => [],
            'action'      => site_url('distribusi-dokumen/dokumen-masuk'),
            'submitLabel' => 'Simpan dokumen',
            'returnUrl'   => site_url('distribusi-dokumen'),
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

        return redirect()->to(site_url('distribusi-dokumen'));
    }

    public function show(int $id): string|ResponseInterface
    {
        $dokumen = $this->findOrFail($id);
        $isComplete = trim((string) ($dokumen['pengambilan'] ?? '')) !== '';
        $isDistributionRequest = service('uri')->getSegment(1) === 'distribusi-dokumen';

        if (($isDistributionRequest && $isComplete) || (! $isDistributionRequest && ! $isComplete)) {
            throw PageNotFoundException::forPageNotFound('Dokumen masuk tidak tersedia pada tahap ini.');
        }

        if ($this->request->isAJAX()) {
            $handoverHistory = db_connect()->table('security_handover_history')
                ->where('dokumen_masuk_id', $id)
                ->orderBy('diserahkan_at', 'DESC')
                ->orderBy('id', 'DESC')
                ->get()
                ->getResultArray();

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
                    'satuan_jumlah'   => ($dokumen['satuan_jumlah'] ?? null) ?: '-',
                    'satuan_jumlah_value' => ($dokumen['satuan_jumlah'] ?? null) ?: '',
                    'ekspedisi'       => $dokumen['ekspedisi'] ?: '-',
                    'pengambilan'     => $dokumen['pengambilan'] ?: 'Belum diserahkan',
                    'penyerahan_at'   => $dokumen['penyerahan_at']
                        ? date('d-m-Y H:i', strtotime($dokumen['penyerahan_at'])) . ' WIB'
                        : '',
                    'serah_terima_shift_at' => $dokumen['serah_terima_shift_at']
                        ? date('d-m-Y H:i', strtotime($dokumen['serah_terima_shift_at'])) . ' WIB'
                        : '',
                    'serah_terima_shift_oleh' => $dokumen['serah_terima_shift_oleh'] ?: '',
                    'security_penanggung_jawab' => $dokumen['penerima'] ?: 'Belum ditentukan',
                    'serah_terima_history' => array_map(static fn (array $item): array => [
                        'security_dari' => $item['security_dari'] ?: 'Belum ditentukan',
                        'security_ke'   => $item['security_ke'],
                        'dicatat_oleh'  => $item['dicatat_oleh'],
                        'waktu'         => date('d-m-Y H:i', strtotime($item['diserahkan_at'])) . ' WIB',
                    ], $handoverHistory),
                    'created_at'      => date('d-m-Y H:i', strtotime($dokumen['created_at'])) . ' WIB',
                    'updated_at'      => date('d-m-Y H:i', strtotime($dokumen['updated_at'])) . ' WIB',
                    'edit_url'        => site_url("distribusi-dokumen/dokumen-masuk/{$id}/ubah"),
                    'update_url'      => site_url("distribusi-dokumen/dokumen-masuk/{$id}"),
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
        $dokumen = $this->findOrFail($id);
        if (trim((string) ($dokumen['pengambilan'] ?? '')) !== '') {
            throw PageNotFoundException::forPageNotFound('Dokumen yang sudah selesai hanya dapat dilihat dari arsip Dokumen Masuk.');
        }

        return view('dokumen_masuk/form', [
            'title'       => 'Ubah Dokumen Masuk',
            'dokumen'     => $dokumen,
            'action'      => site_url("distribusi-dokumen/dokumen-masuk/{$id}"),
            'submitLabel' => 'Simpan perubahan',
            'returnUrl'   => site_url('distribusi-dokumen'),
        ]);
    }

    public function update(int $id): ResponseInterface
    {
        $dokumen = $this->findOrFail($id);
        if (trim((string) ($dokumen['pengambilan'] ?? '')) !== '') {
            return $this->response->setStatusCode(409)->setJSON([
                'success' => false,
                'message' => 'Dokumen yang sudah selesai hanya dapat dilihat dari arsip Dokumen Masuk.',
                'errors'  => ['Data distribusi yang sudah selesai tidak dapat diubah.'],
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }
        $data = $this->payload();
        // Admin tetap dapat memperbaiki penerima dari form edit. Untuk role
        // lainnya, pergantian penerima hanya melalui alur Serah Terima.
        if ((string) session()->get('auth_role') !== 'admin') {
            $data['penerima'] = (string) ($dokumen['penerima'] ?? '');
        }

        if (! $this->validatePayload($data)) {
            if ($this->request->isAJAX()) {
                return $this->ajaxError(service('validation')->getErrors());
            }

            return redirect()->back()->withInput()->with('errors', service('validation')->getErrors());
        }

        $isShiftHandover = $this->request->getPost('submit_action') === 'handover';
        $handoverHistory = null;
        if ($isShiftHandover) {
            $actor = trim((string) session()->get('auth_display_name'));
            if ($actor === '') {
                $actor = trim((string) session()->get('auth_username')) ?: 'Petugas Security';
            }

            $securityTujuan = trim((string) $this->request->getPost('security_tujuan'));
            if (! in_array($securityTujuan, SecurityPersonnel::NAMES, true)) {
                return $this->ajaxError(['Pilih Security penerima serah terima dari daftar yang tersedia.']);
            }

            $securityDari = trim((string) ($dokumen['penerima'] ?? ''));
            if ($securityDari === '') {
                return $this->ajaxError(['Penerima lama belum tersedia dan harus diisi sebelum serah terima.']);
            }

            if ($securityDari === $securityTujuan) {
                return $this->ajaxError(['Security tujuan harus berbeda dari penanggung jawab saat ini.']);
            }

            $handoverAt = date('Y-m-d H:i:s');
            $data['serah_terima_shift_at']   = $handoverAt;
            $data['serah_terima_shift_oleh'] = $actor;
            $data['security_penanggung_jawab'] = $securityTujuan;
            $data['penerima'] = $securityTujuan;
            $handoverHistory = [
                'dokumen_masuk_id' => $id,
                'security_dari'    => $securityDari,
                'security_ke'      => $securityTujuan,
                'dicatat_oleh'     => $actor,
                'diserahkan_at'    => $handoverAt,
            ];
        }

        $db = db_connect();
        $db->transStart();
        $updated = $this->model->update($id, $data);
        if ($updated && $handoverHistory !== null) {
            $db->table('security_handover_history')->insert($handoverHistory);
        }
        $db->transComplete();

        if (! $updated || ! $db->transStatus()) {
            if ($this->request->isAJAX()) {
                return $this->ajaxError($this->model->errors());
            }

            return redirect()->back()->withInput()->with('errors', $this->model->errors());
        }

        $message = $isShiftHandover
            ? 'Serah terima shift berhasil dicatat. Dokumen tetap berada di antrean distribusi.'
            : 'Dokumen masuk berhasil diperbarui.';
        session()->setFlashdata('success', $message);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'message' => $message,
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        return redirect()->to(site_url('distribusi-dokumen'));
    }

    public function reopen(int $id): ResponseInterface
    {
        $dokumen = $this->findOrFail($id);
        if (trim((string) ($dokumen['pengambilan'] ?? '')) === '') {
            return $this->response->setStatusCode(409)->setJSON([
                'success' => false,
                'message' => 'Dokumen sudah berada di Distribusi Dokumen.',
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        if (! $this->model->update($id, [
            'pengambilan'   => null,
            'penyerahan_at' => null,
        ])) {
            return $this->ajaxError($this->model->errors() ?: ['Dokumen belum dapat dikembalikan ke Distribusi Dokumen.']);
        }

        $message = 'Dokumen Masuk berhasil dikembalikan ke Distribusi Dokumen dan telah keluar dari arsip.';
        session()->setFlashdata('success', $message);

        return $this->response->setJSON([
            'success'      => true,
            'message'      => $message,
            'redirect_url' => site_url('dokumen-masuk'),
            'csrf'         => ['name' => csrf_token(), 'hash' => csrf_hash()],
        ]);
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

        if (! $this->deleteRecord($this->model, 'dokumen_masuk', $id)) {
            $errors = $this->model->errors() ?: ['delete' => 'Dokumen gagal dihapus.'];

            if ($this->request->isAJAX()) {
                return $this->ajaxError($errors);
            }

            return redirect()->back()->with('errors', $errors);
        }

        $message = $this->currentRoleIsAdmin()
            ? "Dokumen dari {$dokumen['pengirim']} berhasil dihapus permanen."
            : "Dokumen dari {$dokumen['pengirim']} berhasil dihapus.";
        session()->setFlashdata('success', $message);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'message' => $message,
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        return redirect()->to(site_url('distribusi-dokumen'));
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
            'satuan_jumlah' => $this->nullIfEmpty($this->request->getPost('satuan_jumlah')),
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
            'jenis'     => 'required|in_list[Surat,Dokumen,Berkas,Paket]',
            'jumlah'    => 'required|integer|greater_than_equal_to[1]',
            'satuan_jumlah' => 'permit_empty|max_length[50]',
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
