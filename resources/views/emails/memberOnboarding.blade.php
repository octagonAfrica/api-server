<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Octagon Africa - Member Portal Login Credentials</title>
    @include('emails.header')
   Hello  {{ $mailData['user_full_names'] }}<br/>

   <p>Thank you for registering with us. Please find your username and password below to access our <a href="https://cloud.octagonafrica.com/crm/member_portal/">Member Portal</a></p>
    <ul>
        <li><p>Username: {{ $mailData['user_username'] }} </p></li>
        <li><p>Password: {{ $mailData['password'] }} '</p></li>
    </ul>
    <p>
        <a href='{{ $mailData['updateLink'] }}' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none;'>Update Your Profile</a>
    </p>

   @include('emails.footer')
