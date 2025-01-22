<?php
// pages/chat.php

// Start output buffering
ob_start();

require_once '../includes/functions.php';
require_once '../includes/user_auth.php';

// Require login for chat
requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $conn = connect_db();
        $query = "INSERT INTO messages (user_id, message, is_admin_reply) VALUES (?, ?, 0)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $user_id, $message);
        
        if (!$stmt->execute()) {
            $error = "Failed to send message. Please try again.";
        }
    }
}

// Get existing messages
$conn = connect_db();
$query = "SELECT m.*, a.email as admin_email 
          FROM messages m 
          LEFT JOIN admin a ON m.admin_id = a.id 
          WHERE m.user_id = ? 
          ORDER BY m.created_at ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$messages = $stmt->get_result();

require_once '../includes/header.php';
?>

<div class="chat-page">
    <div class="chat-container">
        <!-- Chat Header -->
        <div class="chat-header">
            <h1><i class="fas fa-headset"></i> Customer Support</h1>
            <p>We typically reply within 24 hours</p>
        </div>

        <!-- Messages Area -->
        <div class="messages-area" id="messagesArea">
            <?php if ($messages->num_rows === 0): ?>
                <div class="welcome-message">
                    <i class="fas fa-comments"></i>
                    <h2>Welcome to Customer Support</h2>
                    <p>Send us a message and we'll get back to you as soon as possible.</p>
                </div>
            <?php else: ?>
                <?php while ($message = $messages->fetch_assoc()): ?>
                    <div class="message <?php echo $message['is_admin_reply'] ? 'admin' : 'user'; ?>">
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                            <div class="message-info">
                                <?php if ($message['is_admin_reply']): ?>
                                    <span class="sender">Support Team</span>
                                <?php endif; ?>
                                <span class="time">
                                    <?php echo date('M j, g:i A', strtotime($message['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <!-- Message Input -->
        <form method="POST" class="message-form" id="messageForm">
            <div class="message-input-container">
                <textarea name="message" 
                          placeholder="Type your message here..." 
                          rows="3" 
                          required></textarea>
                <button type="submit">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
        </form>
    </div>
</div>

<style>
.chat-page {
    max-width: 800px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.chat-container {
    background-color: var(--dark-surface);
    border-radius: 8px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: calc(100vh - 200px);
}

.chat-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    text-align: center;
}

.chat-header h1 {
    color: var(--dark-primary);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.chat-header p {
    color: var(--dark-text-secondary);
    font-size: 0.9rem;
}

.messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.welcome-message {
    text-align: center;
    color: var(--dark-text-secondary);
    margin: auto;
}

.welcome-message i {
    font-size: 3rem;
    color: var(--dark-primary);
    margin-bottom: 1rem;
}

.welcome-message h2 {
    margin-bottom: 0.5rem;
}

.message {
    max-width: 80%;
    display: flex;
}

.message.user {
    margin-right: auto;
}

.message.admin {
    margin-left: auto;
    flex-direction: row-reverse;
}

.message-content {
    padding: 1rem;
    border-radius: 8px;
    position: relative;
}

.message.user .message-content {
    background-color: var(--dark-primary);
    color: var(--dark-bg);
    border-bottom-left-radius: 0;
}

.message.admin .message-content {
    background-color: rgba(255, 255, 255, 0.1);
    border-bottom-right-radius: 0;
}

.message-info {
    display: flex;
    gap: 0.5rem;
    font-size: 0.8rem;
    margin-top: 0.5rem;
}

.message.user .message-info {
    color: rgba(0, 0, 0, 0.7);
}

.message.admin .message-info {
    color: var(--dark-text-secondary);
}

.message-form {
    padding: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.message-input-container {
    display: flex;
    gap: 1rem;
}

.message-input-container textarea {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    background-color: var(--dark-bg);
    color: var(--dark-text);
    resize: none;
    min-height: 42px;
    max-height: 150px;
}

.message-input-container button {
    background-color: var(--dark-primary);
    color: var(--dark-bg);
    border: none;
    border-radius: 4px;
    width: 42px;
    height: 42px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    align-self: flex-end;
}

.error-message {
    color: #f44336;
    margin-top: 0.5rem;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .message {
        max-width: 90%;
    }
}
</style>

<script>
// Auto-scroll to bottom of messages
function scrollToBottom() {
    const messagesArea = document.getElementById('messagesArea');
    messagesArea.scrollTop = messagesArea.scrollHeight;
}

// Scroll to bottom on page load
scrollToBottom();

// Auto-resize textarea
const textarea = document.querySelector('.message-input-container textarea');
textarea.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
});

// Form submission
document.getElementById('messageForm').addEventListener('submit', function() {
    const textarea = this.querySelector('textarea');
    const message = textarea.value.trim();
    
    if (!message) {
        event.preventDefault();
        return;
    }
});

// Check for new messages every 5 seconds
setInterval(async function() {
    try {
        const response = await fetch('ajax/check_messages.php');
        const data = await response.json();
        
        if (data.messages && data.messages.length > 0) {
            location.reload(); // Refresh page to show new messages
        }
    } catch (error) {
        console.error('Error checking messages:', error);
    }
}, 5000);
</script>

<?php 
require_once '../includes/footer.php';
ob_end_flush();
?>