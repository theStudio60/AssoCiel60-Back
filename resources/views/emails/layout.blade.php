<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Alprail' }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #3776c5;
        }
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #3776c5;
        }
        .content {
            margin-bottom: 30px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #3776c5 0%, #2d5fa3 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin: 20px 0;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #3776c5;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #666;
        }
        h1 { color: #3776c5; margin-bottom: 20px; }
        h2 { color: #555; font-size: 20px; }
        .highlight { color: #3776c5; font-weight: bold; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">ALPRAIL</div>
        </div>
        
        <div class="content">
            @yield('content')
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Alprail. Tous droits réservés.</p>
            <p>Route de Lausanne 1, 1000 Lausanne, Suisse</p>
            <p>Email: contact@alprail.net | Tél: +41 21 555 00 00</p>
        </div>
    </div>
</body>
</html>