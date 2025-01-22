<?php
// admin/add-product.php

// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check admin authentication
requireAdmin();

// Get database connection
$conn = connect_db();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and validate input
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $category = trim($_POST['category'] ?? ''); // Get category name
    
    // Basic validation
   
    if (empty($name) || empty($description) || $price <= 0 || $quantity < 0 || empty($category)) {
        $error = "Please fill in all required fields correctly";
    } else {
        // Create products directory if it doesn't exist
        $upload_dir = '../assets/images/products/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $image_paths = [];
        $video_paths = [];
        
        // Handle image uploads
        if (!empty($_FILES['images']['name'][0])) {
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                $file_name = $_FILES['images']['name'][$key];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                if (in_array($file_ext, $allowed_types)) {
                    $new_name = uniqid() . '.' . $file_ext;
                    $upload_path = $upload_dir . $new_name;
                    
                    if (move_uploaded_file($tmp_name, $upload_path)) {
                        $image_paths[] = 'assets/images/products/' . $new_name;
                    }
                }
            }
        }

        // Handle video uploads
if (!empty($_FILES['videos']['name'][0])) {
    $videos_dir = '../assets/videos/products/';
    if (!file_exists($videos_dir)) {
        mkdir($videos_dir, 0777, true);
    }
    
    $allowed_types = ['mp4', 'webm'];
    foreach ($_FILES['videos']['tmp_name'] as $key => $tmp_name) {
        $file_name = $_FILES['videos']['name'][$key];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_types)) {
            $new_name = uniqid() . '.' . $file_ext;
            $upload_path = $videos_dir . $new_name;
            
            if (move_uploaded_file($tmp_name, $upload_path)) {
                $video_paths[] = 'assets/videos/products/' . $new_name;
            }
        }
    }
}
        
$query = "INSERT INTO products (name, description, price, quantity, category, images, videos) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        
        $images_str = implode(',', $image_paths);
        $videos_str = implode(',', $video_paths);
        
        $stmt->bind_param("ssdssss", 
            $name, 
            $description, 
            $price, 
            $quantity, 
            $category,  // This will now be the category name directly
            $images_str,
            $videos_str
        );
        
        if ($stmt->execute()) {
            $success = "Product added successfully!";
            // Clear form data
            $name = $description = $category = '';
            $price = $quantity = 0;
        } else {
            $error = "Error adding product: " . $conn->error;
        }
    }
}

// Get existing categories
$categories_query = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category";
$categories = $conn->query($categories_query);
$existing_categories = [];
while ($cat = $categories->fetch_assoc()) {
    $existing_categories[] = $cat['category'];
}

// Include header
require_once 'includes/admin-header.php';
?>

<div class="add-product-page">
    <div class="form-container">
        <h2>Add New Product</h2>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="product-form" id="addProductForm">
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" id="name" name="name" required
                           value="<?php echo htmlspecialchars($name ?? ''); ?>"
                           placeholder="Enter product name">
                </div>

<div class="form-group">
    <label for="category">Category Name *</label>
    <input type="text" 
           id="category" 
           name="category" 
           required
           value="<?php echo htmlspecialchars($category ?? ''); ?>"
           placeholder="Enter category name (e.g., Phones, Electronics, etc.)"
           class="form-input">
</div>

                <div class="form-group">
                    <label for="price">Price ($) *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required
                           value="<?php echo htmlspecialchars($price ?? ''); ?>"
                           placeholder="Enter price">
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="number" id="quantity" name="quantity" min="0" required
                           value="<?php echo htmlspecialchars($quantity ?? ''); ?>"
                           placeholder="Enter quantity">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description *</label>
                <textarea id="description" name="description" rows="5" required
                          placeholder="Enter product description"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>Product Images *</label>
                <div class="file-upload-container" id="imageUpload">
                    <div class="file-upload-box">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Drag & drop images or click to select</p>
                        <small>Supported formats: JPG, PNG, GIF (Max 5MB each)</small>
                    </div>
                    <input type="file" name="images[]" multiple accept="image/*" class="file-input">
                </div>
                <div id="imagePreview" class="preview-container"></div>
            </div>

          <div class="form-group">
               <label>Product Videos</label>
              <div class="file-upload-container" id="videoUpload">
                <div class="file-upload-box">
               <i class="fas fa-film"></i>
              <p>Drag & drop videos or click to select</p>
              <small>Supported formats: MP4, WebM (Max 50MB each)</small>
                 </div>
                <input type="file" name="videos[]" multiple accept="video/*" class="file-input">
                </div>
              <div id="videoPreview" class="preview-container"></div>
         </div>

            <div class="form-actions">
                <button type="submit" class="submit-btn">
                    <i class="fas fa-plus"></i> Add Product
                </button>
                <a href="products.php" class="cancel-btn">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.add-product-page {
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
document.addEventListener('DOMContentLoaded', function() {
    // File upload preview
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
                        <button class="preview-remove" onclick="removeFile(this)">×</button>
                    `;
                } else {
                    preview.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button class="preview-remove" onclick="removeFile(this)">×</button>
                    `;
                }
            };
            
            reader.readAsDataURL(file);
            previewContainer.appendChild(preview);
        });
    }

    // Image upload
    const imageUpload = document.querySelector('#imageUpload input');
    const imagePreview = document.querySelector('#imagePreview');
    imageUpload.addEventListener('change', () => handleFileUpload(imageUpload, imagePreview, false));

    // Video upload
    const videoUpload = document.querySelector('#videoUpload input');
    const videoPreview = document.querySelector('#videoPreview');
    videoUpload.addEventListener('change', () => handleFileUpload(videoUpload, videoPreview, true));

    // Drag and drop
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

function removeFile(button) {
    const item = button.parentElement;
    const container = item.parentElement;
    item.remove();
    
    // Update the file input
    const input = container.previousElementSibling.querySelector('input');
    const dt = new DataTransfer();
    Array.from(input.files).forEach((file, index) => {
        if (index !== Array.from(container.children).indexOf(item)) {
            dt.items.add(file);
        }
    });
    input.files = dt.files;
}
</script>


<?php require_once 'includes/admin-footer.php'; ?>