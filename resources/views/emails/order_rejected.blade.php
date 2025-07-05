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
            <img src="{{ url('images/logo.png') }}" alt="BookShare" style="width: 100px; margin-bottom: 20px;">
        </div>

        {{-- Title --}}
        <h2 style="color: #dc3545; text-align: center;">Order Rejected ❌</h2>

        {{-- Body --}}
        <p>Dear {{ $client->name }},</p>

        <p>Unfortunately, your order has been rejected by the book owner.</p>

        <p><strong>Order Summary:</strong></p>
        <ul>
            <li><strong>Order ID:</strong> #{{ $order->id }}</li>
            <li><strong>Total Price:</strong> {{ $order->total_price }} EGP</li>
            <li><strong>Payment Method:</strong> {{ $order->payment_method }}</li>
            <li><strong>Order Date:</strong> {{ $order->created_at->format('Y-m-d H:i') }}</li>
        </ul>

        {{-- Book Titles --}}
        <p><strong>Books in your order:</strong></p>
        <ul>
            @foreach ($books as $book)
                <li>{{ $book->title }}</li>
            @endforeach
        </ul>

        {{-- Optional Reason --}}
        {{-- @if(!empty($rejection_reason))
            <p><strong>Reason for rejection:</strong></p>
            <blockquote style="background: #f8d7da; padding: 10px; border-left: 5px solid #dc3545;">
                {{ $rejection_reason }}
            </blockquote>
        @endif --}}

        <p>We understand this might be disappointing. You can still explore other books on BookShare.</p>


        <p style="margin-top: 40px;">Thank you for using BookShare!</p>
        <p>Warm regards,<br>BookShare 📚 Team</p>
    </div>
</body>
</html>
