<?php

/** Remove source figures/formulas while keeping the reference workbook's layout. */
if ($argc !== 2 || !is_file($argv[1])) {
    fwrite(STDERR, "Usage: php sanitize_export_template.php <workbook.xlsx>\n");
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($argv[1]) !== true) {
    fwrite(STDERR, "Cannot open template workbook.\n");
    exit(1);
}

$mainNamespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
$removed = 0;
try {
    $partsToDelete = [];
    for ($index = 0; $index < $zip->numFiles; $index++) {
        $name = $zip->getNameIndex($index);
        if (!is_string($name)) continue;
        if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) {
            $xml = new DOMDocument('1.0', 'UTF-8');
            if (!$xml->loadXML((string) $zip->getFromName($name), LIBXML_NONET)) {
                throw new RuntimeException("Invalid worksheet: $name");
            }
            $xpath = new DOMXPath($xml);
            $xpath->registerNamespace('m', $mainNamespace);
            foreach ($xpath->query('//m:sheetData/m:row/m:c') ?: [] as $cell) {
                if (!$cell instanceof DOMElement) continue;
                $reference = $cell->getAttribute('r');
                $isData = preg_match('/^([F-S])(\d{1,3})$/D', $reference, $matches)
                    && (int) $matches[2] >= 9 && (int) $matches[2] <= 176;
                if (!$isData && $reference !== 'R4') continue;
                foreach (iterator_to_array($cell->childNodes) as $child) {
                    if (in_array($child->localName, ['f', 'v', 'is'], true)) {
                        $cell->removeChild($child);
                        $removed++;
                    }
                }
                $cell->removeAttribute('t');
            }
            foreach ($xpath->query('//m:legacyDrawing') ?: [] as $node) {
                $node->parentNode?->removeChild($node);
            }
            $zip->addFromString($name, $xml->saveXML());
        } elseif (preg_match('#^xl/(?:comments\d+\.xml|drawings/vmldrawing\d*\.vml|persons/person\.xml)$#', $name)) {
            $partsToDelete[] = $name;
        } elseif (preg_match('#^xl/worksheets/_rels/sheet\d+\.xml\.rels$#', $name)) {
            $xml = new DOMDocument('1.0', 'UTF-8');
            $xml->loadXML((string) $zip->getFromName($name), LIBXML_NONET);
            $xpath = new DOMXPath($xml);
            $xpath->registerNamespace('p', 'http://schemas.openxmlformats.org/package/2006/relationships');
            foreach ($xpath->query('//p:Relationship[contains(@Type,"/comments") or contains(@Type,"/vmlDrawing")]') ?: [] as $node) {
                $node->parentNode?->removeChild($node);
            }
            $zip->addFromString($name, $xml->saveXML());
        }
    }
    foreach ($partsToDelete as $name) $zip->deleteName($name);

    foreach (['[Content_Types].xml', 'xl/_rels/workbook.xml.rels'] as $name) {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->loadXML((string) $zip->getFromName($name), LIBXML_NONET);
        $xpath = new DOMXPath($xml);
        $xpath->registerNamespace('p', $name === '[Content_Types].xml'
            ? 'http://schemas.openxmlformats.org/package/2006/content-types'
            : 'http://schemas.openxmlformats.org/package/2006/relationships');
        $query = $name === '[Content_Types].xml'
            ? '//p:Override[contains(@ContentType,"comments") or contains(@ContentType,"person")]'
            : '//p:Relationship[contains(@Type,"/person")]';
        foreach ($xpath->query($query) ?: [] as $node) $node->parentNode?->removeChild($node);
        $zip->addFromString($name, $xml->saveXML());
    }
} finally {
    $zip->close();
}

echo "Removed $removed source values/formulas.\n";
