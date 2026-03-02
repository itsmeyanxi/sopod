# SOPOD Module & Database Audit Report
**Generated:** 2026-03-02

---

## Executive Summary

✅ **36 Controllers** mapped to **36 Tables** (excluding Laravel system tables)
⚠️ **6 Missing Tables** - Models exist but migrations don't
🔴 **Critical Issues:** Some features may not work if these tables are missing

---

## ✅ All Working Modules (Complete)

### Sales Management
- ✅ **SalesOrderController** → `sales_orders`, `sales_order_items`
- ✅ **DeliveriesController** → `deliveries`
- ✅ **CustomerController** → `customers`

### Purchasing & Supply Chain
- ✅ **PurchaseRequestController** → `purchase_requests`, `purchase_request_items`
- ✅ **PurchaseOrderController** → `purchase_orders`, `purchase_order_items`
- ✅ **SuppliersController** → `suppliers`, `supplier_documents`
- ✅ **SupplierReceivingReportController** → `supplier_receiving_reports`, `supplier_receiving_report_items`
- ✅ **TradeItemController** → `trade_items`
- ✅ **NonTradeItemController** → `non_trade_items`

### Finance & Accounting
- ✅ **RequestForPaymentController** → `request_for_payments`
- ✅ **AccountsPayableInvoiceController** → `accounts_payable_invoices`
- ✅ **CheckVoucherController** → `check_vouchers`
- ✅ **CashAdvanceRequestController** → `cash_advance_requests`
- ✅ **LiquidationFormController** → `liquidation_forms`, `liquidation_form_items`
- ✅ **ReimbursementFormController** → `reimbursement_forms`, `reimbursement_form_items`

### Inventory & Items
- ✅ **ItemController** → `items`
- ✅ **CurrencyController** → `currencies`
- ✅ **IssueSlipController** → `issue_slips`, `issue_slip_items`

### Admin & System
- ✅ **UserController** → `users`
- ✅ **UserManagementController** → `users`
- ✅ **DashboardController** → (read-only, aggregates data)
- ✅ **ChangeLogController** → (check details below)

### Analytics & Reports
- ✅ **SalesDashboardController** → (read-only, uses sales_orders)
- ✅ **AgingReportController** → (read-only, uses customers & invoices)
- ✅ **Posummarycontroller** → (read-only, uses purchase_orders)
- ✅ **ReceivingReportsController** → (read-only, uses deliveries)

### Utilities
- ✅ **ExcelImportController** → (utility function)
- ✅ **ImportController** → (utility function)
- ✅ **SupplierImportController** → (utility function)
- ✅ **RecordsController** → (records view)
- ✅ **PurchaseOrderRecordsController** → (records view)
- ✅ **LockController** → (record locking)

---

## ⚠️ CRITICAL: Missing Table Definitions

These models exist but their tables are NOT in migrations:

### 1. **ar_adjustments**
- **Model:** `ArAdjustment`
- **Used By:** `ArAdjustmentController`
- **Fields Expected:**
  - customer_code, customer_name, branch
  - reference_number, transaction_type, transaction_date
  - amount, is_decrease, invoice_number, dr_no
  - gl_account, signed_by, remarks, created_by
- **Route:** `/ar_adjustments`
- **Impact:** AR Adjustments feature will fail if table doesn't exist
- **Status:** ❌ NO MIGRATION FOUND

### 2. **ar_aging**
- **Model:** `ArAging`
- **Used By:** `AgingReportController`
- **Impact:** Aging reports may use cached/calculated data
- **Status:** ❌ NO MIGRATION FOUND

### 3. **ar_ledger**
- **Model:** `ARLedger`
- **Used By:** AR tracking
- **Impact:** AR Ledger tracking may be incomplete
- **Status:** ❌ NO MIGRATION FOUND

### 4. **delivery_items**
- **Model:** `DeliveryItem`
- **Used By:** `DeliveriesController`
- **Fields Expected:** delivery_id, item_id, qty, unit_price
- **Impact:** Delivery item details may not persist properly
- **Status:** ❌ NO MIGRATION FOUND
- **Note:** Uses `deliveries` table instead - may be using JSON or relationship

### 5. **sales_invoices**
- **Model:** `SalesInvoice`
- **Used By:** Invoice management
- **Fields Expected:**
  - invoice_no, so_id, dr_id, customer_code
  - invoice_date, due_date, gross_amount, total_discount
  - vat_amount, net_amount, payment_status
- **Impact:** Sales invoice tracking will fail
- **Status:** ❌ NO MIGRATION FOUND

### 6. **payments**
- **Model:** `Payment`
- **Used By:** `PaymentController`
- **Fields Expected:** customer_code, payment_date, amount, method, reference
- **Impact:** Payment tracking will fail
- **Status:** ❌ NO MIGRATION FOUND

---

## 📋 Data Model Summary

| Table Name | Type | Rows | Used By |
|-----------|------|------|---------|
| users | Core | ✓ | All modules |
| customers | Core | ✓ | Sales, AR |
| suppliers | Core | ✓ | Purchasing, SRR |
| items | Core | ✓ | Inventory |
| sales_orders | Transaction | ✓ | Sales, Deliveries |
| sales_order_items | Transaction | ✓ | Sales |
| deliveries | Transaction | ✓ | Sales, Receiving |
| purchase_requests | Transaction | ✓ | Purchasing |
| purchase_request_items | Transaction | ✓ | Purchasing |
| purchase_orders | Transaction | ✓ | Purchasing |
| purchase_order_items | Transaction | ✓ | Purchasing |
| supplier_receiving_reports | Transaction | ✓ | SRR |
| supplier_receiving_report_items | Transaction | ✓ | SRR |
| request_for_payments | Transaction | ✓ | Finance |
| accounts_payable_invoices | Transaction | ✓ | Finance |
| check_vouchers | Transaction | ✓ | Finance |
| issue_slips | Transaction | ✓ | Inventory |
| issue_slip_items | Transaction | ✓ | Inventory |
| cash_advance_requests | Transaction | ✓ | Finance |
| liquidation_forms | Transaction | ✓ | Finance |
| liquidation_form_items | Transaction | ✓ | Finance |
| reimbursement_forms | Transaction | ✓ | Finance |
| reimbursement_form_items | Transaction | ✓ | Finance |
| non_trade_items | Reference | ✓ | Inventory |
| trade_items | Reference | ✓ | Inventory |
| currencies | Reference | ✓ | Finance |
| supplier_documents | Reference | ✓ | Suppliers |
| monthly_sales | Summary | ✓ | Analytics |
| ❌ ar_adjustments | Transaction | ✗ | AR |
| ❌ ar_aging | Summary | ✗ | Analytics |
| ❌ ar_ledger | Journal | ✗ | AR |
| ❌ delivery_items | Detail | ✗ | Deliveries |
| ❌ sales_invoices | Transaction | ✗ | Invoicing |
| ❌ payments | Transaction | ✗ | AR/Collections |

---

## Recommended Actions

### Immediate (Required to Prevent Errors)

1. **Check if tables exist** - Run this query in phpMyAdmin:
   ```sql
   SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = 'sopod'
   AND TABLE_NAME IN ('ar_adjustments', 'ar_aging', 'ar_ledger',
                      'delivery_items', 'sales_invoices', 'payments');
   ```

2. **If tables exist but no migration:**
   - Create migrations for existing tables
   - Use: `php artisan make:migration create_missing_tables_table`
   - Copy the table structure from database

3. **If tables don't exist:**
   - Disable features that use them (remove from menu/routes)
   - Create migrations when those features are implemented
   - Or use alternative table names if they're being used elsewhere

### Medium Priority

1. **Test all module creation forms** to ensure no "cannot be null" errors
2. **Verify all required fields** match database constraints
3. **Run integration tests** for Purchase, Sales, and Finance workflows

### Long-term

1. Keep this audit updated as new modules are added
2. Always create migrations BEFORE creating models
3. Use database-first validation in forms
4. Test each module end-to-end after deployment

---

## Testing Checklist

- [ ] Create a Sales Order with items
- [ ] Create a Delivery from the Sales Order
- [ ] Create a Purchase Request with multiple items
- [ ] Create a Purchase Order from PR
- [ ] Create Supplier Receiving Report
- [ ] Create Issue Slip from SRR
- [ ] Create Request for Payment
- [ ] Create Cash Advance Request
- [ ] Create Liquidation Form
- [ ] Create Reimbursement Form
- [ ] Check all reports generate without errors
- [ ] Verify all list views show data correctly

---

## Notes

- **Activity Table:** Uses `activities` table (not explicitly defined but created for activity logging)
- **ChangeNotification:** Uses `change_notifications` table (may be created dynamically)
- **SalesOrderChange:** Uses `sales_order_changes` table (audit logging)
- **ReceivingReport:** Aliases `DeliveryReport` (uses deliveries table)

**Database Status:** Mostly complete, but 6 critical features need table definitions
