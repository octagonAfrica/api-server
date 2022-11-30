<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Octagon Africa - Password reset</title>
</head>
<body>
    <p>Hello, {{ $mailData['name'] }}</p>

    <p>You have requested a password change. Please on the below OTP to reset your password,</p>
    
    <p>Token: {{$mailData['token']}}</p>
    
    <p>Regards</p>
</body>
</html>
