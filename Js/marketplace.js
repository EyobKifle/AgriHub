import { escapeHtml } from './utils.js';

class Marketplace {
  constructor(container) {
    this.els = {
      grid: document.getElementById('products-grid'),
      categoryList: document.getElementById('category-list'),
      searchInput: document.getElementById('search-input'),
      priceMin: document.getElementById('price-min'),
      priceMax: document.getElementById('price-max'),
      priceApply: document.getElementById('price-apply'),
      sortSelect: document.getElementById('sort-select'),
      emptyState: document.getElementById('empty-state'),
      container: container,
    };
    this.filters = {
      category: 'all',
      min_price: '',
      max_price: '',
      search: '',
      sort: 'latest'
    };
    this.searchTimeout = null;
  }

  async loadProducts() {
    try {
      this.showLoading();

      const params = new URLSearchParams();
      for (const [key, value] of Object.entries(this.filters)) {
        if (value) params.append(key, value);
      }

      const response = await fetch(`../php/ProductApi.php?action=get_products&${params}`);
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
      const response = await fetch('../php/ProductApi.php?action=get_categories');
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
    this.hideLoading();
    
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
            <button class="btn btn-secondary details-btn" data-product-id="${product.id}">Details</button>
            <button class="btn btn-primary add-cart-btn" data-product-id="${product.id}">
              <i class="fas fa-shopping-cart"></i> Add to Cart</button>
          </div>
        </div>
      </div>
    `).join('');
  }

  updateCategoryList(categoryCounts) {
    const categoryList = document.getElementById('category-list');
    let totalProducts = Object.values(categoryCounts).reduce((sum, count) => sum + parseInt(count, 10), 0);
    let allProductsHTML = `<li data-category="all" class="${this.filters.category === 'all' ? 'active' : ''}">All Products <span class="count" data-cat="all">(${totalProducts})</span></li>`;
    let categoriesHTML = Object.entries(categoryCounts).map(([slug, count]) => {
      const isActive = this.filters.category === slug;
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
      this.searchTimeout = setTimeout(() => {
        this.filters.search = e.target.value;
        this.loadProducts();
      }, 500);
    });

    const sortSelect = document.getElementById('sort-select');
    sortSelect.addEventListener('change', (e) => {
      this.filters.sort = e.target.value;
      this.loadProducts();
    });

    const priceApply = document.getElementById('price-apply');
    priceApply.addEventListener('click', () => {
      this.filters.min_price = document.getElementById('price-min').value;
      this.filters.max_price = document.getElementById('price-max').value;
      this.loadProducts();
    });

    document.getElementById('price-min').addEventListener('input', (e) => {
      if (!e.target.value) this.filters.min_price = '';
    });
    document.getElementById('price-max').addEventListener('input', (e) => {
      if (!e.target.value) this.filters.max_price = '';
    });

    // Event Delegation for product actions
    this.els.grid.addEventListener('click', (e) => {
      const button = e.target.closest('button');
      if (!button) return;

      const productId = button.closest('.product-card')?.dataset.productId;
      if (!productId) return;

      if (button.classList.contains('details-btn')) {
        this.viewProduct(productId);
      } else if (button.classList.contains('add-cart-btn')) {
        this.addToCart(productId);
      }
    });
  }

  setCategoryFilter(category) {
    this.filters.category = category;
    this.loadProducts();
    
    document.querySelectorAll('#category-list li').forEach(li => {
      li.classList.toggle('active', li.dataset.category === category);
    });
  }
  
  showLoading() {
    this.els.container.classList.add('loading');
  }

  hideLoading() {
    this.els.container.classList.remove('loading');
  }

  showError(message) {
    const grid = document.getElementById('products-grid');
    grid.innerHTML = `
      <div class="error-state" style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; padding: 3rem; color: #dc2626;">
        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
        <p>${message}</p>
        <button class="btn btn-primary retry-btn" style="margin-top: 1rem;">Retry</button>
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

export const initializeMarketplace = () => {
  const marketplaceContainer = document.querySelector('.marketplace-layout');
  if (marketplaceContainer) {
    const marketplace = new Marketplace(marketplaceContainer);
    marketplace.loadCategories();
    marketplace.loadProducts();
    marketplace.setupEventListeners();
  }
};
