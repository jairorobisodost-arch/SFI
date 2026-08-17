<?php
/**
 * SFI Queuing System - List Users API
 * GET /api/users/list.php
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
requireRole('admin');
initAPI();

try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT id, username, full_name, role, assigned_counter, status, created_at FROM users WHERE is_archived = 0 ORDER BY id ASC");
    $users = $stmt->fetchAll();
    Response::success('Users loaded', ['users' => $users]);
} catch (Exception $e) {
    error_log('SFI Users List Error: ' . $e->getMessage());
    Response::error('Failed to load users.', [], 500);
}
