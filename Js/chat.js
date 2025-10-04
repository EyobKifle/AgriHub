/**
 * This file is the main controller for the community chat feature.
 * It orchestrates the UI, data, and event handling for the chat.
 */

import { LS_KEY, currentUser, demoUsers } from './chat/chat-config.js';
import { loadMessages, saveMessages } from './chat/chat-data.js';
import { els, renderAll, createAttachmentPreview } from './chat/chat-ui.js';

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
 * If the chat is empty when the page loads, this function adds some
 * example messages to show how it works.
 */
function seedDemoIfEmpty() {
  const list = loadMessages();
  if (list.length) return;
  const demo = [
    { id: crypto.randomUUID(), user: demoUsers[0], text: 'Welcome to the community chat! Share your tips.', attachments: [], createdAt: nowISO() },
    { id: crypto.randomUUID(), user: currentUser, text: 'Hi all! Uploading a PDF checklist.', attachments: [{ type: 'pdf', url: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf', name: 'Checklist.pdf' }], createdAt: nowISO() },
    { id: crypto.randomUUID(), user: demoUsers[1], text: 'Here is an image of my drip setup.', attachments: [{ type: 'image', url: 'https://placehold.co/600x400/1e4620/FFF?text=Drip+Setup', name: 'drip.png' }], createdAt: nowISO() }
  ];
  saveMessages(demo);
}

/**
 * A function to simulate another user replying to a message.
 * It waits a short time and then adds a new message to the list.
 */
function scheduleBotReply() {
  // Simulate real-time by storing a bot reply; will propagate via storage event to other tabs
  setTimeout(() => {
    const msg = { id: crypto.randomUUID(), user: demoUsers[2], text: `@${currentUser.name} thanks for sharing!`, attachments: [], createdAt: nowISO() };
    const list = loadMessages();
    list.push(msg);
    saveMessages(list);
    renderAll();
  }, 1200);
}

// --- EVENT LISTENERS ---

// When the user selects a file to upload, show the previews.
els.file.addEventListener('change', () => {
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
});

// When the user submits the chat form (by clicking Send or pressing Enter).
els.form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const text = els.input.value.trim();
  const files = Array.from(els.file.files);
  if (!text && files.length === 0) return;

  const attachments = await filesToAttachments(files);
  // Create a new message object with all the necessary info.
  const msg = {
    id: crypto.randomUUID(),
    user: currentUser,
    text,
    attachments,
    createdAt: nowISO(),
  };

  // Add the new message to our list and save it.
  const list = loadMessages();
  list.push(msg);
  saveMessages(list);

  // Reset the form, clear the previews, and re-render the chat.
  els.input.value = '';
  els.file.value = '';
  els.previews.innerHTML = '';
  renderAll();

  // Schedule a fake reply from a bot.
  scheduleBotReply();
});

// This is a key part of the "real-time" update feature.
// It listens for changes to `localStorage` that happen in other tabs.
// If the chat data changes, it re-renders the chat in this tab.
window.addEventListener('storage', (e) => {
  if (e.key === LS_KEY) {
    renderAll();
  }
});

// --- INITIALIZATION ---
// When the page first loads, run these functions.
seedDemoIfEmpty();
renderAll();
