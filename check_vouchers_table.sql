-- =====================================================
-- Check Vouchers Module - Database Table
-- Run this SQL in phpMyAdmin to create the table
-- =====================================================

CREATE TABLE IF NOT EXISTS `check_vouchers` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cv_no` varchar(255) NOT NULL,
  `accounts_payable_invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cv_date` date NOT NULL,
  `check_date` date NOT NULL,
  `supplier_code` varchar(255) DEFAULT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `supplier_address` text DEFAULT NULL,
  `supplier_tin` varchar(255) DEFAULT NULL,
  `check_no` varchar(255) DEFAULT NULL,
  `bank` varchar(255) DEFAULT NULL,
  `branch` varchar(255) DEFAULT NULL,
  `check_amount` decimal(15,2) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_type` varchar(255) DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `apv_no` varchar(255) DEFAULT NULL,
  `paid_amount` decimal(15,2) NOT NULL,
  `particulars` text NOT NULL,
  `journal_entries` json DEFAULT NULL,
  `prepared_by` varchar(255) DEFAULT NULL,
  `reviewed_by` varchar(255) DEFAULT NULL,
  `approved_by` varchar(255) DEFAULT NULL,
  `received_by` varchar(255) DEFAULT NULL,
  `date_received` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `check_vouchers_cv_no_unique` (`cv_no`),
  KEY `check_vouchers_accounts_payable_invoice_id_index` (`accounts_payable_invoice_id`),
  KEY `check_vouchers_created_by_index` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
