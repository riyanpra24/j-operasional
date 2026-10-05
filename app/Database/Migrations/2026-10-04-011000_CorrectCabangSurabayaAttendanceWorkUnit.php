<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CorrectCabangSurabayaAttendanceWorkUnit extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('sdm_attendance_imports') || ! $this->db->tableExists('sdm_attendance_records')) {
            return;
        }

        $this->db->query(
            "UPDATE sdm_attendance_imports AS imports
             SET work_unit = 'Kantor Cabang Surabaya'
             WHERE work_unit = 'Kantor Wilayah Surabaya'
               AND EXISTS (
                   SELECT 1 FROM sdm_attendance_records AS records
                   WHERE records.import_id = imports.id
                     AND records.organization LIKE '%Cabang Surabaya%'
               )
               AND NOT EXISTS (
                   SELECT 1 FROM sdm_attendance_records AS records
                   WHERE records.import_id = imports.id
                     AND (records.organization LIKE '%KW Surabaya%'
                       OR records.organization LIKE '%Kantor Wilayah Surabaya%')
               )"
        );
    }

    public function down(): void
    {
        // Perbaikan label unit kerja berdasarkan isi ESS tidak perlu dibalik.
    }
}
