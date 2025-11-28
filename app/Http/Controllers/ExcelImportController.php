<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Item;
use App\Models\Customer;

class ExcelImportController extends Controller
{
    public function importItems(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:10240'
        ]);

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Skip first row if it's merged/empty (formatting row)
            $firstRow = $rows[0];
            if (empty(array_filter($firstRow)) || count(array_filter($firstRow)) < 3) {
                array_shift($rows); // Remove the merged header row
            }

            // Get actual header row and create mapping
            $headers = array_shift($rows);
            
            // Clean headers - remove extra spaces and convert to lowercase
            $cleanHeaders = [];
            foreach ($headers as $header) {
                $cleanHeaders[] = strtolower(trim($header));
            }
            
            // Find column indices by header name
            $columnMap = array_flip($cleanHeaders);
            
            // Debug: Check if required columns exist
            $requiredColumns = ['item_code', 'item_category', 'item_description', 'brand'];
            $missingColumns = [];
            foreach ($requiredColumns as $col) {
                if (!isset($columnMap[$col])) {
                    $missingColumns[] = $col;
                }
            }
            
            if (!empty($missingColumns)) {
                return redirect()->back()->with('error', 
                    'Missing required columns in Excel file: ' . implode(', ', $missingColumns) . 
                    "\n\nFound columns: " . implode(', ', $cleanHeaders) . 
                    "\n\nPlease ensure your Excel has these exact column names: item_code, item_category, item_description, brand, unit"
                );
            }
            
            $imported = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                $rowNum = $index + 2; // Excel row number
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Map columns using header names (now lowercase)
                $itemCode = isset($columnMap['item_code']) ? trim($row[$columnMap['item_code']] ?? '') : '';
                $itemCategory = isset($columnMap['item_category']) ? trim($row[$columnMap['item_category']] ?? '') : '';
                $itemDescription = isset($columnMap['item_description']) ? trim($row[$columnMap['item_description']] ?? '') : '';
                $brand = isset($columnMap['brand']) ? trim($row[$columnMap['brand']] ?? '') : '';
                $unit = isset($columnMap['unit']) && !empty($row[$columnMap['unit']]) ? trim($row[$columnMap['unit']]) : null;

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

                // Check if item_code already exists
                if (Item::where('item_code', $itemCode)->exists()) {
                    $errors[] = "Row $rowNum: item_code '$itemCode' already exists";
                    continue;
                }

                // Save to database
                Item::create([
                    'item_code' => $itemCode,
                    'item_category' => $itemCategory,
                    'item_description' => $itemDescription,
                    'brand' => $brand,
                    'unit' => $unit,
                    'approval_status' => 'pending', // Default to pending
                    'is_enabled' => 1,
                ]);

                $imported++;
            }

            if (!empty($errors)) {
                $errorMessage = implode("\n", $errors);
                if ($imported > 0) {
                    return redirect()->back()->with('error', "Imported $imported items, but encountered errors:\n" . $errorMessage);
                }
                return redirect()->back()->with('error', $errorMessage);
            }

            return redirect()->back()->with('success', "Successfully imported $imported items!");

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    public function importCustomers(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:10240'
        ]);

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Remove header row
            $headers = array_shift($rows);
            
            $imported = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Map columns - adjust indices based on your Excel structure
                $customerCode = trim($row[0] ?? '');
                $customerName = trim($row[1] ?? '');
                $salesExecutive = !empty($row[2]) ? trim($row[2]) : null;
                $businessStyle = !empty($row[3]) ? trim($row[3]) : null;
                $billingAddress = !empty($row[4]) ? trim($row[4]) : null;
                $branch = !empty($row[5]) ? trim($row[5]) : null;
                $tin = !empty($row[6]) ? trim($row[6]) : null;
                $shippingAddress = !empty($row[7]) ? trim($row[7]) : null;

                // Validate required fields
                if (empty($customerCode)) {
                    $errors[] = "Row $rowNum: customer_code is required";
                    continue;
                }
                if (empty($customerName)) {
                    $errors[] = "Row $rowNum: customer_name is required";
                    continue;
                }

                // Check if customer_code already exists
                if (Customer::where('customer_code', $customerCode)->exists()) {
                    $errors[] = "Row $rowNum: customer_code '$customerCode' already exists";
                    continue;
                }

                // Save to database
                Customer::create([
                    'customer_code' => $customerCode,
                    'customer_name' => $customerName,
                    'sales_executive' => $salesExecutive,
                    'business_style' => $businessStyle,
                    'billing_address' => $billingAddress,
                    'branch' => $branch,
                    'tin' => $tin,
                    'shipping_address' => $shippingAddress,
                    'status' => 'active', // Default status
                ]);

                $imported++;
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
            return redirect()->back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }
}