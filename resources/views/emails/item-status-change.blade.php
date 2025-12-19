<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: #FF9800; color: white; padding: 20px; text-align: center; }
        .header.approved { background: #28a745; }
        .header.rejected { background: #dc3545; }
        .content { padding: 30px; }
        .info-box { background: #f9f9f9; border-left: 4px solid #FF9800; padding: 15px; margin: 20px 0; }
        .info-box.approved { border-left-color: #28a745; }
        .info-box.rejected { border-left-color: #dc3545; }
        .button { display: inline-block; background: #FF9800; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .button.approved { background: #28a745; }
        .button.rejected { background: #dc3545; }
        .footer { background: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .alert-danger { background: #f8d7da; border-left: 4px solid #dc3545; padding: 12px; margin: 15px 0; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header {{ $action ?? '' }}">
            <h2 style="margin: 0;">{{ $title ?? 'Item Status Changed' }}</h2>
        </div>
        
        <div class="content">
            <p>{{ $message ?? 'Item status has been updated.' }}</p>
            
            <div class="info-box {{ $action ?? '' }}">
                <p><strong>Item Code:</strong> {{ $item_code ?? 'N/A' }}</p>
                <p><strong>Description:</strong> {{ $item_description ?? 'N/A' }}</p>
                <p><strong>Brand:</strong> {{ $brand ?? 'N/A' }}</p>
                <p><strong>Category:</strong> {{ $category ?? 'N/A' }}</p>
                <p><strong>{{ ucfirst($action ?? 'Updated') }} By:</strong> {{ $actioned_by ?? 'System' }}</p>
                @if(isset($rejection_reason) && $rejection_reason)
                <p><strong>Reason:</strong> {{ $rejection_reason }}</p>
                @endif
            </div>
            
            @if(isset($rejection_reason) && $rejection_reason)
            <div class="alert-danger">
                <strong>⚠️ Rejection Reason:</strong><br>
                {{ $rejection_reason }}
            </div>
            @endif
            
            <p style="margin-top: 25px;">
                <a href="{{ $view_url ?? '#' }}" class="button {{ $action ?? '' }}">View Item</a>
            </p>
        </div>
        
        <div class="footer">
            <p>This is an automated notification from SOPOD System.</p>
            <p>Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>