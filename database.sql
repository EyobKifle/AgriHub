-- Create a database named 'agrihub_db' if it doesn't exist
CREATE DATABASE IF NOT EXISTS agrihub_db;
USE agrihub_db;

-- Table for products in the marketplace
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title_key` VARCHAR(255) NOT NULL,
  `location_key` VARCHAR(255) NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `unit` VARCHAR(50) NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `featured` BOOLEAN DEFAULT FALSE,
  `on_sale` BOOLEAN DEFAULT FALSE,
  `date_listed` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for news articles
CREATE TABLE `news_articles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title_key` VARCHAR(255) NOT NULL,
  `desc_key` VARCHAR(500) NOT NULL,
  `author_key` VARCHAR(255) NOT NULL,
  `time_key` VARCHAR(100) NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `category_key` VARCHAR(50) NOT NULL,
  `tags` VARCHAR(255),
  `is_featured` BOOLEAN DEFAULT FALSE,
  `date_published` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample Product Data
INSERT INTO `products` (`title_key`, `location_key`, `price`, `unit`, `image_url`, `category`, `featured`) VALUES
('product.teff.title', 'product.teff.location', 3500.00, '/ Quintal', '/images/Homepage images/Grain Teff.jpg', 'grains', TRUE),
('product.coffee.title', 'product.coffee.location', 8000.00, '/ Quintal', '/images/Homepage images/yergachefe buna.jpg', 'coffee', TRUE),
('product.fertilizer.title', 'product.fertilizer.location', 1200.00, '/ 50kg Bag', '/images/Homepage images/organiccompost.jpg', 'fertilizers', FALSE),
('product.tomatoes.title', 'product.tomatoes.location', 50.00, '/ kg', 'https://placehold.co/400x300/ff6347/FFF?text=Tomatoes', 'vegetables', FALSE),
('product.maize.title', 'product.maize.location', 800.00, '/ 10kg', 'https://placehold.co/400x300/feca57/FFF?text=Maize+Seeds', 'seeds', FALSE),
('product.irrigation.title', 'product.irrigation.location', 15000.00, '/ Kit', 'https://placehold.co/400x300/45a049/FFF?text=Drip+Kit', 'equipment', FALSE);

-- Sample News Data
INSERT INTO `news_articles` (`title_key`, `desc_key`, `author_key`, `time_key`, `image_url`, `category_key`, `tags`, `is_featured`) VALUES
('news.article1.title', 'news.article1.desc', 'news.article1.author', 'news.article1.time', 'https://placehold.co/800x400/1E4620/FFF?text=New+Subsidies', 'news.cat.policy', 'subsidy,government', TRUE),
('news.article2.title', 'news.article2.desc', 'news.article2.author', 'news.article2.time', 'https://placehold.co/400x250/D97706/FFF?text=Coffee+Prices', 'news.cat.market', 'coffee,exports', FALSE),
('news.article3.title', 'news.article3.desc', 'news.article3.author', 'news.article3.time', 'https://placehold.co/400x250/2a9d8f/FFF?text=Teff+Research', 'news.cat.research', 'drought,teff', FALSE),
('news.article4.title', 'news.article4.desc', 'news.article4.author', 'news.article4.time', 'https://placehold.co/400x250/264653/FFF?text=Digital+Farming', 'news.cat.technology', 'digital,mobile', FALSE),
('news.article5.title', 'news.article5.desc', 'news.article5.author', 'news.article5.time', 'https://placehold.co/400x250/fca311/FFF?text=Rainfall+Forecast', 'news.cat.weather', 'weather,forecast', FALSE);

-- Table for users
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('buyer', 'seller', 'admin') NOT NULL,
  `status` ENUM('active', 'pending', 'banned') NOT NULL DEFAULT 'pending',
  `avatar_url` VARCHAR(255),
  `date_joined` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample User Data
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `status`) VALUES
('John Farmer', 'john.f@example.com', 'some_secure_hash', 'seller', 'active'),
('Meron K.', 'meron.k@example.com', 'another_secure_hash', 'buyer', 'pending'),
('Admin User', 'admin@agrihub.com', 'admin_secure_hash', 'admin', 'active');