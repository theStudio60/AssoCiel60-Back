<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\User;
use App\Mail\PaymentReminderMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendPaymentReminders extends Command
{
    protected $signature = 'invoices:send-reminders';
    protected $description = 'Envoie des rappels automatiques pour les factures impayées';

    public function handle()
    {
        // Factures impayées avec échéance dans 7 jours
        $invoices = Invoice::with(['organization', 'subscription.subscriptionPlan'])
            ->where('status', 'pending')
            ->whereDate('due_date', '=', Carbon::now()->addDays(7)->toDateString())
            ->get();

        $sent = 0;

        foreach ($invoices as $invoice) {
            $user = User::where('organization_id', $invoice->organization_id)->first();
            
            if ($user) {
                try {
                    Mail::to($user->email)->send(new PaymentReminderMail(
                        $invoice,
                        $user,
                        $invoice->organization
                    ));
                    $sent++;
                } catch (\Exception $e) {
                    $this->error('Erreur envoi email pour facture ' . $invoice->invoice_number);
                }
            }
        }

        $this->info("$sent rappels envoyés avec succès !");
    }
}