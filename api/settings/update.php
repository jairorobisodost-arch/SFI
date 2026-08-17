<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin(); requireRole('admin'); initAPI(); requirePost();
try {
    $db = Database::getConnection();
    // Accept JSON body of key-value pairs
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        // Fallback to POST form data
        $input = $_POST;
    }
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:key, :val) ON DUPLICATE KEY UPDATE setting_value = :val2");
    foreach ($input as $key => $val) {
        if (in_array($key, ['company_name','display_title','announcement_message','enable_audio','announcement_voice','announcement_speed','auto_reset_queue','kiosk_reset_time','session_timeout','max_login_attempts','login_lockout_time'])) {
            $stmt->execute([':key' => $key, ':val' => $val, ':val2' => $val]);
        }
    }
    logActivity(getUserId(), getUsername(), 'update_settings', 'Updated system settings');
    Response::success('Settings saved successfully.');
} catch (Exception $e) {
    error_log('SFI Settings Error: ' . $e->getMessage());
    Response::error('Failed to save settings.', [], 500);
}
