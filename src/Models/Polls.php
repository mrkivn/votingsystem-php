<?php

namespace Src\Models;

use Src\Database\Database;

class Polls {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function createPoll($title, $description, $candidates) {
        $pollData = [
            'title' => $title,
            'description' => $description,
            'created_at' => time(),
            'active' => true
        ];

        // Create poll
        $response = $this->db->request('polls', 'POST', $pollData);
        $pollId = $response['name']; // Firebase returns 'name' as the key for POST

        // Add candidates
        foreach ($candidates as $name) {
            if (!empty(trim($name))) {
                $this->db->request("polls/$pollId/candidates", 'POST', [
                    'name' => trim($name),
                    'votes' => 0
                ]);
            }
        }

        return $pollId;
    }

    public function getAllPolls() {
        return $this->db->request('polls') ?: [];
    }

    public function getPoll($pollId) {
        return $this->db->request("polls/$pollId");
    }

    public function vote($pollId, $candidateId, $userId) {
        // Check if user already voted
        $voteCheck = $this->db->request("votes/$pollId/$userId");
        
        if ($voteCheck) {
            return ['success' => false, 'error' => 'You have already voted in this poll.'];
        }

        // Increment vote count
        // Note: Without transactions (REST), there's a race condition risk, but acceptable for this scope.
        $candidatePath = "polls/$pollId/candidates/$candidateId";
        $candidate = $this->db->request($candidatePath);
        
        $currentVotes = $candidate['votes'] ?? 0;
        $this->db->request("$candidatePath/votes", 'PUT', $currentVotes + 1);

        // Record that user voted
        $this->db->request("votes/$pollId/$userId", 'PUT', [
            'timestamp' => time(),
            'candidate_id' => $candidateId
        ]);

        return ['success' => true];
    }
    
    public function hasVoted($pollId, $userId) {
        $voteCheck = $this->db->request("votes/$pollId/$userId");
        return $voteCheck !== null;
    }
}
