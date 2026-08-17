<?php
/**
 * SFI Queuing System - Minimal XLSX Writer (dependency-free)
 * Builds a real .xlsx file from a header row + data rows using ZipArchive.
 * Cells are written as inline strings so no shared strings table is needed.
 */

/**
 * Escape a value for XML output.
 */
function xlsxEscape($value) {
    return htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

/**
 * Convert a zero-based column index to an Excel column letter (A, B, ..., AA...).
 */
function xlsxColumnName($index) {
    $letters = '';
    $index++; // 0-based -> 1-based
    while ($index > 0) {
        $mod = ($index - 1) % 26;
        $letters = chr(65 + $mod) . $letters;
        $index = intdiv($index - 1, 26);
    }
    return $letters;
}

/**
 * Build an Excel 2007+ (.xlsx) file as a binary string.
 *
 * @param array $headers Column names (strings).
 * @param array $rows    Array of rows; each row is an indexed array matching $headers.
 * @return string Binary .xlsx content.
 */
function xlsxFromRows(array $headers, array $rows) {
    $colCount = count($headers);

    // Build the worksheet XML with inline string cells
    $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<x:worksheet xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
        . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<x:sheetData>';

    // Header row
    $rowNum = 1;
    $sheet .= '<x:row>';
    foreach ($headers as $i => $h) {
        $sheet .= '<x:c r="' . xlsxColumnName($i) . $rowNum . '" t="inlineStr"><x:is><x:t>' . xlsxEscape($h) . '</x:t></x:is></x:c>';
    }
    $sheet .= '</x:row>';

    // Data rows
    foreach ($rows as $row) {
        $rowNum++;
        $sheet .= '<x:row>';
        for ($i = 0; $i < $colCount; $i++) {
            $v = isset($row[$i]) ? $row[$i] : '';
            $sheet .= '<x:c r="' . xlsxColumnName($i) . $rowNum . '" t="inlineStr"><x:is><x:t>' . xlsxEscape($v) . '</x:t></x:is></x:c>';
        }
        $sheet .= '</x:row>';
    }
    $sheet .= '</x:sheetData></x:worksheet>';

    $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<x:workbook xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
        . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<x:sheets><x:sheet name="Report" sheetId="1" r:id="rId1"/></x:sheets></x:workbook>';

    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>';

    $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '</Relationships>';

    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '</Types>';

    // Package everything into a zip
    $tmp = tempnam(sys_get_temp_dir(), 'sfi_xlsx_');
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
        throw new Exception('Unable to create the Excel file.');
    }
    $zip->addFromString('[Content_Types].xml', $contentTypes);
    $zip->addFromString('_rels/.rels', $rels);
    $zip->addFromString('xl/workbook.xml', $workbook);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
    $zip->close();

    $data = file_get_contents($tmp);
    unlink($tmp);

    return $data;
}

/**
 * Build an Excel 2007+ (.xlsx) file with MULTIPLE sheets.
 *
 * @param array $sheets Each item: ['name' => 'Sheet Name', 'headers' => [...], 'rows' => [[...], ...]]
 * @return string Binary .xlsx content.
 */
function xlsxFromSheets(array $sheets) {
    $sheetXmls        = [];
    $sheetNames       = [];
    $relsXml          = '';
    $contentOverrides = '';
    $sheetId          = 1;

    foreach ($sheets as $idx => $s) {
        $sheetName = isset($s['name']) ? (string)$s['name'] : ('Sheet' . ($idx + 1));
        $sheetName = preg_replace('/[\[\]:*?\/\\\\]/', '', $sheetName); // invalid xlsx chars
        $sheetName = substr($sheetName, 0, 31);
        if ($sheetName === '') $sheetName = 'Sheet' . ($idx + 1);
        $sheetNames[] = $sheetName;

        $headers  = isset($s['headers']) ? $s['headers'] : [];
        $rows     = isset($s['rows']) ? $s['rows'] : [];
        $colCount = count($headers);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<x:worksheet xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<x:sheetData>';

        $rowNum = 1;
        $xml .= '<x:row>';
        foreach ($headers as $i => $h) {
            $xml .= '<x:c r="' . xlsxColumnName($i) . $rowNum . '" t="inlineStr"><x:is><x:t>' . xlsxEscape($h) . '</x:t></x:is></x:c>';
        }
        $xml .= '</x:row>';

        foreach ($rows as $row) {
            $rowNum++;
            $xml .= '<x:row>';
            for ($i = 0; $i < $colCount; $i++) {
                $v = isset($row[$i]) ? $row[$i] : '';
                $xml .= '<x:c r="' . xlsxColumnName($i) . $rowNum . '" t="inlineStr"><x:is><x:t>' . xlsxEscape($v) . '</x:t></x:is></x:c>';
            }
            $xml .= '</x:row>';
        }
        $xml .= '</x:sheetData></x:worksheet>';
        $sheetXmls[] = $xml;

        $relsXml .= '<Relationship Id="rId' . $sheetId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $sheetId . '.xml"/>';
        $contentOverrides .= '<Override PartName="/xl/worksheets/sheet' . $sheetId . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $sheetId++;
    }

    $sheetTags = '';
    foreach ($sheetNames as $i => $name) {
        $sheetTags .= '<x:sheet name="' . xlsxEscape($name) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
    }

    $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<x:workbook xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
        . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<x:sheets>' . $sheetTags . '</x:sheets></x:workbook>';

    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>';

    $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . $relsXml
        . '</Relationships>';

    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . $contentOverrides
        . '</Types>';

    $tmp = tempnam(sys_get_temp_dir(), 'sfi_xlsx_');
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
        throw new Exception('Unable to create the Excel file.');
    }
    $zip->addFromString('[Content_Types].xml', $contentTypes);
    $zip->addFromString('_rels/.rels', $rels);
    $zip->addFromString('xl/workbook.xml', $workbook);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
    foreach ($sheetXmls as $i => $xml) {
        $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $xml);
    }
    $zip->close();

    $data = file_get_contents($tmp);
    unlink($tmp);

    return $data;
}
