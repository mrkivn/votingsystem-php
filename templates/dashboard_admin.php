<?php
use Src\Auth\Auth;
use Src\Models\Polls;

$auth = new Auth();
if (!$auth->isAuthenticated() || !$auth->isAdmin()) {
    header('Location: /');
    exit;
}

$pollsModel = new Polls();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $candidatesRaw = $_POST['candidates'] ?? '';
    
    $candidates = explode("\n", $candidatesRaw);
    
    if ($title && count($candidates) > 0) {
        $pollsModel->createPoll($title, $description, $candidates);
        $message = "Poll created successfully!";
    } else {
        $message = "Please provide a title and at least one candidate.";
    }
}

$polls = $pollsModel->getAllPolls();

ob_start();
?>

<div class="dashboard-container">
    <h1>Admin Dashboard</h1>
    
    <?php if ($message): ?>
        <div style="background: #e6ffe6; color: green; padding: 10px; margin-bottom: 20px; border: 1px solid green;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
        <!-- Create Poll Section -->
        <div class="card">
            <h2>Create New Poll</h2>
            <form method="POST" action="/admin">
                <div class="form-group">
                    <label for="title">Poll Title</label>
                    <input type="text" id="title" name="title" required placeholder="e.g., Class President Election">
                </div>
                
                <div class="form-group">
                    <label for="description">Description (Optional)</label>
                    <input type="text" id="description" name="description" placeholder="Short description...">
                </div>

                <div class="form-group">
                    <label for="candidates">Candidates (One per line)</label>
                    <textarea id="candidates" name="candidates" rows="5" style="width: 100%; padding: 12px; border: 2px solid var(--border-color); font-family: var(--font-main);" required placeholder="Candidate A&#10;Candidate B&#10;Candidate C"></textarea>
                </div>

                <button type="submit" class="btn-primary">Launch Poll</button>
            </form>
        </div>

        <!-- Active Polls & Results -->
        <div>
            <h2>Active Polls</h2>
            <?php if (empty($polls)): ?>
                <p>No active polls.</p>
            <?php else: ?>
                <?php foreach ($polls as $id => $poll): ?>
                    <?php if (!is_array($poll)) continue; ?>
                    <div class="card">
                        <h3><?php echo htmlspecialchars($poll['title']); ?></h3>
                        <p style="margin-bottom: 15px; opacity: 0.7;"><?php echo htmlspecialchars($poll['description'] ?? ''); ?></p>
                        
                        <div class="results">
                            <?php if (isset($poll['candidates']) && is_array($poll['candidates'])): ?>
                                <?php 
                                    $totalVotes = 0;
                                    foreach ($poll['candidates'] as $c) {
                                        $totalVotes += ($c['votes'] ?? 0);
                                    }
                                ?>
                                <?php foreach ($poll['candidates'] as $cid => $candidate): ?>
                                    <?php 
                                        $votes = $candidate['votes'] ?? 0;
                                        $percent = $totalVotes > 0 ? round(($votes / $totalVotes) * 100) : 0;
                                    ?>
                                    <div style="margin-bottom: 10px;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                            <strong><?php echo htmlspecialchars($candidate['name']); ?></strong>
                                            <span><?php echo $votes; ?> votes (<?php echo $percent; ?>%)</span>
                                        </div>
                                        <div style="background: #eee; height: 10px; width: 100%; border: 1px solid var(--border-color);">
                                            <div style="background: var(--primary-color); height: 100%; width: <?php echo $percent; ?>%;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No candidates defined.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
