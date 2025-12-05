<?php
require_once __DIR__ . '/includes/init.php';

// Destroy session and redirect to login
session_destroy();
header('Location: index.php');
exit;
