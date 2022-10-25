<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Octagon Africa</title>
</head>
<body>
    <p>Hello, {{ $mailData['name'] }}</p>

    <p>Kindly use the following otp to log in to your account.</p>
    
    <p>Otp: {{$mailData['otp']}}</p>
    
    <p>Regards</p>
</body>
</html>
