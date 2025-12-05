<?php

namespace Src\Models;

use Src\Database\Database;

class VotingPeriod {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($title, $description, $startTime, $endTime, $createdBy) {
        return $this->db->insert(
            "INSERT INTO voting_periods (title, description, start_time, end_time, created_by) 
             VALUES (?, ?, ?, ?, ?)",
            [$title, $description, $startTime, $endTime, $createdBy]
        );
    }

    public function getAll() {
        return $this->db->fetchAll(
            "SELECT vp.*, u.full_name as created_by_name 
             FROM voting_periods vp
             LEFT JOIN users u ON vp.created_by = u.id
             ORDER BY vp.start_time DESC"
        );
    }

    public function getById($id) {
        return $this->db->fetchOne(
            "SELECT * FROM voting_periods WHERE id = ?",
            [$id]
        );
    }

    public function getActive() {
        $now = date('Y-m-d H:i:s');
        return $this->db->fetchOne(
            "SELECT * FROM voting_periods 
             WHERE start_time <= ? AND end_time >= ? AND status = 'active'
             ORDER BY start_time DESC LIMIT 1",
            [$now, $now]
        );
    }

    public function updateStatus($id, $status) {
        return $this->db->update(
            "UPDATE voting_periods SET status = ? WHERE id = ?",
            [$status, $id]
        );
    }

    public function update($id, $title, $description, $startTime, $endTime) {
        return $this->db->update(
            "UPDATE voting_periods 
             SET title = ?, description = ?, start_time = ?, end_time = ?
             WHERE id = ?",
            [$title, $description, $startTime, $endTime, $id]
        );
    }

    public function delete($id) {
        return $this->db->delete(
            "DELETE FROM voting_periods WHERE id = ?",
            [$id]
        );
    }

    public function isVotingOpen() {
        return $this->getActive() !== null;
    }

    public function updateStatuses() {
        $now = date('Y-m-d H:i:s');
        
        // Update to active if start time has passed
        $this->db->update(
            "UPDATE voting_periods 
             SET status = 'active' 
             WHERE start_time <= ? AND end_time >= ? AND status = 'upcoming'",
            [$now, $now]
        );
        
        // Update to closed if end time has passed
        $this->db->update(
            "UPDATE voting_periods 
             SET status = 'closed' 
             WHERE end_time < ? AND status != 'closed'",
            [$now]
        );
    }

    public function getStatistics($id) {
        $period = $this->getById($id);
        if (!$period) return null;

        $stats = [
            'period' => $period,
            'total_votes' => 0,
            'total_voters' => 0,
            'positions' => []
        ];

        // Get total votes for this period
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM votes WHERE voting_period_id = ?",
            [$id]
        );
        $stats['total_votes'] = $result['count'] ?? 0;

        // Get unique voters
        $result = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT user_id) as count FROM votes WHERE voting_period_id = ?",
            [$id]
        );
        $stats['total_voters'] = $result['count'] ?? 0;

        // Get votes by position
        $stats['positions'] = $this->db->fetchAll(
            "SELECT p.title, p.id, COUNT(v.id) as vote_count
             FROM positions p
             LEFT JOIN votes v ON p.id = v.position_id AND v.voting_period_id = ?
             GROUP BY p.id, p.title
             ORDER BY p.display_order ASC",
            [$id]
        );

        return $stats;
    }
}
