<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierDocument;
use App\Models\Vendor;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SuppliersController extends Controller
{
    /**
     * Display a listing of suppliers
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'vendors');
        $search = $request->get('search', '');
        $category = $request->get('category', '');

        $suppliers = Supplier::with('creator')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->query());

        $vendorsQuery = Vendor::query()->orderBy('vendor_name');

        if ($category) {
            $vendorsQuery->where('category', $category);
        }
        if ($search) {
            $vendorsQuery->where(function ($q) use ($search) {
                $q->where('vendor_code', 'like', "%{$search}%")
                  ->orWhere('vendor_name', 'like', "%{$search}%");
            });
        }

        $vendors = $vendorsQuery->paginate(30)->appends($request->query());

        $vendorCategoryCounts = Vendor::selectRaw('category, COUNT(*) as cnt')
            ->groupBy('category')
            ->pluck('cnt', 'category');

        return view('suppliers.index', compact('suppliers', 'vendors', 'tab', 'search', 'category', 'vendorCategoryCounts'));
    }

    /**
     * Show the form for creating a new supplier
     */
    public function create()
    {
        return view('suppliers.create');
    }

    /**
     * Store a newly created supplier
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_code' => 'required|string|unique:suppliers,supplier_code',
            'supplier_name' => 'required|string',
            'address' => 'nullable|string',
            'email' => 'nullable|string',
            'contact_number' => 'nullable|string',
            'contact_person' => 'nullable|string',
            'terms' => 'nullable|string',
            'tin' => 'nullable|string',
            'bank' => 'nullable|string',
            'account_name' => 'nullable|string',
            'account_number' => 'nullable|string',
            'bank2' => 'nullable|string',
            'account_name2' => 'nullable|string',
            'account_number2' => 'nullable|string',
            'storage' => 'nullable|string',
            'documents.*' => 'nullable|file|max:10240',
            'document_names.*' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $supplier = Supplier::create([
                'supplier_code' => $request->supplier_code,
                'supplier_name' => $request->supplier_name,
                'address' => $request->address,
                'email' => $request->email,
                'contact_number' => $request->contact_number,
                'contact_person' => $request->contact_person,
                'terms' => $request->terms,
                'tin' => $request->tin,
                'bank' => $request->bank,
                'account_name' => $request->account_name,
                'account_number' => $request->account_number,
                'bank2' => $request->bank2,
                'account_name2' => $request->account_name2,
                'account_number2' => $request->account_number2,
                'storage' => $request->storage,
                'status' => 'active',
                'created_by' => Auth::id(),
            ]);

            // Upload documents if provided
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $index => $file) {
                    $originalName = $file->getClientOriginalName();
                    $documentName = $request->document_names[$index] ?? pathinfo($originalName, PATHINFO_FILENAME);
                    $storedPath = $file->store('supplier_documents/' . $supplier->id, 'public');

                    SupplierDocument::create([
                        'supplier_id' => $supplier->id,
                        'document_name' => $documentName,
                        'original_filename' => $originalName,
                        'file_path' => $storedPath,
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => Auth::id(),
                    ]);
                }
            }

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Created',
                'item' => $supplier->supplier_code . ' - ' . $supplier->supplier_name,
                'target' => $supplier->address ?? 'N/A',
                'type' => 'Supplier',
                'message' => 'Added new supplier: ' . $supplier->supplier_name . ' (' . $supplier->supplier_code . ')',
            ]);

            return redirect()
                ->route('suppliers.show', $supplier->id)
                ->with('success', 'Supplier created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error creating supplier: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified supplier
     */
    public function show($id)
    {
        $supplier = Supplier::with(['creator', 'documents.uploader'])->findOrFail($id);

        return view('suppliers.show', compact('supplier'));
    }

    /**
     * Show the form for editing the specified supplier
     */
    public function edit($id)
    {
        $supplier = Supplier::findOrFail($id);

        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified supplier
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'supplier_code' => 'required|string|unique:suppliers,supplier_code,' . $id,
            'supplier_name' => 'required|string',
            'address' => 'nullable|string',
            'email' => 'nullable|string',
            'contact_number' => 'nullable|string',
            'contact_person' => 'nullable|string',
            'terms' => 'nullable|string',
            'tin' => 'nullable|string',
            'bank' => 'nullable|string',
            'account_name' => 'nullable|string',
            'account_number' => 'nullable|string',
            'bank2' => 'nullable|string',
            'account_name2' => 'nullable|string',
            'account_number2' => 'nullable|string',
            'storage' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $supplier = Supplier::findOrFail($id);

            $supplier->update([
                'supplier_code' => $request->supplier_code,
                'supplier_name' => $request->supplier_name,
                'address' => $request->address,
                'email' => $request->email,
                'contact_number' => $request->contact_number,
                'contact_person' => $request->contact_person,
                'terms' => $request->terms,
                'tin' => $request->tin,
                'bank' => $request->bank,
                'account_name' => $request->account_name,
                'account_number' => $request->account_number,
                'bank2' => $request->bank2,
                'account_name2' => $request->account_name2,
                'account_number2' => $request->account_number2,
                'storage' => $request->storage,
            ]);

            DB::commit();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Updated',
                'item' => $supplier->supplier_code . ' - ' . $supplier->supplier_name,
                'target' => $supplier->address ?? 'N/A',
                'type' => 'Supplier',
                'message' => 'Updated supplier: ' . $supplier->supplier_name . ' (' . $supplier->supplier_code . ')',
            ]);

            return redirect()
                ->route('suppliers.show', $supplier->id)
                ->with('success', 'Supplier updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error updating supplier: ' . $e->getMessage());
        }
    }

    /**
     * Toggle supplier status
     */
    public function toggleStatus($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $newStatus = $supplier->status === 'active' ? 'inactive' : 'active';
            $supplier->status = $newStatus;
            $supplier->save();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Status Changed',
                'item' => $supplier->supplier_code . ' - ' . $supplier->supplier_name,
                'target' => $newStatus,
                'type' => 'Supplier',
                'message' => 'Supplier ' . $supplier->supplier_name . ' status changed to ' . $newStatus,
            ]);

            return back()->with('success', 'Supplier status updated successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error updating supplier status: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified supplier
     */
    public function destroy($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $supplierName = $supplier->supplier_code . ' - ' . $supplier->supplier_name;
            $supplier->delete();

            Activity::create([
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Deleted',
                'item' => $supplierName,
                'target' => 'N/A',
                'type' => 'Supplier',
                'message' => 'Deleted supplier: ' . $supplierName,
            ]);

            return redirect()
                ->route('suppliers.index')
                ->with('success', 'Supplier deleted successfully!');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Error deleting supplier: ' . $e->getMessage());
        }
    }

    private function nextVendorCode(string $category): string
    {
        $prefix = match(strtoupper($category)) {
            'EMPLOYEES' => 'VE',
            'NON TRADE' => 'VN',
            default     => 'VT',
        };
        $last = Vendor::where('vendor_code', 'LIKE', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(vendor_code, 3) AS UNSIGNED) DESC')
            ->value('vendor_code');
        $num = $last ? ((int) substr($last, 2)) + 1 : 1;
        return $prefix . str_pad($num, 7, '0', STR_PAD_LEFT);
    }

    public function createVendor(Request $request)
    {
        $category = $request->input('category', 'TRADE');
        $nextCode = $this->nextVendorCode($category);
        return view('suppliers.vendor_create', compact('nextCode', 'category'));
    }

    public function storeVendor(Request $request)
    {
        $request->validate([
            'vendor_name' => 'required|string',
            'category'    => 'required|in:TRADE,NON TRADE,EMPLOYEES',
        ]);

        $code = $this->nextVendorCode($request->category);

        Vendor::create(array_merge($request->only([
            'vendor_name','name_2307','category','group','gl_account','status',
            'company','ee_id','last_name','first_name','middle_name',
            'position','department','location','office_address','date_hired',
            'billing_street','billing_block','billing_city','billing_zip','billing_country',
            'shipping_street','shipping_block','shipping_city','shipping_zip','shipping_country',
            'payment_terms','selling_price_list','vat','withholding','registration','tin','account',
        ]), ['vendor_code' => $code, 'status' => $request->status ?? 'active']));

        return redirect()->route('suppliers.index', ['tab' => 'vendors'])
            ->with('success', "Vendor created with code {$code}!");
    }

    public function editVendor($id)
    {
        $vendor = Vendor::findOrFail($id);
        return view('suppliers.vendor_edit', compact('vendor'));
    }

    public function updateVendor(Request $request, $id)
    {
        $request->validate([
            'vendor_code' => 'required|string|unique:vendors,vendor_code,' . $id,
            'vendor_name' => 'required|string',
            'category'    => 'nullable|string',
            'group'       => 'nullable|string',
            'gl_account'  => 'nullable|string',
            'status'      => 'required|in:active,inactive',
            'date_hired'  => 'nullable|date',
        ]);

        $vendor = Vendor::findOrFail($id);
        $vendor->update($request->only([
            'vendor_code','vendor_name','name_2307','category','group','gl_account','status',
            'company','ee_id','last_name','first_name','middle_name',
            'position','department','location','office_address','date_hired',
            'billing_street','billing_block','billing_city','billing_zip','billing_country',
            'shipping_street','shipping_block','shipping_city','shipping_zip','shipping_country',
            'payment_terms','selling_price_list','vat','withholding','registration','tin','account',
        ]));

        return redirect()->route('suppliers.index', ['tab' => 'vendors'])
            ->with('success', 'Vendor updated successfully!');
    }

    public function destroyVendor($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->delete();

        return redirect()->route('suppliers.index', ['tab' => 'vendors'])
            ->with('success', 'Vendor deleted successfully!');
    }

    /**
     * Export suppliers to Excel
     */
    public function export()
    {
        $suppliers = Supplier::all();

        $filename = 'suppliers_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($suppliers) {
            $file = fopen('php://output', 'w');

            // Add headers
            fputcsv($file, [
                'Supplier Code',
                'Supplier Name',
                'Payment Terms',
                'Contact Person',
                'Address',
                'Email Addresses',
                'Contact Number',
                'TIN',
                'Bank',
                'Account Name',
                'Account Number',
                'Bank 2',
                'Account Name 2',
                'Account Number 2',
                'Status',
                'Created At'
            ]);

            // Add data
            foreach ($suppliers as $supplier) {
                fputcsv($file, [
                    $supplier->supplier_code,
                    $supplier->supplier_name,
                    $supplier->terms,
                    $supplier->contact_person,
                    $supplier->address,
                    $supplier->email,
                    $supplier->contact_number,
                    $supplier->tin,
                    $supplier->bank,
                    $supplier->account_name,
                    $supplier->account_number,
                    $supplier->bank2,
                    $supplier->account_name2,
                    $supplier->account_number2,
                    $supplier->status,
                    $supplier->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Upload business documents for a supplier
     */
    public function uploadDocuments(Request $request, $id)
    {
        $request->validate([
            'documents' => 'required|array|min:1',
            'documents.*' => 'required|file|max:10240',
            'document_names' => 'nullable|array',
            'document_names.*' => 'nullable|string|max:255',
        ]);

        $supplier = Supplier::findOrFail($id);

        try {
            foreach ($request->file('documents') as $index => $file) {
                $originalName = $file->getClientOriginalName();
                $documentName = $request->document_names[$index] ?? pathinfo($originalName, PATHINFO_FILENAME);
                $storedPath = $file->store('supplier_documents/' . $supplier->id, 'public');

                SupplierDocument::create([
                    'supplier_id' => $supplier->id,
                    'document_name' => $documentName,
                    'original_filename' => $originalName,
                    'file_path' => $storedPath,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => Auth::id(),
                ]);
            }

            return redirect()
                ->route('suppliers.show', $supplier->id)
                ->with('success', 'Documents uploaded successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error uploading documents: ' . $e->getMessage());
        }
    }

    /**
     * Download a supplier document
     */
    public function downloadDocument($id, $documentId)
    {
        $document = SupplierDocument::where('supplier_id', $id)
            ->findOrFail($documentId);

        $filePath = storage_path('app/public/' . $document->file_path);

        if (!file_exists($filePath)) {
            return back()->with('error', 'File not found.');
        }

        return response()->download($filePath, $document->original_filename);
    }

    /**
     * View a supplier document (for images/PDFs)
     */
    public function viewDocument($id, $documentId)
    {
        $document = SupplierDocument::where('supplier_id', $id)
            ->findOrFail($documentId);

        $filePath = storage_path('app/public/' . $document->file_path);

        if (!file_exists($filePath)) {
            return back()->with('error', 'File not found.');
        }

        return response()->file($filePath, [
            'Content-Type' => $document->file_type,
        ]);
    }

    /**
     * Delete a supplier document
     */
    public function deleteDocument($id, $documentId)
    {
        try {
            $document = SupplierDocument::where('supplier_id', $id)
                ->findOrFail($documentId);

            Storage::disk('public')->delete($document->file_path);
            $document->delete();

            return back()->with('success', 'Document deleted successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error deleting document: ' . $e->getMessage());
        }
    }
}
