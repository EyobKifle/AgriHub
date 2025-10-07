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
    statusGroup: document.getElementById('status-form-group'),
    statusSelect: document.getElementById('form-status'),
    // Table
    tableBody: document.getElementById('listings-table-body'),
};

let allListings = [];
let allCategories = [];

/**
 * Populates the page with data from the server.
 */
function renderPage(user) {
    if (user) {
        els.userInitialAvatar.textContent = user.initial;
        els.userProfileName.textContent = user.name;
        els.userProfileEmail.textContent = user.email;
    }
    // The page is now rendered by PHP. We just need to fetch the data for the edit form.
    fetch('../php/listings.php').then(res => res.json()).then(data => {
        allListings = data.listings || [];
    }).catch(err => console.error("Could not fetch listings for editing.", err));
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
    els.statusSelect.value = listing.status;

    els.formTitle.textContent = 'Edit Listing';
    els.submitBtn.textContent = 'Save Changes';
    els.cancelBtn.style.display = 'inline-flex';
    els.statusGroup.style.display = 'block';
    els.createCard.style.display = 'block';
    els.createCard.scrollIntoView({ behavior: 'smooth' });
}

function resetAndHideForm() {
    els.form.reset();
    els.productIdInput.value = '0';
    els.formTitle.textContent = 'New Listing';
    els.submitBtn.textContent = 'Create';
    els.cancelBtn.style.display = 'none';
    els.statusGroup.style.display = 'none';
    els.createCard.style.display = 'none';
}

async function handleFormSubmit(e) {
    e.preventDefault();
    const formData = new FormData(els.form);
    showStatus('Saving...', '#555');

    try {
        const response = await fetch('../php/listings.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'An unknown error occurred.');
        
        showStatus(result.message, '#090');
        resetAndHideForm();
        window.location.reload(); // Easiest way to refresh the list
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
        const response = await fetch('../php/listings.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'An unknown error occurred.');

        showStatus(result.message, '#090');
        window.location.reload(); // Easiest way to refresh the list
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
    renderPage(); // This will now just fetch data for the edit form
}

document.addEventListener('DOMContentLoaded', init);
