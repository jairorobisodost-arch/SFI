<?php
/**
 * SFI Queuing System - Client Data Report Export
 * GET /api/reports/clients-export.php?search=&barangay=&gender=&civil_status=&loan_type=
 * Downloads ALL matching clients as a real Excel (.xlsx) file.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/xlsx-writer.php';
requireLogin();
requireRole('admin');
initAPI();

try {
    $search      = trim(get('search'));
    $barangay    = trim(get('barangay'));
    $gender      = trim(get('gender'));
    $civilStatus = trim(get('civil_status'));
    $loanType    = trim(get('loan_type'));

    $db = Database::getConnection();

    $where  = [];
    $params = [];

    $where[] = "c.is_archived = 0";

    if ($search !== '') {
        $where[] = "(c.full_name LIKE :s1 OR c.contact_number LIKE :s2 OR c.address LIKE :s3)";
        $like = '%' . $search . '%';
        $params[':s1'] = $like;
        $params[':s2'] = $like;
        $params[':s3'] = $like;
    }
    if ($barangay !== '') {
        $where[] = "JSON_UNQUOTE(JSON_EXTRACT(c.raw_data, '$.Barangay')) = :barangay";
        $params[':barangay'] = $barangay;
    }
    if ($gender !== '') {
        $where[] = "JSON_UNQUOTE(JSON_EXTRACT(c.raw_data, '$.Gender')) = :gender";
        $params[':gender'] = $gender;
    }
    if ($civilStatus !== '') {
        $where[] = "JSON_UNQUOTE(JSON_EXTRACT(c.raw_data, '$.CivilStatus')) = :civil";
        $params[':civil'] = $civilStatus;
    }
    if ($loanType !== '') {
        $where[] = "c.loan_type_id = :lt";
        $params[':lt'] = (int)$loanType;
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $sql = "SELECT c.full_name, c.contact_number, c.address, c.remarks, c.raw_data, c.created_at
            FROM clients c
            LEFT JOIN loan_types lt ON lt.id = c.loan_type_id
            $whereSql
            ORDER BY c.full_name";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build the column list: all raw Excel fields (in file order) + standard fields
    $rawKeys = [];
    foreach ($rows as $r) {
        if ($r['raw_data'] !== null) {
            $decoded = json_decode($r['raw_data'], true);
            if (is_array($decoded)) {
                $rawKeys = array_keys($decoded);
                break;
            }
        }
    }
    $headers = array_merge($rawKeys, ['Full Name', 'Contact Number', 'Address', 'Remarks', 'Date Added']);

    $dataRows = [];
    foreach ($rows as $r) {
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
        $dataRows[] = $line;
    }

    $xlsx = xlsxFromRows($headers, $dataRows);

    $filename = 'client-report-' . date('Ymd_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($xlsx));
    echo $xlsx;
    exit;

} catch (Exception $e) {
    error_log('SFI Client Report Export Error: ' . $e->getMessage());
    Response::error('Failed to export the client report.', [], 500);
}
