<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ====================================================================
        // EMAILS - Rappels de paiement automatiques
        // Exécuté tous les jours à 9h00
        // Envoie un email 7 jours avant l'échéance des factures impayées
        // ====================================================================
        $schedule->command('invoices:send-reminders')
                 ->dailyAt('09:00')
                 ->timezone('Europe/Zurich');
        
        // ====================================================================
        // FACTURES - Mise à jour du statut en "en retard"
        // Exécuté tous les jours à minuit
        // Change le statut des factures dont la date d'échéance est dépassée
        // ====================================================================
        $schedule->call(function () {
            \App\Models\Invoice::where('status', 'pending')
                ->whereDate('due_date', '<', now())
                ->update(['status' => 'overdue']);
                
            \Log::info('✅ Factures en retard mises à jour');
        })->dailyAt('00:00')->timezone('Europe/Zurich');
        
        // ====================================================================
        // ABONNEMENTS - Expiration automatique
        // Exécuté tous les jours à minuit
        // Change le statut des abonnements dont la date de fin est dépassée
        // ====================================================================
        $schedule->call(function () {
            $expired = \App\Models\Subscription::where('status', 'active')
                ->whereDate('end_date', '<', now())
                ->update(['status' => 'expired']);
                
            \Log::info("✅ {$expired} abonnements expirés");
        })->dailyAt('00:00')->timezone('Europe/Zurich');
        
        // ====================================================================
        // RENOUVELLEMENT AUTOMATIQUE - Abonnements avec auto_renew
        // Exécuté tous les jours à minuit
        // Renouvelle les abonnements 7 jours avant expiration si auto_renew = true
        // Génère une facture et envoie un email de confirmation
        // ====================================================================
        $schedule->call(function () {
            \Log::info('🔄 Début du processus de renouvellement automatique');
            
            // Récupérer les abonnements à renouveler
            $subscriptions = \App\Models\Subscription::with(['subscriptionPlan', 'organization'])
                ->where('auto_renew', true)
                ->where('status', 'active')
                ->whereDate('end_date', '=', now()->addDays(7)->toDateString())
                ->get();
                
            \Log::info("📊 {$subscriptions->count()} abonnement(s) à renouveler");
            
            $renewed = 0;
            $errors = 0;
            
            foreach ($subscriptions as $subscription) {
                try {
                    \DB::beginTransaction();
                    
                    // Sauvegarder l'ancienne date de fin
                    $oldEndDate = $subscription->end_date;
                    
                    // Renouveler l'abonnement pour 1 an
                    $subscription->update([
                        'end_date' => \Carbon\Carbon::parse($subscription->end_date)->addYear(),
                    ]);
                    
                    // Créer la facture de renouvellement
                    $invoice = \App\Models\Invoice::create([
                        'organization_id' => $subscription->organization_id,
                        'subscription_id' => $subscription->id,
                        'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . str_pad($subscription->organization_id, 5, '0', STR_PAD_LEFT),
                        'issue_date' => now(),
                        'due_date' => now()->addDays(30),
                        'amount' => $subscription->subscriptionPlan->price_chf,
                        'tax_amount' => 0,
                        'total_amount' => $subscription->subscriptionPlan->price_chf,
                        'currency' => 'CHF',
                        'status' => 'pending',
                    ]);
                    
                    // Logger l'activité
                    \App\Models\ActivityLog::log(
                        'auto_renewed',
                        "Abonnement {$subscription->subscriptionPlan->name} renouvelé automatiquement pour {$subscription->organization->name}",
                        \App\Models\Subscription::class,
                        $subscription->id,
                        [
                            'old_end_date' => $oldEndDate,
                            'new_end_date' => $subscription->end_date,
                            'invoice_id' => $invoice->id,
                            'amount' => $invoice->total_amount,
                        ]
                    );
                    
                    // Envoyer l'email de confirmation si activé
                    $user = \App\Models\User::where('organization_id', $subscription->organization_id)->first();
                    
                    if ($user) {
                        $emailEnabled = \App\Models\EmailSetting::get('subscription_enabled', true);
                        
                        if (filter_var($emailEnabled, FILTER_VALIDATE_BOOLEAN)) {
                            try {
                                \Mail::to($user->email)->send(
                                    new \App\Mail\SubscriptionRenewedMail(
                                        $subscription->fresh()->load('subscriptionPlan'), 
                                        $invoice, 
                                        $user
                                    )
                                );
                                \Log::info("📧 Email de renouvellement envoyé à {$user->email}");
                            } catch (\Exception $e) {
                                \Log::error("❌ Erreur envoi email: " . $e->getMessage());
                                // On continue même si l'email échoue
                            }
                        } else {
                            \Log::info("📧 Email de renouvellement désactivé - non envoyé");
                        }
                    }
                    
                    \DB::commit();
                    $renewed++;
                    
                    \Log::info("✅ Abonnement #{$subscription->id} renouvelé avec succès - Facture {$invoice->invoice_number} créée");
                    
                } catch (\Exception $e) {
                    \DB::rollBack();
                    $errors++;
                    \Log::error("❌ Erreur renouvellement abonnement #{$subscription->id}: " . $e->getMessage());
                }
            }
            
            \Log::info("🏁 Fin du processus: {$renewed} renouvelé(s), {$errors} erreur(s)");
            
        })->dailyAt('00:05')->timezone('Europe/Zurich'); // 00:05 pour être après l'expiration
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}