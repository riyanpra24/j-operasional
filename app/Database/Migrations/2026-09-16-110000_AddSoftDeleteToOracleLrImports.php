<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
final class AddSoftDeleteToOracleLrImports extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('accounting_lr_imports',[
            'deleted_at'=>['type'=>'DATETIME','null'=>true],
            'deleted_by_role'=>['type'=>'VARCHAR','constraint'=>80,'null'=>true],
            'deleted_by_name'=>['type'=>'VARCHAR','constraint'=>150,'null'=>true],
        ]);
    }
    public function down(): void { $this->forge->dropColumn('accounting_lr_imports',['deleted_at','deleted_by_role','deleted_by_name']); }
}
