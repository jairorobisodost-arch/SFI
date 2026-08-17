<?php
/**
 * SFI Queuing System - Change Password API
 * POST /api/auth/change-password.php
 *
 * Used by the forced password-change page (login/change-password.php).
 * Verifies the user's current password, saves the new one and clears the
 * force_password_change flag so the user can use the system again.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
initAPI();
requirePost();

try {
    $currentPassword = post('current_password');
    $newPassword     = post('new_password');
    $confirmPassword = post('confirm_password');

    if (empty($currentPassword)) Response::error('Please enter your current password.');
    if (empty($newPassword) || strlen($newPassword) < 6) Response::error('New password must be at least 6 characters.');
    if ($newPassword !== $confirmPassword) Response::error('New password and confirmation do not match.');

    $db = Database::getConnection();
    $uid = (int)getUserId();

    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id AND is_archived = 0 LIMIT 1");
    $stmt->execute([':id' => $uid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
        Response::error('Current password is incorrect.');
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $upd = $db->prepare("UPDATE users SET password_hash = :p, force_password_change = 0 WHERE id = :id");
    $upd->execute([':p' => $hash, ':id' => $uid]);

    // Clear the session flag so the user can use the system normally
    $_SESSION['force_password_change'] = false;

    logActivity($uid, getUsername(), 'change_password', 'User changed their password');

    Response::success('Password changed successfully.');

} catch (Exception $e) {
    error_log('SFI Change Password Error: ' . $e->getMessage());
    Response::error('Failed to change password.', [], 500);
}
