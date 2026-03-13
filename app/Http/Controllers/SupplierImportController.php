<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SupplierImportController extends Controller
{
    /**
     * Import suppliers from Excel/CSV file
     * HANDLES MULTI-ROW FORMAT where one supplier has data across multiple rows
     */
    public function importSuppliers(Request $request)
    {
        // Validate file upload
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('file');
            $data = Excel::toArray([], $file)[0];

            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded file is empty.'
                ], 422);
            }

            // Get headers from first row
            $headers = array_map('trim', array_map('strtolower', $data[0]));
            
            // Remove the header row
            array_shift($data);

            // Map column names to indices
            $columnMap = $this->mapColumns($headers);

            if (empty($columnMap)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No recognized columns found in the file. Please include at least one of: supplier, contact_person, email_address, company_address, terms, etc.'
                ], 422);
            }

            // ✅ GROUP ROWS BY SUPPLIER - Merge multi-row data
            $groupedSuppliers = $this->groupSupplierRows($data, $columnMap);

            $imported = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($groupedSuppliers as $supplierCode => $supplierData) {
                try {
                    // Validate supplier data
                    $rules = [];
                    
                    if (isset($supplierData['supplier_code'])) {
                        $rules['supplier_code'] = 'string|max:50';
                    }
                    if (isset($supplierData['supplier_name'])) {
                        $rules['supplier_name'] = 'string|max:255';
                    }
                    if (isset($supplierData['email'])) {
                        $rules['email'] = 'email|max:255';
                    }

                    $validator = Validator::make($supplierData, $rules);

                    if ($validator->fails()) {
                        $errors[] = "Supplier {$supplierCode}: " . implode(', ', $validator->errors()->all());
                        $skipped++;
                        continue;
                    }

                    // Check if supplier already exists
                    $supplier = Supplier::where('supplier_code', $supplierData['supplier_code'])->first();

                    if ($supplier) {
                        // Update existing supplier
                        $supplier->update($supplierData);
                        $updated++;
                    } else {
                        // Create new supplier
                        Supplier::create($supplierData);
                        $imported++;
                    }

                } catch (\Exception $e) {
                    $errors[] = "Supplier {$supplierCode}: " . $e->getMessage();
                    $skipped++;
                }
            }

            DB::commit();

            // Return JSON response with detailed feedback
            return response()->json([
                'success' => true,
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => array_slice($errors, 0, 10),
                'errors_count' => count($errors),
                'total_errors' => count($errors),
                'message' => "Imported: {$imported}, Updated: {$updated}, Skipped: {$skipped}"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ GROUP MULTIPLE ROWS INTO SINGLE SUPPLIER RECORDS
     * This handles the format where one supplier has data split across multiple rows
     */
    private function groupSupplierRows($data, $columnMap)
    {
        $groupedSuppliers = [];
        $currentSupplierKey = null;
        $rowNumber = 2; // Starting row number (after header)

        foreach ($data as $row) {
            // Skip completely empty rows
            if ($this->isEmptyRow($row)) {
                $rowNumber++;
                continue;
            }

            // Extract data from current row
            $rowData = $this->extractSupplierData($row, $columnMap);

            // Check if this row has a supplier name (indicates new supplier or continuation)
            $hasSupplierName = !empty($rowData['supplier_name']);

            if ($hasSupplierName) {
                // This is a new supplier or first row of a supplier
                $supplierName = $rowData['supplier_name'];
                
                // Generate supplier code if not provided
                if (empty($rowData['supplier_code'])) {
                    $rowData['supplier_code'] = $this->generateSupplierCode($supplierName, $rowNumber);
                }

                $currentSupplierKey = $rowData['supplier_code'];

                // Initialize or update supplier data
                if (!isset($groupedSuppliers[$currentSupplierKey])) {
                    $groupedSuppliers[$currentSupplierKey] = $rowData;
                } else {
                    // Merge with existing data (shouldn't happen often, but just in case)
                    $groupedSuppliers[$currentSupplierKey] = array_merge(
                        $groupedSuppliers[$currentSupplierKey],
                        $rowData
                    );
                }
            } elseif ($currentSupplierKey !== null) {
                // This row belongs to the current supplier (continuation row)
                // Merge additional data like contact numbers, emails, etc.
                
                // Handle contact person (can be multiple)
                if (!empty($rowData['contact_person'])) {
                    if (!empty($groupedSuppliers[$currentSupplierKey]['contact_person'])) {
                        // Append to existing contact person with separator
                        $groupedSuppliers[$currentSupplierKey]['contact_person'] .= ', ' . $rowData['contact_person'];
                    } else {
                        $groupedSuppliers[$currentSupplierKey]['contact_person'] = $rowData['contact_person'];
                    }
                }

                // Handle contact numbers (can be multiple)
                if (!empty($rowData['contact_number'])) {
                    if (!empty($groupedSuppliers[$currentSupplierKey]['contact_number'])) {
                        // Append to existing contact number with separator
                        $groupedSuppliers[$currentSupplierKey]['contact_number'] .= ', ' . $rowData['contact_number'];
                    } else {
                        $groupedSuppliers[$currentSupplierKey]['contact_number'] = $rowData['contact_number'];
                    }
                }

                // Handle email addresses (can be multiple)
                if (!empty($rowData['email'])) {
                    if (!empty($groupedSuppliers[$currentSupplierKey]['email'])) {
                        // Append to existing email with separator
                        $groupedSuppliers[$currentSupplierKey]['email'] .= ', ' . $rowData['email'];
                    } else {
                        $groupedSuppliers[$currentSupplierKey]['email'] = $rowData['email'];
                    }
                }

                // For address, terms, and other single-value fields, only update if current row has data
                foreach (['address', 'tin', 'bank', 'account_name', 'account_number', 'terms'] as $field) {
                    if (!empty($rowData[$field]) && empty($groupedSuppliers[$currentSupplierKey][$field])) {
                        $groupedSuppliers[$currentSupplierKey][$field] = $rowData[$field];
                    }
                }
            }

            $rowNumber++;
        }

        return $groupedSuppliers;
    }

    /**
     * Generate a unique supplier code
     */
    private function generateSupplierCode($supplierName, $rowNumber)
    {
        // Take first 6 characters of supplier name (alphanumeric only)
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $supplierName), 0, 6));
        if (empty($prefix)) {
            $prefix = 'SUP';
        }
        
        return $prefix . '_' . time() . '_' . $rowNumber;
    }

    /**
     * Map column headers to standard field names
     */
    private function mapColumns($headers)
    {
        $columnMap = [];

        $mappings = [
            'supplier_code' => ['supplier_code', 'supplier code', 'code'],
            'supplier' => ['supplier', 'supplier name', 'supplier_name', 'name'],
            'contact_person' => ['contact_person', 'contact person', 'contact', 'contact name'],
            'email_address' => ['email_address', 'email address', 'email'],
            'company_address' => ['company_address', 'company address', 'address'],
            'contact_number' => ['contact_number', 'contact number', 'contact #', 'contact#', 'phone', 'telephone', 'tel'],
            'terms' => ['terms', 'payment_terms', 'payment terms'],
            'tin' => ['tin', 'tax_id', 'tax id', 'tax identification number'],
            'bank' => ['bank', 'bank_name', 'bank name'],
            'account_name' => ['account_name', 'account name', 'bank_account_name'],
            'account_number' => ['account_number', 'account number', 'bank_account_number', 'account_no'],
        ];

        foreach ($headers as $index => $header) {
            $header = trim(strtolower($header));
            
            // Skip empty headers
            if (empty($header)) {
                continue;
            }
            
            foreach ($mappings as $field => $variations) {
                if (in_array($header, $variations)) {
                    $columnMap[$field] = $index;
                    break;
                }
            }
        }

        return $columnMap;
    }

    /**
     * Extract supplier data from row
     */
    private function extractSupplierData($row, $columnMap)
    {
        $data = [];

        // Only add fields that exist in the column map
        if (isset($columnMap['supplier_code'])) {
            $data['supplier_code'] = $this->getColumnValue($row, $columnMap, 'supplier_code');
        }
        
        if (isset($columnMap['supplier'])) {
            $data['supplier_name'] = $this->getColumnValue($row, $columnMap, 'supplier');
        }
        
        if (isset($columnMap['company_address'])) {
            $data['address'] = $this->getColumnValue($row, $columnMap, 'company_address');
        }
        
        if (isset($columnMap['email_address'])) {
            $data['email'] = $this->getColumnValue($row, $columnMap, 'email_address');
        }
        
        if (isset($columnMap['contact_number'])) {
            $data['contact_number'] = $this->getColumnValue($row, $columnMap, 'contact_number');
        }
        
        if (isset($columnMap['tin'])) {
            $data['tin'] = $this->getColumnValue($row, $columnMap, 'tin');
        }
        
        if (isset($columnMap['bank'])) {
            $data['bank'] = $this->getColumnValue($row, $columnMap, 'bank');
        }
        
        if (isset($columnMap['account_name'])) {
            $data['account_name'] = $this->getColumnValue($row, $columnMap, 'account_name');
        }
        
        if (isset($columnMap['account_number'])) {
            $data['account_number'] = $this->getColumnValue($row, $columnMap, 'account_number');
        }
        
        if (isset($columnMap['contact_person'])) {
            $data['contact_person'] = $this->getColumnValue($row, $columnMap, 'contact_person');
        }
        
        if (isset($columnMap['terms'])) {
            $data['terms'] = $this->getColumnValue($row, $columnMap, 'terms');
        }

        // Filter out null/empty values
        return array_filter($data, function($value) {
            return $value !== null && $value !== '';
        });
    }

    /**
     * Get value from row by column map
     */
    private function getColumnValue($row, $columnMap, $field)
    {
        if (isset($columnMap[$field]) && isset($row[$columnMap[$field]])) {
            $value = $row[$columnMap[$field]];
            return is_string($value) ? trim($value) : $value;
        }
        return null;
    }

    /**
     * Check if row is empty
     */
    private function isEmptyRow($row)
    {
        foreach ($row as $cell) {
            if (!empty(trim($cell))) {
                return false;
            }
        }
        return true;
    }
}