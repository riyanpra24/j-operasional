<?php

namespace App\Controllers;

use App\Models\DokumenMasukModel;
use App\Models\DokumenKeluarModel;
use CodeIgniter\HTTP\ResponseInterface;

class DistribusiDokumen extends BaseController
{
    public function index(): string
    {
        $model   = new DokumenMasukModel();
        $outgoing = new DokumenKeluarModel();
        $keyword = trim((string) $this->request->getGet('q'));
        $from    = trim((string) $this->request->getGet('dari'));
        $to      = trim((string) $this->request->getGet('sampai'));
        $perPage = (int) $this->request->getGet('per_page');

        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 10;
        }

        $model->groupStart()
            ->where('pengambilan', null)
            ->orWhere('pengambilan', '')
            ->groupEnd();

        if ($keyword !== '') {
            $model->groupStart()
                ->like('pengirim', $keyword)
                ->orLike('perihal', $keyword)
                ->orLike('penerima', $keyword)
                ->orLike('jenis', $keyword)
                ->orLike('ekspedisi', $keyword)
                ->groupEnd();
        }

        if ($this->isDate($from)) {
            $model->where('tanggal >=', $from);
        }

        if ($this->isDate($to)) {
            $model->where('tanggal <=', $to);
        }

        $outgoing->select('dokumen_keluar.*')
            ->join('distribusi_dokumen', 'distribusi_dokumen.dokumen_keluar_id = dokumen_keluar.id', 'inner')
            ->where('dokumen_keluar.progres !=', 'Diambil Ekspedisi');

        if ($keyword !== '') {
            $outgoing->groupStart()
                ->like('dokumen_keluar.nomor_surat', $keyword)
                ->orLike('dokumen_keluar.jenis_surat', $keyword)
                ->orLike('dokumen_keluar.pemohon', $keyword)
                ->orLike('dokumen_keluar.pelaksana', $keyword)
                ->orLike('dokumen_keluar.up', $keyword)
                ->orLike('dokumen_keluar.security', $keyword)
                ->orLike('dokumen_keluar.alamat_penerima', $keyword)
                ->groupEnd();
        }

        if ($this->isDate($from)) {
            $outgoing->where('dokumen_keluar.tanggal_pengiriman >=', $from);
        }

        if ($this->isDate($to)) {
            $outgoing->where('dokumen_keluar.tanggal_pengiriman <=', $to);
        }

        $db = db_connect();

        return view('distribusi_dokumen/index', [
            'title'          => 'Distribusi Dokumen',
            'dokumen'        => $model->orderBy('created_at', 'ASC')->orderBy('id', 'ASC')->paginate($perPage, 'distribusi_dokumen'),
            'pager'          => $model->pager,
            'dokumenKeluar'  => $outgoing->orderBy('distribusi_dokumen.id', 'ASC')->paginate($perPage, 'distribusi_keluar'),
            'pagerKeluar'    => $outgoing->pager,
            'filters'        => compact('keyword', 'from', 'to', 'perPage'),
            'totalData' => $db->table('dokumen_masuk')
                ->where('deleted_at', null)
                ->groupStart()
                ->where('pengambilan', null)
                ->orWhere('pengambilan', '')
                ->groupEnd()
                ->countAllResults(),
            'totalKeluar' => $db->table('distribusi_dokumen')
                ->join('dokumen_keluar', 'dokumen_keluar.id = distribusi_dokumen.dokumen_keluar_id', 'inner')
                ->where('dokumen_keluar.progres !=', 'Diambil Ekspedisi')
                ->countAllResults(),
        ]);
    }

    public function showOutgoing(int $id): ResponseInterface
    {
        $dokumen = $this->findOutgoing($id);

        return $this->response->setJSON([
            'success' => true,
            'dokumen' => [
                'nomor_surat'              => $dokumen['nomor_surat'],
                'jenis_surat'              => $dokumen['jenis_surat'],
                'pemohon'                  => $dokumen['pemohon'] ?: '-',
                'pelaksana'                => $dokumen['pelaksana'] ?: '-',
                'up'                       => $dokumen['up'] ?: '-',
                'tanggal_pengiriman'       => date('d-m-Y', strtotime($dokumen['tanggal_pengiriman'])),
                'alamat_penerima'          => $dokumen['alamat_penerima'],
                'tanggal_security_value'   => $dokumen['tanggal_security'] ?: '',
                'security_value'           => $dokumen['security'] ?: '',
                'progres_value'            => $dokumen['progres'] ?: 'Menunggu Ekspedisi',
                'process_url'              => site_url("distribusi-dokumen/surat-keluar/{$id}"),
            ],
        ]);
    }

    public function completeOutgoing(int $id): ResponseInterface
    {
        $dokumen = $this->findOutgoing($id);
        $data = [
            'tanggal_security' => trim((string) $this->request->getPost('tanggal_security')),
            'security'         => trim((string) $this->request->getPost('security')),
            'progres'          => trim((string) $this->request->getPost('progres')),
        ];

        $validation = service('validation')->setRules([
            'tanggal_security' => 'required|valid_date[Y-m-d]',
            'security'         => 'required|in_list[Yanto Pujoyuwono,M. Aziz Dwi Pratomo,Ach. Fathur Rozi,Yayak Andriyani]',
            'progres'          => 'required|in_list[Diambil Ekspedisi,Menunggu Ekspedisi]',
        ], [
            'security' => [
                'required'   => 'Field Security wajib diisi.',
                'in_list'    => 'Pilih petugas Security dari daftar yang tersedia.',
            ],
            'progres' => [
                'required' => 'Field Progres wajib dipilih.',
                'in_list'  => 'Pilih Progres dari daftar yang tersedia.',
            ],
            'tanggal_security' => [
                'required'   => 'Tanggal Diterima Security wajib diisi.',
                'valid_date' => 'Format Tanggal Diterima Security tidak valid.',
            ],
        ]);

        if (! $validation->run($data)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Periksa kembali data distribusi Surat Keluar.',
                'errors'  => array_values($validation->getErrors()),
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        if ($data['tanggal_security'] !== '' && $data['tanggal_security'] < $dokumen['tanggal_pengiriman']) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Tanggal Security tidak boleh lebih awal dari tanggal pengiriman.',
                'errors'  => ['Periksa kembali Tanggal Security.'],
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        foreach ($data as $field => $value) {
            if ($value === '') {
                $data[$field] = null;
            }
        }

        if (! db_connect()->table('dokumen_keluar')->where('id', $id)->update($data)) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Data distribusi Surat Keluar belum dapat disimpan.',
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        $message = 'Distribusi Surat Keluar berhasil diperbarui.';
        session()->setFlashdata('success', $message);

        return $this->response->setJSON([
            'success' => true,
            'message' => $message,
            'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
        ]);
    }

    public function show(int $id): ResponseInterface
    {
        $model   = new DokumenMasukModel();
        $dokumen = $model->find($id);

        if ($dokumen === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Dokumen tidak ditemukan.',
            ]);
        }

        if (trim((string) $dokumen['pengambilan']) !== '') {
            return $this->response->setStatusCode(409)->setJSON([
                'success' => false,
                'message' => 'Dokumen ini sudah diproses pengambilannya.',
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'dokumen' => [
                'pengirim'   => $dokumen['pengirim'],
                'perihal'    => $dokumen['perihal'] ?: '-',
                'penerima'   => $dokumen['penerima'] ?: '-',
                'hari'       => $dokumen['hari'],
                'tanggal'    => date('d-m-Y', strtotime($dokumen['tanggal'])),
                'jenis'      => $dokumen['jenis'],
                'jumlah'     => number_format((int) $dokumen['jumlah'], 0, ',', '.'),
                'ekspedisi'  => $dokumen['ekspedisi'] ?: '-',
                'process_url'=> site_url("distribusi-dokumen/{$id}"),
            ],
        ]);
    }

    public function complete(int $id): ResponseInterface
    {
        $model   = new DokumenMasukModel();
        $dokumen = $model->find($id);

        if ($dokumen === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Dokumen tidak ditemukan.',
            ]);
        }

        if (trim((string) $dokumen['pengambilan']) !== '') {
            return $this->response->setStatusCode(409)->setJSON([
                'success' => false,
                'message' => 'Pengambilan sudah pernah dicatat dan tidak dapat diubah.',
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        $pengambilan = trim((string) $this->request->getPost('pengambilan'));
        $validation  = service('validation')->setRules([
            'pengambilan' => 'required|max_length[255]',
        ]);

        if (! $validation->run(['pengambilan' => $pengambilan])) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Isi kolom pengambilan terlebih dahulu.',
                'errors'  => array_values($validation->getErrors()),
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        $db = db_connect();
        $db->transStart();
<<<<<<< HEAD
        $model->update($id, [
            'pengambilan'  => $pengambilan,
            'penyerahan_at' => date('Y-m-d H:i:s'),
=======
        $pengambilanAt = date('Y-m-d H:i:s');
        $model->update($id, [
            'pengambilan'    => $pengambilan,
            'pengambilan_at' => $pengambilanAt,
>>>>>>> 3fb4f07c1d3125ff5fb057a935357640b39148e3
        ]);

        $agendaExists = $db->table('agendaris')->where('dokumen_masuk_id', $id)->countAllResults() > 0;
        if (! $agendaExists) {
            $db->table('agendaris')->insert([
                'dokumen_masuk_id' => $id,
                'pengirim'          => $dokumen['pengirim'],
                'penerima'          => $dokumen['penerima'],
                'pengambilan'       => $pengambilan,
                'jenis'             => $dokumen['jenis'],
                'tanggal_diterima'  => $dokumen['tanggal'],
                'tanggal_surat'    => null,
                'nomor_surat'      => null,
                'perihal_surat'    => trim((string) $dokumen['perihal']) ?: 'Belum diisi',
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);
        }
        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Pengambilan dan Agendaris belum dapat disimpan.',
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        $message = 'Pengambilan berhasil dicatat dan dokumen masuk otomatis ke Agendaris.';
        session()->setFlashdata('success', $message);

        return $this->response->setJSON([
            'success' => true,
            'message' => $message,
            'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
        ]);
    }

    private function isDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function findOutgoing(int $id): array
    {
        $dokumen = db_connect()->table('dokumen_keluar')
            ->select('dokumen_keluar.*')
            ->join('distribusi_dokumen', 'distribusi_dokumen.dokumen_keluar_id = dokumen_keluar.id', 'inner')
            ->where('dokumen_keluar.id', $id)
            ->where('dokumen_keluar.progres !=', 'Diambil Ekspedisi')
            ->get()
            ->getRowArray();

        if ($dokumen === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Data distribusi Surat Keluar tidak ditemukan.');
        }

        return $dokumen;
    }
}
