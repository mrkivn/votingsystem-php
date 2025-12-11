<?php

namespace Src\Database;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            require_once __DIR__ . '/../../config/config.php';
            
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->connection = new PDO($dsn, DB_USER, DB_PASS);
            
            // Set PDO error mode to exception
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            // For debugging purposes on InfinityFree
            if (ini_get('display_errors')) {
                echo "Query Failed: " . $e->getMessage() . "<br>";
                echo "SQL: " . $sql . "<br>";
            }
            return false;
        }
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch() : null;
    }

    public function insert($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $this->connection->lastInsertId() : false;
    }

    public function update($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->rowCount() : false;
    }

    public function delete($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->rowCount() : false;
    }
}
