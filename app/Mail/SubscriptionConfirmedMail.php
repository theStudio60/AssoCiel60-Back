<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SubscriptionConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subscription;
    public $user;
    public $organization;

    public function __construct($subscription, $user, $organization)
    {
        $this->subscription = $subscription;
        $this->user = $user;
        $this->organization = $organization;
    }

    public function build()
    {
        return $this->subject('Votre adhésion est confirmée')
                    ->view('emails.subscription-confirmed');
    }
}