<?php

namespace App\Controllers;

use App\Libraries\AtkPickupProofPdf;
use App\Models\AtkKantorModel;
use App\Models\AtkPengambilanItemModel;
use App\Models\AtkPengambilanModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Disposition;

class AtkKantor extends BaseController
{
    private const URL = 'agendaris/atk-kantor';
    private const UNITS = ['Box', 'Pcs', 'Unit', 'Set', 'Rim', 'Pack', 'Buah', 'Lembar'];

    public function index(): string
    {
        $query = $this->request->getGet('q');
        $stock = $this->request->getGet('stok');
        $perPage = (int) $this->request->getGet('per_page');
        $filters = [
            'q' => is_string($query) ? mb_substr(trim($query), 0, 150) : '',
            'stok' => is_string($stock) && in_array($stock, ['tersedia', 'habis'], true) ? $stock : '',
            'perPage' => in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 10,
        ];
        $model = new AtkKantorModel();
        $model->select('agendaris_atk_kantor.*, COALESCE(pengambilan_terpakai.terpakai, 0) AS terpakai', false)
            ->join('(' . $this->usageQuery()->getCompiledSelect() . ') pengambilan_terpakai', 'pengambilan_terpakai.atk_id = agendaris_atk_kantor.id', 'left', false);
        if ($filters['q'] !== '') {
            $model->groupStart()
                ->like('nama_barang', $filters['q'])
                ->orLike('merek', $filters['q'])
                ->orLike('keterangan', $filters['q'])
                ->groupEnd();
        }
        if ($filters['stok'] === 'tersedia') $model->where('stok_barang >', 0);
        if ($filters['stok'] === 'habis') $model->where('stok_barang', 0);

        return view('agendaris/atk_kantor/index', [
            'title' => 'ATK Kantor',
            'filters' => $filters,
            'units' => self::UNITS,
            'records' => $model->orderBy('nama_barang', 'ASC')->orderBy('id', 'ASC')->paginate($filters['perPage'], 'atk_kantor'),
            'pager' => $model->pager,
            'popupState' => session()->getFlashdata('atk_kantor_popup'),
            'pickupRecords' => $this->pickupRecords(),
            'pickupYears' => $this->pickupYears(),
            'pickupAtks' => (new AtkKantorModel())->where('stok_barang >', 0)->orderBy('nama_barang', 'ASC')->findAll(),
            'pickupRecipients' => Disposition::RECIPIENTS,
            'pickupState' => session()->getFlashdata('atk_pickup_popup'),
            'pickupListOpen' => session()->getFlashdata('atk_pickup_list_open') === true,
        ]);
    }

    public function storePickup(): RedirectResponse
    {
        $name = trim((string) $this->request->getPost('nama_pengambil'));
        $division = '-';
        $purpose = 'Pengambilan ATK';
        $notes = '';
        $date = $this->dateValue($this->request->getPost('tanggal_pengambilan'));
        $items = $this->pickupItems();
        $errors = [];
        if ($name === '' || mb_strlen($name) > 150) $errors[] = 'Nama pengambil wajib diisi dan maksimal 150 karakter.';
        elseif (! in_array($name, Disposition::RECIPIENTS, true)) $errors[] = 'Nama pengambil harus dipilih dari daftar disposisi Agendaris.';
        if ($date === null) $errors[] = 'Tanggal pengambilan wajib diisi.';
        if ($items === []) $errors[] = 'Pilih minimal satu barang dan isi jumlah pengambilan.';
        $values = ['nama_pengambil' => $name, 'tanggal_pengambilan' => (string) $this->request->getPost('tanggal_pengambilan'), 'pickup_items' => $this->submittedPickupItems()];
        if ($errors !== []) return $this->pickupPopupError($values, $errors);

        $db = db_connect();
        try {
            $db->transBegin();
            $model = new AtkPengambilanModel();
            if (! $model->insert(['nama_pengambil' => $name, 'divisi_bagian' => $division, 'keperluan_kegiatan' => $purpose, 'keterangan' => $notes === '' ? null : $notes, 'tanggal_pengambilan' => $date])) throw new \RuntimeException('Data pengambilan ATK belum dapat disimpan.');
            $pickupId = (int) $model->getInsertID();
            foreach ($items as $item) {
                $stockUpdated = $db->table('agendaris_atk_kantor')->set('stok_barang', 'stok_barang - ' . $item['jumlah_diambil'], false)->where('id', $item['atk_id'])->where('stok_barang >=', $item['jumlah_diambil'])->where('deleted_at', null)->update();
                if (! $stockUpdated || $db->affectedRows() !== 1) throw new \RuntimeException('Stok ' . $item['nama_barang'] . ' tidak mencukupi.');
                if (! $db->table('agendaris_atk_pengambilan_item')->insert(['pengambilan_id' => $pickupId, 'atk_id' => $item['atk_id'], 'jumlah_diambil' => $item['jumlah_diambil']])) throw new \RuntimeException('Item pengambilan ATK belum dapat disimpan.');
            }
            if (! $db->transStatus()) throw new \RuntimeException('Pengambilan ATK belum dapat disimpan.');
            $db->transCommit();
        } catch (\Throwable $error) {
            $db->transRollback();
            return $this->pickupPopupError($values, [$error->getMessage() ?: 'Pengambilan ATK belum dapat disimpan.']);
        }
        return redirect()->to(site_url(self::URL))->with('success', 'Pengambilan ATK berhasil disimpan dan stok telah diperbarui.');
    }

    public function destroyPickup(int $id): RedirectResponse
    {
        $pickup = (new AtkPengambilanModel())->find($id);
        if ($pickup === null) throw PageNotFoundException::forPageNotFound('Bukti pengambilan ATK tidak ditemukan.');
        $items = (new AtkPengambilanItemModel())->where('pengambilan_id', $id)->findAll();
        $db = db_connect();
        try {
            $db->transBegin();
            foreach ($items as $item) $db->table('agendaris_atk_kantor')->set('stok_barang', 'stok_barang + ' . (int) $item['jumlah_diambil'], false)->where('id', (int) $item['atk_id'])->update();
            $deleted = (new AtkPengambilanModel())->update($id, ['deleted_by_role' => (string) session()->get('auth_role'), 'deleted_by_name' => (string) session()->get('auth_display_name')]) && (new AtkPengambilanModel())->delete($id);
            if (! $deleted || ! $db->transStatus()) throw new \RuntimeException('Bukti pengambilan ATK belum dapat dihapus.');
            $db->transCommit();
        } catch (\Throwable $error) {
            $db->transRollback();
            return redirect()->to(site_url(self::URL))->with('error', 'Bukti pengambilan ATK belum dapat dihapus.')->with('atk_pickup_list_open', true);
        }
        return redirect()->to(site_url(self::URL))->with('success', 'Bukti pengambilan ATK dihapus dan stok telah dikembalikan.')->with('atk_pickup_list_open', true);
    }

    public function downloadPickupProof(): ResponseInterface
    {
        $month = filter_var($this->request->getGet('bulan'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);
        $year = filter_var($this->request->getGet('tahun'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 2020, 'max_range' => 2100]]);
        if ($month === false || $year === false) return $this->response->setStatusCode(422)->setBody('Bulan atau tahun bukti pengambilan tidak valid.');

        try {
            $pdf = (new AtkPickupProofPdf())->render($this->pickupProofRows((int) $year, (int) $month));
        } catch (\Throwable $error) {
            log_message('error', 'Gagal membuat bukti pengambilan ATK: {message}', ['message' => $error->getMessage()]);
            return $this->response->setStatusCode(500)->setBody('Bukti pengambilan ATK belum dapat dibuat. Silakan coba kembali.');
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', sprintf('attachment; filename="Bukti-Pengambilan-ATK-%04d-%02d.pdf"', $year, $month))
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setBody($pdf);
    }

    public function store(): RedirectResponse
    {
        return $this->saveRecord();
    }

    public function update(int $id): RedirectResponse
    {
        return $this->saveRecord($id, $this->findRecord($id));
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->findRecord($id);
        if (! $this->deleteRecord(new AtkKantorModel(), 'agendaris_atk_kantor', $id)) {
            return redirect()->to(site_url(self::URL))->with('error', 'Data ATK Kantor belum dapat dihapus.');
        }

        return redirect()->to(site_url(self::URL))->with('success', 'Data ATK Kantor berhasil dihapus.');
    }

    private function saveRecord(?int $id = null, array $existing = []): RedirectResponse
    {
        $data = [
            'nama_barang' => trim((string) $this->request->getPost('nama_barang')),
            'merek' => trim((string) $this->request->getPost('merek')),
            'stok_barang' => $this->nonNegativeQuantity('stok_barang'),
            'satuan' => trim((string) $this->request->getPost('satuan')),
            'keterangan' => trim((string) $this->request->getPost('keterangan')),
        ];
        $errors = [];
        if ($data['nama_barang'] === '') $errors[] = 'Nama barang wajib diisi.';
        elseif (mb_strlen($data['nama_barang']) > 150) $errors[] = 'Nama barang maksimal 150 karakter.';
        if (mb_strlen($data['merek']) > 150) $errors[] = 'Merek maksimal 150 karakter.';
        if ($data['stok_barang'] === null) $errors[] = 'Stok barang harus berupa angka bulat nol atau lebih.';
        if (! in_array($data['satuan'], self::UNITS, true)) $errors[] = 'Satuan barang tidak valid.';
        if (mb_strlen($data['keterangan']) > 2000) $errors[] = 'Keterangan maksimal 2.000 karakter.';

        $values = [
            'nama_barang' => $data['nama_barang'],
            'merek' => $data['merek'],
            'stok_barang' => (string) $this->request->getPost('stok_barang'),
            'satuan' => $data['satuan'],
            'keterangan' => $data['keterangan'],
        ];
        if ($errors !== []) {
            return $this->popupError($id, $values, $errors);
        }

        $data['merek'] = $data['merek'] === '' ? null : $data['merek'];
        $data['keterangan'] = $data['keterangan'] === '' ? null : $data['keterangan'];
        $data['jumlah'] = $data['stok_barang'] + ($id === null ? 0 : $this->usedQuantity($id));
        $data['kondisi'] = 'Baik';
        $model = new AtkKantorModel();
        $saved = $id === null ? $model->insert($data) : $model->update($id, $data);
        if (! $saved) {
            return $this->popupError($id, $values, ['Data ATK Kantor belum dapat disimpan.']);
        }

        return redirect()->to(site_url(self::URL))->with('success', $id === null ? 'Data ATK Kantor berhasil ditambahkan.' : 'Data ATK Kantor berhasil diperbarui.');
    }

    private function findRecord(int $id): array
    {
        $record = (new AtkKantorModel())->find($id);
        if ($record === null) throw PageNotFoundException::forPageNotFound('Data ATK Kantor tidak ditemukan.');
        return $record;
    }

    private function popupError(?int $id, array $values, array $errors): RedirectResponse
    {
        return redirect()->to(site_url(self::URL))->with('atk_kantor_popup', ['id' => $id, 'values' => $values, 'errors' => $errors]);
    }

    private function nonNegativeQuantity(string $field): ?int
    {
        $value = $this->request->getPost($field);
        if (! is_string($value) || ! preg_match('/^(?:0|[1-9][0-9]{0,9})$/D', $value)) return null;
        $quantity = (int) $value;
        return $quantity <= 2147483647 ? $quantity : null;
    }

    private function dateValue(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\\d{4}-\\d{2}-\\d{2}$/D', $value)) return null;
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
    }

    /** @return list<array{atk_id:int,jumlah_diambil:int,nama_barang:string}> */
    private function pickupItems(): array
    {
        $submitted = $this->submittedPickupItems();
        if ($submitted === []) return [];
        $quantities = [];
        foreach ($submitted as $item) $quantities[$item['atk_id']] = ($quantities[$item['atk_id']] ?? 0) + $item['jumlah_diambil'];
        $records = (new AtkKantorModel())->whereIn('id', array_keys($quantities))->findAll();
        if (count($records) !== count($quantities)) return [];
        $names = array_column($records, 'nama_barang', 'id');
        $items = [];
        foreach ($quantities as $id => $quantity) $items[] = ['atk_id' => (int) $id, 'jumlah_diambil' => $quantity, 'nama_barang' => (string) $names[$id]];
        return $items;
    }

    /** @return list<array{atk_id:int,jumlah_diambil:int}> */
    private function submittedPickupItems(): array
    {
        $ids = $this->request->getPost('atk_ids');
        $quantities = $this->request->getPost('jumlah_diambil');
        if (! is_array($ids) || ! is_array($quantities)) return [];
        $items = [];
        foreach ($ids as $index => $id) {
            $quantity = $quantities[$index] ?? null;
            if (! is_string($id) || ! ctype_digit($id) || (int) $id < 1 || ! is_string($quantity) || ! preg_match('/^[1-9][0-9]{0,9}$/D', $quantity)) return [];
            $items[] = ['atk_id' => (int) $id, 'jumlah_diambil' => (int) $quantity];
        }
        return $items;
    }

    /** @return list<array<string, mixed>> */
    private function pickupRecords(): array
    {
        $rows = db_connect()->table('agendaris_atk_pengambilan as pengambilan')->select('pengambilan.*, GROUP_CONCAT(atk.nama_barang ORDER BY item.id SEPARATOR ", ") AS nama_barang, GROUP_CONCAT(CONCAT(item.jumlah_diambil, " ", atk.satuan) ORDER BY item.id SEPARATOR ", ") AS jumlah_diambil')->join('agendaris_atk_pengambilan_item as item', 'item.pengambilan_id = pengambilan.id')->join('agendaris_atk_kantor as atk', 'atk.id = item.atk_id')->where('pengambilan.deleted_at', null)->groupBy('pengambilan.id')->orderBy('pengambilan.tanggal_pengambilan', 'DESC')->orderBy('pengambilan.id', 'DESC')->get()->getResultArray();
        return $rows;
    }

    /** @return list<int> */
    private function pickupYears(): array
    {
        $years = db_connect()->table('agendaris_atk_pengambilan')
            ->select('YEAR(tanggal_pengambilan) AS tahun', false)
            ->where('deleted_at', null)
            ->groupBy('YEAR(tanggal_pengambilan)')
            ->orderBy('tahun', 'DESC')
            ->get()
            ->getResultArray();
        $values = array_map(static fn (array $row): int => (int) $row['tahun'], $years);
        $currentYear = (int) date('Y');
        if (! in_array($currentYear, $values, true)) array_unshift($values, $currentYear);
        return $values;
    }

    /** @return list<array{nama_pengambil:string,nama_barang:string,tanggal_pengambilan:string,jumlah_diambil:int,satuan:string}> */
    private function pickupProofRows(int $year, int $month): array
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = (new \DateTimeImmutable($start))->modify('+1 month')->format('Y-m-d');
        $rows = db_connect()->table('agendaris_atk_pengambilan as pengambilan')
            ->select('pengambilan.nama_pengambil, pengambilan.tanggal_pengambilan, item.jumlah_diambil, atk.nama_barang, atk.satuan')
            ->join('agendaris_atk_pengambilan_item as item', 'item.pengambilan_id = pengambilan.id')
            ->join('agendaris_atk_kantor as atk', 'atk.id = item.atk_id')
            ->where('pengambilan.deleted_at', null)
            ->where('pengambilan.tanggal_pengambilan >=', $start)
            ->where('pengambilan.tanggal_pengambilan <', $end)
            ->orderBy('pengambilan.tanggal_pengambilan', 'ASC')
            ->orderBy('pengambilan.id', 'ASC')
            ->orderBy('item.id', 'ASC')
            ->limit(30)
            ->get()
            ->getResultArray();

        return array_map(static fn (array $row): array => [
            'nama_pengambil' => (string) $row['nama_pengambil'],
            'nama_barang' => (string) $row['nama_barang'],
            'tanggal_pengambilan' => (string) $row['tanggal_pengambilan'],
            'jumlah_diambil' => (int) $row['jumlah_diambil'],
            'satuan' => (string) ($row['satuan'] ?: 'Pcs'),
        ], $rows);
    }

    private function usedQuantity(int $atkId): int
    {
        $row = $this->usageQuery()->where('item.atk_id', $atkId)->get()->getRowArray();
        return (int) ($row['terpakai'] ?? 0);
    }

    private function usageQuery(): \CodeIgniter\Database\BaseBuilder
    {
        return db_connect()->table('agendaris_atk_pengambilan_item as item')
            ->select('item.atk_id, SUM(item.jumlah_diambil) AS terpakai', false)
            ->join('agendaris_atk_pengambilan as pengambilan', 'pengambilan.id = item.pengambilan_id')
            ->where('pengambilan.deleted_at', null)
            ->groupBy('item.atk_id');
    }

    private function pickupPopupError(array $values, array $errors): RedirectResponse
    {
        return redirect()->to(site_url(self::URL))->with('atk_pickup_popup', ['values' => $values, 'errors' => $errors]);
    }
}
