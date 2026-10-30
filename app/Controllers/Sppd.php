<?php

namespace App\Controllers;

use App\Models\SppdModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

final class Sppd extends BaseController
{
    private const PER_PAGE_OPTIONS = [10, 20, 50, 100];

    private SppdModel $records;

    public function __construct()
    {
        $this->records = new SppdModel();
    }

    public function index(): string
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $tanggalSppd = $this->dateValue($this->request->getGet('tanggal_sppd'));
        $tujuan = trim((string) $this->request->getGet('tujuan'));
        $perPage = (int) $this->request->getGet('per_page');
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = 10;
        }

        $model = new SppdModel();
        if ($keyword !== '') {
            $model->groupStart()
                ->like('nomor_sppd', $keyword)
                ->orLike('nama', $keyword)
                ->orLike('jabatan', $keyword)
                ->orLike('tujuan', $keyword)
                ->orLike('keperluan', $keyword)
                ->groupEnd();
        }
        if ($tanggalSppd !== null) {
            $model->where('tanggal_sppd', $tanggalSppd);
        }
        if ($tujuan !== '') {
            $model->where('tujuan', $tujuan);
        }

        $destinations = (new SppdModel())
            ->select('tujuan')
            ->distinct()
            ->orderBy('tujuan', 'ASC')
            ->findColumn('tujuan') ?: [];

        return view('sdm/sppd', [
            'title' => 'SPPD | SDM & Teller',
            'records' => $model->orderBy('tanggal_sppd', 'DESC')->orderBy('id', 'DESC')->paginate($perPage, 'sppd'),
            'pager' => $model->pager,
            'total' => (new SppdModel())->countAllResults(),
            'destinations' => $destinations,
            'filters' => ['keyword' => $keyword, 'tanggalSppd' => $tanggalSppd ?? '', 'tujuan' => $tujuan, 'perPage' => $perPage],
        ]);
    }

    public function store(): RedirectResponse
    {
        return $this->save();
    }

    public function update(int $id): RedirectResponse
    {
        return $this->save($this->findRecord($id));
    }

    public function destroy(int $id): RedirectResponse
    {
        $record = $this->findRecord($id);
        $this->records->update($id, [
            'deleted_by_role' => (string) session()->get('auth_role') ?: null,
            'deleted_by_name' => trim((string) session()->get('auth_display_name')) ?: null,
        ]);

        if (! $this->records->delete($id)) {
            return $this->returnToList()->with('error', 'Data SPPD belum dapat dihapus.');
        }

        return $this->returnToList()->with('success', 'SPPD ' . $record['nomor_sppd'] . ' berhasil dihapus.');
    }

    private function save(?array $existing = null): RedirectResponse
    {
        $data = $this->payload();
        $errors = $this->validatePayload($data, $existing['id'] ?? null);
        if ($errors !== []) {
            return $this->popupError($existing['id'] ?? null, $data, $errors);
        }

        $data['total_hari'] = $this->totalDays($data['tanggal_berangkat'], $data['tanggal_kembali']);
        if ($existing === null) {
            $data['created_by'] = (int) session()->get('auth_user_id') ?: null;
            $data['created_by_name'] = trim((string) session()->get('auth_display_name')) ?: null;
            $saved = $this->records->insert($data, true);
        } else {
            $saved = $this->records->update((int) $existing['id'], $data);
        }

        if (! $saved) {
            return $this->popupError($existing['id'] ?? null, $data, ['Data SPPD belum dapat disimpan.']);
        }

        return $this->returnToList()->with('success', $existing === null ? 'Data SPPD berhasil ditambahkan.' : 'Data SPPD berhasil diperbarui.');
    }

    /** @return array<string, string> */
    private function validatePayload(array $data, ?int $id): array
    {
        $errors = [];
        foreach (['nomor_sppd' => 'SPPD', 'nama' => 'Nama', 'jabatan' => 'Jabatan', 'tujuan' => 'Tujuan', 'keperluan' => 'Keperluan'] as $field => $label) {
            if ($data[$field] === '') {
                $errors[$field] = $label . ' wajib diisi.';
            }
        }
        if (mb_strlen($data['nomor_sppd']) > 100) $errors['nomor_sppd'] = 'SPPD maksimal 100 karakter.';
        if (mb_strlen($data['nama']) > 150) $errors['nama'] = 'Nama maksimal 150 karakter.';
        if (mb_strlen($data['jabatan']) > 150) $errors['jabatan'] = 'Jabatan maksimal 150 karakter.';
        if (mb_strlen($data['tujuan']) > 200) $errors['tujuan'] = 'Tujuan maksimal 200 karakter.';
        if (mb_strlen($data['keperluan']) > 500) $errors['keperluan'] = 'Keperluan maksimal 500 karakter.';
        foreach (['tanggal_sppd' => 'Tgl SPPD', 'tanggal_berangkat' => 'Tgl berangkat', 'tanggal_kembali' => 'Tgl kembali'] as $field => $label) {
            if ($data[$field] === null) $errors[$field] = $label . ' wajib diisi.';
        }
        if ($data['tanggal_berangkat'] !== null && $data['tanggal_kembali'] !== null && $data['tanggal_kembali'] < $data['tanggal_berangkat']) {
            $errors['tanggal_kembali'] = 'Tgl kembali harus sama atau setelah tgl berangkat.';
        }
        if ($data['nomor_sppd'] !== '') {
            $duplicate = (new SppdModel())->where('nomor_sppd', $data['nomor_sppd']);
            if ($id !== null) $duplicate->where('id !=', $id);
            if ($duplicate->countAllResults() > 0) $errors['nomor_sppd'] = 'SPPD sudah digunakan.';
        }

        return $errors;
    }

    /** @return array<string, string|null> */
    private function payload(): array
    {
        return [
            'nomor_sppd' => $this->textValue('nomor_sppd'),
            'tanggal_sppd' => $this->dateValue($this->request->getPost('tanggal_sppd')),
            'nama' => $this->textValue('nama'),
            'jabatan' => $this->textValue('jabatan'),
            'tujuan' => $this->textValue('tujuan'),
            'keperluan' => $this->textValue('keperluan'),
            'tanggal_berangkat' => $this->dateValue($this->request->getPost('tanggal_berangkat')),
            'tanggal_kembali' => $this->dateValue($this->request->getPost('tanggal_kembali')),
        ];
    }

    private function textValue(string $field): string
    {
        return trim((string) $this->request->getPost($field));
    }

    private function dateValue(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return null;
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
    }

    private function totalDays(string $startDate, string $endDate): int
    {
        return (int) (new \DateTimeImmutable($startDate))->diff(new \DateTimeImmutable($endDate))->days + 1;
    }

    private function findRecord(int $id): array
    {
        $record = $this->records->find($id);
        if ($record === null) throw PageNotFoundException::forPageNotFound('Data SPPD tidak ditemukan.');
        return $record;
    }

    private function popupError(?int $id, array $values, array $errors): RedirectResponse
    {
        return $this->returnToList()->with('sppd_popup', ['id' => $id, 'values' => $values, 'errors' => array_values($errors)]);
    }

    private function returnToList(): RedirectResponse
    {
        $base = site_url('sdm/sppd');
        $returnTo = trim((string) $this->request->getPost('return_to'));
        return redirect()->to($returnTo !== '' && str_starts_with($returnTo, $base) ? $returnTo : $base);
    }
}
