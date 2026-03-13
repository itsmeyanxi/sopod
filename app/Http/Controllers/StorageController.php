<?php

namespace App\Http\Controllers;

use App\Models\Storage;
use App\Models\Warehouse;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StorageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $storages = Storage::with('warehouse', 'creator')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('storages.index', compact('storages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $warehouses = Warehouse::where('status', 'active')->orderBy('warehouse_name')->get();
        return view('storages.create', compact('warehouses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // ✅ Generate storage code if not provided
        $storageCode = $request->storage_code;
        if (!$storageCode || trim($storageCode) === '') {
            $storageName = $request->storage_name;
            $abbr = substr(
                collect(explode(' ', $storageName))
                    ->map(fn($word) => strtoupper(substr($word, 0, 1)))
                    ->join(''),
                0,
                3
            );

            $timestamp = substr((string)time(), -6);
            $storageCode = "ST-{$abbr}-{$timestamp}";
        }

        $request->validate([
            'storage_code' => 'nullable|string|unique:storages,storage_code',
            'storage_name' => 'required|string',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'temperature_controlled' => 'in:Yes,No',
        ]);

        $storage = Storage::create([
            'storage_code' => $storageCode,
            'storage_name' => $request->storage_name,
            'warehouse_id' => $request->warehouse_id,
            'location' => $request->location,
            'description' => $request->description,
            'temperature_controlled' => $request->temperature_controlled ?? 'No',
            'capacity' => $request->capacity,
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Created',
            'item' => $storage->storage_code,
            'target' => $storage->storage_name,
            'type' => 'Storage',
            'message' => 'Created Storage ' . $storage->storage_name,
        ]);

        return redirect()
            ->route('storages.index')
            ->with('success', 'Storage created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Storage $storage)
    {
        $storage->load('warehouse', 'creator');
        return view('storages.show', compact('storage'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Storage $storage)
    {
        $warehouses = Warehouse::where('status', 'active')->orderBy('warehouse_name')->get();
        return view('storages.edit', compact('storage', 'warehouses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Storage $storage)
    {
        $request->validate([
            'storage_code' => "nullable|string|unique:storages,storage_code,{$storage->id}",
            'storage_name' => 'required|string',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'temperature_controlled' => 'in:Yes,No',
        ]);

        $storage->update([
            'storage_code' => $request->storage_code ?? $storage->storage_code,
            'storage_name' => $request->storage_name,
            'warehouse_id' => $request->warehouse_id,
            'location' => $request->location,
            'description' => $request->description,
            'temperature_controlled' => $request->temperature_controlled,
            'capacity' => $request->capacity,
            'status' => $request->status ?? $storage->status,
        ]);

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Updated',
            'item' => $storage->storage_code,
            'target' => $storage->storage_name,
            'type' => 'Storage',
            'message' => 'Updated Storage ' . $storage->storage_name,
        ]);

        return redirect()
            ->route('storages.show', $storage->id)
            ->with('success', 'Storage updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Storage $storage)
    {
        $name = $storage->storage_name;
        $code = $storage->storage_code;
        $storage->delete();

        Activity::create([
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Deleted',
            'item' => $code,
            'target' => 'N/A',
            'type' => 'Storage',
            'message' => 'Deleted Storage ' . $name,
        ]);

        return redirect()
            ->route('storages.index')
            ->with('success', 'Storage deleted successfully!');
    }
}
