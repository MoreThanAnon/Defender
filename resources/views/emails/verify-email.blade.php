<!DOCTYPE html>
<html>
<head>
    <title>Verify Your Email Address</title>
</head>
<body>
    <p>Hello {{ $username }},</p>
    <p>Please verify your email address by clicking the link below:</p>
    <p><a href="{{ $verificationUrl }}">Verify Email</a></p>
    <p>Thank you!</p>
</body>
</html>
