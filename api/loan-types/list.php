<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
requireRole('admin');
initAPI();
try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT * FROM loan_types WHERE is_archived = 0 ORDER BY id ASC");
    Response::success('Loan types loaded', ['loan_types' => $stmt->fetchAll()]);
} catch (Exception $e) {
    Response::error('Failed to load loan types.', [], 500);
}
