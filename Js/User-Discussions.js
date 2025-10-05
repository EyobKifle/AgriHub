/**
 * Manages the "My Discussions" page.
 * This module is dynamically loaded by `dashboard.js`.
 * It initializes event listeners and fetches data for the user's discussions page.
 */

const els = {
    // User info
    userInitialAvatar: document.getElementById('user-initial-avatar'),
    userProfileName: document.getElementById('user-profile-name'),
    userProfileEmail: document.getElementById('user-profile-email'),
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

let allDiscussions = [];
let allCategories = [];

/**
 * Fetches all necessary data from the backend API.
 */
async function fetchData() {
    try {
        const response = await fetch('../php/api/discussions.php');
        if (!response.ok) {
            if (response.status === 401) window.location.href = '../HTML/Login.html';
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        allDiscussions = data.discussions || [];
        allCategories = data.categories || [];
        renderPage(data.user);
    } catch (error) {
        console.error('Error fetching page data:', error);
        els.tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; color: #900;">Failed to load data.</td></tr>`;
    }
}

/**
 * Populates the page with data from the server.
 */
function renderPage(user) {
    if (user) {
        if (els.userInitialAvatar) els.userInitialAvatar.textContent = user.initial;
        if (els.userProfileName) els.userProfileName.textContent = user.name;
        if (els.userProfileEmail) els.userProfileEmail.textContent = user.email;
    }

    let categoryOptions = '<option value="">Select category</option>';
    allCategories.forEach(cat => {
        categoryOptions += `<option value="${cat.id}">${escapeHtml(cat.name)}</option>`;
    });
    els.categorySelect.innerHTML = categoryOptions;

    let discussionRows = '';
    if (allDiscussions.length > 0) {
        allDiscussions.forEach(item => {
            discussionRows += `
                <tr data-id="${item.id}">
                    <td><a href="Discussion.php?id=${item.id}">${escapeHtml(item.title)}</a></td>
                    <td>${escapeHtml(item.category_name)}</td>
                    <td>${timeAgo(item.updated_at)}</td>
                    <td>${item.comment_count}</td>
                    <td class="action-buttons">
                        <button class="delete-btn" title="Delete Discussion"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>`;
        });
    } else {
        discussionRows = `<tr><td colspan="5" style="text-align:center; opacity:.8;">You have not started any discussions yet.</td></tr>`;
    }
    els.tableBody.innerHTML = discussionRows;
}

function resetAndHideForm() {
    els.form.reset();
    els.createCard.style.display = 'none';
}

async function handleFormSubmit(e) {
    e.preventDefault();
    const formData = new FormData(els.form);
    showStatus('Saving...', '#555');

    try {
        const response = await fetch('../php/api/discussions.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'An unknown error occurred.');
        
        showStatus(result.message, '#090');
        resetAndHideForm();
        fetchData(); // Refresh the data
    } catch (error) {
        showStatus(`Error: ${error.message}`, '#900');
    }
}

function showStatus(message, color) {
    els.statusMessage.textContent = message;
    els.statusMessage.style.color = color;
    setTimeout(() => { els.statusMessage.textContent = ''; }, 4000);
}

function escapeHtml(str) {
    if (typeof str !== 'string') return '';
    return str.replace(/[&<>"']/g, (match) => ({'&': '&amp;','<': '&lt;','>': '&gt;','"': '&quot;',"'": '&#039;'}[match]));
}

function timeAgo(dateString) {
    const date = new Date(dateString);
    const seconds = Math.floor((new Date() - date) / 1000);
    let interval = seconds / 31536000;
    if (interval > 1) return Math.floor(interval) + " years ago";
    interval = seconds / 2592000;
    if (interval > 1) return Math.floor(interval) + " months ago";
    interval = seconds / 86400;
    if (interval > 1) return Math.floor(interval) + " days ago";
    interval = seconds / 3600;
    if (interval > 1) return Math.floor(interval) + " hours ago";
    interval = seconds / 60;
    if (interval > 1) return Math.floor(interval) + " minutes ago";
    return Math.floor(seconds) + " seconds ago";
}

function initializeEventListeners() {
    els.createBtn.addEventListener('click', () => {
        resetAndHideForm();
        els.createCard.style.display = 'block';
        els.createCard.scrollIntoView({ behavior: 'smooth' });
    });
    
    els.form.addEventListener('submit', handleFormSubmit);
}

function init() {
    if (!els.tableBody) return; // Make sure we are on the correct page
    initializeEventListeners();
    fetchData();
}

init();