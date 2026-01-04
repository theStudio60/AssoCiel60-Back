<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Subscription;
use App\Models\User;

class ExpiryWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subscription;
    public $user;
    public $days;

    public function __construct(Subscription $subscription, User $user, int $days)
    {
        $this->subscription = $subscription;
        $this->user = $user;
        $this->days = $days;
    }

    public function build()
    {
        return $this->subject("Votre abonnement expire dans {$this->days} jours")
                    ->view('emails.expiry-warning')
                    ->with([
                        'subscription' => $this->subscription,
                        'user' => $this->user,
                        'days' => $this->days,
                    ]);
    }
}