<?php
// Include the central database connection
require_once __DIR__ . '/config.php';

// Check if the connection is established
if (!$conn) {
    // You can output a JSON error or a more user-friendly message
    die("Database connection failed.");
}

// Product class
class Product {
    private $conn;
    private $table_name = "products";

    public function __construct($mysqli_conn) {
        $this->conn = $mysqli_conn;
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
            if (!empty($filters['category']) && $filters['category'] !== 'all') {
                $conditions[] = "c.slug = ?";
                $params[] = $filters['category'];
            }

            // Price range filter
            if (!empty($filters['min_price'])) {
                $conditions[] = "p.price >= ?";
                $params[] = $filters['min_price'];
            }
            if (!empty($filters['max_price'])) {
                $conditions[] = "p.price <= ?";
                $params[] = $filters['max_price'];
            }

            // Search filter
            if (!empty($filters['search'])) {
                $conditions[] = "(p.title LIKE ? OR p.description LIKE ?)";
                $search_term = '%' . $filters['search'] . '%';
                $params[] = $search_term;
                $params[] = $search_term;
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
            if (!empty($params)) {
                // Dynamically bind parameters
                $types = str_repeat('s', count($params)); // Assuming all are strings for simplicity
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $products = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

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
                        c.slug as category_slug,
                        COUNT(p.id) as product_count
                    FROM categories c
                    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
                    GROUP BY c.id, c.slug";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();
            $counts = [];
            while ($row = $result->fetch_assoc()) {
                $counts[$row['category_slug']] = $row['product_count'];
            }
            $stmt->close();

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
if ($conn) {
    $product = new Product($conn);
    $product->handleRequest();
}

include 'HTML/marketplace.html';
