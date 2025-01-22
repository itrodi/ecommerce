<?php
// ajax/chat_updates.php
require_once '../includes/functions.php';
require_once '../includes/user_auth.php';

// Ensure user is logged in
if (!isUserLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get parameters
$user_id = $_SESSION['user_id'];
$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
$action = $_GET['action'] ?? 'get_messages';

$conn = connect_db();

switch ($action) {
    case 'get_messages':
        // Get new messages
        $query = "SELECT m.*, a.email as admin_email 
                  FROM messages m 
                  LEFT JOIN admin a ON m.admin_id = a.id 
                  WHERE m.user_id = ? AND m.id > ? 
                  ORDER BY m.created_at ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $user_id, $last_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            // Mark user's messages as read when admin replies
            if ($row['is_admin_reply'] && !$row['read_status']) {
                markMessageAsRead($row['id']);
            }
            
            $messages[] = [
                'id' => $row['id'],
                'message' => $row['message'],
                'is_admin_reply' => $row['is_admin_reply'],
                'timestamp' => date('c', strtotime($row['created_at'])),
                'admin_email' => $row['admin_email']
            ];
        }
        
        echo json_encode(['messages' => $messages]);
        break;

    case 'send_message':
        // Validate request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit();
        }

        $message = trim($_POST['message'] ?? '');
        if (empty($message)) {
            http_response_code(400);
            echo json_encode(['error' => 'Message cannot be empty']);
            exit();
        }

        // Insert message
        $query = "INSERT INTO messages (user_id, message, is_admin_reply) VALUES (?, ?, 0)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $user_id, $message);
        
        if ($stmt->execute()) {
            $message_id = $stmt->insert_id;
            
            // Get the inserted message details
            $query = "SELECT m.*, a.email as admin_email 
                      FROM messages m 
                      LEFT JOIN admin a ON m.admin_id = a.id 
                      WHERE m.id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $message_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $message_data = $result->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'message' => [
                    'id' => $message_data['id'],
                    'message' => $message_data['message'],
                    'is_admin_reply' => false,
                    'timestamp' => date('c', strtotime($message_data['created_at'])),
                    'admin_email' => null
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to send message']);
        }
        break;

    case 'mark_read':
        // Mark messages as read
        $query = "UPDATE messages 
                  SET read_status = 1 
                  WHERE user_id = ? AND is_admin_reply = 1 AND read_status = 0";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update message status']);
        }
        break;

    case 'get_status':
        // Get unread message count
        $query = "SELECT COUNT(*) as unread 
                  FROM messages 
                  WHERE user_id = ? AND is_admin_reply = 1 AND read_status = 0";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $unread = $result->fetch_assoc()['unread'];
        
        // Get admin online status (if admin replied in last 5 minutes)
        $query = "SELECT MAX(created_at) as last_reply 
                  FROM messages 
                  WHERE user_id = ? AND is_admin_reply = 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $last_reply = $result->fetch_assoc()['last_reply'];
        
        $admin_active = false;
        if ($last_reply) {
            $last_reply_time = strtotime($last_reply);
            $admin_active = (time() - $last_reply_time) < 300; // 5 minutes
        }
        
        echo json_encode([
            'unread_count' => $unread,
            'admin_active' => $admin_active
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

/**
 * Helper function to mark message as read
 */
function markMessageAsRead($message_id) {
    global $conn;
    $query = "UPDATE messages SET read_status = 1 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
}
?>