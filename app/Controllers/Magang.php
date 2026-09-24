<?php

namespace App\Controllers;

use App\Libraries\MagangWorkbookParser;
use App\Models\MagangModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;

final class Magang extends BaseController
{
    private const STATUSES = ['Aktif', 'Selesai', 'Belum Mulai', 'Belum Lengkap'];
    private const UNIT_OPTIONS = [
        'Kanwil Surabaya',
        'Cabang Surabaya',
        'Cabang Madiun',
        'Cabang Kediri',
        'Cabang Malang',
        'Cabang Banyuwangi',
        'KUP Bojonegoro',
        'KUP Pamekasan',
        'KUP Jember',
    ];
    private const TYPE_OPTIONS = [
        'Magang Administrasi',
        'Magang Kuliah',
        'Magang Kemenaker',
        'Magang Magenta',
    ];

    private MagangModel $records;

    public function __construct()
    {
        $this->records = new MagangModel();
    }

    public function index(): string
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $unit = trim((string) $this->request->getGet('unit_kerja'));
        $status = trim((string) $this->request->getGet('status'));
        $type = trim((string) $this->request->getGet('jenis'));
        $perPage = (int) $this->request->getGet('per_page');
        $order = $this->requestedListOrder();
        $today = date('Y-m-d');

        if (! in_array($status, self::STATUSES, true)) {
            $status = '';
        }
        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 10;
        }

        $model = new MagangModel();
        if ($keyword !== '') {
            $model->groupStart()
                ->like('nama_magang', $keyword)
                ->orLike('nomor_kontrak_kerja', $keyword)
                ->orLike('unit_kerja', $keyword)
                ->orLike('jenis_magang', $keyword)
                ->groupEnd();
        }
        if ($unit !== '') {
            $model->where('unit_kerja', $unit);
        }
        if ($type !== '') {
            $model->where('jenis_magang', $type);
        }
        $this->applyStatusFilter($model, $status, $today);

        if ($order === 'terlama') {
            $model->orderBy('tanggal_mulai', 'ASC')->orderBy('id', 'ASC');
        } else {
            $model->orderBy('tanggal_mulai', 'DESC')->orderBy('id', 'DESC');
        }

        $savedUnits = (new MagangModel())->select('unit_kerja')->distinct()->orderBy('unit_kerja', 'ASC')->findColumn('unit_kerja') ?: [];
        $units = array_values(array_unique(array_merge(self::UNIT_OPTIONS, $savedUnits)));
        $savedTypes = (new MagangModel())->select('jenis_magang')->distinct()->orderBy('jenis_magang', 'ASC')->findColumn('jenis_magang') ?: [];
        $types = array_values(array_unique(array_merge(self::TYPE_OPTIONS, $savedTypes)));
        $total = (new MagangModel())->countAllResults();

        return view('sdm/data_magang', [
            'title' => 'Data Magang | SDM & Teller',
            'records' => $this->decorate($model->paginate($perPage, 'data_magang'), $today),
            'pager' => $model->pager,
            'total' => $total,
            'units' => $units,
            'types' => $types,
            'nextContractSequence' => $this->nextContractSequence(),
            'filters' => compact('keyword', 'unit', 'status', 'type', 'perPage', 'order'),
        ]);
    }

    public function store(): RedirectResponse
    {
        $data = $this->payload();
        $errors = $this->validatePayload($data, null, true);
        if ($errors !== []) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $database = db_connect();
        $database->transBegin();
        try {
            $data['nomor'] = $this->nextRecordNumber();
            $data['nomor_kontrak_kerja'] = $this->generateContractNumber((string) $data['tanggal_mulai']);
            $errors = $this->validatePayload($data);
            if ($errors !== []) {
                throw new RuntimeException('Nomor kontrak kerja belum dapat dibuat.');
            }

            $data += $this->actorData();
            if (! $this->records->insert($data, true)) {
                throw new RuntimeException('Data magang belum berhasil disimpan.');
            }
            $database->transCommit();
        } catch (\Throwable $exception) {
            $database->transRollback();
            log_message('error', 'Simpan data magang gagal: {message}', ['message' => $exception->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'Data magang belum berhasil disimpan. Silakan coba kembali.');
        }

        return $this->returnToList()->with('success', 'Data peserta magang berhasil ditambahkan.');
    }

    public function update(int $id): RedirectResponse
    {
        $record = $this->findRecord($id);
        $data = $this->payload();
        $data['nomor'] = $record['nomor'] !== null ? (int) $record['nomor'] : null;
        $data['nomor_kontrak_kerja'] = (string) $record['nomor_kontrak_kerja'];
        $errors = $this->validatePayload($data, $id);
        if ($errors !== []) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }
        if (! $this->records->update($id, $data)) {
            return redirect()->back()->withInput()->with('error', 'Data magang belum berhasil diperbarui.');
        }

        return $this->returnToList()->with('success', 'Data peserta magang berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $record = $this->findRecord($id);
        if (! $this->deleteRecord($this->records, 'sdm_magang', $id)) {
            return $this->returnToList()->with('error', 'Data magang belum berhasil dihapus.');
        }

        return $this->returnToList()->with('success', 'Data ' . $record['nama_magang'] . ' berhasil dihapus.');
    }

    public function import(): RedirectResponse
    {
        $file = $this->request->getFile('magang_excel');
        if ($file === null || ! $file->isValid() || $file->hasMoved() || strtolower($file->getClientExtension()) !== 'xlsx' || $file->getSize() > 5 * 1024 * 1024) {
            return $this->returnToList()->with('error', 'Pilih Excel Database Magang berformat .xlsx dengan ukuran maksimal 5 MB.');
        }

        try {
            $rows = (new MagangWorkbookParser())->parse($file->getTempName());
            $db = db_connect();
            $db->transStart();
            $added = 0;
            $updated = 0;
            foreach ($rows as $row) {
                $existing = $db->table('sdm_magang')->where('nomor_kontrak_kerja', $row['nomor_kontrak_kerja'])->get()->getRowArray();
                $data = $row + $this->actorData();
                if ($existing === null) {
                    $this->records->insert($data, true);
                    $added++;
                } else {
                    $data['deleted_at'] = null;
                    $db->table('sdm_magang')->where('id', $existing['id'])->update($data);
                    $updated++;
                }
            }
            $db->transComplete();
            if (! $db->transStatus()) {
                throw new RuntimeException('Data tidak dapat disimpan ke database.');
            }

            return $this->returnToList()->with('success', count($rows) . " data berhasil diimpor ({$added} baru, {$updated} diperbarui).");
        } catch (RuntimeException $exception) {
            return $this->returnToList()->with('error', $exception->getMessage());
        }
    }

    /** @return array<string, string|int|null> */
    private function payload(): array
    {
        return [
            'nomor' => $this->nullableInteger($this->request->getPost('nomor')),
            'nama_magang' => $this->clean($this->request->getPost('nama_magang')),
            'nomor_kontrak_kerja' => $this->clean($this->request->getPost('nomor_kontrak_kerja')),
            'unit_kerja' => $this->clean($this->request->getPost('unit_kerja')),
            'jenis_magang' => $this->clean($this->request->getPost('jenis_magang')),
            'tanggal_mulai' => $this->nullIfEmpty($this->request->getPost('tanggal_mulai')),
            'tanggal_selesai' => $this->nullIfEmpty($this->request->getPost('tanggal_selesai')),
            'link_pkk' => $this->nullIfEmpty($this->request->getPost('link_pkk')),
            'keterangan' => $this->nullIfEmpty($this->request->getPost('keterangan')),
        ];
    }

    /** @param array<string, string|int|null> $data
     * @return array<string, string> */
    private function validatePayload(array $data, ?int $id = null, bool $automaticContract = false): array
    {
        $unique = 'is_unique[sdm_magang.nomor_kontrak_kerja' . ($id === null ? ']' : ',id,' . $id . ']');
        $validation = service('validation')->setRules([
            'nomor' => ['label' => 'Nomor', 'rules' => 'permit_empty|integer|greater_than[0]'],
            'nama_magang' => ['label' => 'Nama peserta', 'rules' => 'required|max_length[200]'],
            'nomor_kontrak_kerja' => ['label' => 'Nomor kontrak kerja', 'rules' => ($automaticContract ? 'permit_empty' : 'required') . '|max_length[200]|' . $unique],
            'unit_kerja' => ['label' => 'Unit kerja', 'rules' => 'required|max_length[150]'],
            'jenis_magang' => ['label' => 'Jenis magang', 'rules' => 'required|max_length[100]'],
            'tanggal_mulai' => ['label' => 'Awal magang', 'rules' => ($automaticContract ? 'required' : 'permit_empty') . '|valid_date[Y-m-d]'],
            'tanggal_selesai' => ['label' => 'Selesai magang', 'rules' => 'permit_empty|valid_date[Y-m-d]'],
            'link_pkk' => ['label' => 'Link PKK', 'rules' => 'permit_empty|max_length[2048]|valid_url'],
            'keterangan' => ['label' => 'Keterangan', 'rules' => 'permit_empty|max_length[500]'],
        ]);
        if (! $validation->run($data)) {
            return $validation->getErrors();
        }
        if ($data['tanggal_mulai'] !== null && $data['tanggal_selesai'] !== null && $data['tanggal_mulai'] > $data['tanggal_selesai']) {
            return ['tanggal_selesai' => 'Tanggal selesai magang harus sama atau setelah tanggal awal.'];
        }

        return [];
    }

    private function nextContractSequence(): int
    {
        $row = db_connect()->query(
            "SELECT MAX(CAST(SUBSTRING_INDEX(nomor_kontrak_kerja, '/', 1) AS UNSIGNED)) AS last_sequence FROM sdm_magang"
        )->getRowArray();

        return max(1, ((int) ($row['last_sequence'] ?? 0)) + 1);
    }

    private function nextRecordNumber(): int
    {
        $row = db_connect()->query('SELECT MAX(nomor) AS last_number FROM sdm_magang')->getRowArray();

        return max(1, ((int) ($row['last_number'] ?? 0)) + 1);
    }

    private function generateContractNumber(string $startDate): string
    {
        $date = new \DateTimeImmutable($startDate);
        $romanMonths = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];
        $sequence = $this->nextContractSequence();

        return sprintf('%03d/PKKM/KW.6/%s/%s', $sequence, $romanMonths[(int) $date->format('n')], $date->format('Y'));
    }

    private function applyStatusFilter(MagangModel $model, string $status, string $today): void
    {
        if ($status === 'Belum Lengkap') {
            $model->groupStart()->where('tanggal_mulai IS NULL', null, false)->orWhere('tanggal_selesai IS NULL', null, false)->groupEnd();
        } elseif ($status === 'Belum Mulai') {
            $model->where('tanggal_mulai >', $today)->where('tanggal_selesai IS NOT NULL', null, false);
        } elseif ($status === 'Aktif') {
            $model->where('tanggal_mulai <=', $today)->where('tanggal_selesai >=', $today);
        } elseif ($status === 'Selesai') {
            $model->where('tanggal_selesai <', $today)->where('tanggal_mulai IS NOT NULL', null, false);
        }
    }

    /** @param list<array<string, mixed>> $records
     * @return list<array<string, mixed>> */
    private function decorate(array $records, string $today): array
    {
        foreach ($records as &$record) {
            $record['status'] = $this->status($record, $today);
            $record['status_class'] = match ($record['status']) {
                'Aktif' => 'active', 'Selesai' => 'completed', 'Belum Mulai' => 'pending', default => 'incomplete',
            };
        }
        unset($record);

        return $records;
    }

    /** @param array<string, mixed> $record */
    private function status(array $record, string $today): string
    {
        if (empty($record['tanggal_mulai']) || empty($record['tanggal_selesai'])) {
            return 'Belum Lengkap';
        }
        if ($record['tanggal_mulai'] > $today) {
            return 'Belum Mulai';
        }

        return $record['tanggal_selesai'] < $today ? 'Selesai' : 'Aktif';
    }

    /** @return array<string, int|string> */
    private function actorData(): array
    {
        return [
            'created_by' => (int) session()->get('auth_user_id') ?: null,
            'created_by_name' => trim((string) session()->get('auth_display_name')) ?: null,
        ];
    }

    private function findRecord(int $id): array
    {
        $record = $this->records->find($id);
        if ($record === null) {
            throw PageNotFoundException::forPageNotFound('Data magang tidak ditemukan.');
        }

        return $record;
    }

    private function returnToList(): RedirectResponse
    {
        $returnTo = trim((string) $this->request->getPost('return_to'));
        $base = site_url('sdm/data-magang');
        if ($returnTo !== '' && str_starts_with($returnTo, $base)) {
            return redirect()->to($returnTo);
        }

        return redirect()->to($base);
    }

    private function clean(mixed $value): string
    {
        return trim((string) $value);
    }

    private function nullIfEmpty(mixed $value): ?string
    {
        $value = $this->clean($value);

        return $value === '' ? null : $value;
    }

    private function nullableInteger(mixed $value): ?int
    {
        $value = $this->nullIfEmpty($value);

        return $value === null ? null : (int) $value;
    }
}
