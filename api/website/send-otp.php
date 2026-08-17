<?php
/**
 * SFI Queuing System - Send OTP API
 * POST /api/website/send-otp.php
 *
 * The client enters their FULL NAME + CONTACT NUMBER. If both match an existing
 * client record, a 6-digit OTP is generated, stored, and sent to their number
 * via SMS. The OTP must then be verified with verify-otp.php before loan info
 * is shown.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/sms.php';
initAPI();
requirePost();

try {
    $name   = post('name');
    $number = post('number');

    // ---- Validation ----
    if (mb_strlen(trim($name)) < 2) {
        Response::error('Please enter your full name.');
    }
    $digits = preg_replace('/\D+/', '', $number);
    if (strlen($digits) < 10) {
        Response::error('Please enter a valid contact number.');
    }

    // ---- Rate limiting (10 attempts per 15 minutes per browser/IP) ----
    $ip = getClientIP();
    $key = 'lookup_attempts_' . md5($ip);
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first' => time()];
    }
    if (time() - $_SESSION[$key]['first'] > 900) {
        $_SESSION[$key] = ['count' => 0, 'first' => time()];
    }
    if ($_SESSION[$key]['count'] >= 10) {
        Response::error('Too many attempts. Please try again after 15 minutes.', [], 429);
    }

    $db = Database::getConnection();

    // ---- Find the client: number (canonical, acts as the access code) ----
    $canon = normalizeContactNumber($digits);
    $stmt = $db->prepare("
        SELECT c.full_name, c.contact_number
        FROM clients c
        WHERE c.contact_number LIKE :c AND c.is_archived = 0
        LIMIT 10
    ");
    $stmt->execute([':c' => '%' . $canon . '%']);

    $match = null;
    $tokens = array_values(array_filter(preg_split('/\s+/', normalizeClientName($name))));
    foreach ($stmt as $row) {
        if (normalizeContactNumber($row['contact_number']) !== $canon) continue;
        $storedName = normalizeClientName($row['full_name']);
        $nameOk = true;
        foreach ($tokens as $t) {
            if ($t !== '' && strpos($storedName, $t) === false) {
                $nameOk = false;
                break;
            }
        }
        if ($nameOk) {
            $match = $row;
            break;
        }
    }

    // ---- Generic failure so names can't be probed ----
    if (!$match) {
        $_SESSION[$key]['count']++;
        Response::error('No matching record found. Please double-check your full name and contact number.', [], 404);
    }

    // ---- Send OTP ----
    $result = sendOTP($match['contact_number']);
    if (!$result['ok']) {
        error_log('SFI OTP Send Error: ' . $result['message']);
        Response::error('Failed to send the verification code. Please try again later.', [], 500);
    }

    $_SESSION[$key] = ['count' => 0, 'first' => time()];
    $_SESSION['otp_pending_number'] = $match['contact_number'];

    $data = [
        'message' => 'A verification code has been sent to your number. Please enter it below.',
        'expires_in' => SMS_OTP_EXPIRY_MINUTES * 60,
        'demo' => false
    ];
    // In demo mode, include the code so local testing is possible without real SMS
    if (defined('SMS_DEMO_MODE') && SMS_DEMO_MODE && isset($result['otp'])) {
        $data['demo'] = true;
        $data['demo_otp'] = $result['otp'];
    }

    Response::success('Verification code sent.', $data);

} catch (Exception $e) {
    error_log('SFI Send OTP Error: ' . $e->getMessage());
    Response::error('A system error occurred. Please try again later.', [], 500);
}
