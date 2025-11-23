<?php
use Src\Auth\Auth;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new Auth();
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Default role is client
        $result = $auth->register($email, $password, 'client');

        if ($result['success']) {
            $success = "Registration successful! You can now login.";
        } else {
            $error = $result['error'];
        }
    }
}

ob_start();
?>

<div class="auth-container" style="max-width: 400px; margin: 50px auto;">
    <div class="card">
        <h2 style="text-align: center;">Register</h2>
        
        <?php if ($error): ?>
            <div style="background: #ffe6e6; color: red; padding: 10px; margin-bottom: 15px; border: 1px solid red;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="background: #e6ffe6; color: green; padding: 10px; margin-bottom: 15px; border: 1px solid green;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/register">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%;">Get Voter ID</button>
        </form>
        
        <p style="margin-top: 20px; text-align: center;">
            Already registered? <a href="/" class="highlight">Login here</a>
        </p>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
