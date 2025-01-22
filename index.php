<?php
// index.php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

$conn = connect_db();

// Fetch categories
$categories_query = "SELECT DISTINCT category FROM products";
$categories_result = $conn->query($categories_query);

// Fetch top purchased products
$top_products_query = "SELECT * FROM products 
                      ORDER BY created_at DESC LIMIT 6";
$top_products = $conn->query($top_products_query);

// Search functionality
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
if ($search) {
    $products_query = "SELECT * FROM products WHERE 
                      name LIKE '%$search%' OR 
                      description LIKE '%$search%' OR 
                      category LIKE '%$search%'";
} else {
    $products_query = "SELECT * FROM products LIMIT 12";
}
$products = $conn->query($products_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-commerce Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/darkmode.css">
</head>
<body class="dark-mode">
    <!-- Search Bar -->
    <div class="search-container">
        <form action="index.php" method="GET">
            <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <!-- Categories -->
    <div class="categories">
        <h2>Categories</h2>
        <div class="category-grid">
            <?php while($category = $categories_result->fetch_assoc()): ?>
                <a href="pages/category.php?category=<?php echo urlencode($category['category']); ?>" class="category-card">
                    <?php echo htmlspecialchars($category['category']); ?>
                </a>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Top Purchased Products -->
    <div class="top-products">
        <h2>Top Purchased Products</h2>
        <div class="product-grid">
            <?php while($product = $top_products->fetch_assoc()): ?>
                <a href="pages/product.php?id=<?php echo $product['id']; ?>" class="product-card">
                    <img src="<?php echo explode(',', $product['images'])[0]; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
                </a>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- General Products -->
    <div class="general-products">
        <h2>All Products</h2>
        <div class="product-grid">
            <?php while($product = $products->fetch_assoc()): ?>
                <a href="pages/product.php?id=<?php echo $product['id']; ?>" class="product-card">
                    <img src="<?php echo explode(',', $product['images'])[0]; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
     
<style>
    /* Root variables for consistent theming */
:root {
    --primary-color: #2563eb;
    --secondary-color: #3b82f6;
    --background: #0f172a;
    --surface: #1e293b;
    --text-primary: #f8fafc;
    --text-secondary: #cbd5e1;
    --border-radius: 12px;
    --transition: all 0.3s ease;
}

/* Base styles */
body.dark-mode {
    background-color: var(--background);
    color: var(--text-primary);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    margin: 0;
    padding: 20px;
    min-height: 100vh;
}

/* Search Container */
.search-container {
    margin: 2rem auto;
    max-width: 600px;
    padding: 0 1rem;
}

.search-container form {
    display: flex;
    gap: 1rem;
}

.search-container input {
    flex: 1;
    padding: 1rem 1.5rem;
    border: 2px solid var(--surface);
    border-radius: var(--border-radius);
    background: var(--surface);
    color: var(--text-primary);
    font-size: 1rem;
    transition: var(--transition);
}

.search-container input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
}

.search-container button {
    padding: 1rem 2rem;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: var(--border-radius);
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
}

.search-container button:hover {
    background: var(--secondary-color);
    transform: translateY(-2px);
}

/* Categories Section */
.categories {
    margin: 4rem 0;
}

.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    padding: 1rem;
}

.category-card {
    background: var(--surface);
    padding: 1.5rem;
    border-radius: var(--border-radius);
    text-align: center;
    text-decoration: none;
    color: var(--text-primary);
    font-weight: 500;
    transition: var(--transition);
    border: 2px solid transparent;
}

.category-card:hover {
    border-color: var(--primary-color);
    transform: translateY(-4px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
}

/* Product Grid */
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 2rem;
    padding: 1rem;
}

.product-card {
    background: var(--surface);
    border-radius: var(--border-radius);
    overflow: hidden;
    text-decoration: none;
    color: var(--text-primary);
    transition: var(--transition);
    display: flex;
    flex-direction: column;
}

.product-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
}

.product-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
}

.product-card h3 {
    margin: 1rem;
    font-size: 1.1rem;
    color: var(--text-primary);
}

.product-card .price {
    margin: 0 1rem 1rem;
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--primary-color);
}

/* Section Headers */
h2 {
    font-size: 1.8rem;
    margin: 2rem 1rem;
    color: var(--text-primary);
    border-left: 4px solid var(--primary-color);
    padding-left: 1rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    body.dark-mode {
        padding: 10px;
    }

    .category-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }

    .product-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }

    h2 {
        font-size: 1.5rem;
    }
}

/* Loading States */
.product-card.loading {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

/* Smooth Scrolling */
html {
    scroll-behavior: smooth;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 12px;
}

::-webkit-scrollbar-track {
    background: var(--surface);
}

::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 6px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--secondary-color);
}
</style>


    <?php require_once 'includes/footer.php'; ?>
</body>
</html>