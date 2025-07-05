<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Order Notification</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f8f9fa; padding: 20px; color: #333;">
    <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">

        {{-- Logo --}}
        <div style="text-align: center;">
            <img src="{{ url('images/logo.png') }}" alt="BookShare" style="width: 100px; margin-bottom: 20px;">
        </div>

        {{-- Title --}}
        <h2 style="text-align: center; color: #007BFF;">New Order from {{ $client->name }}</h2>

        {{-- Order Info --}}
        <p><strong>Order Details:</strong></p>
        <ul>
            <li><strong>Books:</strong>
                <ul>
                    @foreach ($books as $book)
                        <li>{{ $book->title }}</li>
                    @endforeach
                </ul>
            </li>
            <li><strong>Total Price:</strong> {{ $order->total_price }} EGP</li>
            <li><strong>Payment Method:</strong> {{ $order->payment_method }}</li>
            <li><strong>Order Date:</strong> {{ $order->created_at->format('Y-m-d H:i') }}</li>
        </ul>

        {{-- Client Info --}}
        <p><strong>Client Information:</strong></p>
        <ul>
            <li><strong>Name:</strong> {{ $client->name }}</li>
            <li><strong>Email:</strong> {{ $client->email }}</li>
            <li><strong>Phone Number:</strong> {{ $client->phone_number }}</li>
            <li><strong>Location:</strong> {{ $client->location }}</li>
        </ul>

        {{-- Footer --}}
        <p style="margin-top: 40px;">Best regards,<br>BookShare 📚 Team</p>
    </div>
</body>
</html>
