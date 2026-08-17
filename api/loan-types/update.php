<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin(); requireRole('admin'); initAPI(); requirePost();
try {
    $id = (int)post('id'); $name = trim(post('name')); $prefix = strtoupper(trim(post('prefix'))); $desc = trim(post('description'));
    $category = post('category');
    if (!in_array($category, ['transaction', 'product'], true)) $category = 'transaction';
    if ($id < 1) Response::error('Invalid ID.');
    if (empty($name)) Response::error('Name is required.');
    $db = Database::getConnection();
    $stmt = $db->prepare("UPDATE loan_types SET name = :n, prefix = :p, description = :d, category = :c WHERE id = :id");
    $stmt->execute([':n' => $name, ':p' => $prefix, ':d' => $desc, ':c' => $category, ':id' => $id]);
    logActivity(getUserId(), getUsername(), 'update_loan_type', 'Updated loan type ID: ' . $id);
    Response::success('Loan type updated.');
} catch (Exception $e) { Response::error('Failed to update loan type.', [], 500); }
