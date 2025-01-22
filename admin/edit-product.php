<?php
// admin/edit-product.php

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

// Get product ID
$product_id = $_GET['id'] ?? 0;
if (!$product_id) {
    header('Location: products.php');
    exit();
}

// Get product data
$query = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: products.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    
    if (empty($name) || empty($description) || $price <= 0 || $quantity < 0 || empty($category)) {
        $error = "Please fill in all required fields correctly";
    } else {
        $existing_images = isset($_POST['existing_images']) ? $_POST['existing_images'] : [];
        $existing_videos = isset($_POST['existing_videos']) ? $_POST['existing_videos'] : [];
        
        $image_paths = $existing_images;
        $video_paths = $existing_videos;
        
        // Handle new image uploads
        if (!empty($_FILES['images']['name'][0])) {
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                $file_name = $_FILES['images']['name'][$key];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                if (in_array($file_ext, $allowed_types)) {
                    $new_name = uniqid() . '.' . $file_ext;
                    $upload_path = '../assets/images/products/' . $new_name;
                    
                    if (move_uploaded_file($tmp_name, $upload_path)) {
                        $image_paths[] = 'assets/images/products/' . $new_name;
                    }
                }
            }
        }
        
        // Handle new video uploads
        if (!empty($_FILES['videos']['name'][0])) {
            $allowed_types = ['mp4', 'webm'];
            foreach ($_FILES['videos']['tmp_name'] as $key => $tmp_name) {
                $file_name = $_FILES['videos']['name'][$key];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                if (in_array($file_ext, $allowed_types)) {
                    $new_name = uniqid() . '.' . $file_ext;
                    $upload_path = '../assets/videos/products/' . $new_name;
                    
                    if (move_uploaded_file($tmp_name, $upload_path)) {
                        $video_paths[] = 'assets/videos/products/' . $new_name;
                    }
                }
            }
        }
        
        // Update product in database
        $query = "UPDATE products SET 
                  name = ?, 
                  description = ?, 
                  price = ?, 
                  quantity = ?, 
                  category = ?, 
                  images = ?, 
                  videos = ? 
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        
        $images_str = implode(',', $image_paths);
        $videos_str = implode(',', $video_paths);
        
        $stmt->bind_param("ssdisssi", 
            $name, 
            $description, 
            $price, 
            $quantity, 
            $category, 
            $images_str, 
            $videos_str, 
            $product_id
        );
        
        if ($stmt->execute()) {
            $success = "Product updated successfully!";
            // Refresh product data
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
        } else {
            $error = "Error updating product: " . $conn->error;
        }
    }
}

// Get existing categories
$categories_query = "SELECT DISTINCT category FROM products ORDER BY category";
$categories = $conn->query($categories_query);
$existing_categories = [];
while ($cat = $categories->fetch_assoc()) {
    $existing_categories[] = $cat['category'];
}

// Get current images and videos
$current_images = !empty($product['images']) ? explode(',', $product['images']) : [];
$current_videos = !empty($product['videos']) ? explode(',', $product['videos']) : [];

// Include header after all processing
require_once 'includes/admin-header.php';
?>

<div class="edit-product-page">
    <div class="form-container">
        <h2>Edit Product</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="product-form" id="editProductForm">
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" id="name" name="name" required
                           value="<?php echo htmlspecialchars($product['name']); ?>"
                           placeholder="Enter product name">
                </div>

                <div class="form-group">
                    <label for="category">Category *</label>
                    <input type="text" id="category" name="category" required
                           value="<?php echo htmlspecialchars($product['category']); ?>"
                           list="categories" placeholder="Select or enter category">
                    <datalist id="categories">
                        <?php foreach ($existing_categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="form-group">
                    <label for="price">Price ($) *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required
                           value="<?php echo htmlspecialchars($product['price']); ?>"
                           placeholder="Enter price">
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="number" id="quantity" name="quantity" min="0" required
                           value="<?php echo htmlspecialchars($product['quantity']); ?>"
                           placeholder="Enter quantity">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description *</label>
                <textarea id="description" name="description" rows="5" required
                          placeholder="Enter product description"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>

            <!-- Existing Images -->
            <div class="form-group">
                <label>Current Images</label>
                <div class="media-grid">
                    <?php foreach ($current_images as $image): ?>
                        <div class="media-item">
                            <img src="../<?php echo htmlspecialchars($image); ?>" alt="Product Image">
                            <input type="hidden" name="existing_images[]" value="<?php echo htmlspecialchars($image); ?>">
                            <button type="button" class="media-remove" onclick="removeMedia(this)">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- New Images Upload -->
            <div class="form-group">
                <label>Add New Images</label>
                <div class="file-upload-container" id="imageUpload">
                    <div class="file-upload-box">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Drag & drop images or click to select</p>
                        <small>Supported formats: JPG, PNG, GIF (Max 5MB each)</small>
                    </div>
                    <input type="file" name="images[]" multiple accept="image/*" class="file-input">
                </div>
                <div class="preview-container" id="imagePreview"></div>
            </div>

            <!-- Existing Videos -->
            <div class="form-group">
                <label>Current Videos</label>
                <div class="media-grid">
                    <?php foreach ($current_videos as $video): ?>
                        <div class="media-item">
                            <video src="../<?php echo htmlspecialchars($video); ?>" controls></video>
                            <input type="hidden" name="existing_videos[]" value="<?php echo htmlspecialchars($video); ?>">
                            <button type="button" class="media-remove" onclick="removeMedia(this)">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- New Videos Upload -->
            <div class="form-group">
                <label>Add New Videos</label>
                <div class="file-upload-container" id="videoUpload">
                    <div class="file-upload-box">
                        <i class="fas fa-film"></i>
                        <p>Drag & drop videos or click to select</p>
                        <small>Supported formats: MP4, WebM (Max 50MB each)</small>
                    </div>
                    <input type="file" name="videos[]" multiple accept="video/*" class="file-input">
                </div>
                <div class="preview-container" id="videoPreview"></div>
            </div>

            <div class="form-actions">
                <button type="submit" class="submit-btn">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="products.php" class="cancel-btn">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
/* Include all styles from add-product.php */
.edit-product-page {
    padding: 1rem;
}

.form-container {
    background-color: var(--dark-surface);
    padding: 2rem;
    border-radius: 8px;
    max-width: 1000px;
    margin: 0 auto;
}

.form-container h2 {
    margin-bottom: 2rem;
    color: var(--dark-primary);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--dark-text);
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    background-color: var(--dark-bg);
    color: var(--dark-text);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
    max-width: 100%; /* Ensures grid doesn't overflow */
}

.media-item {
    position: relative;
    border-radius: 4px;
    overflow: hidden;
    width: 150px; /* Fixed width */
    height: 150px; /* Fixed height */
    border: 1px solid rgba(255, 255, 255, 0.1);
    background-color: var(--dark-bg);
}

.media-item img {
    width: 100%;
    height: 100%;
    object-fit: cover; /* Makes image cover the container while maintaining aspect ratio */
    display: block; /* Removes any extra space below the image */
}

/* Optional: Add hover effect */
.media-item:hover {
    transform: scale(1.02);
    transition: transform 0.2s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Media query for responsive sizing */
@media (max-width: 768px) {
    .media-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }
    
    .media-item {
        width: 120px;
        height: 120px;
    }
}

.file-upload-container {
    border: 2px dashed rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    position: relative;
}

.file-upload-box {
    color: var(--dark-text-secondary);
}

.file-upload-box i {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.file-input {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.preview-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.preview-item {
    position: relative;
    border-radius: 4px;
    overflow: hidden;
    aspect-ratio: 1;
}

.preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-item video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-remove {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    cursor: pointer;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.submit-btn,
.cancel-btn {
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    text-decoration: none;
}

.submit-btn {
    background-color: var(--dark-primary);
    color: var(--dark-bg);
    border: none;
}

.cancel-btn {
    background-color: transparent;
    color: var(--dark-text);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.alert {
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1.5rem;
}

.alert-error {
    background-color: rgba(244, 67, 54, 0.1);
    color: #f44336;
}

.alert-success {
    background-color: rgba(76, 175, 80, 0.1);
    color: #4caf50;
}
</style>

<script>
// Include all JavaScript from add-product.php
document.addEventListener('DOMContentLoaded', function() {
    // File upload preview functionality
    function handleFileUpload(input, previewContainer, isVideo) {
        const files = input.files;
        previewContainer.innerHTML = '';
        
        Array.from(files).forEach(file => {
            const reader = new FileReader();
            const preview = document.createElement('div');
            preview.className = 'preview-item';
            
            reader.onload = function(e) {
                if (isVideo) {
                    preview.innerHTML = `
                        <video src="${e.target.result}" controls></video>
                        <button type="button" class="preview-remove" onclick="removePreview(this)">×</button>
                    `;
                } else {
                    preview.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="preview-remove" onclick="removePreview(this)">×</button>
                    `;
                }
            };
            
            reader.readAsDataURL(file);
            previewContainer.appendChild(preview);
        });
    }

    // Set up file upload handlers
    const imageUpload = document.querySelector('#imageUpload input');
    const imagePreview = document.querySelector('#imagePreview');
    const videoUpload = document.querySelector('#videoUpload input');
    const videoPreview = document.querySelector('#videoPreview');

    imageUpload.addEventListener('change', () => handleFileUpload(imageUpload, imagePreview, false));
    videoUpload.addEventListener('change', () => handleFileUpload(videoUpload, videoPreview, true));

    // Drag and drop functionality
    ['imageUpload', 'videoUpload'].forEach(id => {
        const container = document.getElementById(id);
        
        container.addEventListener('dragover', (e) => {
            e.preventDefault();
            container.style.borderColor = 'var(--dark-primary)';
        });

        container.addEventListener('dragleave', () => {
            container.style.borderColor = 'rgba(255, 255, 255, 0.1)';
        });

        container.addEventListener('drop', (e) => {
            e.preventDefault();
            container.style.borderColor = 'rgba(255, 255, 255, 0.1)';
            const input = container.querySelector('input');
            const dt = new DataTransfer();
            
            Array.from(e.dataTransfer.files).forEach(file => {
                if (id === 'imageUpload' && file.type.startsWith('image/')) {
                    dt.items.add(file);
                } else if (id === 'videoUpload' && file.type.startsWith('video/')) {
                    dt.items.add(file);
                }
            });
            
            input.files = dt.files;
            input.dispatchEvent(new Event('change'));
        });
    });
});

// Remove preview item
function removePreview(button) {
    const item = button.parentElement;
    const container = item.parentElement;
    const input = container.previousElementSibling.querySelector('input');
    
    item.remove();
    
    // Update input files
    const dt = new DataTransfer();
    Array.from(input.files).forEach((file, index) => {
        if (index !== Array.from(container.children).indexOf(item)) {
            dt.items.add(file);
        }
    });
    input.files = dt.files;
}

<script>
// Remove existing media item
function removeMedia(button) {
    if (confirm('Are you sure you want to remove this media?')) {
        const item = button.parentElement;
        const container = item.parentElement;
        item.remove();
    }
}

// Form validation
document.getElementById('editProductForm').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value;
    const price = document.getElementById('price').value;
    const quantity = document.getElementById('quantity').value;
    const category = document.getElementById('category').value;
    const description = document.getElementById('description').value;
    
    let errors = [];
    
    // Validate required fields
    if (!name.trim()) errors.push('Product name is required');
    if (!category.trim()) errors.push('Category is required');
    if (!description.trim()) errors.push('Description is required');
    
    // Validate price
    if (price <= 0) {
        errors.push('Price must be greater than 0');
    }
    
    // Validate quantity
    if (quantity < 0) {
        errors.push('Quantity cannot be negative');
    }
    
    // Check if at least one image exists or is being uploaded
    const existingImages = document.querySelectorAll('input[name="existing_images[]"]');
    const newImages = document.querySelector('input[name="images[]"]').files;
    if (existingImages.length === 0 && newImages.length === 0) {
        errors.push('At least one product image is required');
    }
    
    // Display errors if any
    if (errors.length > 0) {
        e.preventDefault();
        const errorList = document.createElement('div');
        errorList.className = 'alert alert-error';
        errorList.innerHTML = errors.map(error => `<div>${error}</div>`).join('');
        
        // Remove existing error messages
        const existingErrors = document.querySelector('.alert-error');
        if (existingErrors) {
            existingErrors.remove();
        }
        
        // Add new error messages at the top of the form
        const form = document.getElementById('editProductForm');
        form.insertBefore(errorList, form.firstChild);
        
        // Scroll to errors
        errorList.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
});

// File size validation
function validateFileSize(input, maxSize) {
    const files = input.files;
    const oversizedFiles = [];
    
    Array.from(files).forEach(file => {
        if (file.size > maxSize) {
            oversizedFiles.push(file.name);
        }
    });
    
    if (oversizedFiles.length > 0) {
        alert(`The following files exceed the maximum size limit:\n${oversizedFiles.join('\n')}`);
        input.value = '';
        return false;
    }
    
    return true;
}

// Add file size validation to uploads
document.querySelector('input[name="images[]"]').addEventListener('change', function() {
    validateFileSize(this, 5 * 1024 * 1024); // 5MB limit for images
});

document.querySelector('input[name="videos[]"]').addEventListener('change', function() {
    validateFileSize(this, 50 * 1024 * 1024); // 50MB limit for videos
});

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Preview file details
function showFileDetails(files, container) {
    const detailsList = document.createElement('div');
    detailsList.className = 'file-details';
    
    Array.from(files).forEach(file => {
        const size = (file.size / (1024 * 1024)).toFixed(2);
        const details = document.createElement('div');
        details.className = 'file-detail-item';
        details.innerHTML = `
            <span>${file.name}</span>
            <small>${size} MB</small>
        `;
        detailsList.appendChild(details);
    });
    
    // Remove existing details
    const existing = container.querySelector('.file-details');
    if (existing) {
        existing.remove();
    }
    
    container.appendChild(detailsList);
}

// Add file detail preview
document.querySelector('input[name="images[]"]').addEventListener('change', function() {
    showFileDetails(this.files, this.closest('.form-group'));
});

document.querySelector('input[name="videos[]"]').addEventListener('change', function() {
    showFileDetails(this.files, this.closest('.form-group'));
});

// Add additional styles dynamically
const style = document.createElement('style');
style.textContent = `
    .file-details {
        margin-top: 1rem;
        background: var(--dark-bg);
        border-radius: 4px;
        padding: 0.5rem;
    }

    .file-detail-item {
        display: flex;
        justify-content: space-between;
        padding: 0.25rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .file-detail-item:last-child {
        border-bottom: none;
    }

    .file-detail-item small {
        color: var(--dark-text-secondary);
    }

    .media-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1rem;
        margin: 1rem 0;
    }

    .media-item {
        position: relative;
        border-radius: 4px;
        overflow: hidden;
        aspect-ratio: 1;
        background: var(--dark-bg);
    }

    .media-item img,
    .media-item video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .loading-spinner {
        color: var(--dark-primary);
        font-size: 2rem;
    }

    .form-container.loading .loading-overlay {
        display: flex;
    }
`;

document.head.appendChild(style);

// Add loading overlay
const loadingOverlay = document.createElement('div');
loadingOverlay.className = 'loading-overlay';
loadingOverlay.innerHTML = `
    <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
        <div>Saving changes...</div>
    </div>
`;
document.querySelector('.form-container').appendChild(loadingOverlay);

// Show loading state during form submission
document.getElementById('editProductForm').addEventListener('submit', function(e) {
    if (!e.defaultPrevented) {
        this.closest('.form-container').classList.add('loading');
    }
});
</script>