<?php

namespace Src\Database;

class Database {
    private static $instance = null;
    private $baseUrl;
    private $apiKey;

    private function __construct() {
        // Using the credentials provided by the user
        $projectId = 'voting-b791c';
        $this->apiKey = 'AIzaSyCauRjj874eNbMhVUwXVPPfEpC8sEMsaZE';
        
        // Standard Firebase Realtime Database URL format
        $this->baseUrl = "https://voting-b791c-default-rtdb.asia-southeast1.firebasedatabase.app";
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getApiKey() {
        return $this->apiKey;
    }

    // Generic request method
    public function request($path, $method = 'GET', $data = null, $auth = true) {
        $url = $this->baseUrl . '/' . ltrim($path, '/') . '.json';
        
        // Add auth token if available and requested
        if ($auth && isset($_SESSION['user']['idToken'])) {
            $url .= '?auth=' . $_SESSION['user']['idToken'];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            // Handle curl error
            return null;
        }
        
        curl_close($ch);

        return json_decode($response, true);
    }
}
