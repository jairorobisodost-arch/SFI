<?php
/**
 * SFI Queuing System - Login API
 * POST /api/auth/login.php
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

initAPI();
requirePost();

try {
    // Check rate limiting
    if (!checkLoginRateLimit()) {
        $remaining = ceil(getLockoutRemaining() / 60);
        Response::error("Too many failed attempts. Please try again in {$remaining} minute(s).", [], 429);
    }

    $username = post('username');
    $password = post('password');

    // Validate input
    if (empty($username) || empty($password)) {
        Response::error('Please enter your username and password.');
    }

    // Sanitize username
    $username = trim($username);

    $db = Database::getConnection();

    // Find user
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :username AND is_archived = 0 LIMIT 1");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    // Verify user exists and password matches
    if (!$user || !password_verify($password, $user['password_hash'])) {
        recordFailedLogin();
        Response::error('Invalid username or password.');
    }

    // Check if user is active
    if ($user['status'] !== 'active') {
        recordFailedLogin();
        Response::error('Your account is inactive. Please contact the administrator.');
    }

    // Successful login - clear attempts and set session
    clearLoginAttempts();
    setLoginSession($user);

    // Log activity
    logActivity($user['id'], $user['username'], 'login', 'User logged in successfully');

    // Determine redirect URL based on role
    // admin  -> Admin Dashboard
    // teller -> Employee Queuing Dashboard
    if ($user['role'] === 'admin') {
        $redirect = BASE_URL . '/admin/dashboard.php';
    } else { // teller
        $redirect = BASE_URL . '/employee/queuing.php';
    }

    Response::success('Login successful', [
        'redirect' => $redirect,
        'user' => [
            'id'        => $user['id'],
            'username'  => $user['username'],
            'full_name' => $user['full_name'],
            'role'      => $user['role'],
            'counter'   => $user['assigned_counter'],
            'force_password_change' => (bool)$user['force_password_change']
        ]
    ]);

} catch (PDOException $e) {
    error_log('SFI Login Error: ' . $e->getMessage());
    Response::error('A system error occurred. Please try again later.', [], 500);
}
