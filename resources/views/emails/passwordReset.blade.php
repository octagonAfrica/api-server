<!DOCTYPE html>

<html lang='en' xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:v='urn:schemas-microsoft-com:vml'>
<head>
<title>Octagon Africa - Password Reset</title>
    @include('emails.header')
    Hello, {{ $mailData['name'] }}<br>
    You have requested a password change. Please use the OTP below to reset your password,<br>
    Token: <strong>{{$mailData['token']}}</strong><br>
    @include('emails.footer')

