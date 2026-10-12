<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCompositionMappingToSdmEmployeeProfiles extends Migration
{
    public function up(): void
    {
        $fields = [];

        if (! $this->db->fieldExists('composition_position', 'sdm_employee_profiles')) {
            $fields['composition_position'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'position',
            ];
        }

        if (! $this->db->fieldExists('outsourcing_category', 'sdm_employee_profiles')) {
            $fields['outsourcing_category'] = [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'after'      => 'composition_position',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('sdm_employee_profiles', $fields);
        }

        $this->db->query("UPDATE sdm_employee_profiles
            SET employment_status = 'PKWT Umum'
            WHERE UPPER(TRIM(COALESCE(employment_status, ''))) IN ('KKWT', 'PKWT', 'PKWT UMUM')");
        $this->db->query("UPDATE sdm_employee_profiles
            SET employment_status = 'Outsourcing'
            WHERE UPPER(TRIM(COALESCE(employment_status, ''))) IN ('OS', 'OUTSORCING', 'OUTSOURCING')");
    }

    public function down(): void
    {
        foreach (['outsourcing_category', 'composition_position'] as $field) {
            if ($this->db->fieldExists($field, 'sdm_employee_profiles')) {
                $this->forge->dropColumn('sdm_employee_profiles', $field);
            }
        }
    }
}
