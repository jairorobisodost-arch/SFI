<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin(); requireRole('admin'); initAPI(); requirePost();
try {
    $name = trim(post('name'));
    $number = (int)post('counter_number');
    if (empty($name)) Response::error('Counter name is required.');
    if ($number < 1) Response::error('Invalid counter number.');
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT id FROM counters WHERE counter_number = :n LIMIT 1");
    $stmt->execute([':n' => $number]);
    if ($stmt->fetch()) Response::error('Counter number already exists.');
    $stmt = $db->prepare("INSERT INTO counters (name, counter_number) VALUES (:n, :num)");
    $stmt->execute([':n' => $name, ':num' => $number]);
    logActivity(getUserId(), getUsername(), 'create_counter', 'Created counter: ' . $name);
    Response::success('Counter created.', ['id' => $db->lastInsertId()], 201);
} catch (Exception $e) { Response::error('Failed to create counter.', [], 500); }
