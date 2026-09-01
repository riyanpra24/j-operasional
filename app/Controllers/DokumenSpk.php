<?php

namespace App\Controllers;

use App\Models\DokumenSpkModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class DokumenSpk extends BaseController
{
    private const DOCUMENT_TYPE = 'SPK';

    private DokumenSpkModel $records;

    public function __construct()
    {
        $this->records = new DokumenSpkModel();
    }

    public function index(): string
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $completeness = trim((string) $this->request->getGet('kelengkapan'));
        $perPage = (int) $this->request->getGet('per_page');
        $years = $this->availableYears();
        $yearParam = $this->request->getGet('tahun');

        if (! in_array($completeness, ['lengkap', 'belum_lengkap'], true)) {
            $completeness = '';
        }
        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 10;
        }

        if ($yearParam === 'semua') {
            $year = 0;
        } else {
            $requestedYear = (int) $yearParam;
            $year = in_array($requestedYear, $years, true)
                ? $requestedYear
                : (in_array((int) date('Y'), $years, true) ? (int) date('Y') : ($years[0] ?? (int) date('Y')));
        }

        $totalQuery = (new DokumenSpkModel())->where('jenis_dokumen', self::DOCUMENT_TYPE);
        if ($year > 0) {
            $totalQuery->where('tahun', $year);
        }
        $total = $totalQuery->countAllResults();

        $query = (new DokumenSpkModel())->where('jenis_dokumen', self::DOCUMENT_TYPE);
        if ($year > 0) {
            $query->where('tahun', $year);
        }
        if ($keyword !== '') {
            $query->groupStart()
                ->like('nomor_dokumen', $keyword)
                ->orLike('perihal', $keyword)
                ->groupEnd();
        }
        $this->applyCompletenessFilter($query, $completeness);

        return view('dokumen_spk/index', [
            'title' => 'Dokumen SPK',
            'records' => $this->decorate($query->orderBy('tahun', 'DESC')->orderBy('nomor_urut', 'DESC')->paginate($perPage, 'dokumen_spk')),
            'pager' => $query->pager,
            'total' => $total,
            'filters' => compact('keyword', 'completeness', 'perPage', 'year'),
            'years' => $years,
        ]);
    }

    public function generateNumber(): ResponseInterface
    {
        $year = (int) $this->request->getGet('tahun');
        $id = (int) $this->request->getGet('id');

        if ($year < 2000 || $year > 2100) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Tahun Register harus berada di antara 2000 dan 2100.',
            ]);
        }
        $existing = $id > 0 ? $this->findRecord($id) : null;
        $sequence = $this->resolveSequence($year, $existing);
        $number = $existing !== null && (int) $existing['tahun'] === $year
            ? (string) $existing['nomor_dokumen']
            : $this->generateDocumentNumber((string) $sequence, (string) $year);

        return $this->response->setJSON([
            'success' => true,
            'sequence' => $sequence,
            'number' => $number,
        ]);
    }

    public function store(): ResponseInterface
    {
        $data = $this->payload();
        $errors = $this->validatePayload($data);
        if ($errors !== []) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Periksa kembali data yang dimasukkan.',
                    'errors' => $errors,
                    'csrfToken' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $data += [
            'created_by' => $this->actorId(),
            'created_by_name' => $this->actorName(),
        ];
        $id = $this->records->insert($data, true);
        if (! $id) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Dokumen gagal disimpan.',
                    'csrfToken' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            return redirect()->back()->withInput()->with('error', 'Dokumen gagal disimpan.');
        }

        $message = 'Dokumen ' . $data['nomor_dokumen'] . ' berhasil ditambahkan.';
        if ($this->request->isAJAX()) {
            session()->setFlashdata('success', $message);

            return $this->response->setJSON([
                'success' => true,
                'message' => $message,
                'csrfToken' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        return redirect()->to(site_url('bagian-umum-1/dokumen-spk'))
            ->with('success', $message);
    }

    public function update(int $id): ResponseInterface
    {
        $record = $this->findRecord($id);
        $data = $this->payload($record);
        $errors = $this->validatePayload($data, $id);
        if ($errors !== []) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Periksa kembali data yang dimasukkan.',
                    'errors' => $errors,
                    'csrfToken' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            return redirect()->back()->withInput()->with('errors', $errors);
        }

        if (! $this->records->update($id, $data)) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Perubahan dokumen gagal disimpan.',
                    'csrfToken' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            return redirect()->back()->withInput()->with('error', 'Perubahan dokumen gagal disimpan.');
        }

        $message = 'Dokumen ' . $data['nomor_dokumen'] . ' berhasil diperbarui.';
        if ($this->request->isAJAX()) {
            session()->setFlashdata('success', $message);

            return $this->response->setJSON([
                'success' => true,
                'message' => $message,
                'csrfToken' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        return redirect()->to(site_url('bagian-umum-1/dokumen-spk'))
            ->with('success', $message);
    }

    public function destroy(int $id): RedirectResponse
    {
        $record = $this->findRecord($id);
        if (! $this->deleteRecord($this->records, 'dokumen_spk', $id)) {
            return redirect()->to(site_url('bagian-umum-1/dokumen-spk'))->with('error', 'Dokumen gagal dihapus.');
        }

        return redirect()->to(site_url('bagian-umum-1/dokumen-spk'))
            ->with('success', 'Dokumen ' . $record['nomor_dokumen'] . ($this->currentRoleIsAdmin() ? ' berhasil dihapus permanen.' : ' berhasil dihapus.'));
    }

    private function payload(?array $existing = null): array
    {
        $documentDate = $this->nullIfEmpty($this->request->getPost('tanggal_dokumen'));
        $year = trim((string) $this->request->getPost('tahun'));
        $sequence = $this->resolveSequence((int) $year, $existing);
        $submittedNumber = $this->cleanText($this->request->getPost('nomor_dokumen'));
        if ($existing !== null && (int) $existing['tahun'] === (int) $year) {
            $number = (string) $existing['nomor_dokumen'];
        } elseif ($this->isGeneratedNumberValid($submittedNumber, $sequence, (int) $year)) {
            $number = $submittedNumber;
        } else {
            $number = $this->generateDocumentNumber((string) $sequence, $year);
        }

        return [
            'nomor_urut' => $sequence,
            'jenis_dokumen' => self::DOCUMENT_TYPE,
            'nomor_dokumen' => $number,
            'tanggal_dokumen' => $documentDate,
            'tahun' => $year,
            'perihal' => $this->cleanText($this->request->getPost('perihal')),
            'link_berkas' => $this->nullIfEmpty($this->request->getPost('link_berkas')),
        ];
    }

    private function validatePayload(array $data, ?int $id = null): array
    {
        $uniqueNumber = 'is_unique[dokumen_spk.nomor_dokumen' . ($id === null ? ']' : ',id,' . $id . ']');
        $validation = service('validation');
        $validation->setRules([
            'nomor_urut' => ['label' => 'Nomor urut', 'rules' => 'required|integer|greater_than[0]'],
            'jenis_dokumen' => ['label' => 'Jenis dokumen', 'rules' => 'required|in_list[SPK]'],
            'nomor_dokumen' => ['label' => 'Nomor SPK', 'rules' => 'required|max_length[200]|' . $uniqueNumber],
            'tanggal_dokumen' => ['label' => 'Tanggal dokumen', 'rules' => 'permit_empty|valid_date[Y-m-d]'],
            'tahun' => ['label' => 'Tahun register', 'rules' => 'required|integer|greater_than_equal_to[2000]|less_than_equal_to[2100]'],
            'perihal' => ['label' => 'Perihal', 'rules' => 'required|max_length[5000]'],
            'link_berkas' => ['label' => 'Link berkas', 'rules' => 'permit_empty|max_length[2048]'],
        ]);
        if (! $validation->run($data)) {
            return $validation->getErrors();
        }

        $errors = [];
        $duplicateSequence = (new DokumenSpkModel())
            ->where('tahun', (int) $data['tahun'])
            ->where('nomor_urut', (int) $data['nomor_urut']);
        if ($id !== null) {
            $duplicateSequence->where('id !=', $id);
        }
        if ($duplicateSequence->first() !== null) {
            $errors['nomor_urut'] = 'Nomor urut tersebut sudah digunakan pada tahun yang sama.';
        }
        if ($data['link_berkas'] !== null && ! $this->isValidUrl($data['link_berkas'])) {
            $errors['link_berkas'] = 'Link berkas harus berupa URL http atau https yang valid.';
        }

        return $errors;
    }

    private function applyCompletenessFilter(DokumenSpkModel $query, string $completeness): void
    {
        if ($completeness === 'lengkap') {
            $query->where('tanggal_dokumen IS NOT NULL', null, false)
                ->where('link_berkas IS NOT NULL', null, false)
                ->where('link_berkas !=', '');
        } elseif ($completeness === 'belum_lengkap') {
            $query->groupStart()
                ->where('tanggal_dokumen IS NULL', null, false)
                ->orWhere('link_berkas IS NULL', null, false)
                ->orWhere('link_berkas', '')
                ->groupEnd();
        }
    }

    private function availableYears(): array
    {
        $rows = (new DokumenSpkModel())->select('tahun')->distinct()->orderBy('tahun', 'DESC')->findAll();

        return array_map(static fn (array $row): int => (int) $row['tahun'], $rows);
    }

    private function nextSequence(int $year): int
    {
        $latest = (new DokumenSpkModel())->where('tahun', $year)->orderBy('nomor_urut', 'DESC')->first();

        return $latest === null ? 1 : ((int) $latest['nomor_urut'] + 1);
    }

    private function resolveSequence(int $year, ?array $existing = null): int
    {
        if ($existing !== null && (int) $existing['tahun'] === $year) {
            return (int) $existing['nomor_urut'];
        }

        return $this->nextSequence($year);
    }

    private function generateDocumentNumber(string $sequence, string $year): string
    {
        $romanMonths = [
            1 => 'I', 'II', 'III', 'IV', 'V', 'VI',
            'VII', 'VIII', 'IX', 'X', 'XI', 'XII',
        ];
        $month = (int) date('n');

        $paddedSequence = str_pad((string) max(0, (int) $sequence), 3, '0', STR_PAD_LEFT);

        return $paddedSequence . '/SPK/W.6/' . $romanMonths[$month] . '/' . (int) $year;
    }

    private function isGeneratedNumberValid(string $number, int $sequence, int $year): bool
    {
        $paddedSequence = str_pad((string) max(0, $sequence), 3, '0', STR_PAD_LEFT);
        $romanMonth = '(?:I|II|III|IV|V|VI|VII|VIII|IX|X|XI|XII)';
        $pattern = '#^' . preg_quote($paddedSequence, '#') . '/SPK/W\.6/' . $romanMonth . '/' . $year . '$#';

        return preg_match($pattern, $number) === 1;
    }

    private function decorate(array $records): array
    {
        return array_map(fn (array $record): array => $this->decorateRecord($record), $records);
    }

    private function decorateRecord(array $record): array
    {
        $complete = ! empty($record['tanggal_dokumen']) && ! empty($record['link_berkas']);
        $record['kelengkapan_key'] = $complete ? 'lengkap' : 'belum_lengkap';
        $record['kelengkapan_label'] = $complete ? 'Lengkap' : 'Belum Lengkap';
        $record['kelengkapan_class'] = $complete ? 'complete' : 'incomplete';
        $record['tanggal_label'] = $record['tanggal_dokumen'] ? date('d-m-Y', strtotime($record['tanggal_dokumen'])) : 'Belum diisi';
        $record['updated_label'] = $record['updated_at'] ? date('d-m-Y H:i', strtotime($record['updated_at'])) . ' WIB' : '-';

        return $record;
    }

    private function findRecord(int $id): array
    {
        $record = $this->records->find($id);
        if ($record === null) {
            throw PageNotFoundException::forPageNotFound('Dokumen SPK tidak ditemukan.');
        }

        return $record;
    }

    private function isValidUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    private function cleanText($value): string
    {
        $value = str_replace("\u{00A0}", ' ', (string) $value);

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private function nullIfEmpty($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function actorId(): ?int
    {
        $id = session()->get('auth_user_id');

        return $id === null ? null : (int) $id;
    }

    private function actorName(): string
    {
        return (string) (session()->get('auth_display_name') ?: session()->get('auth_username') ?: 'Pengguna');
    }
}
