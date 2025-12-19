<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: #6c757d; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; }
        .info-box { background: #f9f9f9; border-left: 4px solid #6c757d; padding: 15px; margin: 20px 0; }
        .info-row { display: flex; padding: 8px 0; border-bottom: 1px solid #eee; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: bold; width: 140px; color: #666; }
        .info-value { flex: 1; color: #333; }
        .button { display: inline-block; background: #6c757d; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .footer { background: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .alert-info { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 12px; margin: 15px 0; color: #0c5460; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin: 0;">{{ $title ?? 'Sales Order Closed' }}</h2>
        </div>
        
        <div class="content">
            <p>{{ $emailMessage ?? 'Sales Order has been closed.' }}</p>
            
            <div class="alert-info">
                ✅ All items in this sales order have been fully delivered. This order is now complete.
            </div>
            
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Sales Order No:</span>
                    <span class="info-value"><strong>{{ $sales_order_number ?? 'N/A' }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Customer:</span>
                    <span class="info-value">{{ $customer_name ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Amount:</span>
                    <span class="info-value">₱{{ $total_amount ?? '0.00' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="status-badge">Closed</span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Closed By:</span>
                    <span class="info-value">{{ $closed_by ?? 'System' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Closed At:</span>
                    <span class="info-value">{{ $closed_at ?? now()->format('M d, Y h:i A') }}</span>
                </div>
            </div>
            
            <p style="margin-top: 25px;">
                <a href="{{ $view_url ?? '#' }}" class="button">View Sales Order</a>
            </p>
        </div>
        
        <div class="footer">
            <p>This is an automated notification from SOPOD System.</p>
            <p>Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>