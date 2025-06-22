<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Rejected</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px; color: #333;">
    <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; padding: 25px; box-shadow: 0 0 10px rgba(0,0,0,0.05);">

        {{-- Logo --}}
        <div style="text-align: center;">
            <img src="{{ asset('storage/logo.png') }}" alt="BookShare Logo" style="max-width: 100px; margin-bottom: 20px;">
        </div>

        {{-- Title --}}
        <h2 style="color: #dc3545; text-align: center;">Order Rejected ❌</h2>

        {{-- Body --}}
        <p>Dear {{ $client->name }},</p>

        <p>Unfortunately, your order has been rejected by the book owner.</p>

        <p><strong>Order Summary:</strong></p>
        <ul>
            <li><strong>Order ID:</strong> #{{ $order->id }}</li>
            <li><strong>Book Title:</strong> {{ $book->title }}</li>
            <li><strong>Total Price:</strong> {{ $order->total_price }} EGP</li>
            <li><strong>Payment Method:</strong> {{ $order->payment_method }}</li>
            <li><strong>Order Date:</strong> {{ $order->created_at->format('Y-m-d H:i') }}</li>
        </ul>

        {{-- Optional Reason (if available) --}}
        @if(!empty($rejection_reason))
            <p><strong>Reason for rejection:</strong></p>
            <blockquote style="background: #f8d7da; padding: 10px; border-left: 5px solid #dc3545;">
                {{ $rejection_reason }}
            </blockquote>
        @endif

        <p>We understand this might be disappointing. You can still explore other books on BookShare.</p>

        <div style="text-align: center; margin-top: 30px;">
            <a href="{{ url('/books') }}" style="display: inline-block; padding: 10px 20px; background: #007bff; color: #fff; border-radius: 5px; text-decoration: none;">Browse More Books</a>
        </div>

        <p style="margin-top: 40px;">Thank you for using BookShare!</p>
        <p>Warm regards,<br>BookShare 📚 Team</p>
    </div>
</body>
</html>
