<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RebasePksDocumentSequence extends Migration
{
    public function up()
    {
        // Pindahkan addendum sementara agar perubahan nomor tidak berbenturan
        // dengan unique key (kerjasama_id, urutan).
        $this->db->query("UPDATE pks_dokumen_kerjasama SET urutan = urutan + 100000 WHERE jenis_dokumen = 'Addendum'");
        $this->db->query("UPDATE pks_dokumen_kerjasama SET urutan = 0 WHERE jenis_dokumen = 'PKS'");
        $this->db->query("UPDATE pks_dokumen_kerjasama SET urutan = urutan - 100001 WHERE jenis_dokumen = 'Addendum'");
    }

    public function down()
    {
        $this->db->query("UPDATE pks_dokumen_kerjasama SET urutan = urutan + 100000 WHERE jenis_dokumen = 'Addendum'");
        $this->db->query("UPDATE pks_dokumen_kerjasama SET urutan = 1 WHERE jenis_dokumen = 'PKS'");
        $this->db->query("UPDATE pks_dokumen_kerjasama SET urutan = urutan - 99999 WHERE jenis_dokumen = 'Addendum'");
    }
}
