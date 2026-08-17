<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin(); requireRole('admin'); initAPI();
try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings ORDER BY id ASC");
    $settings = [];
    while ($row = $stmt->fetch()) { $settings[$row['setting_key']] = $row['setting_value']; }
    Response::success('Settings loaded', ['settings' => $settings]);
} catch (Exception $e) { Response::error('Failed to load settings.', [], 500); }
