// Vanilla JS chat with localStorage persistence, file previews, avatars, and realtime updates via storage events

const LS_KEY = 'agrihub_chat_messages_v1';
const currentUser = {
  id: 'me',
  name: 'You',
  avatar: 'https://placehold.co/80x80/1e4620/FFF?text=Y'
};

const demoUsers = [
  { id: 'u1', name: 'Alemayehu', avatar: 'https://placehold.co/80x80/2a7742/FFF?text=A' },
  { id: 'u2', name: 'Meron', avatar: 'https://placehold.co/80x80/d97706/FFF?text=M' },
  { id: 'u3', name: 'Kebede', avatar: 'https://placehold.co/80x80/0a6e2d/FFF?text=K' }
];

const els = {
  messages: document.getElementById('chat-messages'),
  form: document.getElementById('chat-form'),
  input: document.getElementById('message-input'),
  file: document.getElementById('file-upload'),
  previews: document.getElementById('file-previews'),
};

function nowISO() { return new Date().toISOString(); }
function fmtTime(iso) {
  const d = new Date(iso);
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function loadMessages() {
  try {
    return JSON.parse(localStorage.getItem(LS_KEY)) || [];
  } catch {
    return [];
  }
}

function saveMessages(list) {
  localStorage.setItem(LS_KEY, JSON.stringify(list));
}

function scrollToBottom() {
  els.messages.scrollTop = els.messages.scrollHeight;
}

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
  remove.addEventListener('click', () => {
    // Remove this file from selection by reconstructing FileList is complex; reset input and rebuild others
    const files = Array.from(els.file.files).filter(f => f !== file);
    const dt = new DataTransfer();
    files.forEach(f => dt.items.add(f));
    els.file.files = dt.files;
    wrap.remove();
  });

  if (file.type.startsWith('image/')) {
    const img = document.createElement('img');
    img.alt = file.name;
    const reader = new FileReader();
    reader.onload = e => { img.src = e.target.result; };
    reader.readAsDataURL(file);
    wrap.appendChild(img);
  } else {
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

function renderAll() {
  const list = loadMessages();
  els.messages.innerHTML = '';
  const frag = document.createDocumentFragment();
  list.forEach(m => frag.appendChild(renderMessage(m)));
  els.messages.appendChild(frag);
  scrollToBottom();
}

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

els.file.addEventListener('change', () => {
  els.previews.innerHTML = '';
  Array.from(els.file.files).forEach(f => {
    els.previews.appendChild(createAttachmentPreview(f));
  });
});

els.form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const text = els.input.value.trim();
  const files = Array.from(els.file.files);
  if (!text && files.length === 0) return;

  const attachments = await filesToAttachments(files);
  const msg = {
    id: crypto.randomUUID(),
    user: currentUser,
    text,
    attachments,
    createdAt: nowISO(),
  };

  const list = loadMessages();
  list.push(msg);
  saveMessages(list);

  els.input.value = '';
  els.file.value = '';
  els.previews.innerHTML = '';
  renderAll();
  scheduleBotReply(msg);
});

window.addEventListener('storage', (e) => {
  if (e.key === LS_KEY) {
    renderAll();
  }
});

seedDemoIfEmpty();
renderAll();
