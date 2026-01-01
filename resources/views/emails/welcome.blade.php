@extends('emails.layout')

@section('content')
    <h1>Bienvenue chez Alprail ! 👋</h1>
    
    <p>Bonjour <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>
    
    <p>Nous sommes ravis de vous accueillir parmi nos membres !</p>
    
    <div class="info-box">
        <h2>Vos informations de compte</h2>
        <p><strong>Email :</strong> {{ $user->email }}</p>
        <p><strong>Organisation :</strong> {{ $organization->name }}</p>
        <p><strong>Adresse :</strong> {{ $organization->address }}, {{ $organization->zip_code }} {{ $organization->city }}</p>
    </div>
    
    <p>Vous pouvez dès maintenant accéder à votre espace membre.</p>
    
    <center>
        <a href="{{ env('FRONTEND_URL') }}/login" class="button">Accéder à mon espace</a>
    </center>
    
    <p>Cordialement,<br><strong>L'équipe Alprail</strong></p>
@endsection