<?php
use Src\Auth\Auth;

$error = '';
$success = '';

// Password strength validation function
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "at least 8 characters";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "one number";
    }
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = "one special character";
    }
    
    return $errors;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new Auth();
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';

    // Check password strength first
    $passwordErrors = validatePasswordStrength($password);
    
    if (!empty($passwordErrors)) {
        $error = "Password must contain: " . implode(", ", $passwordErrors);
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Default role is voter
        $result = $auth->register($email, $password, $full_name, 'voter');

        if ($result['success']) {
            if ($result['status'] === 'pending') {
                $success = $result['message'];
            } else {
                // Auto-logged in
                header('Location: ' . BASE_PATH . '/dashboard');
                exit;
            }
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

        <form method="POST" action="<?php echo BASE_PATH; ?>/register" autocomplete="off">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" autocomplete="off" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" autocomplete="off" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" autocomplete="new-password" oninput="checkPasswordStrength(this.value)" required>
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
                
                <!-- Password Strength Meter -->
                <div class="password-strength-container" id="password-strength-container" style="margin-top: 10px; display: none;">
                    <div class="strength-meter">
                        <div class="strength-meter-fill" id="strength-meter-fill"></div>
                    </div>
                    <div class="strength-text" id="strength-text"></div>
                    <div class="password-requirements" id="password-requirements"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" oninput="checkPasswordMatch()" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)" aria-label="Toggle password visibility">
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
                <div id="password-match-indicator" style="margin-top: 5px; font-size: 0.85rem;"></div>
            </div>

            <button type="submit" id="submit-btn" class="btn-primary" style="width: 100%;">Get Voter ID</button>
        </form>
        
        <p style="margin-top: 20px; text-align: center;">
            Already registered? <a href="<?php echo BASE_PATH; ?>/" class="highlight">Login here</a>
        </p>
    </div>
</div>

<script>
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

    function checkPasswordStrength(password) {
        const container = document.getElementById('password-strength-container');
        const meterFill = document.getElementById('strength-meter-fill');
        const strengthText = document.getElementById('strength-text');
        const requirementsDiv = document.getElementById('password-requirements');
        
        // Hide everything if password is empty
        if (password.length === 0) {
            container.style.display = 'none';
            checkPasswordMatch();
            return;
        }
        
        // Show the container when user starts typing
        container.style.display = 'block';
        
        // Check individual requirements
        const hasLength = password.length >= 8;
        const hasUpper = /[A-Z]/.test(password);
        const hasLower = /[a-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
        
        // Build list of missing requirements
        let missingRequirements = [];
        if (!hasLength) missingRequirements.push('At least 8 characters');
        if (!hasUpper) missingRequirements.push('One uppercase letter');
        if (!hasLower) missingRequirements.push('One lowercase letter');
        if (!hasNumber) missingRequirements.push('One number');
        if (!hasSpecial) missingRequirements.push('One special character (!@#$%^&*)');
        
        // Display only the first missing requirement
        if (missingRequirements.length > 0) {
            requirementsDiv.innerHTML = `<div class="missing-req">✗ ${missingRequirements[0]}</div>`;
        } else {
            requirementsDiv.innerHTML = '';
        }
        
        // Calculate strength score (0-5)
        let score = 0;
        if (hasLength) score++;
        if (hasUpper) score++;
        if (hasLower) score++;
        if (hasNumber) score++;
        if (hasSpecial) score++;
        
        // Extra points for longer passwords
        if (password.length >= 12) score += 0.5;
        if (password.length >= 16) score += 0.5;
        
        // Update meter
        const percentage = (score / 6) * 100;
        meterFill.style.width = percentage + '%';
        
        // Update color and text based on score
        if (score < 3) {
            meterFill.className = 'strength-meter-fill weak';
            strengthText.textContent = '⚠️ Weak password';
            strengthText.style.color = '#ff4d4d';
        } else if (score < 5) {
            meterFill.className = 'strength-meter-fill medium';
            strengthText.textContent = '🔶 Medium password';
            strengthText.style.color = '#ffa64d';
        } else {
            meterFill.className = 'strength-meter-fill strong';
            strengthText.textContent = '✅ Strong password';
            strengthText.style.color = '#4dff88';
        }
        
        checkPasswordMatch();
    }
    
    function checkPasswordMatch() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const indicator = document.getElementById('password-match-indicator');
        
        if (confirmPassword.length === 0) {
            indicator.textContent = '';
        } else if (password === confirmPassword) {
            indicator.textContent = '✅ Passwords match';
            indicator.style.color = '#4dff88';
        } else {
            indicator.textContent = '❌ Passwords do not match';
            indicator.style.color = '#ff4d4d';
        }
    }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
