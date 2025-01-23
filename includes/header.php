<?php
// includes/header.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the current page for active navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Get cart count if user is logged in
$cart_count = 0;
if (isset($_SESSION['user_logged_in']) && isset($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']);
}

// Get any alert messages
$alert = isset($_SESSION['alert']) ? $_SESSION['alert'] : null;
unset($_SESSION['alert']); // Clear the alert after getting it
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-commerce Store</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/ecommerce/assets/css/darkmode.css">
    <link rel="stylesheet" href="/ecommerce/assets/css/style.css">
    <style>
        /* Header-specific styles */
        .header {
            background-color: var(--dark-surface);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            color: var(--dark-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-menu {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: var(--dark-text);
            padding: 0.5rem;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-link:hover {
            color: var(--dark-primary);
        }

        .nav-link.active {
            color: var(--dark-primary);
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--dark-primary);
        }

        .cart-icon {
            position: relative;
            padding: 0.5rem;
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--dark-primary);
            color: var(--dark-bg);
            border-radius: 50%;
            padding: 0.2rem 0.5rem;
            font-size: 0.8rem;
        }

        .user-menu {
            position: relative;
            display: inline-block;
        }

        .user-menu-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background-color: var(--dark-surface);
            min-width: 160px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            border-radius: 4px;
            padding: 0.5rem 0;
        }

        .user-menu:hover .user-menu-content {
            display: block;
        }

        .user-menu-link {
            display: block;
            padding: 0.5rem 1rem;
            color: var(--dark-text);
            text-decoration: none;
        }

        .user-menu-link:hover {
            background-color: var(--dark-primary);
            color: var(--dark-bg);
        }

        .alert {
            padding: 1rem;
            margin: 1rem 2rem;
            border-radius: 4px;
            text-align: center;
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .alert-error {
            background-color: rgba(244, 67, 54, 0.1);
            color: #F44336;
        }

        @media (max-width: 768px) {
            .nav-menu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background-color: var(--dark-surface);
                flex-direction: column;
                padding: 1rem;
            }

            .nav-menu.active {
                display: flex;
            }

            .mobile-menu-btn {
                display: block;
            }
        }
    </style>
</head>
<body class="dark-mode">
    <header class="header">
        <div class="header-content">
            <a href="/ecommerce/index.php" class="logo">
                <i class="fas fa-store"></i> E-Store
            </a>

            <nav class="nav-menu" id="navMenu">
                <a href="/ecommerce/index.php" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                    Home
                </a>
                
                <?php if (isset($_SESSION['user_logged_in'])): ?>
                    <a href="/ecommerce/pages/cart.php" class="nav-link cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-count"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <div class="user-menu">
                        <a href="#" class="nav-link">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <div class="user-menu-content">
                            <a href="/ecommerce/pages/chat.php" class="user-menu-link">
                                <i class="fas fa-comments"></i> Chat with Admin
                            </a>
                            <a href="logout.php" class="user-menu-link">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/ecommerce/login.php" class="nav-link">Login</a>
                    <a href="/ecommerce/register.php" class="nav-link">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <?php if ($alert): ?>
        <div class="alert alert-<?php echo $alert['type']; ?>">
            <?php echo htmlspecialchars($alert['message']); ?>
        </div>
    <?php endif; ?>

    <!-- Main content starts here -->
    <main class="main-content">