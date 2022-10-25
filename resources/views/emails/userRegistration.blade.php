<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Octagon Africa</title>
</head>
<body>
    <p>Hello, {{ $mailData['fullnames'] }}</p>

    <p>Thank you for registering with us.</p>
    <p>Please find your username and password below to access our member portal.</p>

    <p>Username: {{$mailData['username']}}</p>
    <p>Password: {{ $mailData['password'] }}</p>
    
    <p>Regards</p>
</body>
</html>
