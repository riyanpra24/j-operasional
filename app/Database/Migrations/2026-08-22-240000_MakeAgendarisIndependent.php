<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeAgendarisIndependent extends Migration
{
    public function up()
    {
        $this->db->query(
            'ALTER TABLE agendaris '
            . 'DROP FOREIGN KEY agendaris_dokumen_masuk_fk, '
            . 'MODIFY dokumen_masuk_id BIGINT(20) UNSIGNED NULL, '
            . 'ADD pengirim VARCHAR(255) NULL AFTER dokumen_masuk_id, '
            . 'ADD penerima VARCHAR(255) NULL AFTER pengirim, '
            . 'ADD pengambilan VARCHAR(255) NULL AFTER penerima, '
            . 'ADD jenis VARCHAR(100) NULL AFTER pengambilan, '
            . 'ADD tanggal_diterima DATE NULL AFTER jenis'
        );

        $this->db->query(
            'UPDATE agendaris a JOIN dokumen_masuk d ON d.id = a.dokumen_masuk_id '
            . 'SET a.pengirim = d.pengirim, '
            . 'a.penerima = d.penerima, '
            . 'a.pengambilan = d.pengambilan, '
            . 'a.jenis = d.jenis, '
            . 'a.tanggal_diterima = d.tanggal'
        );

        $this->db->query(
            'ALTER TABLE agendaris '
            . 'MODIFY pengirim VARCHAR(255) NOT NULL, '
            . 'MODIFY tanggal_diterima DATE NOT NULL, '
            . 'ADD CONSTRAINT agendaris_dokumen_masuk_fk FOREIGN KEY (dokumen_masuk_id) '
            . 'REFERENCES dokumen_masuk(id) ON UPDATE CASCADE ON DELETE SET NULL'
        );
    }

    public function down()
    {
        $this->db->query('ALTER TABLE agendaris DROP FOREIGN KEY agendaris_dokumen_masuk_fk');
        $this->db->query('DELETE FROM agendaris WHERE dokumen_masuk_id IS NULL');
        $this->db->query(
            'ALTER TABLE agendaris '
            . 'MODIFY dokumen_masuk_id BIGINT(20) UNSIGNED NOT NULL, '
            . 'ADD CONSTRAINT agendaris_dokumen_masuk_fk FOREIGN KEY (dokumen_masuk_id) '
            . 'REFERENCES dokumen_masuk(id) ON UPDATE CASCADE ON DELETE CASCADE, '
            . 'DROP COLUMN pengirim, '
            . 'DROP COLUMN penerima, '
            . 'DROP COLUMN pengambilan, '
            . 'DROP COLUMN jenis, '
            . 'DROP COLUMN tanggal_diterima'
        );
    }
}
