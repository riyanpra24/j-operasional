<?php

namespace App\Controllers;

use App\Models\BuktiBayarModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

final class BuktiBayar extends BaseController
{
    private BuktiBayarModel $model;

    public function __construct()
    {
        $this->model = new BuktiBayarModel();
    }

    public function index(): string
    {
        $filters = [
            'tanggal_dari' => $this->dateFilter((string) $this->request->getGet('tanggal_dari')),
            'tanggal_sampai' => $this->dateFilter((string) $this->request->getGet('tanggal_sampai')),
            'nomor_voucher' => trim((string) $this->request->getGet('nomor_voucher')),
            'nomor_memo' => trim((string) $this->request->getGet('nomor_memo')),
            'nominal' => trim((string) $this->request->getGet('nominal')),
        ];
        $filterError = null;

        if ($filters['tanggal_dari'] !== '') {
            $this->model->where('tanggal >=', $filters['tanggal_dari']);
        }
        if ($filters['tanggal_sampai'] !== '') {
            $this->model->where('tanggal <=', $filters['tanggal_sampai']);
        }
        if ($filters['nomor_voucher'] !== '') {
            $this->model->like('nomor_voucher', $filters['nomor_voucher']);
        }
        if ($filters['nomor_memo'] !== '') {
            $this->model->like('nomor_memo', $filters['nomor_memo']);
        }
        if ($filters['nominal'] !== '') {
            $nominal = $this->normalizeNominal($filters['nominal']);
            if ($nominal === null) {
                $filterError = 'Nominal filter harus berupa angka yang valid.';
            } else {
                $this->model->where('nominal', $nominal);
            }
        }

        $records = $this->model
            ->orderBy('tanggal', 'DESC')
            ->orderBy('id', 'DESC')
            ->paginate(20, 'bukti_bayar');

        return view('akutansi/bukti_bayar', [
            'title' => 'Bukti Bayar',
            'records' => $records,
            'pager' => $this->model->pager,
            'filters' => $filters,
            'filterError' => $filterError,
        ]);
    }

    public function show(int $id): ResponseInterface
    {
        $record = $this->model->find($id);
        if ($record === null) {
            throw PageNotFoundException::forPageNotFound('Bukti bayar tidak ditemukan.');
        }
        $record['berkas_url'] = $record['berkas_url'] ?: (! empty($record['berkas_path'])
            ? site_url('akutansi/bukti-bayar/' . $id . '/berkas')
            : null);

        return $this->response->setJSON([
            'success' => true,
            'record' => $record,
        ]);
    }

    public function store(): RedirectResponse
    {
        try {
            $payload = $this->payload();
            $this->validatePayload($payload);
            $payload['created_by'] = (int) session()->get('auth_user_id') ?: null;
            $payload['created_by_name'] = (string) session()->get('auth_display_name') ?: null;

            if ($this->model->insert($payload, true) === false) {
                throw new RuntimeException('Bukti bayar belum dapat disimpan.');
            }

            return redirect()->to($this->listUrlFromPost())->with('success', 'Bukti bayar berhasil ditambahkan.');
        } catch (Throwable $exception) {
            return redirect()->to($this->listUrlFromPost())->withInput()
                ->with('bukti_bayar_error', $this->knownMessage($exception, 'Bukti bayar belum dapat disimpan.'));
        }
    }

    public function update(int $id): RedirectResponse
    {
        try {
            $existing = $this->model->find($id);
            if ($existing === null) {
                throw PageNotFoundException::forPageNotFound('Bukti bayar tidak ditemukan.');
            }
            $payload = $this->payload();
            $this->validatePayload($payload);
            if (! $this->model->update($id, $payload)) {
                throw new RuntimeException('Bukti bayar belum dapat diperbarui.');
            }

            return redirect()->to($this->listUrlFromPost())->with('success', 'Bukti bayar berhasil diperbarui.');
        } catch (Throwable $exception) {
            return redirect()->to($this->listUrlFromPost())->withInput()
                ->with('bukti_bayar_error', $this->knownMessage($exception, 'Bukti bayar belum dapat diperbarui.'));
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            if ($this->request->getPost('confirm_delete') !== '1') {
                throw new RuntimeException('Konfirmasi penghapusan bukti bayar terlebih dahulu.');
            }
            if ($this->model->find($id) === null) {
                throw PageNotFoundException::forPageNotFound('Bukti bayar tidak ditemukan.');
            }
            if (! $this->deleteRecord($this->model, 'accounting_bukti_bayar', $id)) {
                throw new RuntimeException('Bukti bayar belum dapat dihapus.');
            }

            return redirect()->to($this->listUrlFromPost())->with('success', 'Bukti bayar berhasil dihapus.');
        } catch (Throwable $exception) {
            return redirect()->to($this->listUrlFromPost())
                ->with('bukti_bayar_error', $this->knownMessage($exception, 'Bukti bayar belum dapat dihapus.'));
        }
    }

    public function downloadBerkas(int $id): ResponseInterface
    {
        $record = $this->model->find($id);
        if ($record === null || empty($record['berkas_path'])) {
            throw PageNotFoundException::forPageNotFound('Berkas bukti bayar tidak ditemukan.');
        }

        $path = WRITEPATH . ltrim((string) $record['berkas_path'], '/\\');
        if (! is_file($path)) {
            throw PageNotFoundException::forPageNotFound('Berkas bukti bayar tidak tersedia.');
        }

        $filename = (string) ($record['berkas_nama'] ?: basename($path));
        return $this->response->download($path, null)->setFileName($filename);
    }

    /** @return array{nomor_memo:string,nomor_voucher:string,tanggal:string,nominal:string,kategori:?string,berkas_url:?string} */
    private function payload(): array
    {
        $kategori = trim((string) $this->request->getPost('kategori'));
        $berkasUrl = trim((string) $this->request->getPost('berkas_url'));

        return [
            'nomor_memo' => trim((string) $this->request->getPost('nomor_memo')),
            'nomor_voucher' => trim((string) $this->request->getPost('nomor_voucher')),
            'tanggal' => trim((string) $this->request->getPost('tanggal')),
            'nominal' => (string) $this->normalizeNominal((string) $this->request->getPost('nominal')),
            'kategori' => $kategori === '' ? null : $kategori,
            'berkas_url' => $berkasUrl === '' ? null : $berkasUrl,
        ];
    }

    /** @param array{nomor_memo:string,nomor_voucher:string,tanggal:string,nominal:string,kategori:?string,berkas_url:?string} $payload */
    private function validatePayload(array $payload): void
    {
        $errors = [];
        if ($payload['nomor_memo'] === '' || mb_strlen($payload['nomor_memo']) > 120) {
            $errors[] = 'Nomor memo wajib diisi dan maksimal 120 karakter.';
        }
        if ($payload['nomor_voucher'] === '' || mb_strlen($payload['nomor_voucher']) > 120) {
            $errors[] = 'Nomor voucher wajib diisi dan maksimal 120 karakter.';
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $payload['tanggal']);
        if ($date === false || $date->format('Y-m-d') !== $payload['tanggal']) {
            $errors[] = 'Tanggal bukti bayar belum valid.';
        }
        if ($payload['nominal'] === '') {
            $errors[] = 'Nominal wajib diisi dalam format angka.';
        }
        if ($payload['kategori'] !== null && mb_strlen($payload['kategori']) > 150) {
            $errors[] = 'Kategori maksimal 150 karakter.';
        }
        if ($payload['kategori'] !== null && ! in_array($payload['kategori'], ['Voucher Bank', 'Voucher Kas'], true)) {
            $errors[] = 'Kategori bukti bayar tidak valid.';
        }
        if ($payload['berkas_url'] !== null && (! filter_var($payload['berkas_url'], FILTER_VALIDATE_URL) || mb_strlen($payload['berkas_url']) > 2048)) {
            $errors[] = 'Tautan berkas harus berupa URL yang valid.';
        }
        if ($errors !== []) {
            throw new RuntimeException(implode(' ', $errors));
        }
    }

    private function dateFilter(string $value): string
    {
        $value = trim($value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value ? $value : '';
    }

    private function normalizeNominal(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $value = preg_replace('/[^0-9,.]/', '', $value) ?? '';
        if ($value === '') {
            return null;
        }
        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');
        $lastSeparator = max($lastComma === false ? -1 : $lastComma, $lastDot === false ? -1 : $lastDot);
        $fraction = '';
        if ($lastSeparator >= 0) {
            $candidate = substr($value, $lastSeparator + 1);
            if (strlen($candidate) <= 2) {
                $fraction = $candidate;
                $value = substr($value, 0, $lastSeparator);
            }
        }
        $integer = preg_replace('/[,.]/', '', $value) ?? '';
        if ($integer === '' || strlen($integer) > 18 || ! ctype_digit($integer)) {
            return null;
        }
        $integer = ltrim($integer, '0') ?: '0';
        return $integer . '.' . str_pad($fraction, 2, '0');
    }

    private function listUrlFromPost(): string
    {
        parse_str(ltrim((string) $this->request->getPost('return_query'), '?'), $parameters);
        $allowed = [];
        foreach (['tanggal_dari', 'tanggal_sampai', 'nomor_voucher', 'nomor_memo', 'nominal'] as $key) {
            if (isset($parameters[$key]) && is_scalar($parameters[$key]) && trim((string) $parameters[$key]) !== '') {
                $allowed[$key] = trim((string) $parameters[$key]);
            }
        }
        $query = http_build_query($allowed, '', '&', PHP_QUERY_RFC3986);
        return site_url('akutansi/bukti-bayar') . ($query === '' ? '' : '?' . $query);
    }

    private function knownMessage(Throwable $exception, string $fallback): string
    {
        return $exception instanceof RuntimeException ? $exception->getMessage() : $fallback;
    }
}
