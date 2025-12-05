<?php
use Src\Auth\Auth;
use Src\Models\Position;
use Src\Models\Candidate;
use Src\Models\User;
use Src\Models\VotingPeriod;
use Src\Models\Vote;

$auth = new Auth();
if (!$auth->isAuthenticated() || !$auth->isAdmin()) {
    header('Location: /votingsystem/');
    exit;
}

$positionModel = new Position();
$candidateModel = new Candidate();
$userModel = new User();
$votingPeriodModel = new VotingPeriod();
$voteModel = new Vote();

$message = '';
$error = '';
$activeTab = $_GET['tab'] ?? 'dashboard';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_position':
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $maxVotes = $_POST['max_votes'] ?? 1;
            $displayOrder = $_POST['display_order'] ?? 0;
            
            if ($title) {
                $positionModel->create($title, $description, $maxVotes, $displayOrder);
                $message = "Position added successfully!";
            }
            break;
            
        case 'add_candidate':
            $candidateData = [
                'position_id' => $_POST['position_id'] ?? 0,
                'full_name' => $_POST['full_name'] ?? '',
                'platform' => $_POST['platform'] ?? '',
                'biography' => $_POST['biography'] ?? '',
                'party_affiliation' => $_POST['party_affiliation'] ?? '',
                'contact_email' => $_POST['contact_email'] ?? '',
                'display_order' => $_POST['display_order'] ?? 0
            ];
            
            if ($candidateData['position_id'] && $candidateData['full_name']) {
                $candidateModel->create($candidateData);
                $message = "Candidate added successfully!";
            }
            break;
            
        case 'approve_voter':
            $userId = $_POST['user_id'] ?? 0;
            if ($userId) {
                $userModel->approveVoter($userId);
                $message = "Voter approved successfully!";
            }
            break;
            
        case 'reject_voter':
            $userId = $_POST['user_id'] ?? 0;
            if ($userId) {
                $userModel->rejectVoter($userId);
                $message = "Voter rejected.";
            }
            break;
            
        case 'create_voting_period':
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $startTime = $_POST['start_time'] ?? '';
            $endTime = $_POST['end_time'] ?? '';
            
            if ($title && $startTime && $endTime) {
                $votingPeriodModel->create($title, $description, $startTime, $endTime, $auth->getCurrentUserId());
                $message = "Voting period created successfully!";
            }
            break;
            
        case 'update_period_status':
            $periodId = $_POST['period_id'] ?? 0;
            $status = $_POST['status'] ?? '';
            
            if ($periodId && $status) {
                $votingPeriodModel->updateStatus($periodId, $status);
                $message = "Voting period status updated!";
            }
            break;
            
        case 'toggle_candidate':
            $candidateId = $_POST['candidate_id'] ?? 0;
            if ($candidateId) {
                $candidateModel->toggleStatus($candidateId);
                $message = "Candidate status toggled!";
            }
            break;
    }
}

// Update voting period statuses automatically
$votingPeriodModel->updateStatuses();

// Get data for dashboard
$userStats = $userModel->getStatistics();
$pendingVoters = $userModel->getPendingVoters();
$allPositions = $positionModel->getWithCandidates(true);
$allCandidates = $candidateModel->getAll();
$votingPeriods = $votingPeriodModel->getAll();
$turnoutStats = $voteModel->getTurnoutStatistics();
$results = $candidateModel->getResults();

// Get current user info
$currentUserName = htmlspecialchars($_SESSION['user']['full_name'] ?? $_SESSION['user']['email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - VoteBox</title>
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
            background: var(--primary-color);
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

        .nav-link .badge {
            margin-left: auto;
            background: var(--primary-color);
            color: white;
            font-size: 0.75rem;
            padding: 2px 8px;
            border: 2px solid var(--border-color);
            font-weight: 700;
        }

        .nav-link.active .badge {
            background: white;
            color: var(--primary-color);
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

        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 2px solid var(--border-color);
            padding: 20px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: all 0.2s;
        }

        .stat-card:hover {
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0px 0px var(--border-color);
        }

        .stat-card h3 {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-card p {
            margin: 0;
            opacity: 0.7;
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

        /* ===== GRID LAYOUTS ===== */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 900px) {
            .grid-2 { grid-template-columns: 1fr; }
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
        input[type="number"],
        input[type="datetime-local"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            background: #fff;
            border: 2px solid var(--border-color);
            font-family: var(--font-main);
            font-size: 1rem;
            outline: none;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: var(--primary-color);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-secondary:hover {
            background: var(--border-color);
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
            border: 2px solid var(--border-color);
            padding: 8px 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            border: 2px solid var(--border-color);
            padding: 8px 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        .btn-link {
            color: var(--primary-color);
            text-decoration: underline;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }

        /* ===== TABLES ===== */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table th {
            background: var(--bg-color);
            font-weight: 600;
        }

        .data-table tr:hover {
            background: var(--bg-color);
        }

        /* ===== BADGES ===== */
        .badge {
            padding: 4px 8px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-success, .badge-approved, .badge-active {
            background: #28a745;
            color: white;
        }

        .badge-inactive, .badge-rejected, .badge-closed {
            background: #dc3545;
            color: white;
        }

        .badge-pending, .badge-upcoming {
            background: #ffc107;
            color: #000;
        }

        /* ===== QUICK ACTIONS ===== */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 15px;
        }

        .quick-actions a {
            text-align: center;
        }

        /* ===== PENDING LIST ===== */
        .pending-list {
            list-style: none;
        }

        .pending-list li {
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .pending-list li:last-child {
            border-bottom: none;
        }

        .pending-list .small {
            font-size: 0.85rem;
            opacity: 0.7;
            display: block;
        }

        /* ===== POSITION SECTIONS ===== */
        .position-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        .position-section:last-child {
            border-bottom: none;
        }

        .position-section h3 {
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .text-muted {
            opacity: 0.7;
        }

        /* ===== RESULTS ===== */
        .position-results {
            margin-bottom: 35px;
        }

        .position-results h3 {
            margin-bottom: 15px;
        }

        .results-list {
            margin-top: 15px;
        }

        .result-item {
            padding: 15px;
            border: 2px solid var(--border-color);
            margin-bottom: 10px;
            background: white;
        }

        .result-item.winner {
            border-color: var(--primary-color);
            background: #fff8f0;
            box-shadow: var(--shadow);
        }

        .result-info {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 8px;
        }

        .rank {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            min-width: 40px;
        }

        .party {
            opacity: 0.7;
            font-size: 0.9rem;
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

        /* ===== ACTIVE PERIOD ===== */
        .active-period {
            padding: 20px;
            background: #d4edda;
            border: 2px solid #28a745;
        }

        .active-period h3 {
            color: #155724;
            margin-bottom: 10px;
        }

        .active-period p {
            margin-bottom: 5px;
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
            
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            /* Make tables scrollable on tablet */
            .data-table {
                display: block;
                overflow-x: auto;
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

            .topbar-right .user-info {
                display: none;
            }
            
            .topbar-right .logout-btn {
                padding: 8px 15px;
                font-size: 0.9rem;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
            
            .page-header h1 {
                font-size: 1.5rem;
            }
            
            .card {
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .stat-card {
                padding: 15px;
            }
            
            .stat-card h3 {
                font-size: 1.8rem;
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
            
            .pending-list li {
                padding: 8px 0;
            }
            
            .active-period {
                padding: 15px;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                text-align: left;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .stat-card h3 {
                font-size: 1.5rem;
                margin-bottom: 0;
            }
            
            .btn-primary, .btn-secondary {
                padding: 10px 16px;
                font-size: 0.9rem;
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
            <a href="/votingsystem/admin" class="logo">VOTE<span class="highlight">BOX</span></a>
        </div>
        <div class="topbar-right">
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($currentUserName, 0, 1)); ?></div>
                <div>
                    <div class="user-name"><?php echo $currentUserName; ?></div>
                    <div class="user-role">Administrator</div>
                </div>
            </div>
            <a href="/votingsystem/logout" class="logout-btn">Logout</a>
        </div>
    </header>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <ul class="sidebar-nav">
            <li>
                <a href="?tab=dashboard" class="nav-link <?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>">
                    <span class="icon">
                        <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    </span>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="?tab=candidates" class="nav-link <?php echo $activeTab === 'candidates' ? 'active' : ''; ?>">
                    <span class="icon">
                        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </span>
                    Candidates
                </a>
            </li>
            <li>
                <a href="?tab=voters" class="nav-link <?php echo $activeTab === 'voters' ? 'active' : ''; ?>">
                    <span class="icon">
                        <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>
                    </span>
                    Voter Registration
                    <?php if (count($pendingVoters) > 0): ?>
                        <span class="badge"><?php echo count($pendingVoters); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="?tab=voting_control" class="nav-link <?php echo $activeTab === 'voting_control' ? 'active' : ''; ?>">
                    <span class="icon">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </span>
                    Voting Control
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
        </ul>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Quick Stats</div>
            <ul class="sidebar-nav">
                <li>
                    <div class="nav-link" style="cursor: default; border-left-color: transparent;">
                        <span class="icon">
                            <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                        </span>
                        <?php echo $turnoutStats['turnout_percentage'] ?? 0; ?>% Turnout
                    </div>
                </li>
            </ul>
        </div>
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

        <?php if ($activeTab === 'dashboard'): ?>
            <!-- DASHBOARD TAB -->
            <div class="page-header">
                <h1>Admin Dashboard</h1>
                <p>Welcome, <?php echo $currentUserName; ?>!</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $userStats['total_voters'] ?? 0; ?></h3>
                    <p>Total Voters</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $userStats['approved_users'] ?? 0; ?></h3>
                    <p>Approved Voters</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $userStats['pending_users'] ?? 0; ?></h3>
                    <p>Pending Approval</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo count($allPositions); ?></h3>
                    <p>Total Positions</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo count($allCandidates); ?></h3>
                    <p>Total Candidates</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $turnoutStats['turnout_percentage'] ?? 0; ?>%</h3>
                    <p>Voter Turnout</p>
                </div>
            </div>

            <div class="grid-2">
                <div class="card">
                    <h2>Quick Actions</h2>
                    <div class="quick-actions">
                        <a href="?tab=candidates" class="btn-primary">Add Candidate</a>
                        <a href="?tab=voters" class="btn-secondary">Approve Voters</a>
                        <a href="?tab=voting_control" class="btn-secondary">Manage Voting</a>
                        <a href="?tab=results" class="btn-secondary">View Results</a>
                    </div>
                </div>

                <div class="card">
                    <h2>Pending Approvals (<?php echo count($pendingVoters); ?>)</h2>
                    <?php if (empty($pendingVoters)): ?>
                        <p class="text-muted">No pending voter approvals.</p>
                    <?php else: ?>
                        <ul class="pending-list">
                            <?php foreach (array_slice($pendingVoters, 0, 5) as $voter): ?>
                                <li>
                                    <strong><?php echo htmlspecialchars($voter['full_name'] ?? $voter['email']); ?></strong>
                                    <span class="small"><?php echo htmlspecialchars($voter['email']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (count($pendingVoters) > 5): ?>
                            <a href="?tab=voters" class="btn-link">View all pending...</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($activeTab === 'candidates'): ?>
            <!-- CANDIDATE MANAGEMENT TAB -->
            <div class="page-header">
                <h1>Candidate Management</h1>
                <p>Add and manage positions and candidates for the election.</p>
            </div>

            <div class="grid-2">
                <!-- Add Position Form -->
                <div class="card">
                    <h2>Add New Position</h2>
                    <form method="POST" action="/votingsystem/admin?tab=candidates">
                        <input type="hidden" name="action" value="add_position">
                        <div class="form-group">
                            <label>Position Title *</label>
                            <input type="text" name="title" required placeholder="e.g., President">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="3" placeholder="Position description..."></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Max Votes</label>
                                <input type="number" name="max_votes" value="1" min="1">
                            </div>
                            <div class="form-group">
                                <label>Display Order</label>
                                <input type="number" name="display_order" value="0">
                            </div>
                        </div>
                        <button type="submit" class="btn-primary">Add Position</button>
                    </form>
                </div>

                <!-- Add Candidate Form -->
                <div class="card">
                    <h2>Add New Candidate</h2>
                    <form method="POST" action="/votingsystem/admin?tab=candidates">
                        <input type="hidden" name="action" value="add_candidate">
                        <div class="form-group">
                            <label>Position *</label>
                            <select name="position_id" required>
                                <option value="">Select Position</option>
                                <?php foreach ($allPositions as $pos): ?>
                                    <option value="<?php echo $pos['id']; ?>"><?php echo htmlspecialchars($pos['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" required placeholder="Candidate name">
                        </div>
                        <div class="form-group">
                            <label>Party Affiliation</label>
                            <input type="text" name="party_affiliation" placeholder="e.g., Progressive Party">
                        </div>
                        <div class="form-group">
                            <label>Platform</label>
                            <textarea name="platform" rows="3" placeholder="Candidate's platform and promises..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Biography</label>
                            <textarea name="biography" rows="3" placeholder="Candidate's background and experience..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Contact Email</label>
                            <input type="email" name="contact_email" placeholder="candidate@example.com">
                        </div>
                        <button type="submit" class="btn-primary">Add Candidate</button>
                    </form>
                </div>
            </div>

            <!-- Candidates List -->
            <div class="card">
                <h2>All Candidates</h2>
                <?php foreach ($allPositions as $position): ?>
                    <div class="position-section">
                        <h3><?php echo htmlspecialchars($position['title']); ?></h3>
                        <?php if (empty($position['candidates'])): ?>
                            <p class="text-muted">No candidates yet.</p>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Party</th>
                                        <th>Platform</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($position['candidates'] as $candidate): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($candidate['full_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($candidate['party_affiliation'] ?? 'Independent'); ?></td>
                                            <td><?php echo htmlspecialchars(substr($candidate['platform'] ?? '', 0, 50)) . '...'; ?></td>
                                            <td>
                                                <span class="badge <?php echo $candidate['active'] ? 'badge-success' : 'badge-inactive'; ?>">
                                                    <?php echo $candidate['active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="toggle_candidate">
                                                    <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                                    <button type="submit" class="btn-small btn-secondary">Toggle</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($activeTab === 'voters'): ?>
            <!-- VOTER REGISTRATION TAB -->
            <div class="page-header">
                <h1>Voter Registration</h1>
                <p>Manage voter registrations and approvals.</p>
            </div>

            <div class="card">
                <h2>Pending Approvals (<?php echo count($pendingVoters); ?>)</h2>
                <?php if (empty($pendingVoters)): ?>
                    <p class="text-muted">No pending voter approvals.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Student ID</th>
                                <th>Registration Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingVoters as $voter): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($voter['full_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($voter['email']); ?></td>
                                    <td><?php echo htmlspecialchars($voter['student_id'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($voter['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="approve_voter">
                                            <input type="hidden" name="user_id" value="<?php echo $voter['id']; ?>">
                                            <button type="submit" class="btn-success btn-small">Approve</button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="reject_voter">
                                            <input type="hidden" name="user_id" value="<?php echo $voter['id']; ?>">
                                            <button type="submit" class="btn-danger btn-small">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2>All Voters</h2>
                <?php $allVoters = $userModel->getAll('voter'); ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allVoters as $voter): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($voter['full_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($voter['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $voter['status']; ?>">
                                        <?php echo ucfirst($voter['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($voter['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($activeTab === 'voting_control'): ?>
            <!-- VOTING CONTROL TAB -->
            <div class="page-header">
                <h1>Voting Control</h1>
                <p>Create and manage voting periods.</p>
            </div>

            <div class="grid-2">
                <div class="card">
                    <h2>Create Voting Period</h2>
                    <form method="POST" action="/votingsystem/admin?tab=voting_control">
                        <input type="hidden" name="action" value="create_voting_period">
                        <div class="form-group">
                            <label>Title *</label>
                            <input type="text" name="title" required placeholder="e.g., 2024 Student Council Elections">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Start Time *</label>
                            <input type="datetime-local" name="start_time" required>
                        </div>
                        <div class="form-group">
                            <label>End Time *</label>
                            <input type="datetime-local" name="end_time" required>
                        </div>
                        <button type="submit" class="btn-primary">Create Period</button>
                    </form>
                </div>

                <div class="card">
                    <h2>Active Voting Period</h2>
                    <?php 
                    $activePeriod = $votingPeriodModel->getActive();
                    if ($activePeriod): ?>
                        <div class="active-period">
                            <h3><?php echo htmlspecialchars($activePeriod['title']); ?></h3>
                            <p><?php echo htmlspecialchars($activePeriod['description']); ?></p>
                            <p><strong>Start:</strong> <?php echo date('M d, Y g:i A', strtotime($activePeriod['start_time'])); ?></p>
                            <p><strong>End:</strong> <?php echo date('M d, Y g:i A', strtotime($activePeriod['end_time'])); ?></p>
                            <form method="POST" style="margin-top: 15px;">
                                <input type="hidden" name="action" value="update_period_status">
                                <input type="hidden" name="period_id" value="<?php echo $activePeriod['id']; ?>">
                                <input type="hidden" name="status" value="closed">
                                <button type="submit" class="btn-danger">Close Voting</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No active voting period.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <h2>All Voting Periods</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($votingPeriods as $period): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($period['title']); ?></strong></td>
                                <td><?php echo date('M d, Y g:i A', strtotime($period['start_time'])); ?></td>
                                <td><?php echo date('M d, Y g:i A', strtotime($period['end_time'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $period['status']; ?>">
                                        <?php echo ucfirst($period['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($period['status'] === 'upcoming'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_period_status">
                                            <input type="hidden" name="period_id" value="<?php echo $period['id']; ?>">
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" class="btn-success btn-small">Activate</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($period['status'] === 'active'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_period_status">
                                            <input type="hidden" name="period_id" value="<?php echo $period['id']; ?>">
                                            <input type="hidden" name="status" value="closed">
                                            <button type="submit" class="btn-danger btn-small">Close</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($activeTab === 'results'): ?>
            <!-- RESULTS DASHBOARD TAB -->
            <div class="page-header">
                <h1>Election Results</h1>
                <p>View real-time voting results and statistics.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $turnoutStats['total_eligible_voters'] ?? 0; ?></h3>
                    <p>Eligible Voters</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $turnoutStats['voters_participated'] ?? 0; ?></h3>
                    <p>Voters Participated</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $turnoutStats['total_votes_cast'] ?? 0; ?></h3>
                    <p>Total Votes Cast</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $turnoutStats['turnout_percentage'] ?? 0; ?>%</h3>
                    <p>Turnout Percentage</p>
                </div>
            </div>

            <div class="card">
                <h2>Election Results</h2>
                <?php 
                $resultsByPosition = [];
                foreach ($results as $result) {
                    $resultsByPosition[$result['position_title']][] = $result;
                }
                
                foreach ($resultsByPosition as $posTitle => $candidates): ?>
                    <div class="position-results">
                        <h3><?php echo htmlspecialchars($posTitle); ?></h3>
                        <div class="results-list">
                            <?php 
                            $totalVotes = array_sum(array_column($candidates, 'vote_count'));
                            $rank = 1;
                            foreach ($candidates as $candidate): 
                                $percentage = $totalVotes > 0 ? round(($candidate['vote_count'] / $totalVotes) * 100, 1) : 0;
                                $isWinner = $rank === 1;
                            ?>
                                <div class="result-item <?php echo $isWinner ? 'winner' : ''; ?>">
                                    <div class="result-info">
                                        <span class="rank">#<?php echo $rank; ?></span>
                                        <div>
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
            </div>

        <?php endif; ?>
    </main>

    <script>
        // Sidebar Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');

        // Default: sidebar is OPEN (shown)
        // Only collapse if user explicitly closed it before
        const savedState = localStorage.getItem('adminSidebarCollapsed');
        
        // If no saved state or saved as 'false', sidebar is open
        // Only collapse if explicitly saved as 'true'
        if (savedState === 'true') {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            // Hamburger icon when collapsed
        } else {
            // Sidebar is open by default, show X
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
            localStorage.setItem('adminSidebarCollapsed', sidebar.classList.contains('collapsed'));
        });

        // Mobile handling - collapse on small screens
        if (window.innerWidth <= 768) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            sidebarToggle.classList.remove('active');
        }
    </script>
</body>
</html>
