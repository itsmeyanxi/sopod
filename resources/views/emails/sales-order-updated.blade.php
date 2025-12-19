<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; }
        .info-box { background: #f9f9f9; border-left: 4px solid #007bff; padding: 15px; margin: 20px 0; }
        .button { display: inline-block; background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .footer { background: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin: 0;">{{ $title ?? 'Sales Order Updated' }}</h2>
        </div>
        
        <div class="content">
            <p>{{ $emailMessage ?? 'A sales order has been updated.' }}</p>
            
            <div class="info-box">
                <p><strong>SO Number:</strong> {{ $sales_order_number ?? 'N/A' }}</p>
                <p><strong>Customer:</strong> {{ $customer_name ?? 'N/A' }}</p>
                <p><strong>Total Amount:</strong> ₱{{ $total_amount ?? '0.00' }}</p>
                <p><strong>Updated By:</strong> {{ $updated_by ?? 'System' }}</p>
                <p><strong>Updated At:</strong> {{ $updated_at ?? now()->format('M d, Y h:i A') }}</p>
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