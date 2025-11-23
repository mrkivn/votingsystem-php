<?php
use Src\Auth\Auth;
use Src\Models\Polls;

$auth = new Auth();
if (!$auth->isAuthenticated()) {
    header('Location: /');
    exit;
}

$pollsModel = new Polls();
$userId = $_SESSION['user']['uid'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pollId = $_POST['poll_id'] ?? '';
    $candidateId = $_POST['candidate_id'] ?? '';
    
    if ($pollId && $candidateId) {
        $result = $pollsModel->vote($pollId, $candidateId, $userId);
        if ($result['success']) {
            $message = "Vote cast successfully!";
        } else {
            $error = $result['error'];
        }
    }
}

$polls = $pollsModel->getAllPolls();

ob_start();
?>

<div class="dashboard-container">
    <h1>Voter Dashboard</h1>
    <p style="margin-bottom: 30px;">Welcome, <?php echo htmlspecialchars($_SESSION['user']['email']); ?></p>

    <?php if ($message): ?>
        <div style="background: #e6ffe6; color: green; padding: 10px; margin-bottom: 20px; border: 1px solid green;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div style="background: #ffe6e6; color: red; padding: 10px; margin-bottom: 20px; border: 1px solid red;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="polls-list">
        <?php if (empty($polls)): ?>
            <p>No active polls at the moment.</p>
        <?php else: ?>
            <?php foreach ($polls as $pollId => $poll): ?>
                <?php if (!is_array($poll)) continue; ?>
                <?php $hasVoted = $pollsModel->hasVoted($pollId, $userId); ?>
                <div class="card" style="<?php echo $hasVoted ? 'opacity: 0.8; background: #f9f9f9;' : ''; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <h3><?php echo htmlspecialchars($poll['title']); ?></h3>
                            <p><?php echo htmlspecialchars($poll['description'] ?? ''); ?></p>
                        </div>
                        <?php if ($hasVoted): ?>
                            <span style="background: var(--secondary-color); color: #fff; padding: 4px 8px; font-size: 0.8rem;">VOTED</span>
                        <?php else: ?>
                            <span style="background: var(--primary-color); color: #fff; padding: 4px 8px; font-size: 0.8rem;">OPEN</span>
                        <?php endif; ?>
                    </div>

                    <hr style="margin: 20px 0; border: 0; border-top: 1px solid var(--border-color);">

                    <?php if (!$hasVoted): ?>
                        <form method="POST" action="/dashboard">
                            <input type="hidden" name="poll_id" value="<?php echo $pollId; ?>">
                            <div style="display: grid; gap: 10px;">
                                <?php if (isset($poll['candidates']) && is_array($poll['candidates'])): ?>
                                    <?php foreach ($poll['candidates'] as $cid => $candidate): ?>
                                        <label class="radio-option" style="display: block; padding: 10px; border: 1px solid var(--border-color); cursor: pointer;">
                                            <input type="radio" name="candidate_id" value="<?php echo $cid; ?>" required>
                                            <span style="margin-left: 10px; font-weight: 500;"><?php echo htmlspecialchars($candidate['name']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                    <button type="submit" class="btn-primary" style="margin-top: 15px;">Cast Vote</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    <?php else: ?>
                        <p><em>Thank you for voting! Results will be announced by the admin.</em></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
