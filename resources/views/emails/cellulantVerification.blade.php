<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Octagon Africa - Contribution Verification</title>
    @include('emails.header')
   Dear  {{ $mailData['name'] }}<br/>
   Confirmed we have recieved your contribution of {{ $mailData['amount'] }}.<br/>
   Kindly wait as we proccess the transaction.<br/>
   Incase of any problems please contact your scheme administrator or support@octagonafrica.com<br/>
   @include('emails.footer')
