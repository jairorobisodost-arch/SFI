<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin(); requireRole('admin'); initAPI(); requirePost();
try {
    $id = (int)post('id');
    if ($id < 1) Response::error('Invalid ID.');
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT status FROM loan_types WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $lt = $stmt->fetch();
    if (!$lt) Response::error('Loan type not found.');
    $newStatus = ($lt['status'] === 'active') ? 'inactive' : 'active';
    $stmt = $db->prepare("UPDATE loan_types SET status = :s WHERE id = :id");
    $stmt->execute([':s' => $newStatus, ':id' => $id]);
    logActivity(getUserId(), getUsername(), 'toggle_loan_type', 'Loan type ID ' . $id . ' set to ' . $newStatus);
    Response::success('Loan type ' . ($newStatus === 'active' ? 'enabled' : 'disabled') . '.');
} catch (Exception $e) { Response::error('Failed to update status.', [], 500); }
