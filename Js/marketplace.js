import { escapeHtml } from './utils.js';

class Marketplace {
  constructor() {
    this.currentFilters = {
      category: 'all',
      min_price: '',
      max_price: '',
      search: '',
      sort: 'latest'
    };
    this.init();
  }

  init() {
    this.loadCategories();
    this.loadProducts();
    this.setupEventListeners();
  }

  async loadProducts() {
    try {
      this.showLoading();

      const params = new URLSearchParams();
      params.append('action', 'get_products');
      for (const [key, value] of Object.entries(this.currentFilters)) {
        if (value) params.append(key, value);
      }

      const response = await fetch(`marketplace.php?${params}`);
      const data = await response.json();

      if (data.success) {
        this.renderProducts(data.products);
        this.updateEmptyState(data.products.length === 0);
      } else {
        throw new Error(data.message || 'Failed to load products');
      }
    } catch (error) {
      console.error('Error loading products:', error);
      this.showError('Failed to load products. Please try again.');
    }
  }

  async loadCategories() {
    try {
      const response = await fetch('marketplace.php?action=get_categories');
      const data = await response.json();
      
      if (data.success) {
        this.updateCategoryList(data.category_counts);
      }
    } catch (error) {
      console.error('Error loading categories:', error);
    }
  }

  renderProducts(products) {
    const grid = document.getElementById('products-grid');
    
    if (products.length === 0) {
      grid.innerHTML = '';
      return;
    }

    grid.innerHTML = products.map(product => `
      <div class="product-card" data-product-id="${product.id}">
        <div class="product-media">
          <img src="${product.image_url || 'images/1.jpg'}" 
               alt="${product.title}" 
               onerror="this.src='images/1.jpg'">
          <div class="product-badges">
            ${product.featured ? '<span class="badge featured">Featured</span>' : ''}
            ${product.on_sale ? '<span class="badge sale">Sale</span>' : ''}
          </div>
        </div>
        <div class="product-body">
          <h3 class="product-title">${escapeHtml(product.title)}</h3>
          <div class="product-meta">
            <span class="product-category">${this.escapeHtml(product.category_name)}</span>
            <span class="product-price">${this.formatPrice(product.price)} ETB/${product.unit}</span>
            <span class="product-seller">By ${this.escapeHtml(product.seller_name)}</span>
            <span class="product-stock">${product.quantity_available} ${product.unit} available</span>
          </div>
          <div class="product-actions">
            <button class="btn btn-secondary" onclick="marketplace.viewProduct(${product.id})">Details</button>
            <button class="btn btn-primary" onclick="marketplace.addToCart(${product.id})">
              <i class="fas fa-shopping-cart"></i> Add to Cart</button>
          </div>
        </div>
      </div>
    `).join('');
  }

  updateCategoryList(categoryCounts) {
    const categoryList = document.getElementById('category-list');
    let totalProducts = Object.values(categoryCounts).reduce((sum, count) => sum + parseInt(count, 10), 0);
    let allProductsHTML = `<li data-category="all" class="${this.currentFilters.category === 'all' ? 'active' : ''}">All Products <span class="count" data-cat="all">(${totalProducts})</span></li>`;
    let categoriesHTML = Object.entries(categoryCounts).map(([slug, count]) => {
      const isActive = this.currentFilters.category === slug;
      const name = slug.charAt(0).toUpperCase() + slug.slice(1);
      return `
        <li data-category="${slug}" class="${isActive ? 'active' : ''}">
          ${name} 
          <span class="count" data-cat="${slug}">(${count})</span>
        </li>
      `;
    }).join('');
    categoryList.innerHTML = allProductsHTML + categoriesHTML;

    categoryList.querySelectorAll('li').forEach(li => {
      li.addEventListener('click', () => {
        this.setCategoryFilter(li.dataset.category);
      });
    });
  }

  setupEventListeners() {
    const searchInput = document.getElementById('search-input');
    let searchTimeout;
    searchInput.addEventListener('input', (e) => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        this.currentFilters.search = e.target.value;
        this.loadProducts();
      }, 500);
    });

    const sortSelect = document.getElementById('sort-select');
    sortSelect.addEventListener('change', (e) => {
      this.currentFilters.sort = e.target.value;
      this.loadProducts();
    });

    const priceApply = document.getElementById('price-apply');
    priceApply.addEventListener('click', () => {
      const minPrice = document.getElementById('price-min').value;
      const maxPrice = document.getElementById('price-max').value;
      this.currentFilters.min_price = minPrice;
      this.currentFilters.max_price = maxPrice;
      this.loadProducts();
    });

    document.getElementById('price-min').addEventListener('input', (e) => {
      if (!e.target.value) this.currentFilters.min_price = '';
    });
    document.getElementById('price-max').addEventListener('input', (e) => {
      if (!e.target.value) this.currentFilters.max_price = '';
    });
  }

  setCategoryFilter(category) {
    this.currentFilters.category = category;
    this.loadProducts();
    
    document.querySelectorAll('#category-list li').forEach(li => {
      li.classList.toggle('active', li.dataset.category === category);
    });
  }

  showLoading() {
    const grid = document.getElementById('products-grid');
    grid.innerHTML = `
      <div class="loading-state" style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; padding: 3rem; color: #666;">
        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
        <p>Loading products...</p>
      </div>
    `;
  }

  showError(message) {
    const grid = document.getElementById('products-grid');
    grid.innerHTML = `
      <div class="error-state" style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; padding: 3rem; color: #dc2626;">
        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
        <p>${message}</p>
        <button class="btn btn-primary" onclick="marketplace.loadProducts()" style="margin-top: 1rem;">Retry</button>
      </div>
    `;
  }

  updateEmptyState(isEmpty) {
    const emptyState = document.getElementById('empty-state');
    emptyState.hidden = !isEmpty;
  }

  viewProduct(productId) {
    window.location.href = `php/view-product.php?id=${productId}`;
  }

  addToCart(productId) {
    alert(`Adding product ${productId} to cart. Implement cart logic here.`);
  }

  formatPrice(price) {
    return new Intl.NumberFormat('en-ET').format(price);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  window.marketplace = new Marketplace();
});
