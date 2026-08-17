<?php
/**
 * SFI Queuing System - Logout API
 * POST /api/auth/logout.php
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

initAPI();

try {
    if (isLoggedIn()) {
        logActivity(getUserId(), getUsername(), 'logout', 'User logged out');
    }

    destroySession();

    Response::success('Logged out successfully', [
        'redirect' => BASE_URL . '/login/'
    ]);

} catch (Exception $e) {
    error_log('SFI Logout Error: ' . $e->getMessage());
    // Force destroy session even on error
    session_unset();
    session_destroy();
    Response::success('Logged out successfully', [
        'redirect' => BASE_URL . '/login/'
    ]);
}
