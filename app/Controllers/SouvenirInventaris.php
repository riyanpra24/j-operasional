<?php

namespace App\Controllers;

use App\Libraries\SouvenirPickupProofPdf;
use App\Libraries\SouvenirReturnProofPdf;
use App\Libraries\InventoryLoanProofPdf;
use App\Libraries\InventoryReturnProofPdf;
use App\Models\AgendarisSouvenirInventarisModel;
use App\Models\AgendarisSouvenirPengambilanModel;
use App\Models\AgendarisSouvenirPengambilanItemModel;
use App\Models\AgendarisSouvenirPengembalianModel;
use App\Models\AgendarisSouvenirPengembalianItemModel;
use App\Models\AgendarisInventarisPeminjamanModel;
use App\Models\AgendarisInventarisPeminjamanItemModel;
use App\Models\AgendarisInventarisPengembalianModel;
use App\Models\AgendarisInventarisPengembalianItemModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class SouvenirInventaris extends BaseController
{
    private const URL = 'agendaris/souvenir-inventaris';
    private const UNITS = ['pcs' => 'Pcs', 'box' => 'Box', 'set' => 'Set', 'lusin' => 'Lusin', 'rim' => 'Rim', 'unit' => 'Unit'];

    public function index(): string
    {
        $keyword = $this->request->getGet('q');
        $stock = $this->request->getGet('stok');
        $jenis = $this->request->getGet('jenis');
        $perPage = (int) $this->request->getGet('per_page');
        $filters = [
            'q' => is_string($keyword) ? mb_substr(trim($keyword), 0, 150) : '',
            'stok' => is_string($stock) && in_array($stock, ['tersedia', 'habis'], true) ? $stock : '',
            'jenis' => is_string($jenis) && in_array($jenis, ['Souvenir', 'Inventaris'], true) ? $jenis : '',
            'perPage' => in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 10,
        ];

        $model = new AgendarisSouvenirInventarisModel();
        if ($filters['q'] !== '') {
            $model->groupStart()->like('nama_item', $filters['q'])->orLike('keterangan', $filters['q'])->groupEnd();
        }
        if ($filters['stok'] === 'tersedia') $model->where('persediaan_akhir >', 0);
        if ($filters['stok'] === 'habis') $model->where('persediaan_akhir', 0);
        if ($filters['jenis'] !== '') $model->where('jenis', $filters['jenis']);

        $items = $model->orderBy('nama_item', 'ASC')->orderBy('id', 'ASC')->paginate($filters['perPage'], 'souvenir_inventaris');

        return view('agendaris/souvenir_inventaris/index', [
            'title' => 'Souvenir dan Inventaris',
            'filters' => $filters,
            'items' => $items,
            'pager' => $model->pager,
            'popupState' => session()->getFlashdata('souvenir_popup'),
            'pickupRecords' => $this->pickupRecords(),
            'pickupSouvenirs' => $this->pickupSouvenirs(),
            'pickupState' => session()->getFlashdata('souvenir_pickup_popup'),
            'pickupListOpen' => session()->getFlashdata('souvenir_pickup_list_open'),
            'returnRecords' => $this->returnRecords(),
            'returnState' => session()->getFlashdata('souvenir_return_popup'),
            'returnListOpen' => session()->getFlashdata('souvenir_return_list_open'),
            'loanRecords' => $this->inventoryLoanRecords(),
            'inventoryReturnRecords' => $this->inventoryReturnRecords(),
            'inventoryItems' => $this->inventoryItems(),
            'loanState' => session()->getFlashdata('inventory_loan_popup'),
            'loanListOpen' => session()->getFlashdata('inventory_loan_list_open'),
            'inventoryReturnState' => session()->getFlashdata('inventory_return_popup'),
            'inventoryReturnListOpen' => session()->getFlashdata('inventory_return_list_open'),
            'units' => self::UNITS,
        ]);
    }

    public function create(): string
    {
        return $this->formView([]);
    }

    public function show(int $id): string
    {
        return view('agendaris/souvenir_inventaris/show', [
            'title' => 'Detail Souvenir dan Inventaris',
            'record' => $this->findRecord($id),
        ]);
    }

    public function edit(int $id): string
    {
        return $this->formView($this->findRecord($id));
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
        if (! $this->deleteRecord(new AgendarisSouvenirInventarisModel(), 'agendaris_souvenir_inventaris', $id)) {
            return redirect()->to(site_url(self::URL))->with('error', 'Data souvenir dan inventaris gagal dihapus.');
        }

        return redirect()->to(site_url(self::URL))->with('success', 'Data souvenir dan inventaris berhasil dihapus.');
    }

    public function storePickup(): RedirectResponse
    {
        return $this->savePickup();
    }

    public function updatePickup(int $id): RedirectResponse
    {
        return $this->savePickup($id, $this->findPickup($id));
    }

    public function destroyPickup(int $id): RedirectResponse
    {
        $this->findPickup($id);
        $db = db_connect();
        $pickupItems = $db->table('agendaris_souvenir_pengambilan_item')
            ->select('souvenir_id')
            ->where('pengambilan_id', $id)
            ->get()
            ->getResultArray();
        $souvenirIds = array_values(array_unique(array_map(static fn (array $item): int => (int) $item['souvenir_id'], $pickupItems)));

        try {
            $db->transBegin();
            $stockItems = $this->lockPickupStockItems($db, $souvenirIds);
            if (! (new AgendarisSouvenirPengambilanModel())->delete($id)) {
                throw new \RuntimeException('Data pengambilan belum dapat dihapus.');
            }
            $this->syncPickupStock($db, $stockItems);
            if (! $db->transStatus()) {
                throw new \RuntimeException('Stok souvenir belum dapat diperbarui.');
            }
            $db->transCommit();
        } catch (\Throwable $error) {
            $db->transRollback();
            return redirect()->to(site_url(self::URL))
                ->with('error', 'Data pengambilan belum dapat dihapus.')
                ->with('souvenir_pickup_list_open', true);
        }

        return redirect()->to(site_url(self::URL))
            ->with('success', 'Data pengambilan souvenir berhasil dihapus dan stok telah dikembalikan.')
            ->with('souvenir_pickup_list_open', true);
    }

    public function storeReturn(): RedirectResponse
    {
        return $this->saveReturn();
    }

    public function updateReturn(int $id): RedirectResponse
    {
        return $this->saveReturn($id, $this->findReturn($id));
    }

    public function destroyReturn(int $id): RedirectResponse
    {
        $this->findReturn($id);
        $db = db_connect();
        $returnItems = $db->table('agendaris_souvenir_pengembalian_item')
            ->select('souvenir_id')
            ->where('pengembalian_id', $id)
            ->get()
            ->getResultArray();
        $souvenirIds = array_values(array_unique(array_map(static fn (array $item): int => (int) $item['souvenir_id'], $returnItems)));

        try {
            $db->transBegin();
            $stockItems = $this->lockPickupStockItems($db, $souvenirIds);
            if (! (new AgendarisSouvenirPengembalianModel())->delete($id)) {
                throw new \RuntimeException('Data pengembalian belum dapat dihapus.');
            }
            $this->syncPickupStock($db, $stockItems);
            if (! $db->transStatus()) throw new \RuntimeException('Stok souvenir belum dapat diperbarui.');
            $db->transCommit();
        } catch (\Throwable $error) {
            $db->transRollback();
            return redirect()->to(site_url(self::URL))
                ->with('error', 'Data pengembalian belum dapat dihapus.')
                ->with('souvenir_return_list_open', true);
        }

        return redirect()->to(site_url(self::URL))
            ->with('success', 'Data pengembalian souvenir berhasil dihapus dan stok diperbarui.')
            ->with('souvenir_return_list_open', true);
    }

    public function storeInventoryLoan(): RedirectResponse { return $this->saveInventoryMovement(true); }
    public function updateInventoryLoan(int $id): RedirectResponse { return $this->saveInventoryMovement(true, $id, $this->findInventoryLoan($id)); }
    public function destroyInventoryLoan(int $id): RedirectResponse { return $this->destroyInventoryMovement(true, $id); }
    public function storeInventoryReturn(): RedirectResponse { return $this->saveInventoryMovement(false); }
    public function updateInventoryReturn(int $id): RedirectResponse { return $this->saveInventoryMovement(false, $id, $this->findInventoryReturn($id)); }
    public function destroyInventoryReturn(int $id): RedirectResponse { return $this->destroyInventoryMovement(false, $id); }

    public function downloadPickupProof(int $id): ResponseInterface
    {
        $pickup = $this->findPickup($id);
        $items = (new AgendarisSouvenirPengambilanItemModel())
            ->select('agendaris_souvenir_pengambilan_item.jumlah_diambil, agendaris_souvenir_inventaris.nama_item, agendaris_souvenir_inventaris.satuan')
            ->join('agendaris_souvenir_inventaris', 'agendaris_souvenir_inventaris.id = agendaris_souvenir_pengambilan_item.souvenir_id')
            ->where('agendaris_souvenir_pengambilan_item.pengambilan_id', $id)
            ->orderBy('agendaris_souvenir_pengambilan_item.id', 'ASC')
            ->findAll();

        if ($items === []) {
            throw PageNotFoundException::forPageNotFound('Item pengambilan souvenir tidak ditemukan.');
        }

        $proofItems = array_map(function (array $item): array {
            $unit = self::UNITS[$item['satuan'] ?? 'pcs'] ?? ucfirst((string) ($item['satuan'] ?? 'pcs'));

            return [
                'nama_item' => (string) $item['nama_item'],
                'jumlah_diambil' => (int) $item['jumlah_diambil'],
                'satuan' => $unit,
            ];
        }, $items);

        try {
            $pdf = (new SouvenirPickupProofPdf())->render([
                'nama_pengambil' => (string) $pickup['nama_pengambil'],
                'divisi_bagian' => (string) $pickup['divisi_bagian'],
                'keperluan_kegiatan' => (string) $pickup['keperluan_kegiatan'],
                'tanggal_pengambilan' => (string) $pickup['tanggal_pengambilan'],
                'keterangan' => $pickup['keterangan'] ?? null,
                'petugas' => trim((string) (session()->get('auth_display_name') ?: session()->get('auth_username') ?: 'Petugas Agendaris')),
                'items' => $proofItems,
            ]);
        } catch (\Throwable $error) {
            log_message('error', 'Gagal membuat bukti pengambilan souvenir: {message}', ['message' => $error->getMessage()]);

            return $this->response->setStatusCode(500)->setBody('Bukti pengambilan belum dapat dibuat. Silakan coba kembali.');
        }

        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $pickup['nama_pengambil']) ?: 'pengambilan';
        $filename = 'Bukti-Pengambilan-Souvenir-' . trim($safeName, '-') . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setBody($pdf);
    }

    public function downloadReturnProof(int $id): ResponseInterface
    {
        $return = $this->findReturn($id);
        $items = (new AgendarisSouvenirPengembalianItemModel())
            ->select('agendaris_souvenir_pengembalian_item.jumlah_dikembalikan, agendaris_souvenir_inventaris.nama_item, agendaris_souvenir_inventaris.satuan')
            ->join('agendaris_souvenir_inventaris', 'agendaris_souvenir_inventaris.id = agendaris_souvenir_pengembalian_item.souvenir_id')
            ->where('agendaris_souvenir_pengembalian_item.pengembalian_id', $id)
            ->orderBy('agendaris_souvenir_pengembalian_item.id', 'ASC')
            ->findAll();
        if ($items === []) throw PageNotFoundException::forPageNotFound('Item pengembalian souvenir tidak ditemukan.');

        $proofItems = array_map(function (array $item): array {
            return [
                'nama_item' => (string) $item['nama_item'],
                'jumlah_dikembalikan' => (int) $item['jumlah_dikembalikan'],
                'satuan' => self::UNITS[$item['satuan'] ?? 'pcs'] ?? ucfirst((string) ($item['satuan'] ?? 'pcs')),
            ];
        }, $items);
        try {
            $pdf = (new SouvenirReturnProofPdf())->render([
                'nama_pengembali' => (string) $return['nama_pengembali'],
                'divisi_bagian' => (string) $return['divisi_bagian'],
                'keperluan_kegiatan' => (string) ($return['keperluan_kegiatan'] ?? ''),
                'tanggal_pengembalian' => (string) $return['tanggal_pengembalian'],
                'keterangan' => $return['keterangan'] ?? null,
                'petugas' => trim((string) (session()->get('auth_display_name') ?: session()->get('auth_username') ?: 'Petugas Agendaris')),
                'items' => $proofItems,
            ]);
        } catch (\Throwable $error) {
            log_message('error', 'Gagal membuat bukti pengembalian souvenir: {message}', ['message' => $error->getMessage()]);
            return $this->response->setStatusCode(500)->setBody('Bukti pengembalian belum dapat dibuat. Silakan coba kembali.');
        }
        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $return['nama_pengembali']) ?: 'pengembalian';
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="Bukti-Pengembalian-Souvenir-' . trim($safeName, '-') . '.pdf"')
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setBody($pdf);
    }

    public function downloadInventoryLoanProof(int $id): ResponseInterface
    {
        $loan = $this->findInventoryLoan($id);
        $items = (new AgendarisInventarisPeminjamanItemModel())
            ->select('agendaris_inventaris_peminjaman_item.jumlah_dipinjam, agendaris_souvenir_inventaris.nama_item, agendaris_souvenir_inventaris.satuan')
            ->join('agendaris_souvenir_inventaris', 'agendaris_souvenir_inventaris.id = agendaris_inventaris_peminjaman_item.inventaris_id')
            ->where('agendaris_inventaris_peminjaman_item.peminjaman_id', $id)->orderBy('agendaris_inventaris_peminjaman_item.id', 'ASC')->findAll();
        if ($items === []) throw PageNotFoundException::forPageNotFound('Item peminjaman inventaris tidak ditemukan.');
        try {
            $pdf = (new InventoryLoanProofPdf())->render([
                'nama_peminjam' => (string) $loan['nama_peminjam'], 'divisi_bagian' => (string) $loan['divisi_bagian'], 'keperluan_kegiatan' => (string) $loan['keperluan_kegiatan'], 'tanggal_peminjaman' => (string) $loan['tanggal_peminjaman'], 'keterangan' => $loan['keterangan'] ?? null,
                'petugas' => $this->proofOfficer(),
                'items' => array_map(fn (array $item): array => ['nama_item' => (string) $item['nama_item'], 'jumlah_dipinjam' => (int) $item['jumlah_dipinjam'], 'satuan' => self::UNITS[$item['satuan'] ?? 'unit'] ?? ucfirst((string) ($item['satuan'] ?? 'unit'))], $items),
            ]);
        } catch (\Throwable $error) {
            log_message('error', 'Gagal membuat bukti peminjaman inventaris: {message}', ['message' => $error->getMessage()]);
            return $this->response->setStatusCode(500)->setBody('Bukti peminjaman belum dapat dibuat. Silakan coba kembali.');
        }
        return $this->proofResponse($pdf, 'Bukti-Peminjaman-Inventaris-', (string) $loan['nama_peminjam']);
    }

    public function downloadInventoryReturnProof(int $id): ResponseInterface
    {
        $return = $this->findInventoryReturn($id);
        $items = (new AgendarisInventarisPengembalianItemModel())
            ->select('agendaris_inventaris_pengembalian_item.jumlah_dikembalikan, agendaris_souvenir_inventaris.nama_item, agendaris_souvenir_inventaris.satuan')
            ->join('agendaris_souvenir_inventaris', 'agendaris_souvenir_inventaris.id = agendaris_inventaris_pengembalian_item.inventaris_id')
            ->where('agendaris_inventaris_pengembalian_item.pengembalian_id', $id)->orderBy('agendaris_inventaris_pengembalian_item.id', 'ASC')->findAll();
        if ($items === []) throw PageNotFoundException::forPageNotFound('Item pengembalian inventaris tidak ditemukan.');
        try {
            $pdf = (new InventoryReturnProofPdf())->render([
                'nama_pengembali' => (string) $return['nama_pengembali'], 'divisi_bagian' => (string) $return['divisi_bagian'], 'keperluan_kegiatan' => (string) $return['keperluan_kegiatan'], 'tanggal_pengembalian' => (string) $return['tanggal_pengembalian'], 'keterangan' => $return['keterangan'] ?? null,
                'petugas' => $this->proofOfficer(),
                'items' => array_map(fn (array $item): array => ['nama_item' => (string) $item['nama_item'], 'jumlah_dikembalikan' => (int) $item['jumlah_dikembalikan'], 'satuan' => self::UNITS[$item['satuan'] ?? 'unit'] ?? ucfirst((string) ($item['satuan'] ?? 'unit'))], $items),
            ]);
        } catch (\Throwable $error) {
            log_message('error', 'Gagal membuat bukti pengembalian inventaris: {message}', ['message' => $error->getMessage()]);
            return $this->response->setStatusCode(500)->setBody('Bukti pengembalian belum dapat dibuat. Silakan coba kembali.');
        }
        return $this->proofResponse($pdf, 'Bukti-Pengembalian-Inventaris-', (string) $return['nama_pengembali']);
    }

    private function proofOfficer(): string
    {
        return trim((string) (session()->get('auth_display_name') ?: session()->get('auth_username') ?: 'Petugas Agendaris'));
    }

    private function proofResponse(string $pdf, string $prefix, string $name): ResponseInterface
    {
        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '-', $name) ?: 'dokumen';
        return $this->response->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $prefix . trim($safeName, '-') . '.pdf"')
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')->setBody($pdf);
    }

    private function saveRecord(?int $id = null, array $existing = []): RedirectResponse
    {
        $namaItem = trim((string) $this->request->getPost('nama_item'));
        $jenis = trim((string) $this->request->getPost('jenis'));
        $satuan = strtolower(trim((string) $this->request->getPost('satuan')));
        $keterangan = trim((string) $this->request->getPost('keterangan'));
        $persediaanAwal = $this->quantity('persediaan_awal');
        $persediaanAkhir = $id === null ? $persediaanAwal : (int) ($existing['persediaan_akhir'] ?? 0);
        $errors = [];

        if ($namaItem === '') $errors['nama_item'] = 'Nama item wajib diisi.';
        elseif (mb_strlen($namaItem) > 150) $errors['nama_item'] = 'Nama item maksimal 150 karakter.';
        if (! in_array($jenis, ['Souvenir', 'Inventaris'], true)) $errors['jenis'] = 'Pilih jenis item.';
        if (! array_key_exists($satuan, self::UNITS)) $errors['satuan'] = 'Pilih satuan item.';
        if (mb_strlen($keterangan) > 2000) $errors['keterangan'] = 'Keterangan maksimal 2.000 karakter.';
        if ($persediaanAwal === null) $errors['persediaan_awal'] = 'Persediaan awal harus berupa angka bulat nol atau lebih.';
        if ($persediaanAwal !== null && $persediaanAkhir !== null && $persediaanAkhir > $persediaanAwal) {
            $errors['persediaan_awal'] = 'Persediaan awal tidak boleh lebih kecil dari persediaan akhir saat ini.';
        }

        if ($errors !== []) {
            return redirect()->to(site_url(self::URL))->with('souvenir_popup', [
                'id' => $id,
                'values' => [
                    'nama_item' => $namaItem,
                    'jenis' => $jenis,
                    'satuan' => $satuan,
                    'persediaan_awal' => (string) $this->request->getPost('persediaan_awal'),
                    'keterangan' => $keterangan,
                ],
                'errors' => array_values($errors),
            ]);
        }

        $data = [
            'nama_item' => $namaItem,
            'jenis' => $jenis,
            'satuan' => $satuan,
            'persediaan_awal' => $persediaanAwal,
            'persediaan_akhir' => $persediaanAkhir,
            'terpakai' => $persediaanAwal - $persediaanAkhir,
            'keterangan' => $keterangan === '' ? null : $keterangan,
        ];
        $model = new AgendarisSouvenirInventarisModel();
        $saved = $id === null ? $model->insert($data) : $model->update($id, $data);
        if (! $saved) {
            return redirect()->to(site_url(self::URL))->with('souvenir_popup', [
                'id' => $id,
                'values' => $data,
                'errors' => ['Data belum dapat disimpan.'],
            ]);
        }

        return redirect()->to(site_url(self::URL))->with('success', $id === null ? 'Data souvenir dan inventaris berhasil ditambahkan.' : 'Data souvenir dan inventaris berhasil diperbarui.');
    }

    private function formView(array $record): string
    {
        return view('agendaris/souvenir_inventaris/form', [
            'title' => $record === [] ? 'Tambah Souvenir dan Inventaris' : 'Ubah Souvenir dan Inventaris',
            'record' => $record,
            'units' => self::UNITS,
        ]);
    }

    private function findRecord(int $id): array
    {
        $record = (new AgendarisSouvenirInventarisModel())->find($id);
        if ($record === null) throw PageNotFoundException::forPageNotFound('Data souvenir dan inventaris tidak ditemukan.');
        return $record;
    }

    private function savePickup(?int $id = null, array $existing = []): RedirectResponse
    {
        $namaPengambil = trim((string) $this->request->getPost('nama_pengambil'));
        $divisiBagian = trim((string) $this->request->getPost('divisi_bagian'));
        $keperluanKegiatan = trim((string) $this->request->getPost('keperluan_kegiatan'));
        $keterangan = trim((string) $this->request->getPost('keterangan'));
        $submittedPickupItems = $this->submittedPickupItems();
        $pickupItems = $this->validPickupItems($submittedPickupItems);
        $souvenirIds = array_values(array_unique(array_column($pickupItems, 'souvenir_id')));
        $tanggalPengambilan = $this->dateValue($this->request->getPost('tanggal_pengambilan'));
        $errors = [];

        if ($namaPengambil === '') $errors['nama_pengambil'] = 'Nama pengambil wajib diisi.';
        elseif (mb_strlen($namaPengambil) > 150) $errors['nama_pengambil'] = 'Nama pengambil maksimal 150 karakter.';
        if ($divisiBagian === '') $errors['divisi_bagian'] = 'Divisi atau bagian wajib diisi.';
        elseif (mb_strlen($divisiBagian) > 150) $errors['divisi_bagian'] = 'Divisi atau bagian maksimal 150 karakter.';
        if ($keperluanKegiatan === '') $errors['keperluan_kegiatan'] = 'Keperluan atau kegiatan wajib diisi.';
        elseif (mb_strlen($keperluanKegiatan) > 500) $errors['keperluan_kegiatan'] = 'Keperluan atau kegiatan maksimal 500 karakter.';
        if (mb_strlen($keterangan) > 500) $errors['keterangan'] = 'Catatan maksimal 500 karakter.';
        if ($pickupItems === []) {
            $errors['pickup_items'] = 'Pilih minimal satu item dan isi jumlah yang diambil.';
        } else {
            $availableItems = (new AgendarisSouvenirInventarisModel())
                ->where('jenis', 'Souvenir')
                ->whereIn('id', $souvenirIds)
                ->findAll();
            if (count($availableItems) !== count($souvenirIds)) $errors['souvenir_ids'] = 'Salah satu item souvenir tidak tersedia.';
        }
        if ($tanggalPengambilan === null) $errors['tanggal_pengambilan'] = 'Tanggal pengambilan wajib diisi.';

        $values = [
            'nama_pengambil' => $namaPengambil,
            'divisi_bagian' => $divisiBagian,
            'keperluan_kegiatan' => $keperluanKegiatan,
            'keterangan' => $keterangan,
            'pickup_items' => $submittedPickupItems,
            'tanggal_pengambilan' => (string) $this->request->getPost('tanggal_pengambilan'),
        ];
        if ($errors !== []) {
            return redirect()->to(site_url(self::URL))->with('souvenir_pickup_popup', ['id' => $id, 'values' => $values, 'errors' => array_values($errors)]);
        }

        $data = [
            'nama_pengambil' => $namaPengambil,
            'divisi_bagian' => $divisiBagian,
            'keperluan_kegiatan' => $keperluanKegiatan,
            'keterangan' => $keterangan === '' ? null : $keterangan,
            'souvenir_id' => $pickupItems[0]['souvenir_id'],
            'tanggal_pengambilan' => $tanggalPengambilan,
        ];
        $db = db_connect();
        $previousItems = $id === null ? [] : $db->table('agendaris_souvenir_pengambilan_item')
            ->select('souvenir_id')
            ->where('pengambilan_id', $id)
            ->get()
            ->getResultArray();
        $affectedSouvenirIds = array_values(array_unique(array_merge(
            array_map(static fn (array $item): int => (int) $item['souvenir_id'], $pickupItems),
            array_map(static fn (array $item): int => (int) $item['souvenir_id'], $previousItems)
        )));

        try {
            $db->transBegin();
            $stockItems = $this->lockPickupStockItems($db, $affectedSouvenirIds);
            if (count($stockItems) !== count($affectedSouvenirIds)) {
                throw new \RuntimeException('Salah satu item souvenir tidak tersedia.');
            }

            $model = new AgendarisSouvenirPengambilanModel();
            $saved = $id === null ? $model->insert($data) : $model->update($id, $data);
            $pickupId = $id ?? (int) $model->getInsertID();
            if (! $saved || $pickupId <= 0) {
                throw new \RuntimeException('Data pengambilan belum dapat disimpan.');
            }

            if (! $db->table('agendaris_souvenir_pengambilan_item')->where('pengambilan_id', $pickupId)->delete()) {
                throw new \RuntimeException('Item pengambilan belum dapat disimpan.');
            }
            foreach ($pickupItems as $pickupItem) {
                if (! $db->table('agendaris_souvenir_pengambilan_item')->insert([
                    'pengambilan_id' => $pickupId,
                    'souvenir_id' => $pickupItem['souvenir_id'],
                    'jumlah_diambil' => $pickupItem['jumlah_diambil'],
                ])) {
                    throw new \RuntimeException('Item pengambilan belum dapat disimpan.');
                }
            }

            $this->syncPickupStock($db, $stockItems);
            if (! $db->transStatus()) {
                throw new \RuntimeException('Stok souvenir belum dapat diperbarui.');
            }
            $db->transCommit();
        } catch (\Throwable $error) {
            $db->transRollback();
            return redirect()->to(site_url(self::URL))->with('souvenir_pickup_popup', ['id' => $id, 'values' => $values, 'errors' => [$error->getMessage() ?: 'Data pengambilan belum dapat disimpan.']]);
        }

        return redirect()->to(site_url(self::URL))
            ->with('success', $id === null ? 'Data pengambilan souvenir berhasil ditambahkan.' : 'Data pengambilan souvenir berhasil diperbarui.')
            ->with('souvenir_pickup_list_open', true);
    }

    private function saveReturn(?int $id = null, array $existing = []): RedirectResponse
    {
        $namaPengembali = trim((string) $this->request->getPost('nama_pengembali'));
        $divisiBagian = trim((string) $this->request->getPost('divisi_bagian'));
        $keperluanKegiatan = trim((string) $this->request->getPost('keperluan_kegiatan'));
        $keterangan = trim((string) $this->request->getPost('keterangan'));
        $submittedReturnItems = $this->submittedReturnItems();
        $returnItems = $this->validReturnItems($submittedReturnItems);
        $souvenirIds = array_values(array_unique(array_column($returnItems, 'souvenir_id')));
        $tanggalPengembalian = $this->dateValue($this->request->getPost('tanggal_pengembalian'));
        $errors = [];

        if ($namaPengembali === '') $errors['nama_pengembali'] = 'Nama pengembali wajib diisi.';
        elseif (mb_strlen($namaPengembali) > 150) $errors['nama_pengembali'] = 'Nama pengembali maksimal 150 karakter.';
        if ($divisiBagian === '') $errors['divisi_bagian'] = 'Divisi atau bagian wajib diisi.';
        elseif (mb_strlen($divisiBagian) > 150) $errors['divisi_bagian'] = 'Divisi atau bagian maksimal 150 karakter.';
        if ($keperluanKegiatan === '') $errors['keperluan_kegiatan'] = 'Keperluan atau kegiatan wajib diisi.';
        elseif (mb_strlen($keperluanKegiatan) > 500) $errors['keperluan_kegiatan'] = 'Keperluan atau kegiatan maksimal 500 karakter.';
        if (mb_strlen($keterangan) > 500) $errors['keterangan'] = 'Catatan maksimal 500 karakter.';
        if ($returnItems === []) {
            $errors['return_items'] = 'Pilih minimal satu item dan isi jumlah sisa yang dikembalikan.';
        } else {
            $availableItems = (new AgendarisSouvenirInventarisModel())
                ->where('jenis', 'Souvenir')
                ->whereIn('id', $souvenirIds)
                ->findAll();
            if (count($availableItems) !== count($souvenirIds)) $errors['souvenir_ids'] = 'Salah satu item souvenir tidak tersedia.';
        }
        if ($tanggalPengembalian === null) $errors['tanggal_pengembalian'] = 'Tanggal pengembalian wajib diisi.';

        $values = [
            'nama_pengembali' => $namaPengembali,
            'divisi_bagian' => $divisiBagian,
            'keperluan_kegiatan' => $keperluanKegiatan,
            'keterangan' => $keterangan,
            'return_items' => $submittedReturnItems,
            'tanggal_pengembalian' => (string) $this->request->getPost('tanggal_pengembalian'),
        ];
        if ($errors !== []) {
            return redirect()->to(site_url(self::URL))->with('souvenir_return_popup', ['id' => $id, 'values' => $values, 'errors' => array_values($errors)]);
        }

        $data = [
            'nama_pengembali' => $namaPengembali,
            'divisi_bagian' => $divisiBagian,
            'keperluan_kegiatan' => $keperluanKegiatan,
            'keterangan' => $keterangan === '' ? null : $keterangan,
            'souvenir_id' => $returnItems[0]['souvenir_id'],
            'tanggal_pengembalian' => $tanggalPengembalian,
        ];
        $db = db_connect();
        $previousItems = $id === null ? [] : $db->table('agendaris_souvenir_pengembalian_item')
            ->select('souvenir_id')
            ->where('pengembalian_id', $id)
            ->get()
            ->getResultArray();
        $affectedSouvenirIds = array_values(array_unique(array_merge(
            array_map(static fn (array $item): int => (int) $item['souvenir_id'], $returnItems),
            array_map(static fn (array $item): int => (int) $item['souvenir_id'], $previousItems)
        )));

        try {
            $db->transBegin();
            $stockItems = $this->lockPickupStockItems($db, $affectedSouvenirIds);
            if (count($stockItems) !== count($affectedSouvenirIds)) throw new \RuntimeException('Salah satu item souvenir tidak tersedia.');

            $model = new AgendarisSouvenirPengembalianModel();
            $saved = $id === null ? $model->insert($data) : $model->update($id, $data);
            $returnId = $id ?? (int) $model->getInsertID();
            if (! $saved || $returnId <= 0) throw new \RuntimeException('Data pengembalian belum dapat disimpan.');

            if (! $db->table('agendaris_souvenir_pengembalian_item')->where('pengembalian_id', $returnId)->delete()) {
                throw new \RuntimeException('Item pengembalian belum dapat disimpan.');
            }
            foreach ($returnItems as $returnItem) {
                if (! $db->table('agendaris_souvenir_pengembalian_item')->insert([
                    'pengembalian_id' => $returnId,
                    'souvenir_id' => $returnItem['souvenir_id'],
                    'jumlah_dikembalikan' => $returnItem['jumlah_dikembalikan'],
                ])) throw new \RuntimeException('Item pengembalian belum dapat disimpan.');
            }

            $this->syncPickupStock($db, $stockItems);
            if (! $db->transStatus()) throw new \RuntimeException('Stok souvenir belum dapat diperbarui.');
            $db->transCommit();
        } catch (\Throwable $error) {
            $db->transRollback();
            return redirect()->to(site_url(self::URL))->with('souvenir_return_popup', ['id' => $id, 'values' => $values, 'errors' => [$error->getMessage() ?: 'Data pengembalian belum dapat disimpan.']]);
        }

        return redirect()->to(site_url(self::URL))
            ->with('success', $id === null ? 'Data pengembalian souvenir berhasil ditambahkan.' : 'Data pengembalian souvenir berhasil diperbarui.')
            ->with('souvenir_return_list_open', true);
    }

    private function saveInventoryMovement(bool $isLoan, ?int $id = null, array $existing = []): RedirectResponse
    {
        $nameField = $isLoan ? 'nama_peminjam' : 'nama_pengembali';
        $dateField = $isLoan ? 'tanggal_peminjaman' : 'tanggal_pengembalian';
        $quantityField = $isLoan ? 'jumlah_dipinjam' : 'jumlah_dikembalikan';
        $flashKey = $isLoan ? 'inventory_loan_popup' : 'inventory_return_popup';
        $listFlashKey = $isLoan ? 'inventory_loan_list_open' : 'inventory_return_list_open';
        $label = $isLoan ? 'peminjaman' : 'pengembalian';
        $nama = trim((string) $this->request->getPost($nameField));
        $divisiBagian = trim((string) $this->request->getPost('divisi_bagian'));
        $keperluanKegiatan = trim((string) $this->request->getPost('keperluan_kegiatan'));
        $keterangan = trim((string) $this->request->getPost('keterangan'));
        $submittedItems = $this->submittedInventoryItems($quantityField);
        $movementItems = $this->validInventoryItems($submittedItems, $quantityField);
        $inventarisIds = array_values(array_unique(array_column($movementItems, 'inventaris_id')));
        $tanggal = $this->dateValue($this->request->getPost($dateField));
        $errors = [];

        if ($nama === '') $errors[$nameField] = 'Nama wajib diisi.';
        elseif (mb_strlen($nama) > 150) $errors[$nameField] = 'Nama maksimal 150 karakter.';
        if ($divisiBagian === '') $errors['divisi_bagian'] = 'Divisi atau bagian wajib diisi.';
        elseif (mb_strlen($divisiBagian) > 150) $errors['divisi_bagian'] = 'Divisi atau bagian maksimal 150 karakter.';
        if ($keperluanKegiatan === '') $errors['keperluan_kegiatan'] = 'Keperluan atau kegiatan wajib diisi.';
        elseif (mb_strlen($keperluanKegiatan) > 500) $errors['keperluan_kegiatan'] = 'Keperluan atau kegiatan maksimal 500 karakter.';
        if (mb_strlen($keterangan) > 500) $errors['keterangan'] = 'Catatan maksimal 500 karakter.';
        if ($movementItems === []) {
            $errors['movement_items'] = 'Pilih minimal satu item inventaris dan isi jumlahnya.';
        } elseif (! $this->hasInventoryItems($inventarisIds)) {
            $errors['inventaris_ids'] = 'Salah satu item inventaris tidak tersedia.';
        }
        if ($tanggal === null) $errors[$dateField] = 'Tanggal wajib diisi.';

        $values = [$nameField => $nama, 'divisi_bagian' => $divisiBagian, 'keperluan_kegiatan' => $keperluanKegiatan, 'keterangan' => $keterangan, 'movement_items' => $submittedItems, $dateField => (string) $this->request->getPost($dateField)];
        if ($errors !== []) return redirect()->to(site_url(self::URL))->with($flashKey, ['id' => $id, 'values' => $values, 'errors' => array_values($errors)]);

        $tables = $isLoan
            ? ['header' => 'agendaris_inventaris_peminjaman', 'items' => 'agendaris_inventaris_peminjaman_item', 'foreign' => 'peminjaman_id', 'model' => new AgendarisInventarisPeminjamanModel()]
            : ['header' => 'agendaris_inventaris_pengembalian', 'items' => 'agendaris_inventaris_pengembalian_item', 'foreign' => 'pengembalian_id', 'model' => new AgendarisInventarisPengembalianModel()];
        $data = [$nameField => $nama, 'divisi_bagian' => $divisiBagian, 'keperluan_kegiatan' => $keperluanKegiatan, 'keterangan' => $keterangan === '' ? null : $keterangan, 'inventaris_id' => $movementItems[0]['inventaris_id'], $dateField => $tanggal];
        $db = db_connect();
        $previousItems = $id === null ? [] : $db->table($tables['items'])->select('inventaris_id')->where($tables['foreign'], $id)->get()->getResultArray();
        $affectedIds = array_values(array_unique(array_merge(array_map(static fn (array $item): int => (int) $item['inventaris_id'], $movementItems), array_map(static fn (array $item): int => (int) $item['inventaris_id'], $previousItems))));

        try {
            $db->transBegin();
            $stockItems = $this->lockPickupStockItems($db, $affectedIds);
            if (count($stockItems) !== count($affectedIds)) throw new \RuntimeException('Salah satu item inventaris tidak tersedia.');
            $model = $tables['model'];
            $saved = $id === null ? $model->insert($data) : $model->update($id, $data);
            $movementId = $id ?? (int) $model->getInsertID();
            if (! $saved || $movementId <= 0) throw new \RuntimeException('Data ' . $label . ' belum dapat disimpan.');
            if (! $db->table($tables['items'])->where($tables['foreign'], $movementId)->delete()) throw new \RuntimeException('Item ' . $label . ' belum dapat disimpan.');
            foreach ($movementItems as $movementItem) {
                if (! $db->table($tables['items'])->insert([$tables['foreign'] => $movementId, 'inventaris_id' => $movementItem['inventaris_id'], $quantityField => $movementItem[$quantityField]])) throw new \RuntimeException('Item ' . $label . ' belum dapat disimpan.');
            }
            $this->syncInventoryLoanStock($db, $stockItems);
            if (! $db->transStatus()) throw new \RuntimeException('Stok inventaris belum dapat diperbarui.');
            $db->transCommit();
        } catch (\Throwable $error) {
            $db->transRollback();
            return redirect()->to(site_url(self::URL))->with($flashKey, ['id' => $id, 'values' => $values, 'errors' => [$error->getMessage() ?: 'Data ' . $label . ' belum dapat disimpan.']]);
        }

        return redirect()->to(site_url(self::URL))->with('success', 'Data ' . $label . ' inventaris berhasil ' . ($id === null ? 'ditambahkan.' : 'diperbarui.'))->with($listFlashKey, true);
    }

    private function destroyInventoryMovement(bool $isLoan, int $id): RedirectResponse
    {
        $record = $isLoan ? $this->findInventoryLoan($id) : $this->findInventoryReturn($id);
        unset($record);
        $tables = $isLoan
            ? ['items' => 'agendaris_inventaris_peminjaman_item', 'foreign' => 'peminjaman_id', 'model' => new AgendarisInventarisPeminjamanModel(), 'label' => 'peminjaman', 'flash' => 'inventory_loan_list_open']
            : ['items' => 'agendaris_inventaris_pengembalian_item', 'foreign' => 'pengembalian_id', 'model' => new AgendarisInventarisPengembalianModel(), 'label' => 'pengembalian', 'flash' => 'inventory_return_list_open'];
        $db = db_connect();
        $items = $db->table($tables['items'])->select('inventaris_id')->where($tables['foreign'], $id)->get()->getResultArray();
        $ids = array_values(array_unique(array_map(static fn (array $item): int => (int) $item['inventaris_id'], $items)));
        try {
            $db->transBegin();
            $stockItems = $this->lockPickupStockItems($db, $ids);
            if (! $tables['model']->delete($id)) throw new \RuntimeException('Data belum dapat dihapus.');
            $this->syncInventoryLoanStock($db, $stockItems);
            if (! $db->transStatus()) throw new \RuntimeException('Stok inventaris belum dapat diperbarui.');
            $db->transCommit();
        } catch (\Throwable $error) {
            $db->transRollback();
            return redirect()->to(site_url(self::URL))->with('error', 'Data ' . $tables['label'] . ' belum dapat dihapus.')->with($tables['flash'], true);
        }
        return redirect()->to(site_url(self::URL))->with('success', 'Data ' . $tables['label'] . ' inventaris berhasil dihapus dan stok diperbarui.')->with($tables['flash'], true);
    }

    private function pickupRecords(): array
    {
        $pickups = (new AgendarisSouvenirPengambilanModel())
            ->orderBy('tanggal_pengambilan', 'DESC')
            ->orderBy('agendaris_souvenir_pengambilan.id', 'DESC')
            ->findAll();
        if ($pickups === []) return [];

        $pickupIds = array_map(static fn (array $pickup): int => (int) $pickup['id'], $pickups);
        $items = (new AgendarisSouvenirPengambilanItemModel())
            ->select('agendaris_souvenir_pengambilan_item.pengambilan_id, agendaris_souvenir_pengambilan_item.souvenir_id, agendaris_souvenir_pengambilan_item.jumlah_diambil, agendaris_souvenir_inventaris.nama_item, agendaris_souvenir_inventaris.satuan')
            ->join('agendaris_souvenir_inventaris', 'agendaris_souvenir_inventaris.id = agendaris_souvenir_pengambilan_item.souvenir_id')
            ->whereIn('agendaris_souvenir_pengambilan_item.pengambilan_id', $pickupIds)
            ->orderBy('agendaris_souvenir_pengambilan_item.id', 'ASC')
            ->findAll();
        $itemsByPickup = [];
        foreach ($items as $item) {
            $pickupId = (int) $item['pengambilan_id'];
            $quantity = (int) $item['jumlah_diambil'];
            $unit = self::UNITS[$item['satuan'] ?? 'pcs'] ?? ucfirst((string) ($item['satuan'] ?? 'pcs'));
            $itemsByPickup[$pickupId]['items'][] = ['souvenir_id' => (int) $item['souvenir_id'], 'jumlah_diambil' => $quantity];
            $itemsByPickup[$pickupId]['names'][] = $item['nama_item'] . ' (' . number_format($quantity, 0, ',', '.') . ' ' . $unit . ')';
        }
        foreach ($pickups as &$pickup) {
            $itemData = $itemsByPickup[(int) $pickup['id']] ?? ['items' => [['souvenir_id' => (int) $pickup['souvenir_id'], 'jumlah_diambil' => 1]], 'names' => []];
            $pickup['pickup_items'] = $itemData['items'];
            $pickup['nama_item'] = implode(', ', array_unique($itemData['names']));
        }
        unset($pickup);
        return $pickups;
    }

    private function returnRecords(): array
    {
        $returns = (new AgendarisSouvenirPengembalianModel())
            ->orderBy('tanggal_pengembalian', 'DESC')
            ->orderBy('agendaris_souvenir_pengembalian.id', 'DESC')
            ->findAll();
        if ($returns === []) return [];

        $returnIds = array_map(static fn (array $return): int => (int) $return['id'], $returns);
        $items = (new AgendarisSouvenirPengembalianItemModel())
            ->select('agendaris_souvenir_pengembalian_item.pengembalian_id, agendaris_souvenir_pengembalian_item.souvenir_id, agendaris_souvenir_pengembalian_item.jumlah_dikembalikan, agendaris_souvenir_inventaris.nama_item, agendaris_souvenir_inventaris.satuan')
            ->join('agendaris_souvenir_inventaris', 'agendaris_souvenir_inventaris.id = agendaris_souvenir_pengembalian_item.souvenir_id')
            ->whereIn('agendaris_souvenir_pengembalian_item.pengembalian_id', $returnIds)
            ->orderBy('agendaris_souvenir_pengembalian_item.id', 'ASC')
            ->findAll();
        $itemsByReturn = [];
        foreach ($items as $item) {
            $returnId = (int) $item['pengembalian_id'];
            $quantity = (int) $item['jumlah_dikembalikan'];
            $unit = self::UNITS[$item['satuan'] ?? 'pcs'] ?? ucfirst((string) ($item['satuan'] ?? 'pcs'));
            $itemsByReturn[$returnId]['items'][] = ['souvenir_id' => (int) $item['souvenir_id'], 'jumlah_dikembalikan' => $quantity];
            $itemsByReturn[$returnId]['names'][] = $item['nama_item'] . ' (' . number_format($quantity, 0, ',', '.') . ' ' . $unit . ')';
        }
        foreach ($returns as &$return) {
            $itemData = $itemsByReturn[(int) $return['id']] ?? ['items' => [['souvenir_id' => (int) $return['souvenir_id'], 'jumlah_dikembalikan' => 1]], 'names' => []];
            $return['return_items'] = $itemData['items'];
            $return['nama_item'] = implode(', ', array_unique($itemData['names']));
        }
        unset($return);
        return $returns;
    }

    private function inventoryMovementRecords(bool $isLoan): array
    {
        $tables = $isLoan
            ? ['model' => new AgendarisInventarisPeminjamanModel(), 'items' => 'agendaris_inventaris_peminjaman_item', 'foreign' => 'peminjaman_id', 'date' => 'tanggal_peminjaman', 'name' => 'nama_peminjam', 'quantity' => 'jumlah_dipinjam', 'item_key' => 'loan_items']
            : ['model' => new AgendarisInventarisPengembalianModel(), 'items' => 'agendaris_inventaris_pengembalian_item', 'foreign' => 'pengembalian_id', 'date' => 'tanggal_pengembalian', 'name' => 'nama_pengembali', 'quantity' => 'jumlah_dikembalikan', 'item_key' => 'return_items'];
        $records = $tables['model']->orderBy($tables['date'], 'DESC')->orderBy('id', 'DESC')->findAll();
        if ($records === []) return [];
        $ids = array_map(static fn (array $record): int => (int) $record['id'], $records);
        $items = db_connect()->table($tables['items'] . ' as movement_item')
            ->select('movement_item.' . $tables['foreign'] . ', movement_item.inventaris_id, movement_item.' . $tables['quantity'] . ', agendaris_souvenir_inventaris.nama_item, agendaris_souvenir_inventaris.satuan')
            ->join('agendaris_souvenir_inventaris', 'agendaris_souvenir_inventaris.id = movement_item.inventaris_id')
            ->whereIn('movement_item.' . $tables['foreign'], $ids)
            ->orderBy('movement_item.id', 'ASC')->get()->getResultArray();
        $byRecord = [];
        foreach ($items as $item) {
            $recordId = (int) $item[$tables['foreign']]; $quantity = (int) $item[$tables['quantity']];
            $unit = self::UNITS[$item['satuan'] ?? 'unit'] ?? ucfirst((string) ($item['satuan'] ?? 'unit'));
            $byRecord[$recordId]['items'][] = ['inventaris_id' => (int) $item['inventaris_id'], $tables['quantity'] => $quantity];
            $byRecord[$recordId]['names'][] = $item['nama_item'] . ' (' . number_format($quantity, 0, ',', '.') . ' ' . $unit . ')';
        }
        foreach ($records as &$record) {
            $itemData = $byRecord[(int) $record['id']] ?? ['items' => [['inventaris_id' => (int) $record['inventaris_id'], $tables['quantity'] => 1]], 'names' => []];
            $record[$tables['item_key']] = $itemData['items'];
            $record['nama_item'] = implode(', ', array_unique($itemData['names']));
        }
        unset($record);
        return $records;
    }

    private function inventoryLoanRecords(): array { return $this->inventoryMovementRecords(true); }
    private function inventoryReturnRecords(): array { return $this->inventoryMovementRecords(false); }

    /** @return list<array<string, mixed>> */
    private function inventoryItems(): array
    {
        return db_connect()->query(
            "SELECT id, nama_item, satuan, persediaan_akhir FROM agendaris_souvenir_inventaris WHERE deleted_at IS NULL AND LOWER(TRIM(jenis)) = ? ORDER BY nama_item ASC, id ASC",
            ['inventaris']
        )->getResultArray();
    }

    private function hasInventoryItems(array $ids): bool
    {
        if ($ids === []) return false;
        $rows = db_connect()->query(
            'SELECT id FROM agendaris_souvenir_inventaris WHERE deleted_at IS NULL AND LOWER(TRIM(jenis)) = ? AND id IN (' . implode(',', array_map('intval', $ids)) . ')',
            ['inventaris']
        )->getResultArray();
        return count($rows) === count($ids);
    }

    /** @return list<array<string, mixed>> */
    private function pickupSouvenirs(): array
    {
        return db_connect()->query(
            "SELECT id, nama_item, satuan, persediaan_akhir\n"
            . "FROM agendaris_souvenir_inventaris\n"
            . "WHERE deleted_at IS NULL AND LOWER(TRIM(jenis)) = ?\n"
            . "ORDER BY nama_item ASC, id ASC",
            ['souvenir']
        )->getResultArray();
    }

    private function findPickup(int $id): array
    {
        $record = (new AgendarisSouvenirPengambilanModel())->find($id);
        if ($record === null) throw PageNotFoundException::forPageNotFound('Data pengambilan souvenir tidak ditemukan.');
        return $record;
    }

    private function findReturn(int $id): array
    {
        $record = (new AgendarisSouvenirPengembalianModel())->find($id);
        if ($record === null) throw PageNotFoundException::forPageNotFound('Data pengembalian souvenir tidak ditemukan.');
        return $record;
    }

    private function findInventoryLoan(int $id): array
    {
        $record = (new AgendarisInventarisPeminjamanModel())->find($id);
        if ($record === null) throw PageNotFoundException::forPageNotFound('Data peminjaman inventaris tidak ditemukan.');
        return $record;
    }

    private function findInventoryReturn(int $id): array
    {
        $record = (new AgendarisInventarisPengembalianModel())->find($id);
        if ($record === null) throw PageNotFoundException::forPageNotFound('Data pengembalian inventaris tidak ditemukan.');
        return $record;
    }

    /** @return list<array{id: int, nama_item: string, persediaan_awal: int}> */
    private function lockPickupStockItems(object $db, array $souvenirIds): array
    {
        if ($souvenirIds === []) return [];

        $ids = array_values(array_unique(array_map('intval', $souvenirIds)));
        sort($ids, SORT_NUMERIC);
        $rows = $db->query(
            'SELECT id, nama_item, persediaan_awal FROM agendaris_souvenir_inventaris WHERE deleted_at IS NULL AND id IN (' . implode(',', $ids) . ') ORDER BY id ASC FOR UPDATE'
        )->getResultArray();

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'nama_item' => (string) $row['nama_item'],
            'persediaan_awal' => (int) $row['persediaan_awal'],
        ], $rows);
    }

    /** @param list<array{id: int, nama_item: string, persediaan_awal: int}> $stockItems */
    private function syncPickupStock(object $db, array $stockItems): void
    {
        if ($stockItems === []) return;

        $ids = array_column($stockItems, 'id');
        $usedRows = $db->table('agendaris_souvenir_pengambilan_item as pickup_item')
            ->select('pickup_item.souvenir_id, SUM(pickup_item.jumlah_diambil) as jumlah_diambil')
            ->join('agendaris_souvenir_pengambilan as pickup', 'pickup.id = pickup_item.pengambilan_id')
            ->whereIn('pickup_item.souvenir_id', $ids)
            ->where('pickup.deleted_at', null)
            ->groupBy('pickup_item.souvenir_id')
            ->get()
            ->getResultArray();
        $usedBySouvenir = [];
        foreach ($usedRows as $row) $usedBySouvenir[(int) $row['souvenir_id']] = (int) $row['jumlah_diambil'];

        $returnRows = $db->table('agendaris_souvenir_pengembalian_item as return_item')
            ->select('return_item.souvenir_id, SUM(return_item.jumlah_dikembalikan) as jumlah_dikembalikan')
            ->join('agendaris_souvenir_pengembalian as souvenir_return', 'souvenir_return.id = return_item.pengembalian_id')
            ->whereIn('return_item.souvenir_id', $ids)
            ->where('souvenir_return.deleted_at', null)
            ->groupBy('return_item.souvenir_id')
            ->get()
            ->getResultArray();
        $returnedBySouvenir = [];
        foreach ($returnRows as $row) $returnedBySouvenir[(int) $row['souvenir_id']] = (int) $row['jumlah_dikembalikan'];

        foreach ($stockItems as $stockItem) {
            $used = ($usedBySouvenir[$stockItem['id']] ?? 0) - ($returnedBySouvenir[$stockItem['id']] ?? 0);
            if ($used > $stockItem['persediaan_awal']) {
                throw new \RuntimeException('Jumlah pengambilan ' . $stockItem['nama_item'] . ' melebihi stok yang tersedia.');
            }
        }

        foreach ($stockItems as $stockItem) {
            $used = ($usedBySouvenir[$stockItem['id']] ?? 0) - ($returnedBySouvenir[$stockItem['id']] ?? 0);
            if (! $db->table('agendaris_souvenir_inventaris')->where('id', $stockItem['id'])->update([
                'persediaan_akhir' => $stockItem['persediaan_awal'] - $used,
                'terpakai' => max(0, $used),
            ])) {
                throw new \RuntimeException('Stok souvenir belum dapat diperbarui.');
            }
        }
    }

    /** @param list<array{id: int, nama_item: string, persediaan_awal: int}> $stockItems */
    private function syncInventoryLoanStock(object $db, array $stockItems): void
    {
        if ($stockItems === []) return;
        $ids = array_column($stockItems, 'id');
        $loanRows = $db->table('agendaris_inventaris_peminjaman_item as loan_item')
            ->select('loan_item.inventaris_id, SUM(loan_item.jumlah_dipinjam) as jumlah_dipinjam')
            ->join('agendaris_inventaris_peminjaman as loan', 'loan.id = loan_item.peminjaman_id')
            ->whereIn('loan_item.inventaris_id', $ids)->where('loan.deleted_at', null)->groupBy('loan_item.inventaris_id')->get()->getResultArray();
        $returnRows = $db->table('agendaris_inventaris_pengembalian_item as return_item')
            ->select('return_item.inventaris_id, SUM(return_item.jumlah_dikembalikan) as jumlah_dikembalikan')
            ->join('agendaris_inventaris_pengembalian as inventory_return', 'inventory_return.id = return_item.pengembalian_id')
            ->whereIn('return_item.inventaris_id', $ids)->where('inventory_return.deleted_at', null)->groupBy('return_item.inventaris_id')->get()->getResultArray();
        $loans = []; foreach ($loanRows as $row) $loans[(int) $row['inventaris_id']] = (int) $row['jumlah_dipinjam'];
        $returns = []; foreach ($returnRows as $row) $returns[(int) $row['inventaris_id']] = (int) $row['jumlah_dikembalikan'];
        foreach ($stockItems as $stockItem) {
            $used = ($loans[$stockItem['id']] ?? 0) - ($returns[$stockItem['id']] ?? 0);
            if ($used > $stockItem['persediaan_awal']) throw new \RuntimeException('Jumlah peminjaman ' . $stockItem['nama_item'] . ' melebihi stok yang tersedia.');
            if ($used < 0) throw new \RuntimeException('Jumlah pengembalian ' . $stockItem['nama_item'] . ' melebihi jumlah yang pernah dipinjam.');
            if (! $db->table('agendaris_souvenir_inventaris')->where('id', $stockItem['id'])->update(['persediaan_akhir' => $stockItem['persediaan_awal'] - $used, 'terpakai' => $used])) {
                throw new \RuntimeException('Stok inventaris belum dapat diperbarui.');
            }
        }
    }

    private function quantity(string $field): ?int
    {
        $value = $this->request->getPost($field);
        if (! is_string($value) || ! preg_match('/^(?:0|[1-9][0-9]{0,9})$/D', $value)) return null;
        $quantity = (int) $value;
        return $quantity <= 2147483647 ? $quantity : null;
    }

    private function positiveId(mixed $value): ?int
    {
        if (! is_string($value) || ! preg_match('/^[1-9][0-9]{0,9}$/D', $value)) return null;
        $id = (int) $value;
        return $id <= 2147483647 ? $id : null;
    }

    private function submittedPickupItems(): array
    {
        $ids = $this->request->getPost('souvenir_ids');
        $quantities = $this->request->getPost('jumlah_diambil');
        if (! is_array($ids) || ! is_array($quantities) || count($ids) !== count($quantities)) return [];
        $items = [];
        foreach ($ids as $index => $souvenirId) {
            $items[] = ['souvenir_id' => $souvenirId, 'jumlah_diambil' => $quantities[$index] ?? null];
        }
        return $items;
    }

    private function validPickupItems(array $items): array
    {
        $validItems = [];
        foreach ($items as $item) {
            $souvenirId = $this->positiveId($item['souvenir_id'] ?? null);
            $quantity = $this->quantityValue($item['jumlah_diambil'] ?? null);
            if ($souvenirId === null || $quantity === null) return [];
            $validItems[] = ['souvenir_id' => $souvenirId, 'jumlah_diambil' => $quantity];
        }
        return $validItems;
    }

    private function submittedReturnItems(): array
    {
        $ids = $this->request->getPost('souvenir_ids');
        $quantities = $this->request->getPost('jumlah_dikembalikan');
        if (! is_array($ids) || ! is_array($quantities) || count($ids) !== count($quantities)) return [];
        $items = [];
        foreach ($ids as $index => $souvenirId) {
            $items[] = ['souvenir_id' => $souvenirId, 'jumlah_dikembalikan' => $quantities[$index] ?? null];
        }
        return $items;
    }

    private function validReturnItems(array $items): array
    {
        $validItems = [];
        foreach ($items as $item) {
            $souvenirId = $this->positiveId($item['souvenir_id'] ?? null);
            $quantity = $this->quantityValue($item['jumlah_dikembalikan'] ?? null);
            if ($souvenirId === null || $quantity === null) return [];
            $validItems[] = ['souvenir_id' => $souvenirId, 'jumlah_dikembalikan' => $quantity];
        }
        return $validItems;
    }

    private function submittedInventoryItems(string $quantityField): array
    {
        $ids = $this->request->getPost('inventaris_ids');
        $quantities = $this->request->getPost($quantityField);
        if (! is_array($ids) || ! is_array($quantities) || count($ids) !== count($quantities)) return [];
        $items = [];
        foreach ($ids as $index => $inventarisId) $items[] = ['inventaris_id' => $inventarisId, $quantityField => $quantities[$index] ?? null];
        return $items;
    }

    private function validInventoryItems(array $items, string $quantityField): array
    {
        $validItems = [];
        foreach ($items as $item) {
            $inventarisId = $this->positiveId($item['inventaris_id'] ?? null);
            $quantity = $this->quantityValue($item[$quantityField] ?? null);
            if ($inventarisId === null || $quantity === null) return [];
            $validItems[] = ['inventaris_id' => $inventarisId, $quantityField => $quantity];
        }
        return $validItems;
    }

    private function quantityValue(mixed $value): ?int
    {
        if (! is_string($value) || ! preg_match('/^[1-9][0-9]{0,9}$/D', $value)) return null;
        $quantity = (int) $value;
        return $quantity <= 2147483647 ? $quantity : null;
    }

    private function dateValue(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $value, $matches)) return null;
        return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]) ? $value : null;
    }
}
