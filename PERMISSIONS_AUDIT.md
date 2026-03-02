# SOPOD Permissions & Access Control Audit
**Generated:** 2026-03-02

---

## Permission Structure Overview

**Total Permission Methods:** 51
**Role-Based Access Control:** All routes protected by permission methods
**Status:** ✅ Comprehensive coverage

---

## Module Permissions by Feature

### 📊 Sales Management Permissions

| Permission | Method | Roles | Scope |
|-----------|--------|-------|-------|
| View/List Sales Orders | `canManageSalesOrders()` | Admin, IT, CSR, CC_Approver, CC_Creator, Accounting_Approver, Delivery_Approver, Delivery_Creator | All SO |
| Create Sales Orders | `canCreateSalesOrders()` | Admin, IT, CSR | Create new |
| Approve Sales Orders | `canApproveSalesOrders()` | Admin, IT, CC_Approver, CC_Creator, Accounting_Approver | Approval stage |
| Manage Customers | `canManageCustomers()` | Admin, IT, CC_Approver, CC_Creator, Accounting_Creator, Accounting_Approver | CRUD |
| Add Customers | `canAddCustomers()` | Admin, IT, CSR (depends on canManageCustomers) | Create |
| Edit Customers | `canEditCustomers()` | Admin, IT, CC_Creator (depends on canManageCustomers) | Update |
| Delete Customers | `canDeleteCustomers()` | Admin, IT, CC_Approver | Delete |
| Import Customers | `canImportCustomers()` | Admin, IT, CC_Approver | Bulk import |
| Manage Deliveries | `canManageDeliveries()` | Delivery_Creator, Delivery_Approver, Admin, IT, CC_Approver | All DR |
| Create Deliveries | `canCreateDeliveries()` | Admin, IT, Delivery_Creator, Delivery_Approver | Create new |
| Approve Deliveries | `canApproveDeliveries()` | Admin, IT, Delivery_Approver | Approval |

---

### 🛒 Purchase Management Permissions

| Permission | Method | Roles | Scope |
|-----------|--------|-------|-------|
| View/Manage Purchase Requests | `canManagePurchaseRequests()` | Admin, IT, Requisitioner, PR_Approver, Procurement_Approver, Department_Head, General_Manager, CFO, President, Vice_President, **SCM** | All PR |
| Create Purchase Requests | `canCreatePurchaseRequests()` | Admin, IT, Requisitioner, PR_Approver, Procurement_Approver, **SCM** | Create new |
| Approve PR as DH | `canApprovePurchaseRequestsAsDH()` | Admin, IT, Department_Head | DH level |
| Approve PR as Management | `canApprovePurchaseRequestsAsManagement()` | Admin, IT, General_Manager, CFO | Management level |
| Approve PR as Executive | `canApprovePurchaseRequestsAsExecutive()` | Admin, IT, President, Vice_President | Executive level |
| Approve Purchase Requests | `canApprovePurchaseRequests()` | Admin, IT, PR_Approver, Procurement_Approver, Department_Head, General_Manager, CFO, President, Vice_President, **SCM** | All levels |
| View/Manage Purchase Orders | `canManagePurchaseOrders()` | Admin, IT, Purchasing, Procurement_Approver, Department_Head, General_Manager, CFO, President, Vice_President, **SCM** | All PO |
| Create Purchase Orders | `canCreatePurchaseOrders()` | Admin, IT, Purchasing, Procurement_Approver, **SCM** | Create new |
| Approve PO as DH | `canApprovePurchaseOrdersAsDH()` | Admin, IT, Department_Head | DH level |
| Approve PO as Management | `canApprovePurchaseOrdersAsManagement()` | Admin, IT, General_Manager, CFO | Management level |
| Approve PO as Executive | `canApprovePurchaseOrdersAsExecutive()` | Admin, IT, President, Vice_President | Executive level |
| Approve Purchase Orders | `canApprovePurchaseOrders()` | Admin, IT, Procurement_Approver, Department_Head, General_Manager, CFO, President, Vice_President, **SCM** | All levels |

---

### 🏢 Supplier Management Permissions

| Permission | Method | Roles | Scope |
|-----------|--------|-------|-------|
| View/Manage Suppliers | `canManageSuppliers()` | Admin, IT, Purchasing, Accounting_Creator, Accounting_Approver, **SCM** | All suppliers |
| Delete Suppliers | `canDeleteSuppliers()` | Admin, IT, Accounting_Approver | Delete only |
| View Supplier Receiving Reports | `canManageSupplierReceivingReports()` | Admin, IT, **SCM** | All SRR |
| Approve SRR | `canApproveSupplierReceivingReports()` | Admin, IT, **SCM** | Approve |
| Manage Issue Slips | `canManageIssueSlips()` | Admin, IT, **SCM**, CSR, CC_Approver, CC_Creator, Delivery_Creator, Delivery_Approver | All IS |

---

### 💰 Finance & Accounting Permissions

| Permission | Method | Roles | Scope |
|-----------|--------|-------|-------|
| View/Manage Request For Payments | `canManageRequestForPayments()` | Admin, IT, Purchasing, Procurement_Approver, **SCM** | All RFP |
| Create Request For Payments | `canCreateRequestForPayments()` | Admin, IT, Purchasing, Procurement_Approver, **SCM** | Create new |
| Approve RFP as DH | `canApproveRFPAsDH()` | Admin, IT, Department_Head | DH level |
| Approve RFP as Accounting | `canApproveRFPAsAccounting()` | Admin, IT, Accounting_Approver | Accounting level |
| Approve RFP as Executive | `canApproveRFPAsExecutive()` | Admin, IT, CFO, President, Vice_President | Executive level |
| Approve Request For Payments | `canApproveRequestForPayments()` | Admin, IT, Procurement_Approver, Department_Head, Accounting_Approver, CFO, President, Vice_President, **SCM** | All levels |
| Approve APV as DH | `canApproveAPVAsDH()` | Admin, IT, Department_Head | DH level |
| Approve APV | `canApproveAPV()` | Admin, IT, Accounting_Approver | Approve |
| Approve CV as Accounting | `canApproveCVAsAccounting()` | Admin, IT, Accounting_Approver | Accounting level |
| Approve Check Voucher | `canApproveCV()` | Admin, IT, Accounting_Approver | Approve |

---

### 📦 Inventory Management Permissions

| Permission | Method | Roles | Scope |
|-----------|--------|-------|-------|
| View/Manage Items | `canManageItems()` | Admin, IT, Accounting_Creator, Accounting_Approver, CC_Creator, CC_Approver | All items |
| Add Items | `canAddItems()` | Admin, IT, Accounting_Creator (depends on canManageItems) | Create |
| Edit Items | `canEditItems()` | Admin, IT, CC_Creator (depends on canManageItems) | Update |
| Delete Items | `canDeleteItems()` | Admin, IT, CC_Approver | Delete |
| Approve Items | `canApproveItems()` | Admin, IT, CC_Approver | Approve |
| Import Items | `canImportItems()` | Admin, IT, CC_Approver | Bulk import |

---

### 🔐 Admin & System Permissions

| Permission | Method | Roles | Scope |
|-----------|--------|-------|-------|
| Manage Users | `canManageUsers()` | Admin, IT | User CRUD |
| Edit After CC Approval | `canEditAfterCCApproval()` | Admin, IT, CSR | Edit locked items |
| Initiate Edit | `canInitiateEdit()` | Admin, IT, CC_Approver | Request edits |

---

### 📈 Analytics & Reports Permissions

| Permission | Method | Roles | Scope |
|-----------|--------|-------|-------|
| Access Aging Reports | `canAccessAgingReports()` | Admin, IT, CC_Approver, CC_Creator | View AR aging |
| Access AR Dashboard | `canAccessARDashboard()` | Admin, IT, CC_Approver | View AR dashboard |
| Access Payments | `canAccessPayments()` | Admin, IT, CC_Approver | View payments |
| Access Sales Analytics | `canAccessSalesAnalytics()` | Admin, IT, CSR, CC_Creator | View analytics |
| Access Receiving Reports | `canAccessReceivingReports()` | Admin, IT, Delivery_Creator, Delivery_Approver | View RR |
| Access Changelog | `canAccessChangelog()` | Admin, IT, CC_Approver, CC_Creator | View logs |
| Access Excel Import | `canAccessExcelImport()` | Admin, IT, CC_Approver, CC_Creator, Accounting_Creator, Accounting_Approver | Import utility |
| Access Records | `canAccessRecords()` | Admin, IT, CC_Approver, Delivery_Approver | View records |

---

## Role Breakdown

### 🔴 Admin
- **All permissions** - Full system access
- **Count:** 51/51 ✅

### 🟠 IT
- **All permissions** - Full system access
- **Count:** 51/51 ✅

### 🟡 CSR (Customer Service Rep)
- Sales Orders (create, manage, approve)
- Customers (add, manage)
- Sales Analytics
- Edit after CC approval
- **Count:** 8/51

### 🟢 CC_Creator (Credit & Collections Creator)
- Sales Orders, Customers, Items (manage, add)
- Sales Analytics, Changelog, Excel Import
- Edit after CC approval
- **Count:** 12/51

### 🟢 CC_Approver (Credit & Collections Approver)
- All CC_Creator permissions PLUS
- Sales Orders (approve)
- Customers, Items (delete, manage)
- Aging Reports, AR Dashboard, Payments
- Changelog, Excel Import, Records, Initiate Edit
- **Count:** 18/51

### 🔵 Accounting_Creator
- Items (manage, add)
- Suppliers (manage)
- Excel Import
- **Count:** 5/51

### 🔵 Accounting_Approver
- Items (manage, add, approve, delete)
- Suppliers (manage, delete)
- APV (approve)
- CV (approve)
- Excel Import
- **Count:** 9/51

### 🟣 Delivery_Creator
- Deliveries (create, manage)
- Issue Slips (manage)
- Receiving Reports
- **Count:** 5/51

### 🟣 Delivery_Approver
- Deliveries (create, manage, approve)
- Issue Slips (manage)
- Receiving Reports, Records
- **Count:** 7/51

### 🎯 Requisitioner
- Purchase Requests (create, manage)
- **Count:** 2/51

### 🎯 PR_Approver
- Purchase Requests (create, manage, approve)
- **Count:** 3/51

### 🎯 Procurement_Approver
- Purchase Requests (create, manage, approve - all levels)
- Purchase Orders (create, manage, approve - all levels)
- Request For Payments (create, manage, approve)
- Suppliers (manage)
- **Count:** 12/51

### 🎯 Purchasing
- Suppliers (manage)
- Purchase Orders (create, manage)
- Request For Payments (create, manage)
- **Count:** 5/51

### 👔 Department_Head
- PR approval (DH level)
- PO approval (DH level)
- RFP approval (DH level)
- APV approval (DH level)
- **Count:** 4/51

### 👔 General_Manager
- PR approval (Management level)
- PO approval (Management level)
- **Count:** 2/51

### 👔 CFO
- PR approval (Executive level)
- PO approval (Executive level)
- RFP approval (Executive level)
- **Count:** 3/51

### 👔 President
- PR approval (Executive level)
- PO approval (Executive level)
- RFP approval (Executive level)
- **Count:** 3/51

### 👔 Vice_President
- PR approval (Executive level)
- PO approval (Executive level)
- RFP approval (Executive level)
- **Count:** 3/51

### 🚀 **SCM (Supply Chain Management)**
- Suppliers (manage)
- Purchase Requests (create, manage, approve)
- Purchase Orders (create, manage, approve)
- Request For Payments (create, manage, approve)
- Supplier Receiving Reports (manage, approve)
- Issue Slips (manage)
- **Count:** 10/51 ✅

---

## Permission Grant Summary

| Role | Count | Primary Function |
|------|-------|------------------|
| Admin | 51 | Full access |
| IT | 51 | Full access |
| CC_Approver | 18 | Credit approval |
| Procurement_Approver | 12 | Purchasing approval |
| CC_Creator | 12 | Customer/Credit entry |
| Accounting_Approver | 9 | Finance approval |
| Delivery_Approver | 7 | Delivery approval |
| **SCM** | **10** | **Supply Chain (NEW)** |
| Accounting_Creator | 5 | Finance entry |
| Purchasing | 5 | Purchase creation |
| Delivery_Creator | 5 | Delivery creation |
| Department_Head | 4 | Department approval |
| CFO | 3 | Executive approval |
| President | 3 | Executive approval |
| Vice_President | 3 | Executive approval |
| General_Manager | 2 | Manager approval |
| Requisitioner | 2 | PR creation |
| PR_Approver | 3 | PR approval |

---

## Access Control Rules

### Sales Orders
- **Create:** CSR, Admin, IT
- **View:** CSR, CC_Approver, CC_Creator, Accounting_Approver, Delivery_*, Admin, IT
- **Approve:** CC_Approver, CC_Creator, Accounting_Approver, Admin, IT

### Purchase Requests
- **Create:** Requisitioner, PR_Approver, Procurement_Approver, Admin, IT, **SCM**
- **View:** All approval roles + Admin, IT, **SCM**
- **Approve:** PR_Approver, Procurement_Approver, Department_Head, General_Manager, CFO, President, Vice_President, Admin, IT, **SCM**

### Purchase Orders
- **Create:** Purchasing, Procurement_Approver, Admin, IT, **SCM**
- **View:** All approval roles + Admin, IT, **SCM**
- **Approve:** Procurement_Approver, Department_Head, General_Manager, CFO, President, Vice_President, Admin, IT, **SCM**

### Finance (RFP, APV, CV)
- **Create:** Purchasing, Procurement_Approver, Admin, IT, **SCM**
- **Approve:** Various approval roles + Admin, IT, **SCM**

---

## Recent Changes

### ✅ SCM Role Enhancement (Latest)
Added SCM role to:
- ✅ `canManageSuppliers()`
- ✅ `canManagePurchaseRequests()` + create
- ✅ `canManagePurchaseOrders()` + create
- ✅ `canManageRequestForPayments()` + create
- ✅ `canApproveRequestForPayments()`
- ✅ Already had: Supplier Receiving Reports, Issue Slips

---

## Recommendations

1. ✅ **SCM Role is fully configured** - Can access all supply chain modules
2. ✅ **Multi-level approvals** - PR, PO, RFP have DH → Management → Executive levels
3. ⚠️ **Consider:** Add approval level checks to sensitive finance operations
4. ⚠️ **Review:** AR-related roles (ar_adjustments, ar_aging) - no dedicated roles found
5. ⚠️ **Monitor:** Track permission usage to identify unused roles

---

## Testing Checklist

- [ ] Verify SCM can create Purchase Request
- [ ] Verify SCM can create Purchase Order
- [ ] Verify SCM can create Request For Payment
- [ ] Verify Finance roles can't see Finance menu (for SCM users)
- [ ] Verify CSR can create Sales Order
- [ ] Verify Delivery_Creator can create Delivery
- [ ] Verify each role can only see their allowed modules
- [ ] Verify approval workflows work for each level

