<?php
// Common initialization file - include this at the top of every page

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Manual Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'Src\\';
    $base_dir = __DIR__ . '/../src/';
    
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
