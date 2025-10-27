<?php
/**
 * Database Configuration
 */

define('DB_HOST', 'localhost:3307');
define('DB_NAME', 'travelease_db');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

/**
 * Application Configuration
 */
define('BASE_URL', 'http://localhost/travelease');
define('ADMIN_SESSION_TIMEOUT', 3600); // 1 hour in seconds

/**
 * Security Configuration
 */
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_NAME', 'travelease_session');

/**
 * Date/Time Configuration
 */
date_default_timezone_set('Asia/Kolkata');

/**
 * Error Reporting (Set to 0 in production)
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>