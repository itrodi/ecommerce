<?php
// admin/ajax/get_messages.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_GET['user_id'] ?? 0;
$after_id = $_GET['after'] ?? 0;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit();
}

$conn = connect_db();

// Build query
$query = "SELECT m.*, u.name as user_name 
          FROM messages m 
          LEFT JOIN users u ON m.user_id = u.id 
          WHERE m.user_id = ? ";
          
if ($after_id) {
    $query .= " AND m.id > ?";
}
$query .= " ORDER BY m.created_at ASC";

$stmt = $conn->prepare($query);

if ($after_id) {
    $stmt->bind_param("ii", $user_id, $after_id);
} else {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Mark messages as read
$update = "UPDATE messages SET read_status = 1 
           WHERE user_id = ? AND is_admin_reply = 0 AND read_status = 0";
$stmt = $conn->prepare($update);
$stmt->bind_param("i", $user_id);
$stmt->execute();

header('Content-Type: application/json');
echo json_encode($messages);