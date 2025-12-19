<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: #2196F3; color: white; padding: 20px; text-align: center; }
        .header.approved { background: #28a745; }
        .header.rejected { background: #dc3545; }
        .header.declined { background: #dc3545; }
        .header.cancelled { background: #6c757d; }
        .content { padding: 30px; }
        .info-box { background: #f9f9f9; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; }
        .info-box.approved { border-left-color: #28a745; }
        .info-box.rejected { border-left-color: #dc3545; }
        .info-box.declined { border-left-color: #dc3545; }
        .info-box.cancelled { border-left-color: #6c757d; }
        .info-row { display: flex; padding: 8px 0; border-bottom: 1px solid #eee; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: bold; width: 140px; color: #666; }
        .info-value { flex: 1; color: #333; }
        .button { display: inline-block; background: #2196F3; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .button.approved { background: #28a745; }
        .button.rejected { background: #dc3545; }
        .button.declined { background: #dc3545; }
        .button.cancelled { background: #6c757d; }
        .footer { background: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-declined { background: #f8d7da; color: #721c24; }
        .status-cancelled { background: #d1ecf1; color: #0c5460; }
        .alert { background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header {{ strtolower($new_status ?? 'updated') }}">
            <h2 style="margin: 0;">{{ $title ?? 'Sales Order Status Updated' }}</h2>
        </div>
        
        <div class="content">
            <p>{{ $emailMessage ?? 'Sales Order status has been updated.' }}</p>
            
            <div class="info-box {{ strtolower($new_status ?? 'updated') }}">
                <div class="info-row">
                    <span class="info-label">Sales Order No:</span>
                    <span class="info-value"><strong>{{ $sales_order_number ?? 'N/A' }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Customer:</span>
                    <span class="info-value">{{ $customer_name ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Previous Status:</span>
                    <span class="info-value">{{ $old_status ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">New Status:</span>
                    <span class="info-value">
                        <span class="status-badge status-{{ strtolower($new_status ?? 'pending') }}">{{ $new_status ?? 'N/A' }}</span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Updated By:</span>
                    <span class="info-value">{{ $updated_by ?? 'System' }}</span>
                </div>
            </div>
            
            @if(isset($notes) && $notes)
            <div class="alert">
                <strong>Notes:</strong> {{ $notes }}
            </div>
            @endif
            
            <p style="margin-top: 25px;">
                <a href="{{ $view_url ?? '#' }}" class="button {{ strtolower($new_status ?? '') }}">View Sales Order</a>
            </p>
        </div>
        
        <div class="footer">
            <p>This is an automated notification from SOPOD System.</p>
            <p>Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>