<?php

namespace App\Models;

use CodeIgniter\Model;

final class SdmOvertimeRecordModel extends Model
{
    protected $table = 'sdm_overtime_records';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'import_id', 'employee_key', 'employee_no', 'employee_name', 'organization', 'overtime_date', 'shift',
        'actual_in', 'actual_out', 'overtime_start', 'overtime_end', 'overtime_minutes', 'overtime_hours',
        'request_no', 'request_date', 'overtime_type', 'reason', 'remark', 'hour_type', 'request_start',
        'request_end', 'request_status',
    ];
}
