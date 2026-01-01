<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; }
        .code { font-size: 32px; font-weight: bold; color: #3776c5; letter-spacing: 8px; text-align: center; margin: 30px 0; }
        .footer { color: #666; font-size: 12px; margin-top: 40px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h2 style="color: #3776c5;">Bonjour {{ $userName }},</h2>
        <p>Votre code de vérification 2FA est :</p>
        <div class="code">{{ $code }}</div>
        <p>Ce code expire dans <strong>10 minutes</strong>.</p>
        <p>Si vous n'avez pas demandé ce code, ignorez cet email.</p>
        <div class="footer">
            © 2026 Alprail. Tous droits réservés.
        </div>
    </div>
</body>
</html>