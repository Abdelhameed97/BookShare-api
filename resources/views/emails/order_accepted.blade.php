<!DOCTYPE html>
<html>
<head>
    <title>Order Accepted</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2d3748;">Your Order Has Been Accepted!</h2>
        
        <p>Hello {{ $order->client->user->name }},</p>
        
        <p>Great news! Your order has been accepted by the book owner.</p>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0;">Order Details:</h3>
            <p><strong>Book:</strong> {{ $order->book->title }}</p>
            <p><strong>Quantity:</strong> {{ $order->quantity }}</p>
            <p><strong>Total Price:</strong> ${{ $order->total_price }}</p>
            <p><strong>Status:</strong> Accepted</p>
        </div>
        
        <p>We'll contact you shortly with further instructions for payment and delivery.</p>
        
        <p>Thank you for using BookShare!</p>
        
        <p>Best regards,<br>BookShare Team</p>
    </div>
</body>
</html> 