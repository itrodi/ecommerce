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

// Get filters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query
$where_clauses = [];
$params = [];
$types = "";

if ($filter === 'unread') {
    $where_clauses[] = "m.read_status = 0";
}

if ($search) {
    $where_clauses[] = "(u.name LIKE ? OR u.email LIKE ? OR m.message LIKE ?)";
    $search_param = "%$search%";
    array_push($params, $search_param, $search_param, $search_param);
    $types .= "sss";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get total messages count
$count_query = "SELECT COUNT(DISTINCT u.id) as total 
                FROM messages m 
                JOIN users u ON m.user_id = u.id 
                $where_sql";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_conversations = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_conversations / $per_page);

// Get conversations list with latest message
$query = "SELECT 
            u.id as user_id,
            u.name as user_name,
            u.email as user_email,
            MAX(m.created_at) as last_message_time,
            COUNT(CASE WHEN m.read_status = 0 AND m.is_admin_reply = 0 THEN 1 END) as unread_count
          FROM messages m 
          JOIN users u ON m.user_id = u.id 
          $where_sql 
          GROUP BY u.id 
          ORDER BY last_message_time DESC 
          LIMIT ? OFFSET ?";
$types .= "ii";
array_push($params, $per_page, $offset);

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$conversations = $stmt->get_result();

require_once 'includes/admin-header.php';
?>

<div class="messages-page">
    <div class="messages-container">
        <!-- Sidebar -->
        <div class="conversations-sidebar">
            <!-- Search and Filter -->
            <div class="sidebar-header">
                <form class="search-form" method="GET">
                    <div class="search-input">
                        <input type="text" name="search" placeholder="Search conversations..."
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <select name="filter" class="filter-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Messages</option>
                        <option value="unread" <?php echo $filter === 'unread' ? 'selected' : ''; ?>>Unread</option>
                    </select>
                </form>
            </div>

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

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>" 
                           class="page-link">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <span class="page-info"><?php echo $page; ?> / <?php echo $total_pages; ?></span>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>" 
                           class="page-link">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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

.sidebar-header {
    padding: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.search-form {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.search-input {
    position: relative;
}

.search-input input {
    width: 100%;
    padding: 0.5rem 2rem 0.5rem 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    background-color: var(--dark-bg);
    color: var(--dark-text);
}

.chat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background-color: var(--dark-surface);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.chat-header .chat-actions {
    display: flex;
    gap: 1rem;
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
}

.search-input button {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--dark-text-secondary);
    cursor: pointer;
}

.filter-select {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    background-color: var(--dark-bg);
    color: var(--dark-text);
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

.chat-messages {
    flex: 1;
    padding: 1rem;
    overflow-y: auto;
}

.messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    background-color: var(--dark-bg);
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
    background-color: rgba(255, 255, 255, 0.1);
    border-bottom-left-radius: 0;
}

.message.admin .message-content {
    background-color: var(--dark-primary);
    color: var(--dark-bg);
    border-bottom-right-radius: 0;
}

.message-info {
    display: flex;
    gap: 0.5rem;
    font-size: 0.8rem;
    margin-top: 0.5rem;
}

.message.user .message-info {
    color: var(--dark-text-secondary);
}

.message.admin .message-info {
    color: rgba(0, 0, 0, 0.7);
}

.message-input-container {
    display: flex;
    gap: 1rem;
    align-items: flex-end;
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
}

.chat-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    background-color: var(--dark-surface);
}

.chat-header h2 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.chat-header p {
    color: var(--dark-text-secondary);
    font-size: 0.9rem;
}
</style>

<script>
let activeConversation = null;
let lastMessageId = 0;
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
        }

        // Clear previous interval
        if (updateInterval) {
            clearInterval(updateInterval);
        }

        activeConversation = userId;
        lastMessageId = 0;

        // Initialize chat area
        // In your loadConversation function, update the chat area initialization:
const chatArea = document.getElementById('chatArea');
chatArea.innerHTML = `
    <div class="chat-header">
        <div class="chat-user-info">
            <h2><i class="fas fa-user"></i> ${currentItem.querySelector('.conversation-name').textContent}</h2>
            <p>${currentItem.querySelector('.conversation-email').textContent}</p>
        </div>
        <div class="chat-actions">
            <button class="clear-chat-btn" onclick="clearMessages(${userId})">
                <i class="fas fa-trash"></i> Clear Chat
            </button>
        </div>
    </div>
    <div class="messages-area" id="chatMessages"></div>
    <div class="chat-input">
        <form id="messageForm" onsubmit="sendMessage(event)">
            <div class="message-input-container">
                <textarea name="message" placeholder="Type your message..." rows="1"></textarea>
                <label for="imageUpload" class="image-upload-label">
                    <i class="fas fa-image"></i>
                </label>
                <input type="file" id="imageUpload" name="image" accept="image/*" style="display: none">
                <button type="submit">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </form>
    </div>
`;

        // Add image upload preview handler
        const imageUpload = document.getElementById('imageUpload');
        imageUpload.addEventListener('change', handleImageSelect);

        // Add textarea auto-resize
        const textarea = document.querySelector('.chat-input textarea');
        textarea.addEventListener('input', autoResize);

        // Load initial messages
        await loadMessages();

        // Start auto-update
        updateInterval = setInterval(loadNewMessages, 5000);

    } catch (error) {
        console.error('Error:', error);
        alert('Error loading conversation');
    }
}

function handleImageSelect(event) {
    const file = event.target.files[0];
    if (file) {
        if (file.size > 5 * 1024 * 1024) { // 5MB limit
            alert('Image size must be less than 5MB');
            event.target.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.className = 'image-preview';
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Upload preview">
                <button type="button" onclick="removeImagePreview()">×</button>
            `;
            
            const existingPreview = document.querySelector('.image-preview');
            if (existingPreview) {
                existingPreview.remove();
            }
            
            document.querySelector('.input-wrapper').appendChild(preview);
        };
        reader.readAsDataURL(file);
    }
}

function removeImagePreview() {
    const preview = document.querySelector('.image-preview');
    if (preview) {
        preview.remove();
    }
    document.getElementById('imageUpload').value = '';
}

function autoResize(event) {
    const textarea = event.target;
    textarea.style.height = 'auto';
    textarea.style.height = (textarea.scrollHeight) + 'px';
}

async function loadMessages() {
    try {
        const response = await fetch(`ajax/get_messages.php?user_id=${activeConversation}`);
        const messages = await response.json();
        
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.innerHTML = messages.map(message => createMessageHTML(message)).join('');
        
        // Update last message ID
        if (messages.length > 0) {
            lastMessageId = messages[messages.length - 1].id;
        }

        // Scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
    } catch (error) {
        console.error('Error:', error);
    }
}

async function loadNewMessages() {
    if (!activeConversation || !lastMessageId) return;

    try {
        const response = await fetch(`ajax/get_messages.php?user_id=${activeConversation}&after=${lastMessageId}`);
        const messages = await response.json();

        if (messages.length > 0) {
            const chatMessages = document.getElementById('chatMessages');
            messages.forEach(message => {
                chatMessages.insertAdjacentHTML('beforeend', createMessageHTML(message));
                lastMessageId = message.id;
            });

            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function createMessageHTML(message) {
    const time = new Date(message.created_at).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
        hour12: true
    });

    let content = message.message;
    if (message.image_path) {
        content += `<div class="message-image">
            <img src="../${message.image_path}" alt="Message image">
        </div>`;
    }

    return `
        <div class="message ${message.is_admin_reply ? 'admin' : 'user'}">
            <div class="message-content">
                ${nl2br(content)}
                <div class="message-info">
                    ${message.is_admin_reply ? '<span class="sender">Support Team</span>' : ''}
                    <span class="time">${time}</span>
                </div>
            </div>
        </div>
    `;
}

// Helper function to convert newlines to <br> tags
function nl2br(str) {
    return str.replace(/\n/g, '<br>');
}

async function sendMessage(event) {
    event.preventDefault();
    
    const form = event.target;
    const textarea = form.querySelector('textarea');
    const imageInput = form.querySelector('input[type="file"]');
    const message = textarea.value.trim();
    
    if ((!message && !imageInput.files[0]) || !activeConversation) return;
    
    try {
        const formData = new FormData();
        formData.append('user_id', activeConversation);
        formData.append('message', message);
        
        if (imageInput.files[0]) {
            formData.append('image', imageInput.files[0]);
        }
        
        const response = await fetch('ajax/send_message.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            textarea.value = '';
            textarea.style.height = 'auto';
            imageInput.value = '';
            removeImagePreview();
            loadNewMessages();
        } else {
            throw new Error(result.message || 'Failed to send message');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error sending message');
    }
}

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
            document.getElementById('chatMessages').innerHTML = '';
            lastMessageId = 0;

            const conversationItem = document.querySelector(`[data-user-id="${userId}"]`);
            const unreadBadge = conversationItem.querySelector('.unread-badge');
            if (unreadBadge) {
                unreadBadge.remove();
            }
        } else {
            throw new Error(result.message || 'Failed to clear messages');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error clearing messages');
    }
}

document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        if (updateInterval) {
            clearInterval(updateInterval);
        }
    } else {
        if (activeConversation) {
            updateInterval = setInterval(loadNewMessages, 5000);
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