<?php

// Load configuration (defines BASE_PATH and starts session)
require_once __DIR__ . '/../config/config.php';

// Manual Autoloader since we don't have Composer
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'Src\\';

    // Base directory for the namespace prefix
    $base_dir = __DIR__ . '/../src/';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Simple Router
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remove trailing slash
$path = rtrim($path, '/');
if ($path === '') {
    $path = '/';
}

// Basic Routing Logic
switch ($path) {
    case '/':
        require __DIR__ . '/../templates/login.php';
        break;
    case '/register':
        require __DIR__ . '/../templates/register.php';
        break;
    case '/dashboard':
        require __DIR__ . '/../templates/dashboard_client.php';
        break;
    case '/admin':
        require __DIR__ . '/../templates/dashboard_admin.php';
        break;
    case '/logout':
        session_start();
        session_destroy();
        header('Location: /');
        break;
    default:
        http_response_code(404);
        echo "404 Not Found";
        break;
}
