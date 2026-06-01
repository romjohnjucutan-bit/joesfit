-- Joe's Fit Database Schema
-- Import this file in phpMyAdmin

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+08:00";

CREATE DATABASE IF NOT EXISTS `joesfit` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `joesfit`;

-- Categories
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `sku` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `images` text DEFAULT NULL,
  `sizes` varchar(255) DEFAULT NULL,
  `colors` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Staff/Admin Users
CREATE TABLE `staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') DEFAULT 'staff',
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Customers
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tracking_code` varchar(20) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(150) NOT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `shipping_address` text NOT NULL,
  `shipping_city` varchar(100) NOT NULL,
  `shipping_province` varchar(100) NOT NULL,
  `shipping_zip` varchar(10) DEFAULT NULL,
  `payment_method` enum('cod','gcash','maya','card') DEFAULT 'cod',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `delivery_method` enum('standard','express','pickup') DEFAULT 'standard',
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled','returned') DEFAULT 'pending',
  `subtotal` decimal(10,2) NOT NULL,
  `shipping_fee` decimal(10,2) DEFAULT 0,
  `discount` decimal(10,2) DEFAULT 0,
  `total` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tracking_code` (`tracking_code`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order Items
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `size` varchar(20) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order Status History
CREATE TABLE `order_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `note` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reviews
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `rating` tinyint(1) NOT NULL DEFAULT 5,
  `title` varchar(200) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('new_order','low_stock','review','payment','other') DEFAULT 'other',
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Coupons
CREATE TABLE `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `type` enum('percent','fixed') DEFAULT 'percent',
  `value` decimal(10,2) NOT NULL,
  `min_order` decimal(10,2) DEFAULT 0,
  `max_uses` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `expires_at` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

INSERT INTO `categories` (`name`, `slug`, `description`, `is_active`) VALUES
('Varsity Jackets', 'varsity-jackets', 'Classic American collegiate style', 1),
('Bomber Jackets', 'bomber-jackets', 'Sleek and sporty bomber styles', 1),
('Windbreakers', 'windbreakers', 'Lightweight weather-resistant jackets', 1),
('Hooded Jackets', 'hooded-jackets', 'Casual hooded styles for everyday wear', 1),
('Leather Jackets', 'leather-jackets', 'Premium leather and faux leather styles', 1);

INSERT INTO `staff` (`name`, `email`, `password`, `role`, `is_active`) VALUES
('Joe Admin', 'admin@joesfit.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1),
('Staff One', 'staff@joesfit.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 1);
-- Default password for both: password

INSERT INTO `products` (`category_id`, `name`, `slug`, `description`, `price`, `sale_price`, `stock`, `sku`, `sizes`, `colors`, `is_featured`, `is_active`) VALUES
(1, 'Classic Black Varsity', 'classic-black-varsity', 'Timeless black varsity jacket with white leather sleeves, ribbed cuffs, and embroidered chest logo. Made with premium wool blend.', 2499.00, 1999.00, 50, 'JF-VAR-001', 'S,M,L,XL,XXL', 'Black/White,Navy/White,Red/White', 1, 1),
(1, 'Gold Edition Varsity', 'gold-edition-varsity', 'Premium varsity jacket featuring luxurious gold accents and satin lining. A statement piece for the bold.', 3299.00, NULL, 30, 'JF-VAR-002', 'S,M,L,XL', 'Black/Gold,Navy/Gold', 1, 1),
(2, 'Alpha Bomber MA-1', 'alpha-bomber-ma1', 'Military-inspired MA-1 bomber jacket in nylon with knit collar and cuffs. Classic orange lining inside.', 1899.00, 1599.00, 75, 'JF-BOM-001', 'S,M,L,XL,XXL', 'Olive,Black,Navy,Khaki', 1, 1),
(2, 'Satin Bomber Luxe', 'satin-bomber-luxe', 'Sophisticated satin bomber with floral embroidery on the back. Street-meets-luxury aesthetic.', 2799.00, NULL, 25, 'JF-BOM-002', 'S,M,L,XL', 'Black,Champagne,Burgundy', 1, 1),
(3, 'Storm Windbreaker', 'storm-windbreaker', 'Lightweight packable windbreaker with taped seams and adjustable hood. Perfect for active lifestyles.', 1499.00, 1199.00, 100, 'JF-WIN-001', 'S,M,L,XL,XXL', 'Black,Electric Blue,Neon Green,Red', 0, 1),
(3, 'Urban Shell Jacket', 'urban-shell-jacket', 'Technical shell jacket designed for city explorers. Water-resistant and breathable.', 1799.00, NULL, 60, 'JF-WIN-002', 'S,M,L,XL', 'Stealth Black,Concrete Gray,Forest', 0, 1),
(4, 'Essential Hoodie Jacket', 'essential-hoodie-jacket', 'Cozy fleece-lined hoodie jacket with kangaroo pocket and full-zip closure. Weekend essential.', 1299.00, 999.00, 120, 'JF-HOD-001', 'S,M,L,XL,XXL,3XL', 'Black,Heather Gray,Navy,Burgundy', 0, 1),
(5, 'Moto Faux Leather', 'moto-faux-leather', 'Edgy moto jacket in vegan faux leather with asymmetric zipper, zippered pockets, and buckle details.', 2999.00, NULL, 40, 'JF-LEA-001', 'XS,S,M,L,XL', 'Jet Black,Dark Brown,Deep Red', 1, 1);

INSERT INTO `coupons` (`code`, `type`, `value`, `min_order`, `max_uses`, `is_active`) VALUES
('JOESFIT10', 'percent', 10.00, 500.00, 100, 1),
('WELCOME20', 'percent', 20.00, 1000.00, 50, 1),
('FLAT200', 'fixed', 200.00, 1500.00, 30, 1);

-- Sample orders
INSERT INTO `orders` (`tracking_code`, `customer_name`, `customer_email`, `customer_phone`, `shipping_address`, `shipping_city`, `shipping_province`, `shipping_zip`, `payment_method`, `payment_status`, `delivery_method`, `status`, `subtotal`, `shipping_fee`, `total`) VALUES
('JF-20240001', 'Maria Santos', 'maria@example.com', '09171234567', '123 Rizal Street, Barangay 1', 'Quezon City', 'Metro Manila', '1100', 'gcash', 'paid', 'standard', 'delivered', 1999.00, 150.00, 2149.00),
('JF-20240002', 'Juan dela Cruz', 'juan@example.com', '09281234567', '456 Mabini Ave', 'Manila', 'Metro Manila', '1000', 'cod', 'pending', 'standard', 'shipped', 2799.00, 150.00, 2949.00),
('JF-20240003', 'Ana Reyes', 'ana@example.com', '09391234567', '789 Bonifacio St', 'Makati', 'Metro Manila', '1200', 'maya', 'paid', 'express', 'processing', 3299.00, 250.00, 3549.00);

INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `size`, `color`, `quantity`, `price`, `subtotal`) VALUES
(1, 1, 'Classic Black Varsity', 'M', 'Black/White', 1, 1999.00, 1999.00),
(2, 4, 'Satin Bomber Luxe', 'L', 'Black', 1, 2799.00, 2799.00),
(3, 2, 'Gold Edition Varsity', 'M', 'Black/Gold', 1, 3299.00, 3299.00);

INSERT INTO `order_history` (`order_id`, `status`, `note`) VALUES
(1, 'pending', 'Order placed'),
(1, 'confirmed', 'Payment verified via GCash'),
(1, 'processing', 'Being prepared for shipping'),
(1, 'shipped', 'Handed to courier'),
(1, 'delivered', 'Package delivered'),
(2, 'pending', 'Order placed'),
(2, 'confirmed', 'Order confirmed'),
(2, 'shipped', 'Out for delivery'),
(3, 'pending', 'Order placed'),
(3, 'confirmed', 'Payment verified via Maya'),
(3, 'processing', 'Being prepared');

INSERT INTO `reviews` (`product_id`, `order_id`, `customer_name`, `rating`, `title`, `body`, `is_approved`) VALUES
(1, 1, 'Maria Santos', 5, 'Perfect fit, love the quality!', 'The jacket exceeded my expectations. The wool blend feels premium and the white leather sleeves are spotless. Highly recommend!', 1);

INSERT INTO `notifications` (`type`, `title`, `message`, `link`, `is_read`) VALUES
('new_order', 'New Order #JF-20240003', 'Ana Reyes placed a new order worth ₱3,549.00', '/joesfit/admin/pages/orders.php?id=3', 0),
('low_stock', 'Low Stock Alert: Satin Bomber Luxe', 'Only 25 units remaining in stock', '/joesfit/admin/pages/products.php', 0),
('review', 'New Review on Classic Black Varsity', 'Maria Santos left a 5-star review', '/joesfit/admin/pages/reviews.php', 1);
