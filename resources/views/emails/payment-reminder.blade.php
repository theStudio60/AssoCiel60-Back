@extends('emails.layout')

@section('content')
    <h1>Rappel de paiement ⏰</h1>
    
    <p>Bonjour <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>
    
    <p>Nous vous rappelons qu'une facture est en attente de paiement.</p>
    
    <div class="info-box">
        <h2>Détails de la facture</h2>
        <p><strong>Numéro :</strong> {{ $invoice->invoice_number }}</p>
        <p><strong>Montant :</strong> CHF {{ number_format($invoice->total_amount, 2) }}</p>
        <p><strong>Date d'échéance :</strong> <span class="highlight">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</span></p>
    </div>
    
    <center>
        <a href="{{ env('FRONTEND_URL') }}/member/invoices" class="button">Voir ma facture</a>
    </center>
    
    <p>Cordialement,<br><strong>L'équipe Alprail</strong></p>
@endsection