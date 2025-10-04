<<<<<<< HEAD
CREATE DATABASE `agrihub` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `agrihub`;

CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    `avatar_url` VARCHAR(512) DEFAULT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `location` VARCHAR(150) DEFAULT NULL,
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_location` (`location`)
);

CREATE TABLE `user_profiles` (
    `user_id` INT UNSIGNED NOT NULL PRIMARY KEY,
    `bio` TEXT,
    `farm_size_hectares` DECIMAL(10, 2) DEFAULT NULL,
    `specialization` VARCHAR(255) DEFAULT NULL,
    `experience_years` TINYINT UNSIGNED DEFAULT NULL,
    `language_preference` VARCHAR(10) NOT NULL DEFAULT 'en',
    `pref_email_notifications` BOOLEAN NOT NULL DEFAULT TRUE,
    `pref_theme` ENUM('light', 'dark') NOT NULL DEFAULT 'light',
    `business_name` VARCHAR(255) DEFAULT NULL,
    `business_address` TEXT,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_user_profiles_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);

CREATE TABLE `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `name_key` VARCHAR(100) NOT NULL UNIQUE,
    `slug` VARCHAR(120) NOT NULL UNIQUE,
    `description_key` VARCHAR(255) DEFAULT NULL,
    `product_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_categories_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
);

CREATE TABLE `products` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `seller_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `price` DECIMAL(12, 2) NOT NULL,
    `unit` VARCHAR(50) NOT NULL DEFAULT 'unit',
    `quantity_available` DECIMAL(10, 2) NOT NULL DEFAULT 0,
    `status` ENUM('active', 'inactive', 'sold_out', 'pending_approval') NOT NULL DEFAULT 'active',
    `is_featured` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_products_seller_id` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_products_category_id` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
    INDEX `idx_products_status` (`status`),
    INDEX `idx_products_price` (`price`)
);

CREATE TABLE `product_images` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT UNSIGNED NOT NULL,
    `image_url` VARCHAR(512) NOT NULL,
    `is_primary` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_product_images_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
);

CREATE TABLE `orders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `buyer_id` INT UNSIGNED NOT NULL,
    `order_code` VARCHAR(20) NOT NULL UNIQUE,
    `total_amount` DECIMAL(14, 2) NOT NULL,
    `status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    `delivery_address` TEXT,
    `notes` TEXT,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_orders_buyer_id` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_orders_status` (`status`)
);

CREATE TABLE `order_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `seller_id` INT UNSIGNED NOT NULL,
    `quantity` DECIMAL(10, 2) NOT NULL,
    `unit_price` DECIMAL(12, 2) NOT NULL,
    `line_total` DECIMAL(14, 2) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_order_items_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_order_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_order_items_seller_id` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
);

CREATE TABLE `reviews` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT UNSIGNED NOT NULL,
    `reviewer_id` INT UNSIGNED NOT NULL,
    `order_item_id` INT UNSIGNED DEFAULT NULL,
    `rating` TINYINT UNSIGNED NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `comment` TEXT,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_reviews_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reviews_reviewer_id` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reviews_order_item_id` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL,
    UNIQUE `uq_review_per_order_item` (`order_item_id`)
);

CREATE TABLE `discussion_categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `name_key` VARCHAR(100) NOT NULL UNIQUE,
    `slug` VARCHAR(120) NOT NULL UNIQUE,
    `description_key` VARCHAR(255) DEFAULT NULL,
    `discussion_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `display_order` SMALLINT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_discussion_categories_display_order` (`display_order`)
);

CREATE TABLE `discussions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `author_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `status` ENUM('published', 'draft', 'archived') NOT NULL DEFAULT 'published',
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `like_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `comment_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_discussions_author_id` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_discussions_category_id` FOREIGN KEY (`category_id`) REFERENCES `discussion_categories` (`id`) ON DELETE RESTRICT
);

CREATE TABLE `discussion_comments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `discussion_id` INT UNSIGNED NOT NULL,
    `author_id` INT UNSIGNED NOT NULL,
    `parent_comment_id` INT UNSIGNED DEFAULT NULL,
    `content` TEXT NOT NULL,
    `like_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_comments_discussion_id` FOREIGN KEY (`discussion_id`) REFERENCES `discussions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_comments_author_id` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_comments_parent_id` FOREIGN KEY (`parent_comment_id`) REFERENCES `discussion_comments` (`id`) ON DELETE CASCADE
);

CREATE TABLE `tags` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `use_count` INT UNSIGNED NOT NULL DEFAULT 0
);

CREATE TABLE `discussion_tags` (
    `discussion_id` INT UNSIGNED NOT NULL,
    `tag_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`discussion_id`, `tag_id`),
    CONSTRAINT `fk_discussion_tags_discussion_id` FOREIGN KEY (`discussion_id`) REFERENCES `discussions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_discussion_tags_tag_id` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
);

CREATE TABLE `content_categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `type` ENUM('news', 'guidance') NOT NULL,
    `name_key` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(120) NOT NULL,
    `description_key` VARCHAR(255) DEFAULT NULL,
    `article_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `display_order` SMALLINT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE `uq_content_cat_slug_type` (`slug`, `type`)
);

CREATE TABLE `articles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NOT NULL,
    `author_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `content` LONGTEXT NOT NULL,
    `excerpt` TEXT,
    `image_url` VARCHAR(512) DEFAULT NULL,
    `status` ENUM('published', 'draft', 'archived') NOT NULL DEFAULT 'draft',
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_articles_category_id` FOREIGN KEY (`category_id`) REFERENCES `content_categories` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_articles_author_id` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
);

CREATE TABLE `article_tags` (
    `article_id` INT UNSIGNED NOT NULL,
    `tag_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`article_id`, `tag_id`),
    CONSTRAINT `fk_article_tags_article_id` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_article_tags_tag_id` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
);

CREATE TABLE `article_media` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `article_id` INT UNSIGNED NOT NULL,
    `media_type` ENUM('image', 'video') NOT NULL,
    `url` VARCHAR(512) NOT NULL,
    `caption` VARCHAR(255) DEFAULT NULL,
    `display_order` SMALLINT NOT NULL DEFAULT 0,
    CONSTRAINT `fk_article_media_article_id` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE
);

CREATE TABLE `conversations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `subject` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `conversation_participants` (
    `conversation_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `last_read_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`conversation_id`, `user_id`),
    CONSTRAINT `fk_convo_participants_convo_id` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_convo_participants_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);

CREATE TABLE `messages` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT UNSIGNED NOT NULL,
    `sender_id` INT UNSIGNED NOT NULL,
    `body` TEXT,
    `attachment_url` VARCHAR(512) DEFAULT NULL,
    `attachment_type` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_messages_convo_id` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_messages_sender_id` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);

CREATE TABLE `reports` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reporter_id` INT UNSIGNED NOT NULL,
    `reported_item_type` ENUM('product', 'discussion', 'comment', 'user', 'other') NOT NULL,
    `reported_item_id` INT UNSIGNED NOT NULL,
    `reason` TEXT NOT NULL,
    `status` ENUM('open', 'in_review', 'resolved', 'dismissed') NOT NULL DEFAULT 'open',
    `resolved_by` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_reports_reporter_id` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reports_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_reports_status` (`status`)
);

CREATE TABLE `user_favorites` (
    `user_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `product_id`),
    CONSTRAINT `fk_favorites_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_favorites_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
);

CREATE TABLE `user_activity_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `activity_type` VARCHAR(100) NOT NULL,
    `related_item_id` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_activity_log_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);


-- Insert Marketplace Categories
INSERT INTO `categories` (`name`, `name_key`, `slug`) VALUES
('Grains', 'market.cat.grains', 'grains'),
('Coffee', 'market.cat.coffee', 'coffee'),
('Vegetables', 'market.cat.vegetables', 'vegetables'),
('Equipment', 'market.cat.equipment', 'equipment'),
('Fertilizers', 'market.cat.fertilizers', 'fertilizers'),
('Seeds', 'market.cat.seeds', 'seeds');

-- Insert Community Discussion Categories
INSERT INTO `discussion_categories` (`name`, `name_key`, `slug`, `display_order`) VALUES
('All Discussions', 'community.cat.all', 'all-discussions', 0),
('Crop Management', 'community.cat.crop', 'crop-management', 1),
('Livestock', 'community.cat.livestock', 'livestock', 2),
('Market Info', 'community.cat.market', 'market-info', 3),
('Pest Control', 'community.cat.pest', 'pest-control', 4);

-- Insert Content Categories (News & Guidance)
INSERT INTO `content_categories` (`type`, `name_key`, `slug`, `display_order`) VALUES
('news', 'news.cat.policy', 'policy', 1),
('news', 'news.cat.market', 'market-trends', 2),
('news', 'news.cat.research', 'research', 3),
('guidance', 'guidance.cat.teff', 'teff-guide', 1),
('guidance', 'guidance.cat.coffee', 'coffee-guide', 2);

-- Insert Sample Products
INSERT INTO `products` (`seller_id`, `category_id`, `title`, `description`, `price`, `unit`, `quantity_available`, `status`) VALUES
(2, 1, 'Fresh Organic Teff - Premium Grade', 'Premium grade teff sourced from local farmers in Gojjam. Perfect for high-quality injera.', 850.00, 'kg', 100, 'active'),
(2, 4, 'Irrigation System - Drip Kit', 'Efficient drip irrigation kit for small to medium farms. Covers up to 1 acre.', 3800.00, 'unit', 10, 'active'),
(2, 2, 'Arabica Coffee Beans - Washed', 'High-quality washed Arabica coffee beans from the Jimma region. Floral and citrus notes.', 1200.00, 'kg', 200, 'active');

-- Insert Sample Product Images
INSERT INTO `product_images` (`product_id`, `image_url`, `is_primary`) VALUES
(1, 'images/1.jpg', TRUE),
(2, 'images/2.jpg', TRUE),
(3, 'images/3.jpg', TRUE);

-- Insert Sample Discussion
INSERT INTO `discussions` (`author_id`, `category_id`, `title`, `content`) VALUES
(2, 2, 'Best practices for teff cultivation in highland areas?', 'I have been growing teff for 5 years and want to share some techniques that have improved my yields. What are your best tips for land preparation and fertilization?');

-- Insert Sample Comment
INSERT INTO `discussion_comments` (`discussion_id`, `author_id`, `content`) VALUES
(1, 3, 'Thanks for starting this discussion! For fertilization, I have found that using a mix of compost and a small amount of DAP at planting time works best.');

-- Insert System Settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('marketplace_fee_percent', '5', 'The percentage fee charged on each successful marketplace transaction.'),
('user_listing_limit', '10', 'The maximum number of active listings a regular user can have.');
=======
-- Central repository for all farming community data, transactions, and interactions
CREATE DATABASE agrihub;
USE agrihub;

-- ====================================================================
-- 1. USERS & AUTHENTICATION SYSTEM
--  Core user management for login, profiles, and role-based access
-- ====================================================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,          
    password_hash VARCHAR(255) NOT NULL,         --  Secure password
    name VARCHAR(100) NOT NULL,                  
    role VARCHAR(20) DEFAULT 'farmer',           -- (admin, seller, buyer, farmer)
    avatar_url VARCHAR(500),                     --  Profile picture 
    phone VARCHAR(20),                           
    location VARCHAR(100),                       --  For filtering
    is_active BOOLEAN DEFAULT TRUE,              --  For Soft delete - deactivate instead of deleting accounts
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    last_login TIMESTAMP NULL,                   --  Monitor active users and engagement
    last_activity TIMESTAMP NULL                 
);

--  Extended user profile for farming-specific information
CREATE TABLE user_profiles (
    user_id INT PRIMARY KEY,
    bio TEXT,                                    --  For introduction and farming background
    farm_size DECIMAL(10,2),                     
    specialization VARCHAR(100),                 --  Crop/livestock focus for community matching
    experience_years INT,                        
    language_preference VARCHAR(10) DEFAULT 'en',
    business_name VARCHAR(255),                  --  For sellers/business accounts
    business_address TEXT,                       
    FOREIGN KEY (user_id) REFERENCES users(id)
);

--  Session management for secure login persistence
CREATE TABLE sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,                        
    expires_at TIMESTAMP NOT NULL,               --  Auto-logout for security
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ====================================================================
-- 2. MARKETPLACE SYSTEM
--  E-commerce functionality for buying/selling farm products
-- ====================================================================

--  Organize products into categories for easy browsing
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,                  --  Category name (Grains, Coffee, Equipment)
    parent_id INT,                               --  Hierarchical categories (Grains → Teff, Maize)
    description TEXT,                            
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

--  Main product listings for the marketplace
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,                      
    title VARCHAR(200) NOT NULL,                 --  Product name
    description TEXT,                           
    category_id INT NOT NULL,                   
    price DECIMAL(12,2) NOT NULL,               
    unit VARCHAR(20) DEFAULT 'kg',               --  Sales unit (kg, quintal, bag, unit)
    quantity_available DECIMAL(10,2) DEFAULT 0,  --  Stock management
    status VARCHAR(20) DEFAULT 'active',         --  Control listing visibility
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

--  Multiple images per product for better presentation
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,             
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

--  Order management system for transactions
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,                       
    seller_id INT NOT NULL,                      
    product_id INT NOT NULL,                     
    quantity DECIMAL(10,2) NOT NULL,             
    unit_price DECIMAL(12,2) NOT NULL,           
    total_price DECIMAL(12,2) NOT NULL,          --  Calculated total for payment
    status VARCHAR(20) DEFAULT 'pending',        --  Track order progress
    delivery_address TEXT,                       --  Where to deliver products
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id),
    FOREIGN KEY (seller_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

--  Trust system through user reviews and ratings
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,                     
    reviewer_id INT NOT NULL,                    
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5), --  1-5 star rating system
    comment TEXT,                                --  Detailed feedback
    review_type VARCHAR(20) DEFAULT 'buyer',     --  Buyer reviews seller or vice versa
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (reviewer_id) REFERENCES users(id)
);



--  Wishlist functionality for users

-- CREATE TABLE favorites (
--     user_id INT NOT NULL,                        
--     product_id INT NOT NULL,                     
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     PRIMARY KEY (user_id, product_id),           
--     FOREIGN KEY (user_id) REFERENCES users(id),
--     FOREIGN KEY (product_id) REFERENCES products(id)
-- );




-- ====================================================================
-- 3. COMMUNITY & FORUM SYSTEM (PUBLIC DISCUSSIONS)
--  Knowledge sharing platform for farmers to learn from each other
-- ====================================================================

--  Organize discussions by topics for easy navigation
CREATE TABLE discussion_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,                  --  Category name (Crop Management, Livestock)
    description TEXT,                            
    parent_id INT,                               --  Hierarchical categories
    post_count INT DEFAULT 0,                    --  Show activity level in category
    display_order INT DEFAULT 0,                 --  For ordering categories in UI
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

--  Main discussion threads - the core of community knowledge sharing
CREATE TABLE discussions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author_id INT NOT NULL,                      --  Who started the discussion
    category_id INT NOT NULL,                    --  Which category this belongs to
    title VARCHAR(200) NOT NULL,                 
    content TEXT NOT NULL,                       
    view_count INT DEFAULT 0,                    
    like_count INT DEFAULT 0,                    
    comment_count INT DEFAULT 0,                 
    status VARCHAR(20) DEFAULT 'published',      
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES discussion_categories(id)
);

--  Tagging system for better content discovery
CREATE TABLE discussion_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    discussion_id INT NOT NULL,                  --  Which discussion is being tagged
    tag_name VARCHAR(50) NOT NULL,               --  Tag name (teff, irrigation, organic)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (discussion_id) REFERENCES discussions(id)
);

--  Comments/replies on discussions - community interactions
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    discussion_id INT NOT NULL,                   
    author_id INT NOT NULL,                      
    parent_comment_id INT,                       --  Nested replies to comments
    content TEXT NOT NULL,                       --  The actual comment 
    like_count INT DEFAULT 0,                    --  Amount of likes
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (discussion_id) REFERENCES discussions(id),
    FOREIGN KEY (author_id) REFERENCES users(id),
    FOREIGN KEY (parent_comment_id) REFERENCES comments(id)
);

--  Track user likes on discussions for engagement metrics
CREATE TABLE discussion_likes (
    user_id INT NOT NULL,                        
    discussion_id INT NOT NULL,                  
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, discussion_id),        --  Prevent duplicate likes from a user
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (discussion_id) REFERENCES discussions(id)
);

--  Track user likes on comments for helpful answer identification
CREATE TABLE comment_likes (
    user_id INT NOT NULL,                        --  Who liked the comment
    comment_id INT NOT NULL,                     --  Which comment was liked
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, comment_id),           --  Prevent duplicate likes from a user
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (comment_id) REFERENCES comments(id)
);

-- ====================================================================
-- 4. FARMING GUIDANCE SYSTEM
--  Educational content repository for farming practices
-- ====================================================================

--  Organize guidance articles in hierarchical structure
CREATE TABLE article_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(100) NOT NULL,                --  Broad category (Crops, Livestock, Soil)
    subdomain VARCHAR(100),                      --  Sub-category (Grains, Vegetables, Poultry)
    item VARCHAR(100),                           --  Specific item (Teff, Tomato, Chicken)
    description TEXT,                            
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

--  Main educational content storage
CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,                 
    content TEXT NOT NULL,                       
    category_id INT NOT NULL,                    
    author_id INT NOT NULL,                      
    image_url VARCHAR(500),                     
    status VARCHAR(20) DEFAULT 'draft',          --  Workflow control (draft, published, archived)
    view_count INT DEFAULT 0,                    
    like_count INT DEFAULT 0,                    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES article_categories(id),
    FOREIGN KEY (author_id) REFERENCES users(id)
);


-- ====================================================================
-- 5. NEWS 
-- ====================================================================

--  Categorize news articles for better organization
CREATE TABLE news_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,                  --  Category name (Policy, Market, Research)
    description TEXT,                            
    display_order INT DEFAULT 0,                 --  For ordering categories in UI
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

--  Store news articles and agricultural updates
CREATE TABLE news_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,                 --  headline
    content TEXT NOT NULL,                       --  Full news content
    excerpt TEXT,                                --  Short summary for preview
    category_id INT NOT NULL,                    
    author_id INT NOT NULL,                      
    image_url VARCHAR(500),                      
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES news_categories(id),
    FOREIGN KEY (author_id) REFERENCES users(id)
);

--  Tagging system for news articles
CREATE TABLE news_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_article_id INT NOT NULL,                --  Which news article
    tag_name VARCHAR(50) NOT NULL,               --  Tag name (coffee, subsidy, drought)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (news_article_id) REFERENCES news_articles(id)
);


-- ====================================================================
-- DEFAULT DATA FOR IMMEDIATE PLATFORM USAGE
--  Pre-populate essential data so platform works immediately
-- ====================================================================

-- Product categories for marketplace
INSERT INTO categories (name, description) VALUES
('Grains', 'Cereal grains and staples'),         
('Coffee', 'Coffee beans and products'),         
('Vegetables', 'Fresh vegetables'),              
('Equipment', 'Farming tools and machinery'),    
('Fertilizers', 'Soil nutrients and amendments'),
('Seeds', 'Seeds for planting');                 

-- Forum categories for community discussions
INSERT INTO discussion_categories (name, description, display_order) VALUES
('All Discussions', 'General farming discussions', 1),          
('Crop Management', 'Crop cultivation techniques', 2),          
('Livestock', 'Animal farming and care', 3),                    
('Market Info', 'Pricing and market trends', 4),                
('Pest Control', 'Pest and disease management', 5),             
('Water Management', 'Irrigation and water conservation', 6),   
('Equipment', 'Farm tools and machinery', 7);                   

-- News categories for content organization
INSERT INTO news_categories (name, description, display_order) VALUES
('Policy', 'Government policies and subsidies', 1),             
('Market', 'Market trends and prices', 2),                      
('Research', 'Agricultural research findings', 3),              
('Technology', 'Farming technology updates', 4),                
('Weather', 'Weather forecasts and alerts', 5);                 

-- Farming guidance categories for educational content
INSERT INTO article_categories (domain, subdomain, item, description) VALUES
('Crops', 'Grains', 'Teff', 'Teff cultivation guidance'),       
('Crops', 'Grains', 'Maize', 'Maize farming techniques'),       
('Crops', 'Grains', 'Wheat', 'Wheat production guide'),         
('Crops', 'Vegetables', 'Tomato', 'Tomato cultivation'),        
('Crops', 'Vegetables', 'Onion', 'Onion farming'),              
('Livestock', 'Poultry', 'Chicken', 'Chicken farming'),         
('Livestock', 'Dairy', 'Cattle', 'Dairy cattle management'),    
('Soil Management', 'Fertilization', 'Compost', 'Compost making and use'), 
('Soil Management', 'Conservation', 'Terracing', 'Soil conservation techniques'); 


--  Initial admin account to manage the platform immediately after installation
INSERT INTO users (email, password_hash, name, role, is_active) VALUES
('admin@agrihub.com', 'agrihubpas', 'System Administrator', 'super_admin', TRUE);

INSERT INTO user_profiles (user_id, bio, specialization) VALUES
(1, 'System administrator account', 'Platform Management');


-- Demo user to test regular user functionality
INSERT INTO users (email, password_hash, name, role, location, is_active)
VALUES ('farmer@example.com', 'user1', 'Elias Zerabruk', 'farmer', 'Addis Ababa', TRUE);


INSERT INTO user_profiles (user_id, bio, farm_size, specialization, experience_years) VALUES
(2, 'Teff and maize farmer with 10 years experience', 5.5, 'Grains', 10);

--

>>>>>>> 52ea2f9316dc2c70b9abed62ca66489f3c2d6676
