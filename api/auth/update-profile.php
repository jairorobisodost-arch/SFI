<?php
/**
 * SFI Queuing System - Update Profile API
 * POST /api/auth/update-profile.php
 *
 * Lets the logged-in user update their own profile details
 * (currently: full name).
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
initAPI();
requirePost();

try {
    $fullName = trim(post('full_name'));

    if (empty($fullName)) Response::error('Full name is required.');
    if (strlen($fullName) > 100) Response::error('Full name is too long.');

    $uid = (int)getUserId();
    $db = Database::getConnection();

    $stmt = $db->prepare("UPDATE users SET full_name = :n WHERE id = :id");
    $stmt->execute([':n' => $fullName, ':id' => $uid]);

    // Update the session so the topbar reflects the new name immediately
    $_SESSION['full_name'] = $fullName;

    logActivity($uid, getUsername(), 'update_profile', 'User updated their profile');

    Response::success('Profile updated successfully.', ['full_name' => $fullName]);

} catch (Exception $e) {
    error_log('SFI Update Profile Error: ' . $e->getMessage());
    Response::error('Failed to update profile.', [], 500);
}
