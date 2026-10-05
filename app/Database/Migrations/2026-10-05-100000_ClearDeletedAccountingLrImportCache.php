<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class ClearDeletedAccountingLrImportCache extends Migration
{
    public function up(): void
    {
        // Deleted reports can be restored from their original workbook.  Clear the
        // stored calculation now so historical values can never be reused as cache.
        $this->db->table('accounting_lr_imports')
            ->where('deleted_at IS NOT NULL', null, false)
            ->update(['result_json' => '{}']);
    }

    public function down(): void
    {
        // The discarded snapshots are intentionally not recoverable.
    }
}
