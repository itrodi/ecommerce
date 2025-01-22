<?php
// pages/cart.php
require_once '../includes/functions.php';
require_once '../includes/user_auth.php';

// Require login for cart
requireLogin();

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quantity'])) {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        updateCartQuantity($product_id, $quantity);
    } elseif (isset($_POST['remove_item'])) {
        $product_id = (int)$_POST['product_id'];
        removeFromCart($product_id);
    }
    
    // Redirect to prevent form resubmission
    header('Location: cart.php');
    exit();
}

// Get cart items
$cart_items = getCartItems();
$total = getCartTotal();
?>

<?php require_once '../includes/header.php'; ?>

<div class="cart-page">
    <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h2>Your Cart is Empty</h2>
            <p>Looks like you haven't added any products to your cart yet.</p>
            <a href="../index.php" class="continue-shopping">
                <i class="fas fa-arrow-left"></i> Continue Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="cart-container">
            <!-- Cart Items -->
            <div class="cart-items">
                <h2>Shopping Cart</h2>
                
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <div class="item-image">
                            <?php 
                            $images = explode(',', $item['images']);
                            $first_image = $images[0];
                            ?>
                            <img src="../<?php echo htmlspecialchars($first_image); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                        </div>
                        
                        <div class="item-details">
                            <h3>
                                <a href="product.php?id=<?php echo $item['id']; ?>">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </a>
                            </h3>
                            <p class="item-category">
                                Category: <?php echo htmlspecialchars($item['category']); ?>
                            </p>
                            <p class="item-price">
                                $<?php echo number_format($item['price'], 2); ?> each
                            </p>
                        </div>
                        
                        <div class="item-actions">
                            <form method="POST" class="quantity-form">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <div class="quantity-selector">
                                    <button type="button" onclick="updateQuantity(this, -1)">-</button>
                                    <input type="number" name="quantity" value="<?php echo $item['cart_quantity']; ?>" 
                                           min="1" max="<?php echo $item['quantity']; ?>" 
                                           onchange="this.form.submit()">
                                    <button type="button" onclick="updateQuantity(this, 1)">+</button>
                                </div>
                                <input type="hidden" name="update_quantity" value="1">
                            </form>
                            
                            <div class="item-subtotal">
                                $<?php echo number_format($item['price'] * $item['cart_quantity'], 2); ?>
                            </div>
                            
                            <form method="POST" class="remove-form" 
                                  onsubmit="return confirm('Remove this item from cart?');">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="remove_item" class="remove-btn">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Cart Summary -->
            <div class="cart-summary">
                <h2>Order Summary</h2>
                
                <div class="summary-details">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <div class="total-row">
                        <span>Total</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
                
                <a href="checkout.php" class="checkout-btn">
                    <i class="fas fa-lock"></i> Proceed to Checkout
                </a>
                
                <a href="../index.php" class="continue-shopping-link">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.cart-page {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.empty-cart {
    text-align: center;
    padding: 4rem 2rem;
    background-color: var(--dark-surface);
    border-radius: 8px;
}

.empty-cart i {
    font-size: 4rem;
    color: var(--dark-primary);
    margin-bottom: 1rem;
}

.empty-cart h2 {
    margin-bottom: 1rem;
}

.empty-cart p {
    color: var(--dark-text-secondary);
    margin-bottom: 2rem;
}

.cart-container {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 2rem;
}

.cart-items {
    background-color: var(--dark-surface);
    border-radius: 8px;
    padding: 2rem;
}

.cart-items h2 {
    margin-bottom: 2rem;
    color: var(--dark-primary);
}

.cart-item {
    display: grid;
    grid-template-columns: 100px 1fr auto;
    gap: 1.5rem;
    padding: 1.5rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.cart-item:last-child {
    border-bottom: none;
}

.item-image {
    width: 100px;
    height: 100px;
    border-radius: 8px;
    overflow: hidden;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-details h3 {
    margin: 0 0 0.5rem 0;
}

.item-details h3 a {
    color: var(--dark-text);
    text-decoration: none;
}

.item-details h3 a:hover {
    color: var(--dark-primary);
}

.item-category {
    color: var(--dark-text-secondary);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.item-price {
    color: var(--dark-primary);
}

.item-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 1rem;
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quantity-selector button {
    width: 30px;
    height: 30px;
    border: none;
    background-color: var(--dark-primary);
    color: var(--dark-bg);
    border-radius: 4px;
    cursor: pointer;
}

.quantity-selector input {
    width: 50px;
    height: 30px;
    text-align: center;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background-color: var(--dark-bg);
    color: var(--dark-text);
    border-radius: 4px;
}

.item-subtotal {
    font-weight: bold;
    color: var(--dark-primary);
}

.remove-btn {
    background: none;
    border: none;
    color: #f44336;
    cursor: pointer;
    padding: 0.5rem;
}

.remove-btn:hover {
    color: #d32f2f;
}

.cart-summary {
    background-color: var(--dark-surface);
    border-radius: 8px;
    padding: 2rem;
    height: fit-content;
    position: sticky;
    top: 2rem;
}

.cart-summary h2 {
    margin-bottom: 2rem;
}

.summary-details {
    margin-bottom: 2rem;
}

.summary-row,
.total-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
}

.total-row {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    margin-top: 1rem;
    padding-top: 1rem;
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--dark-primary);
}

.checkout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 1rem;
    background-color: var(--dark-primary);
    color: var(--dark-bg);
    text-decoration: none;
    border-radius: 4px;
    margin-bottom: 1rem;
    transition: opacity 0.3s ease;
}

.checkout-btn:hover {
    opacity: 0.9;
}

.continue-shopping,
.continue-shopping-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    text-decoration: none;
    transition: background-color 0.3s ease;
}

.continue-shopping {
    background-color: var(--dark-primary);
    color: var(--dark-bg);
}

.continue-shopping-link {
    width: 100%;
    justify-content: center;
    background-color: transparent;
    color: var(--dark-text);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

@media (max-width: 768px) {
    .cart-container {
        grid-template-columns: 1fr;
    }

    .cart-item {
        grid-template-columns: 80px 1fr;
        gap: 1rem;
    }

    .item-actions {
        grid-column: 1 / -1;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
}
</style>

<script>
// Quantity update function
function updateQuantity(button, change) {
    const input = button.parentElement.querySelector('input');
    const currentValue = parseInt(input.value);
    const maxValue = parseInt(input.max);
    const newValue = Math.max(1, Math.min(maxValue, currentValue + change));
    
    if (newValue !== currentValue) {
        input.value = newValue;
        input.form.submit();
    }
}

// Prevent negative values in quantity input
document.querySelectorAll('.quantity-selector input').forEach(input => {
    input.addEventListener('input', function() {
        const value = parseInt(this.value);
        const max = parseInt(this.max);
        
        if (isNaN(value) || value < 1) {
            this.value = 1;
        } else if (value > max) {
            this.value = max;
        }
    });
});

// Submit form on quantity change
document.querySelectorAll('.quantity-selector input').forEach(input => {
    input.addEventListener('change', function() {
        this.form.submit();
    });
});

// Add loading state
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            if (submitBtn.classList.contains('checkout-btn')) {
                submitBtn.innerHTML = `
                    <i class="fas fa-spinner fa-spin"></i> Processing...
                `;
            }
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>