<?php
/**
 * SFI Queuing System - Archive Snapshot Export
 *
 * Whenever data is archived (either by the "Archive All Old Data" button or by
 * a new Excel upload), ALL current (active) records are written into ONE dated
 * .xlsx file inside the Archive/ folder. The file name carries the date, e.g.:
 *
 *   Archive/archive_2026-08-14_143025.xlsx
 *
 * The file has one sheet per table: Clients, Loan Types, Counters, Users.
 */

require_once __DIR__ . '/xlsx-writer.php';

/**
 * Make sure the Archive folder exists and block direct web access to it
 * (the files contain sensitive client data).
 *
 * @return string Absolute path to the Archive folder.
 */
function archiveFolder() {
    $dir = __DIR__ . '/../Archive';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $htaccess = $dir . '/.htaccess';
    if (!file_exists($htaccess)) {
        @file_put_contents($htaccess, "# Deny direct web access - archive files contain sensitive client data\n"
            . "<IfModule mod_authz_core.c>\n"
            . "    Require all denied\n"
            . "</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n"
            . "    Order allow,deny\n"
            . "    Deny from all\n"
            . "</IfModule>\n");
    }
    return $dir;
}

/**
 * Write ONE dated .xlsx file containing the records being archived.
 *
 * By default it snapshots the current ACTIVE records (the data that is about to
 * be moved to the archive). If there is no active data but archived records
 * exist (e.g. everything was archived already), pass $mode = 'archived' so the
 * file is built from the archived records instead - the admin still gets a file.
 *
 * @param string $mode 'active' (default) or 'archived'
 * @return array ['file' => filename, 'path' => absolute path, 'counts' => [...]]
 * @throws Exception if the file cannot be written.
 */
function writeArchiveSnapshot($mode = 'active') {
    $db = Database::getConnection();
    $where = ($mode === 'archived') ? 'is_archived = 1' : 'is_archived = 0';

    // ---------------- Clients ----------------
    $stmt = $db->query("SELECT full_name, contact_number, address, remarks, raw_data, created_at
                        FROM clients WHERE $where ORDER BY full_name");
    $clientRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $rawKeys = [];
    foreach ($clientRows as $r) {
        if ($r['raw_data'] !== null) {
            $decoded = json_decode($r['raw_data'], true);
            if (is_array($decoded)) {
                $rawKeys = array_keys($decoded);
                break;
            }
        }
    }
    $clientsHeaders = array_merge($rawKeys, ['Full Name', 'Contact Number', 'Address', 'Remarks', 'Date Added']);
    $clientsRows = [];
    foreach ($clientRows as $r) {
        $decoded = ($r['raw_data'] !== null) ? json_decode($r['raw_data'], true) : [];
        if (!is_array($decoded)) $decoded = [];
        $line = [];
        foreach ($rawKeys as $k) {
            $line[] = isset($decoded[$k]) ? $decoded[$k] : '';
        }
        $line[] = $r['full_name'];
        $line[] = $r['contact_number'];
        $line[] = $r['address'];
        $line[] = $r['remarks'];
        $line[] = $r['created_at'];
        $clientsRows[] = $line;
    }

    // ---------------- Loan Types ----------------
    $ltRows = $db->query("SELECT name, prefix, description, category, status
                          FROM loan_types WHERE $where ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $loanTypesHeaders = ['Name', 'Prefix', 'Description', 'Category', 'Status'];
    $loanTypesRows = [];
    foreach ($ltRows as $r) {
        $loanTypesRows[] = [$r['name'], $r['prefix'], $r['description'], $r['category'], $r['status']];
    }

    // ---------------- Counters ----------------
    $ctRows = $db->query("SELECT name, counter_number, status
                          FROM counters WHERE $where ORDER BY counter_number")->fetchAll(PDO::FETCH_ASSOC);
    $countersHeaders = ['Name', 'Counter Number', 'Status'];
    $countersRows = [];
    foreach ($ctRows as $r) {
        $countersRows[] = [$r['name'], $r['counter_number'], $r['status']];
    }

    // ---------------- Users ----------------
    $usRows = $db->query("SELECT username, full_name, role, assigned_counter, status
                          FROM users WHERE $where ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
    $usersHeaders = ['Username', 'Full Name', 'Role', 'Assigned Counter', 'Status'];
    $usersRows = [];
    foreach ($usRows as $r) {
        $usersRows[] = [$r['username'], $r['full_name'], $r['role'], $r['assigned_counter'], $r['status']];
    }

    // ---------------- Build the single .xlsx file ----------------
    $xlsx = xlsxFromSheets([
        ['name' => 'Clients',    'headers' => $clientsHeaders,  'rows' => $clientsRows],
        ['name' => 'Loan Types', 'headers' => $loanTypesHeaders, 'rows' => $loanTypesRows],
        ['name' => 'Counters',   'headers' => $countersHeaders, 'rows' => $countersRows],
        ['name' => 'Users',      'headers' => $usersHeaders,    'rows' => $usersRows],
    ]);

    // Put the file inside a DATE subfolder (e.g. Archive/2026-08-14/) so all
    // the data archived on the same day are grouped together in one folder.
    $dir = archiveFolder() . '/' . date('Y-m-d');
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $filename = 'archive_' . date('Y-m-d_His') . '.xlsx';
    $path     = $dir . '/' . $filename;

    if (@file_put_contents($path, $xlsx) === false) {
        throw new Exception('Unable to write the archive file.');
    }

    return [
        'file'   => $filename,
        'path'   => $path,
        'counts' => [
            'clients'    => count($clientRows),
            'loan_types' => count($ltRows),
            'counters'   => count($ctRows),
            'users'      => count($usRows),
        ],
    ];
}
