{{-- -------------------------------------------------------------------- --}}
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
            <img src="{{ asset('storage/logo.png') }}" alt="BookShare Logo" style="max-width: 120px; margin-bottom: 20px;">
        </div>

        {{-- Title --}}
        <h2 style="text-align: center; color: #007BFF;">New Order from {{ $client->name }}</h2>

        {{-- Order Info --}}
        <p><strong>Order Details:</strong></p>
        <ul>
            <li><strong>Book Title:</strong> {{ $book->title }}</li>
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

        {{-- Action Buttons --}}
        <div style="text-align: center; margin-top: 30px;">
            <form action="{{ url('/api/orders/' . $order->id . '/accept') }}" method="POST" style="display: inline-block; margin-right: 10px;">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <button type="submit" style="background-color: green; color: white; padding: 10px 20px; border: none; border-radius: 5px;">Accept</button>
            </form>

            <form action="{{ url('/api/orders/' . $order->id . '/reject') }}" method="POST" style="display: inline-block;">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <button type="submit" style="background-color: red; color: white; padding: 10px 20px; border: none; border-radius: 5px;">Reject</button>
            </form>
        </div>

        {{-- Footer --}}
        <p style="margin-top: 40px;">Best regards,<br>BookShare 📚 Team</p>
    </div>
</body>
</html>
