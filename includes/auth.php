<?php
// includes/auth.php

// First, include the database connection
require_once dirname(__FILE__) . '/../config/database.php';

// Set session settings before starting the session
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 0 for development/localhost
    
    // Set session name
    session_name('ecommerce_admin_session');
    
    // Start session
    session_start();
}

/**
 * Admin Authentication Functions
 */
function adminLogin($email, $password) {
    $conn = connect_db();
    
    // Prevent SQL injection using prepared statements
    $query = "SELECT * FROM admin WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($admin = $result->fetch_assoc()) {
        if (password_verify($password, $admin['password'])) {
            // Set session variables
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['last_activity'] = time();
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            return true;
        }
    }
    
    return false;
}

function adminLogout() {
    // Unset admin session variables
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_email']);
    unset($_SESSION['last_activity']);
    
    // Destroy session
    session_destroy();
    
    // Clear admin cookie if exists
    if (isset($_COOKIE['admin_token'])) {
        setcookie('admin_token', '', time() - 3600, '/');
    }
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Session Security Functions
 */
function checkSessionSecurity() {
    if (!isAdminLoggedIn()) {
        return false;
    }
    
    // Check session timeout (30 minutes)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        adminLogout();
        return false;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Admin Access Control
 */
function requireAdmin() {
    if (!checkSessionSecurity()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: /ecommerce/admin/login.php');
        exit();
    }
}

/**
 * Create Required Tables
 */
function createAdminTables() {
    $conn = connect_db();
    
    // Create admin table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS admin (
        id INT PRIMARY KEY AUTO_INCREMENT,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create admin tokens table
    $conn->query("CREATE TABLE IF NOT EXISTS admin_tokens (
        id INT PRIMARY KEY AUTO_INCREMENT,
        admin_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        expiry DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES admin(id)
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

    // Check if default admin exists
    $result = $conn->query("SELECT id FROM admin LIMIT 1");
    if ($result->num_rows === 0) {
        // Create default admin account
        $default_email = 'admin@example.com';
        $default_password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO admin (email, password) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ss", $default_email, $default_password);
        $stmt->execute();
    }
}

/**
 * Login Attempt Functions
 */
function checkLoginAttempts($email) {
    $conn = connect_db();
    $time = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    
    $query = "SELECT COUNT(*) as attempts FROM login_attempts 
              WHERE email = ? AND attempt_time > ? AND success = 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $email, $time);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['attempts'] < 5; // Allow 5 attempts per 15 minutes
}

function logLoginAttempt($email, $success) {
    $conn = connect_db();
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $query = "INSERT INTO login_attempts (email, success, attempt_time, ip_address) 
              VALUES (?, ?, NOW(), ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sis", $email, $success, $ip);
    $stmt->execute();
}

// Create tables when file is included
createAdminTables();

/**
 * Password Reset Functions
 */
function generateResetToken($email) {
    $conn = connect_db();
    
    // Verify admin email exists
    $query = "SELECT id FROM admin WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        return false;
    }
    
    // Generate token
    $token = bin2hex(random_bytes(32));
    $hashed_token = password_hash($token, PASSWORD_DEFAULT);
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Store token
    $update_query = "UPDATE admin SET 
                    reset_token = ?, 
                    reset_token_expiry = ? 
                    WHERE email = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sss", $hashed_token, $expiry, $email);
    
    if ($update_stmt->execute()) {
        return $token;
    }
    
    return false;
}

// Initialize security headers
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
?>