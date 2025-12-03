<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Customer;
use App\Models\Item;
use Illuminate\Support\Facades\DB;

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

    public function importItems(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240'
        ]);

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
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
                return strtolower(trim($header));
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
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
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
                return strtolower(trim($header));
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

            // Check for required columns
            $requiredColumns = ['customer_code', 'customer_name'];
            $missingColumns = array_diff($requiredColumns, $mappedHeaders);
            
            if (!empty($missingColumns)) {
                return redirect()->back()->with('error', 
                    'Missing required columns in file: ' . implode(', ', $missingColumns) . 
                    "\n\nFound columns: " . implode(', ', $cleanHeaders) . 
                    "\n\nRequired columns: customer_code (or Customer Code), customer_name (or Customer Name)"
                );
            }

            $imported = 0;
            $errors = [];
            $processedCodes = []; // Track customer codes in this import

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Combine headers with row data
                $data = array_combine($mappedHeaders, $row);

                // Validate required fields
                $customerCode = trim($data['customer_code'] ?? '');
                $customerName = trim($data['customer_name'] ?? '');

                if (empty($customerCode)) {
                    $errors[] = "Row $rowNum: customer_code is required";
                    continue;
                }
                if (empty($customerName)) {
                    $errors[] = "Row $rowNum: customer_name is required";
                    continue;
                }

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
                    // Prepare customer data
                    $customerData = [
                        'customer_code' => $customerCode,
                        'customer_name' => $customerName,
                        'status' => 'enabled',
                    ];

                    // Add optional fields if they exist
                    $optionalFields = [
                        'business_style', 'branch', 'customer_group', 'customer_type',
                        'currency', 'telephone_1', 'telephone_2', 'mobile', 'email',
                        'website', 'name_of_contact', 'billing_address', 'shipping_address',
                        'whtrate', 'whtcode', 'require_si', 'ar_type', 'tin_no',
                        'collection_terms', 'sales_rep', 'credit_limit', 'assigned_bank'
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

                    Customer::create($customerData);
                    $processedCodes[] = $customerCode; // Mark as processed
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row $rowNum: " . $e->getMessage();
                }
            }

            DB::commit();

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
}