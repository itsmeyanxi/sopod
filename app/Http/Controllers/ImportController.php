<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CustomersImport;
use App\Imports\ItemsImport;

class ImportController extends Controller
{
    // =================== CUSTOMERS ===================
    
    public function showCustomersForm()
    {
        return view('import.customers');
    }
    
    public function importCustomers(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048'
        ]);
        
        try {
            Excel::import(new CustomersImport, $request->file('file'));
            
            return redirect()->back()->with('success', 'Customers imported successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
    
    public function downloadCustomersTemplate()
    {
        $headers = [
            'customer_code',
            'customer_name',
            'branch',
            'business_style',
            'billing_address',
            'tin',
            'shipping_address',
            'sales_executive'
        ];
        
        $filename = 'customers_template.csv';
        $handle = fopen('php://output', 'w');
        
        ob_start();
        fputcsv($handle, $headers);
        // Add sample row
        fputcsv($handle, [
            'CUST-001',
            'ABC Corporation',
            'Makati Branch',
            'Retail',
            '123 Main St, Makati City',
            '123-456-789-000',
            '456 Shipping St, Makati City',
            'Juan Dela Cruz'
        ]);
        fclose($handle);
        $csv = ob_get_clean();
        
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
    
    // =================== ITEMS ===================
    
    public function showItemsForm()
    {
        return view('import.items');
    }
    
    public function importItems(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048'
        ]);
        
        try {
            Excel::import(new ItemsImport, $request->file('file'));
            
            return redirect()->back()->with('success', 'Items imported successfully!');
        } catch (\Exception $e) {
            // Show detailed error for debugging
            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
        }
    }
    
        public function downloadItemsTemplate()
    {
        $headers = [
            'item_code',
            'item_description',
            'item_group',      
            'brand',
            'unit',             
            'unit_price'
        ];
        
        $filename = 'items_template.csv';
        $handle = fopen('php://output', 'w');
        
        ob_start();
        fputcsv($handle, $headers);
        // Add sample row
        fputcsv($handle, [
            'ITEM-001',
            'Sample Product',
            'This is a sample product description',
            'Electronics',
            'Samsung',
            'pcs',
            '999.99'
        ]);
        fclose($handle);
        $csv = ob_get_clean();
        
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}