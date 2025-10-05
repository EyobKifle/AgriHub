/**
 * Manages the "My Listings" page.
 * This module is dynamically loaded by `dashboard.js`.
 * It initializes event listeners and fetches data for the user listings page.
 * Fetches user's listings and categories, renders them, and handles CRUD operations.
 */

const els = {
    // User info
    userInitialAvatar: document.getElementById('user-initial-avatar'),
    userProfileName: document.getElementById('user-profile-name'),
    userProfileEmail: document.getElementById('user-profile-email'),
    // Page controls
    createBtn: document.getElementById('create-listing-btn'),
    statusMessage: document.getElementById('form-status-message'),
    // Form elements
    createCard: document.getElementById('create-listing-card'),
    form: document.getElementById('listing-form'),
    formTitle: document.getElementById('form-title'),
    submitBtn: document.getElementById('form-submit-btn'),
    cancelBtn: document.getElementById('cancel-edit-btn'),
    // Form inputs
    productIdInput: document.getElementById('product_id'),
    titleInput: document.getElementById('form-title-input'),
    descriptionInput: document.getElementById('form-description'),
    categorySelect: document.getElementById('form-category-id'),
    priceInput: document.getElementById('form-price'),
    unitInput: document.getElementById('form-unit'),
    quantityInput: document.getElementById('form-quantity'),
    // Table
    tableBody: document.getElementById('listings-table-body'),
};

let allListings = [];
let allCategories = [];

/**
 * Fetches all necessary data from the backend API.
 */
async function fetchData() {
    try {
        const response = await fetch('../php/User-Listings.php');
        if (!response.ok) {
            if (response.status === 401) window.location.href = '../HTML/Login.html';
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        allListings = data.listings || [];
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
        els.userInitialAvatar.textContent = user.initial;
        els.userProfileName.textContent = user.name;
        els.userProfileEmail.textContent = user.email;
    }

    let categoryOptions = '<option value="">Select category</option>';
    allCategories.forEach(cat => {
        categoryOptions += `<option value="${cat.id}">${escapeHtml(cat.name)}</option>`;
    });
    els.categorySelect.innerHTML = categoryOptions;

    let listingRows = '';
    if (allListings.length > 0) {
        allListings.forEach(item => {
            const priceFormatted = `${Number(item.price).toFixed(2)} / ${escapeHtml(item.unit)}`;
            listingRows += `
                <tr data-id="${item.id}">
                    <td>${escapeHtml(item.title)}</td>
                    <td>${escapeHtml(item.category_name)}</td>
                    <td>${priceFormatted}</td>
                    <td><span class="status status-${escapeHtml(item.status.toLowerCase())}">${escapeHtml(item.status)}</span></td>
                    <td class="action-buttons">
                        <button class="edit-btn" title="Edit Listing"><i class="fa-solid fa-pen"></i></button>
                        <button class="delete-btn" title="Delete Listing"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>`;
        });
    } else {
        listingRows = `<tr><td colspan="5" style="text-align:center; opacity:.8;">You have no listings yet.</td></tr>`;
    }
    els.tableBody.innerHTML = listingRows;
}

function showFormForEdit(listingId) {
    const listing = allListings.find(l => l.id == listingId);
    if (!listing) return;

    els.form.reset();
    els.productIdInput.value = listing.id;
    els.titleInput.value = listing.title;
    els.descriptionInput.value = listing.description;
    els.categorySelect.value = listing.category_id;
    els.priceInput.value = listing.price;
    els.unitInput.value = listing.unit;
    els.quantityInput.value = listing.quantity_available;

    els.formTitle.textContent = 'Edit Listing';
    els.cancelBtn.style.display = 'inline-flex';
    els.createCard.style.display = 'block';
    els.createCard.scrollIntoView({ behavior: 'smooth' });
}

function resetAndHideForm() {
    els.form.reset();
    els.productIdInput.value = '0';
    els.formTitle.textContent = 'New Listing';
    els.cancelBtn.style.display = 'none';
    els.createCard.style.display = 'none';
}

async function handleFormSubmit(e) {
    e.preventDefault();
    const formData = new FormData(els.form);
    showStatus('Saving...', '#555');

    try {
        const response = await fetch('../php/User-Listings.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'An unknown error occurred.');
        
        showStatus(result.message, '#090');
        resetAndHideForm();
        fetchData(); // Refresh the data
    } catch (error) {
        showStatus(`Error: ${error.message}`, '#900');
    }
}

async function handleDelete(listingId) {
    const listing = allListings.find(l => l.id == listingId);
    if (!listing || !confirm(`Are you sure you want to delete "${listing.title}"?`)) return;

    const formData = new FormData();
    formData.append('action', 'delete_listing');
    formData.append('product_id', listingId);
    showStatus('Deleting...', '#555');

    try {
        const response = await fetch('../php/User-Listings.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'An unknown error occurred.');

        showStatus(result.message, '#090');
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

function initializeEventListeners() {
    els.createBtn.addEventListener('click', () => {
        resetAndHideForm();
        els.createCard.style.display = 'block';
        els.createCard.scrollIntoView({ behavior: 'smooth' });
    });
    
    els.cancelBtn.addEventListener('click', resetAndHideForm);
    els.form.addEventListener('submit', handleFormSubmit);
    
    els.tableBody.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.edit-btn');
        const deleteBtn = e.target.closest('.delete-btn');
        const listingId = e.target.closest('tr')?.dataset.id;
    
        if (editBtn && listingId) {
            showFormForEdit(listingId);
        } else if (deleteBtn && listingId) {
            handleDelete(listingId);
        }
    });
}

function init() {
    if (!els.tableBody) return; // Make sure we are on the correct page
    
    initializeEventListeners();
    fetchData();
}

init();
