<?php
// pages/category.php
require_once '../includes/functions.php';
require_once '../includes/user_auth.php';

$category = $_GET['category'] ?? '';
if (empty($category)) {
    header('Location: ../index.php');
    exit();
}

// Get filters
$sort = $_GET['sort'] ?? 'newest';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : null;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : null;
$page = max(1, $_GET['page'] ?? 1);
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query
$where_clauses = ["category = ?"];
$params = [$category];
$types = "s";

if ($min_price !== null) {
    $where_clauses[] = "price >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if ($max_price !== null) {
    $where_clauses[] = "price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// Sort order
$sort_sql = match($sort) {
    'price_low' => "ORDER BY price ASC",
    'price_high' => "ORDER BY price DESC",
    'oldest' => "ORDER BY created_at ASC",
    default => "ORDER BY created_at DESC" // newest
};

$conn = connect_db();

// Get total products count
$count_query = "SELECT COUNT(*) as total FROM products $where_sql";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_products = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_products / $per_page);

// Get price range for this category
$price_query = "SELECT MIN(price) as min_price, MAX(price) as max_price 
                FROM products WHERE category = ?";
$price_stmt = $conn->prepare($price_query);
$price_stmt->bind_param("s", $category);
$price_stmt->execute();
$price_range = $price_stmt->get_result()->fetch_assoc();

// Get products
$query = "SELECT * FROM products $where_sql $sort_sql LIMIT ? OFFSET ?";
$types .= "ii";
array_push($params, $per_page, $offset);

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$products = $stmt->get_result();
?>

<?php require_once '../includes/header.php'; ?>

<div class="category-page">
    <div class="category-header">
        <h1><?php echo htmlspecialchars($category); ?></h1>
        <p><?php echo $total_products; ?> products found</p>
    </div>

    <div class="category-container">
        <!-- Filters Sidebar -->
        <aside class="filters-sidebar">
            <form class="filters-form" method="GET">
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                
                <div class="filter-section">
                    <h3>Sort By</h3>
                    <select name="sort" onchange="this.form.submit()">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>
                            Newest First
                        </option>
                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>
                            Oldest First
                        </option>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>
                            Price: Low to High
                        </option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>
                            Price: High to Low
                        </option>
                    </select>
                </div>

                <div class="filter-section">
                    <h3>Price Range</h3>
                    <div class="price-inputs">
                        <div class="price-input">
                            <label>Min</label>
                            <input type="number" name="min_price" 
                                   value="<?php echo $min_price ?? ''; ?>" 
                                   min="<?php echo floor($price_range['min_price']); ?>" 
                                   max="<?php echo ceil($price_range['max_price']); ?>" 
                                   placeholder="Min" 
                                   step="0.01">
                        </div>
                        <div class="price-input">
                            <label>Max</label>
                            <input type="number" name="max_price" 
                                   value="<?php echo $max_price ?? ''; ?>" 
                                   min="<?php echo floor($price_range['min_price']); ?>" 
                                   max="<?php echo ceil($price_range['max_price']); ?>" 
                                   placeholder="Max" 
                                   step="0.01">
                        </div>
                    </div>
                    <button type="submit" class="apply-filters">Apply Filters</button>
                    <a href="?category=<?php echo urlencode($category); ?>" class="reset-filters">
                        Reset Filters
                    </a>
                </div>
            </form>
        </aside>

        <!-- Products Grid -->
        <div class="products-section">
            <?php if ($products->num_rows === 0): ?>
                <div class="no-products">
                    <i class="fas fa-box-open"></i>
                    <p>No products found in this category.</p>
                    <a href="../index.php" class="back-home">Return to Homepage</a>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="product-card">
                            <div class="product-image">
                                <?php 
                                $images = explode(',', $product['images']);
                                $first_image = $images[0];
                                ?>
                                <img src="../<?php echo htmlspecialchars($first_image); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                            </div>
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
                                <?php if ($product['quantity'] <= 0): ?>
                                    <span class="out-of-stock">Out of Stock</span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?category=<?php echo urlencode($category); ?>&page=<?php echo $page - 1; ?>&sort=<?php echo urlencode($sort); ?>&min_price=<?php echo $min_price ?? ''; ?>&max_price=<?php echo $max_price ?? ''; ?>" 
                               class="page-link">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?category=<?php echo urlencode($category); ?>&page=<?php echo $i; ?>&sort=<?php echo urlencode($sort); ?>&min_price=<?php echo $min_price ?? ''; ?>&max_price=<?php echo $max_price ?? ''; ?>" 
                               class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?category=<?php echo urlencode($category); ?>&page=<?php echo $page + 1; ?>&sort=<?php echo urlencode($sort); ?>&min_price=<?php echo $min_price ?? ''; ?>&max_price=<?php echo $max_price ?? ''; ?>" 
                               class="page-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.category-page {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.category-header {
    margin-bottom: 2rem;
    text-align: center;
}

.category-header h1 {
    color: var(--dark-primary);
    margin-bottom: 0.5rem;
}

.category-header p {
    color: var(--dark-text-secondary);
}

.category-container {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 2rem;
}

.filters-sidebar {
    background-color: var(--dark-surface);
    padding: 1.5rem;
    border-radius: 8px;
    height: fit-content;
}

.filter-section {
    margin-bottom: 1.5rem;
}

.filter-section:last-child {
    margin-bottom: 0;
}

.filter-section h3 {
    margin-bottom: 1rem;
    font-size: 1rem;
    color: var(--dark-text);
}

.filters-form select,
.filters-form input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    background-color: var(--dark-bg);
    color: var(--dark-text);
}

.price-inputs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.price-input label {
    display: block;
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
    color: var(--dark-text-secondary);
}

.apply-filters,
.reset-filters {
    width: 100%;
    padding: 0.5rem;
    border-radius: 4px;
    text-align: center;
    cursor: pointer;
}

.apply-filters {
    background-color: var(--dark-primary);
    color: var(--dark-bg);
    border: none;
    margin-bottom: 0.5rem;
}

.reset-filters {
    display: block;
    background-color: transparent;
    color: var(--dark-text-secondary);
    border: 1px solid rgba(255, 255, 255, 0.1);
    text-decoration: none;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1.5rem;
}

.product-card {
    background-color: var(--dark-surface);
    border-radius: 8px;
    overflow: hidden;
    text-decoration: none;
    transition: transform 0.3s ease;
}

.product-card:hover {
    transform: translateY(-5px);
}

.product-image {
    aspect-ratio: 1;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-info {
    padding: 1rem;
}

.product-info h3 {
    margin: 0 0 0.5rem 0;
    color: var(--dark-text);
    font-size: 1rem;
}

.price {
    color: var(--dark-primary);
    font-weight: bold;
}

.out-of-stock {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background-color: rgba(244, 67, 54, 0.1);
    color: #f44336;
    border-radius: 4px;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.page-link {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    background-color: var(--dark-surface);
    color: var(--dark-text);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.page-link.active {
    background-color: var(--dark-primary);
    color: var(--dark-bg);
}

.no-products {
    text-align: center;
    padding: 3rem;
    background-color: var(--dark-surface);
    border-radius: 8px;
    color: var(--dark-text-secondary);
}

.no-products i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.back-home {
    display: inline-block;
    margin-top: 1rem;
    padding: 0.5rem 1rem;
    background-color: var(--dark-primary);
    color: var(--dark-bg);
    text-decoration: none;
    border-radius: 4px;
}

@media (max-width: 768px) {
    .category-container {
        grid-template-columns: 1fr;
    }

    .filters-sidebar {
        position: sticky;
        top: 1rem;
        z-index: 10;
    }
}
</style>

<script>
// Price range validation
document.querySelector('.filters-form').addEventListener('submit', function(e) {
    const minPrice = parseFloat(this.min_price.value);
    const maxPrice = parseFloat(this.max_price.value);
    
    if (minPrice && maxPrice && minPrice > maxPrice) {
        e.preventDefault();
        alert('Minimum price cannot be greater than maximum price');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>