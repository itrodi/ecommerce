<?php
// pages/product.php
require_once '../includes/functions.php';
require_once '../includes/user_auth.php';

// Get product ID
$product_id = $_GET['id'] ?? 0;

// Get product details
$conn = connect_db();
$query = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: ../index.php');
    exit();
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isUserLoggedIn()) {
        $_SESSION['redirect_url'] = "/ecommerce/pages/product.php?id=$product_id";
        header('Location: ../login.php');
        exit();
    }

    $quantity = min((int)$_POST['quantity'], $product['quantity']);
    if ($quantity > 0) {
        addToCart($product_id, $quantity);
        displayAlert('Product added to cart successfully!', 'success');
    }
}

// Get similar products
$similar_products = getSimilarProducts($product_id, $product['category'], 4);

// Get product images and videos
$images = explode(',', $product['images']);
$videos = !empty($product['videos']) ? explode(',', $product['videos']) : [];
?>

<?php require_once '../includes/header.php'; ?>

<div class="product-page">
    <div class="product-container">
        <!-- Product Images Gallery -->
        <div class="product-gallery">
            <div class="main-image">
                <img src="../<?php echo htmlspecialchars($images[0]); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     id="mainImage">
            </div>
            
            <div class="thumbnail-list">
                <?php foreach ($images as $index => $image): ?>
                    <div class="thumbnail" onclick="changeImage(<?php echo $index; ?>)">
                        <img src="../<?php echo htmlspecialchars($image); ?>" 
                             alt="Thumbnail <?php echo $index + 1; ?>">
                    </div>
                <?php endforeach; ?>
                
                <?php foreach ($videos as $video): ?>
                    <div class="thumbnail video">
                        <video src="../<?php echo htmlspecialchars($video); ?>" 
                               onclick="playVideo(this.src)">
                            <i class="fas fa-play"></i>
                        </video>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Product Information -->
        <div class="product-info">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            
            <div class="product-category">
                Category: 
                <a href="category.php?category=<?php echo urlencode($product['category']); ?>">
                    <?php echo htmlspecialchars($product['category']); ?>
                </a>
            </div>

            <div class="product-price">
                $<?php echo number_format($product['price'], 2); ?>
            </div>

            <div class="stock-status <?php echo $product['quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                <?php echo $product['quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                <?php if ($product['quantity'] > 0): ?>
                    (<?php echo $product['quantity']; ?> available)
                <?php endif; ?>
            </div>

            <?php if ($product['quantity'] > 0): ?>
                <form method="POST" class="add-to-cart-form">
                    <div class="quantity-selector">
                        <button type="button" onclick="updateQuantity(-1)">-</button>
                        <input type="number" name="quantity" value="1" min="1" 
                               max="<?php echo $product['quantity']; ?>" id="quantity">
                        <button type="button" onclick="updateQuantity(1)">+</button>
                    </div>

                    <button type="submit" name="add_to_cart" class="add-to-cart-btn">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                </form>
            <?php endif; ?>

            <div class="product-description">
                <h2>Description</h2>
                <div class="description-content">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Similar Products -->
    <?php if ($similar_products->num_rows > 0): ?>
        <div class="similar-products">
            <h2>Similar Products</h2>
            <div class="products-grid">
                <?php while ($similar = $similar_products->fetch_assoc()): ?>
                    <a href="product.php?id=<?php echo $similar['id']; ?>" class="product-card">
                        <div class="product-image">
                            <?php 
                            $sim_images = explode(',', $similar['images']);
                            $first_image = $sim_images[0];
                            ?>
                            <img src="../<?php echo htmlspecialchars($first_image); ?>" 
                                 alt="<?php echo htmlspecialchars($similar['name']); ?>">
                        </div>
                        <div class="product-details">
                            <h3><?php echo htmlspecialchars($similar['name']); ?></h3>
                            <p class="price">$<?php echo number_format($similar['price'], 2); ?></p>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Video Modal -->
<div id="videoModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <video id="modalVideo" controls>
            Your browser does not support the video tag.
        </video>
    </div>
</div>

<style>
.product-page {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.product-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    background-color: var(--dark-surface);
    padding: 2rem;
    border-radius: 8px;
}

.product-gallery {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.main-image {
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
}

.main-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.thumbnail-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 0.5rem;
}

.thumbnail {
    aspect-ratio: 1;
    border-radius: 4px;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    transition: border-color 0.3s ease;
}

.thumbnail:hover {
    border-color: var(--dark-primary);
}

.thumbnail img,
.thumbnail video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.thumbnail.video {
    position: relative;
}

.thumbnail.video::after {
    content: '\f144';
    font-family: 'Font Awesome 5 Free';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 1.5rem;
    text-shadow: 0 0 10px rgba(0,0,0,0.5);
}

.product-info h1 {
    margin: 0 0 1rem 0;
    font-size: 2rem;
    color: var(--dark-primary);
}

.product-category {
    margin-bottom: 1rem;
    color: var(--dark-text-secondary);
}

.product-category a {
    color: var(--dark-primary);
    text-decoration: none;
}

.product-price {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 1rem;
}

.stock-status {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.in-stock {
    background-color: rgba(76, 175, 80, 0.1);
    color: #4caf50;
}

.out-of-stock {
    background-color: rgba(244, 67, 54, 0.1);
    color: #f44336;
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.quantity-selector button {
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 4px;
    background-color: var(--dark-primary);
    color: var(--dark-bg);
    cursor: pointer;
}

.quantity-selector input {
    width: 60px;
    height: 36px;
    text-align: center;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background-color: var(--dark-bg);
    color: var(--dark-text);
    border-radius: 4px;
}

.add-to-cart-btn {
    width: 100%;
    padding: 1rem;
    border: none;
    border-radius: 4px;
    background-color: var(--dark-primary);
    color: var(--dark-bg);
    font-size: 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.product-description {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.product-description h2 {
    margin-bottom: 1rem;
    font-size: 1.25rem;
}

.similar-products {
    margin-top: 3rem;
}

.similar-products h2 {
    margin-bottom: 1.5rem;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1.5rem;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.9);
    z-index: 1000;
}

.modal-content {
    position: relative;
    width: 90%;
    max-width: 800px;
    margin: 2rem auto;
}

.close {
    position: absolute;
    top: -2rem;
    right: 0;
    color: white;
    font-size: 2rem;
    cursor: pointer;
}

#modalVideo {
    width: 100%;
}

@media (max-width: 768px) {
    .product-container {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Image gallery
const images = <?php echo json_encode($images); ?>;
const mainImage = document.getElementById('mainImage');

function changeImage(index) {
    mainImage.src = '../' + images[index];
}

// Video player
function playVideo(src) {
    const modal = document.getElementById('videoModal');
    const video = document.getElementById('modalVideo');
    
    video.src = src;
    modal.style.display = 'block';
    video.play();
}

// Close modal
document.querySelector('.close').onclick = function() {
    const modal = document.getElementById('videoModal');
    const video = document.getElementById('modalVideo');
    
    modal.style.display = 'none';
    video.pause();
}

// Quantity selector
function updateQuantity(change) {
    const input = document.getElementById('quantity');
    const newValue = Math.max(1, Math.min(<?php echo $product['quantity']; ?>, 
                    parseInt(input.value) + change));
    input.value = newValue;
}

// Form validation
document.querySelector('.add-to-cart-form').onsubmit = function(e) {
    const quantity = document.getElementById('quantity').value;
    if (quantity < 1 || quantity > <?php echo $product['quantity']; ?>) {
        e.preventDefault();
        alert('Please enter a valid quantity');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>