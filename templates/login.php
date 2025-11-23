<?php
use Src\Auth\Auth;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new Auth();
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = $auth->login($email, $password);

    if ($result['success']) {
        if ($_SESSION['role'] === 'admin') {
            header('Location: /admin');
        } else {
            header('Location: /dashboard');
        }
        exit;
    } else {
        $error = $result['error'];
    }
}

ob_start();
?>

<div class="auth-container" style="max-width: 400px; margin: 50px auto;">
    <div class="card">
        <h2 style="text-align: center;">Login</h2>
        
        <?php if ($error): ?>
            <div style="background: #ffe6e6; color: red; padding: 10px; margin-bottom: 15px; border: 1px solid red;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%;">Enter Voting Booth</button>
        </form>
        
        <p style="margin-top: 20px; text-align: center;">
            No ID? <a href="/register" class="highlight">Register here</a>
        </p>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
