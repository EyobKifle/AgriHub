<?php
// Database configuration
class Database {
    private $host = "localhost";
    private $db_name = "agrihub";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
        }
        return $this->conn;
    }
}

// Product class
class Product {
    private $conn;
    private $table_name = "products";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Handle API requests
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
            header('Content-Type: application/json');
            
            if ($_GET['action'] === 'get_products') {
                echo $this->getProducts();
            } elseif ($_GET['action'] === 'get_categories') {
                echo $this->getCategoryCounts();
            }
            exit;
        }
    }

    // Get products with filters
    private function getProducts() {
        $filters = [
            'category' => $_GET['category'] ?? 'all',
            'min_price' => $_GET['min_price'] ?? '',
            'max_price' => $_GET['max_price'] ?? '',
            'search' => $_GET['search'] ?? '',
            'sort' => $_GET['sort'] ?? 'latest'
        ];

        try {
            $query = "SELECT 
                        p.*, 
                        c.name as category_name,
                        u.name as seller_name,
                        pi.image_url
                    FROM " . $this->table_name . " p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN users u ON p.seller_id = u.id
                    LEFT JOIN (
                        SELECT product_id, MIN(image_url) AS image_url
                        FROM product_images
                        GROUP BY product_id
                    ) pi ON p.id = pi.product_id
                    WHERE p.status = 'active'";

            $conditions = [];
            $params = [];

            // Category filter
            if (!empty($filters['category']) && $filters['category'] != 'all') {
                $conditions[] = "LOWER(c.name) = :category";
                $params[':category'] = strtolower($filters['category']);
            }

            // Price range filter
            if (!empty($filters['min_price'])) {
                $conditions[] = "p.price >= :min_price";
                $params[':min_price'] = $filters['min_price'];
            }
            if (!empty($filters['max_price'])) {
                $conditions[] = "p.price <= :max_price";
                $params[':max_price'] = $filters['max_price'];
            }

            // Search filter
            if (!empty($filters['search'])) {
                $conditions[] = "(p.title LIKE :search OR p.description LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }

            // Add conditions to query
            if (!empty($conditions)) {
                $query .= " AND " . implode(" AND ", $conditions);
            }

            
            // Sorting
            $sort_options = [
                'latest' => 'p.created_at DESC',
                'price-asc' => 'p.price ASC',
                'price-desc' => 'p.price DESC'
            ];
            $sort = $filters['sort'];
            $query .= " ORDER BY " . ($sort_options[$sort] ?? 'p.created_at DESC');

            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return json_encode([
                'success' => true,
                'products' => $products,
                'total_products' => count($products)
            ]);

        } catch (Exception $e) {
            return json_encode([
                'success' => false,
                'message' => 'Error loading products'
            ]);
        }
    }

    // Get category counts
    private function getCategoryCounts() {
        try {
            $query = "SELECT 
                        c.name as category_name,
                        COUNT(p.id) as product_count
                    FROM categories c
                    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
                    GROUP BY c.id, c.name";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            $counts = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $counts[strtolower($row['category_name'])] = $row['product_count'];
            }

            return json_encode([
                'success' => true,
                'category_counts' => $counts
            ]);

        } catch (Exception $e) {
            return json_encode([
                'success' => false,
                'message' => 'Error loading categories'
            ]);
        }
    }
}

// Initialize database and handle API requests
$database = new Database();
$db = $database->getConnection();

if ($db) {
    $product = new Product($db);
    $product->handleRequest();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Marketplace - AgriHub</title>
  <link rel="stylesheet" href="Css/marketplace.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <!-- Header Placeholder -->
  <div id="header-placeholder"></div>

  <main>
    <div class="container">
      <header class="page-header">
        <h1>Marketplace</h1>
        <p>Buy and sell agricultural products or rent farming equipment from trusted vendors.</p>
      </header>

      <div class="marketplace-layout">
        <!-- Sidebar: Filters -->
        <aside class="marketplace-sidebar">
          <div class="sidebar-section">
            <a href="#" class="btn btn-primary full-width">+ List a Product</a>
          </div>

          <div class="sidebar-section">
            <h3>Categories</h3>
            <ul class="category-list" id="category-list">
              <li data-category="all" class="active">All Products <span class="count" data-cat="all">(0)</span></li>
              <li data-category="grains">Grains <span class="count" data-cat="grains">(0)</span></li>
              <li data-category="coffee">Coffee <span class="count" data-cat="coffee">(0)</span></li>
              <li data-category="vegetables">Vegetables <span class="count" data-cat="vegetables">(0)</span></li>
              <li data-category="equipment">Equipment <span class="count" data-cat="equipment">(0)</span></li>
              <li data-category="fertilizers">Fertilizers <span class="count" data-cat="fertilizers">(0)</span></li>
              <li data-category="seeds">Seeds <span class="count" data-cat="seeds">(0)</span></li>
            </ul>
          </div>

          <div class="sidebar-section">
            <h3>Price Range (ETB)</h3>
            <div class="price-range">
              <input id="price-min" type="number" placeholder="Min" min="0" />
              <input id="price-max" type="number" placeholder="Max" min="0" />
            </div>
            <button id="price-apply" class="btn btn-secondary full-width">Apply Filter</button>
          </div>
        </aside>

        <!-- Main Content -->
        <section class="main-content">
          <div class="search-bar">
            <input id="search-input" type="text" placeholder="Search products..." />
            <select id="sort-select" aria-label="Sort products">
              <option value="latest">Sort by: Latest</option>
              <option value="price-asc">Sort by: Price Low to High</option>
              <option value="price-desc">Sort by: Price High to Low</option>
            </select>
          </div>

          <div id="products-grid" class="products-grid" aria-live="polite">
            <!-- Products will be loaded here by JavaScript -->
          </div>
          <div id="empty-state" class="empty-state" hidden>
            <i class="fas fa-frown"></i>
            <p>No products match your filters. Try adjusting your search or category.</p>
          </div>
        </section>
      </div>
    </div>
  </main>

  <!-- Footer Placeholder -->
  <div id="footer-placeholder"></div>

  <!-- Marketplace JavaScript -->
  <script>
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
              <h3 class="product-title">${this.escapeHtml(product.title)}</h3>
              <div class="product-meta">
                <span class="product-category">${this.escapeHtml(product.category_name)}</span>
                <span class="product-price">${this.formatPrice(product.price)} ETB/${product.unit}</span>
                <span class="product-seller">By ${this.escapeHtml(product.seller_name)}</span>
                <span class="product-stock">${product.quantity_available} ${product.unit} available</span>
              </div>
              <div class="product-actions">
                <button class="btn btn-primary" onclick="marketplace.viewProduct(${product.id})">
                  View Details
                </button>
              </div>
            </div>
          </div>
        `).join('');
      }

      updateCategoryList(categoryCounts) {
        const categoryList = document.getElementById('category-list');
        const categories = [
          { id: 'all', name: 'All Products' },
          { id: 'grains', name: 'Grains' },
          { id: 'coffee', name: 'Coffee' },
          { id: 'vegetables', name: 'Vegetables' },
          { id: 'equipment', name: 'Equipment' },
          { id: 'fertilizers', name: 'Fertilizers' },
          { id: 'seeds', name: 'Seeds' }
        ];

        categoryList.innerHTML = categories.map(cat => {
          const count = categoryCounts[cat.id] || 0;
          const isActive = this.currentFilters.category === cat.id;
          return `
            <li data-category="${cat.id}" class="${isActive ? 'active' : ''}">
              ${cat.name} 
              <span class="count" data-cat="${cat.id}">(${count})</span>
            </li>
          `;
        }).join('');

        // Add click event listeners to categories
        categoryList.querySelectorAll('li').forEach(li => {
          li.addEventListener('click', () => {
            this.setCategoryFilter(li.dataset.category);
          });
        });
      }

      setupEventListeners() {
        // Search input
        const searchInput = document.getElementById('search-input');
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
          clearTimeout(searchTimeout);
          searchTimeout = setTimeout(() => {
            this.currentFilters.search = e.target.value;
            this.loadProducts();
          }, 500);
        });

        // Sort select
        const sortSelect = document.getElementById('sort-select');
        sortSelect.addEventListener('change', (e) => {
          this.currentFilters.sort = e.target.value;
          this.loadProducts();
        });

        // Price filter
        const priceApply = document.getElementById('price-apply');
        priceApply.addEventListener('click', () => {
          const minPrice = document.getElementById('price-min').value;
          const maxPrice = document.getElementById('price-max').value;
          this.currentFilters.min_price = minPrice;
          this.currentFilters.max_price = maxPrice;
          this.loadProducts();
        });

        // Reset price filters when inputs are cleared
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
        
        // Update active state in UI
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
        // For now, just show an alert. You can implement a modal or separate page later.
        alert(`Viewing product ${productId}. Implement product details view here.`);
        // window.location.href = `product-details.php?id=${productId}`;
      }

      formatPrice(price) {
        return new Intl.NumberFormat('en-ET').format(price);
      }

      escapeHtml(unsafe) {
        return unsafe
          .replace(/&/g, "&amp;")
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;")
          .replace(/"/g, "&quot;")
          .replace(/'/g, "&#039;");
      }
    }

    // Initialize marketplace when DOM is loaded
    document.addEventListener('DOMContentLoaded', () => {
      window.marketplace = new Marketplace();
    });
  </script>
  <script>window.MARKETPLACE_API = true;</script>
  <script type="module" src="Js/site.js"></script>
</body>
</html>