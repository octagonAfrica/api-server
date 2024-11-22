<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Octagon Africa - Profile Change</title>
    @include('emails.header')
        <p>Dear Team,</p>
        <p> A member of your scheme {{ $mailData['schemeCode'] }}  has updated his/her profile details</p>
        <p>Kindly login to the <a href="https://cloud.octagonafrica.com/crm/hr_portal/">HR portal</a> under member servicing to approve or reject this request.</p>
        <p>Contact us at support@octagonafrica.com OR +254709986000 in case you experience any questions</p>
   @include('emails.footer')
