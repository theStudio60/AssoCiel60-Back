<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #3776c5; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 30px; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
        .button { display: inline-block; background: #3776c5; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⏰ Expiration d'abonnement</h1>
        </div>
        
        <div class="content">
            <p>Bonjour {{ $user->first_name }},</p>
            
            <div class="warning">
                <strong>⚠️ Votre abonnement expire dans {{ $days }} jours !</strong>
            </div>
            
            <p>Votre abonnement <strong>{{ $subscription->subscriptionPlan->name }}</strong> arrive à échéance le <strong>{{ $subscription->end_date->format('d/m/Y') }}</strong>.</p>
            
            <h3>📋 Détails de votre abonnement:</h3>
            <ul>
                <li><strong>Plan:</strong> {{ $subscription->subscriptionPlan->name }}</li>
                <li><strong>Date de fin:</strong> {{ $subscription->end_date->format('d/m/Y') }}</li>
                <li><strong>Prix:</strong> {{ $subscription->subscriptionPlan->price_chf }} CHF / an</li>
            </ul>
            
            <p>Pour éviter toute interruption de service, nous vous recommandons de renouveler votre abonnement dès maintenant.</p>
            
            @if($subscription->auto_renew)
                <p><strong>✅ Renouvellement automatique activé</strong> - Votre abonnement sera renouvelé automatiquement.</p>
            @else
                <p><strong>⚠️ Renouvellement manuel requis</strong> - Connectez-vous pour renouveler votre abonnement.</p>
                <a href="{{ env('FRONTEND_URL') }}/member/subscription" class="button">Renouveler maintenant</a>
            @endif
            
            <p style="margin-top: 30px;">Cordialement,<br><strong>L'équipe ALPRAIL</strong></p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} ALPRAIL. Tous droits réservés.</p>
            <p>Pour toute question: <a href="mailto:contact@alprail.net">contact@alprail.net</a></p>
        </div>
    </div>
</body>
</html>