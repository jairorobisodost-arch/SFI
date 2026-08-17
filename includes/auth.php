<?php
/**
 * SFI Queuing System - Authentication Helpers
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is logged in.
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require the user to be logged in. Redirect to login if not.
 */
function requireLogin() {
    if (!isLoggedIn()) {
        // If it's an API request (AJAX), return JSON error
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
            exit;
        }
        redirect('login/');
    }

    // Force password change: after an admin resets a password, the user can
    // only visit the change-password page until they set a new one.
    if (!empty($_SESSION['force_password_change'])) {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $allowed = (strpos($path, 'login/change-password.php') !== false)
                || (strpos($path, 'api/auth/change-password.php') !== false)
                || (strpos($path, 'api/auth/logout.php') !== false);
        if (!$allowed) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'You must change your password first.']);
                exit;
            }
            redirect('login/change-password.php');
        }
    }

    // Check session timeout
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        if ($elapsed > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
                exit;
            }
            redirect('login/');
        }
    }

    // Update last activity
    $_SESSION['last_activity'] = time();
}

/**
 * Require a specific role. Redirect to dashboard if unauthorized.
 */
function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            Response::error('Access denied. Insufficient permissions.', [], 403);
        }
        redirect('admin/dashboard.php');
    }
}

/**
 * Get the current logged-in user's data from session.
 */
function getSessionUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'               => $_SESSION['user_id'],
        'username'         => $_SESSION['username'],
        'full_name'        => $_SESSION['full_name'],
        'role'             => $_SESSION['role'],
        'assigned_counter' => $_SESSION['assigned_counter'],
    ];
}

/**
 * Get just the user ID from session.
 */
function getUserId() {
    return $_SESSION['user_id'] ?? 0;
}

/**
 * Get just the username from session.
 */
function getUsername() {
    return $_SESSION['username'] ?? '';
}

/**
 * Get the user's role from session.
 */
function getUserRole() {
    return $_SESSION['role'] ?? '';
}

/**
 * Get the user's assigned counter from session.
 */
function getAssignedCounter() {
    return $_SESSION['assigned_counter'] ?? 1;
}

/**
 * Set session data after successful login.
 */
function setLoginSession($user) {
    // Regenerate session ID for security
    session_regenerate_id(true);

    $_SESSION['user_id']          = $user['id'];
    $_SESSION['username']         = $user['username'];
    $_SESSION['full_name']        = $user['full_name'];
    $_SESSION['role']             = $user['role'];
    $_SESSION['assigned_counter'] = $user['assigned_counter'];
    $_SESSION['last_activity']    = time();
    $_SESSION['login_time']       = time();
    $_SESSION['force_password_change'] = (bool)($user['force_password_change'] ?? false);
}

/**
 * Destroy the session (logout).
 */
function destroySession() {
    session_unset();
    session_destroy();
}
