<?php

namespace App\Models;

use CodeIgniter\Model;

final class SdmAttendanceImportModel extends Model
{
    protected $table = 'sdm_attendance_imports';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = [
        'source_name', 'source_hash', 'work_unit', 'period_start', 'period_end', 'report_month', 'report_year', 'employee_count', 'row_count',
        'hadir_count', 'izin_count', 'alpa_count', 'incomplete_count', 'off_count', 'other_count',
        'warnings_json', 'imported_by', 'imported_by_name', 'deleted_by_role', 'deleted_by_name',
    ];
}
