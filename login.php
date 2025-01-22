<?php
// login.php
require_once 'includes/functions.php';
require_once 'includes/user_auth.php';

// Redirect if already logged in
if (isUserLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        $result = loginUser($email, $password);
        if ($result['success']) {
            $redirect = $_SESSION['redirect_url'] ?? 'index.php';
            unset($_SESSION['redirect_url']);
            header("Location: $redirect");
            exit();
        } else {
            $error = $result['message'];
        }
    }
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="login-container">
    <div class="login-box">
        <h2>Login to Your Account</h2>

        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="login-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="login-btn">Login</button>
        </form>

        <div class="form-links">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</div>

<style>
.login-container {
    min-height: calc(100vh - 400px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.login-box {
    background-color: var(--dark-surface);
    padding: 2rem;
    border-radius: 8px;
    width: 100%;
    max-width: 400px;
}

.login-box h2 {
    text-align: center;
    margin-bottom: 2rem;
    color: var(--dark-primary);
}

.error-message {
    background-color: rgba(244, 67, 54, 0.1);
    color: #f44336;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    color: var(--dark-text);
}

.form-group input {
    padding: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    background-color: var(--dark-bg);
    color: var(--dark-text);
}

.login-btn {
    background-color: var(--dark-primary);
    color: var(--dark-bg);
    padding: 0.75rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    transition: opacity 0.3s ease;
}

.login-btn:hover {
    opacity: 0.9;
}

.form-links {
    text-align: center;
    margin-top: 1.5rem;
}

.form-links a {
    color: var(--dark-primary);
    text-decoration: none;
}

.form-links a:hover {
    text-decoration: underline;
}
</style>

<?php require_once 'includes/footer.php'; ?>