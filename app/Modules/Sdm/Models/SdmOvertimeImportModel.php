<?php

namespace App\Modules\Sdm\Models;

use CodeIgniter\Model;

final class SdmOvertimeImportModel extends Model
{
    protected $table = 'sdm_overtime_imports';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'source_name', 'source_hash', 'period_start', 'period_end', 'employee_count', 'row_count',
        'total_minutes', 'imported_by', 'imported_by_name',
    ];
}
