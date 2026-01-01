<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $organization;

    public function __construct($user, $organization)
    {
        $this->user = $user;
        $this->organization = $organization;
    }

    public function build()
    {
        return $this->subject('Bienvenue chez Alprail')
                    ->view('emails.welcome');
    }
}