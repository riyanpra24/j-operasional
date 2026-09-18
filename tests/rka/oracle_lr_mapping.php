<?php
/** Transactional checks for administrator-managed Oracle mappings. */
ob_start();
define('FCPATH',realpath(__DIR__.'/../../public').DIRECTORY_SEPARATOR);
define('ENVIRONMENT','development');
require __DIR__.'/../../app/Config/Paths.php';
$paths=new Config\Paths(); require $paths->systemDirectory.'/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$service=new App\Libraries\OracleLrMappingService();
$db=db_connect(); $checks=0;
$assert=static function(bool $condition,string $message) use (&$checks): void { if(!$condition) throw new RuntimeException($message); $checks++; };

$targets=array_column($service->lobMappings(),'target_column');
$assert($targets===['KUR','NON KUR','PEN'],'LOB defaults must follow the three Excel helper blocks only.');
$assert($service->classify('NON KUR','Penjaminan Produktif Mikro')['target_column']==='NON KUR','NON KUR must not be split into Productive.');
$assert($service->classify('NON KUR','Penjaminan KBG dan Suretyship')['target_column']==='NON KUR','NON KUR must not be split into KBG/Suretyship.');
$assert($service->classify('PEN','Penjaminan PEN KMK PEN')['target_column']==='PEN','PEN must remain separate.');
$branch=$service->accountMap('Surabaya');
$assert(isset($branch['beban gaji karyawan']) && !isset($branch['beban klaim']),'Branch account scope must preserve the approved claim exclusions.');
$assert(isset($branch['pendapatan premi penjaminan kredit'])
    && $branch['pendapatan premi penjaminan kredit']['report_label']==='Imbal Jasa Penjaminan Bruto'
    && $branch['pendapatan premi penjaminan kredit']['sign_mode']==='invert',
    'Every branch must prepare premium revenue, including a future PEN source, with the approved sign.');
$assert(isset($branch['beban pajak pph 21 non karywan'])
    && $branch['beban pajak pph 21 non karywan']['report_label']==='Pendapatan Subrogasi'
    && $branch['beban pajak pph 21 non karywan']['sign_mode']==='invert',
    'Every branch must prepare the exact Oracle PPh 21 Non Karywan source as an optional subrogation component.');
$assert(App\Libraries\LrReportRows::branchFormulaSourceSegments('Beban Pajak PPh 21 Non Karywan')===['NON KUR'],
    'The optional PPh 21 Non Karywan subrogation component must never leak into KUR or PEN.');
$assert(count(App\Libraries\LrReportRows::branchFormulaSourceMappings())===11,
    'Every approved guarantee and claim workpaper source must have an explicit rule.');
$assert(isset($branch['beban klaim penjaminan kredit bruto'],$branch['kenaikan/penurunan estimasi liabilitas klaim - penjaminan']),
    'Claim and corrected reserve sources must be available in every branch.');
$assert(!isset($branch['kenaikan/penurunan estimasi liabilitas klaim - penjaminan ulang']),
    'The erroneous Surabaya reinsurance reserve source must remain excluded.');
$assert(($branch['pendapatan jasa giro']['sign_mode']??null)==='invert' && isset($branch['tranportasi dinas luar negeri']),
    'All-unit other-income signs and the verified outside-travel Oracle spelling must be active.');

$db->transBegin();
try {
    $service->saveAccount([
        'unit_scope'=>'branch','source_description'=>'Akun uji mapping','report_label'=>'Beban gaji karyawan',
        'sign_mode'=>'invert','is_active'=>'1',
    ],'Mapping Test');
    $map=$service->accountMap('Surabaya');
    $assert(isset($map['akun uji mapping']) && $map['akun uji mapping']['sign_mode']==='invert','Saved account mapping must be active for branches.');
    $assert($service->calculationValue('invert','-100.2501','SURABAYA')==='100.2501','Configured sign inversion must preserve precision.');
    try {
        $service->saveAccount([
            'unit_scope'=>'branch','source_description'=>'  akun   uji mapping ','report_label'=>'Beban lembur karyawan',
            'sign_mode'=>'keep','is_active'=>'1',
        ],'Mapping Test');
        throw new LogicException('Normalized duplicate mapping was accepted.');
    } catch (RuntimeException $e) {
        $assert(str_contains($e->getMessage(),'sudah memiliki mapping'),'Duplicate validation must be explicit.');
    }
} finally {
    $db->transRollback();
}

$request=Config\Services::incomingrequest(new Config\App(),false); Config\Services::injectMock('request',$request);
$controller=new App\Controllers\Akutansi(); $controller->initController($request,Config\Services::response(null,false),Config\Services::logger());
session()->set('auth_role','admin');
$html=$controller->oracleMappings();
$assert(is_string($html) && str_contains($html,'Pengaturan Mapping Oracle') && str_contains($html,'Description COA sumber'),'Administrator mapping page must render both mapping levels.');
$dom=new DOMDocument(); @$dom->loadHTML('<?xml encoding="UTF-8">'.$html); $xpath=new DOMXPath($dom);
$assert($xpath->query('//span[contains(@class,"oracle-mapping-locked-status")]')->length===3
    && $xpath->query('//input[@data-mapping-search]')->length===1
    && $xpath->query('//form[contains(@action,"pengaturan-mapping-oracle/lob")]')->length===0,
    'KUR, NON KUR and PEN must be visibly locked while COA mappings remain searchable.');
$assert($xpath->query('//form[contains(@action,"pengaturan-mapping-oracle/coa")]')->length>100,'Seeded COA mappings must be editable by the administrator.');
session()->set('auth_role','akutansi');
$denied=$controller->oracleMappings();
$assert($denied instanceof CodeIgniter\HTTP\RedirectResponse,'Non-administrator must be denied server-side.');
session()->remove('auth_role');

echo "Oracle LR mapping: {$checks} checks OK; base groups follow Excel and realization product formulas remain separately controlled.\n";
