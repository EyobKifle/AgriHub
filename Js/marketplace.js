// Marketplace interactions: vanilla JS only
// - Dynamic rendering
// - Category filter, search, sort, price range

const state = {
  products: [
    {
      id: 1,
      title: 'Fresh Organic Teff - Premium Grade',
      category: 'grains',
      price: 850, // ETB per kg
      rating: 4.8,
      reviews: 24,
      location: 'Addis Ababa, Ethiopia',
      featured: true,
      sale: true,
      image: '../images/1.jpg',
      createdAt: '2025-09-10'
    },
    {
      id: 2,
      title: 'Irrigation System - Drip Kit',
      category: 'equipment',
      price: 3800,
      rating: 4.8,
      reviews: 15,
      location: 'Dire Dawa, Ethiopia',
      featured: true,
      sale: false,
      image: '../images/2.jpg',
      createdAt: '2025-09-12'
    },
    {
      id: 3,
      title: 'Arabica Coffee Beans - Washed',
      category: 'coffee',
      price: 1200,
      rating: 4.6,
      reviews: 58,
      location: 'Jimma, Ethiopia',
      featured: false,
      sale: false,
      image: '../images/3.jpg',
      createdAt: '2025-09-05'
    },
    {
      id: 4,
      title: 'Tomatoes - Greenhouse Fresh',
      category: 'vegetables',
      price: 95,
      rating: 4.2,
      reviews: 31,
      location: 'Bahir Dar, Ethiopia',
      featured: false,
      sale: true,
      image: '../images/4.jpg',
      createdAt: '2025-09-11'
    },
    {
      id: 5,
      title: 'DAP Fertilizer 50kg Bag',
      category: 'fertilizers',
      price: 2650,
      rating: 4.4,
      reviews: 12,
      location: 'Adama, Ethiopia',
      featured: false,
      sale: false,
      image: '../images/5.jpg',
      createdAt: '2025-09-08'
    },
    {
      id: 6,
      title: 'Hybrid Maize Seeds - 10kg',
      category: 'seeds',
      price: 780,
      rating: 4.1,
      reviews: 19,
      location: 'Hawassa, Ethiopia',
      featured: false,
      sale: false,
      image: 'https://placehold.co/600x450/1e4620/FFF?text=Maize+Seeds',
      createdAt: '2025-09-09'
    },
  ],
  ui: {
    category: 'all',
    search: '',
    priceMin: '',
    priceMax: '',
    sort: 'latest'
  }
};

const els = {
  grid: document.getElementById('products-grid'),
  empty: document.getElementById('empty-state'),
  categories: document.getElementById('category-list'),
  search: document.getElementById('search-input'),
  sort: document.getElementById('sort-select'),
  priceMin: document.getElementById('price-min'),
  priceMax: document.getElementById('price-max'),
  priceApply: document.getElementById('price-apply'),
};

function formatPrice(n) { return `ETB ${n.toLocaleString()}${n < 200 ? ' / kg' : ''}`; }

function createCard(p) {
  const card = document.createElement('article');
  card.className = 'product-card';
  card.setAttribute('data-category', p.category);
  card.innerHTML = `
    <div class="product-media">
      <div class="product-badges">
        ${p.featured ? '<span class="badge featured">Featured</span>' : ''}
        ${p.sale ? '<span class="badge sale">Sale</span>' : ''}
      </div>
      <img src="${p.image}" alt="${p.title}" loading="lazy" />
    </div>
    <div class="product-body">
      <h3 class="product-title">${p.title}</h3>
      <div class="product-meta">
        <span class="product-price">${formatPrice(p.price)}</span>
        <span class="product-rating">⭐ ${p.rating.toFixed(1)} (${p.reviews})</span>
        <span class="product-location">${p.location}</span>
        <a class="btn btn-primary" href="#" aria-label="View details for ${p.title}">View Details</a>
      </div>
    </div>
  `;
  return card;
}

function applyFilters(products) {
  const { category, search, priceMin, priceMax } = state.ui;
  const q = search.trim().toLowerCase();
  const min = priceMin !== '' ? Number(priceMin) : -Infinity;
  const max = priceMax !== '' ? Number(priceMax) : Infinity;
  return products.filter(p => {
    const inCat = category === 'all' || p.category === category;
    const inSearch = !q || p.title.toLowerCase().includes(q) || p.location.toLowerCase().includes(q);
    const inPrice = p.price >= min && p.price <= max;
    return inCat && inSearch && inPrice;
  });
}

function applySort(products) {
  const sort = state.ui.sort;
  const sorted = [...products];
  if (sort === 'price-asc') sorted.sort((a,b) => a.price - b.price);
  else if (sort === 'price-desc') sorted.sort((a,b) => b.price - a.price);
  else if (sort === 'latest') sorted.sort((a,b) => new Date(b.createdAt) - new Date(a.createdAt));
  return sorted;
}

function updateCategoryCounts() {
  const counts = { all: state.products.length };
  for (const p of state.products) {
    counts[p.category] = (counts[p.category] || 0) + 1;
  }
  document.querySelectorAll('.count').forEach(span => {
    const cat = span.getAttribute('data-cat');
    const c = counts[cat] || 0;
    span.textContent = `(${c})`;
  });
}

function render() {
  const filtered = applyFilters(state.products);
  const sorted = applySort(filtered);

  els.grid.innerHTML = '';
  if (sorted.length === 0) {
    els.empty.hidden = false;
    return;
  }
  els.empty.hidden = true;

  const frag = document.createDocumentFragment();
  sorted.forEach(p => frag.appendChild(createCard(p)));
  els.grid.appendChild(frag);
}

function setActiveCategory(targetLi) {
  els.categories.querySelectorAll('li').forEach(li => li.classList.remove('active'));
  targetLi.classList.add('active');
}

function initEvents() {
  // categories
  els.categories.addEventListener('click', (e) => {
    const li = e.target.closest('li[data-category]');
    if (!li) return;
    state.ui.category = li.getAttribute('data-category');
    setActiveCategory(li);
    render();
  });

  // search input debounced
  let t;
  els.search.addEventListener('input', () => {
    clearTimeout(t);
    t = setTimeout(() => {
      state.ui.search = els.search.value;
      render();
    }, 200);
  });

  // sort select
  els.sort.addEventListener('change', () => {
    state.ui.sort = els.sort.value;
    render();
  });

  // price apply
  els.priceApply.addEventListener('click', () => {
    state.ui.priceMin = els.priceMin.value;
    state.ui.priceMax = els.priceMax.value;
    render();
  });
}

function init() {
  updateCategoryCounts();
  render();
  initEvents();
}

document.addEventListener('DOMContentLoaded', init);
