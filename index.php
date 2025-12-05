<?php
// XAMPP Entry Point for Voting System
require_once 'config/config.php';

// Manual Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'Src\\';
    $base_dir = __DIR__ . '/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Initialize Auth class for protected routes
use Src\Auth\Auth;
$auth = new Auth();

// Simple Router
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remove the base directory from path
$basePath = '/votingsystem';
if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

// Remove trailing slash
$path = rtrim($path, '/');
if ($path === '') {
    $path = '/';
}

// Basic Routing Logic
switch ($path) {
    case '/':
        require __DIR__ . '/templates/login.php';
        break;
    case '/register':
        require __DIR__ . '/templates/register.php';
        break;
    case '/dashboard':
        // Protected route - require authentication
        if (!$auth->isAuthenticated()) {
            header('Location: /votingsystem/');
            exit;
        }
        require __DIR__ . '/templates/dashboard_client.php';
        break;
    case '/admin':
        // Protected route - require admin authentication
        if (!$auth->isAuthenticated() || !$auth->isAdmin()) {
            header('Location: /votingsystem/');
            exit;
        }
        require __DIR__ . '/templates/dashboard_admin.php';
        break;
    case '/logout':
        session_destroy();
        header('Location: /votingsystem/');
        exit;
        break;
    default:
        http_response_code(404);
        echo "404 Not Found";
        break;
}
