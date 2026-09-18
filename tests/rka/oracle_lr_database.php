<?php
/** Disposable test period only; all inserts are rolled back. */
ob_start();
define('FCPATH',realpath(__DIR__.'/../../public').DIRECTORY_SEPARATOR);
define('ENVIRONMENT','development');
require __DIR__.'/../../app/Config/Paths.php';
$paths=new Config\Paths(); require $paths->systemDirectory.'/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
$db=db_connect(); $year=2097; $model=new App\Models\AccountingLrImportModel();
if ($model->where('report_year',$year)->countAllResults()!==0) throw new RuntimeException('Unused test period 2097 already contains data.');
$request=Config\Services::incomingrequest(new Config\App(),false); Config\Services::injectMock('request',$request);
$controller=new App\Controllers\Akutansi(); $controller->initController($request,Config\Services::response(null,false),Config\Services::logger());
$makeRecord=static function(string $amount,int $month,string $unit='Kanwil') use($model,$year): string {
    $result=['rule'=>App\Libraries\OracleLrSalaryParser::RULE,'unit'=>$unit,'sheet'=>strtoupper($unit),'year'=>$year,'month'=>$month,'period'=>'Periode : AGUSTUS-'.$year,
        'total'=>'999999.99','matches'=>[['row'=>23,'lob'=>'KUR','account'=>'6270201000000','description'=>'Beban gaji karyawan','report_label'=>'Beban gaji karyawan','source_amount'=>$amount,'amount'=>$amount]],'residue_count'=>0];
    if ($model->insert(['unit_name'=>$unit,'report_year'=>$year,'report_month'=>$month,'rule_version'=>App\Libraries\OracleLrSalaryParser::RULE,
        'result_json'=>json_encode($result,JSON_THROW_ON_ERROR),'source_name'=>'Test LR.xlsx','source_hash'=>str_repeat('0',64),'source_path'=>'test-only-no-file.xlsx','created_at'=>date('Y-m-d H:i:s')])===false) throw new RuntimeException('Test insert failed.');
    return (string)$model->getInsertID();
};
$render=static function(string $unit) use($controller,$request,$year): array {
    $request->setGlobal('get',['unit_kerja'=>$unit,'tahun'=>(string)$year]);
    $html=$controller->labaRugi(); $dom=new DOMDocument(); @$dom->loadHTML('<?xml encoding="UTF-8">'.$html); $xpath=new DOMXPath($dom);
    $values=$xpath->query('//table[contains(@class,"lr-profitloss-table")]//span[@class="lr-lr-value"]');
    $salary=$xpath->query('//th[normalize-space(.)="Beban gaji karyawan"]/following-sibling::td[1]');
    return [$html,$xpath,$values,$salary];
};
$db->transBegin();
try {
    $oldId=$makeRecord('100.01',8); $latestId=$makeRecord('-25.02',8); $makeRecord('555.55',7);
    [$html,$xpath,$values,$salary]=$render('Kanwil');
    if ($values->length<4 || trim($salary->item(0)->textContent)!=='*25'
        || $xpath->query('//span[@class="lr-lr-value"]/span[@class="lr-rka-negative-marker"]')->length<1) throw new RuntimeException('Latest YTD source, exact sign or formula calculation failed.');
    if ($xpath->query('//span[@class="lr-lr-value" and @data-lr-exact="-25.02"]')->length<1) throw new RuntimeException('Rounded presentation must retain the exact underlying amount.');
    if (!str_contains($html,'Lihat sumber perhitungan') || !str_contains($html,'6270201000000')) throw new RuntimeException('Source audit missing.');
    if ($xpath->query('//button[@data-lr-mapping-detail-open]')->length!==1 || $xpath->query('//dialog[@id="lrMappingDetailDialog"]')->length!==1) throw new RuntimeException('Mapping detail audit dialog missing.');
    if ($xpath->query('//button[@data-lr-toggle="beban-karyawan" and @aria-expanded="true"]')->length!==1) throw new RuntimeException('Imported salary details should be visible for verification.');
    [,,$corporate,$corporateSalary]=$render('Korporat Kanwil'); if ($corporate->length<4 || trim($corporateSalary->item(0)->textContent)!=='*25') throw new RuntimeException('Corporate view must consolidate current unit results.');
    [,,$other]=$render('Surabaya'); if ($other->length!==0) throw new RuntimeException('Unit filter leaked another unit source.');
    $record=$model->find($latestId); $multi=json_decode($record['result_json'],true);
    $multi['matches'][]=['row'=>200,'lob'=>'KUR','account'=>'test-claims','description'=>'Beban Klaim','report_label'=>'Beban Klaim','source_amount'=>'7.04','amount'=>'7.04'];
    $multi['matches'][]=['row'=>201,'lob'=>'KUR','account'=>'test-rent','description'=>'Sewa gudang','report_label'=>'Sewa gudang','source_amount'=>'-8.05','amount'=>'-8.05'];
    $multi['matches'][]=['row'=>202,'lob'=>'NON KUR','account'=>'test-nonkur','description'=>'Beban gaji karyawan','report_label'=>'Beban gaji karyawan','source_amount'=>'100.4999','amount'=>'100.50'];
    $multi['matches'][]=['row'=>203,'lob'=>'NON KUR','account'=>'test-nonkur','description'=>'Beban gaji karyawan','report_label'=>'Beban gaji karyawan','source_amount'=>'0.0002','amount'=>'0.00'];
    $model->update($latestId,['result_json'=>json_encode($multi,JSON_THROW_ON_ERROR)]);
    [, $multiXpath,$multiValues]=$render('Kanwil');
    if ($multiValues->length<8 || $multiXpath->query('//tr[@data-lr-expandable]//span[@data-lr-group-output and @aria-hidden="true"]')->length<1) throw new RuntimeException('Parent-group totals must be calculated and hidden while their details are expanded.');
    if (trim($multiXpath->query('//th[normalize-space(.)="Beban gaji karyawan"]/following-sibling::td[3]')->item(0)->textContent)!=='101' || $multiXpath->query('//span[@class="lr-lr-value" and @data-lr-exact="100.5001"]')->length<1) throw new RuntimeException('NON KUR must sum exact source values and round only presentation.');
    if (trim($multiXpath->query('//th[normalize-space(.)="Sewa gudang"]/following-sibling::td[1]')->item(0)->textContent)!=='*8') throw new RuntimeException('Administrative amount/sign not displayed correctly.');
    $model->update($latestId,['result_json'=>$record['result_json']]);
    if (($argv[1]??'')==='--write-preview') {
        $makeRecord('774895270.36',8); [$html]=$render('Kanwil');
        $html=preg_replace('/(<form\b[^>]*\baction=")[^"]*(")/i','$1#$2',$html);
        $html=str_replace(base_url(),'/',$html);
        $directory=__DIR__.'/../../tmp/rka_template/ui'; if (!is_dir($directory)) mkdir($directory,0777,true);
        file_put_contents($directory.'/laba_rugi.html',$html);
    }
    $latest=$model->where('unit_name','Kanwil')->where('report_year',$year)->orderBy('report_month','DESC')->orderBy('id','DESC')->first();
    $latestId=(string)$latest['id'];
    $request->setGlobal('post',['unit_kerja'=>'Kanwil','tahun'=>(string)$year,'bulan'=>'8']);
    $controller->deleteLabaRugi();
    if ($model->where('unit_name','Kanwil')->where('report_year',$year)->countAllResults()===0) throw new RuntimeException('Missing confirmation must not delete reports.');
    $service=new App\Libraries\OracleLrImportService();
    try { $service->delete('Kanwil',$year,13,'admin','Test'); throw new LogicException('Invalid month accepted.'); }
    catch (RuntimeException $e) { if (!str_contains($e->getMessage(),'tidak valid')) throw $e; }
    $otherId=$makeRecord('9.99',8,'Surabaya');
    [$branchHtml,,,$branchSalary]=$render('Surabaya');
    if (trim($branchSalary->item(0)->textContent)!=='10' || !str_contains($branchHtml,'Sheet SURABAYA')) throw new RuntimeException('Branch result must render from its own selected sheet.');
    $count=$service->delete('Kanwil',$year,8,'admin','LR Test');
    if ($count<2 || $model->where('unit_name','Kanwil')->where('report_year',$year)->where('report_month',8)->countAllResults()!==0
        || $model->where('unit_name','Kanwil')->where('report_year',$year)->where('report_month',7)->countAllResults()!==1) throw new RuntimeException('Deletion must hide only revisions in the selected month and year.');
    if ($model->find($otherId)===null || $model->onlyDeleted()->where('unit_name','Kanwil')->where('report_year',$year)->where('report_month',8)->countAllResults()!==$count) throw new RuntimeException('Deletion leaked months or units, or lost recoverable history.');
    [,,$deletedValues,$fallbackSalary]=$render('Kanwil');
    if ($deletedValues->length<4 || trim($fallbackSalary->item(0)->textContent)!=='556') throw new RuntimeException('Deleting August must leave the July report available.');
    session()->set('auth_role','admin');
    $deletedController=new App\Controllers\DeletedData(); $deletedController->initController($request,Config\Services::response(null,false),Config\Services::logger());
    $deletedController->restore('laporan-laba-rugi',(int)$latestId);
    if ($model->find($latestId)===null) throw new RuntimeException('Administrator restoration failed.');
    session()->remove('auth_role');
    echo "Oracle LR deletion: mandatory confirmation, exact month/year scope, revision history, isolation and administrator recovery OK.\n";
    echo "Oracle LR DB/render: persisted source rows, exact recomputation, no duplicate-upload summation, latest YTD period, isolated units and red negative marker OK.\n";
} finally { $db->transRollback(); }
if ($model->where('report_year',$year)->countAllResults()!==0) throw new RuntimeException('Test records retained.');
