<?php
/**
 * SFI Queuing System - Helper Functions
 */

/**
 * Redirect to a URL relative to BASE_URL.
 */
function redirect($path) {
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

/**
 * Sanitize a string input.
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Get a value from $_POST safely.
 */
function post($key, $default = '') {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

/**
 * Get a value from $_GET safely.
 */
function get($key, $default = '') {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

/**
 * Get the client's IP address.
 */
function getClientIP() {
    $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = explode(',', $_SERVER[$key])[0];
            return trim($ip);
        }
    }
    return '0.0.0.0';
}

/**
 * Format a date for display.
 */
function formatDate($date, $format = 'F j, Y') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

/**
 * Format a datetime for display.
 */
function formatDateTime($date, $format = 'g:i A') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

/**
 * Format a full datetime string.
 */
function formatFullDateTime($date) {
    if (empty($date)) return '-';
    return date('F j, Y \\a\\t g:i A', strtotime($date));
}

/**
 * Get today's date string.
 */
function today() {
    return date('Y-m-d');
}

/**
 * Canonicalize a contact number for matching: keep digits, then take the
 * last 10 digits so "09171234567" and "+639171234567" match each other.
 */
function normalizeContactNumber($contact) {
    $digits = preg_replace('/\D+/', '', (string)$contact);
    return (strlen($digits) > 10) ? substr($digits, -10) : $digits;
}

/**
 * Normalize a client name for matching: lowercase, trim, collapse spaces.
 */
function normalizeClientName($name) {
    return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string)$name)));
}

/**
 * Convert an Excel serial date number (e.g. 46178) to a readable date.
 */
function excelSerialToDate($serial) {
    if (!is_numeric($serial) || (int)$serial <= 0) return '';
    // 25569 = days between the Excel epoch (1899-12-30) and the Unix epoch
    $unix = ((int)$serial - 25569) * 86400;
    return gmdate('F j, Y', $unix);
}

/**
 * Generate a CSRF token.
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token.
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Log an activity to the database.
 */
function logActivity($userId, $username, $action, $description = '') {
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, username, action, description, ip_address) VALUES (:user_id, :username, :action, :description, :ip)");
        $stmt->execute([
            ':user_id'     => $userId,
            ':username'    => $username,
            ':action'      => $action,
            ':description' => $description,
            ':ip'          => getClientIP()
        ]);
    } catch (Exception $e) {
        error_log('SFI Activity Log Error: ' . $e->getMessage());
    }
}

/**
 * Get a setting value from the database.
 */
function getSetting($key, $default = '') {
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1");
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Generate a unique ticket number for today.
 * Uses a transaction with row locking for race-condition safety.
 *
 * @param PDO    $db     Database connection
 * @param string $prefix Ticket prefix (e.g., PL, SL)
 * @return string        Formatted ticket number (e.g., PL-001)
 */
function generateTicketNumber($db, $prefix) {
    $today = today();

    // Lock and find the highest ticket number for this prefix today
    $stmt = $db->prepare("
        SELECT ticket_number 
        FROM queue_tickets 
        WHERE queue_date = :date AND prefix = :prefix 
        ORDER BY id DESC 
        LIMIT 1 
        FOR UPDATE
    ");
    $stmt->execute([':date' => $today, ':prefix' => $prefix]);
    $last = $stmt->fetch();

    if ($last) {
        // Extract the numeric part after the dash
        $parts = explode('-', $last['ticket_number']);
        $num = (int)end($parts) + 1;
    } else {
        $num = 1;
    }

    return $prefix . '-' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

/**
 * Emit a Socket.IO event via HTTP POST to the Node server.
 * Sends the shared EMIT_TOKEN as a bearer token when configured.
 */
function emitSocketEvent($event, $data = []) {
    try {
        $url = SOCKET_SERVER . '/emit';
        $payload = json_encode([
            'event' => $event,
            'data'  => $data
        ]);

        $headers = ['Content-Type: application/json'];
        if (defined('EMIT_TOKEN') && EMIT_TOKEN !== '') {
            $headers[] = 'Authorization: Bearer ' . EMIT_TOKEN;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 2,
            CURLOPT_CONNECTTIMEOUT => 1,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    } catch (Exception $e) {
        error_log('SFI Socket Emit Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get the count of waiting tickets ahead of a given ticket.
 */
function getWaitingCountAhead($db, $ticketId) {
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM queue_tickets WHERE id < :id AND queue_date = :date AND status = 'waiting'");
    $stmt->execute([':id' => $ticketId, ':date' => today()]);
    $row = $stmt->fetch();
    return $row ? (int)$row['cnt'] : 0;
}

/**
 * Calculate time difference in a human-readable format.
 */
function timeDiff($start, $end) {
    if (empty($start) || empty($end)) return '-';
    $diff = strtotime($end) - strtotime($start);
    $mins = floor($diff / 60);
    $secs = $diff % 60;
    if ($mins > 0) {
        return $mins . 'm ' . $secs . 's';
    }
    return $secs . 's';
}
