<?php
/**
 * SFI Queuing System - Reset User Password API
 * POST /api/users/reset-password.php
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
requireRole('admin');
initAPI();
requirePost();

try {
    $id = (int)post('id');
    $newPassword = post('new_password');

    if ($id < 1) Response::error('Invalid user ID.');
    if (empty($newPassword) || strlen($newPassword) < 6) Response::error('Password must be at least 6 characters.');

    $db = Database::getConnection();
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password_hash = :p, force_password_change = 1 WHERE id = :id");
    $stmt->execute([':p' => $hash, ':id' => $id]);

    logActivity(getUserId(), getUsername(), 'reset_password', 'Reset password for user ID: ' . $id);
    Response::success('Password reset successfully. User will be prompted to change on next login.');

} catch (Exception $e) {
    error_log('SFI Reset Password Error: ' . $e->getMessage());
    Response::error('Failed to reset password.', [], 500);
}
