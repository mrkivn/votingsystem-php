<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'voting_system');

// Detect environment and set base path
// Vercel uses /api/index.php, XAMPP uses /votingsystem
if (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false) {
    define('BASE_PATH', ''); // Vercel - no prefix
} else {
    define('BASE_PATH', '/votingsystem'); // XAMPP localhost
}

// Application Configuration
define('APP_URL', 'http://localhost' . BASE_PATH);
define('APP_NAME', 'Voting System');

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
