<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: #17a2b8; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; }
        .info-box { background: #f9f9f9; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0; }
        .button { display: inline-block; background: #17a2b8; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .footer { background: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin: 0;">{{ $title ?? 'Delivery Updated' }}</h2>
        </div>
        
        <div class="content">
            <p>{{ $message ?? 'A delivery has been updated.' }}</p>
            
            <div class="info-box">
                <p><strong>DR Number:</strong> {{ $dr_no ?? 'N/A' }}</p>
                <p><strong>Sales Order:</strong> {{ $sales_order_number ?? 'N/A' }}</p>
                <p><strong>Customer:</strong> {{ $customer_name ?? 'N/A' }}</p>
                <p><strong>Delivery Batch:</strong> {{ $delivery_batch ?? 'N/A' }}</p>
                <p><strong>Status:</strong> <span class="status-badge">{{ $approval_status ?? 'N/A' }}</span></p>
                <p><strong>Updated By:</strong> {{ $updated_by ?? 'System' }}</p>
                <p><strong>Updated At:</strong> {{ $updated_at ?? now()->format('M d, Y h:i A') }}</p>
            </div>
            
            <p style="margin-top: 25px;">
                <a href="{{ $view_url ?? '#' }}" class="button">View Delivery</a>
            </p>
        </div>
        
        <div class="footer">
            <p>This is an automated notification from SOPOD System.</p>
            <p>Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>