<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Contact Message</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f9f9f9; margin: 0; padding: 20px;">

    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <h2 style="color: #4B6BFB; text-align: center;">📩 New Contact Message</h2>

        <p><strong style="color: #333;">From:</strong> {{ $data['name'] }}</p>
        <p><strong style="color: #333;">Email:</strong> <a href="mailto:{{ $data['email'] }}">{{ $data['email'] }}</a></p>
        <p><strong style="color: #333;">Subject:</strong> {{ $data['subject'] }}</p>

        <hr style="margin: 20px 0;">

        <p style="color: #333;"><strong>Message:</strong></p>
        <div style="background-color: #f1f1f1; padding: 15px; border-radius: 6px; white-space: pre-wrap; color: #444;">
            {{ $data['message'] }}
        </div>

        <p style="margin-top: 30px; font-size: 13px; color: #999; text-align: center;">
            This message was sent from your website contact form.
        </p>
    </div>

</body>
</html>
