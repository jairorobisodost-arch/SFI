<?php
/**
 * Import History API
 * Returns the list of all data imports (from activity_logs) so admins
 * can see past uploads even after refreshing the page.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
requireRole('admin');
initAPI();

try {
    $db = Database::getConnection();

    $search = get('search');
    $params = [];

    $where = "action = 'import_data'";
    if ($search) {
        $where .= " AND description LIKE :search";
        $params[':search'] = "%$search%";
    }

    $stmt = $db->prepare("SELECT id, username, description, ip_address, created_at
                          FROM activity_logs
                          WHERE $where
                          ORDER BY id DESC
                          LIMIT 50");
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    Response::success('Import history loaded', ['history' => $logs]);
} catch (Exception $e) {
    error_log('SFI Import History Error: ' . $e->getMessage());
    Response::error('Failed to load import history.', [], 500);
}
