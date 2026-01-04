<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\ActivityLog;
use Carbon\Carbon;

class AutoRenewSubscriptions extends Command
{
    protected $signature = 'subscriptions:auto-renew';
    protected $description = 'Renouvelle automatiquement les abonnements avec auto_renew activé';

    public function handle()
    {
        $this->info('🔄 Vérification des abonnements à renouveler...');

        // Récupère les abonnements qui expirent dans les 7 prochains jours et ont auto_renew activé
        $subscriptions = Subscription::where('auto_renew', true)
            ->where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays(7)])
            ->with(['organization', 'subscriptionPlan'])
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('✅ Aucun abonnement à renouveler');
            return 0;
        }

        $renewed = 0;
        $errors = 0;

        foreach ($subscriptions as $subscription) {
            try {
                // Créer un nouvel abonnement
                $newSubscription = Subscription::create([
                    'organization_id' => $subscription->organization_id,
                    'subscription_plan_id' => $subscription->subscription_plan_id,
                    'start_date' => $subscription->end_date->addDay(),
                    'end_date' => $subscription->end_date->addYear(),
                    'status' => 'active',
                    'auto_renew' => $subscription->auto_renew,
                ]);

                // Marquer l'ancien comme expiré
                $subscription->update(['status' => 'expired']);

                // Créer une facture
                $plan = $subscription->subscriptionPlan;
                $invoice = Invoice::create([
                    'organization_id' => $subscription->organization_id,
                    'subscription_id' => $newSubscription->id,
                    'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . str_pad($subscription->organization_id, 5, '0', STR_PAD_LEFT),
                    'issue_date' => now(),
                    'due_date' => now()->addDays(30),
                    'amount' => $plan->price_chf,
                    'tax_amount' => 0,
                    'total_amount' => $plan->price_chf,
                    'currency' => 'CHF',
                    'status' => 'pending',
                ]);

                // Log
                ActivityLog::log(
                    'subscription_auto_renewed',
                    "Abonnement renouvelé automatiquement pour {$subscription->organization->name}",
                    Subscription::class,
                    $newSubscription->id,
                    [
                        'old_subscription_id' => $subscription->id,
                        'invoice_id' => $invoice->id,
                    ]
                );

                // Envoyer email de renouvellement
                $user = \App\Models\User::where('organization_id', $subscription->organization_id)->first();
                if ($user) {
                    try {
                        \Mail::to($user->email)->send(
                            new \App\Mail\SubscriptionConfirmationMail($newSubscription->load('subscriptionPlan'), $user)
                        );
                    } catch (\Exception $e) {
                        $this->warn("⚠️  Email non envoyé pour {$user->email}: {$e->getMessage()}");
                    }
                }

                $renewed++;
                $this->info("✅ Abonnement renouvelé: {$subscription->organization->name}");

            } catch (\Exception $e) {
                $errors++;
                $this->error("❌ Erreur pour {$subscription->organization->name}: {$e->getMessage()}");
            }
        }

        $this->info("\n📊 Résumé:");
        $this->info("✅ {$renewed} abonnements renouvelés");
        if ($errors > 0) {
            $this->warn("⚠️  {$errors} erreurs");
        }

        return 0;
    }
}