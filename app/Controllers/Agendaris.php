<?php

namespace App\Controllers;

use App\Libraries\IncomingControlSheetPdf;
use App\Models\AgendarisModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class Agendaris extends BaseController
{
    private const NOMOR_AGENDARIS_PREFIX = 'AGD/KW/VI/';

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
        $this->model->where('agendaris.progres', 'Selesai');

        if ($keyword !== '') {
            $this->model->groupStart()
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
            ->where('progres', 'Selesai')
            ->where('jenis IS NOT NULL', null, false)
            ->where('jenis !=', '')
            ->groupBy('jenis')
            ->orderBy('jenis', 'ASC')
            ->get()
            ->getResultArray();

        return view('agendaris/index', [
            'title'   => 'Dokumen Masuk',
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
        $returnUrl = site_url('agendaris/progres-dokumen');

        try {
            $db->query(
                "INSERT INTO agendaris (dokumen_masuk_id, pengirim, penerima, pengambilan, jenis, tanggal_diterima, tanggal_surat, nomor_surat, perihal_surat, created_at, updated_at) "
                . "SELECT d.id, d.pengirim, d.penerima, d.pengambilan, d.jenis, d.tanggal, NULL, NULL, COALESCE(NULLIF(TRIM(d.perihal), ''), 'Belum diisi'), NOW(), NOW() "
                . 'FROM dokumen_masuk d '
                . 'LEFT JOIN agendaris a ON a.dokumen_masuk_id = d.id '
                . "WHERE d.deleted_at IS NULL AND TRIM(COALESCE(d.pengambilan, '')) <> '' AND a.id IS NULL"
            );
        } catch (\Throwable $error) {
            return redirect()->to($returnUrl)
                ->with('error', 'Sinkronisasi belum berhasil. Silakan coba kembali.');
        }

        $total = $db->affectedRows();
        $message = $total > 0
            ? "Sinkronisasi selesai. {$total} Surat Masuk yang hilang berhasil dibuat ulang dari Dokumen Masuk."
            : 'Semua Surat Masuk sudah tersinkronisasi dengan Dokumen Masuk.';

        return redirect()->to($returnUrl)->with('sync_success', $message);
    }

    public function generateNomor(): ResponseInterface
    {
        $row = db_connect()->table('agendaris')
            ->select('MAX(CAST(SUBSTRING(nomor_agendaris, 11) AS UNSIGNED)) AS nomor_terakhir', false)
            ->where("nomor_agendaris REGEXP '^AGD/KW/VI/[0-9]+$'", null, false)
            ->get()
            ->getRowArray();

        $next = ((int) ($row['nomor_terakhir'] ?? 0)) + 1;
        $nomor = self::NOMOR_AGENDARIS_PREFIX . str_pad((string) $next, 3, '0', STR_PAD_LEFT);

        return $this->successResponse('Nomor Agendaris berhasil dibuat.', [
            'nomor_agendaris' => $nomor,
        ]);
    }

    public function downloadControlSheet(): ResponseInterface
    {
        $data = [
            'nomor_agendaris'   => $this->nullIfEmpty($this->request->getPost('nomor_agendaris')),
            'tanggal_agendaris' => $this->nullIfEmpty($this->request->getPost('tanggal_agendaris')),
            'nomor_surat'       => trim((string) $this->request->getPost('nomor_surat')),
            'tanggal_surat'     => trim((string) $this->request->getPost('tanggal_surat')),
            'perihal_surat'     => trim((string) $this->request->getPost('perihal_surat')),
        ];

        $validation = service('validation')->setRules([
            'nomor_agendaris'   => 'permit_empty|max_length[50]',
            'tanggal_agendaris' => 'permit_empty|valid_date[Y-m-d]',
            'nomor_surat'       => 'required|max_length[150]',
            'tanggal_surat'     => 'required|valid_date[Y-m-d]',
            'perihal_surat'     => 'required|max_length[255]',
        ]);

        if (! $validation->run($data)) {
            return $this->validationError(array_values($validation->getErrors()));
        }

        try {
            $pdf = (new IncomingControlSheetPdf())->render($data);
        } catch (\Throwable $error) {
            log_message('error', 'Gagal membuat Lembar Pengendalian Surat Masuk: {message}', ['message' => $error->getMessage()]);

            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Lembar Pengendalian belum dapat dibuat. Silakan coba kembali.',
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        $safeNumber = preg_replace('/[^A-Za-z0-9_-]+/', '-', $data['nomor_surat']) ?: 'surat-masuk';
        $filename   = 'Lembar-Pengendalian-' . trim($safeNumber, '-') . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setHeader('X-CSRF-Name', csrf_token())
            ->setHeader('X-CSRF-Hash', csrf_hash())
            ->setBody($pdf);
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
                'penyerahan_at'       => $agenda['penyerahan_at']
                    ? date('d-m-Y H:i', strtotime($agenda['penyerahan_at'])) . ' WIB'
                    : '',
                'jenis'               => $agenda['jenis'] ?: '-',
                'jenis_value'         => $agenda['jenis'] ?: '',
                'tanggal_diterima'    => date('d-m-Y', strtotime($agenda['tanggal_diterima'])),
                'tanggal_value'       => $agenda['tanggal_diterima'],
                'tanggal_surat'       => $agenda['tanggal_surat'] ? date('d-m-Y', strtotime($agenda['tanggal_surat'])) : 'Belum diisi',
                'tanggal_surat_value' => $agenda['tanggal_surat'] ?: '',
                'nomor_surat'         => $agenda['nomor_surat'] ?: 'Belum diisi',
                'nomor_surat_value'   => $agenda['nomor_surat'] ?: '',
                'nomor_agendaris'     => $agenda['nomor_agendaris'] ?: 'Belum dibuat',
                'nomor_agendaris_value' => $agenda['nomor_agendaris'] ?: '',
                'tanggal_agendaris'   => $agenda['tanggal_agendaris'] ? date('d-m-Y', strtotime($agenda['tanggal_agendaris'])) : 'Belum diisi',
                'tanggal_agendaris_value' => $agenda['tanggal_agendaris'] ?: '',
                'perihal_surat'       => $agenda['perihal_surat'],
                'berkas_link'         => $agenda['berkas_link'] ?: '',
                'disposisi_1'         => $agenda['disposisi_1'] ?: 'Belum diisi',
                'disposisi_1_value'   => $agenda['disposisi_1'] ?: '',
                'disposisi_1_status_value' => $agenda['disposisi_1_status'] ?: 'Menunggu',
                'disposisi_1_waktu_value'  => $this->dateTimeLocalValue($agenda['disposisi_1_waktu'] ?? null),
                'disposisi_1_catatan_value'=> $agenda['disposisi_1_catatan'] ?: '',
                'disposisi_2'         => $agenda['disposisi_2'] ?: 'Belum diisi',
                'disposisi_2_value'   => $agenda['disposisi_2'] ?: '',
                'disposisi_2_status_value' => $agenda['disposisi_2_status'] ?: 'Menunggu',
                'disposisi_2_waktu_value'  => $this->dateTimeLocalValue($agenda['disposisi_2_waktu'] ?? null),
                'disposisi_2_catatan_value'=> $agenda['disposisi_2_catatan'] ?: '',
                'disposisi_3'         => $agenda['disposisi_3'] ?: 'Belum diisi',
                'disposisi_3_value'   => $agenda['disposisi_3'] ?: '',
                'disposisi_3_status_value' => $agenda['disposisi_3_status'] ?: 'Menunggu',
                'disposisi_3_waktu_value'  => $this->dateTimeLocalValue($agenda['disposisi_3_waktu'] ?? null),
                'disposisi_3_catatan_value'=> $agenda['disposisi_3_catatan'] ?: '',
                'disposisi_timeline'  => $this->dispositionTimeline($agenda),
                'progres'             => $agenda['progres'] ?: 'Menunggu Penyelesaian',
                'created_at'          => date('d-m-Y H:i', strtotime($agenda['created_at'])) . ' WIB',
                'updated_at'          => date('d-m-Y H:i', strtotime($agenda['updated_at'])) . ' WIB',
                'update_url'          => site_url("agendaris/progres-dokumen-masuk/{$id}"),
                'delete_url'          => site_url("agendaris/progres-dokumen-masuk/{$id}/hapus"),
            ],
        ]);
    }

    public function update(int $id): ResponseInterface
    {
        $agenda = $this->findJoined($id);
        $data   = $this->payload();

        if ($agenda['dokumen_masuk_id'] !== null) {
            // Hanya Pengirim yang boleh diperbarui oleh Agendaris. Field
            // sumber Security lainnya tetap menggunakan nilai asal.
            foreach (['penerima', 'pengambilan', 'jenis', 'tanggal_diterima'] as $lockedField) {
                $data[$lockedField] = $agenda[$lockedField];
            }
        }

        $errors = $this->validateAgenda($data, $id);

        if ($errors !== []) {
            return $this->validationError($errors);
        }

        if (! $this->model->update($id, $data)) {
            return $this->validationError($this->model->errors() ?: ['Data agendaris gagal diperbarui.']);
        }

        return $this->successResponse('Surat masuk Agendaris berhasil diperbarui.');
    }

    public function reopen(int $id): ResponseInterface
    {
        $agenda = $this->findJoined($id);
        if (($agenda['progres'] ?? '') !== 'Selesai') {
            return $this->response->setStatusCode(409)->setJSON([
                'success' => false,
                'message' => 'Dokumen sudah berada di Progres Dokumen.',
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        if (! $this->model->update($id, ['progres' => 'Menunggu Penyelesaian'])) {
            return $this->validationError($this->model->errors() ?: ['Dokumen belum dapat dikembalikan ke progres.']);
        }

        $message = 'Dokumen Masuk berhasil dikembalikan ke Progres Dokumen dan telah keluar dari arsip.';
        session()->setFlashdata('success', $message);

        return $this->successResponse($message, [
            'redirect_url' => site_url('agendaris/surat-masuk'),
        ]);
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
        $data = [
            'pengirim'         => trim((string) $this->request->getPost('pengirim')),
            'penerima'         => trim((string) $this->request->getPost('penerima')),
            'pengambilan'      => trim((string) $this->request->getPost('pengambilan')),
            'jenis'            => trim((string) $this->request->getPost('jenis')),
            'tanggal_diterima' => trim((string) $this->request->getPost('tanggal_diterima')),
            'tanggal_surat'    => trim((string) $this->request->getPost('tanggal_surat')),
            'nomor_surat'      => trim((string) $this->request->getPost('nomor_surat')),
            'nomor_agendaris'  => $this->nullIfEmpty($this->request->getPost('nomor_agendaris')),
            'tanggal_agendaris'=> $this->nullIfEmpty($this->request->getPost('tanggal_agendaris')),
            'perihal_surat'    => trim((string) $this->request->getPost('perihal_surat')),
            'berkas_link'      => trim((string) $this->request->getPost('berkas_link')),
            'progres'          => trim((string) $this->request->getPost('progres')),
        ];

        for ($step = 1; $step <= 3; $step++) {
            $recipient = $this->nullIfEmpty($this->request->getPost("disposisi_{$step}"));
            $data["disposisi_{$step}"] = $recipient;

            if ($recipient === null) {
                $data["disposisi_{$step}_status"] = null;
                $data["disposisi_{$step}_waktu"] = null;
                $data["disposisi_{$step}_catatan"] = null;
                continue;
            }

            $data["disposisi_{$step}_status"] = $this->nullIfEmpty($this->request->getPost("disposisi_{$step}_status")) ?? 'Menunggu';
            $data["disposisi_{$step}_waktu"] = $this->normalizeDateTime($this->request->getPost("disposisi_{$step}_waktu")) ?? date('Y-m-d H:i:s');
            $data["disposisi_{$step}_catatan"] = $this->nullIfEmpty($this->request->getPost("disposisi_{$step}_catatan"));
        }

        return $data;
    }

    private function validateAgenda(array $data, ?int $ignoreId = null): array
    {
        $validation = service('validation')->setRules([
            'pengirim'         => 'required|max_length[255]',
            'penerima'         => 'permit_empty|max_length[255]',
            'pengambilan'      => 'permit_empty|max_length[255]',
            'jenis'            => 'required|max_length[100]',
            'tanggal_diterima' => 'required|valid_date[Y-m-d]',
            'tanggal_surat'    => 'required|valid_date[Y-m-d]',
            'nomor_surat'      => 'required|max_length[150]',
            'nomor_agendaris'  => 'permit_empty|max_length[50]',
            'tanggal_agendaris'=> 'permit_empty|valid_date[Y-m-d]',
            'perihal_surat'    => 'required|max_length[255]',
            'berkas_link'      => 'permit_empty|max_length[2048]',
            'disposisi_1'      => 'permit_empty|max_length[255]',
            'disposisi_1_status' => 'permit_empty|in_list[Menunggu,Diterima,Diproses,Diteruskan,Selesai]',
            'disposisi_1_waktu'  => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'disposisi_1_catatan'=> 'permit_empty|max_length[1000]',
            'disposisi_2'      => 'permit_empty|max_length[255]',
            'disposisi_2_status' => 'permit_empty|in_list[Menunggu,Diterima,Diproses,Diteruskan,Selesai]',
            'disposisi_2_waktu'  => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'disposisi_2_catatan'=> 'permit_empty|max_length[1000]',
            'disposisi_3'      => 'permit_empty|max_length[255]',
            'disposisi_3_status' => 'permit_empty|in_list[Menunggu,Diterima,Diproses,Diteruskan,Selesai]',
            'disposisi_3_waktu'  => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'disposisi_3_catatan'=> 'permit_empty|max_length[1000]',
            'progres'          => 'required|in_list[Menunggu Penyelesaian,Selesai]',
        ]);

        if (! $validation->run($data)) {
            return array_values($validation->getErrors());
        }

        if ($data['disposisi_2'] !== null && $data['disposisi_1'] === null) {
            return ['Disposisi 1 harus diisi sebelum Disposisi 2.'];
        }

        if ($data['disposisi_3'] !== null && $data['disposisi_2'] === null) {
            return ['Disposisi 2 harus diisi sebelum Disposisi 3.'];
        }

        if ($data['nomor_agendaris'] !== null) {
            if (preg_match('/^AGD\/KW\/VI\/[0-9]{3,}$/', $data['nomor_agendaris']) !== 1) {
                return ['Nomor Agendaris harus menggunakan format AGD/KW/VI/001.'];
            }

            $duplicate = db_connect()->table('agendaris')
                ->where('nomor_agendaris', $data['nomor_agendaris']);
            if ($ignoreId !== null) {
                $duplicate->where('id !=', $ignoreId);
            }
            if ($duplicate->countAllResults() > 0) {
                return ['Nomor Agendaris sudah digunakan. Silakan generate nomor baru.'];
            }
        }

        if ($data['berkas_link'] !== '') {
            $scheme = strtolower((string) parse_url($data['berkas_link'], PHP_URL_SCHEME));
            if ($scheme !== 'https' || filter_var($data['berkas_link'], FILTER_VALIDATE_URL) === false) {
                return ['Link berkas harus berupa URL HTTPS yang valid.'];
            }
        }

        return [];
    }

    private function nullIfEmpty(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeDateTime(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $dateOnly = \DateTime::createFromFormat('!Y-m-d', $value);
        if ($dateOnly !== false && $dateOnly->format('Y-m-d') === $value) {
            return $dateOnly->format('Y-m-d H:i:s');
        }

        foreach (['Y-m-d\\TH:i', 'Y-m-d H:i:s'] as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date !== false && $date->format($format) === $value) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        return $value;
    }

    private function dateTimeLocalValue(?string $value): string
    {
        return $value ? date('Y-m-d', strtotime($value)) : '';
    }

    /**
     * @return array<int, array<string, int|string|bool>>
     */
    private function dispositionTimeline(array $agenda): array
    {
        $timeline = [];

        for ($step = 1; $step <= 3; $step++) {
            $recipient = trim((string) ($agenda["disposisi_{$step}"] ?? ''));
            $time = $agenda["disposisi_{$step}_waktu"] ?? null;

            $timeline[] = [
                'urutan'       => $step,
                'terisi'       => $recipient !== '',
                'penerima'     => $recipient !== '' ? $recipient : 'Belum ditentukan',
                'status'       => $recipient !== '' ? ($agenda["disposisi_{$step}_status"] ?: 'Menunggu') : 'Belum ditentukan',
                'waktu'        => $time ? date('d-m-Y', strtotime($time)) : 'Tanggal belum dicatat',
                'catatan'      => ($agenda["disposisi_{$step}_catatan"] ?? null) ?: 'Belum ada catatan',
            ];
        }

        return $timeline;
    }

    private function findJoined(int $id): array
    {
        $row = db_connect()->table('agendaris')
            ->select('agendaris.*, dokumen_masuk.penyerahan_at')
            ->join('dokumen_masuk', 'dokumen_masuk.id = agendaris.dokumen_masuk_id', 'left')
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
