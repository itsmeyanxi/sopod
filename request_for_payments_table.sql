-- =====================================================
-- Request for Payments Module - Database Table
-- Run this SQL in phpMyAdmin to create the table
-- =====================================================

CREATE TABLE IF NOT EXISTS `request_for_payments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `rfp_no` varchar(255) NOT NULL,
  `purchase_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company` varchar(255) NOT NULL,
  `payment_methods` json DEFAULT NULL,
  `date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `payee` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `particulars` text DEFAULT NULL,
  `bank` varchar(255) DEFAULT NULL,
  `apv_no` varchar(255) DEFAULT NULL,
  `cv_no` varchar(255) DEFAULT NULL,
  `requested_by` varchar(255) DEFAULT NULL,
  `checked_by` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_for_payments_rfp_no_unique` (`rfp_no`),
  KEY `request_for_payments_purchase_order_id_index` (`purchase_order_id`),
  KEY `request_for_payments_created_by_index` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
