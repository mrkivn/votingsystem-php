<?php

namespace Src\Models;

use Src\Database\Database;

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getById($id) {
        return $this->db->fetchOne(
            "SELECT id, email, full_name, role, status, student_id, phone, profile_photo, created_at 
             FROM users WHERE id = ?",
            [$id]
        );
    }

    public function getAll($roleFilter = null, $statusFilter = null) {
        $sql = "SELECT id, email, full_name, role, status, student_id, phone, created_at, verified_at 
                FROM users WHERE 1=1";
        $params = [];

        if ($roleFilter) {
            $sql .= " AND role = ?";
            $params[] = $roleFilter;
        }

        if ($statusFilter) {
            $sql .= " AND status = ?";
            $params[] = $statusFilter;
        }

        $sql .= " ORDER BY created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }

    public function getPendingVoters() {
        return $this->getAll('voter', 'pending');
    }

    public function updateProfile($id, $data) {
        return $this->db->update(
            "UPDATE users 
             SET full_name = ?, student_id = ?, phone = ?
             WHERE id = ?",
            [
                $data['full_name'] ?? null,
                $data['student_id'] ?? null,
                $data['phone'] ?? null,
                $id
            ]
        );
    }

    public function updateProfilePhoto($id, $photo) {
        return $this->db->update(
            "UPDATE users SET profile_photo = ? WHERE id = ?",
            [$photo, $id]
        );
    }

    public function updateStatus($id, $status) {
        $verified = $status === 'approved' ? date('Y-m-d H:i:s') : null;
        
        return $this->db->update(
            "UPDATE users SET status = ?, verified_at = ? WHERE id = ?",
            [$status, $verified, $id]
        );
    }

    public function approveVoter($id) {
        return $this->updateStatus($id, 'approved');
    }

    public function rejectVoter($id) {
        return $this->updateStatus($id, 'rejected');
    }

    public function getStatistics() {
        $stats = [];

        $result = $this->db->fetchOne(
            "SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN role = 'voter' THEN 1 ELSE 0 END) as total_voters,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as total_admins,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_users,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_users,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_users
             FROM users"
        );

        return $result ?? [];
    }

    public function search($query) {
        $searchTerm = "%{$query}%";
        return $this->db->fetchAll(
            "SELECT id, email, full_name, role, status, student_id 
             FROM users 
             WHERE email LIKE ? OR full_name LIKE ? OR student_id LIKE ?
             ORDER BY created_at DESC
             LIMIT 50",
            [$searchTerm, $searchTerm, $searchTerm]
        );
    }

    public function delete($id) {
        return $this->db->delete(
            "DELETE FROM users WHERE id = ?",
            [$id]
        );
    }
}
