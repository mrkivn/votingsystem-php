<?php
use Src\Auth\Auth;
use Src\Models\Position;
use Src\Models\Candidate;
use Src\Models\Vote;
use Src\Models\VotingPeriod;
use Src\Models\User;

$auth = new Auth();
if (!$auth->isAuthenticated()) {
    header('Location: /votingsystem/');
    exit;
}

$positionModel = new Position();
$candidateModel = new Candidate();
$voteModel = new Vote();
$votingPeriodModel = new VotingPeriod();
$userModel = new User();

$userId = $auth->getCurrentUserId();
$message = '';
$error = '';
$activeTab = $_GET['tab'] ?? 'vote';

// Check if user is approved
if ($_SESSION['user']['status'] !== 'approved') {
    $error = 'Your account is pending approval. You cannot vote until approved by an administrator.';
}

// Handle voting
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'cast_vote' && $_SESSION['user']['status'] === 'approved') {
        $positionId = $_POST['position_id'] ?? 0;
        $candidateId = $_POST['candidate_id'] ?? 0;
        
        // Get active voting period
        $activePeriod = $votingPeriodModel->getActive();
        $periodId = $activePeriod['id'] ?? null;
        
        if (!$periodId) {
            $error = "No active voting period. Voting is currently closed.";
        } else {
            $result = $voteModel->cast($userId, $positionId, $candidateId, $periodId);
            
            if ($result['success']) {
                $message = "Vote cast successfully!";
            } else {
                $error = $result['error'];
            }
        }
    } elseif ($action === 'update_profile') {
        $profileData = [
            'full_name' => $_POST['full_name'] ?? '',
            'student_id' => $_POST['student_id'] ?? '',
            'phone' => $_POST['phone'] ?? ''
        ];
        
        $userModel->updateProfile($userId, $profileData);
        $message = "Profile updated successfully!";
        
        // Update session
        $_SESSION['user']['full_name'] = $profileData['full_name'];
    }
}

// Update voting period statuses
$votingPeriodModel->updateStatuses();

// Get data
$activePeriod = $votingPeriodModel->getActive();
$isVotingOpen = $activePeriod !== null;
$positions = $positionModel->getWithCandidates(true);
$votingProgress = $voteModel->getVotingProgress($userId, $activePeriod['id'] ?? null);
$userVotes = $voteModel->getUserVotes($userId, $activePeriod['id'] ?? null);
$currentUser = $userModel->getById($userId);
$results = $candidateModel->getResults();

// Get current user info
$currentUserName = htmlspecialchars($_SESSION['user']['full_name'] ?? $_SESSION['user']['email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Dashboard - VoteBox</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f4f3f0;
            --text-color: #1a1a1a;
            --primary-color: #ff4d4d;
            --secondary-color: #1a1a1a;
            --accent-color: #4d79ff;
            --border-color: #1a1a1a;
            --card-bg: #ffffff;
            --shadow: 4px 4px 0px 0px #1a1a1a;
            --font-main: 'Space Grotesk', sans-serif;
            --sidebar-width: 250px;
            --topbar-height: 70px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: var(--font-main);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* ===== TOP NAVBAR ===== */
        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--topbar-height);
            background: var(--card-bg);
            border-bottom: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 1000;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .hamburger-btn {
            width: 40px;
            height: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
            background: transparent;
            border: 2px solid var(--border-color);
            cursor: pointer;
            transition: all 0.2s;
        }

        .hamburger-btn:hover {
            background: var(--border-color);
        }

        .hamburger-btn:hover span {
            background: white;
        }

        .hamburger-btn span {
            width: 18px;
            height: 2px;
            background: var(--border-color);
            transition: all 0.3s;
        }

        .hamburger-btn.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .hamburger-btn.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger-btn.active span:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -1px;
        }

        .logo .highlight {
            color: var(--primary-color);
        }

        .topbar-center {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .voting-status {
            padding: 8px 16px;
            border: 2px solid var(--border-color);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .voting-status.open {
            background: #28a745;
            color: white;
        }

        .voting-status.closed {
            background: #6c757d;
            color: white;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 15px;
            border: 2px solid var(--border-color);
            background: var(--bg-color);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: var(--accent-color);
            border: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
        }

        .user-name {
            font-weight: 600;
        }

        .user-role {
            font-size: 0.75rem;
            opacity: 0.7;
        }

        .logout-btn {
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            border: 2px solid var(--border-color);
            font-weight: 600;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: all 0.2s;
        }

        .logout-btn:hover {
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0px 0px var(--border-color);
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed;
            top: var(--topbar-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--topbar-height));
            background: var(--card-bg);
            border-right: 2px solid var(--border-color);
            padding: 20px 0;
            transition: transform 0.3s ease;
            z-index: 900;
            overflow-y: auto;
        }

        .sidebar.collapsed {
            transform: translateX(-100%);
        }

        .sidebar-nav {
            list-style: none;
        }

        .sidebar-nav li {
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            color: var(--text-color);
            font-weight: 500;
            transition: all 0.2s;
            border-left: 4px solid transparent;
        }

        .nav-link:hover {
            background: var(--bg-color);
            border-left-color: var(--primary-color);
        }

        .nav-link.active {
            background: var(--primary-color);
            color: white;
            border-left-color: var(--border-color);
        }

        .nav-link .icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid var(--border-color);
        }

        .sidebar-section-title {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-color);
            opacity: 0.6;
            margin-bottom: 10px;
            padding: 0 20px;
            font-weight: 700;
        }

        .progress-sidebar {
            padding: 0 20px;
        }

        .progress-sidebar p {
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .progress-bar-sidebar {
            height: 10px;
            background: #eee;
            border: 1px solid var(--border-color);
        }

        .progress-bar-sidebar .progress-fill {
            height: 100%;
            background: var(--primary-color);
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 30px;
            min-height: calc(100vh - var(--topbar-height));
            transition: margin-left 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 0;
        }

        /* ===== PAGE HEADER ===== */
        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .page-header p {
            opacity: 0.7;
        }

        /* ===== ALERTS ===== */
        .alert {
            padding: 15px 20px;
            border: 2px solid var(--border-color);
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }

        /* ===== CARDS ===== */
        .card {
            background: var(--card-bg);
            border: 2px solid var(--border-color);
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.2s;
        }

        .card:hover {
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0px 0px var(--border-color);
        }

        .card h2 {
            font-size: 1.25rem;
            margin-bottom: 20px;
            color: var(--text-color);
        }

        /* ===== VOTING PROGRESS CARD ===== */
        .voting-progress-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #000;
        }

        .voting-progress-card h2 {
            color: white;
        }

        .progress-bar-large {
            height: 30px;
            background: rgba(255,255,255,0.3);
            border: 2px solid white;
            margin: 15px 0;
        }

        .progress-bar-large .progress-fill {
            background: white;
            height: 100%;
        }

        .progress-items {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }

        .progress-item {
            padding: 5px 10px;
            border: 1px solid white;
            font-size: 0.9rem;
        }

        .progress-item.completed {
            background: rgba(255,255,255,0.3);
        }

        /* ===== POSITION CARDS ===== */
        .position-card {
            margin-bottom: 30px;
        }

        .position-card.voted {
            opacity: 0.7;
            background: #f9f9f9;
        }

        .position-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
        }

        .position-desc {
            opacity: 0.7;
            margin: 5px 0;
        }

        .vote-info {
            font-size: 0.9rem;
            margin-top: 10px;
        }

        /* ===== CANDIDATES GRID ===== */
        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .candidate-option {
            cursor: pointer;
        }

        .candidate-option input[type="radio"] {
            display: none;
        }

        .candidate-card {
            border: 2px solid var(--border-color);
            padding: 15px;
            background: white;
            transition: all 0.2s;
        }

        .candidate-option input:checked + .candidate-card {
            border-color: var(--primary-color);
            background: #fff8f0;
            box-shadow: var(--shadow);
        }

        .candidate-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .candidate-info h3 {
            margin-bottom: 5px;
        }

        .party {
            color: #666;
            font-size: 0.9rem;
            font-style: italic;
        }

        .platform {
            margin: 10px 0;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .view-more {
            color: var(--primary-color);
            font-size: 0.9rem;
            text-decoration: underline;
        }

        .voted-message {
            text-align: center;
            padding: 30px;
            background: #d4edda;
            border: 2px solid #28a745;
        }

        .voted-message .small {
            opacity: 0.7;
            margin-top: 5px;
        }

        /* ===== FORMS ===== */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"] {
            width: 100%;
            padding: 12px;
            background: #fff;
            border: 2px solid var(--border-color);
            font-family: var(--font-main);
            font-size: 1rem;
            outline: none;
        }

        input:focus {
            border-color: var(--primary-color);
        }

        input:disabled {
            background: #eee;
            opacity: 0.7;
        }

        /* ===== BUTTONS ===== */
        .btn-primary {
            background: var(--primary-color);
            color: white;
            border: 2px solid var(--border-color);
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: all 0.2s;
            width: 100%;
        }

        .btn-primary:hover {
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0px 0px var(--border-color);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-color);
            border: 2px solid var(--border-color);
            padding: 10px 20px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-block;
        }

        .btn-secondary:hover {
            background: var(--border-color);
            color: white;
        }

        .btn-large {
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
        }

        /* ===== BADGES ===== */
        .badge {
            padding: 4px 8px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-success, .badge-approved {
            background: #28a745;
            color: white;
        }

        .badge-pending {
            background: #ffc107;
            color: #000;
        }

        .badge-rejected {
            background: #dc3545;
            color: white;
        }

        /* ===== GRID LAYOUTS ===== */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        @media (max-width: 900px) {
            .grid-2 { grid-template-columns: 1fr; }
        }

        /* ===== CANDIDATE DETAIL ===== */
        .candidate-detail {
            max-width: 800px;
        }

        .back-link {
            color: var(--primary-color);
            text-decoration: none;
            margin-bottom: 20px;
            display: inline-block;
        }

        .candidate-header-full {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        .party-large {
            font-size: 1.1rem;
            color: #666;
            font-style: italic;
        }

        .detail-section {
            margin: 25px 0;
        }

        .detail-section h3 {
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .detail-section p {
            line-height: 1.7;
        }

        /* ===== CANDIDATES LIST ===== */
        .candidates-list {
            display: grid;
            gap: 20px;
            margin-top: 20px;
        }

        .candidate-list-item {
            border: 2px solid var(--border-color);
            padding: 20px;
        }

        .candidate-summary h3 {
            margin-bottom: 5px;
        }

        .preview {
            margin: 10px 0;
            opacity: 0.8;
        }

        /* ===== RESULTS ===== */
        .notice {
            background: #fff3cd;
            padding: 10px;
            border-left: 4px solid #ffc107;
        }

        .results-list {
            margin-top: 20px;
        }

        .result-item {
            padding: 15px;
            border: 2px solid var(--border-color);
            margin-bottom: 10px;
            background: white;
        }

        .result-item.leader {
            border-color: var(--primary-color);
            background: #fff8f0;
            box-shadow: var(--shadow);
        }

        .result-info {
            display: flex;
            gap: 15px;
            margin-bottom: 8px;
        }

        .rank {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            min-width: 40px;
        }

        .result-candidate {
            flex: 1;
        }

        .result-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 8px;
        }

        .votes {
            font-weight: 600;
        }

        .percentage {
            color: var(--primary-color);
            font-weight: 700;
        }

        .progress-bar {
            height: 20px;
            background: #eee;
            border: 1px solid var(--border-color);
        }

        .progress-fill {
            height: 100%;
            background: var(--primary-color);
            transition: width 0.3s;
        }

        /* ===== PROFILE ===== */
        .info-list {
            margin-top: 15px;
        }

        .info-item {
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
        }

        .vote-history {
            margin-top: 15px;
        }

        .history-item {
            padding: 10px;
            border-left: 3px solid var(--primary-color);
            background: #f9f9f9;
            margin-bottom: 10px;
        }

        .history-item .date {
            font-size: 0.85rem;
            opacity: 0.7;
        }

        /* ===== RESPONSIVE ===== */
        
        /* Tablet */
        @media (max-width: 1024px) {
            .main-content {
                padding: 20px;
            }
            
            .grid-2 {
                grid-template-columns: 1fr;
            }
            
            .candidates-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }
        
        /* Mobile */
        @media (max-width: 768px) {
            :root {
                --sidebar-width: 280px;
                --topbar-height: 60px;
            }
            
            .topbar {
                padding: 0 15px;
            }
            
            .logo {
                font-size: 1.2rem;
            }
            
            .sidebar {
                transform: translateX(-100%);
                box-shadow: none;
            }

            .sidebar:not(.collapsed) {
                transform: translateX(0);
                box-shadow: 5px 0 20px rgba(0,0,0,0.2);
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .topbar-right .user-info,
            .topbar-center {
                display: none;
            }
            
            .topbar-right .logout-btn {
                padding: 8px 15px;
                font-size: 0.9rem;
            }

            .candidates-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header h1 {
                font-size: 1.5rem;
            }
            
            .card {
                padding: 15px;
            }
            
            .stat-card h3 {
                font-size: 1.8rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .position-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .result-info {
                flex-direction: column;
                gap: 5px;
            }
            
            .result-stats {
                flex-wrap: wrap;
            }
            
            .btn-large {
                padding: 12px;
                font-size: 1rem;
            }
            
            .progress-items {
                flex-direction: column;
            }
            
            .voting-progress-card {
                padding: 15px;
            }
            
            .progress-bar-large {
                height: 20px;
            }
            
            .info-item {
                flex-direction: column;
                gap: 5px;
            }
        }
        
        /* Small Mobile */
        @media (max-width: 480px) {
            .hamburger-btn {
                width: 36px;
                height: 36px;
            }
            
            .hamburger-btn span {
                width: 16px;
            }
            
            .topbar-right .logout-btn {
                padding: 6px 12px;
                font-size: 0.85rem;
            }
            
            .candidate-card {
                padding: 12px;
            }
            
            .candidate-info h3 {
                font-size: 1rem;
            }
        }

        /* SVG Icons */
        .icon svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
        }
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="hamburger-btn" id="sidebarToggle" aria-label="Toggle Sidebar">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <a href="/votingsystem/dashboard" class="logo">VOTE<span class="highlight">BOX</span></a>
        </div>
        <div class="topbar-center">
            <?php if ($isVotingOpen): ?>
                <span class="voting-status open">🗳️ Voting is OPEN</span>
            <?php else: ?>
                <span class="voting-status closed">Voting is Closed</span>
            <?php endif; ?>
        </div>
        <div class="topbar-right">
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($currentUserName, 0, 1)); ?></div>
                <div>
                    <div class="user-name"><?php echo $currentUserName; ?></div>
                    <div class="user-role">Voter</div>
                </div>
            </div>
            <a href="/votingsystem/logout" class="logout-btn">Logout</a>
        </div>
    </header>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <ul class="sidebar-nav">
            <li>
                <a href="?tab=vote" class="nav-link <?php echo $activeTab === 'vote' ? 'active' : ''; ?>">
                    <span class="icon">
                        <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                    </span>
                    Cast Vote
                </a>
            </li>
            <li>
                <a href="?tab=candidates" class="nav-link <?php echo $activeTab === 'candidates' ? 'active' : ''; ?>">
                    <span class="icon">
                        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </span>
                    View Candidates
                </a>
            </li>
            <li>
                <a href="?tab=results" class="nav-link <?php echo $activeTab === 'results' ? 'active' : ''; ?>">
                    <span class="icon">
                        <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    </span>
                    Results
                </a>
            </li>
            <li>
                <a href="?tab=profile" class="nav-link <?php echo $activeTab === 'profile' ? 'active' : ''; ?>">
                    <span class="icon">
                        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </span>
                    My Profile
                </a>
            </li>
        </ul>

        <?php if ($isVotingOpen && $_SESSION['user']['status'] === 'approved'): ?>
        <div class="sidebar-section">
            <div class="sidebar-section-title">Voting Progress</div>
            <div class="progress-sidebar">
                <?php 
                $completedVotes = count(array_filter($votingProgress, fn($p) => $p['has_voted']));
                $totalPositions = count($votingProgress);
                $progressPercent = $totalPositions > 0 ? ($completedVotes / $totalPositions * 100) : 0;
                ?>
                <p><strong><?php echo $completedVotes; ?></strong> of <strong><?php echo $totalPositions; ?></strong> completed</p>
                <div class="progress-bar-sidebar">
                    <div class="progress-fill" style="width: <?php echo $progressPercent; ?>%"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($activeTab === 'vote'): ?>
            <!-- VOTE CASTING TAB -->
            <div class="page-header">
                <h1>Cast Your Vote</h1>
                <p>Select your preferred candidate for each position.</p>
            </div>

            <?php if (!$isVotingOpen): ?>
                <div class="card">
                    <h2>Voting is Closed</h2>
                    <p>There is no active voting period at this time. Please check back later.</p>
                </div>
            <?php elseif ($_SESSION['user']['status'] !== 'approved'): ?>
                <div class="card">
                    <h2>Account Pending Approval</h2>
                    <p>Your account needs to be approved by an administrator before you can vote.</p>
                    <p>Please wait for approval notification.</p>
                </div>
            <?php else: ?>
                <!-- Voting Progress -->
                <div class="card voting-progress-card">
                    <h2>Your Voting Progress</h2>
                    <div class="progress-list">
                        <?php 
                        $completedVotes = count(array_filter($votingProgress, fn($p) => $p['has_voted']));
                        $totalPositions = count($votingProgress);
                        ?>
                        <p><strong><?php echo $completedVotes; ?> of <?php echo $totalPositions; ?></strong> positions completed</p>
                        <div class="progress-bar-large">
                            <div class="progress-fill" style="width: <?php echo $totalPositions > 0 ? ($completedVotes / $totalPositions * 100) : 0; ?>%"></div>
                        </div>
                        <div class="progress-items">
                            <?php foreach ($votingProgress as $prog): ?>
                                <span class="progress-item <?php echo $prog['has_voted'] ? 'completed' : 'pending'; ?>">
                                    <?php echo $prog['has_voted'] ? '✓' : '○'; ?> <?php echo htmlspecialchars($prog['position_title']); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Voting Forms -->
                <?php foreach ($positions as $position): ?>
                    <?php 
                    $hasVoted = $voteModel->hasVoted($userId, $position['id'], $activePeriod['id'] ?? null);
                    ?>
                    <div class="card position-card <?php echo $hasVoted ? 'voted' : ''; ?>">
                        <div class="position-header">
                            <div>
                                <h2><?php echo htmlspecialchars($position['title']); ?></h2>
                                <?php if ($position['description']): ?>
                                    <p class="position-desc"><?php echo htmlspecialchars($position['description']); ?></p>
                                <?php endif; ?>
                                <p class="vote-info">You can vote for <strong><?php echo $position['max_votes']; ?></strong> candidate(s)</p>
                            </div>
                            <?php if ($hasVoted): ?>
                                <span class="badge badge-success">✓ Voted</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!$hasVoted): ?>
                            <form method="POST" action="/votingsystem/dashboard?tab=vote" class="voting-form">
                                <input type="hidden" name="action" value="cast_vote">
                                <input type="hidden" name="position_id" value="<?php echo $position['id']; ?>">
                                
                                <div class="candidates-grid">
                                    <?php foreach ($position['candidates'] as $candidate): ?>
                                        <label class="candidate-option">
                                            <input type="radio" name="candidate_id" value="<?php echo $candidate['id']; ?>" required>
                                            <div class="candidate-card">
                                                <div class="candidate-info">
                                                    <h3><?php echo htmlspecialchars($candidate['full_name']); ?></h3>
                                                    <?php if ($candidate['party_affiliation']): ?>
                                                        <p class="party"><?php echo htmlspecialchars($candidate['party_affiliation']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($candidate['platform']): ?>
                                                    <p class="platform"><?php echo htmlspecialchars(substr($candidate['platform'], 0, 150)) . '...'; ?></p>
                                                    <a href="?tab=candidates&id=<?php echo $candidate['id']; ?>" class="view-more">View Full Profile →</a>
                                                <?php endif; ?>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                
                                <button type="submit" class="btn-primary btn-large">Cast Vote for <?php echo htmlspecialchars($position['title']); ?></button>
                            </form>
                        <?php else: ?>
                            <div class="voted-message">
                                <p>✓ Thank you for voting for <strong><?php echo htmlspecialchars($position['title']); ?></strong>!</p>
                                <p class="small">Your vote has been securely recorded.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        <?php elseif ($activeTab === 'candidates'): ?>
            <!-- VIEW CANDIDATE PROFILES TAB -->
            <div class="page-header">
                <h1>Candidate Profiles</h1>
                <p>Learn more about the candidates running for each position.</p>
            </div>

            <?php 
            $viewCandidateId = $_GET['id'] ?? null;
            if ($viewCandidateId):
                $candidate = $candidateModel->getById($viewCandidateId);
                if ($candidate): ?>
                    <div class="card candidate-detail">
                        <a href="?tab=candidates" class="back-link">← Back to all candidates</a>
                        <div class="candidate-header-full">
                            <div>
                                <h1><?php echo htmlspecialchars($candidate['full_name']); ?></h1>
                                <h3>Running for: <?php echo htmlspecialchars($candidate['position_title']); ?></h3>
                                <?php if ($candidate['party_affiliation']): ?>
                                    <p class="party-large"><?php echo htmlspecialchars($candidate['party_affiliation']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($candidate['platform']): ?>
                            <div class="detail-section">
                                <h3>Platform & Promises</h3>
                                <p><?php echo nl2br(htmlspecialchars($candidate['platform'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($candidate['biography']): ?>
                            <div class="detail-section">
                                <h3>Biography & Background</h3>
                                <p><?php echo nl2br(htmlspecialchars($candidate['biography'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($candidate['contact_email']): ?>
                            <div class="detail-section">
                                <h3>Contact</h3>
                                <p><a href="mailto:<?php echo htmlspecialchars($candidate['contact_email']); ?>"><?php echo htmlspecialchars($candidate['contact_email']); ?></a></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif;
            else: ?>
                <?php foreach ($positions as $position): ?>
                    <div class="card">
                        <h2><?php echo htmlspecialchars($position['title']); ?></h2>
                        <div class="candidates-list">
                            <?php foreach ($position['candidates'] as $candidate): ?>
                                <div class="candidate-list-item">
                                    <div class="candidate-summary">
                                        <h3><?php echo htmlspecialchars($candidate['full_name']); ?></h3>
                                        <?php if ($candidate['party_affiliation']): ?>
                                            <p class="party"><?php echo htmlspecialchars($candidate['party_affiliation']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($candidate['platform']): ?>
                                            <p class="preview"><?php echo htmlspecialchars(substr($candidate['platform'], 0, 120)) . '...'; ?></p>
                                        <?php endif; ?>
                                        <a href="?tab=candidates&id=<?php echo $candidate['id']; ?>" class="btn-secondary">View Full Profile</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        <?php elseif ($activeTab === 'results'): ?>
            <!-- RESULTS VIEWING TAB -->
            <div class="page-header">
                <h1>Election Results</h1>
                <?php if ($isVotingOpen): ?>
                    <p class="notice">⚠️ Voting is still in progress. Results shown are preliminary and may change.</p>
                <?php else: ?>
                    <p>Final election results after voting has ended.</p>
                <?php endif; ?>
            </div>

            <?php 
            $resultsByPosition = [];
            foreach ($results as $result) {
                $resultsByPosition[$result['position_title']][] = $result;
            }
            
            foreach ($resultsByPosition as $posTitle => $candidates): ?>
                <div class="card">
                    <h2><?php echo htmlspecialchars($posTitle); ?></h2>
                    <div class="results-list">
                        <?php 
                        $totalVotes = array_sum(array_column($candidates, 'vote_count'));
                        $rank = 1;
                        foreach ($candidates as $candidate): 
                            $percentage = $totalVotes > 0 ? round(($candidate['vote_count'] / $totalVotes) * 100, 1) : 0;
                            $isLeader = $rank === 1 && $candidate['vote_count'] > 0;
                        ?>
                            <div class="result-item <?php echo $isLeader ? 'leader' : ''; ?>">
                                <div class="result-info">
                                    <span class="rank">#<?php echo $rank; ?></span>
                                    <div class="result-candidate">
                                        <strong><?php echo htmlspecialchars($candidate['full_name']); ?></strong>
                                        <?php if ($candidate['party_affiliation']): ?>
                                            <span class="party">(<?php echo htmlspecialchars($candidate['party_affiliation']); ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="result-stats">
                                    <span class="votes"><?php echo $candidate['vote_count']; ?> votes</span>
                                    <span class="percentage"><?php echo $percentage; ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </div>
                        <?php 
                            $rank++;
                        endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php elseif ($activeTab === 'profile'): ?>
            <!-- PROFILE MANAGEMENT TAB -->
            <div class="page-header">
                <h1>My Profile</h1>
                <p>Manage your account information and view your voting history.</p>
            </div>

            <div class="grid-2">
                <div class="card">
                    <h2>Update Profile</h2>
                    <?php if ($currentUser): ?>
                    <form method="POST" action="/votingsystem/dashboard?tab=profile">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label>Email (cannot be changed)</label>
                            <input type="email" value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($currentUser['full_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Student ID</label>
                            <input type="text" name="student_id" value="<?php echo htmlspecialchars($currentUser['student_id'] ?? ''); ?>" placeholder="Enter your student ID">
                        </div>
                        
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>" placeholder="(123) 456-7890">
                        </div>
                        
                        <button type="submit" class="btn-primary">Update Profile</button>
                    </form>
                    <?php else: ?>
                    <p>Unable to load profile data. Please try logging out and back in.</p>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h2>Account Information</h2>
                    <div class="info-list">
                        <div class="info-item">
                            <strong>Account Status:</strong>
                            <span class="badge badge-<?php echo $currentUser ? $currentUser['status'] : 'unknown'; ?>">
                                <?php echo $currentUser ? ucfirst($currentUser['status']) : 'Unknown'; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <strong>Role:</strong>
                            <span><?php echo $currentUser ? ucfirst($currentUser['role']) : 'Unknown'; ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Member Since:</strong>
                            <span><?php echo $currentUser && isset($currentUser['created_at']) ? date('F d, Y', strtotime($currentUser['created_at'])) : 'N/A'; ?></span>
                        </div>
                    </div>

                    <h3 style="margin-top: 25px;">My Voting History</h3>
                    <?php if (empty($userVotes)): ?>
                        <p>You haven't cast any votes yet.</p>
                    <?php else: ?>
                        <div class="vote-history">
                            <?php foreach ($userVotes as $vote): ?>
                                <div class="history-item">
                                    <strong><?php echo htmlspecialchars($vote['position_title']); ?></strong>
                                    <p>Voted for: <?php echo htmlspecialchars($vote['candidate_name']); ?></p>
                                    <span class="date"><?php echo date('M d, Y g:i A', strtotime($vote['voted_at'])); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>
    </main>

    <script>
        // Sidebar Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');

        // Check for saved sidebar state
        // When sidebar is OPEN (not collapsed) -> show X (active class)
        // When sidebar is CLOSED (collapsed) -> show hamburger (no active class)
        const sidebarCollapsed = localStorage.getItem('voterSidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            // No active class when collapsed (show hamburger)
        } else {
            // Sidebar is open, show X
            sidebarToggle.classList.add('active');
        }

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            // Toggle active class inversely - active when sidebar is OPEN
            if (sidebar.classList.contains('collapsed')) {
                sidebarToggle.classList.remove('active');
            } else {
                sidebarToggle.classList.add('active');
            }
            
            // Save state
            localStorage.setItem('voterSidebarCollapsed', sidebar.classList.contains('collapsed'));
        });

        // Mobile handling
        if (window.innerWidth <= 768) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            sidebarToggle.classList.remove('active');
        }
    </script>
</body>
</html>
