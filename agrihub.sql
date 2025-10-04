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
