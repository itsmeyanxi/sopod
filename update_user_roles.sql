-- ============================================================
-- Update User Roles - Consolidation Migration
-- Run this SQL on your database to update existing user roles
-- ============================================================
--
-- Role mapping:
--   CSR_Creator + CSR_Approver  →  CSR
--   PR_Creator                  →  Requisitioner
--   PR_Approver                 →  PR_Approver (KEPT SEPARATE - PR approval only)
--   PO_Creator + RFP_Creator    →  Purchasing
--   PO_Approver + RFP_Approver  →  Procurement_Approver (PO + RFP approval)
--
-- Unchanged roles: Admin, IT, CC_Creator, CC_Approver,
--   Delivery_Creator, Delivery_Approver, Accounting_Creator, Accounting_Approver
-- ============================================================

-- STEP 1: Update the legacy 'role' column (single role)
UPDATE users SET role = 'CSR' WHERE role = 'CSR_Creator';
UPDATE users SET role = 'CSR' WHERE role = 'CSR_Approver';
UPDATE users SET role = 'Requisitioner' WHERE role = 'PR_Creator';
-- PR_Approver stays as PR_Approver (no change needed)
UPDATE users SET role = 'Purchasing' WHERE role = 'PO_Creator';
UPDATE users SET role = 'Purchasing' WHERE role = 'RFP_Creator';
UPDATE users SET role = 'Procurement_Approver' WHERE role = 'PO_Approver';
UPDATE users SET role = 'Procurement_Approver' WHERE role = 'RFP_Approver';

-- STEP 2: Update the 'roles' JSON column
-- Replace old role names with new ones in the JSON array
UPDATE users SET roles = REPLACE(roles, '"CSR_Creator"', '"CSR"') WHERE roles LIKE '%CSR_Creator%';
UPDATE users SET roles = REPLACE(roles, '"CSR_Approver"', '"CSR"') WHERE roles LIKE '%CSR_Approver%';
UPDATE users SET roles = REPLACE(roles, '"PR_Creator"', '"Requisitioner"') WHERE roles LIKE '%PR_Creator%';
-- PR_Approver stays as PR_Approver (no change needed)
UPDATE users SET roles = REPLACE(roles, '"PO_Creator"', '"Purchasing"') WHERE roles LIKE '%PO_Creator%';
UPDATE users SET roles = REPLACE(roles, '"PO_Approver"', '"Procurement_Approver"') WHERE roles LIKE '%PO_Approver%';
UPDATE users SET roles = REPLACE(roles, '"RFP_Creator"', '"Purchasing"') WHERE roles LIKE '%RFP_Creator%';
UPDATE users SET roles = REPLACE(roles, '"RFP_Approver"', '"Procurement_Approver"') WHERE roles LIKE '%RFP_Approver%';

-- STEP 3: Clean up duplicate roles in JSON array
-- Fix users who had PO_Creator + RFP_Creator (now have duplicate "Purchasing")
UPDATE users SET roles = REPLACE(roles, '"Purchasing","Purchasing"', '"Purchasing"') WHERE roles LIKE '%"Purchasing","Purchasing"%';
UPDATE users SET roles = REPLACE(roles, '"Purchasing", "Purchasing"', '"Purchasing"') WHERE roles LIKE '%"Purchasing", "Purchasing"%';

-- Fix users who had PO_Approver + RFP_Approver (now have duplicate "Procurement_Approver")
UPDATE users SET roles = REPLACE(roles, '"Procurement_Approver","Procurement_Approver"', '"Procurement_Approver"') WHERE roles LIKE '%"Procurement_Approver","Procurement_Approver"%';
UPDATE users SET roles = REPLACE(roles, '"Procurement_Approver", "Procurement_Approver"', '"Procurement_Approver"') WHERE roles LIKE '%"Procurement_Approver", "Procurement_Approver"%';

-- Fix users who had CSR_Creator + CSR_Approver (now have duplicate "CSR")
UPDATE users SET roles = REPLACE(roles, '"CSR","CSR"', '"CSR"') WHERE roles LIKE '%"CSR","CSR"%';
UPDATE users SET roles = REPLACE(roles, '"CSR", "CSR"', '"CSR"') WHERE roles LIKE '%"CSR", "CSR"%';

-- STEP 4: Verify the update
SELECT id, name, role, roles FROM users ORDER BY name;
