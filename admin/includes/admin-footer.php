<?php
// admin/includes/admin-footer.php

// Get system info
$php_version = phpversion();
$mysql_version = $conn->get_server_info();
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
?>

        </main>
    </div>

    <footer class="admin-footer">
        <div class="footer-content">
            <!-- Quick Links -->
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="orders.php">Orders</a></li>
                    <li><a href="messages.php">Messages</a></li>
                </ul>
            </div>

            <!-- System Info -->
            <div class="footer-section">
                <h4>System Information</h4>
                <ul class="system-info">
                    <li>
                        <span>PHP Version:</span>
                        <span><?php echo htmlspecialchars($php_version); ?></span>
                    </li>
                    <li>
                        <span>MySQL Version:</span>
                        <span><?php echo htmlspecialchars($mysql_version); ?></span>
                    </li>
                    <li>
                        <span>Total Products:</span>
                        <span><?php echo $total_products; ?></span>
                    </li>
                    <li>
                        <span>Total Orders:</span>
                        <span><?php echo $total_orders; ?></span>
                    </li>
                </ul>
            </div>

            <!-- Support Links -->
            <div class="footer-section">
                <h4>Support</h4>
                <ul>
                    <li><a href="#" onclick="openDocs()">Documentation</a></li>
                    <li><a href="#" onclick="openHelp()">Help Center</a></li>
                    <li><a href="profile.php">Admin Settings</a></li>
                    <li><a href="../index.php" target="_blank">View Store</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="admin-info">
                Logged in as: <span><?php echo htmlspecialchars($_SESSION['admin_email']); ?></span>
            </div>
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> E-Store Admin Panel. All rights reserved.
            </div>
            <div class="version">
                Version 1.0.0
            </div>
        </div>
    </footer>

<style>
.admin-footer {
    background-color: var(--dark-surface);
    padding: 2rem 1rem 1rem;
    margin-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.footer-section h4 {
    color: var(--dark-primary);
    margin-bottom: 1rem;
    font-size: 1rem;
    font-weight: 500;
}

.footer-section ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-section ul li {
    margin-bottom: 0.5rem;
}

.footer-section ul li a {
    color: var(--dark-text-secondary);
    text-decoration: none;
    transition: color 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.footer-section ul li a:hover {
    color: var(--dark-primary);
}

.system-info li {
    display: flex;
    justify-content: space-between;
    color: var(--dark-text-secondary);
    padding: 0.25rem 0;
}

.footer-bottom {
    max-width: 1200px;
    margin: 0 auto;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    font-size: 0.875rem;
    color: var(--dark-text-secondary);
}

.admin-info span {
    color: var(--dark-primary);
}

.version {
    padding: 0.25rem 0.5rem;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
}

@media (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr;
    }

    .footer-bottom {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
}

/* Modal styles for documentation and help */
.admin-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
}

.modal-content {
    background-color: var(--dark-surface);
    border-radius: 8px;
    max-width: 600px;
    margin: 2rem auto;
    padding: 2rem;
    position: relative;
    max-height: calc(100vh - 4rem);
    overflow-y: auto;
}

.close-modal {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: none;
    border: none;
    color: var(--dark-text);
    cursor: pointer;
    font-size: 1.5rem;
}

.modal-content h2 {
    color: var(--dark-primary);
    margin-bottom: 1rem;
}
</style>

<script>
// Documentation modal
function openDocs() {
    const modal = document.createElement('div');
    modal.className = 'admin-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal(this)">&times;</button>
            <h2>Admin Documentation</h2>
            <div class="docs-content">
                <h3>Getting Started</h3>
                <ul>
                    <li>Dashboard Overview</li>
                    <li>Managing Products</li>
                    <li>Processing Orders</li>
                    <li>Customer Messages</li>
                </ul>

                <h3>Quick Tips</h3>
                <ul>
                    <li>Use the search function to quickly find products and orders</li>
                    <li>Monitor the dashboard for important notifications</li>
                    <li>Regularly check customer messages</li>
                    <li>Keep product information up to date</li>
                </ul>

                <h3>System Requirements</h3>
                <p>This admin panel requires:</p>
                <ul>
                    <li>PHP <?php echo PHP_VERSION ?> or higher</li>
                    <li>MySQL 5.7 or higher</li>
                    <li>Modern web browser</li>
                </ul>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    modal.style.display = 'block';
}

// Help center modal
function openHelp() {
    const modal = document.createElement('div');
    modal.className = 'admin-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal(this)">&times;</button>
            <h2>Help Center</h2>
            <div class="help-content">
                <h3>Common Tasks</h3>
                <ul>
                    <li>Adding new products</li>
                    <li>Processing orders</li>
                    <li>Managing inventory</li>
                    <li>Customer support</li>
                </ul>

                <h3>Need Support?</h3>
                <p>Contact system administrator:</p>
                <ul>
                    <li>Email: support@estore.com</li>
                    <li>Phone: (555) 123-4567</li>
                    <li>Hours: Mon-Fri, 9AM-5PM</li>
                </ul>

                <h3>Troubleshooting</h3>
                <ul>
                    <li>Clear browser cache</li>
                    <li>Check system requirements</li>
                    <li>Verify database connection</li>
                    <li>Check error logs</li>
                </ul>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    modal.style.display = 'block';
}

// Close modal
function closeModal(button) {
    const modal = button.closest('.admin-modal');
    modal.style.display = 'none';
    modal.remove();
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('admin-modal')) {
        event.target.style.display = 'none';
        event.target.remove();
    }
}

// Check session timeout
setInterval(function() {
    fetch('ajax/check_session.php')
        .then(response => response.json())
        .then(data => {
            if (!data.valid) {
                window.location.href = 'login.php';
            }
        });
}, 300000); // Check every 5 minutes
</script>

</body>
</html>