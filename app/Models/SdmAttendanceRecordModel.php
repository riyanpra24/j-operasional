<?php

namespace App\Models;

use CodeIgniter\Model;

final class SdmAttendanceRecordModel extends Model
{
    protected $table = 'sdm_attendance_records';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'import_id', 'employee_key', 'employee_no', 'employee_name', 'position', 'organization',
        'attendance_date', 'shift', 'actual_in', 'actual_out', 'day_type', 'raw_status',
        'other_status', 'remark', 'recap_code',
    ];
}
