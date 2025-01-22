<?php
// includes/footer.php

// Handle newsletter subscription if enabled
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newsletter_email'])) {
    $email = filter_var($_POST['newsletter_email'], FILTER_SANITIZE_EMAIL);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Here you would typically add the email to your newsletter database
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'Thank you for subscribing to our newsletter!'
        ];
    } else {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'Please enter a valid email address.'
        ];
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
?>

    </main>
    <!-- Main content ends here -->
    
    <footer class="footer">
        <div class="footer-content">
            <!-- Quick Links Section -->
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="/ecommerce/index.php">Home</a></li>
                    <?php if (isset($_SESSION['user_logged_in'])): ?>
                        <li><a href="/ecommerce/pages/cart.php">Shopping Cart</a></li>
                        <li><a href="/ecommerce/pages/chat.php">Chat with Admin</a></li>
                    <?php else: ?>
                        <li><a href="/ecommerce/login.php">Login</a></li>
                        <li><a href="/ecommerce/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Contact Information -->
            <div class="footer-section">
                <h3>Contact Us</h3>
                <ul class="contact-info">
                    <li>
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:contact@estore.com">contact@estore.com</a>
                    </li>
                    <li>
                        <i class="fas fa-phone"></i>
                        <span>+1 (555) 123-4567</span>
                    </li>
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <span>123 E-commerce Street, Digital City</span>
                    </li>
                </ul>
            </div>

            <!-- Newsletter Signup -->
            <div class="footer-section">
                <h3>Newsletter</h3>
                <p>Subscribe for updates and special offers!</p>
                <form class="newsletter-form" method="POST">
                    <input 
                        type="email" 
                        name="newsletter_email" 
                        placeholder="Enter your email"
                        required
                    >
                    <button type="submit">Subscribe</button>
                </form>
            </div>
        </div>

        <!-- Social Media Links -->
        <div class="social-links">
            <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
            <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
            <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
            <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
        </div>

        <!-- Copyright -->
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> E-Store. All rights reserved.</p>
        </div>
    </footer>

    <style>
        .footer {
            background-color: var(--dark-surface);
            color: var(--dark-text);
            padding: 3rem 2rem 1rem;
            margin-top: 4rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .footer-section {
            padding: 1rem;
        }

        .footer-section h3 {
            color: var(--dark-primary);
            margin-bottom: 1rem;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li {
            margin-bottom: 0.5rem;
        }

        .footer-section a {
            color: var(--dark-text);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section a:hover {
            color: var(--dark-primary);
        }

        .contact-info li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .newsletter-form {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .newsletter-form input {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            background-color: var(--dark-bg);
            color: var(--dark-text);
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .social-link {
            color: var(--dark-text);
            font-size: 1.5rem;
            transition: color 0.3s ease;
        }

        .social-link:hover {
            color: var(--dark-primary);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr;
            }

            .newsletter-form {
                flex-direction: column;
            }
        }
    </style>

    <script>
        // Handle mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            const navMenu = document.getElementById('navMenu');
            
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                });
            }

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>