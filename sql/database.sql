-- ============================================================
-- PHP E-Commerce Website — Full Database Schema
-- Repository: https://github.com/BhartiKuldeep/php-ecommerce-website
-- 
-- Run this file in phpMyAdmin or MySQL CLI:
--   mysql -u root -p < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS `ecommerce_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ecommerce_db`;

-- ------------------------------------------------------------
-- 1. ADMINS
-- ------------------------------------------------------------
CREATE TABLE `admins` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100)  NOT NULL,
  `email`      VARCHAR(150)  NOT NULL UNIQUE,
  `password`   VARCHAR(255)  NOT NULL,
  `role`       ENUM('superadmin','admin','editor') NOT NULL DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. USERS
-- ------------------------------------------------------------
CREATE TABLE `users` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100)  NOT NULL,
  `email`      VARCHAR(150)  NOT NULL UNIQUE,
  `password`   VARCHAR(255)  NOT NULL,
  `phone`      VARCHAR(20)   DEFAULT NULL,
  `address`    TEXT           DEFAULT NULL,
  `city`       VARCHAR(100)  DEFAULT NULL,
  `state`      VARCHAR(100)  DEFAULT NULL,
  `zip`        VARCHAR(20)   DEFAULT NULL,
  `country`    VARCHAR(100)  DEFAULT 'India',
  `is_active`  TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. CATEGORIES
-- ------------------------------------------------------------
CREATE TABLE `categories` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100)  NOT NULL UNIQUE,
  `slug`        VARCHAR(120)  NOT NULL UNIQUE,
  `description` TEXT          DEFAULT NULL,
  `image`       VARCHAR(255)  DEFAULT NULL,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. PRODUCTS
-- ------------------------------------------------------------
CREATE TABLE `products` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id`   INT UNSIGNED  NOT NULL,
  `name`          VARCHAR(200)  NOT NULL,
  `slug`          VARCHAR(220)  NOT NULL UNIQUE,
  `description`   TEXT          DEFAULT NULL,
  `price`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `sale_price`    DECIMAL(10,2) DEFAULT NULL,
  `image`         VARCHAR(255)  DEFAULT NULL,
  `stock`         INT UNSIGNED  NOT NULL DEFAULT 0,
  `is_featured`   TINYINT(1)   NOT NULL DEFAULT 0,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. CART  (persistent DB cart, mirrors session cart on login)
-- ------------------------------------------------------------
CREATE TABLE `cart` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity`   INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. WISHLIST
-- ------------------------------------------------------------
CREATE TABLE `wishlist` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `user_product` (`user_id`, `product_id`),
  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. COUPONS
-- ------------------------------------------------------------
CREATE TABLE `coupons` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code`           VARCHAR(50)   NOT NULL UNIQUE,
  `discount_type`  ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `min_order`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `max_uses`       INT UNSIGNED  NOT NULL DEFAULT 0,
  `used_count`     INT UNSIGNED  NOT NULL DEFAULT 0,
  `is_active`      TINYINT(1)   NOT NULL DEFAULT 1,
  `expires_at`     DATE          DEFAULT NULL,
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 8. ORDERS
-- ------------------------------------------------------------
CREATE TABLE `orders` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`         INT UNSIGNED  NOT NULL,
  `coupon_id`       INT UNSIGNED  DEFAULT NULL,
  `order_number`    VARCHAR(30)   NOT NULL UNIQUE,
  `subtotal`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `shipping_cost`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total`           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status`          ENUM('pending','confirmed','packed','shipped','delivered','cancelled')
                      NOT NULL DEFAULT 'pending',
  `shipping_name`   VARCHAR(100)  NOT NULL,
  `shipping_email`  VARCHAR(150)  NOT NULL,
  `shipping_phone`  VARCHAR(20)   NOT NULL,
  `shipping_address` TEXT         NOT NULL,
  `shipping_city`   VARCHAR(100)  NOT NULL,
  `shipping_state`  VARCHAR(100)  NOT NULL,
  `shipping_zip`    VARCHAR(20)   NOT NULL,
  `shipping_country` VARCHAR(100) NOT NULL DEFAULT 'India',
  `notes`           TEXT          DEFAULT NULL,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`coupon_id`) REFERENCES `coupons`(`id`)  ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 9. ORDER_ITEMS
-- ------------------------------------------------------------
CREATE TABLE `order_items` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id`   INT UNSIGNED  NOT NULL,
  `product_id` INT UNSIGNED  NOT NULL,
  `name`       VARCHAR(200)  NOT NULL,
  `price`      DECIMAL(10,2) NOT NULL,
  `quantity`   INT UNSIGNED  NOT NULL DEFAULT 1,
  `total`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 10. PAYMENTS
-- ------------------------------------------------------------
CREATE TABLE `payments` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id`       INT UNSIGNED  NOT NULL UNIQUE,
  `payment_method` ENUM('cod','card','upi','netbanking') NOT NULL DEFAULT 'cod',
  `payment_status` ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `transaction_id` VARCHAR(100) DEFAULT NULL,
  `amount`         DECIMAL(10,2) NOT NULL,
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 11. REVIEWS
-- ------------------------------------------------------------
CREATE TABLE `reviews` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `rating`     TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `comment`    TEXT         DEFAULT NULL,
  `is_approved` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- SEED DATA
-- ============================================================

-- Default admin  (password: Admin@123)
INSERT INTO `admins` (`name`, `email`, `password`, `role`) VALUES
('Super Admin', 'admin@example.com', '$2y$10$8KzQxH6kGZ0BQY8w0X6WaOQ1z3v5a1HQDoshfyNn/q4bRbO4Xj5yS', 'superadmin');

-- Default user  (password: User@123)
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `address`, `city`, `state`, `zip`) VALUES
('John Doe', 'user@example.com', '$2y$10$dO1TGnCvPEU5Zx3G6Qs6T.CiYFBpDk3n2u2J3c8e4wKFsVx0PnOxS', '9876543210', '123 Main Street', 'Mumbai', 'Maharashtra', '400001');

-- Categories
INSERT INTO `categories` (`name`, `slug`, `description`, `is_active`) VALUES
('Electronics',  'electronics',  'Gadgets, devices, and electronic accessories', 1),
('Fashion',      'fashion',      'Clothing, apparel, and fashion accessories',   1),
('Books',        'books',        'Fiction, non-fiction, and educational books',   1),
('Shoes',        'shoes',        'Footwear for men, women, and kids',            1),
('Accessories',  'accessories',  'Watches, bags, sunglasses, and more',          1);

-- Products — Electronics (category_id = 1)
INSERT INTO `products` (`category_id`, `name`, `slug`, `description`, `price`, `sale_price`, `image`, `stock`, `is_featured`) VALUES
(1, 'Wireless Bluetooth Headphones', 'wireless-bluetooth-headphones', 'Premium noise-cancelling wireless headphones with 30-hour battery life and deep bass.', 2999.00, 2499.00, 'headphones.jpg', 50, 1),
(1, 'Smart Fitness Band',            'smart-fitness-band',            'Track your heart rate, steps, and sleep with this waterproof fitness band.',             1499.00, 999.00,  'fitness-band.jpg', 80, 1),
(1, 'Portable Bluetooth Speaker',    'portable-bluetooth-speaker',    'Compact speaker with 360° sound and 12-hour battery life.',                              1999.00, NULL,    'speaker.jpg', 35, 0),
(1, 'USB-C Fast Charger',            'usb-c-fast-charger',            '65W GaN charger compatible with laptops, tablets, and phones.',                           1299.00, 999.00,  'charger.jpg', 100, 0),
(1, '4K Webcam',                     '4k-webcam',                     'Ultra HD webcam with auto-focus and built-in microphone for video calls.',                3499.00, NULL,    'webcam.jpg', 25, 1);

-- Products — Fashion (category_id = 2)
INSERT INTO `products` (`category_id`, `name`, `slug`, `description`, `price`, `sale_price`, `image`, `stock`, `is_featured`) VALUES
(2, 'Classic Denim Jacket',   'classic-denim-jacket',   'Timeless denim jacket with a modern slim fit.',                                  2499.00, 1999.00, 'denim-jacket.jpg', 40, 1),
(2, 'Cotton Crew-Neck T-Shirt', 'cotton-crewneck-tshirt', '100% organic cotton tee available in multiple colors.',                       599.00,  499.00,  'tshirt.jpg', 200, 0),
(2, 'Formal Slim-Fit Shirt',  'formal-slimfit-shirt',   'Wrinkle-free formal shirt perfect for office and events.',                       1299.00, NULL,    'formal-shirt.jpg', 60, 0),
(2, 'Winter Hoodie',          'winter-hoodie',          'Warm fleece-lined hoodie with kangaroo pocket.',                                 1799.00, 1499.00, 'hoodie.jpg', 70, 1),
(2, 'Chino Trousers',         'chino-trousers',         'Comfortable stretch chinos for everyday wear.',                                  1499.00, NULL,    'chinos.jpg', 55, 0);

-- Products — Books (category_id = 3)
INSERT INTO `products` (`category_id`, `name`, `slug`, `description`, `price`, `sale_price`, `image`, `stock`, `is_featured`) VALUES
(3, 'Atomic Habits',               'atomic-habits',               'James Clear's best-selling guide to building good habits and breaking bad ones.', 499.00, 399.00, 'atomic-habits.jpg', 150, 1),
(3, 'The Psychology of Money',     'psychology-of-money',         'Morgan Housel explores the strange ways people think about money.',                450.00, NULL,   'psychology-money.jpg', 120, 0),
(3, 'Clean Code',                  'clean-code',                  'Robert C. Martin's handbook of agile software craftsmanship.',                    699.00, 599.00, 'clean-code.jpg', 80, 1),
(3, 'The Alchemist',               'the-alchemist',               'Paulo Coelho's masterpiece about following your dreams.',                         350.00, 299.00, 'alchemist.jpg', 200, 0),
(3, 'Deep Work',                   'deep-work',                   'Cal Newport's rules for focused success in a distracted world.',                  499.00, NULL,   'deep-work.jpg', 90, 0);

-- Products — Shoes (category_id = 4)
INSERT INTO `products` (`category_id`, `name`, `slug`, `description`, `price`, `sale_price`, `image`, `stock`, `is_featured`) VALUES
(4, 'Running Sport Shoes',    'running-sport-shoes',    'Lightweight mesh running shoes with cushioned sole.',           2499.00, 1999.00, 'running-shoes.jpg', 60, 1),
(4, 'Casual Canvas Sneakers', 'casual-canvas-sneakers', 'Classic lace-up canvas sneakers for everyday comfort.',         1299.00, NULL,    'canvas-sneakers.jpg', 90, 0),
(4, 'Leather Formal Shoes',   'leather-formal-shoes',   'Genuine leather oxford shoes with premium finish.',             3499.00, 2999.00, 'formal-shoes.jpg', 30, 0),
(4, 'Flip Flop Sandals',      'flip-flop-sandals',      'Comfortable rubber sandals for home and beach.',                 399.00,  299.00,  'sandals.jpg', 150, 0),
(4, 'Hiking Boots',           'hiking-boots',           'Waterproof trekking boots with ankle support.',                  4499.00, 3999.00, 'hiking-boots.jpg', 25, 1);

-- Products — Accessories (category_id = 5)
INSERT INTO `products` (`category_id`, `name`, `slug`, `description`, `price`, `sale_price`, `image`, `stock`, `is_featured`) VALUES
(5, 'Analog Wrist Watch',      'analog-wrist-watch',      'Stainless steel analog watch with leather strap.',             2999.00, 2499.00, 'watch.jpg', 45, 1),
(5, 'Polarized Sunglasses',    'polarized-sunglasses',    'UV400 polarized sunglasses with lightweight frame.',            999.00,  799.00,  'sunglasses.jpg', 100, 0),
(5, 'Leather Laptop Bag',      'leather-laptop-bag',      'Genuine leather bag fits up to 15.6-inch laptops.',             3499.00, NULL,    'laptop-bag.jpg', 35, 1),
(5, 'Canvas Backpack',         'canvas-backpack',         'Durable 30L canvas backpack with multiple compartments.',        1799.00, 1499.00, 'backpack.jpg', 65, 0),
(5, 'Minimalist Wallet',       'minimalist-wallet',       'Slim RFID-blocking wallet with card slots and money clip.',      799.00,  599.00,  'wallet.jpg', 120, 0);

-- Coupons
INSERT INTO `coupons` (`code`, `discount_type`, `discount_value`, `min_order`, `max_uses`, `is_active`, `expires_at`) VALUES
('WELCOME10', 'percentage', 10.00, 500.00,  100, 1, '2026-12-31'),
('FLAT200',   'fixed',      200.00, 1500.00, 50,  1, '2026-12-31'),
('SUMMER15',  'percentage', 15.00, 1000.00, 75,  1, '2026-06-30');

-- Sample order for demo user
INSERT INTO `orders` (`user_id`, `order_number`, `subtotal`, `discount`, `shipping_cost`, `total`, `status`,
  `shipping_name`, `shipping_email`, `shipping_phone`, `shipping_address`, `shipping_city`, `shipping_state`, `shipping_zip`)
VALUES
(1, 'ORD-20260101-0001', 4498.00, 0.00, 0.00, 4498.00, 'delivered',
 'John Doe', 'user@example.com', '9876543210', '123 Main Street', 'Mumbai', 'Maharashtra', '400001');

INSERT INTO `order_items` (`order_id`, `product_id`, `name`, `price`, `quantity`, `total`) VALUES
(1, 1, 'Wireless Bluetooth Headphones', 2499.00, 1, 2499.00),
(1, 7, 'Cotton Crew-Neck T-Shirt',       499.00,  1, 499.00),
(1, 16, 'Running Sport Shoes',           1999.00, 1, 1999.00);

-- NOTA BENE: the hashed passwords above were generated with password_hash().
-- If they don't work, run the /sql/hash_passwords.php helper or use the
-- registration form to create fresh accounts.

INSERT INTO `payments` (`order_id`, `payment_method`, `payment_status`, `amount`) VALUES
(1, 'cod', 'completed', 4498.00);

-- Sample reviews
INSERT INTO `reviews` (`user_id`, `product_id`, `rating`, `comment`, `is_approved`) VALUES
(1, 1, 5, 'Amazing sound quality! Best headphones in this price range.', 1),
(1, 6, 4, 'Great jacket, fits perfectly. Material quality is good.', 1),
(1, 11, 5, 'Life-changing book. Highly recommend to everyone.', 1);
