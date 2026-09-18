<?php

namespace App\Models;

use CodeIgniter\Model;

final class AccountingRkaBudgetModel extends Model
{
    protected $table = 'accounting_rka_budgets';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = ['unit_name', 'budget_year', 'template_version', 'inputs_json', 'calculated_json', 'source_type', 'source_name', 'source_hash', 'revision', 'updated_by', 'updated_by_name', 'created_at', 'updated_at'];
}
