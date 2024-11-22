<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Octagon Africa - Benefits Claims</title>
    @include('emails.header')
   Dear  {{ $mailData['name'] }}<br/>

   Your member portal access token is<strong> {{ $mailData['randomString'] }}</strong> <br/>
   Use it to claim your benefits.<br/>
   Contact us at support@octagonafrica.com incase you experience any issues while attempting to log in.<br/>
   @include('emails.footer')

