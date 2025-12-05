<?php

namespace Src\Models;

use Src\Database\Database;

class Position {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($title, $description, $maxVotes = 1, $displayOrder = 0) {
        return $this->db->insert(
            "INSERT INTO positions (title, description, max_votes, display_order) VALUES (?, ?, ?, ?)",
            [$title, $description, $maxVotes, $displayOrder]
        );
    }

    public function getAll($activeOnly = false) {
        $sql = "SELECT * FROM positions";
        if ($activeOnly) {
            $sql .= " WHERE active = 1";
        }
        $sql .= " ORDER BY display_order ASC, title ASC";
        
        return $this->db->fetchAll($sql);
    }

    public function getById($id) {
        return $this->db->fetchOne(
            "SELECT * FROM positions WHERE id = ?",
            [$id]
        );
    }

    public function update($id, $title, $description, $maxVotes, $displayOrder) {
        return $this->db->update(
            "UPDATE positions SET title = ?, description = ?, max_votes = ?, display_order = ? WHERE id = ?",
            [$title, $description, $maxVotes, $displayOrder, $id]
        );
    }

    public function toggleStatus($id) {
        return $this->db->update(
            "UPDATE positions SET active = NOT active WHERE id = ?",
            [$id]
        );
    }

    public function delete($id) {
        return $this->db->delete(
            "DELETE FROM positions WHERE id = ?",
            [$id]
        );
    }

    public function getWithCandidates($activeOnly = false) {
        $positions = $this->getAll($activeOnly);
        
        foreach ($positions as &$position) {
            $position['candidates'] = $this->db->fetchAll(
                "SELECT * FROM candidates WHERE position_id = ? AND active = 1 ORDER BY display_order ASC, full_name ASC",
                [$position['id']]
            );
            $position['candidate_count'] = count($position['candidates']);
        }
        
        return $positions;
    }
}
