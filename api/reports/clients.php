<?php
/**
 * SFI Queuing System - Client Data Report API
 * GET /api/reports/clients.php?search=&barangay=&gender=&civil_status=&loan_type=&page=
 * Queries the imported client masterlist (with raw Excel fields) and returns
 * paginated matches plus the distinct filter options.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
requireRole('admin');
initAPI();

try {
    $search      = trim(get('search'));
    $barangay    = trim(get('barangay'));
    $gender      = trim(get('gender'));
    $civilStatus = trim(get('civil_status'));
    $loanType    = trim(get('loan_type'));
    $page        = max(1, (int)get('page', 1));
    $perPage     = 20;
    $offset      = ($page - 1) * $perPage;

    $db = Database::getConnection();

    // ---------------- Preset query list with counts ----------------
    if (get('queries') === '1') {
        $queries = [];

        $total = (int)$db->query("SELECT COUNT(*) FROM clients WHERE is_archived = 0")->fetchColumn();
        $queries[] = ['key' => 'all', 'label' => 'All Clients', 'count' => $total, 'params' => []];

        foreach ($db->query("SELECT JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.Gender')) AS v, COUNT(*) AS c FROM clients WHERE raw_data IS NOT NULL AND is_archived = 0 GROUP BY v ORDER BY c DESC") as $r) {
            if ($r['v'] === '' || $r['v'] === null) continue;
            $queries[] = ['key' => 'gender_' . $r['v'], 'label' => 'Gender: ' . $r['v'], 'count' => (int)$r['c'], 'params' => ['gender' => $r['v']]];
        }
        foreach ($db->query("SELECT JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.CivilStatus')) AS v, COUNT(*) AS c FROM clients WHERE raw_data IS NOT NULL AND is_archived = 0 GROUP BY v ORDER BY c DESC") as $r) {
            if ($r['v'] === '' || $r['v'] === null) continue;
            $queries[] = ['key' => 'civil_' . $r['v'], 'label' => 'Civil Status: ' . $r['v'], 'count' => (int)$r['c'], 'params' => ['civil_status' => $r['v']]];
        }
        foreach ($db->query("SELECT JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.Barangay')) AS v, COUNT(*) AS c FROM clients WHERE raw_data IS NOT NULL AND is_archived = 0 GROUP BY v ORDER BY c DESC") as $r) {
            if ($r['v'] === '' || $r['v'] === null) continue;
            $queries[] = ['key' => 'barangay_' . md5($r['v']), 'label' => 'Barangay: ' . $r['v'], 'count' => (int)$r['c'], 'params' => ['barangay' => $r['v']]];
        }
        // Loan type queries use loan PRODUCTS only (transaction types are for the kiosk)
        foreach ($db->query("SELECT lt.id, lt.name, COUNT(c.id) AS c FROM loan_types lt LEFT JOIN clients c ON c.loan_type_id = lt.id AND c.is_archived = 0 WHERE lt.category = 'product' AND lt.is_archived = 0 GROUP BY lt.id, lt.name ORDER BY c DESC") as $r) {
            $queries[] = ['key' => 'loan_' . $r['id'], 'label' => 'Loan Type: ' . $r['name'], 'count' => (int)$r['c'], 'params' => ['loan_type' => (string)$r['id']]];
        }

        Response::success('OK', ['queries' => $queries]);
    }

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
    // Filters read the stored raw Excel fields (JSON)
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

    // Total count
    $stmt = $db->prepare("SELECT COUNT(*) FROM clients c $whereSql");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    // Page rows
    $sql = "SELECT c.id, c.full_name, c.contact_number, c.address, c.remarks, c.raw_data, c.created_at,
                   lt.name AS loan_type_name
            FROM clients c
            LEFT JOIN loan_types lt ON lt.id = c.loan_type_id
            $whereSql
            ORDER BY c.id DESC
            LIMIT $perPage OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
        $r['raw_data'] = ($r['raw_data'] !== null) ? json_decode($r['raw_data'], true) : null;
    }
    unset($r);

    // Distinct filter options
    $options = [
        'barangays'     => [],
        'genders'       => [],
        'civil_statuses'=> [],
        'loan_types'    => []
    ];
    foreach ($db->query("SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.Barangay')) AS v FROM clients WHERE raw_data IS NOT NULL AND is_archived = 0 AND JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.Barangay')) <> '' ORDER BY v") as $r) {
        if ($r['v'] !== null) $options['barangays'][] = $r['v'];
    }
    foreach ($db->query("SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.Gender')) AS v FROM clients WHERE raw_data IS NOT NULL AND is_archived = 0 AND JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.Gender')) <> '' ORDER BY v") as $r) {
        if ($r['v'] !== null) $options['genders'][] = $r['v'];
    }
    foreach ($db->query("SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.CivilStatus')) AS v FROM clients WHERE raw_data IS NOT NULL AND is_archived = 0 AND JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.CivilStatus')) <> '' ORDER BY v") as $r) {
        if ($r['v'] !== null) $options['civil_statuses'][] = $r['v'];
    }
    foreach ($db->query("SELECT id, name FROM loan_types WHERE category = 'product' AND is_archived = 0 ORDER BY name") as $r) {
        $options['loan_types'][] = ['id' => (int)$r['id'], 'name' => $r['name']];
    }

    Response::success('OK', [
        'rows'         => $rows,
        'total'        => $total,
        'page'         => $page,
        'total_pages'  => (int)ceil($total / $perPage),
        'options'      => $options
    ]);

} catch (Exception $e) {
    error_log('SFI Client Report Error: ' . $e->getMessage());
    Response::error('Failed to load the client report.', [], 500);
}
