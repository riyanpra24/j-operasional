<?php

namespace App\Models;

use CodeIgniter\Model;

/** Stores deliberate manual adjustments made from the Rekap Laba/Rugi editor. */
final class AccountingLrRecapOverrideModel extends Model
{
    protected $table = 'accounting_lr_recap_overrides';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'unit_name', 'report_year', 'report_month', 'values_json', 'updated_by_name',
    ];
}
