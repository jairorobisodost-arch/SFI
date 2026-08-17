<?php
/**
 * SFI Queuing System - Secrets Template
 * Copy this file to config/secrets.local.php and fill in your real keys.
 * secrets.local.php is git-ignored so your keys are never uploaded to GitHub.
 */

// Shared secret for the Socket.IO /emit endpoint. Must match EMIT_TOKEN in server/.env.
define('EMIT_TOKEN', 'change-me-to-a-long-random-string');

// Cohere AI Chatbot (website) - get a key at https://dashboard.cohere.com/api-keys
define('COHERE_API_KEY', 'your-cohere-api-key');

// SMS (IPROG SMS) - used for OTP verification on the Check Your Loan portal
define('SMS_API_TOKEN', 'your-iprog-sms-token');
