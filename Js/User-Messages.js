/**
 * Manages the private messaging interface.
 * Handles fetching conversations, messages, and sending new messages.
 */

const els = {
    layout: document.getElementById('messages-layout'),
    conversationListBody: document.getElementById('conversations-list-body'),
    chatHeader: document.getElementById('chat-header'),
    chatWithName: document.getElementById('chat-with-name'),
    chatMessagesArea: document.getElementById('chat-messages-area'),
    chatEmptyState: document.getElementById('chat-empty-state'),
    chatInputArea: document.getElementById('chat-input-area'),
    messageInput: document.getElementById('message-input'),
    sendMessageBtn: document.getElementById('send-message-btn'),
    backToConvosBtn: document.getElementById('back-to-convos'),
};

let state = {
    currentUserId: 0,
    activeConversationId: null,
    activeRecipientId: null,
    conversations: [],
};

const API_URL = '/AgriHub/php/ChatApi.php';

/**
 * Fetches all conversations for the current user.
 */
async function fetchConversations() {
    try {
        const response = await fetch(`${API_URL}?action=get_conversations`);
        const result = await response.json();
        if (!result.success) throw new Error(result.message);
        state.conversations = result.data;
        renderConversations();
    } catch (error) {
        console.error('Failed to fetch conversations:', error);
        els.conversationListBody.innerHTML = `<p class="error-state">Could not load conversations.</p>`;
    }
}

/**
 * Renders the list of conversations in the sidebar.
 */
function renderConversations() {
    if (!els.conversationListBody) return;
    els.conversationListBody.innerHTML = '';
    if (state.conversations.length === 0) {
        els.conversationListBody.innerHTML = `<p class="empty-state">No conversations yet.</p>`;
        return;
    }

    const frag = document.createDocumentFragment();
    state.conversations.forEach(convo => {
        const convoEl = document.createElement('div');
        convoEl.className = 'conversation-item';
        convoEl.dataset.conversationId = convo.conversation_id;
        convoEl.dataset.recipientId = convo.other_user_id;
        convoEl.innerHTML = `
            <div class="avatar">
                <img src="${convo.other_user_avatar ? '/AgriHub/' + convo.other_user_avatar : 'https://placehold.co/48x48/cccccc/FFF?text=' + convo.other_user_name.charAt(0)}" alt="${convo.other_user_name}">
            </div>
            <div class="convo-details">
                <div class="convo-header">
                    <span class="name">${convo.other_user_name}</span>
                    <span class="time">${convo.last_message_time ? new Date(convo.last_message_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : ''}</span>
                </div>
                <div class="last-message">
                    ${convo.last_message_content || '...'}
                </div>
            </div>
            ${convo.unread_count > 0 ? `<div class="unread-count">${convo.unread_count}</div>` : ''}
        `;
        frag.appendChild(convoEl);
    });
    els.conversationListBody.appendChild(frag);
}

/**
 * Fetches and displays messages for a given conversation.
 * @param {number} conversationId
 */
async function openConversation(conversationId) {
    state.activeConversationId = conversationId;
    els.chatEmptyState.style.display = 'none';
    els.chatMessagesArea.innerHTML = '<p>Loading messages...</p>';

    try {
        const response = await fetch(`${API_URL}?action=get_messages&conversation_id=${conversationId}`);
        const result = await response.json();
        if (!result.success) throw new Error(result.message);
        renderMessages(result.data);
    } catch (error) {
        console.error('Failed to fetch messages:', error);
        els.chatMessagesArea.innerHTML = `<p class="error-state">Could not load messages.</p>`;
    }
}

/**
 * Renders an array of message objects into the chat area.
 * @param {Array} messages
 */
function renderMessages(messages) {
    els.chatMessagesArea.innerHTML = '';
    const frag = document.createDocumentFragment();
    messages.forEach(msg => {
        const isMe = msg.sender_id === state.currentUserId;
        const msgEl = document.createElement('div');
        msgEl.className = `message ${isMe ? 'sent' : 'received'}`;
        msgEl.innerHTML = `
            <div class="bubble">
                <div class="text">${msg.content}</div>
                <div class="time">${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
            </div>
        `;
        frag.appendChild(msgEl);
    });
    els.chatMessagesArea.appendChild(frag);
    els.chatMessagesArea.scrollTop = els.chatMessagesArea.scrollHeight;
}

/**
 * Sends a new message.
 */
async function sendMessage() {
    const content = els.messageInput.value.trim();
    if (!content || !state.activeRecipientId) return;

    const originalButtonContent = els.sendMessageBtn.innerHTML;
    els.sendMessageBtn.disabled = true;
    els.sendMessageBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    try {
        const response = await fetch(`${API_URL}?action=send_message`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                recipient_id: state.activeRecipientId,
                content: content,
            }),
        });
        const result = await response.json();
        if (!result.success) throw new Error(result.message);

        els.messageInput.value = '';
        // If it was a new conversation, we now have an ID.
        if (!state.activeConversationId) {
            state.activeConversationId = result.data.conversation_id;
        }
        await openConversation(state.activeConversationId); // Refresh messages
        await fetchConversations(); // Refresh conversation list to show new last message
    } catch (error) {
        console.error('Failed to send message:', error);
        alert('Error: Could not send message.');
    } finally {
        els.sendMessageBtn.disabled = false;
        els.sendMessageBtn.innerHTML = originalButtonContent;
    }
}

function handleConversationClick(e) {
    const target = e.target.closest('.conversation-item');
    if (!target) return;

    const conversationId = parseInt(target.dataset.conversationId, 10);
    const recipientId = parseInt(target.dataset.recipientId, 10);
    const recipientName = target.querySelector('.name').textContent;

    state.activeConversationId = conversationId;
    state.activeRecipientId = recipientId;

    els.chatWithName.textContent = recipientName;
    els.chatHeader.style.display = 'flex';
    els.chatInputArea.style.display = 'flex';
    els.layout.classList.add('chat-active');

    openConversation(conversationId);
}

async function init() {
    if (!els.layout) return;

    state.currentUserId = parseInt(els.layout.dataset.currentUserId, 10);
    await fetchConversations();

    els.conversationListBody.addEventListener('click', handleConversationClick);
    els.sendMessageBtn.addEventListener('click', sendMessage);
    els.messageInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });
    els.backToConvosBtn.addEventListener('click', () => {
        els.layout.classList.remove('chat-active');
    });

    // Check if we need to start a new conversation
    const newUserId = parseInt(els.layout.dataset.newUserId, 10);
    if (newUserId && newUserId !== state.currentUserId) {
        const existingConvo = state.conversations.find(c => c.other_user_id === newUserId);
        if (existingConvo) {
            // Found existing conversation, open it
            document.querySelector(`[data-conversation-id="${existingConvo.conversation_id}"]`).click();
        } else {
            // No existing conversation, prepare a new one
            // This part would require fetching the user's name to display it.
            // For now, we'll just set the recipient and show the input.
            state.activeConversationId = null;
            state.activeRecipientId = newUserId;
            els.chatWithName.textContent = 'New Message'; // A better implementation would fetch the user's name
            els.chatHeader.style.display = 'flex';
            els.chatInputArea.style.display = 'flex';
            els.chatEmptyState.style.display = 'none';
            els.layout.classList.add('chat-active');
        }
    }
}

document.addEventListener('DOMContentLoaded', init);