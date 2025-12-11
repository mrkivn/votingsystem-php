<?php
// Database Configuration - InfinityFree
define('DB_HOST', 'sql304.infinityfree.com');
define('DB_USER', 'if0_40604027');
define('DB_PASS', '5xWDoSeNwP0T');
define('DB_NAME', 'if0_40604027_voting');

// Application Configuration
define('APP_NAME', 'Voting System');

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set Timezone
date_default_timezone_set('Asia/Manila');

// Error Reporting (Enable for debugging on InfinityFree)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
