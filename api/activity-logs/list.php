<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin(); requireRole('admin'); initAPI();
try {
    $db = Database::getConnection();
    $page = max(1, (int)get('page', 1));
    $perPage = ITEMS_PER_PAGE;
    $offset = ($page - 1) * $perPage;

    // Build WHERE conditions
    $where = [];
    $params = [];

    $dateFrom = get('date_from');
    if ($dateFrom) {
        $where[] = "DATE(created_at) >= :date_from";
        $params[':date_from'] = $dateFrom;
    }

    $dateTo = get('date_to');
    if ($dateTo) {
        $where[] = "DATE(created_at) <= :date_to";
        $params[':date_to'] = $dateTo;
    }

    $userFilter = get('user');
    if ($userFilter) {
        $where[] = "username = :username";
        $params[':username'] = $userFilter;
    }

    $actionFilter = get('action');
    if ($actionFilter) {
        $where[] = "action = :action";
        $params[':action'] = $actionFilter;
    }

    $search = get('search');
    if ($search) {
        $where[] = "(description LIKE :search OR ip_address LIKE :search2)";
        $params[':search'] = "%$search%";
        $params[':search2'] = "%$search%";
    }

    $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

    // Count total
    $countStmt = $db->prepare("SELECT COUNT(*) FROM activity_logs $whereClause");
    foreach ($params as $k => $v) {
        $countStmt->bindValue($k, $v);
    }
    $countStmt->execute();
    $count = (int)$countStmt->fetchColumn();

    // Fetch paginated
    $stmt = $db->prepare("SELECT * FROM activity_logs $whereClause ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    Response::success('Activity logs loaded', [
        'logs' => $stmt->fetchAll(),
        'total' => $count,
        'page' => $page,
        'total_pages' => max(1, ceil($count / $perPage))
    ]);
} catch (Exception $e) {
    error_log('SFI Activity Logs Error: ' . $e->getMessage());
    Response::error('Failed to load logs.', [], 500);
}
