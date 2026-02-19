-- Supplier Receiving Reports Tables
-- Run this SQL on your database to create the tables

CREATE TABLE IF NOT EXISTS `supplier_receiving_reports` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `srr_code` VARCHAR(255) NOT NULL UNIQUE,
    `report_date` DATE NOT NULL,
    `supplier_id` BIGINT UNSIGNED NOT NULL,
    `supplier_name` VARCHAR(255) NOT NULL,
    `cv_no` VARCHAR(255) NULL,
    `po_no` VARCHAR(255) NULL,
    `storage` VARCHAR(255) NULL,
    `report_type` VARCHAR(255) NOT NULL,
    `note` TEXT NULL,
    `prepared_by` VARCHAR(255) NULL,
    `checked_by` VARCHAR(255) NULL,
    `received_by` VARCHAR(255) NULL,
    `verified_by` VARCHAR(255) NULL,
    `status` VARCHAR(255) NOT NULL DEFAULT 'draft',
    `created_by` BIGINT UNSIGNED NULL,
    `approved_by` BIGINT UNSIGNED NULL,
    `approved_at` DATETIME NULL,
    `rejection_reason` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    CONSTRAINT `supplier_receiving_reports_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `supplier_receiving_reports_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `supplier_receiving_reports_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `supplier_receiving_report_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `supplier_receiving_report_id` BIGINT UNSIGNED NOT NULL,
    `item_no` INT NOT NULL,
    `item_code` VARCHAR(255) NULL,
    `item_description` VARCHAR(255) NULL,
    `brand` VARCHAR(255) NULL,
    `no_of_boxes` INT NOT NULL DEFAULT 0,
    `net_weight_pd` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `expiry_date` DATE NULL,
    `pallet_no` VARCHAR(255) NULL,
    `remarks` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    CONSTRAINT `srr_items_srr_id_foreign` FOREIGN KEY (`supplier_receiving_report_id`) REFERENCES `supplier_receiving_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
