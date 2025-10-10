CREATE DATABASE IF NOT EXISTS `agrihub` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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
    `status` ENUM('active', 'pending', 'banned', 'inactive') NOT NULL DEFAULT 'pending',
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_location` (`location`)
);

CREATE TABLE `user_profiles` (
    `user_id` INT UNSIGNED NOT NULL PRIMARY KEY,
    `bio` TEXT,
    `farm_size_hectares` DECIMAL(10,2) DEFAULT NULL,
    `specialization` VARCHAR(255) DEFAULT NULL,
    `experience_years` TINYINT UNSIGNED DEFAULT NULL,
    `language_preference` VARCHAR(10) NOT NULL DEFAULT 'en',
    `pref_theme` ENUM('light','dark') NOT NULL DEFAULT 'light',
    `pref_email_notifications` BOOLEAN NOT NULL DEFAULT TRUE,
    `business_name` VARCHAR(255) DEFAULT NULL,
    `business_address` TEXT,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_user_profiles_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
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
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT UNSIGNED,
    `updated_by` INT UNSIGNED,
    CONSTRAINT `fk_categories_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_categories_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_categories_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

CREATE TABLE `products` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `seller_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `price` DECIMAL(12,2) NOT NULL CHECK (`price`>0),
    `unit` VARCHAR(50) NOT NULL DEFAULT 'unit',
    `quantity_available` DECIMAL(10,2) NOT NULL DEFAULT 0 CHECK (`quantity_available`>=0),
    `phone_number` VARCHAR(25) DEFAULT NULL,
    `status` ENUM('active','inactive','sold_out','pending_approval') NOT NULL DEFAULT 'active',
    `is_featured` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_products_seller_id` FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_products_category_id` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
    INDEX `idx_products_seller_id` (`seller_id`),
    INDEX `idx_products_category_status` (`category_id`,`status`),
    INDEX `idx_products_price` (`price`)
);

CREATE TABLE `product_images` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT UNSIGNED NOT NULL,
    `image_url` VARCHAR(512) NOT NULL,
    `is_primary` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_product_images_product_id` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
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
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_discussion_categories_display_order` (`display_order`)
);

CREATE TABLE `discussions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `author_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `status` ENUM('published','draft','archived') NOT NULL DEFAULT 'published',
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `like_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `comment_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_discussions_author_id` FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_discussions_category_id` FOREIGN KEY (`category_id`) REFERENCES `discussion_categories`(`id`) ON DELETE RESTRICT,
    INDEX `idx_discussions_author_id` (`author_id`),
    INDEX `idx_discussions_category_status` (`category_id`,`status`)
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
    CONSTRAINT `fk_comments_discussion_id` FOREIGN KEY (`discussion_id`) REFERENCES `discussions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_comments_author_id` FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_comments_parent_id` FOREIGN KEY (`parent_comment_id`) REFERENCES `discussion_comments`(`id`) ON DELETE SET NULL
);

CREATE TABLE `tags` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `use_count` INT UNSIGNED NOT NULL DEFAULT 0
);

CREATE TABLE `discussion_tags` (
    `discussion_id` INT UNSIGNED NOT NULL,
    `tag_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`discussion_id`,`tag_id`),
    CONSTRAINT `fk_discussion_tags_discussion_id` FOREIGN KEY (`discussion_id`) REFERENCES `discussions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_discussion_tags_tag_id` FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE
);

CREATE TABLE `conversations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `subject` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE `conversation_participants` (
    `conversation_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `last_read_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`conversation_id`,`user_id`),
    CONSTRAINT `fk_convo_participants_convo_id` FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_convo_participants_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
);

CREATE TABLE `messages` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT UNSIGNED NOT NULL,
    `sender_id` INT UNSIGNED NOT NULL,
    `body` TEXT,
    `attachment_url` VARCHAR(512) DEFAULT NULL,
    `attachment_type` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_messages_convo_id` FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_messages_sender_id` FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
);

CREATE TABLE `reports` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reporter_id` INT UNSIGNED NOT NULL,
    `reported_item_type` ENUM('product','discussion','comment','user','other') NOT NULL,
    `reported_item_id` INT UNSIGNED NOT NULL,
    `reason` TEXT NOT NULL,
    `details` TEXT DEFAULT NULL,
    `status` ENUM('open','in_review','resolved','dismissed') NOT NULL DEFAULT 'open',
    `resolved_by` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_reports_reporter_id` FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_reports_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_reports_status` (`status`)
);


CREATE TABLE `user_activity_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `activity_type` VARCHAR(100) NOT NULL,
    `related_item_id` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_activity_log_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_activity_log_type` (`activity_type`),
    INDEX `idx_activity_log_related_item` (`related_item_id`)
);

CREATE TABLE `system_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT,
    `value_type` ENUM('string','number','boolean','json') NOT NULL DEFAULT 'string',
    `description` VARCHAR(255) DEFAULT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE `translations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `table_name` VARCHAR(50) NOT NULL,
    `field_name` VARCHAR(50) NOT NULL,
    `record_id` INT UNSIGNED NOT NULL,
    `language_code` VARCHAR(10) NOT NULL,
    `translated_value` TEXT NOT NULL,
    UNIQUE `uq_translation` (`table_name`,`field_name`,`record_id`,`language_code`)
);

CREATE TABLE `completed_sales` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `seller_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `product_title` VARCHAR(200) NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `completed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_completed_sales_seller_id` FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    -- Use ON DELETE RESTRICT to prevent product deletion if a sale is logged, or SET NULL if you want to keep the log even if the product is deleted. RESTRICT is safer.
    CONSTRAINT `fk_completed_sales_product_id` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT,
    INDEX `idx_completed_sales_seller_id` (`seller_id`),
    INDEX `idx_completed_sales_product_id` (`product_id`)
);


DELIMITER //

CREATE TRIGGER after_product_insert
AFTER INSERT ON products
FOR EACH ROW
BEGIN
    UPDATE categories SET product_count = product_count + 1 WHERE id = NEW.category_id;
END//

CREATE TRIGGER after_product_delete
AFTER DELETE ON products
FOR EACH ROW
BEGIN
    UPDATE categories SET product_count = GREATEST(0, product_count - 1) WHERE id = OLD.category_id;
END//

CREATE TRIGGER after_product_update
AFTER UPDATE ON products
FOR EACH ROW
BEGIN
    IF OLD.category_id <> NEW.category_id THEN
        UPDATE categories SET product_count = GREATEST(0, product_count - 1) WHERE id = OLD.category_id;
        UPDATE categories SET product_count = product_count + 1 WHERE id = NEW.category_id;
    END IF;
END//

CREATE TRIGGER after_discussion_insert
AFTER INSERT ON discussions
FOR EACH ROW
BEGIN
    UPDATE discussion_categories SET discussion_count = discussion_count + 1 WHERE id = NEW.category_id;
END//

CREATE TRIGGER after_discussion_delete
AFTER DELETE ON discussions
FOR EACH ROW
BEGIN
    UPDATE discussion_categories SET discussion_count = GREATEST(0, discussion_count - 1) WHERE id = OLD.category_id;
END//

DELIMITER ;
INSERT INTO users (
    email,
    password_hash,
    name,
    role,
    avatar_url,
    phone,
    location,
    status,
    last_login,
    created_at,
    updated_at
)
VALUES (
    'Agrihubadmin@gmail.com',
    '$2b$12$d2XAaedjRCMo1vTlVTlsx0teqMk2J9J7BhGQa/IVcI0JTRk6QihVu', -- bcrypt hash
    'AgriHub Admin',
    'admin',
    NULL,
    '000-000-0000',
    'Head Office',
    'active',
    NOW(),
    NOW(),
    NOW()
);

ALTER TABLE categories
ADD COLUMN image_url VARCHAR(512) DEFAULT NULL AFTER description_key;

INSERT INTO categories (name, name_key, slug, description_key, created_by)
VALUES ('Crops', 'crops', 'crops', 'crops_desc', 1);

INSERT INTO categories (parent_id, name, name_key, slug, description_key, image_url, created_by)
VALUES
(1, 'Teff', 'teff', 'teff', 'desc_teff', 'https://images.unsplash.com/photo-teff-field.jpg', 1),
(1, 'Wheat', 'wheat', 'wheat', 'desc_wheat', 'https://images.unsplash.com/photo-wheat-harvest.jpg', 1),
(1, 'Maize', 'maize', 'maize', 'desc_maize', 'https://images.unsplash.com/photo-maize-crop.jpg', 1),
(1, 'Coffee', 'coffee', 'coffee', 'desc_coffee', 'https://images.unsplash.com/photo-coffee-beans.jpg', 1),
(1, 'Vegetables', 'vegetables', 'vegetables', 'desc_vegetables', 'https://images.unsplash.com/photo-vegetable-farm.jpg', 1),
(1, 'Fruits', 'fruits', 'fruits', 'desc_fruits', 'https://images.unsplash.com/photo-fruits-orchard.jpg', 1),
(1, 'Barley', 'barley', 'barley', 'desc_barley', 'https://images.unsplash.com/photo-barley-field.jpg', 1),
(1, 'Sorghum', 'sorghum', 'sorghum', 'desc_sorghum', 'https://images.unsplash.com/photo-sorghum-crop.jpg', 1),
(1, 'Beans', 'beans', 'beans', 'desc_beans', 'https://images.unsplash.com/photo-bean-farm.jpg', 1),
(1, 'Rice', 'rice', 'rice', 'desc_rice', 'https://images.unsplash.com/photo-rice-field.jpg', 1);

-- Parent
INSERT INTO categories (name, name_key, slug, description_key, created_by)
VALUES ('Livestock', 'livestock', 'livestock', 'livestock_desc', 1);

-- Children (example)
INSERT INTO categories (parent_id, name, name_key, slug, description_key, image_url, created_by)
VALUES
(LAST_INSERT_ID(), 'Cattle', 'cattle', 'cattle', 'desc_cattle', 'https://images.unsplash.com/photo-cattle.jpg', 1),
(LAST_INSERT_ID(), 'Goats', 'goats', 'goats', 'desc_goats', 'https://images.unsplash.com/photo-goats.jpg', 1),
(LAST_INSERT_ID(), 'Sheep', 'sheep', 'sheep', 'desc_sheep', 'https://images.unsplash.com/photo-sheep.jpg', 1),
(LAST_INSERT_ID(), 'Camels', 'camels', 'camels', 'desc_camels', 'https://images.unsplash.com/photo-camels.jpg', 1),
(LAST_INSERT_ID(), 'Dairy', 'dairy', 'dairy', 'desc_dairy', 'https://images.unsplash.com/photo-dairy-cows.jpg', 1),
(LAST_INSERT_ID(), 'Beef Production', 'beef', 'beef', 'desc_beef', 'https://images.unsplash.com/photo-beef-farm.jpg', 1),
(LAST_INSERT_ID(), 'Animal Feed', 'animal_feed', 'animal-feed', 'desc_feed', 'https://images.unsplash.com/photo-animal-feed.jpg', 1),
(LAST_INSERT_ID(), 'Animal Health', 'animal_health', 'animal-health', 'desc_health', 'https://images.unsplash.com/photo-vet-animal.jpg', 1),
(LAST_INSERT_ID(), 'Pasture Management', 'pasture', 'pasture', 'desc_pasture', 'https://images.unsplash.com/photo-pasture.jpg', 1),
(LAST_INSERT_ID(), 'Livestock Equipment', 'livestock_equipment', 'livestock-equipment', 'desc_equipment', 'https://images.unsplash.com/photo-livestock-equipment.jpg', 1);
