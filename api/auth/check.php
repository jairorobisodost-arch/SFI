<?php
/**
 * SFI Queuing System - Auth Check API
 * GET /api/auth/check.php
 * Returns the current session user or 401 if not logged in.
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

initAPI();

if (!isLoggedIn()) {
    Response::error('Not authenticated.', [], 401);
}

$user = getSessionUser();

Response::success('Authenticated', [
    'user' => $user,
    'force_password_change' => $_SESSION['force_password_change'] ?? false
]);
