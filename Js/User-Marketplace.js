/**
 * Manages the User Marketplace page.
 * - Fetches products from the API.
 * - Renders product cards.
 * - Handles filtering by category and search.
 */

const els = {
    grid: document.getElementById('products-grid'),
    emptyState: document.getElementById('empty-state'),
    searchInput: document.getElementById('search-input'),
    categoryList: document.getElementById('category-list'),
};

let allProducts = []; // Cache for all fetched products
let currentCategory = '';
let currentSearch = '';

/**
 * Fetches all active products from the API on initial load.
 */
async function fetchProducts() {
    try {
        const response = await fetch('../php/api/products.php');
        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();
        allProducts = data.products || [];
        return allProducts;
    } catch (error) {
        console.error('Failed to fetch products:', error);
        if (els.grid) els.grid.innerHTML = '<p class="error-message">Could not load products.</p>';
        return [];
    }
}

/**
 * Renders products into the grid based on current filters.
 */
function renderProducts(products = allProducts) {
    if (!els.grid) return;

    const filteredProducts = products.filter(product => {
        const matchesCategory = !currentCategory || product.category_slug === currentCategory;
        const matchesSearch = !currentSearch || product.title.toLowerCase().includes(currentSearch);
        return matchesCategory && matchesSearch;
    });

    els.grid.innerHTML = ''; // Clear previous results

    if (filteredProducts.length === 0) {
        els.emptyState.hidden = false;
    } else {
        els.emptyState.hidden = true;
        filteredProducts.forEach(product => {
            const card = createProductCard(product);
            els.grid.appendChild(card);
        });
    }
}

/**
 * Creates the HTML element for a single product card, wrapped in a link.
 * @param {object} product - The product data.
 * @returns {HTMLElement} - The anchor element wrapping the card.
 */
function createProductCard(product) {
    const cardLink = document.createElement('a');
    cardLink.href = `product-detail.php?id=${product.id}`;
    cardLink.className = 'product-card';

    const imageUrl = product.image_url ? `../${product.image_url}` : 'https://placehold.co/400x250?text=No+Image';

    cardLink.innerHTML = `
        <div class="product-media">
            <img src="${imageUrl}" alt="${product.title}" loading="lazy">
        </div>
        <div class="product-content">
            <h4 class="product-title">${product.title}</h4>
            <div class="product-price">
                ETB ${parseFloat(product.price).toFixed(2)}
            </div>
        </div>
    `;
    return cardLink;
}

/**
 * Filters and re-renders the product grid based on current UI controls.
 */
function updateGrid() {
    currentSearch = els.searchInput?.value.toLowerCase() || '';
    const selectedCategorySlug = els.categoryList?.querySelector('.category-list li.selected')?.dataset.slug || '';
    currentCategory = selectedCategorySlug;

    renderProducts();
}

// --- Initialize Page ---
document.addEventListener('DOMContentLoaded', () => {
    // Only run this script on the marketplace page
    if (!els.grid) {
        return;
    }

    fetchProducts().then(() => {
        updateGrid(); // Initial render

        // Safely add event listeners
        els.searchInput?.addEventListener('input', updateGrid);
        els.categoryList?.addEventListener('click', (e) => {
            const li = e.target.closest('li');
            if (!li) return;
            els.categoryList.querySelectorAll('li').forEach(item => item.classList.remove('selected'));
            li.classList.add('selected');
            updateGrid();
        });
    });
});