<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class NormalizeSdmManagerTitles extends Migration
{
    public function up(): void
    {
        $this->db->query(
            "UPDATE sdm_employee_profiles
             SET position = CONCAT('Manajer', SUBSTRING(position, 8))
             WHERE LOWER(position) = 'manager' OR LOWER(position) LIKE 'manager %'"
        );
    }

    public function down(): void
    {
        $this->db->query(
            "UPDATE sdm_employee_profiles
             SET position = CONCAT('Manager', SUBSTRING(position, 8))
             WHERE LOWER(position) = 'manajer' OR LOWER(position) LIKE 'manajer %'"
        );
    }
}
