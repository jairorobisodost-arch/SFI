<?php
/**
 * SFI Queuing System - Spreadsheet Parser (dependency-free)
 * Parses .xlsx (via ZipArchive + DOM) and .csv files into plain row arrays.
 */

/**
 * Parse an uploaded spreadsheet file into rows of strings.
 *
 * @param string $filePath  Temp path of the uploaded file.
 * @param string $extension File extension (xlsx|csv).
 * @return array Array of rows, each row an array of cell strings.
 * @throws Exception On unreadable/invalid file.
 */
function parseSpreadsheet($filePath, $extension) {
    if ($extension === 'xlsx') {
        return parseXlsx($filePath);
    }
    return parseCsv($filePath);
}

/**
 * Query elements by local name, namespace-aware with fallback.
 * Handles both prefixed (x:row) and default-namespace (row) XML styles,
 * since some Excel exporters prefix every element with the spreadsheetml namespace.
 */
function xlsxElements(DOMNode $node, $localName) {
    $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $list = $node->getElementsByTagNameNS($ns, $localName);
    if ($list->length === 0) {
        return $node->getElementsByTagName($localName);
    }
    return $list;
}

/**
 * Query relationship elements in an .rels file, namespace-aware with fallback.
 */
function xlsxRelationships(DOMDocument $dom) {
    $ns = 'http://schemas.openxmlformats.org/package/2006/relationships';
    $list = $dom->getElementsByTagNameNS($ns, 'Relationship');
    if ($list->length === 0) {
        return $dom->getElementsByTagName('Relationship');
    }
    return $list;
}

/**
 * Parse an .xlsx file (first worksheet only).
 */
function parseXlsx($filePath) {
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        throw new Exception('Unable to open the Excel file. It may be corrupted.');
    }

    // Shared strings table (cell values of type "s" reference this list)
    $shared = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml !== false) {
        $reader = new XMLReader();
        if ($reader->XML($ssXml)) {
            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'si') {
                    $shared[] = trim($reader->readString());
                }
            }
            $reader->close();
        }
    }

    // Locate the first worksheet (fallback to sheet1.xml)
    $sheetXml = false;
    $workbookXml = $zip->getFromName('xl/workbook.xml');
    if ($workbookXml !== false) {
        $wbDom = new DOMDocument();
        if ($wbDom->loadXML($workbookXml)) {
            $sheets = xlsxElements($wbDom, 'sheet');
            if ($sheets->length > 0) {
                $rid = $sheets->item(0)->getAttribute('r:id');
                $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
                if ($relsXml !== false) {
                    $relsDom = new DOMDocument();
                    if ($relsDom->loadXML($relsXml)) {
                        foreach (xlsxRelationships($relsDom) as $rel) {
                            if ($rel->getAttribute('Id') === $rid) {
                                $target = ltrim($rel->getAttribute('Target'), '/');
                                // Target can be relative (worksheets/sheet.xml) or
                                // absolute (xl/worksheets/sheet.xml) - handle both
                                $path = (strpos($target, 'xl/') === 0) ? $target : 'xl/' . $target;
                                $path = str_replace('xl/worksheets/../', 'xl/', $path);
                                $sheetXml = $zip->getFromName($path);
                                break;
                            }
                        }
                    }
                }
            }
        }
    }
    if ($sheetXml === false) {
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    }
    $zip->close();

    if ($sheetXml === false) {
        throw new Exception('No worksheet found in the Excel file.');
    }

    // Stream-parse the worksheet with XMLReader (fast on large files)
    $reader = new XMLReader();
    if (!$reader->XML($sheetXml)) {
        throw new Exception('Unable to read the worksheet data.');
    }

    $rows = [];
    $currentRow = null;    // row being built
    $currentCol = 0;       // column index of the cell being read
    $fallbackCol = 0;      // sequential column used when a cell has no r attribute
    $currentType = '';     // cell type (s / inlineStr / numeric)
    $cellText = '';        // raw text captured for the current cell
    $hasValue = false;     // whether the cell actually had a value element

    while ($reader->read()) {
        if ($reader->nodeType === XMLReader::ELEMENT) {
            $name = $reader->localName;
            if ($name === 'row') {
                $currentRow = [];
                $fallbackCol = 0;
            } elseif ($name === 'c' && $currentRow !== null) {
                $ref = $reader->getAttribute('r');
                if ($ref !== null && $ref !== '') {
                    $currentCol = xlsxColumnIndex($ref);
                } else {
                    // Some exporters omit cell references - use sequential order
                    $currentCol = $fallbackCol;
                }
                $currentType = $reader->getAttribute('t');
                $cellText = '';
                $hasValue = false;
            } elseif (($name === 'v' || $name === 'is') && $currentRow !== null) {
                $hasValue = true;
                $cellText = $reader->readString();
            }
        } elseif ($reader->nodeType === XMLReader::END_ELEMENT && $currentRow !== null) {
            $name = $reader->localName;
            if ($name === 'c') {
                $value = '';
                if ($hasValue) {
                    if ($currentType === 's') {
                        $idx = (int)$cellText;
                        $value = isset($shared[$idx]) ? $shared[$idx] : '';
                    } else {
                        $value = trim($cellText);
                    }
                }
                // Pad gaps between columns so indexes stay aligned
                while (count($currentRow) < $currentCol) {
                    $currentRow[] = '';
                }
                $currentRow[$currentCol] = $value;
                $fallbackCol = max($fallbackCol, $currentCol + 1);
            } elseif ($name === 'row') {
                $rows[] = $currentRow;
                $currentRow = null;
            }
        }
    }
    $reader->close();

    return $rows;
}

/**
 * Convert a cell reference like "B3" or "AA12" to a zero-based column index.
 */
function xlsxColumnIndex($cellRef) {
    $letters = preg_replace('/[^A-Za-z]/', '', $cellRef);
    $index = 0;
    for ($i = 0; $i < strlen($letters); $i++) {
        $index = $index * 26 + (ord(strtoupper($letters[$i])) - 64);
    }
    return max(0, $index - 1);
}

/**
 * Parse a .csv file (handles UTF-8 BOM and comma/semicolon delimiters).
 */
function parseCsv($filePath) {
    $handle = fopen($filePath, 'r');
    if (!$handle) {
        throw new Exception('Unable to read the CSV file.');
    }

    // Detect delimiter from the first line
    $firstLine = fgets($handle);
    $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
    rewind($handle);

    // Strip UTF-8 BOM if present
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($handle);
    }

    $rows = [];
    while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
        if ($data === [null] || $data === ['']) continue; // skip blank lines
        $rows[] = array_map(function ($v) {
            return is_string($v) ? trim($v) : '';
        }, $data);
    }
    fclose($handle);

    return $rows;
}
