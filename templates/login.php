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
            header('Location: ' . BASE_PATH . '/admin');
        } else {
            header('Location: ' . BASE_PATH . '/dashboard');
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

        <form method="POST" action="<?php echo BASE_PATH; ?>/">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" autocomplete="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('password', this)" aria-label="Toggle password visibility">
                        <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="eye-off-icon" style="display:none;" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%;">Enter Voting Booth</button>
        </form>

        <script>
            // Clear form multiple times to beat browser autofill
            function clearForm() {
                document.getElementById('email').value = '';
                document.getElementById('password').value = '';
            }
            // Clear immediately
            document.addEventListener('DOMContentLoaded', clearForm);
            // Clear after short delay (some browsers autofill after DOM ready)
            setTimeout(clearForm, 50);
            setTimeout(clearForm, 100);
            setTimeout(clearForm, 200);

            function togglePassword(inputId, button) {
                const input = document.getElementById(inputId);
                const eyeIcon = button.querySelector('.eye-icon');
                const eyeOffIcon = button.querySelector('.eye-off-icon');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    eyeIcon.style.display = 'none';
                    eyeOffIcon.style.display = 'block';
                } else {
                    input.type = 'password';
                    eyeIcon.style.display = 'block';
                    eyeOffIcon.style.display = 'none';
                }
            }
        </script>
        
        <p style="margin-top: 20px; text-align: center;">
            No ID? <a href="<?php echo BASE_PATH; ?>/register" class="highlight">Register here</a>
        </p>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
