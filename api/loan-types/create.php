<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin(); requireRole('admin'); initAPI(); requirePost();
try {
    $name = trim(post('name')); $prefix = strtoupper(trim(post('prefix'))); $desc = trim(post('description'));
    $category = post('category');
    if (!in_array($category, ['transaction', 'product'], true)) $category = 'transaction';
    if (empty($name)) Response::error('Name is required.');
    if (empty($prefix) || strlen($prefix) > 5) Response::error('Prefix is required (max 5 chars).');
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT id FROM loan_types WHERE prefix = :p LIMIT 1");
    $stmt->execute([':p' => $prefix]);
    if ($stmt->fetch()) Response::error('Prefix already exists.');
    $stmt = $db->prepare("INSERT INTO loan_types (name, prefix, description, category) VALUES (:n, :p, :d, :c)");
    $stmt->execute([':n' => $name, ':p' => $prefix, ':d' => $desc, ':c' => $category]);
    logActivity(getUserId(), getUsername(), 'create_loan_type', 'Created loan type: ' . $name);
    Response::success('Loan type created.', ['id' => $db->lastInsertId()], 201);
} catch (Exception $e) { Response::error('Failed to create loan type.', [], 500); }
