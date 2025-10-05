/**
 * Manages the "My Messages" page.
 * Fetches user's conversations from the server, renders them,
 * and handles composing new messages.
 */

const els = {
    userInitialAvatar: document.getElementById('user-initial-avatar'),
    userProfileName: document.getElementById('user-profile-name'),
    userProfileEmail: document.getElementById('user-profile-email'),
    composeForm: document.getElementById('compose-form'),
    conversationsTableBody: document.getElementById('conversations-table-body'),
    statusMessage: document.getElementById('form-status-message'),
};

/**
 * Fetches all necessary data from the backend API.
 */
async function fetchData() {
    try {
        const response = await fetch('../php/User-Messages.php');
        if (!response.ok) {
            if (response.status === 401) window.location.href = '../HTML/Login.html';
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        renderPage(data);
    } catch (error) {
        console.error('Error fetching messages data:', error);
        els.conversationsTableBody.innerHTML = `<tr><td colspan="4" style="text-align:center; color: #900;">Failed to load conversations.</td></tr>`;
        showStatus('Failed to load conversations. Please try again.', '#900');
    }
}

/**
 * Populates the page with data from the server.
 */
function renderPage(data) {
    if (data.user) {
        els.userInitialAvatar.textContent = data.user.initial;
        els.userProfileName.textContent = data.user.name;
        els.userProfileEmail.textContent = data.user.email;
    }

    let convoRows = '';
    if (data.conversations && data.conversations.length > 0) {
        data.conversations.forEach(convo => {
            const isUnread = convo.unread_count > 0;
            convoRows += `
                <tr style="${isUnread ? 'font-weight:bold;' : ''}">
                    <td>
                        <a href="User-Messages.php?convo_id=${convo.id}">
                            ${escapeHtml(convo.subject)}
                            ${isUnread ? `<span class="status status-pending">${convo.unread_count}</span>` : ''}
                        </a>
                    </td>
                    <td>${escapeHtml(convo.other_participant_name)}</td>
                    <td style="opacity:.8; font-size:12px; max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        ${escapeHtml(convo.last_message)}
                    </td>
                    <td>${escapeHtml(convo.updated_at)}</td>
                </tr>
            `;
        });
    } else {
        convoRows = `<tr><td colspan="4" style="text-align:center; opacity:.8;">No conversations yet.</td></tr>`;
    }
    els.conversationsTableBody.innerHTML = convoRows;
}

/**
 * Handles the submission of the "compose message" form.
 */
async function handleComposeSubmit(e) {
    e.preventDefault();
    const formData = new FormData(els.composeForm);
    showStatus('Sending...', '#555');

    try {
        const response = await fetch('../php/User-Messages.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'An unknown error occurred.');

        showStatus(result.message, '#090');
        els.composeForm.reset();
        fetchData(); // Refresh the conversation list
    } catch (error) {
        showStatus(`Error: ${error.message}`, '#900');
    }
}

function showStatus(message, color) {
    els.statusMessage.textContent = message;
    els.statusMessage.style.color = color;
}

function escapeHtml(str) {
    if (typeof str !== 'string' || !str) return '';
    return str.replace(/[&<>"']/g, (match) => ({'&': '&amp;','<': '&lt;','>': '&gt;','"': '&quot;',"'": '&#039;'}[match]));
}

// --- Event Listeners ---
els.composeForm.addEventListener('submit', handleComposeSubmit);

// --- Initial Load ---
document.addEventListener('DOMContentLoaded', fetchData);
