<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification - BuyProtein</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #f53003;
        }
        .otp-code {
            background: #f53003;
            color: white;
            font-size: 32px;
            font-weight: bold;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin: 20px 0;
            letter-spacing: 5px;
        }
        .content {
            text-align: center;
            color: #333;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">BuyProtein</div>
            <p>Account Verification</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $userName }}!</h2>
            <p>Thank you for registering with BuyProtein. To complete your account setup, please use the following verification code:</p>
            
            <div class="otp-code">
                {{ $otpCode }}
            </div>
            
            <p>Enter this code on the verification page to activate your account.</p>
            
            <div class="warning">
                <strong>Important:</strong> This code will expire in 3 minutes for security reasons. If you didn't request this verification, please ignore this email.
            </div>
            
            <p>If you have any questions, feel free to contact our support team.</p>
        </div>
        
        <div class="footer">
            <p>This is an automated email from BuyProtein. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} BuyProtein. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
