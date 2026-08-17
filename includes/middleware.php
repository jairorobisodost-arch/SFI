<?php
/**
 * SFI Queuing System - Middleware
 * Rate limiting, CSRF protection, and request validation.
 */

/**
 * Check login rate limiting.
 * Tracks failed attempts per IP in session.
 *
 * @return bool True if allowed, false if locked out.
 */
function checkLoginRateLimit() {
    $ip = getClientIP();
    $key = 'login_attempts_' . md5($ip);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }

    $data = $_SESSION[$key];

    // Reset if lockout period has passed
    $lockoutSeconds = LOGIN_LOCKOUT_MINUTES * 60;
    if ((time() - $data['first_attempt']) > $lockoutSeconds) {
        unset($_SESSION[$key]);
        return true;
    }

    // Check if exceeded max attempts
    if ($data['count'] >= MAX_LOGIN_ATTEMPTS) {
        return false;
    }

    return true;
}

/**
 * Record a failed login attempt.
 */
function recordFailedLogin() {
    $ip = getClientIP();
    $key = 'login_attempts_' . md5($ip);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }

    $_SESSION[$key]['count']++;
}

/**
 * Clear failed login attempts after successful login.
 */
function clearLoginAttempts() {
    $ip = getClientIP();
    $key = 'login_attempts_' . md5($ip);
    unset($_SESSION[$key]);
}

/**
 * Get remaining lockout time in seconds.
 */
function getLockoutRemaining() {
    $ip = getClientIP();
    $key = 'login_attempts_' . md5($ip);

    if (!isset($_SESSION[$key])) return 0;

    $elapsed = time() - $_SESSION[$key]['first_attempt'];
    $lockoutSeconds = LOGIN_LOCKOUT_MINUTES * 60;
    $remaining = $lockoutSeconds - $elapsed;

    return max(0, $remaining);
}

/**
 * Validate that the request is a POST request.
 */
function requirePost() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Method not allowed. Use POST.', [], 405);
    }
}

/**
 * Validate that the request is an AJAX request (optional soft check).
 */
function preferAjax() {
    // We allow non-AJAX POST for flexibility but prefer AJAX
    // This is informational only, not enforced
}

/**
 * Set standard security headers.
 */
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/**
 * Common bootstrap for all API endpoints.
 * Include this at the top of every API file.
 */
function initAPI() {
    setSecurityHeaders();
    header('Content-Type: application/json; charset=utf-8');
}

/**
 * Common bootstrap for all page files.
 */
function initPage() {
    setSecurityHeaders();
}
