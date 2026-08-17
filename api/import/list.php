<?php
/**
 * SFI Queuing System - List Imported Records API
 * GET /api/import/list.php?search=&page=1&view=current|archived
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
requireRole('admin');
initAPI();

try {
    $search = trim(get('search'));
    $page = max(1, (int)get('page', 1));
    $view = get('view', 'current'); // 'current' (default) or 'archived'
    $perPage = 10;
    $offset = ($page - 1) * $perPage;

    $db = Database::getConnection();

    // Base filter: show active data by default, archived rows when toggled
    $archivedFlag = ($view === 'archived') ? 1 : 0;
    $where = "WHERE c.is_archived = $archivedFlag";
    $params = [];
    if ($search !== '') {
        // Note: each LIKE needs its own named parameter - PDO (with emulated
        // prepares off) does not allow reusing the same placeholder
        $where .= " AND (c.full_name LIKE :s1 OR c.contact_number LIKE :s2 OR c.address LIKE :s3 OR c.remarks LIKE :s4)";
        $like = '%' . $search . '%';
        $params[':s1'] = $like;
        $params[':s2'] = $like;
        $params[':s3'] = $like;
        $params[':s4'] = $like;
    }

    // Total count for the current view
    $stmt = $db->prepare("SELECT COUNT(*) FROM clients c $where");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    // Page rows
    $sql = "SELECT c.id, c.full_name, c.contact_number, c.address, c.remarks, c.raw_data, c.loan_status,
                   c.loan_type_id, c.created_at, c.is_archived,
                   lt.name AS loan_type_name, lt.prefix AS loan_type_prefix
            FROM clients c
            LEFT JOIN loan_types lt ON lt.id = c.loan_type_id
            $where
            ORDER BY c.id DESC
            LIMIT $perPage OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decode the stored raw Excel row so the detail view can render it directly
    foreach ($rows as &$r) {
        $r['raw_data'] = ($r['raw_data'] !== null) ? json_decode($r['raw_data'], true) : null;
    }
    unset($r);

    // Loan products available for assignment (categories: product)
    $loanTypes = [];
    foreach ($db->query("SELECT id, name FROM loan_types WHERE category = 'product' AND is_archived = 0 ORDER BY name") as $r) {
        $loanTypes[] = ['id' => (int)$r['id'], 'name' => $r['name']];
    }

    // Counts for the Current/Archived toggle
    $currentCount = (int)$db->query("SELECT COUNT(*) FROM clients WHERE is_archived = 0")->fetchColumn();
    $archivedCount = (int)$db->query("SELECT COUNT(*) FROM clients WHERE is_archived = 1")->fetchColumn();

    Response::success('OK', [
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'total_pages' => (int)ceil($total / $perPage),
        'loan_types' => $loanTypes,
        'view' => $view,
        'current_count' => $currentCount,
        'archived_count' => $archivedCount
    ]);

} catch (Exception $e) {
    error_log('SFI Import List Error: ' . $e->getMessage());
    Response::error('Failed to load records.', [], 500);
}
