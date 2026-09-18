<?php
require __DIR__ . '/../../app/Libraries/RkaMoney.php';
require __DIR__ . '/../../app/Libraries/RkaCalculator.php';
require __DIR__ . '/../../app/Libraries/RkaWorkbookParser.php';
$path = 'C:/Users/Jamkrindo/Documents/JAKSA/RKA_All Uker Kanwil Surabaya.xlsx';
try {
    $parsed = (new App\Libraries\RkaWorkbookParser())->parseAll($path);
    echo "SYSTEM IMPORT: ACCEPTED\n";
    foreach ($parsed as $unit=>$budget) {
        echo $unit . ' year=' . $budget['year'] . ' inputs=' . count($budget['inputs']) . ' profit=' . $budget['calculated']['H166'] . "\n";
    }
} catch (Throwable $exception) { echo 'SYSTEM IMPORT: REJECTED: ' . $exception->getMessage() . "\n"; }
$zip = new ZipArchive();$zip->open($path);
$ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
$shared = simplexml_load_string($zip->getFromName('xl/sharedStrings.xml'));$shared->registerXPathNamespace('s',$ns);
$strings=[];foreach ($shared->xpath('//s:si') as $item) { $item->registerXPathNamespace('s',$ns);$strings[]=implode('',array_map('strval',$item->xpath('.//s:t'))); }
$book=simplexml_load_string($zip->getFromName('xl/workbook.xml'));$book->registerXPathNamespace('s',$ns);
$relations=simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));$targets=[];
foreach ($relations->children('http://schemas.openxmlformats.org/package/2006/relationships') as $r) { $a=$r->attributes();$targets[(string)$a['Id']]='xl/'.(string)$a['Target']; }
foreach ($book->xpath('//s:sheets/s:sheet') as $info) {
    $id=(string)$info->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
    $sheet=simplexml_load_string($zip->getFromName($targets[$id]));$sheet->registerXPathNamespace('s',$ns);
    $cells=[];foreach($sheet->xpath('//s:sheetData/s:row/s:c') as $c) {
        $v=$c->children($ns);$value=isset($v->v)?(string)$v->v:'';
        if((string)$c['t']==='s')$value=$strings[(int)$value];
        if((string)$c['t']==='inlineStr') { $c->registerXPathNamespace('s',$ns);$value=implode('',array_map('strval',$c->xpath('.//s:is//s:t'))); }
        $cells[(string)$c['r']]=['value'=>$value,'formula'=>isset($v->f)?(string)$v->f:null,'type'=>(string)$c['t']];
    }
    echo 'SHEET: '.(string)$info['name']."\n";
    if (($argv[1] ?? '') === '--compact') {
        $schema=App\Libraries\RkaCalculator::schema();$inputs=[];
        foreach ($schema['input_rows'] as $row) {
            foreach (array_combine(range('C','G'),range('F','J')) as $systemColumn=>$sourceColumn) {
                $inputs[$systemColumn.$row]=App\Libraries\RkaMoney::decimal(($cells[$sourceColumn.$row]['value']??'')?:'0');
            }
        }
        if (!isset($parsed)) throw new RuntimeException('Cannot reconcile a rejected workbook.');
        $unit = array_values(array_filter(App\Libraries\RkaCalculator::UNITS,static fn($unit)=>mb_strtolower($unit)===mb_strtolower((string)$info['name'])))[0];
        $values=$parsed[$unit]['calculated'];$compared=0;$differences=[];
        foreach ($values as $address=>$amount) {
            preg_match('/^([C-H])(\d+)$/',$address,$match);$sourceAddress=chr(ord($match[1])+3).$match[2];
            $cache=$cells[$sourceAddress]['value']??'';
            if ($cache === '') continue;
            $compared++;
            if (App\Libraries\RkaMoney::decimal($cache)!==$amount) $differences[]=$sourceAddress;
        }
        echo 'MAPPED SYSTEM CALCULATOR: compared='.$compared.' differences='.count($differences).' total_profit='.$values['H166']."\n";
        continue;
    }
    foreach ([1,2,5,6,8,10,11,12,13,14,16,17,18,19,20,21,23,25,27,28,29,51,52,54,55,141,142,144,145,159,160,162,164,166] as $row) {
        $selected=[];foreach(range('A','J') as $column) { $cell=$cells[$column.$row]??null;if($cell!==null&&($cell['value']!==''||$cell['formula']!==null))$selected[$column]=$cell; }
        echo $row.': '.json_encode($selected,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
    }
}
$zip->close();
