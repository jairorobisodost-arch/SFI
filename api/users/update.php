<?php
/**
 * SFI Queuing System - Update User API
 * POST /api/users/update.php
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
requireRole('admin');
initAPI();
requirePost();

try {
    $id       = (int)post('id');
    $fullName = trim(post('full_name'));
    $role     = post('role', 'teller');
    $counter  = (int)post('assigned_counter', 1);

    if ($id < 1) Response::error('Invalid user ID.');
    if (empty($fullName)) Response::error('Full name is required.');
    if (!in_array($role, ['admin', 'teller', 'employee'])) Response::error('Invalid role.');

    $db = Database::getConnection();
    $stmt = $db->prepare("UPDATE users SET full_name = :n, role = :r, assigned_counter = :c WHERE id = :id");
    $stmt->execute([':n' => $fullName, ':r' => $role, ':c' => $counter, ':id' => $id]);

    logActivity(getUserId(), getUsername(), 'update_user', 'Updated user ID: ' . $id);
    Response::success('User updated successfully.');

} catch (Exception $e) {
    error_log('SFI Update User Error: ' . $e->getMessage());
    Response::error('Failed to update user.', [], 500);
}
