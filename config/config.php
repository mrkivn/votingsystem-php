<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'voting_system');

// Base Path Configuration
// For XAMPP localhost: '/votingsystem'
// For InfinityFree (root): ''
define('BASE_PATH', '/votingsystem');

// Application Configuration
define('APP_URL', 'http://localhost' . BASE_PATH);
define('APP_NAME', 'Voting System');

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
