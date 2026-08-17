<?php
/**
 * SFI Queuing System - Toggle User Status API
 * POST /api/users/toggle-status.php
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
requireRole('admin');
initAPI();
requirePost();

try {
    $id = (int)post('id');
    if ($id < 1) Response::error('Invalid user ID.');

    // Prevent admin from deactivating themselves
    if ($id === getUserId()) Response::error('You cannot deactivate your own account.');

    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT status FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();
    if (!$user) Response::error('User not found.');

    $newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';
    $stmt = $db->prepare("UPDATE users SET status = :s WHERE id = :id");
    $stmt->execute([':s' => $newStatus, ':id' => $id]);

    logActivity(getUserId(), getUsername(), 'toggle_user_status', 'User ID ' . $id . ' set to ' . $newStatus);
    Response::success('User status updated to ' . $newStatus . '.');

} catch (Exception $e) {
    error_log('SFI Toggle User Error: ' . $e->getMessage());
    Response::error('Failed to update status.', [], 500);
}
