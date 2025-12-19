<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: #00BCD4; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; }
        .info-box { background: #f9f9f9; border-left: 4px solid #00BCD4; padding: 15px; margin: 20px 0; }
        .info-row { display: flex; padding: 8px 0; border-bottom: 1px solid #eee; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: bold; width: 140px; color: #666; }
        .info-value { flex: 1; color: #333; }
        .button { display: inline-block; background: #00BCD4; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .footer { background: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .alert-success { background: #d4edda; border-left: 4px solid #28a745; padding: 12px; margin: 15px 0; }
        .alert-danger { background: #f8d7da; border-left: 4px solid #dc3545; padding: 12px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin: 0;">{{ $title ?? 'Delivery Status Changed' }}</h2>
        </div>
        
        <div class="content">
            <p>{{ $message ?? 'Delivery status has been updated.' }}</p>
            
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">DR Number:</span>
                    <span class="info-value"><strong>{{ $dr_no ?? 'N/A' }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sales Order:</span>
                    <span class="info-value">{{ $sales_order_number ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Customer:</span>
                    <span class="info-value">{{ $customer_name ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Action:</span>
                    <span class="info-value"><strong>{{ ucfirst($action ?? 'Updated') }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">By:</span>
                    <span class="info-value">{{ $actioned_by ?? 'System' }}</span>
                </div>
            </div>
            
            @if(isset($rejection_reason) && $rejection_reason)
            <div class="alert-danger">
                <strong>Rejection Reason:</strong> {{ $rejection_reason }}
            </div>
            @endif
            
            @if(isset($action) && $action === 'approved')
            <div class="alert-success">
                ✅ This delivery has been approved and can now proceed.
            </div>
            @endif
            
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