<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TwoFactorCode extends Mailable
{
    use Queueable, SerializesModels;

    public $code;
    public $userName;

    public function __construct($code, $userName)
    {
        $this->code = $code;
        $this->userName = $userName;
    }

    public function build()
    {
        return $this->subject('Votre code de vérification 2FA')
                    ->view('emails.two-factor-code');
    }
}