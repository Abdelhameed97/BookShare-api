<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Accepted</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f2f2f2; padding: 20px; color: #333;">
    <div style="max-width: 600px; margin: auto; background: #ffffff; border-radius: 8px; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">

        {{-- Logo --}}
        <div style="text-align: center;">
            <img src="{{ url('images/logo.png') }}" alt="BookShare" style="width: 100px; margin-bottom: 20px;">
        </div>

        {{-- Greeting --}}
        <h2 style="color: #28a745; text-align: center;">Your Order Has Been Accepted 🎉</h2>

        {{-- Order Info --}}
        <p>Dear {{ $client->name }},</p>
        <p>We are happy to inform you that the owner has accepted your order!</p>

        <p><strong>Order Details:</strong></p>
        <ul>
            <li><strong>Order ID:</strong> #{{ $order->id }}</li>
            <li><strong>Book Title:</strong> {{ $book->title }}</li>
            <li><strong>Total Price:</strong> {{ $order->total_price }} EGP</li>
            <li><strong>Payment Method:</strong> {{ $order->payment_method }}</li>
            <li><strong>Order Date:</strong> {{ $order->created_at->format('Y-m-d H:i') }}</li>
        </ul>

        {{-- Owner Info --}}
        <p><strong>Owner Information:</strong></p>
        <ul>
            <li><strong>Name:</strong> {{ $owner->name }}</li>
            <li><strong>Email:</strong> {{ $owner->email }}</li>
            <li><strong>Phone Number:</strong> {{ $owner->phone_number }}</li>
            <li><strong>Location:</strong> {{ $owner->location }}</li>
        </ul>

        <p style="margin-top: 20px;">Please reach out to the owner to arrange delivery or pickup.</p>

        {{-- Footer --}}
        <p style="margin-top: 40px;">Best regards,<br>BookShare 📚 Team</p>
    </div>
</body>
</html>
