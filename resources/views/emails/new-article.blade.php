<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Nouvel article publié</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #3776c5 0%, #2d5fa3 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #fff; padding: 30px; border: 1px solid #e0e0e0; }
        .info-box { background: #f8f9fa; padding: 15px; border-left: 4px solid #3776c5; margin: 20px 0; }
        .button { display: inline-block; background: linear-gradient(135deg, #3776c5 0%, #2d5fa3 100%); color: #ffffff !important; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0;">ALPRAIL</h1>
            <p style="margin: 10px 0 0 0;">Nouvel article publié</p>
        </div>
        
        <div class="content">
            <h2>Bonjour {{ $user->first_name }},</h2>
            
            <p>Un nouvel article vient d'être publié sur le site Alprail.</p>
            
            <div class="info-box">
                <p style="margin: 0;"><strong>{{ $article['title'] }}</strong></p>
                @if(!empty($article['excerpt']))
                    <p style="margin: 10px 0 0 0; color: #555;">{{ $article['excerpt'] }}</p>
                @endif
                <p style="margin: 10px 0 0 0; font-size: 13px; color: #666;">
                    Publié le {{ \Carbon\Carbon::parse($article['date'])->format('d/m/Y') }}
                </p>
            </div>
            
            <center>
                <a href="{{ $article['url'] }}" class="button">
                    Lire l'article
                </a>
            </center>
            
            <p style="margin-top: 30px; font-size: 14px; color: #666;">
                Bonne lecture !
            </p>
        </div>
        
        <div class="footer">
            <p>Merci de votre confiance !</p>
            <p>ALPRAIL - 1200 Genève, Suisse</p>
            <p>contact@alprail.net | +41 21 555 00 00</p>
        </div>
    </div>
</body>
</html>