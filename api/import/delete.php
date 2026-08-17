<?php
/**
 * SFI Queuing System - Delete Imported Record API
 * POST /api/import/delete.php
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
requireRole('admin');
initAPI();
requirePost();

try {
    $id = (int)post('id');
    if ($id <= 0) Response::error('Invalid record ID.');

    $db = Database::getConnection();

    $stmt = $db->prepare("SELECT full_name FROM clients WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$record) Response::error('Record not found.');

    $stmt = $db->prepare("DELETE FROM clients WHERE id = :id");
    $stmt->execute([':id' => $id]);

    logActivity(getUserId(), getUsername(), 'delete_client', 'Deleted record: ' . $record['full_name']);
    Response::success('Record deleted successfully.');

} catch (Exception $e) {
    error_log('SFI Import Delete Error: ' . $e->getMessage());
    Response::error('Failed to delete record.', [], 500);
}
