-- =====================================================
-- CRITICAL FIX: Users Table ID Auto-Increment
-- =====================================================
-- This fixes the "Field 'id' doesn't have a default value" error

ALTER TABLE users MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

-- =====================================================
-- Add Supplier Foreign Keys to Purchase Requests
-- =====================================================

ALTER TABLE purchase_requests
ADD COLUMN supplier_id BIGINT UNSIGNED NULL AFTER department,
ADD CONSTRAINT purchase_requests_supplier_id_foreign
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL;

-- =====================================================
-- Add Supplier Foreign Keys to Purchase Orders
-- =====================================================

ALTER TABLE purchase_orders
ADD COLUMN supplier_id BIGINT UNSIGNED NULL AFTER department,
ADD CONSTRAINT purchase_orders_supplier_id_foreign
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL;

-- =====================================================
-- Add Approval Fields to Purchase Requests
-- =====================================================

ALTER TABLE purchase_requests
ADD COLUMN approved_by BIGINT UNSIGNED NULL AFTER status,
ADD COLUMN approved_at TIMESTAMP NULL AFTER approved_by,
ADD COLUMN rejection_reason TEXT NULL AFTER approved_at,
ADD CONSTRAINT purchase_requests_approved_by_foreign
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL;

-- =====================================================
-- Add Approval Fields to Purchase Orders
-- =====================================================

ALTER TABLE purchase_orders
ADD COLUMN approved_by BIGINT UNSIGNED NULL AFTER status,
ADD COLUMN approved_at TIMESTAMP NULL AFTER approved_by,
ADD COLUMN rejection_reason TEXT NULL AFTER approved_at,
ADD CONSTRAINT purchase_orders_approved_by_foreign
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL;

-- =====================================================
-- Add Approval Fields to Request for Payments
-- =====================================================

ALTER TABLE request_for_payments
ADD COLUMN approved_by BIGINT UNSIGNED NULL AFTER status,
ADD COLUMN approved_at TIMESTAMP NULL AFTER approved_by,
ADD COLUMN rejection_reason TEXT NULL AFTER approved_at,
ADD CONSTRAINT request_for_payments_approved_by_foreign
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL;

-- =====================================================
-- DONE! All changes applied
-- =====================================================
-- After running this script:
-- 1. User creation will work without errors
-- 2. Supplier dropdowns will save to database
-- 3. Approval workflow will be functional
-- =====================================================
