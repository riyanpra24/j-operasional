<?php

namespace App\Controllers;

use App\Models\DokumenMasukModel;
use App\Models\DokumenKeluarModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\SecurityPersonnel;

class DistribusiDokumen extends BaseController
{
    public function index(): string
    {
        $model   = new DokumenMasukModel();
        $outgoing = new DokumenKeluarModel();
        $keyword = trim((string) $this->request->getGet('q'));
        $tab     = trim((string) $this->request->getGet('tab'));
        $from    = trim((string) $this->request->getGet('dari'));
        $to      = trim((string) $this->request->getGet('sampai'));
        $perPage = (int) $this->request->getGet('per_page');

        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 10;
        }

        if (! in_array($tab, ['masuk', 'keluar'], true)) {
            $tab = 'masuk';
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
                ->orLike('dokumen_keluar.nama_ekspedisi', $keyword)
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

        return view('distribusi_dokumen/index', [
            'title'          => 'Distribusi Dokumen',
            'dokumen'        => $model->orderBy('created_at', 'ASC')->orderBy('id', 'ASC')->paginate($perPage, 'distribusi_dokumen'),
            'pager'          => $model->pager,
            'dokumenKeluar'  => $outgoing->orderBy('distribusi_dokumen.id', 'ASC')->paginate($perPage, 'distribusi_keluar'),
            'pagerKeluar'    => $outgoing->pager,
            'filters'        => compact('keyword', 'tab', 'from', 'to', 'perPage'),
        ]);
    }

    public function showOutgoing(int $id): ResponseInterface
    {
        $dokumen = $this->findOutgoing($id);
        $handoverHistory = $this->outgoingHandoverHistory($id);

        return $this->response->setJSON([
            'success' => true,
            'dokumen' => [
                'nomor_surat'              => $dokumen['nomor_surat'],
                'jenis_surat'              => $dokumen['jenis_surat'],
                'jumlah_dokumen'           => ($dokumen['jumlah_dokumen'] ?? null) ?: '-',
                'nama_ekspedisi'           => ($dokumen['nama_ekspedisi'] ?? null) ?: '-',
                'pemohon'                  => $dokumen['pemohon'] ?: '-',
                'pelaksana'                => $dokumen['pelaksana'] ?: '-',
                'up'                       => $dokumen['up'] ?: '-',
                'tanggal_pengiriman'       => date('d-m-Y', strtotime($dokumen['tanggal_pengiriman'])),
                'alamat_penerima'          => $dokumen['alamat_penerima'],
                'tanggal_security_value'   => $dokumen['tanggal_security'] ?: '',
                'security_value'           => $dokumen['security'] ?: '',
                'progres_value'            => $dokumen['progres'] ?: 'Menunggu Ekspedisi',
                'serah_terima_history'      => $handoverHistory,
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
        $isShiftHandover = $this->request->getPost('submit_action') === 'handover';
        $handoverHistory = null;
        if (! $isShiftHandover && trim((string) ($dokumen['security'] ?? '')) !== '') {
            $data['security'] = $dokumen['security'];
        }

        $validation = service('validation')->setRules([
            'tanggal_security' => 'required|valid_date[Y-m-d]',
            'security'         => 'required|in_list[' . implode(',', SecurityPersonnel::NAMES) . ']',
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

        if ($isShiftHandover) {
            $securityLama = trim((string) ($dokumen['security'] ?? ''));
            $securityBaru = trim((string) $this->request->getPost('security_tujuan'));
            if ($securityLama === '') {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Security lama belum tersedia.',
                    'errors'  => ['Simpan field Security terlebih dahulu sebelum melakukan serah terima.'],
                    'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
                ]);
            }
            if (! in_array($securityBaru, SecurityPersonnel::NAMES, true)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Pilih Security tujuan dari daftar yang tersedia.',
                    'errors'  => ['Field Security Baru wajib dipilih.'],
                    'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
                ]);
            }
            if ($securityLama === $securityBaru) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Security tujuan harus berbeda dari Security lama.',
                    'errors'  => ['Pilih petugas Security yang berbeda.'],
                    'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
                ]);
            }

            $actor = trim((string) session()->get('auth_display_name'));
            if ($actor === '') {
                $actor = trim((string) session()->get('auth_username')) ?: 'Petugas Security';
            }
            $handoverAt = date('Y-m-d H:i:s');
            $data['security'] = $securityBaru;
            $handoverHistory = [
                'dokumen_keluar_id' => $id,
                'security_dari'     => $securityLama,
                'security_ke'       => $securityBaru,
                'dicatat_oleh'      => $actor,
                'diserahkan_at'     => $handoverAt,
            ];
        }

        $securityTime = ! empty($dokumen['diterima_security_at'])
            ? date('H:i:s', strtotime($dokumen['diterima_security_at']))
            : date('H:i:s');
        $data['diterima_security_at'] = $data['tanggal_security'] . ' ' . $securityTime;

        if ($data['progres'] === 'Diambil Ekspedisi' && empty($dokumen['diambil_ekspedisi_at'])) {
            $data['diambil_ekspedisi_at'] = date('Y-m-d H:i:s');
        }

        $db = db_connect();
        $db->transStart();
        $updated = $db->table('dokumen_keluar')->where('id', $id)->update($data);
        if ($updated && $handoverHistory !== null) {
            $db->table('outgoing_security_handover_history')->insert($handoverHistory);
        }
        $db->transComplete();

        if (! $updated || ! $db->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Data distribusi Surat Keluar belum dapat disimpan.',
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        $message = $isShiftHandover
            ? 'Serah terima Security Dokumen Keluar berhasil dicatat.'
            : 'Distribusi Surat Keluar berhasil diperbarui.';
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
                'message' => 'Dokumen ini sudah diproses penyerahannya.',
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
                'satuan_jumlah' => ($dokumen['satuan_jumlah'] ?? null) ?: '-',
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
                'message' => 'Penyerahan sudah pernah dicatat dan tidak dapat diubah.',
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
                'message' => 'Isi kolom penyerahan terlebih dahulu.',
                'errors'  => array_values($validation->getErrors()),
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        $db = db_connect();
        $db->transStart();
        $model->update($id, [
            'pengambilan'   => $pengambilan,
            'penyerahan_at' => date('Y-m-d H:i:s'),
        ]);

        $agendaData = [
            'pengirim'         => $dokumen['pengirim'],
            'penerima'         => $dokumen['penerima'],
            'pengambilan'      => $pengambilan,
            'jenis'            => $dokumen['jenis'],
            'tanggal_diterima' => $dokumen['tanggal'],
            'perihal_surat'    => trim((string) $dokumen['perihal']) ?: 'Belum diisi',
            'updated_at'       => date('Y-m-d H:i:s'),
        ];
        $agenda = $db->table('agendaris')->where('dokumen_masuk_id', $id)->get()->getRowArray();
        if ($agenda === null) {
            $db->table('agendaris')->insert(array_merge($agendaData, [
                'dokumen_masuk_id' => $id,
                'tanggal_surat'    => null,
                'nomor_surat'      => null,
                'created_at'       => date('Y-m-d H:i:s'),
            ]));
        } else {
            $db->table('agendaris')->where('id', $agenda['id'])->update($agendaData);
        }
        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Penyerahan dan Agendaris belum dapat disimpan.',
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        $message = 'Penyerahan berhasil dicatat dan dokumen masuk otomatis ke Agendaris.';
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
            ->where('dokumen_keluar.deleted_at', null)
            ->where('dokumen_keluar.progres !=', 'Diambil Ekspedisi')
            ->get()
            ->getRowArray();

        if ($dokumen === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Data distribusi Surat Keluar tidak ditemukan.');
        }

        return $dokumen;
    }

    /** @return array<int, array<string, string>> */
    private function outgoingHandoverHistory(int $id): array
    {
        $rows = db_connect()->table('outgoing_security_handover_history')
            ->where('dokumen_keluar_id', $id)
            ->orderBy('diserahkan_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        return array_map(static fn (array $row): array => [
            'security_dari' => $row['security_dari'],
            'security_ke'   => $row['security_ke'],
            'dicatat_oleh'  => $row['dicatat_oleh'],
            'waktu'         => date('d-m-Y H:i', strtotime($row['diserahkan_at'])) . ' WIB',
        ], $rows);
    }
}
