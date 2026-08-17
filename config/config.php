<?php
/**
 * SFI Queuing System - Application Configuration
 */

// Application
define('APP_NAME', 'SFI Queuing System');
define('APP_SUBTITLE', 'Smart Loan Queue Management System');
define('APP_VERSION', '1.0.0');

// Paths - auto-detect base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

// Walk up to find the SFI root directory
$sfiRoot = preg_replace('#/(api|admin|kiosk|display|login|config|includes|website)(/.*)?$#', '', $scriptDir);
if ($sfiRoot === $scriptDir) {
    // We're already at root
    $sfiRoot = rtrim($scriptDir, '/');
}

define('BASE_URL', $protocol . '://' . $host . $sfiRoot);
define('ROOT_PATH', realpath(__DIR__ . '/..'));

// Socket.IO Server
define('SOCKET_SERVER', 'http://localhost:4000');

// Secrets (API keys / tokens) are loaded from config/secrets.local.php
// which is git-ignored. Copy config/secrets.template.php to secrets.local.php
// and fill in your keys on a fresh install.
require_once __DIR__ . '/secrets.local.php';

// Cohere AI Chatbot (website)
define('COHERE_MODEL', 'command-r-plus-08-2024');
define('COHERE_MAX_TOKENS', 400); // max words the bot can reply with

// Session
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);

// Pagination
define('ITEMS_PER_PAGE', 20);

// SMS (IPROG SMS) - used for OTP verification on the Check Your Loan portal
// API endpoint, token, and sender info - see "excel file/example" for the sample
// Phone numbers are sent in E.164 format without the leading + (e.g. 639171234567)
define('SMS_API_URL', 'https://www.iprogsms.com/api/v1/sms_messages');
define('SMS_OTP_EXPIRY_MINUTES', 5); // OTP validity window
define('SMS_DEMO_MODE', false);      // Real SMS is sent via IPROG when false

// Timezone
date_default_timezone_set('Asia/Manila');
