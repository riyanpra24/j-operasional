<?php
namespace App\Models;
use CodeIgniter\Model;
final class AccountingLrImportModel extends Model
{
    protected $table='accounting_lr_imports';
    protected $primaryKey='id';
    protected $returnType='array';
    protected $useSoftDeletes=true;
    protected $allowedFields=['unit_name','report_year','report_month','report_basis','rule_version','result_json','source_name','source_hash','source_path','created_by_name','created_at','deleted_at','deleted_by_role','deleted_by_name'];
}
