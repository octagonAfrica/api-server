<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Octagon Africa - Member Portal Login Credentials</title>
    @include('emails.header')
   Hello  {{ $mailData['user_full_names'] }}<br/>

   <p>Thank you for buying Jistawishe IPP from us. Please update your profile with the link below to get full accces to our products.
    <p>
        <a href='{{ $mailData['updateLink'] }}' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none;'>Update Your Profile</a>
    </p>

   @include('emails.footer')
