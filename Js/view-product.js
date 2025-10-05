/**
 * Manages the product detail page.
 * Fetches product data from the API and renders it.
 * Also handles the image slider functionality.
 */

const els = {
    title: document.querySelector('title'),
    errorBox: document.getElementById('error-box'),
    productContent: document.getElementById('product-content'),
    sliderImages: document.getElementById('slider-images'),
    sliderPrev: document.getElementById('slider-prev'),
    sliderNext: document.getElementById('slider-next'),
    category: document.getElementById('product-category'),
    productTitle: document.getElementById('product-title'),
    price: document.getElementById('product-price'),
    stock: document.getElementById('product-stock'),
    description: document.getElementById('product-description'),
    sellerDetails: document.getElementById('seller-details'),
    addToCartBtn: document.getElementById('add-to-cart-btn'),
};

let slideIndex = 0;

function escapeHtml(str) {
    if (typeof str !== 'string' || !str) return '';
    return str.replace(/[&<>"']/g, (match) => ({'&': '&amp;','<': '&lt;','>': '&gt;','"': '&quot;',"'": '&#039;'}[match]));
}

function showSlide(index) {
    const slides = els.sliderImages.querySelectorAll('img');
    if (!slides.length) return;

    slides.forEach((slide, i) => {
        slide.style.display = (i === index) ? 'block' : 'none';
    });
}

function moveSlide(n) {
    const slides = els.sliderImages.querySelectorAll('img');
    slideIndex = (slideIndex + n + slides.length) % slides.length;
    showSlide(slideIndex);
}

function renderProduct(product) {
    els.title.textContent = `${product.title} - AgriHub`;
    els.category.textContent = product.category_name;
    els.productTitle.textContent = product.title;
    els.price.innerHTML = `${Number(product.price).toFixed(2)} ETB <span class="unit">/ ${escapeHtml(product.unit)}</span>`;
    els.stock.innerHTML = `<strong>${escapeHtml(product.quantity_available)} ${escapeHtml(product.unit)}</strong> available`;
    els.description.innerHTML = escapeHtml(product.description).replace(/\n/g, '<br>');

    const sellerName = product.business_name || product.seller_name;
    els.sellerDetails.innerHTML = `
        <strong>${escapeHtml(sellerName)}</strong><br>
        <i class="fa-solid fa-location-dot"></i> ${escapeHtml(product.seller_location)}
    `;

    // Render images
    if (product.images && product.images.length > 0) {
        els.sliderImages.innerHTML = product.images.map(img =>
            `<img src="../${escapeHtml(img)}" alt="${escapeHtml(product.title)}">`
        ).join('');
    } else {
        els.sliderImages.innerHTML = `<img src="../images/1.jpg" alt="Default product image">`;
    }

    // Setup slider
    if (product.images && product.images.length > 1) {
        els.sliderPrev.style.display = 'block';
        els.sliderNext.style.display = 'block';
        showSlide(slideIndex);
    }

    els.productContent.style.display = 'grid';
}

function showError(message) {
    els.errorBox.textContent = message;
    els.errorBox.style.display = 'block';
    els.productContent.style.display = 'none';
}

async function fetchProductData() {
    const params = new URLSearchParams(window.location.search);
    const productId = params.get('id');

    if (!productId) {
        showError('No product ID specified.');
        return;
    }

    try {
        const response = await fetch(`../php/view-product.php?id=${productId}`);
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Failed to load product.');
        renderProduct(data);
    } catch (error) {
        showError(error.message);
    }
}

els.sliderPrev.addEventListener('click', () => moveSlide(-1));
els.sliderNext.addEventListener('click', () => moveSlide(1));
els.addToCartBtn.addEventListener('click', () => alert('Add to cart functionality to be implemented.'));

document.addEventListener('DOMContentLoaded', fetchProductData);
