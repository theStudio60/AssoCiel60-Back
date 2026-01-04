<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Les commandes Artisan personnalisées de l'application
     */
    protected $commands = [
        Commands\AutoRenewSubscriptions::class,
        Commands\CheckSubscriptionExpiry::class,
        Commands\SendPaymentReminders::class,
    ];

    /**
     * Planification des tâches automatiques de l'application
     */
    protected function schedule(Schedule $schedule): void
    {
        // ====================================================================
        // 1. AUTO-RENEWAL - Renouvellement automatique des abonnements
        // Exécuté tous les jours à 02:00
        // Renouvelle les abonnements avec auto_renew=true qui expirent dans 7j
        // ====================================================================
        $schedule->command('subscriptions:auto-renew')
                 ->dailyAt('02:00')
                 ->timezone('Europe/Zurich')
                 ->appendOutputTo(storage_path('logs/auto-renew.log'));
        
        // ====================================================================
        // 2. EXPIRY CHECK - Vérification et alertes d'expiration
        // Exécuté tous les jours à 06:00
        // Envoie des alertes 30j et 7j avant expiration
        // Marque les abonnements expirés
        // ====================================================================
        $schedule->command('subscriptions:check-expiry')
                 ->dailyAt('06:00')
                 ->timezone('Europe/Zurich')
                 ->appendOutputTo(storage_path('logs/expiry-check.log'));
        
        // ====================================================================
        // 3. PAYMENT REMINDERS - Rappels de paiement automatiques
        // Exécuté tous les jours à 09:00
        // Envoie un email 7 jours avant l'échéance des factures impayées
        // ====================================================================
        $schedule->command('invoices:send-reminders')
                 ->dailyAt('09:00')
                 ->timezone('Europe/Zurich')
                 ->appendOutputTo(storage_path('logs/payment-reminders.log'));
        
        // ====================================================================
        // 4. INVOICES OVERDUE - Mise à jour du statut "en retard"
        // Exécuté tous les jours à 00:00 (minuit)
        // Change le statut des factures dont la date d'échéance est dépassée
        // ====================================================================
        $schedule->call(function () {
            $updated = \App\Models\Invoice::where('status', 'pending')
                ->whereDate('due_date', '<', now())
                ->update(['status' => 'overdue']);
                
            \Log::info("✅ {$updated} facture(s) marquée(s) en retard");
            
            // Logger l'activité
            if ($updated > 0) {
                \App\Models\ActivityLog::log(
                    'invoices_overdue_updated',
                    "{$updated} facture(s) marquée(s) en retard automatiquement",
                    \App\Models\Invoice::class,
                    null,
                    ['count' => $updated]
                );
            }
        })
        ->dailyAt('00:00')
        ->timezone('Europe/Zurich')
        ->name('update-overdue-invoices');
        
        // ====================================================================
        // 5. SUBSCRIPTIONS EXPIRY - Expiration automatique des abonnements
        // Exécuté tous les jours à 00:05 (5 min après minuit)
        // Change le statut des abonnements dont la date de fin est dépassée
        // ====================================================================
        $schedule->call(function () {
            $expired = \App\Models\Subscription::where('status', 'active')
                ->whereDate('end_date', '<', now())
                ->get();
            
            $count = 0;
            
            foreach ($expired as $subscription) {
                $subscription->update(['status' => 'expired']);
                
                // Logger chaque expiration
                \App\Models\ActivityLog::log(
                    'subscription_expired',
                    "Abonnement expiré pour {$subscription->organization->name}",
                    \App\Models\Subscription::class,
                    $subscription->id,
                    [
                        'plan' => $subscription->subscriptionPlan->name,
                        'end_date' => $subscription->end_date,
                    ]
                );
                
                $count++;
            }
            
            \Log::info("✅ {$count} abonnement(s) expiré(s)");
        })
        ->dailyAt('00:05')
        ->timezone('Europe/Zurich')
        ->name('expire-subscriptions');
        
        // ====================================================================
        // 6. ACTIVITY LOGS CLEANUP - Nettoyage automatique des vieux logs
        // Exécuté tous les dimanches à 03:00
        // Supprime les logs de plus de 90 jours pour optimiser la DB
        // ====================================================================
        $schedule->call(function () {
            $deleted = \App\Models\ActivityLog::where('created_at', '<', now()->subDays(90))
                ->delete();
                
            \Log::info("🗑️  {$deleted} log(s) d'activité supprimé(s) (>90 jours)");
        })
        ->weeklyOn(0, '03:00') // Dimanche à 03:00
        ->timezone('Europe/Zurich')
        ->name('cleanup-old-logs');
        
        // ====================================================================
        // 7. DATABASE BACKUP - Sauvegarde automatique de la base de données
        // Exécuté tous les jours à 04:00
        // Optionnel - Décommenter si backup configuré
        // ====================================================================
        // $schedule->command('backup:run')
        //          ->dailyAt('04:00')
        //          ->timezone('Europe/Zurich');
        
        // ====================================================================
        // 8. CACHE CLEANUP - Nettoyage du cache
        // Exécuté toutes les semaines le lundi à 05:00
        // ====================================================================
        $schedule->call(function () {
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');
            
            \Log::info('🧹 Cache nettoyé automatiquement');
        })
        ->weeklyOn(1, '05:00') // Lundi à 05:00
        ->timezone('Europe/Zurich')
        ->name('weekly-cache-cleanup');
    }

    /**
     * Enregistrement des commandes de l'application
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}