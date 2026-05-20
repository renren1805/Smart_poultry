-- Complete Database Schema for Poultry Shop Ordering System
-- This file contains all tables needed for a fully functional e-commerce platform

-- Drop existing tables for fresh setup
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `wishlist`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `order_status_history`;
DROP TABLE IF EXISTS `shopping_cart`;
DROP TABLE IF EXISTS `stock_movements`;
DROP TABLE IF EXISTS `security_logs`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `inventory_items`;
DROP TABLE IF EXISTS `settings`;

-- 1. Admins Table
CREATE TABLE `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'manager', 'staff') DEFAULT 'admin',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Customers Table
CREATE TABLE `customers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `fullname` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `phone` VARCHAR(50) NOT NULL,
    `address` TEXT NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `profile_image` VARCHAR(500) DEFAULT NULL,
    `date_of_birth` DATE DEFAULT NULL,
    `gender` ENUM('male', 'female', 'other') DEFAULT NULL,
    `verification_token` VARCHAR(255) DEFAULT NULL,
    `email_verified` BOOLEAN DEFAULT FALSE,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `status` ENUM('active', 'inactive', 'pending') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Inventory Items Table
CREATE TABLE `inventory_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `category` VARCHAR(100) DEFAULT 'General',
    `current_quantity` INT DEFAULT 0,
    `min_stock` INT DEFAULT 10,
    `price` DECIMAL(10,2) NOT NULL,
    `selling_price` DECIMAL(10,2) DEFAULT 0.00,
    `unit` VARCHAR(50) DEFAULT 'pcs',
    `expiry_date` DATE DEFAULT NULL,
    `supplier` VARCHAR(255) DEFAULT NULL,
    `barcode` VARCHAR(100) DEFAULT NULL,
    `image_path` VARCHAR(500) DEFAULT NULL,
    `status` ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_name` (`name`),
    INDEX `idx_category` (`category`),
    INDEX `idx_status` (`status`),
    INDEX `idx_quantity` (`current_quantity`),
    INDEX `idx_barcode` (`barcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Orders Table
CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `order_number` VARCHAR(100) NOT NULL UNIQUE,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `status` ENUM('Draft', 'Pending Payment', 'Pending Approval', 'Approved', 'Processed', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Draft',
    `payment_method` ENUM('cod', 'gcash', 'card', 'bank_transfer') DEFAULT 'cod',
    `payment_reference` VARCHAR(255) DEFAULT NULL,
    `payment_amount` DECIMAL(10,2) DEFAULT NULL,
    `payment_date` TIMESTAMP NULL DEFAULT NULL,
    `tracking_number` VARCHAR(255) DEFAULT NULL,
    `shipping_date` TIMESTAMP NULL DEFAULT NULL,
    `actual_delivery` TIMESTAMP NULL DEFAULT NULL,
    `delivery_address` TEXT NOT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL,
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_order_number` (`order_number`),
    INDEX `idx_status` (`status`),
    INDEX `idx_payment_method` (`payment_method`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Order Items Table
CREATE TABLE `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `total_price` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `inventory_items`(`id`) ON DELETE RESTRICT,
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Shopping Cart Table (Persistent Cart)
CREATE TABLE `shopping_cart` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `inventory_items`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_cart_item` (`customer_id`, `product_id`),
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Stock Movements Table
CREATE TABLE `stock_movements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `movement_type` ENUM('in', 'out', 'adjustment') NOT NULL,
    `quantity` INT NOT NULL,
    `reference` VARCHAR(255) DEFAULT NULL,
    `user_name` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`product_id`) REFERENCES `inventory_items`(`id`) ON DELETE CASCADE,
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_movement_type` (`movement_type`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Order Status History Table
CREATE TABLE `order_status_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `status` VARCHAR(50) NOT NULL,
    `notes` TEXT DEFAULT NULL,
    `changed_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`changed_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL,
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Security Logs Table
CREATE TABLE `security_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_type` VARCHAR(50) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `user_id` INT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
    INDEX `idx_event_type` (`event_type`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Notifications Table
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `admin_id` INT DEFAULT NULL,
    `type` ENUM('order_status', 'low_stock', 'payment_received', 'new_order', 'security_alert') NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_admin_id` (`admin_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_is_read` (`is_read`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Settings Table
CREATE TABLE `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Reviews Table
CREATE TABLE `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `customer_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
    `review_text` TEXT DEFAULT NULL,
    `is_approved` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `inventory_items`(`id`) ON DELETE CASCADE,
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Wishlist Table
CREATE TABLE `wishlist` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `inventory_items`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_wishlist_item` (`customer_id`, `product_id`),
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Admin User
INSERT INTO `admins` (`name`, `email`, `password`, `role`, `status`) VALUES 
('Administrator', 'admin@poultryshop.com', '$2y$10$abcdefghijklmnopqrstuv.ABCDEF.GHIJKLMNOPQRSTUVWXYZabcdef0123456789', 'admin', 'active');

-- Insert Default Settings
INSERT INTO `settings` (`key`, `value`, `description`) VALUES 
('site_name', 'Poultry Shop', 'Website name'),
('site_email', 'orders@poultryshop.com', 'Contact email'),
('currency', 'PHP', 'Default currency'),
('tax_rate', '0.12', 'Tax rate as decimal'),
('shipping_fee', '50.00', 'Default shipping fee'),
('min_order_amount', '100.00', 'Minimum order amount'),
('enable_cod', '1', 'Enable cash on delivery'),
('enable_gcash', '1', 'Enable GCash payments'),
('enable_card', '1', 'Enable card payments'),
('low_stock_threshold', '10', 'Alert when stock below this level'),
('session_timeout', '1800', 'Session timeout in seconds'),
('max_cart_items', '20', 'Maximum items allowed in cart'),
('enable_reviews', '1', 'Enable product reviews'),
('require_email_verification', '0', 'Require email verification for registration'),
('maintenance_mode', '0', 'Website maintenance mode');

-- Insert Sample Inventory Items
INSERT INTO `inventory_items` (`name`, `description`, `category`, `current_quantity`, `min_stock`, `price`, `selling_price`, `unit`, `image_path`, `status`) VALUES 
('Fresh Chicken', 'Premium quality fresh chicken, perfect for grilling and frying', 'Poultry', 100, 10, 150.00, 150.00, 'kg', 'uploads/inventory/2024/05/chicken.jpg', 'active'),
('Organic Eggs', 'Farm-fresh organic eggs from free-range chickens', 'Poultry', 200, 50, 80.50, 80.50, 'dozen', 'uploads/inventory/2024/05/eggs.jpg', 'active'),
('Chicken Breast', 'Boneless chicken breast, lean and tender', 'Poultry', 75, 15, 200.00, 200.00, 'kg', 'uploads/inventory/2024/05/breast.jpg', 'active'),
('Chicken Wings', 'Classic chicken wings, perfect for parties', 'Poultry', 150, 30, 120.00, 120.00, 'kg', 'uploads/inventory/2024/05/wings.jpg', 'active'),
('Whole Chicken', 'Whole dressed chicken, ready for roasting', 'Poultry', 50, 10, 250.00, 250.00, 'piece', 'uploads/inventory/2024/05/whole.jpg', 'active'),
('Chicken Thighs', 'Boneless chicken thighs, juicy and flavorful', 'Poultry', 80, 20, 180.00, 180.00, 'kg', 'uploads/inventory/2024/05/thighs.jpg', 'active'),
('Drumsticks', 'Chicken drumsticks with skin, great for baking', 'Poultry', 120, 25, 160.00, 160.00, 'kg', 'uploads/inventory/2024/05/drumsticks.jpg', 'active'),
('Chicken Liver', 'Fresh chicken liver, nutrient-rich', 'Poultry', 60, 10, 90.00, 90.00, 'kg', 'uploads/inventory/2024/05/liver.jpg', 'active'),
('Chicken Feet', 'Cleaned chicken feet, perfect for soup', 'Poultry', 90, 15, 70.00, 70.00, 'kg', 'uploads/inventory/2024/05/feet.jpg', 'active'),
('Mixed Chicken Parts', 'Assorted chicken parts pack', 'Poultry', 40, 10, 220.00, 220.00, 'kg', 'uploads/inventory/2024/05/mixed.jpg', 'active');

-- Insert Sample Customers
INSERT INTO `customers` (`fullname`, `email`, `phone`, `address`, `password`, `status`) VALUES 
('John Doe', 'john@example.com', '+639123456789', '123 Main Street, Manila, Philippines', '$2y$10$abcdefghijklmnopqrstuv.ABCDEF.GHIJKLMNOPQRSTUVWXYZabcdef0123456789', 'active'),
('Jane Smith', 'jane@example.com', '+639987654321', '456 Oak Avenue, Quezon City, Philippines', '$2y$10$abcdefghijklmnopqrstuv.ABCDEF.GHIJKLMNOPQRSTUVWXYZabcdef0123456789', 'active');

-- Create Database Triggers for Automation
DELIMITER //

-- Trigger to calculate order total when items are added
CREATE TRIGGER `calculate_order_total` 
AFTER INSERT ON `order_items`
FOR EACH ROW
BEGIN
    UPDATE `orders` 
    SET `total_amount` = (
        SELECT COALESCE(SUM(`total_price`), 0) 
        FROM `order_items` 
        WHERE `order_id` = NEW.`order_id`
    )
    WHERE `id` = NEW.`order_id`;
END//

-- Trigger to update order total when items are updated
CREATE TRIGGER `update_order_total` 
AFTER UPDATE ON `order_items`
FOR EACH ROW
BEGIN
    UPDATE `orders` 
    SET `total_amount` = (
        SELECT COALESCE(SUM(`total_price`), 0) 
        FROM `order_items` 
        WHERE `order_id` = NEW.`order_id`
    )
    WHERE `id` = NEW.`order_id`;
END//

-- Trigger to log order status changes
CREATE TRIGGER `log_order_status_change` 
AFTER UPDATE ON `orders`
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO `order_status_history` (order_id, status, notes, changed_by, created_at)
        VALUES (NEW.id, NEW.status, CONCAT('Status changed from ', OLD.status, ' to ', NEW.status), NEW.created_by, NOW());
    END IF;
END//

-- Trigger to update inventory on order delivery
CREATE TRIGGER `update_inventory_on_delivery` 
AFTER UPDATE ON `orders`
FOR EACH ROW
BEGIN
    IF OLD.status != 'Delivered' AND NEW.status = 'Delivered' THEN
        -- Update inventory based on order items
        UPDATE inventory_items i
        SET i.current_quantity = i.current_quantity - oi.quantity,
            i.updated_at = NOW()
        WHERE i.id IN (
            SELECT product_id FROM order_items oi WHERE oi.order_id = NEW.id
        );
        
        -- Log stock movements
        INSERT INTO stock_movements (product_id, product_name, movement_type, quantity, reference, user_name, created_at)
        SELECT oi.product_id, i.name, 'out', oi.quantity, CONCAT('Order Delivered #', NEW.id), c.fullname, NOW()
        FROM order_items oi
        JOIN inventory_items i ON oi.product_id = i.id
        JOIN customers c ON NEW.customer_id = c.id
        WHERE oi.order_id = NEW.id;
    END IF;
END//

DELIMITER ;

-- Create Database Views for Enhanced Reporting
CREATE VIEW `order_summary_enhanced` AS
SELECT 
    o.id,
    o.order_number,
    o.customer_id,
    c.fullname as customer_name,
    c.email as customer_email,
    o.total_amount,
    o.status,
    o.payment_method,
    o.payment_reference,
    o.tracking_number,
    o.delivery_address,
    o.notes,
    o.created_at,
    o.shipping_date,
    o.actual_delivery,
    COUNT(oi.id) as item_count,
    SUM(oi.total_price) as calculated_total,
    CASE 
        WHEN o.status = 'Delivered' THEN DATEDIFF(o.actual_delivery, o.created_at)
        ELSE NULL
    END as delivery_days
FROM orders o
LEFT JOIN customers c ON o.customer_id = c.id
LEFT JOIN order_items oi ON o.id = oi.order_id
GROUP BY o.id;

CREATE VIEW `inventory_summary_enhanced` AS
SELECT 
    i.id,
    i.name,
    i.category,
    i.current_quantity,
    i.min_stock,
    i.selling_price,
    i.unit,
    i.expiry_date,
    i.status,
    CASE 
        WHEN i.current_quantity <= 0 THEN 'Out of Stock'
        WHEN i.current_quantity <= i.min_stock THEN 'Low Stock'
        ELSE 'In Stock'
    END as stock_status,
    (SELECT COUNT(*) FROM order_items WHERE product_id = i.id) as order_count,
    (SELECT AVG(rating) FROM reviews WHERE product_id = i.id AND is_approved = TRUE) as avg_rating,
    (SELECT COUNT(*) FROM reviews WHERE product_id = i.id AND is_approved = TRUE) as review_count,
    DATEDIFF(i.expiry_date, CURDATE()) as days_to_expiry
FROM inventory_items i;

CREATE VIEW `customer_activity` AS
SELECT 
    c.id,
    c.fullname,
    c.email,
    COUNT(o.id) as total_orders,
    SUM(o.total_amount) as total_spent,
    MAX(o.created_at) as last_order_date,
    c.last_login,
    c.created_at as customer_since
FROM customers c
LEFT JOIN orders o ON c.id = o.customer_id
GROUP BY c.id;

-- Success Message
SELECT 'Complete database schema created successfully!' as message;
SELECT 'All tables, indexes, triggers, and views have been created.' as status;
SELECT 'Your poultry shop now has a complete e-commerce database structure.' as info;
