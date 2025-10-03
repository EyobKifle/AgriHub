/**
 * This file controls all the interactive features of the Marketplace page.
 * It manages the product data, handles filtering and sorting, and updates the display.
 */

// The `state` object holds all the data for our page.
const state = {
  products: [
    // This is our "database" of products. In a real app, this would come from a server.
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
  // This `ui` object keeps track of the user's current filter and sort selections.
  ui: {
    category: 'all',
    search: '',
    priceMin: '',
    priceMax: '',
    sort: 'latest'
  }
};

// The `els` object is a convenient place to store references to all the HTML elements we need to work with.
// This is more efficient than searching for them in the document every time.
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

/**
 * A helper function to format a number as an Ethiopian Birr (ETB) price string.
 * It also adds "/ kg" for smaller prices to provide more context.
 */
function formatPrice(n) {
  let priceString = `ETB ${n.toLocaleString()}`;
  if (n < 200) {
    priceString += ' / kg';
  }
  return priceString;
}

/**
 * Creates the HTML for a single product card.
 * It takes a product object and returns a complete HTML element.
 */
function createCard(p) {
  const card = document.createElement('article');
  card.className = 'product-card';
  card.setAttribute('data-category', p.category);

  // Start with the basic HTML structure.
  let cardHTML = `
    <div class="product-media">
      <div class="product-badges">`;

  // Add badges only if the product is featured or on sale.
  if (p.featured) {
    cardHTML += '<span class="badge featured">Featured</span>';
  }
  if (p.sale) {
    cardHTML += '<span class="badge sale">Sale</span>';
  }

  cardHTML += `</div>
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

  // Set the final HTML for the card.
  card.innerHTML = cardHTML;
  return card;
}

/**
 * Filters the full list of products based on the current UI state
 * (category, search term, and price range).
 */
function applyFilters(products) {
  const { category, search, priceMin, priceMax } = state.ui;
  const searchTerm = search.trim().toLowerCase();

  // Set default min and max prices. If the input is empty, we use very small/large numbers
  // to make sure all products are included by default.
  let minPrice = -Infinity;
  if (priceMin !== '') {
    minPrice = Number(priceMin);
  }
  let maxPrice = Infinity;
  if (priceMax !== '') {
    maxPrice = Number(priceMax);
  }

  return products.filter(p => {
    // A product is shown if it meets all the following conditions:

    // Condition 1: It's in the selected category (or if 'all' is selected).
    const isInCat = category === 'all' || p.category === category;
    // Condition 2: The search term is found in its title or location (or if search is empty).
    const isInSearch = !searchTerm || p.title.toLowerCase().includes(searchTerm) || p.location.toLowerCase().includes(searchTerm);
    // Condition 3: Its price is within the selected range.
    const isInPrice = p.price >= minPrice && p.price <= maxPrice;

    return isInCat && isInSearch && isInPrice;
  });
}

/**
 * Sorts a list of products based on the current sort selection in the UI.
 */
function applySort(products) {
  const sort = state.ui.sort;
  const sorted = [...products];
  // The .sort() method changes the array. We give it a function to tell it how to compare two items (a, b).
  // - If it returns a negative number, 'a' comes first.
  // - If it returns a positive number, 'b' comes first.
  if (sort === 'price-asc') {
    sorted.sort((a, b) => a.price - b.price); // Sorts from lowest price to highest.
  } else if (sort === 'price-desc') {
    sorted.sort((a, b) => b.price - a.price); // Sorts from highest price to lowest.
  } else if (sort === 'latest') {
    // To sort by date, we convert the date strings to actual Date objects and subtract them.
    sorted.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
  }
  return sorted;
}

/**
 * Calculates how many products are in each category and updates the
 * count displayed in the sidebar (e.g., "Grains (3)").
 */
function updateCategoryCounts() {
  const counts = { all: state.products.length };
  for (const p of state.products) {
    // If we haven't counted this category yet, start it at 0.
    if (!counts[p.category]) {
      counts[p.category] = 0;
    }
    counts[p.category]++; // Add one to the count for this category.
  }
  document.querySelectorAll('.count').forEach(span => {
    const cat = span.getAttribute('data-cat');
    // If a category has products, use its count. Otherwise, use 0.
    if (counts[cat]) {
      span.textContent = `(${counts[cat]})`;
    } else {
      span.textContent = `(0)`;
    }
  });
}

/**
 * The main rendering function. It takes the current state, applies filters and sorting,
 * and then updates the product grid on the page with the results.
 */
function render() {
  const filtered = applyFilters(state.products);
  const sorted = applySort(filtered);

  els.grid.innerHTML = '';
  // If no products match the filters, show the "empty state" message.
  if (sorted.length === 0) {
    els.empty.hidden = false;
    return;
  }
  els.empty.hidden = true;

  const frag = document.createDocumentFragment();
  // Create a card for each sorted product and add it to a document fragment.
  // Using a fragment is more efficient than adding each card to the DOM one by one.
  sorted.forEach(p => frag.appendChild(createCard(p)));
  els.grid.appendChild(frag);
}

/**
 * A helper function to visually update which category is currently active in the sidebar.
 */
function setActiveCategory(targetLi) {
  els.categories.querySelectorAll('li').forEach(li => li.classList.remove('active'));
  targetLi.classList.add('active');
}

/**
 * Sets up all the event listeners for the page's interactive elements.
 */
function initEvents() {
  // When a category in the sidebar is clicked...
  els.categories.addEventListener('click', (e) => {
    const li = e.target.closest('li[data-category]');
    if (!li) return;
    state.ui.category = li.getAttribute('data-category');
    setActiveCategory(li);
    render();
  });

  // When the user types in the search bar...
  let searchTimeout; // This variable will hold our timer.
  els.search.addEventListener('input', () => {
    // This is a "debounce" technique. It prevents the search from running on every single keystroke.
    // First, we clear any previous timer that was set.
    clearTimeout(searchTimeout);

    // Then, we set a new timer. The search will only run after the user has stopped typing for 200 milliseconds.
    // This is much more efficient and feels smoother.
    searchTimeout = setTimeout(() => {
      state.ui.search = els.search.value;
      render();
    }, 200); // 200 milliseconds = 0.2 seconds
  });

  // When the user changes the sort dropdown...
  els.sort.addEventListener('change', () => {
    state.ui.sort = els.sort.value;
    render();
  });

  // When the user clicks the "Apply Filter" button for price...
  els.priceApply.addEventListener('click', () => {
    state.ui.priceMin = els.priceMin.value;
    state.ui.priceMax = els.priceMax.value;
    render();
  });
}

/**
 * The main initialization function for the page.
 * It's called once the DOM is ready.
 */
export function initializeMarketplace() {
  // Check if we are on the marketplace page by looking for a key element.
  if (!els.grid) return;

  updateCategoryCounts();
  render();
  initEvents();
}
