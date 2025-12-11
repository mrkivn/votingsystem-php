<?php
// Debug script - DELETE THIS FILE AFTER TESTING!
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

$host = 'sql304.infinityfree.com';
$user = 'if0_40604027';
$pass = '5xWDoSeNwP0T';
$db = 'if0_40604027_voting';

echo "<p><strong>Host:</strong> $host</p>";
echo "<p><strong>Database:</strong> $db</p>";
echo "<p><strong>User:</strong> $user</p>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Tables found:</h3><ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Check users table
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    echo "<p>Users in database: $count</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database connection failed!</p>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr><p><strong>Delete this file (test_db.php) after testing!</strong></p>";
?>
