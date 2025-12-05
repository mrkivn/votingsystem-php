<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'voting_system');

// Application Configuration
define('APP_NAME', 'Voting System');

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
