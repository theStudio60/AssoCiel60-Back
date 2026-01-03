<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Abonnement Renouvelé</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #3776c5 0%, #2d5fa3 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #fff; padding: 30px; border: 1px solid #e0e0e0; }
        .info-box { background: #f8f9fa; padding: 15px; border-left: 4px solid #3776c5; margin: 20px 0; }
        .button { display: inline-block; background: linear-gradient(135deg, #3776c5 0%, #2d5fa3 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0;">ALPRAIL</h1>
            <p style="margin: 10px 0 0 0;">Renouvellement d'abonnement</p>
        </div>
        
        <div class="content">
            <h2>Bonjour {{ $user->first_name }},</h2>
            
            <p>Nous vous confirmons que votre abonnement <strong>{{ $subscription->subscriptionPlan->name }}</strong> a été renouvelé automatiquement.</p>
            
            <div class="info-box">
                <p style="margin: 0;"><strong>Détails du renouvellement :</strong></p>
                <ul style="margin: 10px 0;">
                    <li>Plan : {{ $subscription->subscriptionPlan->name }}</li>
                    <li>Nouvelle date de fin : {{ \Carbon\Carbon::parse($subscription->end_date)->format('d/m/Y') }}</li>
                    <li>Montant : {{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency }}</li>
                    <li>Numéro de facture : {{ $invoice->invoice_number }}</li>
                </ul>
            </div>
            
            <p>Une nouvelle facture a été générée et est disponible dans votre espace membre.</p>
            
            <center>
                <a href="{{ env('FRONTEND_URL') }}/member/invoices" class="button">
                    Voir ma facture
                </a>
            </center>
            
            <p style="margin-top: 30px; font-size: 14px; color: #666;">
                <strong>Note :</strong> Si vous souhaitez désactiver le renouvellement automatique, vous pouvez le faire dans votre espace membre.
            </p>
        </div>
        
        <div class="footer">
            <p>Merci de votre confiance !</p>
            <p>ALPRAIL - Route de Lausanne 1, 1000 Lausanne, Suisse</p>
            <p>contact@alprail.net | +41 21 555 00 00</p>
        </div>
    </div>
</body>
</html>