<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Your Email</title>
</head>
<body style="background-color: #f4f4f4; font-family: 'Arial', sans-serif; padding: 40px 0; color: #333;">
    <div style="max-width: 600px; margin: auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1);">
        
        {{-- Logo --}}
        <div style="text-align: center;">
            <img src="{{ url('images/logo.png') }}" alt="BookShare" style="width: 100px; margin-bottom: 20px;">
        </div>

        {{-- Greeting --}}
        <h2 style="text-align: center; color: #007BFF;">Welcome to BookShare 📚</h2>
        <p style="text-align: center;">Hi {{ $user->name ?? 'there' }} 👋,</p>
        <p style="text-align: center;">Thank you for registering on BookShare. Please verify your email address to activate your account.</p>

        {{-- Button --}}
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $url }}" target="_blank" style="padding: 12px 24px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px;">
                Verify Email
            </a>
        </div>

        {{-- Extra Info --}}
        <p style="text-align: center; font-size: 14px; color: #999;">
            If you didn’t create an account, you can safely ignore this email.
        </p>

        {{-- Footer --}}
        <p style="text-align: center; margin-top: 40px;">With love 💚,<br>Team BookShare</p>
    </div>
</body>
</html>
