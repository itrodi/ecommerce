<?php
// admin/orders.php

// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check admin authentication
requireAdmin();

// Get database connection
try {
    $conn = connect_db();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}



// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $drive_link = trim($_POST['drive_link'] ?? '');
    
    $query = "UPDATE orders SET status = ?, drive_link = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $status, $drive_link, $order_id);
    
    if ($stmt->execute()) {
        displayAlert("Order #$order_id status updated successfully", 'success');
    } else {
        displayAlert("Error updating order status", 'error');
    }
}

// Get filters
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query
$where_clauses = [];
$params = [];
$types = "";

if ($status) {
    $where_clauses[] = "o.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($date_from) {
    $where_clauses[] = "DATE(o.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to) {
    $where_clauses[] = "DATE(o.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

if ($search) {
    $where_clauses[] = "(u.name LIKE ? OR u.email LIKE ? OR o.id LIKE ?)";
    $search_param = "%$search%";
    array_push($params, $search_param, $search_param, $search_param);
    $types .= "sss";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get total orders count
$count_query = "SELECT COUNT(*) as total 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                $where_sql";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_orders = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $per_page);

// Get orders with user information
$query = "SELECT o.*, u.name as user_name, u.email as user_email 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          $where_sql 
          ORDER BY o.created_at DESC 
          LIMIT ? OFFSET ?";
$types .= "ii";
array_push($params, $per_page, $offset);

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();

require_once 'includes/admin-header.php';
?>

<div class="orders-page">
    <!-- Filters -->
    <div class="filters-section">
        <form class="filters-form" method="GET">
            <div class="form-group">
                <input type="text" name="search" placeholder="Search orders..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <div class="form-group">
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="accepted" <?php echo $status === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                    <option value="declined" <?php echo $status === 'declined' ? 'selected' : ''; ?>>Declined</option>
                </select>
            </div>

            <div class="form-group">
                <input type="date" name="date_from" placeholder="From Date"
                       value="<?php echo htmlspecialchars($date_from); ?>">
            </div>

            <div class="form-group">
                <input type="date" name="date_to" placeholder="To Date"
                       value="<?php echo htmlspecialchars($date_to); ?>">
            </div>

            <button type="submit" class="filter-btn">
                <i class="fas fa-filter"></i> Filter
            </button>

            <a href="orders.php" class="reset-btn">
                <i class="fas fa-undo"></i> Reset
            </a>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="table-responsive">
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['user_email']); ?></td>
                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                        <td>
                            <button onclick="viewOrder(<?php echo $order['id']; ?>)" class="action-btn view">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="updateStatus(<?php echo $order['id']; ?>)" class="action-btn update">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>

                <?php if ($orders->num_rows === 0): ?>
                    <tr>
                        <td colspan="7" class="no-orders">No orders found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status); ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                   class="page-link">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status); ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                   class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status); ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                   class="page-link">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Order Details Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div id="orderDetails"></div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Update Order Status</h2>
            <form id="updateStatusForm" method="POST">
                <input type="hidden" name="order_id" id="statusOrderId">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" required>
                        <option value="pending">Pending</option>
                        <option value="accepted">Accept</option>
                        <option value="declined">Decline</option>
                    </select>
                </div>
                <div class="form-group" id="driveLinkGroup">
                    <label for="driveLink">Google Drive Link</label>
                    <input type="url" name="drive_link" id="driveLink" 
                           placeholder="Enter Google Drive link for accepted orders">
                    <small>Required when accepting the order</small>
                </div>
                <div class="form-actions">
                    <button type="submit" class="submit-btn">Update Status</button>
                    <button type="button" class="cancel-btn" onclick="closeModal('statusModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.orders-page {
    padding: 1rem;
}

.filters-section {
    background-color: var(--dark-surface);
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.filters-form {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.filters-form .form-group {
    flex: 1;
    min-width: 200px;
}

.filters-form input,
.filters-form select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    background-color: var(--dark-bg);
    color: var(--dark-text);
}

.filter-btn,
.reset-btn {
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-btn {
    background-color: var(--dark-primary);
    color: var(--dark-bg);
}

.reset-btn {
    background-color: transparent;
    color: var(--dark-text);
    border: 1px solid rgba(255, 255, 255, 0.1);
    text-decoration: none;
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
    background-color: var(--dark-surface);
    border-radius: 8px;
    overflow: hidden;
}

.orders-table th,
.orders-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.85rem;
}

.status-pending {
    background-color: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.status-accepted {
    background-color: rgba(76, 175, 80, 0.1);
    color: #4caf50;
}

.status-declined {
    background-color: rgba(244, 67, 54, 0.1);
    color: #f44336;
}

.action-btn {
    background: none;
    border: none;
    padding: 0.5rem;
    cursor: pointer;
    color: var(--dark-text);
}

.action-btn.view {
    color: var(--dark-primary);
}

.action-btn.update {
    color: var(--dark-secondary);
}

.modal {
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
    position: relative;
    background-color: var(--dark-surface);
    margin: 2rem auto;
    padding: 2rem;
    border-radius: 8px;
    max-width: 600px;
    max-height: calc(100vh - 4rem);
    overflow-y: auto;
}

.close-modal {
    position: absolute;
    right: 1rem;
    top: 1rem;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--dark-text);
}

@media (max-width: 768px) {
    .filters-form {
        flex-direction: column;
    }

    .filters-form .form-group {
        width: 100%;
    }

    .orders-table th:nth-child(3),
    .orders-table td:nth-child(3),
    .orders-table th:nth-child(6),
    .orders-table td:nth-child(6) {
        display: none;
    }
}
</style>

<?php require_once 'includes/admin-footer.php'; ?>
<script>
// View order details
async function viewOrder(orderId) {
    try {
        const response = await fetch(`ajax/get_order.php?id=${orderId}`);
        const order = await response.json();
        
        if (!order) {
            alert('Error loading order details');
            return;
        }
        
         // Format order details
         const products = JSON.parse(order.products);
        let productsList = products.map(product => `
            <div class="order-product">
                <div class="product-image">
                    <img src="../${product.images.split(',')[0]}" alt="${product.name}">
                </div>
                <div class="product-info">
                    <h4>${product.name}</h4>
                    <p>Quantity: ${product.cart_quantity}</p>
                    <p>Price: $${(product.price * product.cart_quantity).toFixed(2)}</p>
                </div>
            </div>
        `).join('');

        // Create order details HTML
        const detailsHtml = `
            <h2>Order #${order.id} Details</h2>
            <div class="order-info">
                <div class="info-group">
                    <label>Customer:</label>
                    <span>${order.user_name}</span>
                </div>
                <div class="info-group">
                    <label>Email:</label>
                    <span>${order.user_email}</span>
                </div>
                <div class="info-group">
                    <label>Date:</label>
                    <span>${new Date(order.created_at).toLocaleString()}</span>
                </div>
                <div class="info-group">
                    <label>Status:</label>
                    <span class="status-badge status-${order.status}">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span>
                </div>
                <div class="info-group">
                    <label>Total Amount:</label>
                    <span>$${parseFloat(order.total_amount).toFixed(2)}</span>
                </div>
            </div>

            <div class="order-products">
                <h3>Products</h3>
                ${productsList}
            </div>

            ${order.payment_receipt ? `
                <div class="payment-info">
                    <h3>Payment Receipt</h3>
                    <a href="../${order.payment_receipt}" target="_blank" class="view-receipt-btn">
                        <i class="fas fa-file-invoice"></i> View Receipt
                    </a>
                </div>
            ` : ''}

            ${order.drive_link ? `
                <div class="drive-link">
                    <h3>Product Access</h3>
                    <a href="${order.drive_link}" target="_blank" class="drive-link-btn">
                        <i class="fab fa-google-drive"></i> Access Products
                    </a>
                </div>
            ` : ''}
        `;

        // Display order details in modal
        document.getElementById('orderDetails').innerHTML = detailsHtml;
        openModal('orderModal');
    } catch (error) {
        console.error('Error:', error);
        alert('Error loading order details');
    }
}

// Update order status
function updateStatus(orderId) {
    document.getElementById('statusOrderId').value = orderId;
    openModal('statusModal');
}

// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
};

// Handle status form submission
document.getElementById('updateStatusForm').addEventListener('submit', function(e) {
    const status = document.getElementById('status').value;
    const driveLink = document.getElementById('driveLink').value;

    if (status === 'accepted' && !driveLink) {
        e.preventDefault();
        alert('Please provide a Google Drive link when accepting the order.');
        return;
    }
});

// Toggle drive link field visibility based on status
document.getElementById('status').addEventListener('change', function() {
    const driveLinkGroup = document.getElementById('driveLinkGroup');
    const driveLink = document.getElementById('driveLink');
    
    if (this.value === 'accepted') {
        driveLinkGroup.style.display = 'block';
        driveLink.required = true;
    } else {
        driveLinkGroup.style.display = 'none';
        driveLink.required = false;
    }
});

// Add additional styles dynamically
const style = document.createElement('style');
style.textContent = `
    .order-product {
        display: flex;
        gap: 1rem;
        padding: 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .product-image {
        width: 80px;
        height: 80px;
        border-radius: 4px;
        overflow: hidden;
    }

    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .product-info {
        flex: 1;
    }

    .product-info h4 {
        margin: 0 0 0.5rem 0;
        color: var(--dark-primary);
    }

    .product-info p {
        margin: 0.25rem 0;
        color: var(--dark-text-secondary);
    }

    .info-group {
        display: flex;
        margin-bottom: 0.5rem;
    }

    .info-group label {
        width: 120px;
        color: var(--dark-text-secondary);
    }

    .view-receipt-btn,
    .drive-link-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        text-decoration: none;
        margin-top: 0.5rem;
    }

    .view-receipt-btn {
        background-color: var(--dark-secondary);
        color: var(--dark-bg);
    }

    .drive-link-btn {
        background-color: #4285f4;
        color: white;
    }

    .payment-info,
    .drive-link {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
`;

document.head.appendChild(style);

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});
</script>

<?php
// admin/ajax/get_order.php
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get order ID
$order_id = $_GET['id'] ?? 0;
if (!$order_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order ID']);
    exit();
}

$conn = connect_db();

// Get order with user details
$query = "SELECT o.*, u.name as user_name, u.email as user_email 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE o.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit();
}

// Set content type to JSON
header('Content-Type: application/json');
echo json_encode($order);
?>