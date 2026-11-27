-- ============================================================
-- ASHBAB CAFE - Database Schema
-- File: database/ashbab_cafe.sql
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

CREATE DATABASE IF NOT EXISTS `ashbab_cafe` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ashbab_cafe`;

-- ============================================================
-- Table: users
-- ============================================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','cashier') NOT NULL DEFAULT 'cashier',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: categories
-- ============================================================
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon` varchar(100) DEFAULT 'fa-utensils',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: menus
-- ============================================================
CREATE TABLE `menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('available','unavailable') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `fk_menu_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: orders
-- ============================================================
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(30) NOT NULL UNIQUE,
  `customer_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `table_number` varchar(10) DEFAULT NULL,
  `notes` text,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('transfer','qris') NOT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `payment_status` enum('pending','confirmed','rejected') NOT NULL DEFAULT 'pending',
  `order_status` enum('waiting','accepted','processing','completed','cancelled') NOT NULL DEFAULT 'waiting',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: order_items
-- ============================================================
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `menu_id` (`menu_id`),
  CONSTRAINT `fk_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: expenses
-- ============================================================
CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `category` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text,
  `expense_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: settings
-- ============================================================
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cafe_name` varchar(100) NOT NULL DEFAULT 'ASHBAB CAFE',
  `logo` varchar(255) DEFAULT NULL,
  `favicon` varchar(255) DEFAULT NULL,
  `banner` text DEFAULT NULL,
  `address` text,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `qris_image` varchar(255) DEFAULT NULL,
  `bank_account` text,
  `footer_text` text,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `about_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: notifications
-- ============================================================
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `target` enum('admin','customer') NOT NULL DEFAULT 'admin',
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read') NOT NULL DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Seed Data
-- ============================================================

-- Default Admin User (password: admin123)
-- Default password: 'password' (change immediately after first login!)
INSERT INTO `users` (`name`, `username`, `password`, `role`) VALUES
('Super Admin', 'admin', '$2b$12$Y8jnXlbNe4.e9Um9aecGeulSea8LQUmGzl6euMuXnqcxRYK3u0Aj2', 'admin'),
('Kasir Utama', 'kasir', '$2b$12$Y8jnXlbNe4.e9Um9aecGeulSea8LQUmGzl6euMuXnqcxRYK3u0Aj2', 'cashier');

-- Default Categories
INSERT INTO `categories` (`name`, `icon`) VALUES
('Makanan', 'fa-utensils'),
('Minuman', 'fa-glass-water'),
('Coffee', 'fa-mug-hot'),
('Non Coffee', 'fa-cup-straw'),
('Snack', 'fa-cookie-bite'),
('Dessert', 'fa-ice-cream');

-- Default Settings
INSERT INTO `settings` (`cafe_name`, `address`, `phone`, `email`, `footer_text`, `tax_percent`) VALUES
('ASHBAB CAFE', 'Jl. Contoh No. 123, Jakarta', '08123456789', 'ashbabcafe@email.com', '© 2026 ASHBAB CAFE. All Rights Reserved.', 10.00);

-- Sample Menus
INSERT INTO `menus` (`category_id`, `name`, `description`, `price`, `stock`, `status`) VALUES
(3, 'Espresso', 'Kopi espresso murni yang kuat dan nikmat', 18000, 50, 'available'),
(3, 'Americano', 'Espresso dengan tambahan air panas yang menyegarkan', 22000, 50, 'available'),
(3, 'Cappuccino', 'Espresso dengan susu steam dan foam yang creamy', 28000, 50, 'available'),
(3, 'Latte', 'Espresso dengan susu steam yang lembut dan creamy', 30000, 50, 'available'),
(3, 'Caramel Macchiato', 'Latte dengan saus karamel yang manis dan menggoda', 35000, 50, 'available'),
(4, 'Matcha Latte', 'Teh hijau matcha premium dengan susu yang creamy', 32000, 30, 'available'),
(4, 'Chocolate', 'Cokelat panas yang kaya dan menghangatkan', 28000, 30, 'available'),
(4, 'Strawberry Juice', 'Jus stroberi segar dengan rasa yang manis asam', 25000, 20, 'available'),
(1, 'Nasi Goreng Spesial', 'Nasi goreng dengan bumbu spesial, telur, dan ayam', 35000, 20, 'available'),
(1, 'Mie Goreng', 'Mie goreng dengan bumbu khas dan topping lengkap', 30000, 20, 'available'),
(5, 'Kentang Goreng', 'Kentang goreng crispy dengan saus pilihan', 20000, 30, 'available'),
(5, 'Onion Ring', 'Onion ring crispy yang renyah dan gurih', 22000, 25, 'available'),
(6, 'Es Krim', 'Es krim premium dengan berbagai pilihan rasa', 25000, 15, 'available'),
(6, 'Pudding Cokelat', 'Pudding cokelat lembut dengan saus karamel', 18000, 20, 'available');

COMMIT;
