<?php
// includes/user_auth.php

// Start session at the very beginning if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters before starting session
    if (!headers_sent()) {  // Only set if headers not sent
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0);  // Set to 0 for development/localhost
    }
    session_start();
}

// Include core dependencies
require_once dirname(__FILE__) . '/../config/database.php';
require_once 'functions.php';

// Create necessary tables
function createUserTables() {
    $conn = connect_db();
    
    // Create users table if it doesn't exist
    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        reset_token VARCHAR(255) NULL,
        reset_token_expiry DATETIME NULL,
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        login_attempts INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create user activity log table
    $conn->query("CREATE TABLE IF NOT EXISTS user_activity_log (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        action VARCHAR(255) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    // Create login attempts table
    $conn->query("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        email VARCHAR(255) NOT NULL,
        success TINYINT(1) NOT NULL,
        attempt_time DATETIME NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// Execute table creation
createUserTables();

/**
 * User Authentication Functions
 */
function registerUser($name, $email, $password) {
    $conn = connect_db();
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'All fields are required'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters'];
    }
    
    // Check if email already exists
    $check_query = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $query = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $name, $email, $hashed_password);
    
    if ($stmt->execute()) {
        return ['success' => true, 'user_id' => $conn->insert_id];
    }
    
    return ['success' => false, 'message' => 'Registration failed'];
}

function loginUser($email, $password) {
    $conn = connect_db();
    
    // Check login attempts
    if (!checkUserLoginAttempts($email)) {
        return ['success' => false, 'message' => 'Too many login attempts. Please try again later.'];
    }
    
    // Get user
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            // Regenerate session ID
            session_regenerate_id(true);
            
            // Log successful login
            logUserLoginAttempt($email, true);
            
            return ['success' => true];
        }
    }
    
    // Log failed login
    logUserLoginAttempt($email, false);
    
    return ['success' => false, 'message' => 'Invalid email or password'];
}

function logoutUser() {
    // Clear user session variables
    unset($_SESSION['user_logged_in']);
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_email']);
    
    // Clear cart
    unset($_SESSION['cart']);
    
    // Destroy session
    session_destroy();
}

function isUserLoggedIn() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

function requireLogin() {
    if (!isUserLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: /ecommerce/login.php');
        exit();
    }
}

/**
 * User Security Functions
 */
function checkUserLoginAttempts($email) {
    $conn = connect_db();
    $time = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    
    $query = "SELECT COUNT(*) as attempts 
              FROM login_attempts 
              WHERE email = ? AND attempt_time > ? AND success = 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $email, $time);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['attempts'] < 5;
}

function logUserLoginAttempt($email, $success) {
    $conn = connect_db();
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $query = "INSERT INTO login_attempts (email, success, attempt_time, ip_address) 
              VALUES (?, ?, NOW(), ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sis", $email, $success, $ip);
    $stmt->execute();
}

/**
 * User Profile Functions
 */
function updateUserProfile($user_id, $name, $current_password = null, $new_password = null) {
    $conn = connect_db();
    $updates = [];
    $types = "";
    $params = [];
    
    // Update name if provided
    if (!empty($name)) {
        $updates[] = "name = ?";
        $types .= "s";
        $params[] = $name;
    }
    
    // Update password if both current and new are provided
    if ($current_password && $new_password) {
        // Verify current password
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (!$user || !password_verify($current_password, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        if (strlen($new_password) < 8) {
            return ['success' => false, 'message' => 'New password must be at least 8 characters'];
        }
        
        $updates[] = "password = ?";
        $types .= "s";
        $params[] = password_hash($new_password, PASSWORD_DEFAULT);
    }
    
    if (empty($updates)) {
        return ['success' => false, 'message' => 'No changes to update'];
    }
    
    // Add user_id to params
    $types .= "i";
    $params[] = $user_id;
    
    $query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Profile updated successfully'];
    }
    
    return ['success' => false, 'message' => 'Failed to update profile'];
}

/**
 * Get User Data
 */
function getUserData($user_id = null) {
    if (!$user_id && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    
    if (!$user_id) {
        return false;
    }
    
    $conn = connect_db();
    $query = "SELECT id, name, email, created_at, status 
              FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

?>