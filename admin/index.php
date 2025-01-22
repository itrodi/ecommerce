<?php
// admin/index.php

// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check admin authentication
requireAdmin();

// Get statistics
$conn = connect_db();

// Get total products
$products_query = "SELECT COUNT(*) as total FROM products";
$products_result = $conn->query($products_query);
$total_products = $products_result->fetch_assoc()['total'];

// Get total orders
$orders_query = "SELECT COUNT(*) as total FROM orders";
$orders_result = $conn->query($orders_query);
$total_orders = $orders_result->fetch_assoc()['total'];

// Get total users
$users_query = "SELECT COUNT(*) as total FROM users";
$users_result = $conn->query($users_query);
$total_users = $users_result->fetch_assoc()['total'];

// Include header
require_once 'includes/admin-header.php';
?>

<div class="dashboard">
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-info">
                <h3>Total Products</h3>
                <p><?php echo $total_products; ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-info">
                <h3>Total Orders</h3>
                <p><?php echo $total_orders; ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3>Total Users</h3>
                <p><?php echo $total_users; ?></p>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard {
    padding: 1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background-color: var(--dark-surface);
    padding: 1.5rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    font-size: 2rem;
    color: var(--dark-primary);
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(187, 134, 252, 0.1);
    border-radius: 50%;
}

.stat-info h3 {
    margin: 0;
    font-size: 0.9rem;
    color: var(--dark-text-secondary);
}

.stat-info p {
    margin: 0;
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--dark-text);
}
</style>

<?php require_once 'includes/admin-footer.php'; ?>