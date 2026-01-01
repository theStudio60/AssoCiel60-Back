<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;
    public $user;
    public $organization;

    public function __construct($invoice, $user, $organization)
    {
        $this->invoice = $invoice;
        $this->user = $user;
        $this->organization = $organization;
    }

    public function build()
    {
        return $this->subject('Rappel de paiement - Facture ' . $this->invoice->invoice_number)
                    ->view('emails.payment-reminder');
    }
}