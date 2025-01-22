<?php
// includes/functions.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cart Functions
 */
function addToCart($product_id, $quantity) {
    if (!isset($_SESSION['user_logged_in'])) {
        return false;
    }
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    
    // If product already in cart, update quantity
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    
    // Check if quantity is greater than available stock
    $product = getProduct($product_id);
    if ($product && $_SESSION['cart'][$product_id] > $product['quantity']) {
        $_SESSION['cart'][$product_id] = $product['quantity'];
    }
    
    return true;
}

function removeFromCart($product_id) {
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        return true;
    }
    return false;
}

function updateCartQuantity($product_id, $quantity) {
    if ($quantity <= 0) {
        return removeFromCart($product_id);
    }
    
    $product = getProduct($product_id);
    if ($product) {
        $_SESSION['cart'][$product_id] = min($quantity, $product['quantity']);
        return true;
    }
    return false;
}

function getCartTotal() {
    $total = 0;
    if (isset($_SESSION['cart'])) {
        $conn = connect_db();
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            $product = getProduct($product_id);
            if ($product) {
                $total += $product['price'] * $quantity;
            }
        }
    }
    return $total;
}

function getCartItems() {
    if (!isset($_SESSION['cart'])) {
        return array();
    }
    
    $items = array();
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $product = getProduct($product_id);
        if ($product) {
            $product['cart_quantity'] = $quantity;
            $items[] = $product;
        }
    }
    return $items;
}

/**
 * Product Functions
 */
function getProduct($product_id) {
    $conn = connect_db();
    $query = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getProducts($category = null, $limit = null, $offset = 0, $search = null) {
    $conn = connect_db();
    $params = array();
    $types = "";
    
    $query = "SELECT * FROM products WHERE 1=1";
    
    if ($category) {
        $query .= " AND category = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    if ($search) {
        $query .= " AND (name LIKE ? OR description LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }
    
    $query .= " ORDER BY created_at DESC";
    
    if ($limit) {
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
    }
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

function getSimilarProducts($product_id, $category, $limit = 4) {
    $conn = connect_db();
    $query = "SELECT * FROM products WHERE category = ? AND id != ? LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $category, $product_id, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * File Upload Functions
 */
function uploadFile($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
    $target_dir = "assets/images/uploads/";
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file type
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    // Generate unique filename
    $unique_name = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $unique_name;
    
    // Check file size (5MB limit)
    if ($file['size'] > 5000000) {
        return ['success' => false, 'message' => 'File is too large'];
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => true, 'path' => $target_file];
    }
    
    return ['success' => false, 'message' => 'Failed to upload file'];
}

/**
 * Order Functions
 */
function createOrder($payment_receipt) {
    if (!isset($_SESSION['user_logged_in'])) {
        return false;
    }
    
    $conn = connect_db();
    $user_id = $_SESSION['user_id'];
    $cart_items = getCartItems();
    $total = getCartTotal();
    
    if (empty($cart_items)) {
        return false;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert order
        $query = "INSERT INTO orders (user_id, products, total_amount, payment_receipt) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $products_json = json_encode($cart_items);
        $stmt->bind_param("isds", $user_id, $products_json, $total, $payment_receipt);
        $stmt->execute();
        
        $order_id = $conn->insert_id;
        
        // Update product quantities
        foreach ($cart_items as $item) {
            $new_quantity = $item['quantity'] - $item['cart_quantity'];
            $update_query = "UPDATE products SET quantity = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ii", $new_quantity, $item['id']);
            $update_stmt->execute();
        }
        
        // Clear cart
        $_SESSION['cart'] = array();
        
        // Commit transaction
        $conn->commit();
        return $order_id;
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        return false;
    }
}

function getUserOrders($user_id = null) {
    if (!$user_id && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    
    if (!$user_id) {
        return false;
    }
    
    $conn = connect_db();
    $query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Utility Functions
 */
function sanitize($data) {
    $conn = connect_db();
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return mysqli_real_escape_string($conn, trim($data));
}

function formatPrice($price) {
    return number_format($price, 2);
}

function displayAlert($message, $type = 'success') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

function generatePagination($total_items, $items_per_page, $current_page) {
    $total_pages = ceil($total_items / $items_per_page);
    
    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'has_previous' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
        'previous_page' => max(1, $current_page - 1),
        'next_page' => min($total_pages, $current_page + 1)
    ];
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

?>