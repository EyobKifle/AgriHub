/**
 * Manages the "My Discussions" page.
 * Fetches user's discussions and categories from the server,
 * renders them on the page, and handles the creation of new discussions.
 */

const els = {
    userInitialAvatar: document.getElementById('user-initial-avatar'),
    userProfileName: document.getElementById('user-profile-name'),
    userProfileEmail: document.getElementById('user-profile-email'),
    createDiscussionBtn: document.getElementById('create-discussion-btn'),
    createDiscussionCard: document.getElementById('create-discussion-card'),
    createDiscussionForm: document.getElementById('create-discussion-form'),
    categorySelect: document.getElementById('category_id'),
    discussionsTableBody: document.getElementById('discussions-table-body'),
    formStatusMessage: document.getElementById('form-status-message'),
};

/**
 * Fetches all necessary data from the backend API.
 */
async function fetchData() {
    try {
        const response = await fetch('../php/api/discussions.php');
        if (!response.ok) {
            if (response.status === 401) {
                window.location.href = '../HTML/Login.html';
            }
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        renderPage(data);
    } catch (error) {
        console.error('Error fetching discussion data:', error);
        els.discussionsTableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; color: #900;">Failed to load data. Please try again later.</td></tr>`;
    }
}

/**
 * Populates the page with data from the server.
 * @param {object} data The data object from the API.
 */
function renderPage(data) {
    // Render user info
    if (data.user) {
        els.userInitialAvatar.textContent = data.user.initial;
        els.userProfileName.textContent = data.user.name;
        els.userProfileEmail.textContent = data.user.email;
    }

    // Render category dropdown
    let categoryOptions = '<option value="">Select category</option>';
    if (data.categories && data.categories.length > 0) {
        data.categories.forEach(cat => {
            categoryOptions += `<option value="${cat.id}">${escapeHtml(cat.name)}</option>`;
        });
    }
    els.categorySelect.innerHTML = categoryOptions;

    // Render discussions table
    let discussionRows = '';
    if (data.discussions && data.discussions.length > 0) {
        data.discussions.forEach(d => {
            discussionRows += `
                <tr>
                    <td>${escapeHtml(d.title)}</td>
                    <td>${escapeHtml(d.category_name)}</td>
                    <td>${escapeHtml(d.updated_at)}</td>
                    <td>${d.comment_count}</td>
                    <td class="action-buttons">
                        <a href="../php/discussion.php?id=${d.id}" title="View Discussion"><i class="fa-solid fa-eye"></i></a>
                    </td>
                </tr>
            `;
        });
    } else {
        discussionRows = `<tr><td colspan="5" style="text-align:center; opacity:.8;">You haven't started any discussions yet.</td></tr>`;
    }
    els.discussionsTableBody.innerHTML = discussionRows;
}

/**
 * Handles the submission of the "create discussion" form.
 * @param {Event} e The form submission event.
 */
async function handleCreateDiscussion(e) {
    e.preventDefault();
    const formData = new FormData(els.createDiscussionForm);
    els.formStatusMessage.textContent = 'Submitting...';
    els.formStatusMessage.style.color = '#555';

    try {
        const response = await fetch('../php/api/discussions.php', {
            method: 'POST',
            body: formData,
        });
        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.error || 'An unknown error occurred.');
        }
        els.formStatusMessage.textContent = 'Discussion created successfully!';
        els.formStatusMessage.style.color = '#090';
        els.createDiscussionForm.reset();
        fetchData(); // Refresh the list
    } catch (error) {
        els.formStatusMessage.textContent = `Error: ${error.message}`;
        els.formStatusMessage.style.color = '#900';
    }
}

function escapeHtml(str) {
    return str.replace(/[&<>"']/g, (match) => ({'&': '&amp;','<': '&lt;','>': '&gt;','"': '&quot;',"'": '&#039;'}[match]));
}

// --- Event Listeners ---
els.createDiscussionBtn.addEventListener('click', () => {
    const card = els.createDiscussionCard;
    card.style.display = card.style.display === 'none' ? 'block' : 'none';
});

els.createDiscussionForm.addEventListener('submit', handleCreateDiscussion);

// --- Initial Load ---
document.addEventListener('DOMContentLoaded', fetchData);