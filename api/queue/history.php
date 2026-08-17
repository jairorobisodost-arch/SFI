<?php
/**
 * SFI Queuing System - Queue History API
 * GET /api/queue/history.php
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
initAPI();

try {
    $db = Database::getConnection();

    // Filters
    $date       = get('date', '');
    $loan_type  = get('loan_type', '');
    $counter    = get('counter', '');
    $status     = get('status', '');
    $search     = get('search', '');
    $page       = max(1, (int)get('page', 1));
    $perPage    = ITEMS_PER_PAGE;
    $offset     = ($page - 1) * $perPage;

    // Build query
    $where = [];
    $params = [];

    if (!empty($date)) {
        $where[] = 'qt.queue_date = :date';
        $params[':date'] = $date;
    }
    if (!empty($loan_type)) {
        $where[] = 'qt.prefix = :prefix';
        $params[':prefix'] = $loan_type;
    }
    if (!empty($counter)) {
        $where[] = 'qt.counter_assigned = :counter';
        $params[':counter'] = $counter;
    }
    if (!empty($status)) {
        $where[] = 'qt.status = :status';
        $params[':status'] = $status;
    }
    if (!empty($search)) {
        $where[] = '(qt.ticket_number LIKE :search OR qt.client_name LIKE :search2)';
        $params[':search'] = '%' . $search . '%';
        $params[':search2'] = '%' . $search . '%';
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Count total
    $countSql = "SELECT COUNT(*) FROM queue_tickets qt $whereClause";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Fetch page
    $sql = "
        SELECT qt.*, lt.name AS loan_type_name, u.full_name AS served_by_name
        FROM queue_tickets qt
        LEFT JOIN loan_types lt ON qt.loan_type_id = lt.id
        LEFT JOIN users u ON qt.served_by = u.id
        $whereClause
        ORDER BY qt.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $tickets = $stmt->fetchAll();

    Response::success('History loaded', [
        'tickets'     => $tickets,
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => ceil($total / $perPage)
    ]);

} catch (Exception $e) {
    error_log('SFI History Error: ' . $e->getMessage());
    Response::error('Failed to load history.', [], 500);
}
