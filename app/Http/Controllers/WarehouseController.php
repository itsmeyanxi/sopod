<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::with('creator')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        return view('warehouses.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'warehouse_code' => 'required|string|unique:warehouses,warehouse_code',
            'warehouse_name' => 'required|string',
            'email'          => 'nullable|email',
            'documents.*'    => 'nullable|file|max:10240',
        ]);

        // Handle document uploads
        $documents = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $index => $file) {
                $path = $file->store('warehouse_documents', 'public');
                $documents[] = [
                    'name' => $request->document_names[$index] ?? $file->getClientOriginalName(),
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                ];
            }
        }

        $warehouse = Warehouse::create([
            'warehouse_code' => $request->warehouse_code,
            'warehouse_name' => $request->warehouse_name,
            'address'        => $request->address,
            'email'          => $request->email,
            'contact_number' => $request->contact_number,
            'tin'            => $request->tin,
            'bank'           => $request->bank,
            'account_name'   => $request->account_name,
            'account_number' => $request->account_number,
            'status'         => 'active',
            'documents'      => $documents,
            'created_by'     => Auth::id(),
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action'    => 'Created',
            'item'      => $warehouse->warehouse_code,
            'target'    => $warehouse->warehouse_name,
            'type'      => 'Warehouse',
            'message'   => 'Created Warehouse ' . $warehouse->warehouse_name,
        ]);

        return redirect()
            ->route('warehouses.index')
            ->with('success', 'Warehouse created successfully!');
    }

    public function show($id)
    {
        $warehouse = Warehouse::with('creator')->findOrFail($id);
        return view('warehouses.show', compact('warehouse'));
    }

    public function edit($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        return view('warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);

        $request->validate([
            'warehouse_code' => 'required|string|unique:warehouses,warehouse_code,' . $id,
            'warehouse_name' => 'required|string',
            'email'          => 'nullable|email',
            'documents.*'    => 'nullable|file|max:10240',
        ]);

        // Keep existing documents and append new ones
        $documents = $warehouse->documents ?? [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $index => $file) {
                $path = $file->store('warehouse_documents', 'public');
                $documents[] = [
                    'name'          => $request->document_names[$index] ?? $file->getClientOriginalName(),
                    'path'          => $path,
                    'original_name' => $file->getClientOriginalName(),
                ];
            }
        }

        $warehouse->update([
            'warehouse_code' => $request->warehouse_code,
            'warehouse_name' => $request->warehouse_name,
            'address'        => $request->address,
            'email'          => $request->email,
            'contact_number' => $request->contact_number,
            'tin'            => $request->tin,
            'bank'           => $request->bank,
            'account_name'   => $request->account_name,
            'account_number' => $request->account_number,
            'status'         => $request->status ?? $warehouse->status,
            'documents'      => $documents,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action'    => 'Updated',
            'item'      => $warehouse->warehouse_code,
            'target'    => $warehouse->warehouse_name,
            'type'      => 'Warehouse',
            'message'   => 'Updated Warehouse ' . $warehouse->warehouse_name,
        ]);

        return redirect()
            ->route('warehouses.show', $warehouse->id)
            ->with('success', 'Warehouse updated successfully!');
    }

    public function destroy($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $name = $warehouse->warehouse_name;
        $warehouse->delete();

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action'    => 'Deleted',
            'item'      => $warehouse->warehouse_code,
            'target'    => 'N/A',
            'type'      => 'Warehouse',
            'message'   => 'Deleted Warehouse ' . $name,
        ]);

        return redirect()
            ->route('warehouses.index')
            ->with('success', 'Warehouse deleted successfully!');
    }
}