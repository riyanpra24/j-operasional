<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
final class CreateOracleLrImports extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'=>['type'=>'BIGINT','unsigned'=>true,'auto_increment'=>true],
            'unit_name'=>['type'=>'VARCHAR','constraint'=>80],
            'report_year'=>['type'=>'SMALLINT','unsigned'=>true],
            'report_month'=>['type'=>'TINYINT','unsigned'=>true],
            'rule_version'=>['type'=>'VARCHAR','constraint'=>40],
            'result_json'=>['type'=>'LONGTEXT'],
            'source_name'=>['type'=>'VARCHAR','constraint'=>255],
            'source_hash'=>['type'=>'CHAR','constraint'=>64],
            'source_path'=>['type'=>'VARCHAR','constraint'=>255],
            'created_by_name'=>['type'=>'VARCHAR','constraint'=>150,'null'=>true],
            'created_at'=>['type'=>'DATETIME'],
        ]);
        $this->forge->addKey('id',true);
        $this->forge->addKey(['unit_name','report_year','report_month']);
        $this->forge->createTable('accounting_lr_imports',true,['ENGINE'=>'InnoDB']);
    }
    public function down(): void { $this->forge->dropTable('accounting_lr_imports',true); }
}
