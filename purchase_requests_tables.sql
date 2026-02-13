-- =====================================================
-- Purchase Requests Module - Database Tables
-- Run this SQL in phpMyAdmin to create the tables
-- =====================================================

-- Create purchase_requests table
CREATE TABLE IF NOT EXISTS `purchase_requests` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pr_no` varchar(255) NOT NULL,
  `company` varchar(255) NOT NULL,
  `requisitioner` varchar(255) NOT NULL,
  `department` varchar(255) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `terms` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `date_of_request` date NOT NULL,
  `date_needed` date DEFAULT NULL,
  `type_of_request` varchar(255) DEFAULT NULL,
  `with_budget` varchar(255) DEFAULT NULL,
  `charge_to` varchar(255) DEFAULT NULL,
  `contact_number` varchar(255) DEFAULT NULL,
  `reason_for_requisition` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_requests_pr_no_unique` (`pr_no`),
  KEY `purchase_requests_created_by_foreign` (`created_by`),
  CONSTRAINT `purchase_requests_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create purchase_request_items table
CREATE TABLE IF NOT EXISTS `purchase_request_items` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_request_id` bigint(20) UNSIGNED NOT NULL,
  `item_no` int(11) NOT NULL,
  `qty` decimal(10,2) NOT NULL,
  `uom` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_request_items_purchase_request_id_foreign` (`purchase_request_id`),
  CONSTRAINT `purchase_request_items_purchase_request_id_foreign` FOREIGN KEY (`purchase_request_id`) REFERENCES `purchase_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
