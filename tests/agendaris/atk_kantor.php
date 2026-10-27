<?php

function esc($value, $context = null): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function site_url(string $path): string { return '/' . $path; }
function csrf_field(): string { return '<input type="hidden" name="csrf_test" value="token">'; }
function view(string $path, array $data = []): string { return ''; }

$renderer = new class {
    public function extend($layout): string { return ''; }
    public function section($name): string { return ''; }
    public function endSection(): string { return ''; }
    public function render(): string
    {
        $title = 'ATK Kantor';
        $records = [[
            'id' => 3, 'nama_barang' => 'Pulpen', 'merek' => 'Standard', 'stok_barang' => 24,
            'terpakai' => 6, 'satuan' => 'Pcs', 'keterangan' => 'Untuk kebutuhan administrasi',
        ]];
        $filters = ['q' => '', 'stok' => '', 'perPage' => 10];
        $pickupRecords = [['id' => 8, 'nama_pengambil' => 'Dhia Shofia', 'nama_barang' => 'Pulpen', 'jumlah_diambil' => '2 Pcs', 'tanggal_pengambilan' => '2026-10-26']];
        $pickupAtks = [['id' => 3, 'nama_barang' => 'Pulpen', 'stok_barang' => 24, 'satuan' => 'Pcs']];
        $pickupRecipients = ['Dhia Shofiah Mardiana'];
        $pickupYears = [2026];
        $pager = new class { public function links(string $group, string $template): string { return '<nav>Halaman</nav>'; } };
        $popupState = null;
        ob_start(); include __DIR__ . '/../../app/Views/agendaris/atk_kantor/index.php'; return ob_get_clean();
    }
};

$document = new DOMDocument();
@$document->loadHTML('<?xml encoding="UTF-8">' . $renderer->render());
$xpath = new DOMXPath($document);
if ($xpath->query('//table[contains(@class,"atk-table")]//th')->length !== 6
    || $xpath->query('//table[contains(@class,"atk-table")]//th[text()="Nama Barang"]')->length !== 1
    || $xpath->query('//table[contains(@class,"atk-table")]//th[text()="Merek"]')->length !== 1
    || $xpath->query('//table[contains(@class,"atk-table")]//th[text()="Stok Barang"]')->length !== 1
    || $xpath->query('//table[contains(@class,"atk-table")]//th[text()="Terpakai"]')->length !== 1
    || $xpath->query('//table[contains(@class,"atk-table")]//th[text()="Keterangan"]')->length !== 1) {
    throw new RuntimeException('Kolom tabel ATK Kantor belum lengkap.');
}
if ($xpath->query('//form[contains(@class,"atk-filter-form")]//input[@name="q"]')->length !== 1
    || $xpath->query('//form[contains(@class,"atk-filter-form")]//select[@name="stok"]/option')->length !== 3
    || $xpath->query('//form[contains(@class,"atk-filter-form")]//select[@name="kondisi"]')->length !== 0
    || $xpath->query('//form[contains(@class,"atk-filter-form")]//a[contains(@href,"agendaris/atk-kantor") and text()="Reset"]')->length !== 1
    || $xpath->query('//select[@name="per_page" and @data-table-length]')->length !== 1) {
    throw new RuntimeException('Filter global ATK Kantor belum lengkap.');
}
if ($xpath->query('//button[@data-atk-create]')->length !== 1
    || $xpath->query('//button[@data-atk-pickup-menu-open and text()="Pengambilan ATK"]')->length !== 1
    || $xpath->query('//button[@data-atk-proof-open and text()="Bukti Pengambilan ATK"]')->length !== 1
    || $xpath->query('//button[@data-atk-view="3"]')->length !== 1
    || $xpath->query('//button[@data-atk-edit="3"]')->length !== 1
    || $xpath->query('//button[@data-atk-delete="3"]')->length !== 1) {
    throw new RuntimeException('Aksi CRUD ATK Kantor belum lengkap.');
}
if ($xpath->query('//div[@id="atkProofModal"]//th[text()="Nama"]')->length !== 1
    || $xpath->query('//div[@id="atkProofModal"]//th[text()="Nama Barang / ATK"]')->length !== 1
    || $xpath->query('//div[@id="atkProofModal"]//th[text()="Tanggal Pengambilan"]')->length !== 1
    || $xpath->query('//div[@id="atkProofModal"]//th[text()="Jumlah"]')->length !== 1
    || $xpath->query('//div[@id="atkProofModal"]//th[text()="Aksi"]')->length !== 1
    || $xpath->query('//form[@data-atk-proof-filter]//input[@data-atk-proof-search]')->length !== 1
    || $xpath->query('//form[@data-atk-proof-filter]//input[@data-atk-proof-date]')->length !== 1
    || $xpath->query('//form[@data-atk-proof-filter]//button[@data-atk-proof-download-open and text()="Unduh Bukti"]')->length !== 1
    || $xpath->query('//div[@id="atkProofModal"]//button[@title="Unduh dokumen menunggu instruksi"]')->length !== 0
    || $xpath->query('//div[@id="atkProofModal"]//form[contains(@action,"pengambilan/8/hapus")]')->length !== 1) {
    throw new RuntimeException('Popup bukti pengambilan ATK belum lengkap.');
}
if ($xpath->query('//div[@id="atkProofDownloadModal"]//form[contains(@action,"agendaris/atk-kantor/pengambilan/bukti")]')->length !== 1
    || $xpath->query('//div[@id="atkProofDownloadModal"]//select[@name="bulan"]/option[text()="Januari"]')->length !== 1
    || $xpath->query('//div[@id="atkProofDownloadModal"]//select[@name="tahun"]/option[text()="2026"]')->length !== 1
    || $xpath->query('//div[@id="atkProofDownloadModal"]//button[text()="Unduh"]')->length !== 1) {
    throw new RuntimeException('Popup unduh bukti pengambilan ATK belum lengkap.');
}
if ($xpath->query('//div[@id="atkPickupFormModal"]//select[@name="nama_pengambil" and @required]//option[text()="Dhia Shofiah Mardiana"]')->length !== 1
    || $xpath->query('//div[@id="atkPickupFormModal"]//input[@name="tanggal_pengambilan" and @type="date"]')->length !== 1
    || $xpath->query('//div[@id="atkPickupFormModal"]//select[@name="atk_ids[]"]//option[@value="3"]')->length !== 1
    || $xpath->query('//div[@id="atkPickupFormModal"]//input[@name="jumlah_diambil[]" and @value="1"]')->length !== 1
    || $xpath->query('//div[@id="atkPickupFormModal"]//*[@data-atk-pickup-unit]')->length !== 1
    || $xpath->query('//div[@id="atkPickupFormModal"]//input[@name="divisi_bagian"]')->length !== 0
    || $xpath->query('//div[@id="atkPickupFormModal"]//input[@name="keperluan_kegiatan"]')->length !== 0) {
    throw new RuntimeException('Form tambah pengambilan ATK belum lengkap.');
}
if ($xpath->query('//div[@id="atkFormModal"]//input[@name="nama_barang" and @required]')->length !== 1
    || $xpath->query('//div[@id="atkFormModal"]//input[@name="merek"]')->length !== 1
    || $xpath->query('//div[@id="atkFormModal"]//input[@name="stok_barang" and @type="number"]')->length !== 1
    || $xpath->query('//div[@id="atkFormModal"]//input[@name="jumlah"]')->length !== 0
    || $xpath->query('//div[@id="atkFormModal"]//select[@name="satuan"]/option[text()="Pcs"]')->length !== 1
    || $xpath->query('//div[@id="atkFormModal"]//select[@name="satuan"]/option[text()="Lembar"]')->length !== 1
    || $xpath->query('//div[@id="atkFormModal"]//input[@name="kondisi"]')->length !== 0
    || $xpath->query('//div[@id="atkFormModal"]//textarea[@name="keterangan"]')->length !== 1
    || $xpath->query('//div[@id="atkDeleteModal"]//form[@data-atk-delete-form]')->length !== 1) {
    throw new RuntimeException('Popup formulir ATK Kantor belum lengkap.');
}

echo "ATK Kantor markup: passed\n";
