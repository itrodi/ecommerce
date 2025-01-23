<?php
// admin/ajax/clear_messages.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Check admin authentication
if (!isAdminLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid method']);
    exit();
}

$user_id = $_POST['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['error' => 'User ID is required']);
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
    echo json_encode([
        'error' => 'Failed to clear messages',
        'sql_error' => $conn->error
    ]);
}