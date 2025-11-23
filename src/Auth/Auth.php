<?php

namespace Src\Auth;

use Src\Database\Database;

class Auth {
    private $db;
    private $apiKey;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->apiKey = $this->db->getApiKey();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function register($email, $password, $role = 'client') {
        // 1. Create User in Firebase Auth
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:signUp?key=" . $this->apiKey;
        $data = [
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true
        ];

        $response = $this->makeAuthRequest($url, $data);

        if (isset($response['localId'])) {
            $uid = $response['localId'];
            $idToken = $response['idToken'];

            // Store session immediately to allow DB write
            $_SESSION['user'] = [
                'uid' => $uid,
                'email' => $response['email'],
                'idToken' => $idToken
            ];

            // 2. Store user role in Realtime Database
            // We use the DB class which now uses the session token we just set
            $this->db->request("users/$uid", 'PUT', [
                'email' => $email,
                'role' => $role,
                'created_at' => time()
            ]);

            return ['success' => true, 'uid' => $uid];
        } else {
            return ['success' => false, 'error' => $response['error']['message'] ?? 'Registration failed'];
        }
    }

    public function login($email, $password) {
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=" . $this->apiKey;
        $data = [
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true
        ];

        $response = $this->makeAuthRequest($url, $data);

        if (isset($response['localId'])) {
            $_SESSION['user'] = [
                'uid' => $response['localId'],
                'email' => $response['email'],
                'idToken' => $response['idToken']
            ];

            // Fetch role from DB
            $userData = $this->db->request("users/" . $response['localId']);
            $_SESSION['role'] = $userData['role'] ?? 'client';

            return ['success' => true];
        } else {
            return ['success' => false, 'error' => $response['error']['message'] ?? 'Login failed'];
        }
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

    private function makeAuthRequest($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}
