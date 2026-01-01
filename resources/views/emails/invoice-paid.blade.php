@extends('emails.layout')

@section('content')
    <h1>Paiement reçu ! 💚</h1>
    
    <p>Bonjour <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>
    
    <p>Nous vous confirmons la bonne réception de votre paiement.</p>
    
    <div class="info-box">
        <h2>Détails du paiement</h2>
        <p><strong>Facture :</strong> {{ $invoice->invoice_number }}</p>
        <p><strong>Montant payé :</strong> CHF {{ number_format($invoice->total_amount, 2) }}</p>
        <p><strong>Date de paiement :</strong> {{ \Carbon\Carbon::parse($invoice->paid_at)->format('d/m/Y') }}</p>
        <p><strong>Statut :</strong> <span class="highlight">Payée ✓</span></p>
    </div>
    
    <center>
        <a href="{{ env('FRONTEND_URL') }}/member/invoices" class="button">Télécharger ma facture</a>
    </center>
    
    <p>Merci de votre confiance !</p>
    
    <p>Cordialement,<br><strong>L'équipe Alprail</strong></p>
@endsection