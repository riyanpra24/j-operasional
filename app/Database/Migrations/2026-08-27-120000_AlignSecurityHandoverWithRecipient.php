<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlignSecurityHandoverWithRecipient extends Migration
{
    public function up()
    {
        // Koreksi riwayat pertama yang sebelumnya menggunakan nama pencatat
        // sebagai Security lama. Sumber yang benar adalah Penerima dokumen.
        $this->db->query(
            'UPDATE security_handover_history AS history '
            . 'INNER JOIN ('
            . 'SELECT dokumen_masuk_id, MIN(id) AS first_id '
            . 'FROM security_handover_history GROUP BY dokumen_masuk_id'
            . ') AS first_history ON first_history.first_id = history.id '
            . 'INNER JOIN dokumen_masuk AS dokumen ON dokumen.id = history.dokumen_masuk_id '
            . "SET history.security_dari = dokumen.penerima "
            . "WHERE TRIM(COALESCE(dokumen.penerima, '')) <> ''"
        );

        // Sinkronkan Penerima dengan Security tujuan terakhir untuk data serah
        // terima yang sudah dibuat sebelum alur baru diterapkan.
        $this->db->query(
            'UPDATE dokumen_masuk '
            . 'SET penerima = security_penanggung_jawab '
            . "WHERE TRIM(COALESCE(security_penanggung_jawab, '')) <> ''"
        );
    }

    public function down()
    {
        // Perubahan ini menyelaraskan data operasional dan tidak aman untuk
        // dibalik karena Penerima mungkin sudah diperbarui pada serah terima berikutnya.
    }
}
