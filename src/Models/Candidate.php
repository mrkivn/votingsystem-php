<?php

namespace Src\Models;

use Src\Database\Database;

class Candidate {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($data) {
        return $this->db->insert(
            "INSERT INTO candidates (position_id, full_name, platform, biography, photo, party_affiliation, contact_email, display_order) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['position_id'],
                $data['full_name'],
                $data['platform'] ?? null,
                $data['biography'] ?? null,
                $data['photo'] ?? null,
                $data['party_affiliation'] ?? null,
                $data['contact_email'] ?? null,
                $data['display_order'] ?? 0
            ]
        );
    }

    public function getAll($activeOnly = false) {
        $sql = "SELECT c.*, p.title as position_title 
                FROM candidates c 
                JOIN positions p ON c.position_id = p.id";
        
        if ($activeOnly) {
            $sql .= " WHERE c.active = 1";
        }
        
        $sql .= " ORDER BY p.display_order ASC, c.display_order ASC, c.full_name ASC";
        
        return $this->db->fetchAll($sql);
    }

    public function getById($id) {
        return $this->db->fetchOne(
            "SELECT c.*, p.title as position_title, p.description as position_description
             FROM candidates c 
             JOIN positions p ON c.position_id = p.id 
             WHERE c.id = ?",
            [$id]
        );
    }

    public function getByPosition($positionId, $activeOnly = true) {
        $sql = "SELECT * FROM candidates WHERE position_id = ?";
        if ($activeOnly) {
            $sql .= " AND active = 1";
        }
        $sql .= " ORDER BY display_order ASC, full_name ASC";
        
        return $this->db->fetchAll($sql, [$positionId]);
    }

    public function update($id, $data) {
        return $this->db->update(
            "UPDATE candidates 
             SET full_name = ?, platform = ?, biography = ?, party_affiliation = ?, 
                 contact_email = ?, display_order = ?
             WHERE id = ?",
            [
                $data['full_name'],
                $data['platform'] ?? null,
                $data['biography'] ?? null,
                $data['party_affiliation'] ?? null,
                $data['contact_email'] ?? null,
                $data['display_order'] ?? 0,
                $id
            ]
        );
    }

    public function updatePhoto($id, $photo) {
        return $this->db->update(
            "UPDATE candidates SET photo = ? WHERE id = ?",
            [$photo, $id]
        );
    }

    public function toggleStatus($id) {
        return $this->db->update(
            "UPDATE candidates SET active = NOT active WHERE id = ?",
            [$id]
        );
    }

    public function delete($id) {
        return $this->db->delete(
            "DELETE FROM candidates WHERE id = ?",
            [$id]
        );
    }

    public function getVoteCount($id) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM votes WHERE candidate_id = ?",
            [$id]
        );
        return $result['count'] ?? 0;
    }

    public function getResults($positionId = null) {
        $sql = "SELECT c.*, p.title as position_title, COUNT(v.id) as vote_count
                FROM candidates c
                JOIN positions p ON c.position_id = p.id
                LEFT JOIN votes v ON c.id = v.candidate_id";
        
        if ($positionId) {
            $sql .= " WHERE c.position_id = ?";
            $params = [$positionId];
        } else {
            $params = [];
        }
        
        $sql .= " GROUP BY c.id, p.title
                  ORDER BY p.display_order ASC, vote_count DESC, c.full_name ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
}
