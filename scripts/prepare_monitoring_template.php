<?php
// Preserve each worksheet's layout and labels, without retaining old report data.
if ($argc !== 3) throw new RuntimeException('Usage: php prepare_monitoring_template.php source.xlsx destination-directory');
$zip = new ZipArchive();
if ($zip->open($argv[1]) !== true) throw new RuntimeException('Cannot open source workbook.');
$ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
$shared = new DOMDocument();
if (!$shared->loadXML($zip->getFromName('xl/sharedStrings.xml'), LIBXML_NONET)) throw new RuntimeException('Shared strings are missing.');
$strings = [];
foreach ($shared->getElementsByTagNameNS($ns, 'si') as $item) $strings[] = $item->textContent;
if (!is_dir($argv[2]) && !mkdir($argv[2], 0770, true)) throw new RuntimeException('Cannot create template directory.');
$components = ['styles.xml' => $zip->getFromName('xl/styles.xml'), 'theme.xml' => $zip->getFromName('xl/theme/theme1.xml')];
for ($index = 1; $index <= 7; $index++) {
    $sheet = $zip->getFromName('xl/worksheets/sheet' . $index . '.xml');
    $doc = new DOMDocument();
    if ($sheet === false || !$doc->loadXML($sheet, LIBXML_NONET)) throw new RuntimeException('Invalid template sheet ' . $index);
    $xp = new DOMXPath($doc); $xp->registerNamespace('m', $ns);
    foreach ($xp->query('//m:sheetData/m:row') as $row) {
        $row->removeAttribute('hidden');
        foreach ($xp->query('./m:c', $row) as $cell) {
            $ref = $cell->getAttribute('r');
            $number = (int) substr($ref, 1);
            $keepLabel = $ref[0] === 'B' && $number >= ($index === 1 ? 3 : 4) && $number <= ($index === 1 ? 64 : 64);
            $label = $keepLabel && $cell->getAttribute('t') === 's' ? ($strings[(int) $cell->textContent] ?? '') : '';
            while ($cell->firstChild) $cell->removeChild($cell->firstChild);
            $cell->removeAttribute('t');
            if ($label !== '') {
                $cell->setAttribute('t', 'inlineStr');
                $is = $doc->createElementNS($ns, 'is'); $t = $doc->createElementNS($ns, 't');
                $t->appendChild($doc->createTextNode($label)); $is->appendChild($t); $cell->appendChild($is);
            }
        }
    }
    foreach ($xp->query('//m:autoFilter') as $filter) while ($filter->firstChild) $filter->removeChild($filter->firstChild);
    foreach ($xp->query('//m:pageSetup') as $setup) $setup->removeAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
    // These source parts reference old workbooks or printers and are not needed in exports.
    foreach ($xp->query('//m:legacyDrawing | //m:drawing | //m:extLst') as $node) $node->parentNode->removeChild($node);
    $components['sheet' . $index . '.xml'] = $doc->saveXML();
}
$zip->close();
foreach ($components as $name => $data) {
    if ($data === false || file_put_contents($argv[2] . DIRECTORY_SEPARATOR . $name, $data) === false) throw new RuntimeException('Cannot write template component.');
}
