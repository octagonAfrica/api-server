<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Octagon Africa - Profile Change</title>
    @include('emails.header')
   Dear  {{ $mailData['name'] }},<br/>
   <p>We have received your profile update request and have already forwarded it to your scheme human resource officer for review and approval.</p>
   <p>This process typically takes 5 working days. If you have any questions or need to request an expedited review, please contact your scheme human resource officer directly.</p>
   <p>We appreciate your patience and understanding.</p>
   <p>Contact us at support@octagonafrica.com OR +254709986000 in case you experience any questions</p>
   @include('emails.footer')

