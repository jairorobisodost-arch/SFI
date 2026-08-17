<?php
/**
 * SFI Queuing System - Common Bootstrap
 * Include this file at the top of every PHP page/API endpoint.
 * It loads all required configuration and helper files.
 */

// Error reporting - disable display in production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/middleware.php';
