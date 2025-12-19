<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalesOrderChange;
use App\Models\ChangeNotification;
use App\Models\SalesOrder;

class ChangeLogController extends Controller
{
    // View all changes
    public function index(Request $request)
    {
        // Authorization is already handled in routes - no need for additional check
        
        $query = SalesOrderChange::with(['salesOrder', 'user'])->latest();

        // Filter by field
        if ($request->field) {
            $query->where('field_changed', $request->field);
        }

        // Filter by date range
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Filter by sales order
        if ($request->sales_order_id) {
            $query->where('sales_order_id', $request->sales_order_id);
        }

        $changes = $query->paginate(20);

        // Get available fields for filter
        $fields = SalesOrderChange::distinct()->pluck('field_changed');

        return view('changelog.index', compact('changes', 'fields'));
    }

    // View changes for specific sales order
    public function salesOrderChanges($id)
    {
        // Authorization is already handled in routes - no need for additional check
        
        $salesOrder = SalesOrder::findOrFail($id);
        $changes = SalesOrderChange::where('sales_order_id', $id)
            ->with('user')
            ->latest()
            ->get();

        return view('changelog.sales-order', compact('salesOrder', 'changes'));
    }

    /**
     * Get specific action type for display
     */
    public function getActionType($change)
    {
        $field = $change->field_changed;
        $type = $change->change_type;

        // Handle item-related changes
        if (strpos($field, 'item_') === 0) {
            if ($type === 'create') {
                return 'added';
            } elseif ($type === 'delete') {
                return 'removed';
            } else {
                return 'updated';
            }
        }

        // Handle other fields
        if ($type === 'create') {
            return 'added';
        } elseif ($type === 'delete') {
            return 'removed';
        } else {
            return 'updated';
        }
    }

    /**
     * Get detailed change description
     */
    public function getChangeDescription($change)
    {
        $field = $change->field_changed;
        $type = $change->change_type;
        $soNumber = $change->salesOrder->sales_order_number ?? 'SO';

        // Handle specific field changes
        switch ($field) {
            case 'quantity':
                return "Quantity for {$soNumber} was changed from <span class='text-red-400 font-semibold'>{$change->old_value}</span> to <span class='text-green-400 font-semibold'>{$change->new_value}</span>";
            
            case 'unit_price':
                return "Unit Price for {$soNumber} was changed from <span class='text-red-400 font-semibold'>{$change->old_value}</span> to <span class='text-green-400 font-semibold'>{$change->new_value}</span>";
            
            case 'product_name':
                return "Product name for {$soNumber} was changed from <span class='text-red-400'>{$change->old_value}</span> to <span class='text-green-400'>{$change->new_value}</span>";
            
            case 'line_item':
                $newData = json_decode($change->new_value, true);
                $oldData = json_decode($change->old_value, true);
                
                if ($type === 'create' && $newData) {
                    $productName = $newData['product_name'] ?? 'Unknown Product';
                    $quantity = $newData['quantity'] ?? 0;
                    $unit = $newData['unit'] ?? 'pcs';
                    $price = isset($newData['unit_price']) ? '₱' . number_format($newData['unit_price'], 2) : 'N/A';
                    return "Added item: <strong>{$productName}</strong> | Qty: {$quantity} {$unit} | Price: {$price}";
                } 
                elseif ($type === 'delete' && $oldData) {
                    $productName = $oldData['product_name'] ?? 'Unknown Product';
                    $quantity = $oldData['quantity'] ?? 0;
                    $unit = $oldData['unit'] ?? 'pcs';
                    return "Removed item: <strong>{$productName}</strong> (was {$quantity} {$unit})";
                }
                return "Line item changed";
            
            case 'status':
                return "Status for {$soNumber} was changed from <strong class='text-red-400'>{$change->old_value}</strong> to <strong class='text-green-400'>{$change->new_value}</strong>";
            
            case 'customer_id':
                return "Customer for {$soNumber} was changed from <strong class='text-red-400'>{$change->old_value}</strong> to <strong class='text-green-400'>{$change->new_value}</strong>";
            
            case 'total_amount':
                return "Total amount for {$soNumber} was changed from <strong class='text-red-400'>{$change->old_value}</strong> to <strong class='text-green-400'>{$change->new_value}</strong>";
            
            case 'po_number':
                return "PO Number for {$soNumber} was changed from <strong class='text-red-400'>{$change->old_value}</strong> to <strong class='text-green-400'>{$change->new_value}</strong>";
            
            case 'request_delivery_date':
                return "Requested delivery date for {$soNumber} was changed from <strong class='text-red-400'>{$change->old_value}</strong> to <strong class='text-green-400'>{$change->new_value}</strong>";
            
            case 'shipping_address':
                if ($type === 'create') {
                    return "Added shipping address to {$soNumber}";
                } elseif ($type === 'delete') {
                    return "Removed shipping address from {$soNumber}";
                } else {
                    return "Shipping address for {$soNumber} was updated";
                }
            
            case 'additional_instructions':
                if ($type === 'create') {
                    return "Added instructions to {$soNumber}";
                } elseif ($type === 'delete') {
                    return "Removed instructions from {$soNumber}";
                } else {
                    return "Instructions for {$soNumber} were updated";
                }
            
            case 'sales_rep':
                return "Sales representative for {$soNumber} was changed from <strong class='text-red-400'>{$change->old_value}</strong> to <strong class='text-green-400'>{$change->new_value}</strong>";
            
            default:
                $fieldName = ucwords(str_replace('_', ' ', $field));
                if ($type === 'create') {
                    return "Added {$fieldName} to {$soNumber}: <strong>{$change->new_value}</strong>";
                } elseif ($type === 'delete') {
                    return "Removed {$fieldName} from {$soNumber}: <strong>{$change->old_value}</strong>";
                } else {
                    return "{$fieldName} for {$soNumber} was changed from <strong class='text-red-400'>{$change->old_value}</strong> to <strong class='text-green-400'>{$change->new_value}</strong>";
                }
        }
    }

    /**
     * Get field display name
     */
    public function getFieldDisplay($field)
    {
        $fieldMap = [
            'status' => 'Order Status',
            'customer_id' => 'Customer',
            'total_amount' => 'Total Amount',
            'quantity' => 'Quantity',
            'unit_price' => 'Unit Price',
            'product_name' => 'Product Name',
            'line_item' => 'Line Item Added',
            'po_number' => 'PO Number',
            'request_delivery_date' => 'Delivery Date',
            'shipping_address' => 'Shipping Address',
            'sales_rep' => 'Sales Rep',
            'additional_instructions' => 'Instructions',
        ];
        
        return $fieldMap[$field] ?? ucwords(str_replace('_', ' ', $field));
    }

    // View user's notifications
    public function notifications()
    {
        $notifications = ChangeNotification::where('user_id', auth()->id())
            ->with(['change.salesOrder', 'change.user'])
            ->latest()
            ->paginate(15);

        $unreadCount = ChangeNotification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return view('changelog.notifications', compact('notifications', 'unreadCount'));
    }

    // Mark notification as read
    public function markAsRead($id)
    {
        $notification = ChangeNotification::where('user_id', auth()->id())
            ->findOrFail($id);
        
        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read');
    }

    // Mark all notifications as read
    public function markAllAsRead()
    {
        ChangeNotification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return back()->with('success', 'All notifications marked as read');
    }

    // Get unread count (for AJAX/API)
    public function unreadCount()
    {
        $count = ChangeNotification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    // Export changes to CSV
    public function export(Request $request)
    {
        // Authorization is already handled in routes - no need for additional check
        
        $query = SalesOrderChange::with(['salesOrder', 'user']);

        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $changes = $query->get();

        $filename = 'sales_order_changes_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($changes) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            // Header
            fputcsv($file, [
                'Date',
                'Time',
                'Sales Order',
                'Action',
                'Field Changed',
                'Change Description',
                'Changed By',
            ]);

            // Data
            foreach ($changes as $change) {
                fputcsv($file, [
                    $change->created_at->format('Y-m-d'),
                    $change->created_at->format('H:i:s'),
                    $change->salesOrder->sales_order_number ?? 'N/A',
                    ucfirst($this->getActionType($change)),
                    $this->getFieldDisplay($change->field_changed),
                    strip_tags($this->getChangeDescription($change)),
                    $change->user->name ?? 'System',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}