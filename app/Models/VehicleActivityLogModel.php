<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleActivityLogModel extends Model
{
    protected $table = 'vehicle_activity_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'vehicle_id', 'vehicle_label', 'entity_type', 'entity_id', 'action', 'description', 'actor_name', 'created_at',
    ];
}
