<?php
// admin/login.php
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Redirect if already logged in
if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } elseif (!checkLoginAttempts($email)) {
        $error = "Too many login attempts. Please try again later.";
    } else {
        if (adminLogin($email, $password)) {
            logLoginAttempt($email, 1);
            $redirect = $_SESSION['redirect_url'] ?? 'index.php';
            unset($_SESSION['redirect_url']);
            header("Location: $redirect");
            exit();
        } else {
            logLoginAttempt($email, 0);
            $error = "Invalid credentials";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - E-commerce Store</title>
    <link rel="stylesheet" href="../assets/css/darkmode.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--dark-bg);
        }

        .login-box {
            background-color: var(--dark-surface);
            padding: 2rem;
            border-radius: 8px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--dark-primary);
            font-size: 2rem;
        }

        .error-message {
            background-color: rgba(244, 67, 54, 0.1);
            color: #f44336;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark-text);
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            background-color: var(--dark-bg);
            color: var(--dark-text);
        }

        .login-btn {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--dark-primary);
            color: var(--dark-bg);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: opacity 0.3s ease;
        }

        .login-btn:hover {
            opacity: 0.9;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: var(--dark-text-secondary);
            text-decoration: none;
        }

        .back-link:hover {
            color: var(--dark-primary);
        }
    </style>
</head>
<body class="dark-mode">
    <div class="admin-login-container">
        <div class="login-box">
            <div class="login-logo">
                <i class="fas fa-lock"></i>
                <h2>Admin Login</h2>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
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

            <a href="../index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Store
            </a>
        </div>
    </div>

    <script>
        // Auto-hide error messages after 5 seconds
        const errorMessage = document.querySelector('.error-message');
        if (errorMessage) {
            setTimeout(() => {
                errorMessage.style.opacity = '0';
                setTimeout(() => errorMessage.remove(), 300);
            }, 5000);
        }
    </script>
</body>
</html>