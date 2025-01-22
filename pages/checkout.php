<?php
// pages/checkout.php
require_once '../includes/functions.php';
require_once '../includes/user_auth.php';

// Require login for checkout
requireLogin();

// Redirect if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

$error = '';
$success = '';

// Handle checkout submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate payment receipt
    if (!isset($_FILES['payment_receipt']) || $_FILES['payment_receipt']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Please upload your payment receipt';
    } else {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $file = $_FILES['payment_receipt'];
        
        if (!in_array($file['type'], $allowed_types)) {
            $error = 'Invalid file type. Please upload an image or PDF';
        } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            $error = 'File size too large. Maximum size is 5MB';
        } else {
            // Process file upload
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $upload_path = '../assets/uploads/receipts/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Create order
                $receipt_path = 'assets/uploads/receipts/' . $filename;
                $order_id = createOrder($receipt_path);
                
                if ($order_id) {
                    $success = 'Order placed successfully! Order ID: #' . $order_id;
                    // Clear cart after successful order
                    unset($_SESSION['cart']);
                } else {
                    $error = 'Failed to create order. Please try again.';
                    // Remove uploaded file if order creation fails
                    unlink($upload_path);
                }
            } else {
                $error = 'Failed to upload payment receipt';
            }
        }
    }
}

// Get cart items
$cart_items = getCartItems();
$total = getCartTotal();
?>

<?php require_once '../includes/header.php'; ?>

<div class="checkout-page">
    <div class="checkout-container">
        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <h2>Thank You!</h2>
                <p><?php echo $success; ?></p>
                <p>We will process your order once the payment is verified.</p>
                <div class="success-actions">
                    <a href="../index.php" class="continue-shopping">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Order Summary -->
            <div class="order-summary">
                <h2>Order Summary</h2>
                
                <div class="summary-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="summary-item">
                            <div class="item-image">
                                <?php 
                                $images = explode(',', $item['images']);
                                $first_image = $images[0];
                                ?>
                                <img src="../<?php echo htmlspecialchars($first_image); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                            </div>
                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="item-price">
                                    $<?php echo number_format($item['price'], 2); ?> x 
                                    <?php echo $item['cart_quantity']; ?>
                                </p>
                            </div>
                            <div class="item-total">
                                $<?php echo number_format($item['price'] * $item['cart_quantity'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-total">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="total-divider"></div>
                    <div class="total-row grand-total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Payment Instructions -->
            <div class="payment-section">
                <h2>Payment Instructions</h2>
                
                <div class="payment-info">
                    <div class="payment-method">
                        <h3><i class="fas fa-university"></i> Bank Transfer</h3>
                        <div class="bank-details">
                            <div class="detail-row">
                                <span>Bank Name:</span>
                                <span>Example Bank</span>
                            </div>
                            <div class="detail-row">
                                <span>Account Name:</span>
                                <span>E-Store Company</span>
                            </div>
                            <div class="detail-row">
                                <span>Account Number:</span>
                                <span>1234567890</span>
                            </div>
                            <div class="detail-row">
                                <span>Reference:</span>
                                <span><?php echo 'ORD-' . time(); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="payment-steps">
                        <h3><i class="fas fa-list-ol"></i> Steps to Complete Payment</h3>
                        <ol>
                            <li>Transfer the exact amount to the bank account provided</li>
                            <li>Use the reference number in your transfer description</li>
                            <li>Take a screenshot or photo of your payment receipt</li>
                            <li>Upload the receipt below to complete your order</li>
                        </ol>
                    </div>
                </div>

                <!-- Receipt Upload -->
                <form method="POST" enctype="multipart/form-data" class="receipt-form">
                    <?php if ($error): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <div class="upload-container" id="uploadContainer">
                        <div class="upload-box">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Drag & drop your payment receipt or click to select</p>
                            <small>Supported formats: JPG, PNG, GIF, PDF (Max 5MB)</small>
                        </div>
                        <input type="file" name="payment_receipt" id="paymentReceipt" 
                               accept=".jpg,.jpeg,.png,.gif,.pdf">
                    </div>
                    
                    <div id="filePreview" class="file-preview"></div>

                    <button type="submit" class="submit-order">
                        <i class="fas fa-check"></i> Complete Order
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.checkout-page {
    max-width: 1000px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.checkout-container {
    background-color: var(--dark-surface);
    border-radius: 8px;
    padding: 2rem;
}

.success-message {
    text-align: center;
    padding: 3rem 0;
}

.success-message i {
    font-size: 4rem;
    color: #4CAF50;
    margin-bottom: 1rem;
}

.success-message h2 {
    color: var(--dark-primary);
    margin-bottom: 1rem;
}

.success-actions {
    margin-top: 2rem;
}

.continue-shopping {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background-color: var(--dark-primary);
    color: var(--dark-bg);
    text-decoration: none;
    border-radius: 4px;
}

.order-summary {
    margin-bottom: 3rem;
}

.summary-items {
    margin: 1.5rem 0;
}

.summary-item {
    display: flex;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.item-image {
    width: 80px;
    height: 80px;
    border-radius: 4px;
    overflow: hidden;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-details {
    flex: 1;
}

.item-details h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
}

.item-price {
    color: var(--dark-text-secondary);
}

.summary-total {
    margin-top: 2rem;
    padding: 1rem;
    background-color: var(--dark-bg);
    border-radius: 4px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
}

.total-divider {
    height: 1px;
    background-color: rgba(255, 255, 255, 0.1);
    margin: 0.5rem 0;
}

.grand-total {
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--dark-primary);
}

.payment-section h2 {
    margin-bottom: 1.5rem;
}

.payment-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.payment-method h3,
.payment-steps h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--dark-primary);
    margin-bottom: 1rem;
}

.bank-details {
    background-color: var(--dark-bg);
    padding: 1rem;
    border-radius: 4px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.detail-row:last-child {
    border-bottom: none;
}

.payment-steps ol {
    padding-left: 1.5rem;
    color: var(--dark-text-secondary);
}

.payment-steps li {
    margin-bottom: 0.5rem;
}

.upload-container {
    border: 2px dashed rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    padding: 2rem;
    text-align: center;
    margin-bottom: 1rem;
    cursor: pointer;
    position: relative;
}

.upload-container.drag-over {
    border-color: var(--dark-primary);
    background-color: rgba(187, 134, 252, 0.1);
}

.upload-box i {
    font-size: 2rem;
    color: var(--dark-primary);
    margin-bottom: 1rem;
}

.upload-box small {
    display: block;
    margin-top: 0.5rem;
    color: var(--dark-text-secondary);
}

input[type="file"] {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.file-preview {
    margin: 1rem 0;
}

.preview-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem;
    background-color: var(--dark-bg);
    border-radius: 4px;
}

.preview-item i {
    font-size: 1.5rem;
    color: var(--dark-primary);
}

.preview-info {
    flex: 1;
}

.preview-name {
    margin-bottom: 0.25rem;
}

.preview-size {
    font-size: 0.875rem;
    color: var(--dark-text-secondary);
}

.submit-order {
    width: 100%;
    padding: 1rem;
    background-color: var(--dark-primary);
    color: var(--dark-bg);
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.error-message {
    background-color: rgba(244, 67, 54, 0.1);
    color: #f44336;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .payment-info {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// File upload handling
const uploadContainer = document.getElementById('uploadContainer');
const fileInput = document.getElementById('paymentReceipt');
const filePreview = document.getElementById('filePreview');

// Drag and drop
uploadContainer.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadContainer.classList.add('drag-over');
});

uploadContainer.addEventListener('dragleave', () => {
    uploadContainer.classList.remove('drag-over');
});

uploadContainer.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadContainer.classList.remove('drag-over');
    fileInput.files = e.dataTransfer.files;
    updatePreview();
});

// File selection
fileInput.addEventListener('change', updatePreview);

function updatePreview() {
    const file = fileInput.files[0];
    if (!file) return;

    const size = (file.size / (1024 * 1024)).toFixed(2);
    const icon = getFileIcon(file.type);
    
    filePreview.innerHTML = `
        <div class="preview-item">
            <i class="${icon}"></i>
            <div class="preview-info">
                <div class="preview-name">${file.name}</div>
                <div class="preview-size">${size} MB</div>
            </div>
            </div>
    `; // Added missing closing backtick here
}

function getFileIcon(fileType) {
    if (fileType.includes('image')) {
        return 'fas fa-image';
    } else if (fileType.includes('pdf')) {
        return 'fas fa-file-pdf';
    }
    return 'fas fa-file';
}

// Form validation
document.querySelector('.receipt-form').addEventListener('submit', function(e) {
    const file = fileInput.files[0];
    
    if (!file) {
        e.preventDefault();
        showError('Please select a payment receipt');
        return;
    }

    // Validate file size
    if (file.size > 5 * 1024 * 1024) { // 5MB
        e.preventDefault();
        showError('File size exceeds 5MB limit');
        return;
    }

    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (!allowedTypes.includes(file.type)) {
        e.preventDefault();
        showError('Invalid file type. Please upload an image or PDF');
        return;
    }

    // Show loading state
    showLoading();
});

// Error handling
function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.innerHTML = `
        <i class="fas fa-exclamation-circle"></i>
        ${message}
    `;

    const existingError = document.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }

    const form = document.querySelector('.receipt-form');
    form.insertBefore(errorDiv, form.firstChild);

    // Scroll to error
    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Loading state
function showLoading() {
    const submitButton = document.querySelector('.submit-order');
    submitButton.disabled = true;
    submitButton.innerHTML = `
        <i class="fas fa-spinner fa-spin"></i> Processing Order...
    `;
}

// Copy bank details
function addCopyButtons() {
    const detailRows = document.querySelectorAll('.detail-row');
    
    detailRows.forEach(row => {
        const value = row.children[1].textContent;
        const copyButton = document.createElement('button');
        copyButton.className = 'copy-btn';
        copyButton.innerHTML = '<i class="fas fa-copy"></i>';
        copyButton.title = 'Copy to clipboard';
        
        copyButton.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(value);
                copyButton.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    copyButton.innerHTML = '<i class="fas fa-copy"></i>';
                }, 2000);
            } catch (err) {
                console.error('Failed to copy:', err);
            }
        });
        
        row.appendChild(copyButton);
    });
}

// Add additional styles for copy buttons
const style = document.createElement('style');
style.textContent = `
    .detail-row {
        position: relative;
        padding-right: 2rem !important;
    }

    .copy-btn {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--dark-text-secondary);
        cursor: pointer;
        padding: 0.25rem;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .detail-row:hover .copy-btn {
        opacity: 1;
    }

    .copy-btn:hover {
        color: var(--dark-primary);
    }

    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }

    .loading-overlay.show {
        opacity: 1;
        pointer-events: auto;
    }

    .loading-spinner {
        background-color: var(--dark-surface);
        padding: 2rem;
        border-radius: 8px;
        text-align: center;
    }

    .loading-spinner i {
        font-size: 2rem;
        color: var(--dark-primary);
        margin-bottom: 1rem;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .fa-spinner {
        animation: spin 1s linear infinite;
    }

    .order-confirmation {
        background-color: var(--dark-surface);
        padding: 2rem;
        border-radius: 8px;
        text-align: center;
        max-width: 400px;
    }

    .confirmation-icon {
        font-size: 3rem;
        color: #4CAF50;
        margin-bottom: 1rem;
    }

    .confirmation-message {
        margin-bottom: 1.5rem;
    }

    .order-number {
        background-color: var(--dark-bg);
        padding: 0.5rem;
        border-radius: 4px;
        margin: 1rem 0;
        font-family: monospace;
        font-size: 1.2rem;
    }
`;

document.head.appendChild(style);

// Initialize copy buttons
document.addEventListener('DOMContentLoaded', function() {
    addCopyButtons();
});

// Add loading overlay
const loadingOverlay = document.createElement('div');
loadingOverlay.className = 'loading-overlay';
loadingOverlay.innerHTML = `
    <div class="loading-spinner">
        <i class="fas fa-spinner"></i>
        <p>Processing your order...</p>
    </div>
`;
document.body.appendChild(loadingOverlay);

// Save order details for confirmation
let orderDetails = {
    total: <?php echo json_encode(number_format($total, 2)); ?>,
    items: <?php echo json_encode(array_map(function($item) {
        return [
            'name' => $item['name'],
            'quantity' => $item['cart_quantity'],
            'price' => $item['price']
        ];
    }, $cart_items)); ?>
};

// Prevent accidental navigation
window.addEventListener('beforeunload', function(e) {
    if (document.querySelector('.receipt-form')) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Handle order success
function showOrderSuccess(orderId) {
    const successDiv = document.createElement('div');
    successDiv.className = 'order-confirmation';
    successDiv.innerHTML = `
        <i class="fas fa-check-circle confirmation-icon"></i>
        <h2>Order Successful!</h2>
        <div class="confirmation-message">
            <p>Thank you for your order. We will process it once the payment is verified.</p>
            <div class="order-number">Order #${orderId}</div>
        </div>
        <a href="../index.php" class="continue-shopping">
            <i class="fas fa-arrow-left"></i> Continue Shopping
        </a>
    `;

    // Replace form with success message
    const checkoutContainer = document.querySelector('.checkout-container');
    checkoutContainer.innerHTML = '';
    checkoutContainer.appendChild(successDiv);

    // Clear cart in session
    localStorage.removeItem('cart');
}

// Mobile responsiveness
function adjustForMobile() {
    if (window.innerWidth <= 768) {
        document.querySelector('.bank-details').classList.add('collapse');
    }
}

window.addEventListener('resize', adjustForMobile);
adjustForMobile();
</script>

<?php require_once '../includes/footer.php'; ?>