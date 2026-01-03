<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $user;

    public function __construct($token, $user)
    {
        $this->token = $token;
        $this->user = $user;
    }

    public function build()
    {
        $resetUrl = env('FRONTEND_URL') . '/reset-password?token=' . $this->token . '&email=' . urlencode($this->user->email);
        
        return $this->subject('Réinitialisation de votre mot de passe - ALPRAIL')
                    ->view('emails.password-reset')
                    ->with([
                        'resetUrl' => $resetUrl,
                        'userName' => $this->user->first_name . ' ' . $this->user->last_name,
                    ]);
    }
}