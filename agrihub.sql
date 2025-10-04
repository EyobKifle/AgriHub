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

