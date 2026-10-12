<?php

namespace App\Models;

use CodeIgniter\Model;

final class SdmEmployeeProfileModel extends Model
{
    protected $table = 'sdm_employee_profiles';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'employee_no', 'employee_name', 'gender', 'work_unit', 'organization', 'position',
        'composition_position', 'outsourcing_category', 'employment_status', 'employment_date',
        'phone_number', 'corporate_email',
    ];
}
