<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get message data
$user_id = $_POST['user_id'] ?? 0;
$message = trim($_POST['message'] ?? '');

if (!$user_id || empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid message data']);
    exit();
}

$conn = connect_db();

// Insert message
$query = "INSERT INTO messages (user_id, admin_id, message, is_admin_reply) 
          VALUES (?, ?, ?, 1)";
$stmt = $conn->prepare($query);
$admin_id = $_SESSION['admin_id'];
$stmt->bind_param("iis", $user_id, $admin_id, $message);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message_id' => $stmt->insert_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send message']);
 }
    
 ?>