/**
 * Manages the "My Discussions" page, primarily handling the form visibility.
 */

const els = {
    // Page controls
    createBtn: document.getElementById('create-discussion-btn'),
    statusMessage: document.getElementById('form-status-message'),
    // Form elements
    createCard: document.getElementById('create-discussion-card'),
    form: document.getElementById('create-discussion-form'),
    categorySelect: document.getElementById('category_id'),
    // Table
    tableBody: document.getElementById('discussions-table-body'),
};
function resetAndHideForm() {
    els.form.reset();
    els.createCard.style.display = 'none';
}

function showStatus(message, isError = false) {
    els.statusMessage.textContent = message;
    els.statusMessage.style.color = isError ? '#900' : '#090';
    setTimeout(() => { els.statusMessage.textContent = ''; }, 4000);
}

async function handleDelete(discussionId, rowElement) {
    if (!confirm('Are you sure you want to permanently delete this discussion and all its comments?')) {
        return;
    }

    try {
        const response = await fetch('../php/api/discussions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: discussionId })
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error || 'An unknown error occurred.');
        }

        // Animate and remove the row on success
        rowElement.style.transition = 'opacity 0.5s';
        rowElement.style.opacity = '0';
        setTimeout(() => rowElement.remove(), 500);
        showStatus('Discussion deleted successfully.');

    } catch (error) {
        showStatus(`Error: ${error.message}`, true);
    }
}

function initializeEventListeners() {
    els.createBtn.addEventListener('click', () => {
        resetAndHideForm();
        els.createCard.style.display = 'block';
        els.createCard.scrollIntoView({ behavior: 'smooth' });
    });

    els.tableBody.addEventListener('click', (e) => {
        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            const row = deleteBtn.closest('tr');
            const discussionId = row.dataset.id;
            handleDelete(discussionId, row);
        }
    });
}

function init() {
    if (!els.tableBody) return; // Make sure we are on the correct page
    initializeEventListeners();
}

init();