<?php
/**
 * SFI Queuing System - SMS Helper (IPROG SMS)
 * Sends SMS via the IPROG SMS REST API (see "excel file/example" for the sample).
 *
 * Also provides OTP generation/verification helpers used by the Check Your Loan portal.
 */

/**
 * Normalize a Philippine number to IPROG format (E.164 without the leading +).
 * Examples: "09931478468" -> "639931478468", "+639931478468" -> "639931478468",
 * "639931478468" stays as-is.
 */
function toIprogNumber($number) {
    $digits = preg_replace('/\D+/', '', (string)$number);
    if ($digits === '') return '';
    // 09xxxxxxxxx -> 639xxxxxxxxx
    if (strlen($digits) === 11 && $digits[0] === '0') {
        return '63' . substr($digits, 1);
    }
    // 9xxxxxxxxx -> 639xxxxxxxxx
    if (strlen($digits) === 10 && $digits[0] === '9') {
        return '63' . $digits;
    }
    // Already starts with 63
    if (strlen($digits) === 12 && substr($digits, 0, 2) === '63') {
        return $digits;
    }
    return $digits;
}

/**
 * Send an SMS via the IPROG SMS API.
 *
 * @param string $to      Recipient number (any PH format is normalized automatically)
 * @param string $message SMS body text
 * @return array ['ok' => bool, 'message' => string, 'raw' => mixed]
 */
function sendSMS($to, $message) {
    $phone = toIprogNumber($to);
    if ($phone === '') {
        return ['ok' => false, 'message' => 'Invalid phone number.', 'raw' => null];
    }

    // Demo mode: log instead of sending a real SMS (useful while testing locally)
    if (defined('SMS_DEMO_MODE') && SMS_DEMO_MODE) {
        $line = '[' . date('Y-m-d H:i:s') . '] SMS to ' . $phone . ': ' . $message . PHP_EOL;
        @file_put_contents(ROOT_PATH . '/sms-log.txt', $line, FILE_APPEND);
        return ['ok' => true, 'message' => 'Demo mode: SMS logged to sms-log.txt', 'raw' => ['demo' => true]];
    }

    $data = [
        'api_token'    => SMS_API_TOKEN,
        'message'      => $message,
        'phone_number' => $phone
    ];

    $ch = curl_init(SMS_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        error_log('SFI SMS Error: cURL failed: ' . $err);
        return ['ok' => false, 'message' => 'Failed to reach SMS gateway.', 'raw' => $err];
    }

    // Check the IPROG response body — it must report status 200 for the SMS to be queued
    $body = json_decode($response, true);
    if (is_array($body) && isset($body['status']) && (int)$body['status'] === 200) {
        return ['ok' => true, 'message' => 'SMS sent.', 'raw' => $response];
    }

    $detail = is_array($body) ? json_encode($body) : $response;
    error_log('SFI SMS Error: IPROG rejected the SMS: ' . $detail);
    return ['ok' => false, 'message' => 'SMS gateway rejected the message.', 'raw' => $response];
}

/**
 * Generate a numeric OTP code.
 */
function generateOTP($length = 6) {
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= random_int(0, 9);
    }
    return $code;
}

/**
 * Send an OTP code to a phone number and store it in the database.
 *
 * @param string $contactNumber Client contact number (any PH format)
 * @return array ['ok' => bool, 'message' => string, 'otp' => string|null, 'raw' => mixed]
 */
function sendOTP($contactNumber) {
    $db = Database::getConnection();
    $code = generateOTP(6);
    $expiry = date('Y-m-d H:i:s', time() + SMS_OTP_EXPIRY_MINUTES * 60);

    // Invalidate any previous unused codes for this number
    $stmt = $db->prepare("UPDATE otp_codes SET used = 1 WHERE contact_number = :c AND used = 0");
    $stmt->execute([':c' => $contactNumber]);

    // Store the new code
    $stmt = $db->prepare("INSERT INTO otp_codes (contact_number, otp_code, purpose, expires_at, attempts, max_attempts) VALUES (:c, :o, 'loan_lookup', :e, 0, 5)");
    $stmt->execute([':c' => $contactNumber, ':o' => $code, ':e' => $expiry]);

    $message = 'Your SFI verification code is: ' . $code . '. Valid for ' . SMS_OTP_EXPIRY_MINUTES . ' minutes. Do not share this code with anyone.';
    $result = sendSMS($contactNumber, $message);

    return [
        'ok'      => $result['ok'],
        'message' => $result['message'],
        'otp'     => $code, // returned so demo mode / tests can read it; verify endpoint never relies on it
        'raw'     => $result['raw']
    ];
}

/**
 * Verify an OTP code for a contact number.
 *
 * @param string $contactNumber Client contact number
 * @param string $code          The OTP entered by the user
 * @return array ['ok' => bool, 'message' => string]
 */
function verifyOTP($contactNumber, $code) {
    $db = Database::getConnection();
    $code = trim($code);
    if (!preg_match('/^\d{4,8}$/', $code)) {
        return ['ok' => false, 'message' => 'Invalid verification code.'];
    }

    $stmt = $db->prepare("SELECT * FROM otp_codes WHERE contact_number = :c AND used = 0 AND purpose = 'loan_lookup' ORDER BY id DESC LIMIT 1");
    $stmt->execute([':c' => $contactNumber]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return ['ok' => false, 'message' => 'No active verification code found. Please request a new code.'];
    }

    // Check expiry
    if (strtotime($row['expires_at']) < time()) {
        return ['ok' => false, 'message' => 'The verification code has expired. Please request a new one.'];
    }

    // Check attempt limit
    if ((int)$row['attempts'] >= (int)$row['max_attempts']) {
        return ['ok' => false, 'message' => 'Too many incorrect attempts. Please request a new code.'];
    }

    if (!hash_equals($row['otp_code'], $code)) {
        $stmt = $db->prepare("UPDATE otp_codes SET attempts = attempts + 1 WHERE id = :id");
        $stmt->execute([':id' => $row['id']]);
        return ['ok' => false, 'message' => 'Incorrect verification code. Please try again.'];
    }

    // Success - mark as used
    $stmt = $db->prepare("UPDATE otp_codes SET used = 1 WHERE id = :id");
    $stmt->execute([':id' => $row['id']]);

    return ['ok' => true, 'message' => 'Verification successful.'];
}
