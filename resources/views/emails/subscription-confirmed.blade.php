@extends('emails.layout')

@section('content')
    <h1>Votre abonnement est confirmé ! ✅</h1>
    
    <p>Bonjour <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>
    
    <p>Nous avons le plaisir de vous confirmer l'activation de votre abonnement.</p>
    
    <div class="info-box">
        <h2>Détails de votre abonnement</h2>
        <p><strong>Pack :</strong> {{ $subscription->subscriptionPlan->name }}</p>
        <p><strong>Prix :</strong> CHF {{ $subscription->subscriptionPlan->price_chf }}</p>
        <p><strong>Date de début :</strong> {{ \Carbon\Carbon::parse($subscription->start_date)->format('d/m/Y') }}</p>
        <p><strong>Date de fin :</strong> {{ \Carbon\Carbon::parse($subscription->end_date)->format('d/m/Y') }}</p>
    </div>
    
    <center>
        <a href="{{ env('FRONTEND_URL') }}/member/subscription" class="button">Voir mon abonnement</a>
    </center>
    
    <p>Merci de votre confiance !</p>
    
    <p>Cordialement,<br><strong>L'équipe Alprail</strong></p>
@endsection