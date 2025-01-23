<?php
// admin/ajax/message_updates.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Check admin authentication
if (!isAdminLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';
$user_id = $_GET['user_id'] ?? 0;

$conn = connect_db();

switch ($action) {
    case 'get_messages':
        // Get all messages for this user
        $query = "SELECT m.*, u.name as user_name 
                 FROM messages m 
                 JOIN users u ON m.user_id = u.id 
                 WHERE m.user_id = ? 
                 ORDER BY m.created_at ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = [
                'id' => $row['id'],
                'message' => $row['message'],
                'is_admin_reply' => $row['is_admin_reply'],
                'created_at' => $row['created_at']
            ];
        }

        // Mark messages as read
        $update = "UPDATE messages 
                  SET read_status = 1 
                  WHERE user_id = ? AND is_admin_reply = 0";
        $stmt = $conn->prepare($update);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        echo json_encode(['messages' => $messages]);
        break;

    case 'send_message':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Invalid method']);
            exit();
        }

        $message = trim($_POST['message'] ?? '');
        $user_id = $_POST['user_id'] ?? 0;
        
        if (empty($message) || empty($user_id)) {
            echo json_encode(['error' => 'Message and user ID are required']);
            exit();
        }

        // Since admin_id might not be needed based on your DB structure
        $query = "INSERT INTO messages (user_id, message, is_admin_reply, read_status) 
                 VALUES (?, ?, 1, 0)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $user_id, $message);
        
        if ($stmt->execute()) {
            // Get the inserted message
            $message_id = $stmt->insert_id;
            $query = "SELECT * FROM messages WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $message_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $new_message = $result->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'message' => [
                    'id' => $new_message['id'],
                    'message' => $new_message['message'],
                    'is_admin_reply' => 1,
                    'created_at' => $new_message['created_at']
                ]
            ]);
        } else {
            echo json_encode([
                'error' => 'Failed to send message',
                'sql_error' => $conn->error
            ]);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}