<?php

namespace Src\Auth;

use Src\Database\Database;

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function register($email, $password, $fullName = '', $role = 'voter') {
        // Validate inputs
        if (empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'Email and password are required'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }

        // Check if user already exists
        $existingUser = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = ?",
            [$email]
        );

        if ($existingUser) {
            return ['success' => false, 'error' => 'Email already registered'];
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Status: pending for voters, approved for admins
        $status = $role === 'admin' ? 'approved' : 'pending';

        // Insert user
        $userId = $this->db->insert(
            "INSERT INTO users (email, password, full_name, role, status) VALUES (?, ?, ?, ?, ?)",
            [$email, $hashedPassword, $fullName, $role, $status]
        );

        if ($userId) {
            // Auto login for approved users only
            if ($status === 'approved') {
                $_SESSION['user'] = [
                    'id' => $userId,
                    'email' => $email,
                    'full_name' => $fullName,
                    'role' => $role,
                    'status' => $status
                ];
                $_SESSION['role'] = $role;
            }

            return [
                'success' => true, 
                'uid' => $userId,
                'status' => $status,
                'message' => $status === 'pending' 
                    ? 'Registration successful! Please wait for admin approval.' 
                    : 'Registration successful!'
            ];
        } else {
            return ['success' => false, 'error' => 'Registration failed'];
        }
    }

    public function login($email, $password) {
        // Validate inputs
        if (empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'Email and password are required'];
        }

        // Fetch user from database
        $user = $this->db->fetchOne(
            "SELECT id, email, password, full_name, role, status FROM users WHERE email = ?",
            [$email]
        );

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid email or password'];
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'error' => 'Invalid email or password'];
        }

        // Check if voter is approved
        if ($user['role'] === 'voter' && $user['status'] !== 'approved') {
            if ($user['status'] === 'pending') {
                return ['success' => false, 'error' => 'Your account is pending approval. Please wait for admin verification.'];
            } else {
                return ['success' => false, 'error' => 'Your account has been rejected. Please contact the administrator.'];
            }
        }

        // Set session
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'status' => $user['status']
        ];
        $_SESSION['role'] = $user['role'];

        return ['success' => true];
    }

    public function logout() {
        session_destroy();
    }

    public function isAuthenticated() {
        return isset($_SESSION['user']);
    }

    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public function getCurrentUserId() {
        return $_SESSION['user']['id'] ?? null;
    }

    public function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }
}
