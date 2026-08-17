<?php
/**
 * SFI Queuing System - Import Data API (3-File System)
 * POST /api/import/upload.php (multipart/form-data, field: "file", field: "type")
 *
 * TYPE: client_info | loan_report | cbu_report
 *   - client_info  -> clients table      (Client Information.xlsx)
 *   - loan_report  -> client_loans table (Client Loan Report.xlsx)
 *   - cbu_report   -> client_cbu table   (Client VS And CBU Report.xlsx)
 *
 * The type is auto-detected from the header row when not provided.
 * Every successful upload archives the previous data (is_archived = 1) so
 * the latest upload is always the active data set. Clients are matched across
 * files via client_reference (e.g. B0005-0000454).
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/spreadsheet.php';
requireLogin();
requireRole('admin');
initAPI();
requirePost();

const IMPORT_MAX_ROWS = 20000;
const IMPORT_MAX_SIZE = 10 * 1024 * 1024; // 10 MB

/** Normalize a header cell: lowercase, strip non-alphanumeric. */
function normH($cell) {
    return strtolower(preg_replace('/[^a-z0-9]/i', '', $cell));
}

/** Convert Excel serial date (e.g. 46178) to Y-m-d, or '' when invalid. */
function serialToDate($serial) {
    if (!is_numeric($serial) || (int)$serial <= 0) return '';
    $unix = ((int)$serial - 25569) * 86400;
    return gmdate('Y-m-d', $unix);
}

/** Convert a value to a clean decimal string, or '' when empty. */
function numVal($v) {
    $v = trim((string)$v);
    if ($v === '') return '';
    $v = str_replace(',', '', $v);
    return (is_numeric($v)) ? number_format((float)$v, 2, '.', '') : '';
}

/** Clean a contact number: keep digits; strip leading +/spaces. */
function cleanContact($v) {
    $digits = preg_replace('/\D+/', '', (string)$v);
    return $digits;
}

/**
 * Detect which of the 3 report types the file is, and where the header row is.
 * Scans every row so files with a title/date row above the headers still work.
 * Returns [type, headerRow] or null.
 */
function detectType(array $rows) {
    $signatures = [
        'client_info' => ['clientid', 'clientlastname', 'clientfirstname'],
        'loan_report' => ['clientreference', 'loancategory', 'loanproduct'],
        'cbu_report'  => ['clientreference', 'vsdeposits', 'cbubalance']
    ];
    $best = null;
    $bestScore = 0;
    foreach ($rows as $rowIndex => $row) {
        $keys = [];
        foreach ($row as $cell) {
            $k = normH($cell);
            if ($k !== '') $keys[] = $k;
        }
        foreach ($signatures as $type => $needles) {
            $score = 0;
            foreach ($needles as $n) {
                if (in_array($n, $keys, true)) $score++;
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = ['type' => $type, 'headerRow' => $rowIndex];
            }
        }
    }
    return ($bestScore >= 2) ? $best : null;
}

/**
 * Build a header-index -> db-field map for the given type.
 */
function buildColumnMap($type, array $headerRow) {
    $map = []; // normalized header -> db field
    $known = [
        'client_info' => [
            'ao' => 'ao', 'centername' => 'center_name', 'clientid' => 'client_reference',
            'clientlastname' => 'last_name', 'clientfirstname' => 'first_name',
            'clientmiddlename' => 'middle_name', 'birthday' => 'birthday',
            'gender' => 'gender', 'civilstatus' => 'civil_status',
            'contactno' => 'contact_number', 'address' => 'address',
            'mothermaidenlastname' => 'mother_maiden_lastname',
            'mothermaidenfirstname' => 'mother_maiden_firstname',
            'mothermaidenmiddlename' => 'mother_maiden_middlename',
            'clientsubid' => 'client_subid', 'branch' => 'branch'
        ],
        'loan_report' => [
            'ao' => 'ao', 'centername' => 'center_name',
            'clientreference' => 'client_reference', 'clientname' => 'client_name',
            'loancategory' => 'loan_category', 'loanproduct' => 'loan_product',
            'cycleno' => 'cycle_no', 'daterelease' => 'date_release',
            'datematured' => 'date_matured', 'principal' => 'principal',
            'interest' => 'interest', 'principalbalance' => 'principal_balance',
            'interestbalance' => 'interest_balance', 'advances' => 'advances',
            'totalarrears' => 'total_arrears'
        ],
        'cbu_report' => [
            'ao' => 'ao', 'centername' => 'center_name',
            'clientreference' => 'client_reference', 'clientname' => 'client_name',
            'vsdeposits' => 'vs_deposits', 'vswithdrawals' => 'vs_withdrawals',
            'vsadjustcredit' => 'vs_adjust_credit', 'vsadjustdebit' => 'vs_adjust_debit',
            'vsbalance' => 'vs_balance',
            'cbudeposits' => 'cbu_deposits', 'cbuwithdrawals' => 'cbu_withdrawals',
            'cbutransfertovs' => 'cbu_transfer_to_vs',
            'cbuadjustcredit' => 'cbu_adjust_credit', 'cbuadjustdebit' => 'cbu_adjust_debit',
            'cbubalance' => 'cbu_balance'
        ]
    ];
    foreach ($headerRow as $i => $cell) {
        $k = normH($cell);
        if ($k === '') continue;
        if (isset($known[$type][$k])) {
            $map[$i] = $known[$type][$k];
        }
    }
    return $map;
}

try {
    // ---------------- Validate upload ----------------
    if (empty($_FILES['file']) || !isset($_FILES['file']['tmp_name'])) {
        Response::error('Please choose a file to upload.');
    }
    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        Response::error('File upload failed (error code ' . $file['error'] . ').');
    }
    if ($file['size'] > IMPORT_MAX_SIZE) {
        Response::error('File is too large. Maximum size is 10 MB.');
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['xlsx', 'csv'])) {
        Response::error('Unsupported file type. Please upload an .xlsx or .csv file.');
    }

    $requestedType = strtolower(trim(post('type')));
    if (!in_array($requestedType, ['client_info', 'loan_report', 'cbu_report'], true)) {
        $requestedType = '';
    }

    // ---------------- Parse file ----------------
    $rows = parseSpreadsheet($file['tmp_name'], $ext);
    if (count($rows) < 2) {
        Response::error('The file has no data rows.');
    }

    // ---------------- Detect type + header row ----------------
    $detected = detectType($rows);
    if ($requestedType !== '') {
        // Trust the UI's explicit type, but still locate the header row by signature
        $type = $requestedType;
        if (!$detected) {
            Response::error('Could not find the expected header row for ' . $type . '.');
        }
    } else {
        if (!$detected) {
            Response::error(
                'Could not detect the file type. Expected headers: ' .
                'Client Information (client_id, client_lastname, ...), ' .
                'Client Loan Report (ClientReference, Loan_Category, ...), or ' .
                'Client VS And CBU Report (ClientReference, VS_BALANCE, ...).'
            );
        }
        $type = $detected['type'];
    }

    // If an explicit type was given, re-locate the header row for that type only
    if ($requestedType !== '') {
        $signatures = [
            'client_info' => ['clientid', 'clientlastname', 'clientfirstname'],
            'loan_report' => ['clientreference', 'loancategory', 'loanproduct'],
            'cbu_report'  => ['clientreference', 'vsdeposits', 'cbubalance']
        ];
        $headerRow = null;
        foreach ($rows as $rowIndex => $row) {
            $keys = [];
            foreach ($row as $cell) {
                $k = normH($cell);
                if ($k !== '') $keys[] = $k;
            }
            $score = 0;
            foreach ($signatures[$type] as $n) {
                if (in_array($n, $keys, true)) $score++;
            }
            if ($score >= 2) { $headerRow = $rowIndex; break; }
        }
        if ($headerRow === null) {
            Response::error('Could not find the header row for this file type. Please use the correct Excel template.');
        }
    } else {
        $headerRow = $detected['headerRow'];
    }

    // ---------------- Build column map ----------------
    $headerCells = $rows[$headerRow];
    $columns = buildColumnMap($type, $headerCells);
    $requiredField = ['client_info' => 'client_reference', 'loan_report' => 'client_reference', 'cbu_report' => 'client_reference'][$type];
    if (!in_array($requiredField, $columns, true)) {
        Response::error('The header row is missing the required column (ClientReference / client_id). Please check the file.');
    }

    $dataRows = array_slice($rows, $headerRow + 1);
    if (count($dataRows) > IMPORT_MAX_ROWS) {
        Response::error('Too many rows. Maximum of ' . IMPORT_MAX_ROWS . ' rows per upload.');
    }

    // ---------------- Database ----------------
    $db = Database::getConnection();
    $imported = 0;
    $archived = 0;
    $skipped = [];
    $duplicates = [];

    $db->beginTransaction();

    // Archive previous data for this table
    if ($type === 'client_info') {
        $archived = (int)$db->exec("UPDATE clients SET is_archived = 1 WHERE is_archived = 0");
    } elseif ($type === 'loan_report') {
        $archived = (int)$db->exec("UPDATE client_loans SET is_archived = 1 WHERE is_archived = 0");
    } elseif ($type === 'cbu_report') {
        $archived = (int)$db->exec("UPDATE client_cbu SET is_archived = 1 WHERE is_archived = 0");
    }

    $seenKeys = [];
    // loan_report: a client can have MULTIPLE loans (different products/cycles),
    // so every row is imported. Only client_info and cbu_report are deduped by reference.
    $dedupByRef = ($type !== 'loan_report');

    if ($type === 'client_info') {
        $stmt = $db->prepare("
            INSERT INTO clients
                (client_reference, full_name, last_name, first_name, middle_name, birthday,
                 gender, civil_status, contact_number, address,
                 mother_maiden_lastname, mother_maiden_firstname, mother_maiden_middlename,
                 client_subid, branch, ao, center_name, raw_data)
            VALUES
                (:ref, :full, :ln, :fn, :mn, :bd, :g, :cs, :contact, :addr,
                 :mmln, :mmfn, :mmmn, :subid, :branch, :ao, :center, :raw)
        ");
        foreach ($dataRows as $offset => $row) {
            $rowNumber = $headerRow + $offset + 2;
            $rec = [];
            foreach ($columns as $i => $field) {
                $rec[$field] = isset($row[$i]) ? trim($row[$i]) : '';
            }
            $ref = $rec['client_reference'];
            if ($ref === '') {
                $skipped[] = 'Row ' . $rowNumber . ': ClientReference is empty.';
                continue;
            }
            $key = 'ref|' . $ref;
            if ($dedupByRef && isset($seenKeys[$key])) {
                $duplicates[] = 'Row ' . $rowNumber . ': ClientReference "' . $ref . '" appears more than once in the uploaded file.';
                continue;
            }
            if ($dedupByRef) $seenKeys[$key] = true;

            $fullName = trim(implode(' ', array_filter([
                $rec['last_name'] ? strtoupper($rec['last_name']) . ',' : '',
                $rec['first_name'],
                $rec['middle_name']
            ])));
            if ($fullName === '') {
                $fullName = $ref;
            }
            if (mb_strlen($fullName) > 150) $fullName = mb_substr($fullName, 0, 150);

            $stmt->execute([
                ':ref'     => mb_substr($ref, 0, 50),
                ':full'    => $fullName,
                ':ln'      => mb_substr($rec['last_name'], 0, 100),
                ':fn'      => mb_substr($rec['first_name'], 0, 100),
                ':mn'      => mb_substr($rec['middle_name'], 0, 100),
                ':bd'      => serialToDate($rec['birthday']) ?: null,
                ':g'       => mb_substr($rec['gender'], 0, 20),
                ':cs'      => mb_substr($rec['civil_status'], 0, 30),
                ':contact' => mb_substr(cleanContact($rec['contact_number']), 0, 20),
                ':addr'    => mb_substr($rec['address'], 0, 255),
                ':mmln'    => mb_substr($rec['mother_maiden_lastname'], 0, 100),
                ':mmfn'    => mb_substr($rec['mother_maiden_firstname'], 0, 100),
                ':mmmn'    => mb_substr($rec['mother_maiden_middlename'], 0, 100),
                ':subid'   => mb_substr($rec['client_subid'], 0, 50),
                ':branch'  => mb_substr($rec['branch'], 0, 100),
                ':ao'      => mb_substr($rec['ao'], 0, 150),
                ':center'  => mb_substr($rec['center_name'], 0, 100),
                ':raw'     => json_encode($rec)
            ]);
            $imported++;
        }
    } elseif ($type === 'loan_report') {
        $stmt = $db->prepare("
            INSERT INTO client_loans
                (client_reference, ao, center_name, client_name, loan_category, loan_product,
                 cycle_no, date_release, date_matured, principal, interest,
                 principal_balance, interest_balance, advances, total_arrears, raw_data)
            VALUES
                (:ref, :ao, :center, :cname, :lcat, :lprod, :cycle, :rel, :mat,
                 :princ, :intr, :pbal, :ibal, :adv, :arr, :raw)
        ");
        foreach ($dataRows as $offset => $row) {
            $rowNumber = $headerRow + $offset + 2;
            $rec = [];
            foreach ($columns as $i => $field) {
                $rec[$field] = isset($row[$i]) ? trim($row[$i]) : '';
            }
            $ref = $rec['client_reference'];
            if ($ref === '') {
                $skipped[] = 'Row ' . $rowNumber . ': ClientReference is empty.';
                continue;
            }
            // loan_report: keep every row (a client may have several loans)
            $stmt->execute([
                ':ref'    => mb_substr($ref, 0, 50),
                ':ao'     => mb_substr($rec['ao'], 0, 150),
                ':center' => mb_substr($rec['center_name'], 0, 100),
                ':cname'  => mb_substr($rec['client_name'], 0, 200),
                ':lcat'   => mb_substr($rec['loan_category'], 0, 100),
                ':lprod'  => mb_substr($rec['loan_product'], 0, 100),
                ':cycle'  => ($rec['cycle_no'] !== '' && is_numeric($rec['cycle_no'])) ? (int)$rec['cycle_no'] : null,
                ':rel'    => serialToDate($rec['date_release']) ?: null,
                ':mat'    => serialToDate($rec['date_matured']) ?: null,
                ':princ'  => numVal($rec['principal']),
                ':intr'   => numVal($rec['interest']),
                ':pbal'   => numVal($rec['principal_balance']),
                ':ibal'   => numVal($rec['interest_balance']),
                ':adv'    => numVal($rec['advances']),
                ':arr'    => numVal($rec['total_arrears']),
                ':raw'    => json_encode($rec)
            ]);
            $imported++;
        }
    } elseif ($type === 'cbu_report') {
        $stmt = $db->prepare("
            INSERT INTO client_cbu
                (client_reference, ao, center_name, client_name,
                 vs_deposits, vs_withdrawals, vs_adjust_credit, vs_adjust_debit, vs_balance,
                 cbu_deposits, cbu_withdrawals, cbu_transfer_to_vs,
                 cbu_adjust_credit, cbu_adjust_debit, cbu_balance, raw_data)
            VALUES
                (:ref, :ao, :center, :cname,
                 :vsd, :vsw, :vsac, :vsad, :vsb,
                 :cbd, :cbw, :cbt, :cbac, :cbad, :cbb, :raw)
        ");
        foreach ($dataRows as $offset => $row) {
            $rowNumber = $headerRow + $offset + 2;
            $rec = [];
            foreach ($columns as $i => $field) {
                $rec[$field] = isset($row[$i]) ? trim($row[$i]) : '';
            }
            $ref = $rec['client_reference'];
            if ($ref === '') {
                $skipped[] = 'Row ' . $rowNumber . ': ClientReference is empty.';
                continue;
            }
            $key = 'ref|' . $ref;
            if ($dedupByRef && isset($seenKeys[$key])) {
                $duplicates[] = 'Row ' . $rowNumber . ': ClientReference "' . $ref . '" appears more than once in the uploaded file.';
                continue;
            }
            if ($dedupByRef) $seenKeys[$key] = true;

            $stmt->execute([
                ':ref'   => mb_substr($ref, 0, 50),
                ':ao'    => mb_substr($rec['ao'], 0, 150),
                ':center'=> mb_substr($rec['center_name'], 0, 100),
                ':cname' => mb_substr($rec['client_name'], 0, 200),
                ':vsd'   => numVal($rec['vs_deposits']),
                ':vsw'   => numVal($rec['vs_withdrawals']),
                ':vsac'  => numVal($rec['vs_adjust_credit']),
                ':vsad'  => numVal($rec['vs_adjust_debit']),
                ':vsb'   => numVal($rec['vs_balance']),
                ':cbd'   => numVal($rec['cbu_deposits']),
                ':cbw'   => numVal($rec['cbu_withdrawals']),
                ':cbt'   => numVal($rec['cbu_transfer_to_vs']),
                ':cbac'  => numVal($rec['cbu_adjust_credit']),
                ':cbad'  => numVal($rec['cbu_adjust_debit']),
                ':cbb'   => numVal($rec['cbu_balance']),
                ':raw'   => json_encode($rec)
            ]);
            $imported++;
        }
    }

    if ($imported === 0) {
        $db->rollBack();
        $allIssues = array_merge(array_slice($skipped, 0, 20), array_slice($duplicates, 0, 20));
        Response::error('No records were imported. Please check your file.', $allIssues);
    }

    $db->commit();

    $typeLabels = [
        'client_info' => 'Client Information (clients)',
        'loan_report' => 'Client Loan Report (client_loans)',
        'cbu_report'  => 'Client VS And CBU Report (client_cbu)'
    ];

    logActivity(getUserId(), getUsername(), 'import_data',
        'Imported ' . $imported . ' record(s) into ' . $typeLabels[$type] . ' from ' . $file['name'] .
        ($archived > 0 ? '; archived ' . $archived . ' previous record(s)' : ''));

    $message = 'Upload successful. ' . $imported . ' record(s) saved to ' . $typeLabels[$type] . '.';
    if ($archived > 0) {
        $message .= ' ' . $archived . ' previous record(s) moved to Archive.';
    }
    if (count($duplicates) > 0) {
        $message .= ' ' . count($duplicates) . ' duplicate row(s) skipped.';
    }

    Response::success($message, [
        'type' => $type,
        'type_label' => $typeLabels[$type],
        'imported' => $imported,
        'archived' => $archived,
        'skipped_count' => count($skipped),
        'skipped' => array_slice($skipped, 0, 20),
        'duplicates_count' => count($duplicates),
        'duplicates' => array_slice($duplicates, 0, 20)
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('SFI Import Error: ' . $e->getMessage());
    Response::error('A system error occurred: ' . $e->getMessage(), [], 500);
}
