<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'voting_system');

// Detect environment and set base path
// Check if running on Vercel (by hostname) or localhost
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (strpos($host, 'vercel.app') !== false || strpos($host, 'vercel') !== false) {
    define('BASE_PATH', ''); // Vercel - no prefix
} elseif (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
    define('BASE_PATH', '/votingsystem'); // XAMPP localhost
} else {
    define('BASE_PATH', ''); // Default to no prefix for other hosts
}

// Application Configuration
define('APP_URL', 'http://localhost' . BASE_PATH);
define('APP_NAME', 'Voting System');

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
