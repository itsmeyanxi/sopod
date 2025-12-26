<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Customer;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
// use App\Mail\CustomerBatchImported; // ✅ COMMENTED OUT - Not yet created
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ExcelImportController extends Controller
{
    // Column mapping for customers - maps Excel headers to database columns
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
                        'is_flagged' => $isFlagged, // ✅ NEW
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
                    // ✅ TEMPORARILY DISABLED - Create CustomerBatchImported mail class if needed
                    // $this->sendBatchImportNotification(collect($newCustomers), $imported);
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

    // ✅ COMMENTED OUT - Enable when CustomerBatchImported mail class is created
    /*
    private function sendBatchImportNotification($customers, $count)
    {
        $recipients = User::whereIn('role', ['CC_Approver', 'CC_Creator', 'CSR_Approver', 'Admin', 'IT'])
                        ->whereNotNull('email')
                        ->get();
        
        $data = [
            'title' => 'Batch Customer Import Completed',
            'message' => "$count new customers have been imported to the system.",
            'count' => $count,
            'customers' => $customers->take(10), // Show first 10
            'imported_by' => auth()->user()->name ?? 'System',
            'view_url' => route('customers.index'),
        ];
        
        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->send(new CustomerBatchImported($data));
        }
    }
    */
}