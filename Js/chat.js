/**
 * This file controls all the logic for the community chat feature.
 * It handles sending messages, displaying them, uploading files, and
 * keeping the chat updated across different browser tabs.
 */

// A unique key to save our chat messages in the browser's local storage.
const LS_KEY = 'agrihub_chat_messages_v1';

// An object representing the person using the chat.
const currentUser = {
  id: 'me',
  name: 'You',
  avatar: 'https://placehold.co/80x80/1e4620/FFF?text=Y'
};

// A list of fake users for demonstration purposes.
const demoUsers = [
  { id: 'u1', name: 'Alemayehu', avatar: 'https://placehold.co/80x80/2a7742/FFF?text=A' },
  { id: 'u2', name: 'Meron', avatar: 'https://placehold.co/80x80/d97706/FFF?text=M' },
  { id: 'u3', name: 'Kebede', avatar: 'https://placehold.co/80x80/0a6e2d/FFF?text=K' }
];

// A handy object to store references to the HTML elements we'll be working with.
// This is better than searching for them every time we need them.
const els = {
  messages: document.getElementById('chat-messages'),
  form: document.getElementById('chat-form'),
  input: document.getElementById('message-input'),
  file: document.getElementById('file-upload'),
  previews: document.getElementById('file-previews'),
};

/**
 * A helper function to get the current time as a standard string.
 * ISOString format looks like: "2024-01-01T12:30:00.000Z"
 */
function nowISO() { return new Date().toISOString(); }

/**
 * A helper function to format an ISO date string into a simple time like "12:30 PM".
 */
function fmtTime(iso) {
  const d = new Date(iso);
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

/**
 * Loads the saved chat messages from the browser's local storage.
 * It uses a try-catch block in case the saved data is corrupted.
 */
function loadMessages() {
  try {
    return JSON.parse(localStorage.getItem(LS_KEY)) || [];
  } catch {
    return [];
  }
}

/**
 * Saves the entire list of messages to the browser's local storage.
 */
function saveMessages(list) {
  localStorage.setItem(LS_KEY, JSON.stringify(list));
}

/**
 * A simple function to automatically scroll the chat window to the bottom
 * so the latest messages are always visible.
 */
function scrollToBottom() {
  els.messages.scrollTop = els.messages.scrollHeight;
}

/**
 * Creates a small preview element for a file that is about to be uploaded.
 */
function createAttachmentPreview(file) {
  const wrap = document.createElement('span');
  wrap.className = 'preview';
  const name = document.createElement('span');
  name.className = 'name';
  name.textContent = file.name;

  const remove = document.createElement('button');
  remove.className = 'remove';
  remove.type = 'button';
  remove.textContent = '✕';
  // When the remove button is clicked, it rebuilds the file list without the removed file.
  remove.addEventListener('click', () => {
    // Remove this file from selection by reconstructing FileList is complex; reset input and rebuild others
    const files = Array.from(els.file.files).filter(f => f !== file);
    const dt = new DataTransfer();
    files.forEach(f => dt.items.add(f));
    els.file.files = dt.files;
    wrap.remove();
  });

  // If the file is an image, create a small thumbnail.
  if (file.type.startsWith('image/')) {
    const img = document.createElement('img');
    img.alt = file.name;
    const reader = new FileReader();
    reader.onload = e => { img.src = e.target.result; };
    reader.readAsDataURL(file);
    wrap.appendChild(img);
  } else { // Otherwise, show a generic icon (e.g., for a PDF).
    // icon placeholder for pdf
    const img = document.createElement('img');
    img.src = 'https://placehold.co/44x44/1e4620/FFF?text=PDF';
    img.alt = 'PDF file';
    wrap.appendChild(img);
  }

  wrap.appendChild(name);
  wrap.appendChild(remove);
  return wrap;
}

/**
 * Creates the complete HTML for a single chat message bubble.
 * It handles messages sent by the current user differently from received messages.
 */
function renderMessage(m) {
  const isMe = m.user.id === currentUser.id;
  const row = document.createElement('div');
  row.className = 'message' + (isMe ? ' sent' : '');

  const avatar = document.createElement('div');
  avatar.className = 'avatar';
  const avImg = document.createElement('img');
  avImg.src = m.user.avatar; avImg.alt = `${m.user.name} avatar`;
  avatar.appendChild(avImg);

  const bubble = document.createElement('div');
  bubble.className = 'bubble';
  const meta = document.createElement('div');
  meta.className = 'meta';
  const name = document.createElement('span');
  name.className = 'name';
  name.textContent = m.user.name;
  const time = document.createElement('span');
  time.className = 'time';
  time.textContent = fmtTime(m.createdAt);
  meta.appendChild(name); meta.appendChild(time);

  const text = document.createElement('div');
  text.className = 'text';
  text.textContent = m.text || '';

  // Assemble the bubble.
  bubble.appendChild(meta);
  if (m.text) bubble.appendChild(text);

  if (m.attachments && m.attachments.length) {
    const grid = document.createElement('div');
    grid.className = 'attachments';
    m.attachments.forEach(att => {
      if (att.type === 'image') {
        const a = document.createElement('a');
        a.href = att.url; a.target = '_blank';
        a.className = 'attachment';
        const img = document.createElement('img');
        img.src = att.url; img.alt = att.name;
        a.appendChild(img);
        grid.appendChild(a);
      } else if (att.type === 'pdf') {
        const a = document.createElement('a');
        a.href = att.url; a.target = '_blank';
        a.className = 'attachment file';
        a.innerHTML = `<img src="https://placehold.co/44x44/1e4620/FFF?text=PDF" alt="PDF icon" /> <span>${att.name}</span>`;
        grid.appendChild(a);
      }
    });
    bubble.appendChild(grid);
  }

  row.appendChild(avatar);
  row.appendChild(bubble);
  return row;
}

/**
 * The main rendering function. It clears the chat window,
 * loads all messages, and displays them one by one.
 */
function renderAll() {
  const list = loadMessages();
  els.messages.innerHTML = '';
  const frag = document.createDocumentFragment();
  list.forEach(m => frag.appendChild(renderMessage(m)));
  els.messages.appendChild(frag);
  scrollToBottom();
}

/**
 * Converts a list of files from an input into a format that can be saved.
 * For images, it creates a "Data URL" which is a long string representing the image.
 */
async function filesToAttachments(files) {
  const atts = [];
  for (const f of files) {
    if (f.type.startsWith('image/')) {
      const dataUrl = await new Promise(res => { const r = new FileReader(); r.onload = e => res(e.target.result); r.readAsDataURL(f); });
      atts.push({ type: 'image', url: dataUrl, name: f.name });
    } else if (f.type === 'application/pdf') {
      // Store only name and a blob URL; DataURL can be huge, but we simulate persistence
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
function scheduleBotReply(latest) {
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
  Array.from(els.file.files).forEach(f => {
    els.previews.appendChild(createAttachmentPreview(f));
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
  scheduleBotReply(msg);
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
