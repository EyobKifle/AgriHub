/**
 * Manages the User Messages page.
 * - Fetches and renders conversations.
 * - Handles selecting conversations and loading messages.
 * - Sends new messages.
 * - Supports starting new conversations via ?user_id=... (e.g., contact seller).
 */

const els = {
    layout: document.getElementById('messages-layout'),
    convosBody: document.getElementById('conversations-list-body'),
    chatHeader: document.getElementById('chat-header'),
    chatWithName: document.getElementById('chat-with-name'),
    chatMessagesArea: document.getElementById('chat-messages-area'),
    chatEmptyState: document.getElementById('chat-empty-state'),
    chatInputArea: document.getElementById('chat-input-area'),
    messageInput: document.getElementById('message-input'),
    sendMessageBtn: document.getElementById('send-message-btn'),
    backToConvos: document.getElementById('back-to-convos'),
};

let currentUserId = parseInt(els.layout.dataset.currentUserId, 10);
let currentConvoId = null;
let currentRecipientId = null;
let currentRecipientName = '';

// API helper with error handling
async function apiCall(action, params = {}, method = 'GET', body = null) {
    try {
        let url = `/AgriHub/php/api/messages.php?action=${action}`;
        if (method === 'GET' && Object.keys(params).length) {
            url += '&' + new URLSearchParams(params).toString();
        }
        const options = { method };
        if (body) {
            options.headers = { 'Content-Type': 'application/json' };
            options.body = JSON.stringify(body);
        }
        const response = await fetch(url, options);
        if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        const data = await response.json();
        if (!data.success) throw new Error(data.error || 'API failed');
        return data;
    } catch (error) {
        console.error('API Error:', error);
        alert(`Error: ${error.message}. Please try again.`);
        return null;
    }
}

// Fetch and render conversations
async function loadConversations() {
    const data = await apiCall('get_conversations');
    if (!data || !data.conversations) return;

    els.convosBody.innerHTML = '';
    data.conversations.forEach(convo => {
        const item = document.createElement('div');
        item.className = 'conversation-item';
        item.dataset.convoId = convo.conversation_id;
        item.dataset.recipientId = convo.other_user_id;
        item.dataset.recipientName = convo.other_user_name;
        item.innerHTML = `
            <div class="avatar">${convo.other_user_name[0].toUpperCase()}</div>
            <div class="convo-details">
                <h4>${convo.other_user_name}</h4>
                <p>${convo.last_message_content || 'No messages yet'}</p>
                <span class="time">${convo.last_message_time ? new Date(convo.last_message_time).toLocaleString() : ''}</span>
            </div>
        `;
        item.addEventListener('click', () => selectConversation(convo.conversation_id, convo.other_user_id, convo.other_user_name));
        els.convosBody.appendChild(item);
    });
}

// Load messages for a conversation
async function loadMessages(convoId) {
    const data = await apiCall('get_messages', { conversation_id: convoId });
    if (!data || !data.messages) return;

    els.chatMessagesArea.innerHTML = '';
    data.messages.forEach(msg => {
        const msgEl = document.createElement('div');
        msgEl.className = `message ${msg.sender_id === currentUserId ? 'sent' : 'received'}`;
        msgEl.innerHTML = `
            <p>${msg.content}</p>
            <span class="time">${new Date(msg.created_at).toLocaleTimeString()}</span>
        `;
        els.chatMessagesArea.appendChild(msgEl);
    });
    els.chatMessagesArea.scrollTop = els.chatMessagesArea.scrollHeight;
}

// Select a conversation and show chat panel
function selectConversation(convoId, recipientId, recipientName) {
    currentConvoId = convoId;
    currentRecipientId = recipientId;
    currentRecipientName = recipientName;

    els.chatWithName.textContent = recipientName;
    els.chatHeader.style.display = 'flex';
    els.chatInputArea.style.display = 'flex';
    els.chatEmptyState.style.display = 'none';

    // If it's an existing conversation, load its messages.
    // If it's a new one (convoId is null), just clear the area.
    if (convoId) {
        loadMessages(convoId);
    } else {
        els.chatMessagesArea.innerHTML = ''; // Clear for new conversation
    }

    // For mobile: Hide convos list
    if (window.innerWidth < 768) {
        els.layout.classList.add('chat-open');
    }
}

// Send message handler
async function sendMessage() {
    const content = els.messageInput.value.trim();
    if (!content || !currentRecipientId) return;

    const body = { recipient_id: currentRecipientId, content };
    const data = await apiCall('send_message', {}, 'POST', body);
    if (data) {
        els.messageInput.value = '';
        if (!currentConvoId) currentConvoId = data.conversation_id; // New convo
        loadMessages(currentConvoId);
        loadConversations(); // Refresh list
    }
}

// Handle new conversation from ?user_id=
async function initNewConversation() {
    const newUserId = parseInt(els.layout.dataset.newUserId, 10);
    if (!newUserId) return;

    const userData = await apiCall('get_user_details', { user_id: newUserId });
    if (!userData || !userData.user) return;

    // Check if existing convo; if not, select with null convoId (send will create)
    const convosData = await apiCall('get_conversations');
    let existingConvo = null;
    if (convosData && convosData.conversations) {
        existingConvo = convosData.conversations.find(c => c.other_user_id === newUserId);
    }
    if (existingConvo) {
        selectConversation(existingConvo.conversation_id, newUserId, userData.user.name);
    } else {
        selectConversation(null, newUserId, userData.user.name);
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', () => {
    loadConversations();
    initNewConversation();

    els.sendMessageBtn.addEventListener('click', sendMessage);
    els.messageInput.addEventListener('keypress', e => { if (e.key === 'Enter') sendMessage(); });

    els.backToConvos.addEventListener('click', () => {
        els.layout.classList.remove('chat-open');
        currentConvoId = null;
        currentRecipientId = null;
        els.chatHeader.style.display = 'none';
        els.chatInputArea.style.display = 'none';
        els.chatEmptyState.style.display = 'block';
    });

    // Resize handler for mobile/desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            els.layout.classList.remove('chat-open');
        }
});
});