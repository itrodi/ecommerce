<?php
// admin/messages.php

// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check admin authentication
requireAdmin();

// Get database connection
try {
    $conn = connect_db();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Helper function to format message time
function formatMessageTime($timestamp) {
    $now = time();
    $time = strtotime($timestamp);
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . 'm ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . 'h ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . 'd ago';
    } else {
        return date('M j', $time);
    }
}

// Get conversations list
$query = "SELECT 
            u.id as user_id,
            u.name as user_name,
            u.email as user_email,
            MAX(m.created_at) as last_message_time,
            COUNT(CASE WHEN m.read_status = 0 AND m.is_admin_reply = 0 THEN 1 END) as unread_count
          FROM messages m 
          JOIN users u ON m.user_id = u.id 
          GROUP BY u.id 
          ORDER BY last_message_time DESC";

$conversations = $conn->query($query);

require_once 'includes/admin-header.php';
?>

<div class="messages-page">
    <div class="messages-container">
        <!-- Sidebar -->
        <div class="conversations-sidebar">
            <!-- Conversations List -->
            <div class="conversations-list">
                <?php if ($conversations->num_rows > 0): ?>
                    <?php while ($conv = $conversations->fetch_assoc()): ?>
                        <div class="conversation-item <?php echo $conv['unread_count'] > 0 ? 'unread' : ''; ?>"
                             onclick="loadConversation(<?php echo $conv['user_id']; ?>)"
                             data-user-id="<?php echo $conv['user_id']; ?>">
                            <div class="conversation-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="conversation-info">
                                <div class="conversation-name">
                                    <?php echo htmlspecialchars($conv['user_name']); ?>
                                    <?php if ($conv['unread_count'] > 0): ?>
                                        <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="conversation-email">
                                    <?php echo htmlspecialchars($conv['user_email']); ?>
                                </div>
                                <div class="conversation-time">
                                    <?php echo formatMessageTime($conv['last_message_time']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-conversations">
                        <i class="fas fa-inbox"></i>
                        <p>No conversations found</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area" id="chatArea">
            <div class="chat-placeholder">
                <i class="fas fa-comments"></i>
                <p>Select a conversation to view messages</p>
            </div>
        </div>
    </div>
</div>
<style>
.messages-page {
    padding: 1rem;
    height: calc(100vh - 150px);
}

.messages-container {
    display: flex;
    background-color: var(--dark-surface);
    border-radius: 8px;
    height: 100%;
    overflow: hidden;
}

.conversations-sidebar {
    width: 300px;
    border-right: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    flex-direction: column;
}

.conversations-list {
    flex: 1;
    overflow-y: auto;
}

.conversation-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.conversation-item:hover {
    background-color: rgba(255, 255, 255, 0.05);
}

.conversation-item.active {
    background-color: var(--dark-primary);
}

.conversation-item.unread {
    background-color: rgba(187, 134, 252, 0.1);
}

.conversation-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--dark-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--dark-bg);
}

.conversation-info {
    flex: 1;
    min-width: 0;
}

.conversation-name {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.unread-badge {
    background-color: var(--dark-primary);
    color: var(--dark-bg);
    padding: 0.125rem 0.375rem;
    border-radius: 10px;
    font-size: 0.75rem;
}

.conversation-email {
    font-size: 0.875rem;
    color: var(--dark-text-secondary);
    margin-bottom: 0.25rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.conversation-time {
    font-size: 0.75rem;
    color: var(--dark-text-secondary);
}

.chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    background-color: var(--dark-bg);
}

.chat-placeholder {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--dark-text-secondary);
}

.chat-placeholder i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.message {
    max-width: 70%;
    display: flex;
}

.message.user {
    margin-right: auto;
}

.message.admin {
    margin-left: auto;
}

.message-content {
    padding: 1rem;
    border-radius: 8px;
    background-color: rgba(255, 255, 255, 0.1);
}

.message.user .message-content {
    border-bottom-left-radius: 0;
}

.message.admin .message-content {
    background-color: var(--dark-primary);
    color: var(--dark-bg);
    border-bottom-right-radius: 0;
}

.message-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-top: 0.5rem;
    font-size: 0.75rem;
    opacity: 0.7;
}

.chat-header {
    padding: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    background-color: var(--dark-surface);
}

.chat-header h2 {
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.chat-header p {
    color: var(--dark-text-secondary);
    font-size: 0.875rem;
}

.message-form {
    padding: 1rem;
    background-color: var(--dark-surface);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.message-input-container {
    display: flex;
    gap: 1rem;
}

.message-input-container textarea {
    flex: 1;
    min-height: 42px;
    padding: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    background-color: var(--dark-bg);
    color: var(--dark-text);
    resize: none;
}

.message-input-container button {
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--dark-primary);
    color: var(--dark-bg);
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.no-conversations {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: var(--dark-text-secondary);
}

.no-conversations i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.clear-chat-btn {
    padding: 0.5rem 1rem;
    background-color: #dc3545;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    transition: background-color 0.2s ease;
}

.clear-chat-btn:hover {
    background-color: #c82333;
}

.chat-actions {
    display: flex;
    gap: 1rem;
}
</style>

<script>
let activeConversation = null;
let updateInterval;

async function loadConversation(userId) {
    try {
        // Update active conversation
        const prevActive = document.querySelector('.conversation-item.active');
        if (prevActive) prevActive.classList.remove('active');
        
        const currentItem = document.querySelector(`[data-user-id="${userId}"]`);
        if (currentItem) {
            currentItem.classList.remove('unread');
            currentItem.classList.add('active');
            const unreadBadge = currentItem.querySelector('.unread-badge');
            if (unreadBadge) unreadBadge.remove();
        }

        // Clear previous interval
        if (updateInterval) {
            clearInterval(updateInterval);
        }

        activeConversation = userId;

        // Initialize chat area
        const chatArea = document.getElementById('chatArea');
        chatArea.innerHTML = `
              <div class="chat-header">
        <div class="chat-user-info">
            <h2><i class="fas fa-user"></i> ${currentItem.querySelector('.conversation-name').textContent}</h2>
            <p>${currentItem.querySelector('.conversation-email').textContent}</p>
        </div>
        <div class="chat-actions">
            <button type="button" class="clear-chat-btn" onclick="clearMessages(${userId})">
                <i class="fas fa-trash"></i> Clear Chat
            </button>
        </div>
    </div>
    <div class="messages-area" id="chatMessages"></div>
    <form class="message-form" id="messageForm" onsubmit="sendMessage(event)">
        <div class="message-input-container">
            <textarea name="message" placeholder="Type your message..." rows="1"></textarea>
            <button type="submit">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </form>
        `;

        // Add textarea auto-resize
        const textarea = chatArea.querySelector('textarea');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Load initial messages
        await loadMessages();

        // Start auto-update
        updateInterval = setInterval(loadMessages, 3000);

    } catch (error) {
        console.error('Error:', error);
        alert('Error loading conversation');
    }
}


// Add this function to your JavaScript:
async function clearMessages(userId) {
    if (!confirm('Are you sure you want to clear all messages? This cannot be undone.')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('user_id', userId);

        const response = await fetch('ajax/clear_messages.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // Clear messages display
            document.getElementById('chatMessages').innerHTML = '';
            // Refresh the conversation list
            window.location.reload();
        } else {
            throw new Error(result.error || 'Failed to clear messages');
        }
    } catch (error) {
        console.error('Error:', error);
        alert(error.message || 'Error clearing messages');
    }
}

async function loadMessages() {
    try {
        const response = await fetch(`ajax/message_updates.php?action=get_messages&user_id=${activeConversation}`);
        const data = await response.json();
        
        if (data.messages) {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.innerHTML = data.messages.map(message => createMessageHTML(message)).join('');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function createMessageHTML(message) {
    const time = message.created_at.split(' ')[1].substr(0, 5); // Extract HH:MM from timestamp

    return `
        <div class="message ${message.is_admin_reply == 1 ? 'admin' : 'user'}">
            <div class="message-content">
                ${message.message}
                <div class="message-info">
                    <span class="time">${time}</span>
                </div>
            </div>
        </div>
    `;
}

async function sendMessage(event) {
    event.preventDefault();
    
    const form = event.target;
    const textarea = form.querySelector('textarea');
    const message = textarea.value.trim();
    
    if (!message || !activeConversation) return;
    
    try {
        const formData = new FormData();
        formData.append('user_id', activeConversation);
        formData.append('message', message);
        
        const response = await fetch('ajax/message_updates.php?action=send_message', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            textarea.value = '';
            textarea.style.height = 'auto';
            await loadMessages(); // Reload messages after sending
        } else {
            throw new Error(result.error || 'Failed to send message');
        }
    } catch (error) {
        console.error('Error:', error);
        alert(error.message || 'Error sending message');
    }
}

document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        if (updateInterval) {
            clearInterval(updateInterval);
        }
    } else {
        if (activeConversation) {
            updateInterval = setInterval(loadMessages, 3000);
        }
    }
});

window.addEventListener('beforeunload', function() {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
});
</script>

<?php require_once 'includes/admin-footer.php'; ?>