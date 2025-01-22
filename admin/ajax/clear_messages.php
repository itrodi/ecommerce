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

$user_id = $_POST['user_id'] ?? 0;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit();
}

$conn = connect_db();

// Delete all messages for this user
$query = "DELETE FROM messages WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to clear messages']);
}