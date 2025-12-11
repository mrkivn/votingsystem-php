<?php

namespace Src\Models;

use Src\Database\Database;

class Vote {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function cast($userId, $positionId, $candidateId, $votingPeriodId = null) {
        // Check if user already voted for this position
        if ($this->hasVoted($userId, $positionId, $votingPeriodId)) {
            return ['success' => false, 'error' => 'You have already voted for this position.'];
        }

        // Verify user exists and is approved (double check)
        $user = $this->db->fetchOne(
            "SELECT status FROM users WHERE id = ?", 
            [$userId]
        );

        if (!$user || $user['status'] !== 'approved') {
            return ['success' => false, 'error' => 'Your account is not approved to vote.'];
        }

        // Verify candidate belongs to position
        $candidate = $this->db->fetchOne(
            "SELECT id, position_id FROM candidates WHERE id = ? AND position_id = ? AND active = 1",
            [$candidateId, $positionId]
        );

        if (!$candidate) {
            return ['success' => false, 'error' => 'Invalid candidate selection.'];
        }

        try {
            $conn = $this->db->getConnection();
            $conn->beginTransaction();

            // Insert vote
            $this->db->insert(
                "INSERT INTO votes (user_id, position_id, candidate_id, voting_period_id) 
                 VALUES (?, ?, ?, ?)",
                [$userId, $positionId, $candidateId, $votingPeriodId]
            );

            // Increment candidate vote count
            $this->db->update(
                "UPDATE candidates SET votes = votes + 1 WHERE id = ?",
                [$candidateId]
            );

            $conn->commit();
            return ['success' => true];
        } catch (\Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return ['success' => false, 'error' => 'An error occurred while voting.'];
        }
    }

    public function hasVoted($userId, $positionId, $votingPeriodId = null) {
        $sql = "SELECT id FROM votes WHERE user_id = ? AND position_id = ?";
        $params = [$userId, $positionId];

        if ($votingPeriodId) {
            $sql .= " AND voting_period_id = ?";
            $params[] = $votingPeriodId;
        }

        $vote = $this->db->fetchOne($sql, $params);
        return $vote !== null;
    }

    public function getUserVotes($userId, $votingPeriodId = null) {
        $sql = "SELECT v.*, c.full_name as candidate_name, p.title as position_title
                FROM votes v
                JOIN candidates c ON v.candidate_id = c.id
                JOIN positions p ON v.position_id = p.id
                WHERE v.user_id = ?";
        
        $params = [$userId];
        
        if ($votingPeriodId) {
            $sql .= " AND v.voting_period_id = ?";
            $params[] = $votingPeriodId;
        }
        
        $sql .= " ORDER BY p.display_order ASC";
        
        return $this->db->fetchAll($sql, $params);
    }

    public function getVotingProgress($userId, $votingPeriodId = null) {
        // Get all positions
        $positions = $this->db->fetchAll(
            "SELECT id, title, max_votes FROM positions WHERE active = 1 ORDER BY display_order ASC"
        );

        $progress = [];
        foreach ($positions as $position) {
            $voted = $this->hasVoted($userId, $position['id'], $votingPeriodId);
            $progress[] = [
                'position_id' => $position['id'],
                'position_title' => $position['title'],
                'max_votes' => $position['max_votes'],
                'has_voted' => $voted
            ];
        }

        return $progress;
    }

    public function getTurnoutStatistics($votingPeriodId = null) {
        $sql = "SELECT 
                    COUNT(DISTINCT v.user_id) as voters_participated,
                    (SELECT COUNT(*) FROM users WHERE role = 'voter' AND status = 'approved') as total_eligible_voters,
                    COUNT(v.id) as total_votes_cast
                FROM votes v";
        
        if ($votingPeriodId) {
            $sql .= " WHERE v.voting_period_id = ?";
            $result = $this->db->fetchOne($sql, [$votingPeriodId]);
        } else {
            $result = $this->db->fetchOne($sql);
        }

        if ($result) {
            $result['turnout_percentage'] = $result['total_eligible_voters'] > 0
                ? round(($result['voters_participated'] / $result['total_eligible_voters']) * 100, 2)
                : 0;
        }

        return $result;
    }
}
