/**
 * This file is the main controller for the community chat feature.
 * It orchestrates the UI, data, and event handling for the chat.
 */

import { getMessages, addMessage, updateMessage, deleteMessage } from './chat-api.js';
import { els, renderAll, createAttachmentPreview, toggleEditState } from './chat-ui.js';

/**
 * Fetches messages from the server and renders them.
 * @param {string} discussionId The ID of the discussion.
 * @param {object} currentUser The currently logged-in user object.
 */
async function refreshMessages(discussionId, currentUser) {
  try {
    const messages = await getMessages(discussionId);
    renderAll(messages, currentUser);
  } catch (error) {
    console.error('Failed to refresh messages:', error);
    els.messages.innerHTML = `<div class="error-state">Could not load messages. Please try refreshing the page.</div>`;
  }
}

function getDiscussionId() {
  // The discussion ID is now reliably set on the body element by the PHP script.
  return document.body.dataset.discussionId || '0';
}

function getCurrentUser() {
    const userEl = document.getElementById('user-data');
    if (userEl && userEl.textContent) {
        return JSON.parse(userEl.textContent);
    }
    return { id: 0, name: 'Guest', avatar: 'https://placehold.co/48x48/cccccc/FFF?text=G' };
}

// When the page first loads, run these functions.
async function initializeChat() {
  const discussionId = getDiscussionId();
  const currentUser = getCurrentUser();

  if (!discussionId || discussionId === '0') {
    els.messages.innerHTML = '<div class="error-state">Invalid or missing discussion ID. Cannot load chat.</div>';
    els.form.style.display = 'none';
    return;
  }

  await refreshMessages(discussionId, currentUser);
  initializeEventListeners(discussionId, currentUser);
}

/**
 * Groups all event listener attachments in one place.
 */
function initializeEventListeners(discussionId, currentUser) {
  els.form.addEventListener('submit', (e) => handleFormSubmit(e, discussionId, currentUser));
  els.messages.addEventListener('click', handleMessageActions);
  els.file.addEventListener('change', handleFileSelection);
}

/**
 * Handles the main form submission, delegating to the correct handler
 * based on whether we are in 'new' or 'edit' mode.
 * @param {Event} e The form submission event.
 */
async function handleFormSubmit(e, discussionId, currentUser) {
  e.preventDefault();
  const mode = els.form.dataset.mode;

  if (mode === 'edit') {
    await handleEditMessageSubmit(discussionId, currentUser);
  } else {
    await handleNewMessageSubmit(discussionId, currentUser);
  }
}

/**
 * Handles the logic for submitting a new message.
 */
async function handleNewMessageSubmit(discussionId, currentUser) {
  const text = els.input.value.trim();
  // Note: File attachments are not yet handled by the backend.
  if (!text) return;

  const msg = {
    // The backend will use the session user, but we send text and discussionId.
    text,
    discussionId: discussionId,
  };

  try {
    await addMessage(msg);
    els.input.value = '';
    els.file.value = '';
    els.previews.innerHTML = '';
    
    // Refresh the chat to show the new message
    await refreshMessages(discussionId, currentUser);
  } catch (err) {
    console.error("Failed to send message:", err);
    alert("Could not send message. Please try again.");
  }
}

/**
 * Handles the logic for submitting an edited message.
 */
async function handleEditMessageSubmit(discussionId, currentUser) {
  const messageId = els.form.dataset.editingId;
  const text = els.input.value.trim();
  if (!text || !messageId) return;

  try {
    await updateMessage(messageId, text);
    // Refresh the chat to show the edited message
    await refreshMessages(discussionId, currentUser);
    toggleEditState(null); // Exit edit mode
  } catch (err) {
    console.error("Failed to update message:", err);
    alert("Could not update message. Please try again.");
  }
}

/**
 * Handles clicks on Edit and Delete buttons using event delegation.
 */
async function handleMessageActions(e) {
  const editButton = e.target.closest('.edit-btn');
  if (editButton) {
    toggleEditState(editButton.dataset.messageId);
    return;
  }

  const deleteButton = e.target.closest('.delete-btn');
  if (deleteButton) {
    const messageId = deleteButton.dataset.messageId;
    const discussionId = getDiscussionId();
    const currentUser = getCurrentUser();
    if (confirm('Are you sure you want to delete this message?')) {
      try {
        await deleteMessage(messageId);
      } catch (err) {
        alert("Could not delete the message. Please try again.");
      }
      // Refresh the chat after deletion
      await refreshMessages(discussionId, currentUser);
    }
  }
}

function handleFileSelection() {
  els.previews.innerHTML = '';
  const onRemove = (fileToRemove) => {
    const files = Array.from(els.file.files).filter(f => f !== fileToRemove);
    const dt = new DataTransfer();
    files.forEach(f => dt.items.add(f));
    els.file.files = dt.files;
  };

  Array.from(els.file.files).forEach(f => {
    els.previews.appendChild(createAttachmentPreview(f, onRemove));
  });
}

initializeChat();
