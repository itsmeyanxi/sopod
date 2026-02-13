-- =====================================================
-- Purchase Orders Module - Database Tables
-- Run this SQL in phpMyAdmin to create the tables
-- =====================================================

-- Create purchase_orders table
CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `po_no` varchar(255) NOT NULL,
  `purchase_request_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company` varchar(255) NOT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `supplier_address` text DEFAULT NULL,
  `consignee` varchar(255) DEFAULT NULL,
  `consignee_address` text DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `house` varchar(255) DEFAULT NULL,
  `pr_no` varchar(255) DEFAULT NULL,
  `lc_price` decimal(10,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_orders_po_no_unique` (`po_no`),
  KEY `purchase_orders_purchase_request_id_index` (`purchase_request_id`),
  KEY `purchase_orders_created_by_index` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create purchase_order_items table
CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) UNSIGNED NOT NULL,
  `item_no` int(11) NOT NULL,
  `item_code` varchar(255) DEFAULT NULL,
  `qty` decimal(10,2) NOT NULL,
  `uom` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `tax` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_items_purchase_order_id_index` (`purchase_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
