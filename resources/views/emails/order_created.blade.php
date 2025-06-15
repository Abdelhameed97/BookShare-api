<!DOCTYPE html>
<html>
<head>
    <title>New Order Notification</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2d3748;">New Order Request</h2>
        
        <p>Hello {{ $order->owner->user->name }},</p>
        
        <p>A new order has been placed for your book.</p>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0;">Order Details:</h3>
            <p><strong>Book:</strong> {{ $order->book->title }}</p>
            <p><strong>Quantity:</strong> {{ $order->quantity }}</p>
            <p><strong>Total Price:</strong> ${{ $order->total_price }}</p>
            <p><strong>Customer:</strong> {{ $order->client->user->name }}</p>
        </div>
        
        <p>Please review and respond to this order as soon as possible.</p>
        
        <p>Best regards,<br>BookShare Team</p>
    </div>
</body>
</html> 