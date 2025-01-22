<?php
// register.php

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/user_auth.php';

// Redirect if already logged in
if (isUserLoggedIn()) {
    header('Location: index.php');
    exit();
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate form data
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (!isset($_POST['terms'])) {
        $errors[] = "You must agree to the Terms of Service";
    }
    
    // If no errors, try to register
    if (empty($errors)) {
        $result = registerUser($name, $email, $password);
        
        if ($result['success']) {
            // Log the user in automatically
            $login_result = loginUser($email, $password);
            if ($login_result['success']) {
                // Redirect to homepage
                header('Location: index.php');
                exit();
            } else {
                $errors[] = "Registration successful but login failed. Please try logging in.";
            }
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Include header
require_once 'includes/header.php';
?>

<div class="register-container">
    <div class="register-box">
        <h2>Create Account</h2>

        <?php if (!empty($errors)): ?>
            <div class="error-list">
                <?php foreach ($errors as $error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="register-form">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" 
                       value="<?php echo htmlspecialchars($name); ?>" required
                       placeholder="Enter your full name">
                <small class="form-hint">This will be displayed on your profile</small>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($email); ?>" required
                       placeholder="Enter your email address">
                <small class="form-hint">We'll never share your email with anyone</small>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-input">
                    <input type="password" id="password" name="password" required
                           placeholder="Enter your password">
                    <button type="button" class="toggle-password" onclick="togglePassword('password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <small class="form-hint">Minimum 8 characters</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-input">
                    <input type="password" id="confirm_password" name="confirm_password" required
                           placeholder="Confirm your password">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-group terms">
                <label class="checkbox-label">
                    <input type="checkbox" name="terms" required>
                    <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
                </label>
            </div>

            <button type="submit" class="register-btn">Create Account</button>
        </form>

        <div class="form-links">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</div>

<style>
.register-container {
    min-height: calc(100vh - 400px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.register-box {
    background-color: var(--dark-surface);
    padding: 2rem;
    border-radius: 8px;
    width: 100%;
    max-width: 500px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.register-box h2 {
    text-align: center;
    margin-bottom: 2rem;
    color: var(--dark-primary);
    font-size: 1.8rem;
}

.error-list {
    background-color: rgba(244, 67, 54, 0.1);
    border-radius: 4px;
    margin-bottom: 1.5rem;
    padding: 0.5rem;
}

.error-message {
    color: #f44336;
    padding: 0.5rem;
    font-size: 0.9rem;
}

.register-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    color: var(--dark-text);
    font-weight: 500;
}

.form-group input:not([type="checkbox"]) {
    padding: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    background-color: var(--dark-bg);
    color: var(--dark-text);
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-group input:focus {
    border-color: var(--dark-primary);
    outline: none;
}

.form-hint {
    font-size: 0.8rem;
    color: var(--dark-text-secondary);
}

.password-input {
    position: relative;
    display: flex;
    align-items: center;
}

.toggle-password {
    position: absolute;
    right: 0.75rem;
    background: none;
    border: none;
    color: var(--dark-text-secondary);
    cursor: pointer;
    padding: 0;
}

.toggle-password:hover {
    color: var(--dark-primary);
}

.terms {
    margin-top: 1rem;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    margin-top: 0.25rem;
}

.checkbox-label span {
    font-size: 0.9rem;
    color: var(--dark-text-secondary);
}

.checkbox-label a {
    color: var(--dark-primary);
    text-decoration: none;
}

.checkbox-label a:hover {
    text-decoration: underline;
}

.register-btn {
    background-color: var(--dark-primary);
    color: var(--dark-bg);
    padding: 0.875rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 500;
    transition: opacity 0.3s ease;
    margin-top: 1rem;
}

.register-btn:hover {
    opacity: 0.9;
}

.form-links {
    text-align: center;
    margin-top: 1.5rem;
    color: var(--dark-text-secondary);
    font-size: 0.9rem;
}

.form-links a {
    color: var(--dark-primary);
    text-decoration: none;
}

.form-links a:hover {
    text-decoration: underline;
}

@media (max-width: 576px) {
    .register-box {
        padding: 1.5rem;
    }

    .register-form {
        gap: 1rem;
    }
}
</style>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Client-side validation
document.querySelector('.register-form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;

    let errors = [];

    if (password.length < 8) {
        errors.push('Password must be at least 8 characters long');
    }

    if (password !== confirmPassword) {
        errors.push('Passwords do not match');
    }

    if (name.trim().length < 2) {
        errors.push('Please enter a valid name');
    }

    if (!email.includes('@') || !email.includes('.')) {
        errors.push('Please enter a valid email address');
    }

    if (errors.length > 0) {
        e.preventDefault();
        const errorList = document.querySelector('.error-list') || document.createElement('div');
        errorList.className = 'error-list';
        errorList.innerHTML = errors.map(error => `<div class="error-message">${error}</div>`).join('');
        
        const form = document.querySelector('.register-form');
        form.insertBefore(errorList, form.firstChild);

        // Scroll to errors
        errorList.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>