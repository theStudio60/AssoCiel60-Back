<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Réinitialisation mot de passe</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f8f9fa; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #3776c5 0%, #2d5fa3 100%); color: white; padding: 40px 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; }
        .content { padding: 40px 30px; }
        .content p { margin: 0 0 20px 0; font-size: 16px; color: #555; }
        .button-container { text-align: center; margin: 30px 0; }
        .button { display: inline-block; background: linear-gradient(135deg, #3776c5 0%, #2d5fa3 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; box-shadow: 0 4px 6px rgba(55, 118, 197, 0.3); }
        .button:hover { box-shadow: 0 6px 8px rgba(55, 118, 197, 0.4); }
        .info-box { background: #f8f9fa; padding: 20px; border-left: 4px solid #3776c5; margin: 20px 0; border-radius: 4px; }
        .footer { text-align: center; padding: 30px; color: #999; font-size: 13px; border-top: 1px solid #eee; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .warning p { margin: 0; color: #856404; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 ALPRAIL</h1>
            <p style="margin: 10px 0 0 0; font-size: 16px;">Réinitialisation de mot de passe</p>
        </div>
        
        <div class="content">
            <p>Bonjour <strong>{{ $userName }}</strong>,</p>
            
            <p>Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte ALPRAIL.</p>
            
            <p>Pour créer un nouveau mot de passe, cliquez sur le bouton ci-dessous :</p>
            
            <div class="button-container">
                <a href="{{ $resetUrl }}" class="button" style="color: #eee">
                    Réinitialiser mon mot de passe
                </a>
            </div>
            
            <div class="info-box">
                <p style="margin: 0; font-size: 14px;"><strong>Ce lien est valable pendant 60 minutes.</strong></p>
                <p style="margin: 10px 0 0 0; font-size: 13px; color: #666;">Après ce délai, vous devrez faire une nouvelle demande.</p>
            </div>
            
            <div class="warning">
                <p><strong>⚠️ Vous n'avez pas demandé cette réinitialisation ?</strong></p>
                <p style="margin: 5px 0 0 0;">Ignorez cet email. Votre mot de passe actuel reste inchangé.</p>
            </div>
            
            <p style="margin-top: 30px; font-size: 14px; color: #666;">
                Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :
            </p>
            <p style="font-size: 12px; color: #3776c5; word-break: break-all;">
                {{ $resetUrl }}
            </p>
        </div>
        
        <div class="footer">
            <p style="margin: 0 0 10px 0;">Merci de votre confiance !</p>
            <p style="margin: 0;">ALPRAIL - Route de Lausanne 1, 1000 Lausanne, Suisse</p>
            <p style="margin: 10px 0 0 0;">contact@alprail.net | +41 21 555 00 00</p>
        </div>
    </div>
</body>
</html>