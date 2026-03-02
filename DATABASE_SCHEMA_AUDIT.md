# SOPOD Database Schema Audit

**Last Updated:** 2026-03-02

## Critical Required Fields (Non-Nullable)

### Purchase Request Items Table
- ✅ **qty** - REQUIRED (decimal)
- ✅ **uom** - REQUIRED (string) - **FIXED: Was nullable but DB requires it**
- ✅ **description** - REQUIRED (text)
- ✅ **item_no** - REQUIRED (integer)

### Purchase Order Items Table
- ✅ **qty** - REQUIRED (decimal)
- ✅ **description** - REQUIRED (text)
- ✅ **item_no** - REQUIRED (integer)

### Supplier Receiving Report Items
- ✅ **srr_id** - REQUIRED (foreign key)
- ✅ **item_code** - REQUIRED (string)
- ✅ **qty_received** - REQUIRED (decimal)
- ✅ **qty_net_weight** - REQUIRED (decimal)
- ✅ **unit** - REQUIRED (string)

### Items Table
- ✅ **item_code** - REQUIRED (string)
- ✅ **item_name** - REQUIRED (string)
- ✅ **category** - REQUIRED (string)
- ✅ **status** - REQUIRED (string, default='active')

### Suppliers Table
- ✅ **supplier_code** - REQUIRED (string, unique)
- ✅ **supplier_name** - REQUIRED (string)
- ✅ **status** - REQUIRED (string, default='active')

### Customers Table
- ✅ **customer_code** - REQUIRED (string)
- ✅ **customer_name** - REQUIRED (string)
- ✅ **status** - REQUIRED (string, default='active')

### Purchase Requests Table
- ✅ **pr_no** - REQUIRED (string, unique)
- ✅ **company** - REQUIRED (string, default='MeatPlus')
- ✅ **requisitioner** - REQUIRED (string)
- ✅ **date_of_request** - REQUIRED (date)
- ✅ **status** - REQUIRED (string, default='pending')

### Purchase Orders Table
- ✅ **po_no** - REQUIRED (string, unique)
- ✅ **company** - REQUIRED (string, default='MeatPlus')
- ✅ **date** - REQUIRED (date)
- ✅ **status** - REQUIRED (string, default='pending')
- ✅ **approval_status** - REQUIRED (string, default='pending')
- ✅ **currency** - REQUIRED (string, default='PHP')

---

## Validation Rules vs Database Mismatches

### Fixed Issues:
1. **purchase_request_items.uom**
   - ❌ Was: `'items.*.uom' => 'nullable|string'` (validation)
   - ❌ But: DB column is NOT NULLABLE
   - ✅ Fixed: Changed to `'items.*.uom' => 'required|string'`

---

## Form Field Requirements Checklist

### Purchase Request Form
- [ ] **requisitioner** - Text input (required)
- [ ] **company** - Hidden field, defaults to "MeatPlus"
- [ ] **date_of_request** - Date input (required)
- [ ] For each item:
  - [ ] **item_code** - Text input (optional, but recommended)
  - [ ] **qty** - Number input (required)
  - [ ] **uom** - Text input (required) ← **CRITICAL**
  - [ ] **description** - Text input (required)
  - [ ] **date_needed** - Date input (optional)
  - [ ] **unit_price** - Number input (optional)
  - [ ] **supplier_id** - Autocomplete (optional)

### Purchase Order Form
- [ ] **company** - Hidden field, defaults to "MeatPlus"
- [ ] **date** - Date input (required)
- [ ] **currency** - Select dropdown (required)
- [ ] For each item:
  - [ ] **qty** - Number input (required)
  - [ ] **description** - Text input (required)
  - [ ] **unit_price** - Number input (optional)

---

## Best Practices Going Forward

1. **Always Match Validation to Database:**
   - If a DB column is NOT NULL, validation rule must include `required`
   - If a column is nullable, add `.nullable()` to validation OR provide a default value in controller

2. **Required Fields in Forms:**
   - Add `required` attribute to HTML inputs for required fields
   - Display asterisk (*) for required fields
   - Show validation error messages prominently

3. **Default Values:**
   - Use database defaults where possible (company='MeatPlus', status='pending', etc.)
   - Never leave REQUIRED fields empty in controller logic

4. **Testing:**
   - Test form submission with empty fields to catch validation issues
   - Check error messages are descriptive
   - Verify database constraints match application logic

---

## How to Prevent Similar Errors

When adding a new field to a table:

1. **In Migration:**
   ```php
   // If field is required:
   $table->string('field_name');

   // If field is optional:
   $table->string('field_name')->nullable();
   ```

2. **In Controller Validation:**
   ```php
   // Match the database:
   'field_name' => 'required|string',    // If NOT NULL in DB
   'field_name' => 'nullable|string',    // If NULL in DB
   ```

3. **In Blade Form:**
   ```html
   <!-- If required -->
   <input type="text" name="field_name" required>

   <!-- If optional -->
   <input type="text" name="field_name">
   ```

---

## Database Tables Summary

| Table | Primary Key | Status | Notable Fields |
|-------|------------|--------|----------------|
| users | id | ✅ | roles (JSON), email (unique) |
| customers | id | ✅ | customer_code (unique), status |
| items | id | ✅ | item_code (unique), status |
| suppliers | id | ✅ | supplier_code (unique), status |
| purchase_requests | id | ✅ | pr_no (unique), company, status |
| purchase_request_items | id | ✅ | **uom (CRITICAL - FIXED)** |
| purchase_orders | id | ✅ | po_no (unique), company, currency |
| purchase_order_items | id | ✅ | qty, description |
| request_for_payments | id | ✅ | rfp_no (unique), status |
| accounts_payable_invoices | id | ✅ | apv_no (unique), status |
| check_vouchers | id | ✅ | cv_no (unique), status |
| supplier_receiving_reports | id | ✅ | srr_no (unique) |
| issue_slips | id | ✅ | is_no (unique) |
| non_trade_items | id | ✅ | name, unit |
| trade_items | id | ✅ | item_code, item_name |

---

## Recommendations

1. Run a full validation test on all forms before going live
2. Add server-side validation for all required fields (currently implemented)
3. Add client-side validation with visual indicators (currently implemented)
4. Consider adding a database integrity check script to validate data consistency
5. Document all required fields in API/form specifications
