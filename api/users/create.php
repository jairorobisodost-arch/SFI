<?php
/**
 * SFI Queuing System - Create User API
 * POST /api/users/create.php
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
requireRole('admin');
initAPI();
requirePost();

try {
    $username  = trim(post('username'));
    $password  = post('password');
    $fullName  = trim(post('full_name'));
    $role      = post('role', 'teller');
    $counter   = (int)post('assigned_counter', 1);

    $errors = [];
    if (empty($username) || strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
    if (empty($password) || strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if (empty($fullName)) $errors[] = 'Full name is required.';
    if (!in_array($role, ['admin', 'teller', 'employee'])) $errors[] = 'Invalid role.';
    if ($counter < 1 || $counter > 10) $errors[] = 'Invalid counter number.';
    if (!empty($errors)) Response::error('Validation failed.', $errors);

    $db = Database::getConnection();

    // Check unique username
    $stmt = $db->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
    $stmt->execute([':u' => $username]);
    if ($stmt->fetch()) Response::error('Username already exists.');

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, password_hash, full_name, role, assigned_counter, force_password_change) VALUES (:u, :p, :n, :r, :c, 1)");
    $stmt->execute([':u' => $username, ':p' => $hash, ':n' => $fullName, ':r' => $role, ':c' => $counter]);

    logActivity(getUserId(), getUsername(), 'create_user', 'Created user: ' . $username);
    Response::success('User created successfully.', ['id' => $db->lastInsertId()], 201);

} catch (Exception $e) {
    error_log('SFI Create User Error: ' . $e->getMessage());
    Response::error('Failed to create user.', [], 500);
}
