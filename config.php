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
