<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guaranteed Financial Security</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background-color: #007bff;
            color: white;
            text-align: center;
            padding: 20px;
        }
        .header h2 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .card-body {
            padding: 20px;
        }
        .list-group-item {
            background-color: #f4f4f4;
            border: none;
            padding: 10px 15px;
            margin-bottom: 5px;
            border-radius: 5px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 14px;
            background-color: #007bff;
            color: white;
        }
        .footer p {
            margin: 0;
        }
        .btn-social {
            background-color: #fff;
            border: 1px solid #007bff;
            color: #007bff;
            margin: 5px;
            border-radius: 30px;
            padding: 8px 15px;
            text-transform: uppercase;
            font-weight: bold;
            display: inline-block;
        }
        .btn-social:hover {
            background-color: #007bff;
            color: white;
        }
        .contact-info {
            font-size: 14px;
            color: #555;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Logo -->
        <div class="header">
            <img style="width: 150px;" src="https://cloud.octagonafrica.com/opas/commons/OctagonMail/images/Artboard_1_copy_3.png" alt="Octagon Africa Logo">
            <h2>Guaranteed Financial Security</h2>
        </div>

        <!-- Email Body -->
        <div class="card-body">
            <p>Hello, <strong>{{ $mailData['fullnames'] }}</strong>,</p>
            <p>Thank you for registering with us! We're excited to have you as part of our community.</p>
            <p>Your login credentials will be shared shortly by the Scheme Administrator of your scheme.</p>
            <p>If you have any questions, feel free to reach out to our support team.</p>
            <p>Sincerely,</p>
            <p><strong>Octagon Africa</strong></p>
        </div>

        <!-- Footer with Social Links -->
        <div class="footer">
            <p>Stay connected with us:</p>
            <a href="https://www.facebook.com/OctagonAfrica" class="btn-social">Facebook</a>
            <a href="https://x.com/OctagonAfrica" class="btn-social">Twitter</a>
            <a href="https://www.instagram.com/octagonafrica/" class="btn-social">Instagram</a>
            <a href="https://www.youtube.com/@OctagonAfricaGroup" class="btn-social">YouTube</a>
            <a href="https://www.linkedin.com/company/octagon-pension-services-ltd/mycompany/" class="btn-social">LinkedIn</a>
            
            <!-- Contact Information -->
            <div class="contact-info">
                <p>Email: <a href="mailto:support@octagonafrica.com" style="color: white;">support@octagonafrica.com</a></p>
               
                <p>Website:<a href="https://www.octagonafrica.com/" target="_blank" style="color: white;">www.octagonafrica.com</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

