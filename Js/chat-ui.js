// A handy object to store references to the HTML elements we'll be working with.
export const els = {
  messages: document.getElementById('chat-messages'),
  form: document.getElementById('chat-form'),
  input: document.getElementById('message-input'),
  file: document.getElementById('file-upload'),
  previews: document.getElementById('file-previews'),
};
els.form.dataset.mode = 'new'; // Add a mode to the form for editing

/**
 * A helper function to format an ISO date string into a simple time like "12:30 PM".
 */
function fmtTime(iso) {
  const d = new Date(iso);
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

/**
 * A simple function to automatically scroll the chat window to the bottom
 * so the latest messages are always visible.
 */
export function scrollToBottom() {
  els.messages.scrollTop = els.messages.scrollHeight;
}

/**
 * Creates a small preview element for a file that is about to be uploaded.
 */
export function createAttachmentPreview(file, onRemove) {
  const wrap = document.createElement('span');
  wrap.className = 'preview';
  const name = document.createElement('span');
  name.className = 'name';
  name.textContent = file.name;

  const remove = document.createElement('button');
  remove.className = 'remove';
  remove.type = 'button';
  remove.textContent = '✕';
  remove.addEventListener('click', () => {
    onRemove(file);
    wrap.remove();
  });

  // If the file is an image, create a small thumbnail using the more performant URL.createObjectURL.
  if (file.type.startsWith('image/')) {
    const img = document.createElement('img');
    img.alt = file.name;
    img.src = URL.createObjectURL(file);
    img.onload = () => URL.revokeObjectURL(img.src); // Revoke object URL after image loads to free memory
    wrap.appendChild(img);
  } else { // Otherwise, show a generic icon (e.g., for a PDF).
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
function renderMessage(m, currentUser) {
  const isMe = m.user.id === currentUser.id;
  const row = document.createElement('div');
    row.className = `message ${isMe ? 'sent' : 'received'}`;
  row.setAttribute('data-message-id', m.id);

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
  // Use createTextNode for security to prevent XSS
  text.appendChild(document.createTextNode(m.text || ''));

  // Assemble the bubble.
  bubble.appendChild(meta);
  if (m.text) bubble.appendChild(text);

  // Add Edit/Delete buttons for the current user's messages
  if (isMe) {
    const actions = document.createElement('div');
    actions.className = 'message-actions';
    actions.innerHTML = `
      <button class="btn-action edit-btn" data-message-id="${m.id}" title="Edit">
        <i class="fas fa-pen"></i>
      </button>
      <button class="btn-action delete-btn" data-message-id="${m.id}" title="Delete">
        <i class="fas fa-trash"></i>
      </button>`;
    bubble.appendChild(actions);
  }

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
 * Toggles the UI into or out of "edit mode" for a specific message.
 * @param {string|null} messageId - The ID of the message to edit, or null to exit edit mode.
 */
export function toggleEditState(messageId) {
  // Reset any previously editing message
  document.querySelectorAll('.message.editing').forEach(el => el.classList.remove('editing'));

  if (messageId) {
    const messageNode = document.querySelector(`.message[data-message-id="${messageId}"]`);
    if (messageNode) {
      messageNode.classList.add('editing');
      const textContent = messageNode.querySelector('.text').textContent;
      els.input.value = textContent;
      els.form.dataset.mode = 'edit';
      els.form.dataset.editingId = messageId;
      els.input.focus();
    }
  } else {
    els.input.value = '';
    els.form.dataset.mode = 'new';
    delete els.form.dataset.editingId;
  }
}

/**
 * The main rendering function. It clears the chat window,
 * loads all messages, and displays them one by one.
 */
export function renderAll(messages, currentUser) {
  els.messages.innerHTML = '';
  const frag = document.createDocumentFragment();
  messages.forEach(m => frag.appendChild(renderMessage(m, currentUser)));
  els.messages.appendChild(frag);
  scrollToBottom();
}
