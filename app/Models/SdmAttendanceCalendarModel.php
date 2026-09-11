<?php

namespace App\Models;

use CodeIgniter\Model;

final class SdmAttendanceCalendarModel extends Model
{
    protected $table = 'sdm_attendance_calendar';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'calendar_date', 'is_holiday', 'label', 'updated_by', 'updated_by_name', 'updated_by_role',
    ];
}
