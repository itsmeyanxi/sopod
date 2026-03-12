<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Customer;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
// use App\Mail\CustomerBatchImported; 
use App\Models\ARAging; 
use App\Models\Payment; 
use App\Models\ArAdjustment; 
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ExcelImportController extends Controller
{
    private $customerColumnMap = [
        'customer code' => 'customer_code',
        'customer_code' => 'customer_code',
        'cust code' => 'customer_code',
        'customer name' => 'customer_name',
        'customer_name' => 'customer_name',
        'business style' => 'business_style',
        'business_style' => 'business_style',
        'branch' => 'branch',
        'customer group' => 'customer_group',
        'customer_group' => 'customer_group',
        'customer type' => 'customer_type',
        'customer_type' => 'customer_type',
        'currency' => 'currency',
        'telephone 1' => 'telephone_1',
        'telephone_1' => 'telephone_1',
        'telephone1' => 'telephone_1',
        'telephone 2' => 'telephone_2',
        'telephone_2' => 'telephone_2',
        'telephone2' => 'telephone_2',
        'mobile' => 'mobile',
        'email' => 'email',
        'website' => 'website',
        'name of contact' => 'name_of_contact',
        'name_of_contact' => 'name_of_contact',
        'contact name' => 'name_of_contact',
        'billing address' => 'billing_address',
        'billing_address' => 'billing_address',
        'shipping address' => 'shipping_address',
        'shipping_address' => 'shipping_address',
        'wht rate' => 'whtrate',
        'whtrate' => 'whtrate',
        'wht code' => 'whtcode',
        'whtcode' => 'whtcode',
        'require si' => 'require_si',
        'require_si' => 'require_si',
        'ar type' => 'ar_type',
        'ar_type' => 'ar_type',
        'tin no' => 'tin_no',
        'tin_no' => 'tin_no',
        'tin' => 'tin_no',
        'collection terms' => 'collection_terms',
        'collection term' => 'collection_terms',
        'collection_terms' => 'collection_terms',
        'sales rep' => 'sales_rep',
        'sales_rep' => 'sales_rep',
        'sales representative' => 'sales_rep',
        'sales executive' => 'sales_rep',
        'sales_executive' => 'sales_rep',
        'credit limit' => 'credit_limit',
        'credit_limit' => 'credit_limit',
        'assigned bank' => 'assigned_bank',
        'assigned_bank' => 'assigned_bank',
        'bank' => 'assigned_bank',
        // ✅ NEW: Flag status mapping
        'flag status' => 'flag_status',
        'flag_status' => 'flag_status',
        'flagstatus' => 'flag_status',
        'flagged' => 'flag_status',
        'is flagged' => 'flag_status',
        'is_flagged' => 'flag_status',
    ];

    // Column mapping for items
    private $itemColumnMap = [
        'item code' => 'item_code',
        'item_code' => 'item_code',
        'itemcode' => 'item_code',
        'code' => 'item_code',
        'item category' => 'item_category',
        'item_category' => 'item_category',
        'itemcategory' => 'item_category',
        'category' => 'item_category',
        'item description' => 'item_description',
        'item_description' => 'item_description',
        'itemdescription' => 'item_description',
        'description' => 'item_description',
        'desc' => 'item_description',
        'brand' => 'brand',
        'brand name' => 'brand',
        'unit' => 'unit',
        'unit of measurement' => 'unit',
        'uom' => 'unit',
    ];

    private $collectionColumnMap = [
    // Customer info
    'customer code' => 'customer_code',
    'customer_code' => 'customer_code',
    'customercode' => 'customer_code',
    'customer name' => 'customer_name',
    'customer_name' => 'customer_name',
    'customername' => 'customer_name',
    'client name' => 'customer_name', 
    'client_name' => 'customer_name',
    'clientname' => 'customer_name',
    
    // Receipt info
    'collection receipt number' => 'collection_receipt_number',
    'collection_receipt_number' => 'collection_receipt_number',
    'collectionreceiptnumber' => 'collection_receipt_number',
    'receipt number' => 'collection_receipt_number',
    'receipt_number' => 'collection_receipt_number',
    'cr number' => 'collection_receipt_number', 
    'cr_number' => 'collection_receipt_number',
    'crnumber' => 'collection_receipt_number',
    
    // Dates
    'collection receipt date' => 'collection_receipt_date',
    'collection_receipt_date' => 'collection_receipt_date',
    'collectionreceiptdate' => 'collection_receipt_date',
    'receipt date' => 'collection_receipt_date',
    'cr date' => 'collection_receipt_date',
    'cr_date' => 'collection_receipt_date',
    'crdate' => 'collection_receipt_date',
    
    'payment posting date' => 'payment_posting_date',
    'payment_posting_date' => 'payment_posting_date',
    'paymentpostingdate' => 'payment_posting_date',
    'posting date' => 'payment_posting_date',
    'record date' => 'payment_posting_date', 
    'record_date' => 'payment_posting_date',
    'recorddate' => 'payment_posting_date',
    
    'payment date' => 'payment_date',
    'payment_date' => 'payment_date',
    'paymentdate' => 'payment_date',

    'deposit date' => 'deposit_date',
    'deposit_date' => 'deposit_date',
    'depositdate' => 'deposit_date',

    // Amount fields
    'amount' => 'amount',
    'payment amount' => 'amount',
    'payment_amount' => 'amount',

    'gross amount' => 'gross_amount',
    'gross_amount' => 'gross_amount',
    'grossamount' => 'gross_amount',
    
    'payment option' => 'payment_option',
    'payment_option' => 'payment_option',
    'paymentoption' => 'payment_option',
    
    'tax' => 'tax',
    'ewt' => 'ewt', 
    'withholding tax' => 'ewt',
    
    'net of cwt' => 'net_of_cwt',
    'net_of_cwt' => 'net_of_cwt',
    'netofcwt' => 'net_of_cwt',
    
    'payment notes' => 'payment_notes',
    'payment_notes' => 'payment_notes',
    'paymentnotes' => 'payment_notes',
    'notes' => 'payment_notes',
    
    'created by' => 'created_by',
    'created_by' => 'created_by',
    'createdby' => 'created_by',
    
    'payment method' => 'payment_method',
    'payment_method' => 'payment_method',
    'paymentmethod' => 'payment_method',
    'method' => 'payment_method',
    
    'bank' => 'bank',
    'bank name' => 'bank',
    
    'reference no' => 'reference_no',
    'reference_no' => 'reference_no',
    'referenceno' => 'reference_no',
    'reference number' => 'reference_no',
    'ref no' => 'reference_no',
    
    'remarks' => 'remarks', 
    
    // Invoice/DR info
    'invoice no' => 'invoice_no', 
    'invoice_no' => 'invoice_no',
    'invoiceno' => 'invoice_no',
    'invoice number' => 'invoice_no',
    
    'dr no' => 'dr_no',
    'dr_no' => 'dr_no',
    'drno' => 'dr_no',
    'delivery receipt' => 'dr_no',
    
    'branch' => 'branch',
    
    'status' => 'status', 
    
    'signed by' => 'signed_by', 
    'signed_by' => 'signed_by',
    'signedby' => 'signed_by',
    
    'other adjustment' => 'other_adjustment', 
    'other_adjustment' => 'other_adjustment',
    'otheradjustment' => 'other_adjustment',
    
    'factoring' => 'factoring', 
    
    'check amount' => 'check_amount', 
    'check_amount' => 'check_amount',
    'checkamount' => 'check_amount',
    
    'checking si' => 'checking_si', 
    'checking_si' => 'checking_si',
    'checkingsi' => 'checking_si',
    
    'week no' => 'week_no', 
    'week_no' => 'week_no',
    'weekno' => 'week_no',
    'week number' => 'week_no',
    
    'ar class' => 'ar_class',
    'ar_class' => 'ar_class',
    'arclass' => 'ar_class',
    
    'data check' => 'data_check', 
    'data_check' => 'data_check',
    'datacheck' => 'data_check',
];

private $arAdjustmentColumnMap = [
    // Customer fields
    'customer code' => 'customer_code',
    'customer_code' => 'customer_code',
    'customercode' => 'customer_code',
    'client name' => 'customer_name',        
    'client_name' => 'customer_name',
    'clientname' => 'customer_name',
    'customer name' => 'customer_name',
    'customer_name' => 'customer_name',
    'customername' => 'customer_name',
    
    // Reference/Transaction fields
    'reference number' => 'reference_number',
    'reference_number' => 'reference_number',
    'referencenumber' => 'reference_number',
    'ref number' => 'reference_number',
    'ref no' => 'reference_number',
    'reference no' => 'reference_number',    
    
    'transaction type' => 'transaction_type',
    'transaction_type' => 'transaction_type',
    'transactiontype' => 'transaction_type',
    'type' => 'transaction_type',
    
    // Date fields
    'transaction date' => 'transaction_date',
    'transaction_date' => 'transaction_date',
    'transactiondate' => 'transaction_date',
    'date' => 'transaction_date',
    
    // Amount
    'amount' => 'amount',
    'adjustment amount' => 'amount',
    
    // Is Decrease
    'is decrease' => 'is_decrease',
    'is_decrease' => 'is_decrease',
    'isdecrease' => 'is_decrease',
    'decrease' => 'is_decrease',

     // Branch
    'branch' => 'branch',
    'branch name' => 'branch',
    'branch_name' => 'branch',
    
    // Invoice fields
    'invoice_no' => 'invoice_number',  
    'invoice no' => 'invoice_number', 
    'invoicenumber' => 'invoice_number',
    'invoice no' => 'invoice_number',
    'invoice no.' => 'invoice_number',        
    'invoiceno' => 'invoice_number',
    'invoice' => 'invoice_number',             
    'inv no' => 'invoice_number',               
    'inv_no' => 'invoice_number',               
    'invno' => 'invoice_number',                
    'inv number' => 'invoice_number',          
    
    // DR fields
    'dr no' => 'dr_no',
    'dr no.' => 'dr_no',                      
    'dr_no' => 'dr_no',
    'drno' => 'dr_no',
    'delivery receipt' => 'dr_no',
    
    // Branch
    'branch' => 'branch',                     
    
    // GL Account
    'gl account' => 'gl_account',
    'gl_account' => 'gl_account',
    'glaccount' => 'gl_account',
    'account' => 'gl_account',
    
    // Remarks
    'remarks' => 'remarks',
    'notes' => 'remarks',
    'remark' => 'remarks',
    
    // Signed By
    'signed by' => 'signed_by',
    'signed_by' => 'signed_by',
    'signedby' => 'signed_by',
    
    // Created By
    'created by' => 'created_by',
    'created_by' => 'created_by',
    'createdby' => 'created_by',
    
    // Time
    'time' => 'time',                        
];

    /**
     * Load spreadsheet with proper encoding handling for special characters
     */
    private function loadSpreadsheet($filePath)
    {
        $spreadsheet = IOFactory::load($filePath);
        
        // Set encoding to UTF-8 to handle special characters properly
        $worksheet = $spreadsheet->getActiveSheet();
        
        return $worksheet;
    }

    /**
     * Clean and fix encoding issues in text
     */
    private function fixEncoding($text)
    {
        if (empty($text)) {
            return $text;
        }

        // Convert to UTF-8 if not already
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }

        // Fix common encoding issues
        $text = str_replace('�', '', $text); // Remove replacement character
        
        return trim($text);
    }

    public function importItems(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240'
        ]);

        try {
            $file = $request->file('file');
            $worksheet = $this->loadSpreadsheet($file->getRealPath());
            $rows = $worksheet->toArray();

            if (empty($rows)) {
                return redirect()->back()->with('error', 'The file is empty.');
            }

            // Skip first row if it's merged/empty (formatting row)
            $firstRow = $rows[0];
            if (empty(array_filter($firstRow)) || count(array_filter($firstRow)) < 3) {
                array_shift($rows);
            }

            // Get headers from first row and normalize them
            $headers = array_shift($rows);
            $cleanHeaders = array_map(function($header) {
                return strtolower(trim($this->fixEncoding($header)));
            }, $headers);

            // Map Excel headers to database columns
            $mappedHeaders = [];
            foreach ($cleanHeaders as $header) {
                if (isset($this->itemColumnMap[$header])) {
                    $mappedHeaders[] = $this->itemColumnMap[$header];
                } else {
                    $mappedHeaders[] = $header;
                }
            }

            // Check for required columns
            $requiredColumns = ['item_code', 'item_category', 'item_description', 'brand'];
            $missingColumns = array_diff($requiredColumns, $mappedHeaders);
            
            if (!empty($missingColumns)) {
                return redirect()->back()->with('error', 
                    'Missing required columns in file: ' . implode(', ', $missingColumns) . 
                    "\n\nFound columns: " . implode(', ', $cleanHeaders) . 
                    "\n\nRequired columns: item_code (or Item Code), item_category (or Item Category), item_description (or Item Description), brand (or Brand)"
                );
            }

            $imported = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNum = $index + 2; // +2 because we removed header and Excel is 1-indexed
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Fix encoding for each cell
                $row = array_map(function($cell) {
                    return $this->fixEncoding($cell);
                }, $row);

                // Combine headers with row data
                $data = array_combine($mappedHeaders, $row);

                // Clean and validate data
                $itemCode = trim($data['item_code'] ?? '');
                $itemCategory = trim($data['item_category'] ?? '');
                $itemDescription = trim($data['item_description'] ?? '');
                $brand = trim($data['brand'] ?? '');
                $unit = isset($data['unit']) && !empty($data['unit']) ? trim($data['unit']) : null;

                // Validate required fields
                if (empty($itemCode)) {
                    $errors[] = "Row $rowNum: item_code is required";
                    continue;
                }
                if (empty($itemCategory)) {
                    $errors[] = "Row $rowNum: item_category is required";
                    continue;
                }
                if (empty($itemDescription)) {
                    $errors[] = "Row $rowNum: item_description is required";
                    continue;
                }
                if (empty($brand)) {
                    $errors[] = "Row $rowNum: brand is required";
                    continue;
                }

                // Check if item already exists
                if (Item::where('item_code', $itemCode)->exists()) {
                    $errors[] = "Row $rowNum: item_code '$itemCode' already exists";
                    continue;
                }

                try {
                    Item::create([
                        'item_code' => $itemCode,
                        'item_category' => $itemCategory,
                        'item_description' => $itemDescription,
                        'brand' => $brand,
                        'unit' => $unit,
                        'approval_status' => 'pending',
                        'is_enabled' => 1,
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row $rowNum: " . $e->getMessage();
                }
            }

            DB::commit();

            if (!empty($errors)) {
                $errorMessage = implode("\n", $errors);
                if ($imported > 0) {
                    return redirect()->back()->with('error', "Imported $imported items, but encountered errors:\n" . $errorMessage);
                }
                return redirect()->back()->with('error', $errorMessage);
            }

            return redirect()->back()->with('success', "Successfully imported $imported items!");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    public function importCustomers(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240'
        ]);

        try {
            $file = $request->file('file');
            $worksheet = $this->loadSpreadsheet($file->getRealPath());
            $rows = $worksheet->toArray();

            if (empty($rows)) {
                return redirect()->back()->with('error', 'The file is empty.');
            }

            // Skip first row if it's merged/empty (formatting row)
            $firstRow = $rows[0];
            if (empty(array_filter($firstRow)) || count(array_filter($firstRow)) < 2) {
                array_shift($rows);
            }

            // Get headers from first row and normalize them
            $headers = array_shift($rows);
            $cleanHeaders = array_map(function($header) {
                return strtolower(trim($this->fixEncoding($header)));
            }, $headers);

            // Map Excel headers to database columns
            $mappedHeaders = [];
            foreach ($cleanHeaders as $header) {
                if (isset($this->customerColumnMap[$header])) {
                    $mappedHeaders[] = $this->customerColumnMap[$header];
                } else {
                    $mappedHeaders[] = $header;
                }
            }

            // ✅ UPDATED: Check for required columns - NOW INCLUDING FLAG STATUS
            $requiredColumns = ['customer_code', 'customer_name', 'billing_address', 'sales_rep', 'collection_terms', 'flag_status'];
            $missingColumns = array_diff($requiredColumns, $mappedHeaders);
            
            if (!empty($missingColumns)) {
                return redirect()->back()->with('error', 
                    'Missing required columns in file: ' . implode(', ', $missingColumns) . 
                    "\n\nFound columns: " . implode(', ', $cleanHeaders) . 
                    "\n\nRequired columns: customer_code, customer_name, billing_address, sales_rep, collection_terms, flag_status (Flagged/Unflagged)"
                );
            }

            $imported = 0;
            $errors = [];
            $processedCodes = []; // Track customer codes in this import
            $newCustomers = [];

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Fix encoding for each cell
                $row = array_map(function($cell) {
                    return $this->fixEncoding($cell);
                }, $row);

                // Combine headers with row data
                $data = array_combine($mappedHeaders, $row);

                // ✅ UPDATED: Validate required fields - NOW INCLUDING FLAG STATUS
                $customerCode = trim($data['customer_code'] ?? '');
                $customerName = trim($data['customer_name'] ?? '');
                $billingAddress = trim($data['billing_address'] ?? '');
                $salesRep = trim($data['sales_rep'] ?? '');
                $collectionTerms = trim($data['collection_terms'] ?? '');
                $flagStatus = trim($data['flag_status'] ?? '');

                if (empty($customerCode)) {
                    $errors[] = "Row $rowNum: customer_code is required";
                    continue;
                }
                if (empty($customerName)) {
                    $errors[] = "Row $rowNum: customer_name is required";
                    continue;
                }
                if (empty($billingAddress)) {
                    $errors[] = "Row $rowNum: billing_address is required";
                    continue;
                }
                if (empty($salesRep)) {
                    $errors[] = "Row $rowNum: sales_rep is required";
                    continue;
                }
                if ($collectionTerms === null || $collectionTerms === '') {
                    $errors[] = "Row $rowNum: collection_terms is required";
                    continue;
                }

                // ✅ NEW: Validate flag_status field
                if (empty($flagStatus)) {
                    $errors[] = "Row $rowNum: flag_status is required (must be 'Flagged' or 'Unflagged')";
                    continue;
                }

                // ✅ NEW: Validate flag_status value (must be Flagged or Unflagged)
                $flagStatusLower = strtolower(trim($flagStatus));
                if (!in_array($flagStatusLower, ['flagged', 'unflagged'])) {
                    $errors[] = "Row $rowNum: flag_status must be either 'Flagged' or 'Unflagged' (found: '$flagStatus')";
                    continue;
                }

                // ✅ NEW: Convert flag_status to boolean
                $isFlagged = ($flagStatusLower === 'flagged');
                
                // ✅ DEBUG: Log the flag status conversion
                \Log::info("Processing customer flag status", [
                    'row' => $rowNum,
                    'customer_code' => $customerCode,
                    'flag_status_raw' => $flagStatus,
                    'flag_status_lower' => $flagStatusLower,
                    'is_flagged_boolean' => $isFlagged,
                ]);

                // Check if already processed in THIS import (duplicate in Excel)
                if (in_array($customerCode, $processedCodes)) {
                    $errors[] = "Row $rowNum: customer_code '$customerCode' appears multiple times in your Excel file (skipped duplicate)";
                    continue;
                }

                // Check if customer already exists in database
                if (Customer::where('customer_code', $customerCode)->exists()) {
                    $errors[] = "Row $rowNum: customer_code '$customerCode' already exists in database";
                    continue;
                }

                try {
                    // ✅ UPDATED: Prepare customer data - NOW INCLUDES is_flagged
                    $customerData = [
                        'customer_code' => $customerCode,
                        'customer_name' => $customerName,
                        'billing_address' => $billingAddress,
                        'sales_rep' => $salesRep,
                        'collection_terms' => $collectionTerms,
                        'is_flagged' => $isFlagged, 
                        'status' => 'enabled',
                    ];
                    
                    // ✅ DEBUG: Log what we're about to save
                    \Log::info("Creating customer with data", [
                        'customer_code' => $customerCode,
                        'is_flagged' => $isFlagged,
                        'is_flagged_type' => gettype($isFlagged),
                    ]);

                    // Add optional fields if they exist
                    $optionalFields = [
                        'business_style', 'branch', 'customer_group', 'customer_type',
                        'currency', 'telephone_1', 'telephone_2', 'mobile', 'email',
                        'website', 'name_of_contact', 'shipping_address',
                        'whtrate', 'whtcode', 'require_si', 'ar_type', 'tin_no',
                        'credit_limit', 'assigned_bank'
                    ];

                    foreach ($optionalFields as $field) {
                        if (isset($data[$field]) && $data[$field] !== null && trim($data[$field]) !== '') {
                            $value = trim($data[$field]);
                            
                            // Special handling for require_si
                            if ($field === 'require_si') {
                                $value = strtolower($value) === 'yes' ? 'yes' : 'no';
                            }
                            
                            // Special handling for whtrate - remove % symbol
                            if ($field === 'whtrate') {
                                $value = str_replace(['%', ' '], '', $value);
                                $value = (float) $value;
                            }
                            
                            // Special handling for credit_limit - remove commas
                            if ($field === 'credit_limit') {
                                $value = str_replace(',', '', $value);
                                $value = (float) $value;
                            }
                            
                            $customerData[$field] = $value;
                        }
                    }

                    $customer = Customer::create($customerData);
                    
                    // ✅ DEBUG: Verify what was actually saved
                    \Log::info("Customer created successfully", [
                        'customer_id' => $customer->id,
                        'customer_code' => $customer->customer_code,
                        'is_flagged_saved' => $customer->is_flagged,
                        'is_flagged_type_saved' => gettype($customer->is_flagged),
                    ]);
                    
                    $processedCodes[] = $customerCode; // Mark as processed
                    $newCustomers[] = $customer;
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row $rowNum: " . $e->getMessage();
                }
            }

            DB::commit();

            if ($imported > 0 && !empty($newCustomers)) {
                try {
                    \Log::info('📧 Batch import completed (email notification disabled)', [
                        'count' => $imported,
                        'imported_by' => auth()->user()->name ?? 'System'
                    ]);
                } catch (\Exception $e) {
                    \Log::error('❌ Failed to send batch import notification', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if (!empty($errors)) {
                $errorMessage = implode("\n", $errors);
                if ($imported > 0) {
                    return redirect()->back()->with('error', "Imported $imported customers, but encountered errors:\n" . $errorMessage);
                }
                return redirect()->back()->with('error', $errorMessage);
            }

            return redirect()->back()->with('success', "Successfully imported $imported customers!");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

        public function importARAging(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240'
        ]);

        try {
            $file = $request->file('file');
            $worksheet = $this->loadSpreadsheet($file->getRealPath());
            $rows = $worksheet->toArray();

            if (empty($rows)) {
                return redirect()->back()->with('error', 'The file is empty.');
            }

            // Find the header row
            $headerRowFound = false;
            $headerRow = null;
            
            foreach ($rows as $index => $row) {
                $cleanedRow = array_map(function($cell) {
                    return strtolower(trim($this->fixEncoding($cell ?? '')));
                }, $row);
                
                // Look for any common AR Aging columns to identify header row
                $hasAgingDate = in_array('aging date', $cleanedRow) || in_array('agingdate', $cleanedRow);
                $hasInvoiceNo = in_array('invoice no', $cleanedRow) || in_array('invoice no.', $cleanedRow);
                $hasCustomerCode = in_array('customer code', $cleanedRow) || in_array('customercode', $cleanedRow);
                $hasNetAR = in_array('net ar', $cleanedRow) || in_array('netar', $cleanedRow);
                
                // If we find any of these common columns, assume it's the header
                if ($hasAgingDate || $hasInvoiceNo || $hasCustomerCode || $hasNetAR) {
                    $headerRow = $row;
                    $rows = array_slice($rows, $index + 1);
                    $headerRowFound = true;
                    break;
                }
            }

            if (!$headerRowFound || !$headerRow) {
                return redirect()->back()->with('error', 'Could not find header row. Please ensure your Excel file contains recognizable column headers (e.g., Customer Code, Invoice No, Net AR, etc.).');
            }

            // Clean headers
            $cleanHeaders = array_map(function($header) {
                $cleaned = strtolower(trim($this->fixEncoding($header ?? '')));
                $cleaned = preg_replace('/[^a-z0-9\s_]/', '', $cleaned);
                $cleaned = preg_replace('/\s+/', '_', $cleaned);
                return $cleaned;
            }, $headerRow);

            // Column mapping
            $columnMap = [
                'aging_date' => 'aging_date', 'agingdate' => 'aging_date',
                'counter_date' => 'counter_date', 'counterdate' => 'counter_date',
                'invoice_date' => 'invoice_date', 'invoicedate' => 'invoice_date',
                'record_date' => 'record_date', 'recorddate' => 'record_date',
                'due_date' => 'due_date', 'duedate' => 'due_date',
                'invoice_no' => 'invoice_no', 'invoiceno' => 'invoice_no',
                'po_no' => 'po_no', 'pono' => 'po_no',
                'dr_no' => 'dr_no', 'drno' => 'dr_no',
                'customer_code' => 'customer_code', 'customercode' => 'customer_code',
                'client_name' => 'client_name', 'clientname' => 'client_name',
                'branch' => 'branch',
                'sales_executive' => 'sales_executive', 'salesexecutive' => 'sales_executive',
                'se2' => 'se2',
                'terms' => 'terms',
                'sales_week_no' => 'sales_week_no', 'salesweekno' => 'sales_week_no',
                'age' => 'age',
                'age_category' => 'age_category', 'agecategory' => 'age_category',
                'invoice_amount' => 'invoice_amount', 'invoiceamount' => 'invoice_amount',
                'ar_adjustments' => 'ar_adjustments', 'aradjustments' => 'ar_adjustments',
                'settled_invoice_amount' => 'settled_invoice_amount', 'settledinvoiceamount' => 'settled_invoice_amount',
                'gross_ar_balance' => 'gross_ar_balance', 'grossarbalance' => 'gross_ar_balance',
                'net_ar' => 'net_ar', 'netar' => 'net_ar',
                'cwt' => 'cwt',
                'net_of_cwt' => 'net_of_cwt', 'netofcwt' => 'net_of_cwt', 'net_of_cwtx' => 'net_of_cwt', 'netofcwtx' => 'net_of_cwt',
                'net_ar_balance' => 'net_ar_balance', 'netarbalance' => 'net_ar_balance',
                'factored_ar_amount' => 'factored_ar_amount', 'factoredaramount' => 'factored_ar_amount',
                'status' => 'status',
                'include' => 'include_flag', 'include_flag' => 'include_flag', 'includeflag' => 'include_flag',
                'ar_class' => 'ar_class', 'arclass' => 'ar_class',
            ];

            $mappedHeaders = [];
            foreach ($cleanHeaders as $header) {
                $mappedHeaders[] = $columnMap[$header] ?? $header;
            }

            \Log::info('AR Aging Import - Headers Found', [
                'clean_headers' => $cleanHeaders,
                'mapped_headers' => $mappedHeaders
            ]);

            // ✅ REMOVED: No required columns check - all columns are now optional

            $imported = 0;
            $skipped = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;

                // Skip completely empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                $row = array_map(fn($cell) => $this->fixEncoding($cell), $row);

                if (count($row) < count($mappedHeaders)) {
                    $row = array_pad($row, count($mappedHeaders), '');
                }

                $data = array_combine($mappedHeaders, array_slice($row, 0, count($mappedHeaders)));

                // ✅ UPDATED: Create insert data with only the fields that exist and have values
                $insertData = [];

                // Process all possible fields
                $allFields = [
                    'customer_code', 'invoice_no', 'invoice_date', 'net_ar',
                    'aging_date', 'counter_date', 'record_date', 'due_date',
                    'po_no', 'dr_no', 'client_name', 'branch', 
                    'sales_executive', 'se2', 'terms', 'sales_week_no',
                    'age', 'age_category', 'invoice_amount', 'ar_adjustments',
                    'settled_invoice_amount', 'gross_ar_balance', 'cwt',
                    'net_of_cwt', 'net_ar_balance', 'factored_ar_amount',
                    'status', 'include_flag', 'ar_class'
                ];

                $hasAnyData = false;

                foreach ($allFields as $field) {
                    if (isset($data[$field]) && $data[$field] !== null && trim($data[$field]) !== '') {
                        $value = trim($data[$field]);
                        $hasAnyData = true;
                        
                        // Handle date fields
                        if (in_array($field, ['aging_date', 'counter_date', 'record_date', 'due_date', 'invoice_date'])) {
                            $value = $this->convertExcelDate($value);
                            if (!$value) {
                                \Log::warning("Row $rowNum: Could not convert date for $field: " . $data[$field]);
                                continue;
                            }
                        }
                        // Clean numeric fields
                        elseif (in_array($field, ['invoice_amount', 'ar_adjustments', 'settled_invoice_amount', 
                                            'gross_ar_balance', 'cwt', 'net_of_cwt', 'net_ar_balance', 
                                            'factored_ar_amount', 'age', 'net_ar'])) {
                            $value = str_replace([',', ' ', '-'], '', $value);
                            $value = is_numeric($value) ? (float) $value : 0;
                        }
                        // Special handling for sales_week_no
                        elseif ($field === 'sales_week_no') {
                            $value = preg_replace('/\s+/', '', $value);
                            if (strlen($value) > 20) {
                                $value = substr($value, 0, 20);
                            }
                        }
                        // Special handling for include_flag
                        elseif ($field === 'include_flag') {
                            $valueLower = strtolower(trim($value));
                            if (in_array($valueLower, ['yes', 'y', '1', 'true', 't'])) {
                                $value = 'yes';
                            } elseif (in_array($valueLower, ['no', 'n', '0', 'false', 'f'])) {
                                $value = 'no';
                            } else {
                                $value = 'yes';
                            }
                        }
                        
                        $insertData[$field] = $value;
                    }
                }

                // Skip row if it has no data at all
                if (!$hasAnyData) {
                    $skipped++;
                    continue;
                }

                try {
                    ArAging::create($insertData);
                    $imported++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Row $rowNum: " . $e->getMessage();
                    \Log::error("Row $rowNum import failed", [
                        'error' => $e->getMessage(),
                        'data' => $insertData ?? []
                    ]);
                }
            }

            DB::commit();

            $message = "Successfully imported $imported AR Aging rows!";
            if ($skipped > 0) {
                $message .= " ($skipped empty rows skipped)";
            }

            if (!empty($errors)) {
                $errorMessage = implode("\n", array_slice($errors, 0, 10));
                if (count($errors) > 10) {
                    $errorMessage .= "\n\n... and " . (count($errors) - 10) . " more errors. Check logs for details.";
                }
                
                if ($imported > 0) {
                    return redirect()->back()->with('error', "$message\n\nBut encountered " . count($errors) . " errors:\n\n" . $errorMessage);
                }
                return redirect()->back()->with('error', "Import failed with " . count($errors) . " errors:\n\n" . $errorMessage);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('AR Aging import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

   /**
     * Convert various date formats to MySQL format (YYYY-MM-DD)
     */
    private function convertExcelDate($dateValue)
    {
        if (empty($dateValue)) {
            return null;
        }

        $dateValue = trim($dateValue);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)) {
            return $dateValue;
        }

        if (is_numeric($dateValue)) {
            try {
                $unixTimestamp = ($dateValue - 25569) * 86400;
                return date('Y-m-d', $unixTimestamp);
            } catch (\Exception $e) {
                \Log::warning("Could not convert numeric date: $dateValue");
                return null;
            }
        }

        $formats = [
            'm/d/Y', 'n/j/Y', 'm/d/y', 'n/j/y', 'm-d-Y',
            'd/m/Y', 'Y/m/d', 'd-m-Y', 'Y-m-d', 'M d, Y', 'd M Y',
        ];

        foreach ($formats as $format) {
            try {
                $date = \DateTime::createFromFormat($format, $dateValue);
                if ($date && $date->format($format) == $dateValue) {
                    return $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        try {
            $carbonDate = \Carbon\Carbon::parse($dateValue);
            return $carbonDate->format('Y-m-d');
        } catch (\Exception $e) {
            \Log::warning("Could not parse date: $dateValue");
            return null;
        }
    }

    public function importCollections(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,xls,csv|max:10240'
    ]);

    try {
        $file = $request->file('file');
        $worksheet = $this->loadSpreadsheet($file->getRealPath());
        $rows = $worksheet->toArray();

        if (empty($rows)) {
            return redirect()->back()->with('error', 'The file is empty.');
        }

        // ✅ Look for the exact payments table columns
        $targetColumns = [
            'customer_code', 'customer_name', 'collection_receipt_number',
            'collection_receipt_date', 'payment_posting_date', 'payment_date',
            'amount', 'payment_option', 'invoice_no', 'dr_no', 'branch'
        ];

        $headerRowFound = false;
        $headerRow = null;
        $headerRowIndex = 0;
        
        foreach ($rows as $index => $row) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $cleanedRow = array_map(function($cell) {
                $cleaned = strtolower(trim($this->fixEncoding($cell ?? '')));
                $cleaned = preg_replace('/[^a-z0-9\s_]/', '', $cleaned);
                $cleaned = preg_replace('/\s+/', '_', $cleaned);
                return $cleaned;
            }, $row);
            
            // ✅ Count how many target columns we find
            $matchCount = 0;
            foreach ($targetColumns as $targetCol) {
                // Check for exact match or mapped match
                if (in_array($targetCol, $cleanedRow)) {
                    $matchCount++;
                } else {
                    // Check if any column map exists for this
                    foreach ($this->collectionColumnMap as $excelCol => $dbCol) {
                        if ($dbCol === $targetCol && in_array($excelCol, $cleanedRow)) {
                            $matchCount++;
                            break;
                        }
                    }
                }
            }
            
            // ✅ If we find at least 4 matching columns, this is our header
            if ($matchCount >= 4) {
                $headerRow = $row;
                $headerRowIndex = $index;
                $rows = array_slice($rows, $index + 1);
                $headerRowFound = true;
                
                \Log::info('Collections - Header row found', [
                    'row_index' => $index,
                    'columns_found' => $cleanedRow,
                    'match_count' => $matchCount
                ]);
                
                break;
            }
        }

        if (!$headerRowFound || !$headerRow) {
            \Log::error('Could not find Collections header row', [
                'first_10_rows' => array_slice($rows, 0, 10)
            ]);
            
            return redirect()->back()->with('error', 
                'Could not find the expected header row. Please ensure your Excel file contains columns like: customer_code, customer_name, amount, payment_date, invoice_no, etc.'
            );
        }

        // Clean and map headers
        $cleanHeaders = array_map(function($header) {
            $cleaned = strtolower(trim($this->fixEncoding($header ?? '')));
            $cleaned = preg_replace('/[^a-z0-9\s_]/', '', $cleaned);
            $cleaned = preg_replace('/\s+/', '_', $cleaned);
            return $cleaned;
        }, $headerRow);

        $mappedHeaders = [];
        foreach ($cleanHeaders as $header) {
            $mappedHeaders[] = $this->collectionColumnMap[$header] ?? $header;
        }

        \Log::info('Collections Import - Headers Mapped', [
            'clean_headers' => $cleanHeaders,
            'mapped_headers' => $mappedHeaders
        ]);

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();

        foreach ($rows as $index => $row) {
            $rowNum = $headerRowIndex + $index + 2;

            if (empty(array_filter($row))) {
                $skipped++;
                continue;
            }

            $row = array_map(fn($cell) => $this->fixEncoding($cell), $row);

            if (count($row) < count($mappedHeaders)) {
                $row = array_pad($row, count($mappedHeaders), '');
            }

            $data = array_combine($mappedHeaders, array_slice($row, 0, count($mappedHeaders)));

            $insertData = [];
            $hasAnyData = false;

            $allFields = [
                'customer_code', 'customer_name', 'collection_receipt_number',
                'collection_receipt_date', 'payment_posting_date', 'payment_date',
                'deposit_date', 'amount', 'gross_amount', 'payment_option',
                'tax', 'ewt', 'net_of_cwt', 'payment_notes', 'created_by',
                'payment_method', 'bank', 'reference_no', 'remarks',
                'invoice_no', 'dr_no', 'branch', 'status', 'signed_by',
                'other_adjustment', 'factoring', 'check_amount', 'checking_si',
                'week_no', 'ar_class', 'data_check'
            ];

            foreach ($allFields as $field) {
                if (isset($data[$field]) && $data[$field] !== null && trim($data[$field]) !== '') {
                    $value = trim($data[$field]);
                    $hasAnyData = true;
                    
                    // Handle date fields
                    if (in_array($field, ['collection_receipt_date', 'payment_posting_date', 'payment_date', 'deposit_date'])) {
                        $value = $this->convertExcelDate($value);
                        if (!$value) {
                            \Log::warning("Row $rowNum: Could not convert date for $field: " . $data[$field]);
                            continue;
                        }
                    }
                    // Clean numeric fields
                    elseif (in_array($field, ['amount', 'gross_amount', 'tax', 'ewt', 'net_of_cwt', 'other_adjustment', 'factoring', 'check_amount'])) {
                        $value = str_replace([',', ' '], '', $value);
                        $value = is_numeric($value) ? (float) $value : 0;
                    }
                    
                    $insertData[$field] = $value;
                }
            }

            if (!$hasAnyData) {
                $skipped++;
                continue;
            }

            try {
                Payment::create($insertData);
                $imported++;
                
            } catch (\Exception $e) {
                $errors[] = "Row $rowNum: " . $e->getMessage();
                \Log::error("Row $rowNum import failed", [
                    'error' => $e->getMessage(),
                    'data' => $insertData
                ]);
            }
        }

        DB::commit();

        $message = "Successfully imported $imported collection records!";
        if ($skipped > 0) {
            $message .= " ($skipped empty rows skipped)";
        }

        if (!empty($errors)) {
            $errorMessage = implode("\n", array_slice($errors, 0, 10));
            if (count($errors) > 10) {
                $errorMessage .= "\n\n... and " . (count($errors) - 10) . " more errors. Check logs for details.";
            }
            
            if ($imported > 0) {
                return redirect()->back()->with('warning', "$message\n\nBut encountered " . count($errors) . " errors:\n\n" . $errorMessage);
            }
            return redirect()->back()->with('error', "Import failed with " . count($errors) . " errors:\n\n" . $errorMessage);
        }

        return redirect()->back()->with('success', $message);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Collections import failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return redirect()->back()->with('error', 'Error importing file: ' . $e->getMessage());
    }
}

   public function importArAdjustments(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        'merge_dr' => 'nullable|boolean',
        'auto_approve' => 'nullable|boolean',
        'create_rr_link' => 'nullable|boolean'
    ]);

    try {
        $file = $request->file('file');
        $worksheet = $this->loadSpreadsheet($file->getRealPath());
        $rows = $worksheet->toArray();

        if (empty($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'The file is empty.'
            ], 400);
        }

        // Get import options
        $mergeDr = $request->boolean('merge_dr', true);
        $autoApprove = $request->boolean('auto_approve', true);
        $createRrLink = $request->boolean('create_rr_link', false);

        $tableColumns = \Schema::getColumnListing('ar_adjustments');

        // Find header row
        $headerRowFound = false;
        $headerRow = null;
        $headerRowIndex = 0;
        
        foreach ($rows as $index => $row) {
            $cleanedRow = array_map(function($cell) {
                return strtolower(trim($this->fixEncoding($cell ?? '')));
            }, $row);
            
            $hasCustomerCode = in_array('customer code', $cleanedRow) || in_array('customer_code', $cleanedRow);
            $hasTransactionType = in_array('transaction type', $cleanedRow) || in_array('transaction_type', $cleanedRow);
            $hasAmount = in_array('amount', $cleanedRow);
            
            if ($hasCustomerCode || $hasTransactionType || $hasAmount) {
                $headerRow = $row;
                $headerRowIndex = $index;
                $rows = array_slice($rows, $index + 1);
                $headerRowFound = true;
                break;
            }
        }

        if (!$headerRowFound || !$headerRow) {
            return redirect()->back()->with('error', 'Could not find header row in the Excel file.');
        }

        // Clean and map headers
        $cleanHeaders = array_map(function($header) {
            $cleaned = strtolower(trim($this->fixEncoding($header ?? '')));
            $cleaned = preg_replace('/[^a-z0-9\s_]/', '', $cleaned);
            $cleaned = preg_replace('/\s+/', '_', $cleaned);
            return $cleaned;
        }, $headerRow);

        $mappedHeaders = [];
        foreach ($cleanHeaders as $header) {
            $mappedHeaders[] = $this->arAdjustmentColumnMap[$header] ?? $header;
        }

        \Log::info('AR Adjustments Import - Headers Found', [
            'clean_headers' => $cleanHeaders,
            'mapped_headers' => $mappedHeaders
        ]);

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();

        foreach ($rows as $index => $row) {
            $rowNum = $headerRowIndex + $index + 2;

            if (empty(array_filter($row))) {
                continue;
            }

            $row = array_map(fn($cell) => $this->fixEncoding($cell), $row);

            if (count($row) < count($mappedHeaders)) {
                $row = array_pad($row, count($mappedHeaders), '');
            }

            $data = array_combine($mappedHeaders, array_slice($row, 0, count($mappedHeaders)));

            $insertData = [];
            $hasAnyData = false;

            // ✅ Loop through all mapped headers
            foreach ($mappedHeaders as $mappedHeader) {
                // Skip if column doesn't exist in database
                if (!in_array($mappedHeader, $tableColumns)) {
                    continue;
                }

                if (isset($data[$mappedHeader]) && $data[$mappedHeader] !== null && trim($data[$mappedHeader]) !== '') {
                    $value = trim($data[$mappedHeader]);
                    $hasAnyData = true;
                    
                    // Handle date fields
                    if ($mappedHeader === 'transaction_date') {
                        $value = $this->convertExcelDate($value);
                        if (!$value) {
                            \Log::warning("Row $rowNum: Could not convert date: " . $data[$mappedHeader]);
                            continue;
                        }
                    }
                    // Clean numeric fields
                    elseif ($mappedHeader === 'amount') {
                        $hasParentheses = strpos($value, '(') !== false && strpos($value, ')') !== false;
                        $value = str_replace(['(', ')', ',', ' ', '$'], '', $value);
                        $value = is_numeric($value) ? (float) $value : 0;
                        
                        if ($hasParentheses) {
                            $value = -abs($value);
                        }
                    }
                    // Handle is_decrease boolean
                    elseif ($mappedHeader === 'is_decrease') {
                        $valueLower = strtolower(trim($value));
                        $value = in_array($valueLower, ['yes', 'y', '1', 'true', 't']) ? 1 : 0;
                    }
                    
                    $insertData[$mappedHeader] = $value;
                }
            }

            // Set created_by if not provided
            if (!isset($insertData['created_by'])) {
                $insertData['created_by'] = auth()->user()->name ?? 'System';
            }

            if (!$hasAnyData) {
                $skipped++;
                continue;
            }

            try {
                $adjustment = ArAdjustment::create($insertData);
                $imported++;

                // ✅ AUTO-APPROVE DELIVERY if option enabled and DR number provided
                if ($autoApprove && !empty($insertData['dr_no'])) {
                    $this->approveDeliveryByDrNo($insertData['dr_no']);
                }

                // ✅ LINK TO RECEIVING REPORT if option enabled
                if ($createRrLink && !empty($insertData['dr_no'])) {
                    $this->linkToReceivingReport($adjustment, $insertData['dr_no']);
                }

            } catch (\Exception $e) {
                $errors[] = "Row $rowNum: " . $e->getMessage();
                \Log::error("Row $rowNum import failed", [
                    'error' => $e->getMessage(),
                    'data' => $insertData,
                ]);
            }
        }

        DB::commit();

        $message = "Successfully imported $imported AR adjustment records!";
        if ($skipped > 0) {
            $message .= " ($skipped empty rows skipped)";
        }

        if (!empty($errors)) {
            $errorMessage = implode("\n", array_slice($errors, 0, 10));
            if (count($errors) > 10) {
                $errorMessage .= "\n\n... and " . (count($errors) - 10) . " more errors. Check logs for details.";
            }

            if ($imported > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "$message\n\nBut encountered " . count($errors) . " errors:\n\n" . $errorMessage
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => "Import failed with " . count($errors) . " errors:\n\n" . $errorMessage
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('AR Adjustments import failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Error importing file: ' . $e->getMessage()
        ], 500);
    }

    /**
     * ✅ Auto-approve delivery by DR number
     */
    private function approveDeliveryByDrNo($drNo)
    {
        try {
            $delivery = \App\Models\Deliveries::where('dr_no', $drNo)->first();
            if ($delivery) {
                $delivery->update([
                    'approval_status' => 'approved',
                    'approved_by_user' => auth()->user()->name ?? 'System',
                    'approved_at' => now(),
                    'status' => 'completed'
                ]);
                \Log::info("Delivery auto-approved on AR import", ['dr_no' => $drNo]);
            }
        } catch (\Exception $e) {
            \Log::warning("Could not auto-approve delivery: " . $e->getMessage());
        }
    }

    /**
     * ✅ Link adjustment to Receiving Report if available
     */
    private function linkToReceivingReport($adjustment, $drNo)
    {
        try {
            $rr = \App\Models\ReceivingReport::where('delivery_batch', $drNo)
                ->orWhere('rr_number', $drNo)
                ->first();

            if ($rr) {
                // Link by adding a note/reference in the adjustment
                $remarks = $adjustment->remarks ?? '';
                $remarks .= ($remarks ? "\n" : '') . "Linked to RR: {$rr->rr_number}";
                $adjustment->update(['remarks' => $remarks]);
                \Log::info("Adjustment linked to RR", ['dr_no' => $drNo, 'rr_number' => $rr->rr_number]);
            }
        } catch (\Exception $e) {
            \Log::warning("Could not link to receiving report: " . $e->getMessage());
        }
    }

    /**
     * Import GL Accounts from Excel/CSV
     */
    public function importGlAccounts(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Validate file exists
            if (!$request->hasFile('file')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No file uploaded',
                ], 400);
            }

            $file = $request->file('file');

            // Check file is valid
            if (!$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'File upload failed: ' . $file->getErrorMessage(),
                ], 400);
            }

            // Check file extension (more flexible than MIME type)
            $extension = strtolower($file->getClientOriginalExtension());
            $allowedExtensions = ['xlsx', 'xls', 'csv'];

            if (!in_array($extension, $allowedExtensions)) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid file type. Allowed: CSV, XLS, XLSX. Received: {$extension}",
                ], 400);
            }

            // Check file size (10MB max)
            if ($file->getSize() > 10 * 1024 * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => 'File too large. Maximum size: 10MB',
                ], 400);
            }

            // Load the spreadsheet
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            } catch (\Exception $loadError) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to read file: ' . $loadError->getMessage(),
                ], 400);
            }
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            if (empty($rows)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File is empty',
                ], 400);
            }

            // Find header row (first row should have headers)
            $headerRow = $rows[0];
            $headerRowIndex = 0;

            // Map headers to database columns (case-insensitive)
            $columnMap = [
                'account code' => 'account_code',
                'account name' => 'account_name',
                'fs line item' => 'fs_line_item',
                'fs notes' => 'fs_notes',
            ];

            $mappedHeaders = [];
            foreach ($headerRow as $index => $header) {
                $headerLower = strtolower(trim((string)$header));
                foreach ($columnMap as $searchKey => $dbColumn) {
                    if (strpos($headerLower, $searchKey) !== false) {
                        $mappedHeaders[$index] = $dbColumn;
                        break;
                    }
                }
            }

            if (empty($mappedHeaders)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No recognized columns found in file header',
                ], 400);
            }

            $imported = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];

            // Process data rows
            for ($rowIndex = $headerRowIndex + 1; $rowIndex < count($rows); $rowIndex++) {
                $row = $rows[$rowIndex];

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    // Build data array from mapped columns
                    $data = [];
                    foreach ($mappedHeaders as $colIndex => $dbColumn) {
                        $value = isset($row[$colIndex]) ? trim((string)$row[$colIndex]) : '';
                        if (!empty($value)) {
                            $data[$dbColumn] = $value;
                        }
                    }

                    // Validate required fields
                    if (empty($data['account_code']) || empty($data['account_name'])) {
                        $skipped++;
                        $errors[] = "Row " . ($rowIndex + 1) . ": Missing account code or name";
                        continue;
                    }

                    // Set created_by
                    $data['created_by'] = auth()->user()->name;

                    // Upsert: update if exists by account_code, otherwise create
                    $account = \App\Models\GlAccount::updateOrCreate(
                        ['account_code' => $data['account_code']],
                        $data
                    );

                    if ($account->wasRecentlyCreated) {
                        $imported++;
                    } else {
                        $updated++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($rowIndex + 1) . ": " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
                'message' => "Import complete. Imported: {$imported}, Updated: {$updated}, Skipped: {$skipped}",
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to import GL accounts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to import GL accounts: ' . $e->getMessage(),
            ], 500);
        }
    }
}