<?php
// admin/products.php

// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check admin authentication
requireAdmin();

// Get database connection
$conn = connect_db();

// Handle actions
$action = $_GET['action'] ?? '';
$message = '';

// Handle delete action
if ($action === 'delete' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $query = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        $message = "Product deleted successfully";
    } else {
        $message = "Error deleting product";
    }
}

// Get filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query
$where_clauses = [];
$params = [];
$types = "";

if ($search) {
    $where_clauses[] = "(name LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($category) {
    $where_clauses[] = "category = ?";
    $params[] = $category;
    $types .= "s";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get total products count
$count_query = "SELECT COUNT(*) as total FROM products $where_sql";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_products = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_products / $per_page);

// Get products
$query = "SELECT * FROM products $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?";
$types .= "ii";
array_push($params, $per_page, $offset);

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();

// Get categories for filter
$categories_query = "SELECT DISTINCT category FROM products ORDER BY category";
$categories = $conn->query($categories_query);

// Include header
require_once 'includes/admin-header.php';
?>

<div class="products-page">

   <!-- Filters Section -->
<div class="filters-section">
    <form class="search-form" method="GET">
        <div class="form-group search-input">
            <input type="text" name="search" placeholder="Search products..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i>
            </button>
        </div>

<div class="form-group category-select">
    <select name="category" onchange="this.form.submit()">
        <option value="">All Categories</option>
        <?php 
        // Improved category query to get all distinct categories
        $categories_query = "SELECT DISTINCT category 
                           FROM products 
                           WHERE category IS NOT NULL 
                           AND TRIM(category) != ''
                           ORDER BY category";
        
        $categories_result = $conn->query($categories_query);
        if ($categories_result) {
            while ($cat = $categories_result->fetch_assoc()) {
                $cat_value = htmlspecialchars($cat['category']);
                $selected = ($category === $cat['category']) ? 'selected' : '';
                echo "<option value='{$cat_value}' {$selected}>{$cat_value}</option>";
            }
        }
        ?>
    </select>
</div>

        <a href="add-product.php" class="add-product-btn">
            <i class="fas fa-plus"></i> Add Product
        </a>
    </form>
</div>

    <?php if ($message): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Products Table -->
    <div class="table-responsive">
        <table class="products-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($product = $products->fetch_assoc()): ?>
                    <tr>
                        <td class="product-image">
                            <?php 
                            $images = explode(',', $product['images']);
                            $first_image = !empty($images[0]) ? $images[0] : '../assets/images/placeholder.jpg';
                            ?>
                            <img src="../<?php echo htmlspecialchars($first_image); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                        <td>
                            <span class="stock-badge <?php echo $product['quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                                <?php echo $product['quantity']; ?>
                            </span>
                        </td>
                        <td class="actions">
                            <a href="edit-product.php?id=<?php echo $product['id']; ?>" 
                               class="action-btn edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="deleteProduct(<?php echo $product['id']; ?>)" 
                                    class="action-btn delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>

                <?php if ($products->num_rows === 0): ?>
                    <tr>
                        <td colspan="6" class="no-products">No products found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>" 
                   class="page-link">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>" 
                   class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>" 
                   class="page-link">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
/* Page Layout */
.products-page {
    padding: 1.5rem;
}

/* Filters Section */
.filters-section {
    background-color: var(--dark-surface);
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.search-form {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.search-input {
    flex: 1;
    min-width: 200px;
    position: relative;
}

.search-input input {
    width: 100%;
    padding: 0.75rem;
    padding-right: 2.5rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    background-color: var(--dark-bg);
    color: var(--dark-text);
}

.search-input button {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--dark-text-secondary);
    cursor: pointer;
}

.search-form select {
    padding: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    background-color: var(--dark-bg);
    color: var(--dark-text);
    min-width: 150px;
}

.add-product-btn {
    background-color: var(--dark-primary);
    color: var(--dark-bg);
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: opacity 0.3s ease;
}

.add-product-btn:hover {
    opacity: 0.9;
}

/* Table Styles */
.table-responsive {
    background-color: var(--dark-surface);
    border-radius: 8px;
    overflow: auto;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.products-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

.products-table th,
.products-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.products-table th {
    background-color: rgba(255, 255, 255, 0.05);
    font-weight: 500;
    color: var(--dark-text-secondary);
}

.products-table tr:hover {
    background-color: rgba(255, 255, 255, 0.02);
}

/* Product Image */
.product-image {
    width: 80px;
}

.product-image img {
    width: 60px;
    height: 60px;
    border-radius: 4px;
    object-fit: cover;
}

/* Stock Badge */
.stock-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.875rem;
}

.in-stock {
    background-color: rgba(76, 175, 80, 0.1);
    color: #4CAF50;
}

.out-of-stock {
    background-color: rgba(244, 67, 54, 0.1);
    color: #f44336;
}

/* Action Buttons */
.actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-start;
}

.action-btn {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.action-btn.edit {
    background-color: rgba(187, 134, 252, 0.1);
    color: var(--dark-primary);
}

.action-btn.delete {
    background-color: rgba(244, 67, 54, 0.1);
    color: #f44336;
}

.action-btn:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

/* Alerts */
.alert {
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-success {
    background-color: rgba(76, 175, 80, 0.1);
    color: #4CAF50;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.page-link {
    padding: 0.5rem 1rem;
    background-color: var(--dark-surface);
    color: var(--dark-text);
    text-decoration: none;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: background-color 0.3s ease;
}

.page-link.active {
    background-color: var(--dark-primary);
    color: var(--dark-bg);
}

.page-link:hover:not(.active) {
    background-color: rgba(255, 255, 255, 0.05);
}

.no-products {
    text-align: center;
    color: var(--dark-text-secondary);
    padding: 3rem !important;
}

/* Responsive Design */
@media (max-width: 768px) {
    .search-form {
        flex-direction: column;
        align-items: stretch;
    }

    .search-input {
        width:60%;
    }

    .add-product-btn {
        text-align: center;
        justify-content: center;
    }

    .table-responsive {
        margin: 0 -1.5rem;
        border-radius: 0;
    }

    .products-table {
        font-size: 0.9rem;
    }

    .products-table th:nth-child(3),
    .products-table td:nth-child(3) {
        display: none;
    }

    .product-image img {
        width: 40px;
        height: 40px;
    }
}
</style>

<script>
function deleteProduct(productId) {
    if (confirm('Are you sure you want to delete this product?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'products.php?action=delete';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'product_id';
        input.value = productId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once 'includes/admin-footer.php'; ?>