/**
 * This file is the main controller for the community chat feature.
 * It orchestrates the UI, data, and event handling for the chat.
 */

import { addMessage, updateMessage, deleteMessage } from './chat/chat-api.js';
import { session } from './chat/chat-session.js';
import { els, renderAll, createAttachmentPreview, toggleEditState } from './chat/chat-ui.js';

/**
 * A helper function to get the current time as a standard string.
 * ISOString format looks like: "2024-01-01T12:30:00.000Z"
 */
function nowISO() { return new Date().toISOString(); }

/**
 * Converts a list of files from an input into a format that can be saved.
 * For images, it creates a "Data URL" which is a long string representing the image.
 * For other files, it creates a temporary blob URL.
 */
async function filesToAttachments(files) {
  const atts = [];
  for (const f of files) {
    if (f.type.startsWith('image/')) {
      const dataUrl = await new Promise(res => { const r = new FileReader(); r.onload = e => res(e.target.result); r.readAsDataURL(f); });
      atts.push({ type: 'image', url: dataUrl, name: f.name });
    } else if (f.type === 'application/pdf') {
      // For persistence in localStorage, we must use a Data URL, but for a real backend, a blob would be uploaded.
      const blobUrl = URL.createObjectURL(f);
      atts.push({ type: 'pdf', url: blobUrl, name: f.name });
    }
  }
  return atts;
}

/**
 * A function to simulate another user replying to a message.
 * It waits a short time and then adds a new message to the list.
 */
function scheduleBotReply() {
  // Simulate real-time by storing a bot reply; will propagate via storage event to other tabs
  const botUser = { id: 999, name: 'AgriBot', avatar: 'https://placehold.co/48x48/94a3b8/FFF?text=B' };
  setTimeout(() => {
    const msg = { id: crypto.randomUUID(), user: botUser, text: `@${session.currentUser.name} thanks for sharing!`, attachments: [], createdAt: nowISO(), discussionId: getDiscussionId() };
    addMessage(msg).then(() => {
      renderAll(getDiscussionId());
    }).catch(err => {
      console.error("Bot failed to reply:", err);
    });
  }, 1200);
}

// --- INITIALIZATION ---

function getDiscussionId() {
  return session.discussionId;
}

// When the page first loads, run these functions.
function initializeChat() {
  renderAll(getDiscussionId());
  initializeEventListeners();
}

/**
 * Groups all event listener attachments in one place.
 */
function initializeEventListeners() {
  els.form.addEventListener('submit', handleFormSubmit);
  els.messages.addEventListener('click', handleMessageActions);
  els.file.addEventListener('change', handleFileSelection);
}

/**
 * Handles the main form submission, delegating to the correct handler
 * based on whether we are in 'new' or 'edit' mode.
 * @param {Event} e The form submission event.
 */
async function handleFormSubmit(e) {
  e.preventDefault();
  const mode = els.form.dataset.mode;

  if (mode === 'edit') {
    await handleEditMessageSubmit();
  } else {
    await handleNewMessageSubmit();
  }

  renderAll(getDiscussionId());
}

/**
 * Handles the logic for submitting a new message.
 */
async function handleNewMessageSubmit() {
  const text = els.input.value.trim();
  const files = Array.from(els.file.files);
  if (!text && files.length === 0) return;

  const attachments = await filesToAttachments(files);
  const msg = {
    user: session.currentUser,
    text,
    attachments,
    discussionId: getDiscussionId(),
  };

  try {
    await addMessage(msg);
    els.input.value = '';
    els.file.value = '';
    els.previews.innerHTML = '';
    scheduleBotReply(); // Schedule a fake reply from a bot.
  } catch (err) {
    console.error("Failed to send message:", err);
    alert("Could not send message. Please try again.");
  }
}

/**
 * Handles the logic for submitting an edited message.
 */
async function handleEditMessageSubmit() {
  const messageId = els.form.dataset.editingId;
  const text = els.input.value.trim();
  if (!text || !messageId) return;

  try {
    await updateMessage(messageId, text);
    toggleEditState(null); // Exit edit mode
  } catch (err) {
    console.error("Failed to update message:", err);
    alert("Could not update message. Please try again.");
  }
}

/**
 * Handles clicks on Edit and Delete buttons using event delegation.
 */
function handleMessageActions(e) {
  const editButton = e.target.closest('.edit-btn');
  if (editButton) {
    toggleEditState(editButton.dataset.messageId);
    return;
  }

  const deleteButton = e.target.closest('.delete-btn');
  if (deleteButton) {
    const messageId = deleteButton.dataset.messageId;
    if (confirm('Are you sure you want to delete this message?')) {
      deleteMessage(messageId)
        .then(() => renderAll(getDiscussionId()))
        .catch(err => alert("Could not delete the message. Please try again."));
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
