-- Non-Trade Items Table
-- Run this SQL on your database to create the table
-- These are items used in Purchase Requests (non-trade / buying side)

CREATE TABLE IF NOT EXISTS `non_trade_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(500) NOT NULL,
    `unit` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `non_trade_items_name_unique` (`name`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
