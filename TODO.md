# TODO: Verify and Fix Signup Flow for User Role and Dashboard Redirection

## Steps to Complete:
1. Verify Signup.html form action and method to ensure it posts to auth.php?action=signup
2. Verify User-Dashboard.php is properly implemented for user dashboard functionality
3. Fix any issues found in Signup.html or User-Dashboard.php
4. Test the signup flow end-to-end if needed

## Progress:
- [x] Step 1: Verify Signup.html
- [x] Step 2: Verify User-Dashboard.php
- [x] Step 3: Fix any issues
- [x] Step 4: Test signup flow

# TODO: Fix Database Query Issues

## Backend Security and Consistency
- [ ] Fix auth.php: Use prepared statement for UPDATE users SET last_login in login action
- [ ] Fix get_news.php: Change table name from 'news_articles' to 'articles' and adjust field names to match schema (title, content, etc.)
- [ ] Audit all $conn->query() usages for potential SQL injection risks (found in Admin-Dashboard.php, but safe as no variables)

## Progress:
- [ ] Step 1: Update auth.php last_login query
- [ ] Step 2: Update get_news.php table and fields
- [ ] Step 3: Review other query usages

# TODO: Implement Messages Functionality

## Backend API
- [ ] Create php/api/messages.php to handle GET/POST for conversations and messages
- [ ] Implement database queries for fetching conversations, sending messages, etc.
- [ ] Update User-Messages.php to handle API requests instead of rendering HTML for fetches

## Frontend JS
- [ ] Fix User-Messages.js fetch URL to point to new API endpoint
- [ ] Implement full conversation view and message sending

## Dashboard Integration
- [ ] Update User-Dashboard.php to calculate unread messages count from database
- [ ] Remove hardcoded unreadMessagesCount = 0

## Progress:
- [ ] Step 1: Create messages API
- [ ] Step 2: Update User-Messages.php
- [ ] Step 3: Fix JS fetch
- [ ] Step 4: Update dashboard

# TODO: Fix Frontend Inconsistencies

## Sidebar Consistency
- [ ] Update User-Messages.php to use include __DIR__ . '/_sidebar.php' instead of inline sidebar
- [ ] Check other user pages (User-Orders.php, User-Discussions.php, etc.) for sidebar consistency
- [ ] Ensure all user pages use the same sidebar structure

## JS Fetch Paths
- [ ] Fix User-Listings.js: Change fetch('../php/api/listings.php') to '../php/listings.php'
- [ ] Check other JS files for incorrect API paths

## Progress:
- [ ] Step 1: Update User-Messages.php sidebar
- [ ] Step 2: Audit other pages
- [ ] Step 3: Fix JS paths

# TODO: Implement Chat/Community Features

## ChatApi.php
- [ ] Connect ChatApi.php to actual database instead of mock responses
- [ ] Implement real CRUD for messages in discussions
- [ ] Ensure session authentication

## Community Pages
- [ ] Verify Community.php and related JS for functionality
- [ ] Implement discussion comments if not working

## Progress:
- [ ] Step 1: Update ChatApi.php to use DB
- [ ] Step 2: Test community chat
- [ ] Step 3: Implement comments

# TODO: Clean Up Old and Unused Files

## Remove Deprecated Files
- [ ] Remove Old-User-Management.php and Old-Listings-Management.php if no longer needed
- [ ] Check for other old files in php/ directory

## Unused Features
- [ ] Review Newsletter Template/ folder - integrate or remove if not used
- [ ] Check if all HTML/PHP files are linked and functional

## Progress:
- [ ] Step 1: Identify unused files
- [ ] Step 2: Remove or archive old files
- [ ] Step 3: Verify no broken links

# TODO: Implement Internationalization (i18n)

## Translation Files
- [ ] Verify Languages/*.json files are complete
- [ ] Implement server-side translation loading if not done
- [ ] Ensure all data-i18n-key attributes have corresponding translations

## JS Integration
- [ ] Check Js/i18n.js for functionality
- [ ] Load translations dynamically based on user preference

## Progress:
- [ ] Step 1: Audit translation files
- [ ] Step 2: Implement missing translations
- [ ] Step 3: Test language switching

# TODO: Security Audit

## Input Validation
- [ ] Add server-side validation for all forms (e.g., profile updates, listings)
- [ ] Sanitize all user inputs, even with prepared statements

## Authentication
- [ ] Ensure all pages check session and role appropriately
- [ ] Implement CSRF protection for forms

## File Uploads
- [ ] Secure avatar uploads in profile.php (check file types, sizes, paths)

## Progress:
- [ ] Step 1: Review form validations
- [ ] Step 2: Add CSRF tokens
- [ ] Step 3: Secure uploads

# TODO: Database Schema and Data Integrity

## Schema Verification
- [ ] Ensure all foreign keys are properly defined and constraints work
- [ ] Check triggers for category/product counts
- [ ] Verify indexes for performance

## Data Population
- [ ] Populate categories table with initial data
- [ ] Add sample articles for news/guidance
- [ ] Ensure admin user is inserted

## Progress:
- [ ] Step 1: Test foreign keys
- [ ] Step 2: Add initial data
- [ ] Step 3: Verify triggers

# TODO: Frontend UI/UX Improvements

## CSS Consistency and Design
- [ ] Audit all CSS files for consistent color schemes, fonts, spacing, and component styles
- [ ] Standardize button styles, form inputs, cards, and layouts across all pages
- [ ] Ensure consistent header/footer designs and responsive behavior
- [ ] Implement proper loading states and animations for better UX
- [ ] Add hover/focus states for all interactive elements

## Responsive Design
- [ ] Test all pages on mobile, tablet, and desktop breakpoints
- [ ] Fix responsive issues in sidebar, forms, tables, and grids
- [ ] Ensure touch-friendly button sizes and spacing on mobile
- [ ] Optimize images and layouts for different screen sizes

## Accessibility
- [ ] Add proper ARIA labels and roles to interactive elements
- [ ] Ensure sufficient color contrast ratios
- [ ] Implement keyboard navigation for all features
- [ ] Add alt text for all images and icons

## Page Structure Consistency
- [ ] Standardize HTML structure across all user/admin pages
- [ ] Ensure all pages include proper meta tags, titles, and favicons
- [ ] Implement consistent error pages (404, 500, etc.)
- [ ] Add breadcrumb navigation where appropriate

## JS Error Handling and UX
- [ ] Add try-catch and user-friendly error messages in all JS files
- [ ] Handle network errors gracefully with retry options
- [ ] Implement proper form validation feedback
- [ ] Add loading indicators for async operations

## Progress:
- [ ] Step 1: Audit CSS and design consistency
- [ ] Step 2: Fix responsive design issues
- [ ] Step 3: Improve accessibility
- [ ] Step 4: Standardize page structures
- [ ] Step 5: Enhance JS error handling

# TODO: Performance Optimizations

## Database Queries
- [ ] Optimize queries in User-Dashboard.php (recent activity)
- [ ] Add pagination for large lists (listings, discussions)

## Caching
- [ ] Implement caching for static data (categories, etc.)
- [ ] Use browser caching for assets

## Progress:
- [ ] Step 1: Profile slow queries
- [ ] Step 2: Add pagination
- [ ] Step 3: Implement caching

# TODO: Testing and Quality Assurance

## Unit Tests
- [ ] Create tests for auth.php functions
- [ ] Test database operations

## Integration Tests
- [ ] Test full user flows (signup, login, listing creation)
- [ ] Test admin functionalities

## Progress:
- [ ] Step 1: Set up testing framework
- [ ] Step 2: Write unit tests
- [ ] Step 3: Write integration tests

# TODO: Documentation

## API Documentation
- [ ] Document all PHP API endpoints
- [ ] Update API_Usage_Documentation.txt

## Code Comments
- [ ] Add PHPDoc comments to all functions
- [ ] Document complex logic

## Progress:
- [ ] Step 1: Document APIs
- [ ] Step 2: Add code comments
- [ ] Step 3: Update README.md
